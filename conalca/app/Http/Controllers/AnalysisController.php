<?php

namespace App\Http\Controllers;

use App\Models\GroupCotization;
use App\Models\CotizacionModel;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalysisController extends Controller
{
    public function show()
    {
        \Log::info('AnalysisController::show() called');
        
        try {
            // Obtener datos para el dashboard de análisis
            $data = $this->getAnalysisData();
            \Log::info('Data obtained:', $data);
            
            return view('analysis.show', $data);
        } catch (\Exception $e) {
            \Log::error('Error in AnalysisController::show(): ' . $e->getMessage());
            
            // Retornar datos por defecto en caso de error
            $defaultData = [
                'totalCotizations' => 0,
                'totalClients' => 0,
                'activeCotizations' => 0,
                'transportTypeDistribution' => collect([]),
                'monthlyAnalysis' => collect([]),
                'statusDistribution' => collect([]),
                'topOriginCities' => collect([]),
                'topDestinationCities' => collect([]),
                'vehicleTypesDistribution' => collect([]),
                'acceptanceRate' => 0,
                'averageResponseTime' => 0,
                'monthlyChartData' => [
                    'months' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    'cotizations' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'efficiency' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                ],
            ];
            
            return view('analysis.show', $defaultData);
        }
    }

    private function getAnalysisData()
    {
        // 1. Estadísticas generales
        $totalCotizations = GroupCotization::count();
        $totalClients = Client::count();
        $totalCotizationModels = CotizacionModel::count();
        $activeCotizations = GroupCotization::where('status', 'activa')->count();

        // 2. Distribución por tipo de transporte desde cotizacion_models
        $transportTypeDistribution = CotizacionModel::select('tipo', DB::raw('count(*) as count'))
            ->whereNotNull('tipo')
            ->groupBy('tipo')
            ->get();

        // 3. Distribución por tipo de vehículo requerido
        $vehicleTypeDistribution = CotizacionModel::select('vehiculo_requerido', DB::raw('count(*) as count'))
            ->whereNotNull('vehiculo_requerido')
            ->groupBy('vehiculo_requerido')
            ->orderBy('count', 'desc')
            ->get();

        // 4. Distribución por tipo de carrocería
        $bodyTypeDistribution = CotizacionModel::select('tipo_carroceria', DB::raw('count(*) as count'))
            ->whereNotNull('tipo_carroceria')
            ->groupBy('tipo_carroceria')
            ->orderBy('count', 'desc')
            ->get();

        // 5. Análisis mensual de cotizaciones de los últimos 12 meses
        $monthlyAnalysis = CotizacionModel::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_cotizations'),
                DB::raw('AVG(CASE WHEN valor IS NOT NULL THEN valor ELSE 0 END) as avg_value'),
                DB::raw('SUM(CASE WHEN valor IS NOT NULL THEN valor ELSE 0 END) as total_value')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // 6. Distribución por status de group_cotizations
        $statusDistribution = GroupCotization::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // 7. Top ciudades origen y destino más frecuentes
        $topOriginCities = CotizacionModel::select('ciudad_origen', DB::raw('count(*) as count'))
            ->whereNotNull('ciudad_origen')
            ->where('ciudad_origen', '!=', '')
            ->groupBy('ciudad_origen')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $topDestinationCities = CotizacionModel::select('ciudad_destino', DB::raw('count(*) as count'))
            ->whereNotNull('ciudad_destino')
            ->where('ciudad_destino', '!=', '')
            ->groupBy('ciudad_destino')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // 8. Análisis de valores económicos
        $economicAnalysis = CotizacionModel::select(
                DB::raw('COUNT(*) as total_cotizations'),
                DB::raw('AVG(CASE WHEN valor IS NOT NULL AND valor > 0 THEN valor ELSE NULL END) as avg_value'),
                DB::raw('MAX(CASE WHEN valor IS NOT NULL THEN valor ELSE 0 END) as max_value'),
                DB::raw('MIN(CASE WHEN valor IS NOT NULL AND valor > 0 THEN valor ELSE NULL END) as min_value'),
                DB::raw('SUM(CASE WHEN valor IS NOT NULL THEN valor ELSE 0 END) as total_value')
            )
            ->first();

        // 9. Análisis de peso de mercancía
        $weightAnalysis = CotizacionModel::select(
                DB::raw('AVG(CASE WHEN peso_mercancia IS NOT NULL AND peso_mercancia > 0 THEN peso_mercancia ELSE NULL END) as avg_weight'),
                DB::raw('MAX(CASE WHEN peso_mercancia IS NOT NULL THEN peso_mercancia ELSE 0 END) as max_weight'),
                DB::raw('SUM(CASE WHEN peso_mercancia IS NOT NULL THEN peso_mercancia ELSE 0 END) as total_weight')
            )
            ->first();

        // 10. Distribución por tipo de producto
        $productTypeDistribution = CotizacionModel::select('tipo_producto', DB::raw('count(*) as count'))
            ->whereNotNull('tipo_producto')
            ->where('tipo_producto', '!=', '')
            ->groupBy('tipo_producto')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // 11. Métricas de rendimiento mejoradas
        $acceptanceRate = $this->calculateAcceptanceRate();
        $averageResponseTime = $this->calculateAverageResponseTime();

        // 12. Datos para gráficos mensuales (últimos 12 meses)
        $monthlyChartData = $this->getMonthlyChartData();

        // 13. Análisis de rutas más comunes (origen-destino)
        $commonRoutes = CotizacionModel::select(
                'ciudad_origen',
                'ciudad_destino',
                DB::raw('count(*) as count'),
                DB::raw('AVG(CASE WHEN valor IS NOT NULL AND valor > 0 THEN valor ELSE NULL END) as avg_value')
            )
            ->whereNotNull('ciudad_origen')
            ->whereNotNull('ciudad_destino')
            ->where('ciudad_origen', '!=', '')
            ->where('ciudad_destino', '!=', '')
            ->groupBy('ciudad_origen', 'ciudad_destino')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // 14. Datos para el mapa - ciudades con coordenadas estimadas
        $mapData = $this->getMapData();

        return [
            'totalCotizations' => $totalCotizations,
            'totalClients' => $totalClients,
            'totalCotizationModels' => $totalCotizationModels,
            'activeCotizations' => $activeCotizations,
            'transportTypeDistribution' => $transportTypeDistribution,
            'vehicleTypeDistribution' => $vehicleTypeDistribution,
            'bodyTypeDistribution' => $bodyTypeDistribution,
            'monthlyAnalysis' => $monthlyAnalysis,
            'statusDistribution' => $statusDistribution,
            'topOriginCities' => $topOriginCities,
            'topDestinationCities' => $topDestinationCities,
            'economicAnalysis' => $economicAnalysis,
            'weightAnalysis' => $weightAnalysis,
            'productTypeDistribution' => $productTypeDistribution,
            'acceptanceRate' => $acceptanceRate,
            'averageResponseTime' => $averageResponseTime,
            'monthlyChartData' => $monthlyChartData,
            'commonRoutes' => $commonRoutes,
            'mapData' => $mapData,
        ];
    }

    // Endpoint de debug para verificar datos de ciudades
    public function debugCities()
    {
        try {
            $originCities = CotizacionModel::select('ciudad_origen')
                ->whereNotNull('ciudad_origen')
                ->where('ciudad_origen', '!=', '')
                ->distinct()
                ->limit(10)
                ->pluck('ciudad_origen')
                ->toArray();

            $destinationCities = CotizacionModel::select('ciudad_destino')
                ->whereNotNull('ciudad_destino')
                ->where('ciudad_destino', '!=', '')
                ->distinct()
                ->limit(10)
                ->pluck('ciudad_destino')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'origin_cities' => $originCities,
                    'destination_cities' => $destinationCities,
                    'total_cotizations' => CotizacionModel::count(),
                    'cotizations_with_origin' => CotizacionModel::whereNotNull('ciudad_origen')->count(),
                    'cotizations_with_destination' => CotizacionModel::whereNotNull('ciudad_destino')->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Endpoint específico para datos del mapa
    public function getMapDataEndpoint()
    {
        try {
            $mapData = $this->getMapData();
            
            return response()->json([
                'success' => true,
                'data' => $mapData,
                'debug' => 'Datos reales del mapa desde la base de datos'
            ]);
        } catch (\Exception $e) {
            // Datos de fallback en caso de error
            $fallbackData = [
                'cities' => [
                    ['name' => 'Bogotá', 'lat' => 4.7110, 'lng' => -74.0721, 'type' => 'both', 'count' => 5],
                    ['name' => 'Medellín', 'lat' => 6.2442, 'lng' => -75.5812, 'type' => 'origin', 'count' => 3]
                ],
                'routes' => [
                    [
                        'name' => 'Bogotá - Medellín',
                        'originCoords' => [4.7110, -74.0721],
                        'destinationCoords' => [6.2442, -75.5812],
                        'count' => 2
                    ]
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $fallbackData,
                'error' => $e->getMessage(),
                'debug' => 'Datos de fallback por error en BD'
            ]);
        }
    }

    private function getMapData()
    {
        // Obtener todas las ciudades únicas de origen y destino con sus frecuencias
        $originCities = CotizacionModel::select('ciudad_origen as city', DB::raw('count(*) as count'))
            ->whereNotNull('ciudad_origen')
            ->where('ciudad_origen', '!=', '')
            ->groupBy('ciudad_origen')
            ->get();

        $destinationCities = CotizacionModel::select('ciudad_destino as city', DB::raw('count(*) as count'))
            ->whereNotNull('ciudad_destino')
            ->where('ciudad_destino', '!=', '')
            ->groupBy('ciudad_destino')
            ->get();

        // Combinar y agregar coordenadas conocidas de ciudades colombianas
        $colombianCities = [
            'bogotá' => ['lat' => 4.7110, 'lng' => -74.0721],
            'bogota' => ['lat' => 4.7110, 'lng' => -74.0721],
            'medellín' => ['lat' => 6.2442, 'lng' => -75.5812],
            'medellin' => ['lat' => 6.2442, 'lng' => -75.5812],
            'cali' => ['lat' => 3.4516, 'lng' => -76.5320],
            'barranquilla' => ['lat' => 10.9639, 'lng' => -74.7964],
            'cartagena' => ['lat' => 10.3932, 'lng' => -75.4832],
            'bucaramanga' => ['lat' => 7.1253, 'lng' => -73.1198],
            'pereira' => ['lat' => 4.8133, 'lng' => -75.6961],
            'santa marta' => ['lat' => 11.2408, 'lng' => -74.1990],
            'manizales' => ['lat' => 5.0700, 'lng' => -75.5138],
            'villavicencio' => ['lat' => 4.1420, 'lng' => -73.6266],
            'ibagué' => ['lat' => 4.4389, 'lng' => -75.2322],
            'ibague' => ['lat' => 4.4389, 'lng' => -75.2322],
            'cúcuta' => ['lat' => 7.8939, 'lng' => -72.5078],
            'cucuta' => ['lat' => 7.8939, 'lng' => -72.5078],
            'pasto' => ['lat' => 1.2136, 'lng' => -77.2811],
            'armenia' => ['lat' => 4.5339, 'lng' => -75.6811],
            'neiva' => ['lat' => 2.9273, 'lng' => -75.2819],
            'popayán' => ['lat' => 2.4448, 'lng' => -76.6147],
            'popayan' => ['lat' => 2.4448, 'lng' => -76.6147],
            'valledupar' => ['lat' => 10.4631, 'lng' => -73.2532],
            'montería' => ['lat' => 8.7479, 'lng' => -75.8814],
            'monteria' => ['lat' => 8.7479, 'lng' => -75.8814],
            'sincelejo' => ['lat' => 9.3047, 'lng' => -75.3978],
            'tunja' => ['lat' => 5.5353, 'lng' => -73.3678],
            'florencia' => ['lat' => 1.6144, 'lng' => -75.6062],
            'riohacha' => ['lat' => 11.5444, 'lng' => -72.9072],
            'yopal' => ['lat' => 5.3375, 'lng' => -72.3958],
            'mocoa' => ['lat' => 1.1522, 'lng' => -76.6511],
            'san andrés' => ['lat' => 12.5847, 'lng' => -81.7006],
            'san andres' => ['lat' => 12.5847, 'lng' => -81.7006],
            'leticia' => ['lat' => -4.2151, 'lng' => -69.9406],
            'mitú' => ['lat' => 1.2517, 'lng' => -70.1733],
            'mitu' => ['lat' => 1.2517, 'lng' => -70.1733],
            'inírida' => ['lat' => 3.8653, 'lng' => -67.9239],
            'inirida' => ['lat' => 3.8653, 'lng' => -67.9239],
            'puerto carreño' => ['lat' => 6.1892, 'lng' => -67.4856],
            'puerto carreno' => ['lat' => 6.1892, 'lng' => -67.4856],
            // Ciudades adicionales encontradas en la BD
            'funza' => ['lat' => 4.7167, 'lng' => -74.2167], // Funza está cerca de Bogotá
        ];

        // Procesar ciudades con coordenadas
        $allCities = collect();
        
        foreach ($originCities as $city) {
            $cityName = strtolower(trim($city->city)); // Convertir a minúsculas
            if (isset($colombianCities[$cityName])) {
                $allCities->push([
                    'name' => ucwords($cityName), // Mostrar con primera letra mayúscula
                    'lat' => $colombianCities[$cityName]['lat'],
                    'lng' => $colombianCities[$cityName]['lng'],
                    'count' => $city->count,
                    'type' => 'origin'
                ]);
            }
        }

        foreach ($destinationCities as $city) {
            $cityName = strtolower(trim($city->city)); // Convertir a minúsculas
            if (isset($colombianCities[$cityName])) {
                $existing = $allCities->firstWhere('name', ucwords($cityName));
                if ($existing) {
                    // Agregar el conteo de destino al existente
                    $key = $allCities->search(function ($item) use ($cityName) {
                        return strtolower($item['name']) === $cityName;
                    });
                    $allCities[$key]['count'] += $city->count;
                    $allCities[$key]['type'] = 'both';
                } else {
                    $allCities->push([
                        'name' => ucwords($cityName), // Mostrar con primera letra mayúscula
                        'lat' => $colombianCities[$cityName]['lat'],
                        'lng' => $colombianCities[$cityName]['lng'],
                        'count' => $city->count,
                        'type' => 'destination'
                    ]);
                }
            }
        }

        // Obtener rutas reales (origen-destino)
        $realRoutes = CotizacionModel::select('ciudad_origen', 'ciudad_destino', DB::raw('count(*) as count'))
            ->whereNotNull('ciudad_origen')
            ->whereNotNull('ciudad_destino')
            ->where('ciudad_origen', '!=', '')
            ->where('ciudad_destino', '!=', '')
            ->groupBy('ciudad_origen', 'ciudad_destino')
            ->having('count', '>=', 2) // Solo rutas con al menos 2 cotizaciones
            ->orderBy('count', 'desc')
            ->limit(15)
            ->get();

        $routes = [];
        foreach ($realRoutes as $route) {
            $origin = strtolower(trim($route->ciudad_origen)); // Convertir a minúsculas
            $destination = strtolower(trim($route->ciudad_destino)); // Convertir a minúsculas
            
            if (isset($colombianCities[$origin]) && isset($colombianCities[$destination])) {
                $routes[] = [
                    'origin' => ucwords($origin), // Mostrar con primera letra mayúscula
                    'destination' => ucwords($destination), // Mostrar con primera letra mayúscula
                    'originCoords' => [$colombianCities[$origin]['lat'], $colombianCities[$origin]['lng']],
                    'destinationCoords' => [$colombianCities[$destination]['lat'], $colombianCities[$destination]['lng']],
                    'count' => $route->count,
                    'name' => ucwords($origin) . ' - ' . ucwords($destination)
                ];
            }
        }

        return [
            'cities' => $allCities->sortByDesc('count')->values()->all(),
            'routes' => $routes
        ];
    }

    private function calculateAcceptanceRate()
    {
        $totalCotizations = CotizacionModel::whereNotNull('decision_cliente')->count();
        $acceptedCotizations = CotizacionModel::where('decision_cliente', 'aceptada')->count();
        
        return $totalCotizations > 0 ? round(($acceptedCotizations / $totalCotizations) * 100, 2) : 0;
    }

    private function calculateAverageResponseTime()
    {
        // Calculamos el tiempo promedio entre creación y respuesta del cliente
        $avgTime = CotizacionModel::whereNotNull('decision_cliente')
            ->where('decision_cliente', '!=', 'pendiente')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->first();

        return round($avgTime->avg_hours ?? 0, 1);
    }

    private function getMonthlyChartData()
    {
        $months = [];
        $cotizations = [];
        $values = [];
        $efficiency = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            
            // Obtener datos de cotization_models para este mes
            $monthData = CotizacionModel::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->get();

            $months[] = $date->format('M Y');
            $cotizations[] = $monthData->count();
            
            // Calcular valor promedio del mes
            $avgValue = $monthData->where('valor', '>', 0)->avg('valor') ?? 0;
            $values[] = round($avgValue, 2);
            
            // Calcular eficiencia basada en cotizaciones con valor vs sin valor
            $withValue = $monthData->where('valor', '>', 0)->count();
            $efficiency[] = $monthData->count() > 0 ? round(($withValue / $monthData->count()) * 100, 2) : 0;
        }

        return [
            'months' => $months,
            'cotizations' => $cotizations,
            'values' => $values,
            'efficiency' => $efficiency,
        ];
    }

    public function getAnalysisDataApi()
    {
        // Endpoint para obtener datos vía AJAX para actualizaciones en tiempo real
        return response()->json($this->getAnalysisData());
    }
}
