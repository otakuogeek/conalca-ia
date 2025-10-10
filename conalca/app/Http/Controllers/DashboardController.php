<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CotizacionModel;
use App\Models\SolicitudTransporte;
use App\Models\Appointment;
use App\Models\VehicleOwnerHolderDriver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show()
    {
        // Obtener datos reales del mes actual y anterior
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $today = Carbon::today();

        // === MÉTRICAS PRINCIPALES ===
        
        // Ingresos totales (basado en cotizaciones con valor)
        $currentMonthRevenue = CotizacionModel::where('active', 1)
            ->whereNotNull('valor')
            ->where('created_at', '>=', $currentMonth)
            ->sum('valor');
            
        $previousMonthRevenue = CotizacionModel::where('active', 1)
            ->whereNotNull('valor')
            ->where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->sum('valor');

        // Solicitudes en proceso (más útil que gastos operativos estimados)
        $currentMonthInProcess = SolicitudTransporte::where('created_at', '>=', $currentMonth)
            ->where('estado', 'en_proceso')
            ->count();
            
        $previousMonthInProcess = SolicitudTransporte::where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->where('estado', 'en_proceso')
            ->count();

        // Clientes activos (clientes con cotizaciones o solicitudes en el mes)
        $activeClients = Client::whereHas('cotizaciones', function($query) use ($currentMonth) {
            $query->where('created_at', '>=', $currentMonth)
                  ->where('active', 1);
        })->orWhereHas('solicitud_transportes', function($query) use ($currentMonth) {
            $query->where('created_at', '>=', $currentMonth);
        })->count();

        $previousActiveClients = Client::whereHas('cotizaciones', function($query) use ($previousMonth, $currentMonth) {
            $query->where('created_at', '>=', $previousMonth)
                  ->where('created_at', '<', $currentMonth)
                  ->where('active', 1);
        })->orWhereHas('solicitud_transportes', function($query) use ($previousMonth, $currentMonth) {
            $query->where('created_at', '>=', $previousMonth)
                  ->where('created_at', '<', $currentMonth);
        })->count();

        // Proyectos completados (solicitudes de transporte completadas)
        $completedProjects = SolicitudTransporte::where('created_at', '>=', $currentMonth)
            ->where('estado', 'completado')
            ->count();
            
        $previousCompletedProjects = SolicitudTransporte::where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->where('estado', 'completado')
            ->count();

        // === DATOS PARA GRÁFICOS ===
        
        // Datos para comparación anual (año actual vs año anterior)
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;
        $currentMonth = Carbon::now()->month; // Mes actual para limitar los datos
        
        $yearlyComparison = [];
        
        // Verificar si hay cotizaciones en la base de datos
        $totalCotizaciones = CotizacionModel::where('active', 1)->count();
        
        // SOLO generar datos si hay cotizaciones reales
        if ($totalCotizaciones > 0) {
            \Log::info("=== GENERANDO DATOS PARA GRÁFICO ===");
            \Log::info("Total cotizaciones encontradas: {$totalCotizaciones}");
            
            // Generar datos solo hasta el mes actual
            for ($month = 1; $month <= $currentMonth; $month++) {
                // Año actual
                $currentYearMonth = Carbon::create($currentYear, $month, 1);
                $currentMonthStart = $currentYearMonth->startOfMonth()->copy();
                $currentMonthEnd = $currentYearMonth->endOfMonth()->copy();
                
                $currentYearRevenue = CotizacionModel::where('active', 1)
                    ->whereNotNull('valor')
                    ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                    ->sum('valor');
                
                // Año anterior
                $previousYearMonth = Carbon::create($previousYear, $month, 1);
                $previousMonthStart = $previousYearMonth->startOfMonth()->copy();
                $previousMonthEnd = $previousYearMonth->endOfMonth()->copy();
                
                $previousYearRevenue = CotizacionModel::where('active', 1)
                    ->whereNotNull('valor')
                    ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
                    ->sum('valor');

                // AGREGAR TODOS LOS MESES - mostrar datos que tengamos
                $yearlyComparison[] = [
                    'month' => $currentYearMonth->format('M'),
                    'month_name' => $currentYearMonth->locale('es')->format('F'),
                    'current_year' => $currentYear,
                    'previous_year' => $previousYear,
                    'current_year_revenue' => floatval($currentYearRevenue),
                    'previous_year_revenue' => floatval($previousYearRevenue),
                    'difference' => floatval($currentYearRevenue - $previousYearRevenue),
                    'percentage_change' => $previousYearRevenue > 0 ? 
                        (($currentYearRevenue - $previousYearRevenue) / $previousYearRevenue) * 100 : 0
                ];
                
                \Log::info("Mes {$month}: Actual={$currentYearRevenue}, Anterior={$previousYearRevenue}");
            }
            
            \Log::info("Total meses generados: " . count($yearlyComparison));
            \Log::info("DATOS COMPLETOS:", $yearlyComparison);
        } else {
            \Log::info("NO HAY COTIZACIONES - Array vacío");
        }
        
        // Datos mensuales para vista general (basados en yearlyComparison)
        $monthlyData = [];
        foreach ($yearlyComparison as $monthData) {
            $monthlyData[] = [
                'month' => $monthData['month'],
                'month_short' => substr($monthData['month'], 0, 3),
                'revenue' => floatval($monthData['current_year_revenue']),
                'trips' => 0, // Puedes agregar este campo después si lo necesitas
                'profit' => floatval($monthData['current_year_revenue'])
            ];
        }

        // Appointments actuales y próximos del calendario
        $appointments = Appointment::where(function($query) use ($today) {
                // Eventos de hoy y próximos 30 días
                $query->where('start_date', '>=', $today)
                      ->where('start_date', '<=', $today->copy()->addDays(30));
            })
            ->orWhere(function($query) use ($today) {
                // Eventos en curso (que empezaron antes pero terminan hoy o después)
                $query->where('start_date', '<=', $today)
                      ->where('end_date', '>=', $today);
            })
            ->orWhere(function($query) use ($today) {
                // Tareas pendientes (no completadas)
                $query->where('item_type', 'task')
                      ->where('completed', false)
                      ->where('start_date', '>=', $today->copy()->subDays(7)); // Incluir tareas de la última semana
            })
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(15)
            ->get();

        // === CÁLCULOS DE PORCENTAJES ===
        
        // Calcular porcentajes de cambio
        $revenueChangePercent = $previousMonthRevenue > 0 
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 
            : 0;
            
        $inProcessChangePercent = $previousMonthInProcess > 0 
            ? (($currentMonthInProcess - $previousMonthInProcess) / $previousMonthInProcess) * 100 
            : 0;
            
        $clientsChangePercent = $previousActiveClients > 0 
            ? (($activeClients - $previousActiveClients) / $previousActiveClients) * 100 
            : 0;
            
        $projectsChangePercent = $previousCompletedProjects > 0 
            ? (($completedProjects - $previousCompletedProjects) / $previousCompletedProjects) * 100 
            : 0;

        // === MÉTRICAS ADICIONALES ===
        
        // Top 5 clientes por valor de cotizaciones
        $topClients = Client::with(['cotizaciones' => function($query) use ($currentMonth) {
            $query->where('active', 1)
                  ->where('created_at', '>=', $currentMonth)
                  ->whereNotNull('valor');
        }])
        ->get()
        ->map(function($client) {
            $totalValue = $client->cotizaciones->sum('valor');
            return [
                'name' => $client->cliente ?: $client->name ?: 'Cliente Sin Nombre',
                'value' => $totalValue,
                'cotizations_count' => $client->cotizaciones->count()
            ];
        })
        ->filter(function($client) {
            return $client['value'] > 0;
        })
        ->sortByDesc('value')
        ->take(5)
        ->values();

        // Estados de solicitudes de transporte
        $transportStates = SolicitudTransporte::where('created_at', '>=', $currentMonth)
            ->select('estado', DB::raw('count(*) as count'))
            ->groupBy('estado')
            ->pluck('count', 'estado')
            ->toArray();

        return view('dashboardChart.show', compact(
            'appointments',
            'currentMonthRevenue',
            'previousMonthRevenue', 
            'revenueChangePercent',
            'currentMonthInProcess',
            'previousMonthInProcess',
            'inProcessChangePercent',
            'activeClients',
            'previousActiveClients',
            'clientsChangePercent',
            'completedProjects',
            'previousCompletedProjects',
            'projectsChangePercent',
            'monthlyData',
            'yearlyComparison',
            'currentYear',
            'previousYear',
            'topClients',
            'transportStates'
        ));
    }

    /**
     * API endpoint para datos del gráfico
     */
    public function getChartData(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $previousYear = $year - 1;
        
        // Si es el año actual, limitar hasta el mes actual
        // Si es un año pasado, mostrar todos los 12 meses
        $maxMonth = ($year == Carbon::now()->year) ? Carbon::now()->month : 12;
        
        $yearlyComparison = [];
        
        for ($month = 1; $month <= $maxMonth; $month++) {
            // Año seleccionado
            $currentYearMonth = Carbon::create($year, $month, 1);
            $currentMonthStart = $currentYearMonth->startOfMonth()->copy();
            $currentMonthEnd = $currentYearMonth->endOfMonth()->copy();
            
            $currentYearRevenue = CotizacionModel::where('active', 1)
                ->whereNotNull('valor')
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('valor');
            
            // Año anterior
            $previousYearMonth = Carbon::create($previousYear, $month, 1);
            $previousMonthStart = $previousYearMonth->startOfMonth()->copy();
            $previousMonthEnd = $previousYearMonth->endOfMonth()->copy();
            
            $previousYearRevenue = CotizacionModel::where('active', 1)
                ->whereNotNull('valor')
                ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
                ->sum('valor');
            
            $yearlyComparison[] = [
                'month' => $currentYearMonth->format('M'),
                'month_name' => $currentYearMonth->locale('es')->format('F'),
                'current_year' => intval($year),
                'previous_year' => intval($previousYear),
                'current_year_revenue' => floatval($currentYearRevenue),
                'previous_year_revenue' => floatval($previousYearRevenue),
                'difference' => floatval($currentYearRevenue - $previousYearRevenue),
                'percentage_change' => $previousYearRevenue > 0 ? 
                    (($currentYearRevenue - $previousYearRevenue) / $previousYearRevenue) * 100 : 0
            ];
        }

        return response()->json([
            'yearly_comparison' => $yearlyComparison,
            'current_year' => intval($year),
            'previous_year' => intval($previousYear),
            'max_month' => $maxMonth
        ]);
    }
}
