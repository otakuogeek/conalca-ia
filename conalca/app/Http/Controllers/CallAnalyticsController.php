<?php

namespace App\Http\Controllers;

use App\Models\LlamadaConductor;
use App\Models\Llamada;
use App\Models\DriverCallResponse;
use App\Exports\CallAnalyticsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class CallAnalyticsController extends Controller
{
    public function show()
    {
        $data = $this->getCallAnalyticsData();
        return view('analysis.calls', $data);
    }

    public function apiData()
    {
        return response()->json($this->getCallAnalyticsData());
    }

    public function export(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $data = $this->getCallAnalyticsData($from, $to);

        // Add raw detail data for export
        $query = DB::table('llamadas_conductores as lc')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->select(
                'lc.id',
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.placa',
                'lc.tipo_vehiculo',
                'lc.vehiculo_silogtran',
                'lc.peso_maximo',
                'lc.clase_vehiculo',
                'lc.score',
                'lc.carroceria',
                'lc.capacidad',
                'lc.fuente',
                'lc.estado_llamada',
                'lc.disponible',
                'lc.elevenlabs_conversation_id',
                'lc.fecha_llamada',
                'lc.respuesta_llamada',
                'lc.notas',
                'lc.mercancia',
                'lc.peso_carga',
                'lc.empaque',
                'lc.ciudad_actual',
                'lc.ciudad_origen',
                'lc.ciudad_destino',
                'lc.group_cotization_id',
                'gc.reference as grupo_referencia',
                'gc.type as grupo_tipo',
                'cl.cliente as cliente',
                'lc.created_at',
                'lc.updated_at'
            )
            ->whereNull('lc.deleted_at');

        if ($from) {
            $query->where('lc.created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where('lc.created_at', '<=', $to . ' 23:59:59');
        }

        $data['rawData'] = $query->orderByDesc('lc.created_at')->get();

        // Raw llamadas table
        $llamadasQuery = DB::table('llamadas')
            ->select(
                'id_llamada',
                'numero_destino',
                'status',
                'queue_status',
                'call_status',
                'sip_status_code',
                'sip_status_message',
                'failure_reason',
                'call_initiated_at',
                'call_answered_at',
                'call_completed_at',
                'call_duration_seconds',
                'ring_duration_seconds',
                'talk_duration_seconds',
                'call_direction',
                'call_type',
                'elevenlabs_conversation_id',
                'call_notes',
                'observaciones',
                'created_at'
            );

        if ($from) {
            $llamadasQuery->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $llamadasQuery->where('created_at', '<=', $to . ' 23:59:59');
        }

        $data['rawLlamadas'] = $llamadasQuery->orderByDesc('created_at')->get();

        $suffix = '';
        if ($from && $to) {
            $suffix = "_{$from}_a_{$to}";
        } elseif ($from) {
            $suffix = "_desde_{$from}";
        } elseif ($to) {
            $suffix = "_hasta_{$to}";
        }

        $filename = 'llamadas_elevenlabs_' . date('Y-m-d_H-i-s') . $suffix . '.xlsx';

        return Excel::download(new CallAnalyticsExport($data), $filename);
    }

    private function getCallAnalyticsData(?string $from = null, ?string $to = null): array
    {
        // 1. KPIs generales
        $totalCalls = LlamadaConductor::count();
        $totalLlamadas = Llamada::count();
        $completedCalls = LlamadaConductor::where('estado_llamada', 'completada')->count();
        $failedCalls = LlamadaConductor::where('estado_llamada', 'fallida')->count();
        $pendingCalls = LlamadaConductor::where('estado_llamada', 'pendiente')->count();
        $inProgressCalls = LlamadaConductor::where('estado_llamada', 'en_progreso')->count();
        $answeredCalls = Llamada::where('call_status', 'answered')
            ->orWhere('call_status', 'completed')
            ->count();
        $noAnswerCalls = Llamada::where('call_status', 'no_answer')
            ->orWhere('call_status', 'busy')
            ->orWhere('call_status', 'failed')
            ->count();

        // 2. Distribución por estado de llamada
        $statusDistribution = LlamadaConductor::select('estado_llamada', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_llamada')
            ->orderByDesc('total')
            ->get();

        // 3. Distribución detallada (call_status de tabla llamadas)
        $callStatusDistribution = Llamada::select('call_status', DB::raw('COUNT(*) as total'))
            ->whereNotNull('call_status')
            ->groupBy('call_status')
            ->orderByDesc('total')
            ->get();

        // 4. Análisis por Grupo de Solicitud
        $groupAnalysis = DB::table('llamadas_conductores as lc')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->select(
                'lc.group_cotization_id',
                'gc.reference as group_ref',
                'gc.type as group_type',
                'cl.cliente as client_name',
                DB::raw('COUNT(*) as total_llamadas'),
                DB::raw("SUM(CASE WHEN lc.estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas"),
                DB::raw("SUM(CASE WHEN lc.estado_llamada = 'fallida' THEN 1 ELSE 0 END) as fallidas"),
                DB::raw("SUM(CASE WHEN lc.estado_llamada = 'pendiente' THEN 1 ELSE 0 END) as pendientes"),
                DB::raw("SUM(CASE WHEN lc.estado_llamada = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso"),
                DB::raw("SUM(CASE WHEN lc.disponible = 1 THEN 1 ELSE 0 END) as disponibles"),
                DB::raw('COUNT(DISTINCT lc.telefono) as conductores_unicos'),
                DB::raw('MIN(lc.created_at) as primera_llamada'),
                DB::raw('MAX(lc.created_at) as ultima_llamada')
            )
            ->whereNotNull('lc.group_cotization_id')
            ->groupBy('lc.group_cotization_id', 'gc.reference', 'gc.type', 'cl.cliente')
            ->orderByDesc('total_llamadas')
            ->get();

        // 5. Detalle de conductores llamados (quiénes, cuántas veces, respondieron?)
        $driverDetails = DB::table('llamadas_conductores as lc')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->select(
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.tipo_vehiculo',
                'lc.ciudad_actual',
                'lc.ciudad_origen',
                'lc.ciudad_destino',
                DB::raw('COUNT(*) as veces_llamado'),
                DB::raw("SUM(CASE WHEN lc.estado_llamada = 'completada' THEN 1 ELSE 0 END) as respondio"),
                DB::raw("SUM(CASE WHEN lc.estado_llamada IN ('fallida', 'pendiente') THEN 1 ELSE 0 END) as no_respondio"),
                DB::raw("SUM(CASE WHEN lc.disponible = 1 THEN 1 ELSE 0 END) as veces_disponible"),
                DB::raw('GROUP_CONCAT(DISTINCT lc.group_cotization_id) as grupos'),
                DB::raw('MAX(lc.created_at) as ultima_llamada')
            )
            ->groupBy('lc.nombre_conductor', 'lc.telefono', 'lc.tipo_vehiculo', 'lc.ciudad_actual', 'lc.ciudad_origen', 'lc.ciudad_destino')
            ->orderByDesc('veces_llamado')
            ->get();

        // 6. Llamadas por día (últimos 30 días)
        $dailyCalls = LlamadaConductor::select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas"),
                DB::raw("SUM(CASE WHEN estado_llamada = 'fallida' THEN 1 ELSE 0 END) as fallidas")
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // 7. Respuestas de conductores (driver_call_responses)
        $driverResponses = DriverCallResponse::select(
                'driver_name',
                'driver_phone',
                'vehicle_type',
                'vehicle_plate',
                'call_status',
                'response_status',
                'call_duration',
                'cotizacion_id',
                'elevenlabs_conversation_id',
                'created_at'
            )
            ->orderByDesc('created_at')
            ->get();

        // 8. Top conductores más contactados
        $topDrivers = DB::table('llamadas_conductores')
            ->select(
                'nombre_conductor',
                'telefono',
                DB::raw('COUNT(*) as total_llamadas'),
                DB::raw("SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas"),
                DB::raw("ROUND(SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as tasa_respuesta")
            )
            ->groupBy('nombre_conductor', 'telefono')
            ->orderByDesc('total_llamadas')
            ->limit(10)
            ->get();

        // 9. Conversations de ElevenLabs
        $elevenlabsCalls = LlamadaConductor::whereNotNull('elevenlabs_conversation_id')
            ->count();

        return [
            'totalCalls' => $totalCalls,
            'totalLlamadas' => $totalLlamadas,
            'completedCalls' => $completedCalls,
            'failedCalls' => $failedCalls,
            'pendingCalls' => $pendingCalls,
            'inProgressCalls' => $inProgressCalls,
            'answeredCalls' => $answeredCalls,
            'noAnswerCalls' => $noAnswerCalls,
            'elevenlabsCalls' => $elevenlabsCalls,
            'statusDistribution' => $statusDistribution,
            'callStatusDistribution' => $callStatusDistribution,
            'groupAnalysis' => $groupAnalysis,
            'driverDetails' => $driverDetails,
            'dailyCalls' => $dailyCalls,
            'driverResponses' => $driverResponses,
            'topDrivers' => $topDrivers,
        ];
    }
}
