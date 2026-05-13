<?php

namespace App\Http\Controllers;

use App\Models\Llamada;
use App\Models\LlamadaConductor;
use App\Services\ElevenLabsCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditoriaLlamadasController extends Controller
{
    /**
     * Vista principal de auditoría de llamadas
     */
    public function index(Request $request)
    {
        $data = $this->getAuditoriaData($request);
        return view('analysis.auditoria-llamadas', $data);
    }

    /**
     * API JSON para datos de auditoría (AJAX)
     */
    public function apiData(Request $request)
    {
        return response()->json($this->getAuditoriaData($request));
    }

    /**
     * Detalle completo de un lote/batch de llamadas
     */
    public function batchDetail(Request $request, $groupId)
    {
        $calls = DB::table('llamadas_conductores as lc')
            ->leftJoin('llamadas as l', 'lc.id', '=', 'l.conductor_id')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->where('lc.group_cotization_id', $groupId)
            ->whereNull('lc.deleted_at')
            // Solo mostrar conductores cuyas llamadas ya fueron ejecutadas
            ->where('lc.estado_llamada', '!=', 'pendiente')
            ->select(
                'lc.id',
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.tipo_vehiculo',
                'lc.placa',
                'lc.estado_llamada',
                'lc.disponible',
                'lc.elevenlabs_conversation_id',
                'lc.elevenlabs_sip_call_id',
                'lc.fecha_llamada',
                'lc.respuesta_llamada',
                'lc.notas',
                'lc.mercancia',
                'lc.peso_carga',
                'lc.ciudad_actual',
                'lc.ciudad_origen',
                'lc.ciudad_destino',
                'lc.score',
                'lc.created_at as lc_created_at',
                'l.id_llamada',
                'l.status as llamada_status',
                'l.call_status',
                'l.sip_status_code',
                'l.sip_status_message',
                'l.failure_reason',
                'l.call_initiated_at',
                'l.call_ringing_at',
                'l.call_answered_at',
                'l.call_completed_at',
                'l.call_duration_seconds',
                'l.ring_duration_seconds',
                'l.talk_duration_seconds',
                'l.transcript',
                'l.call_notes',
                'l.internal_notes',
                'l.queue_status',
                'l.batch_number',
                'l.batch_position',
                'l.queue_priority',
                'l.queued_at',
                'l.processing_started_at',
                'l.processing_completed_at',
                'l.processing_attempts',
                'l.call_retry_count',
                'l.call_direction',
                'l.call_type',
                'l.call_metadata',
                'l.elevenlabs_response',
                'gc.reference as grupo_referencia',
                'gc.type as grupo_tipo',
                'cl.cliente as cliente_nombre'
            )
            ->orderBy('l.batch_number')
            ->orderBy('l.batch_position')
            ->orderByDesc('lc.created_at')
            ->get();

        // Agrupar por batch_number
        $batches = $calls->groupBy(function ($call) {
            return $call->batch_number ?? 'sin_lote';
        });

        return response()->json([
            'success' => true,
            'group_id' => $groupId,
            'total_calls' => $calls->count(),
            'batches' => $batches,
            'calls' => $calls,
        ]);
    }

    /**
     * Obtener transcripción de una llamada específica
     */
    public function getTranscript($conversationId)
    {
        // Buscar en llamadas
        $llamada = Llamada::where('elevenlabs_conversation_id', $conversationId)->first();

        if ($llamada && $llamada->transcript) {
            $transcript = $llamada->transcript;

            // Si el transcript almacenado es JSON crudo (array de turns), parsearlo
            $parsed = $this->ensureParsedTranscript($transcript);
            if ($parsed !== $transcript && $parsed) {
                // Guardar la versión limpia para futuras consultas
                $llamada->update(['transcript' => $parsed]);
                $transcript = $parsed;
            }

            return response()->json([
                'success' => true,
                'transcript' => $transcript,
                'source' => 'database',
            ]);
        }

        // Intentar obtener desde ElevenLabs API
        if ($conversationId) {
            try {
                $callService = app(ElevenLabsCallService::class);
                $convDetails = $callService->getConversationDetails($conversationId);

                if ($convDetails['success'] && !empty($convDetails['data'])) {
                    $transcript = $this->parseTranscript($convDetails['data']);
                    if ($transcript && $llamada) {
                        $llamada->update(['transcript' => $transcript]);
                    }
                    return response()->json([
                        'success' => true,
                        'transcript' => $transcript,
                        'source' => 'elevenlabs_api',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo transcripción', [
                    'conversation_id' => $conversationId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Transcripción no disponible',
        ]);
    }

    /**
     * Obtener audio de una conversación
     */
    public function getAudio($conversationId)
    {
        // Buscar audio local primero
        $localPath = storage_path("app/public/recordings/{$conversationId}.mp3");
        if (file_exists($localPath)) {
            return response()->json([
                'success' => true,
                'audio_url' => asset("storage/recordings/{$conversationId}.mp3"),
                'source' => 'local',
            ]);
        }

        // Intentar obtener desde ElevenLabs API
        try {
            $callService = app(ElevenLabsCallService::class);
            $apiKey = config('services.elevenlabs.api_key', env('ELEVENLABS_API_KEY'));

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['xi-api-key' => $apiKey])
                ->get("https://api.elevenlabs.io/v1/convai/conversations/{$conversationId}/audio");

            if ($response->successful()) {
                // Guardar localmente para futuras solicitudes
                $dir = storage_path('app/public/recordings');
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                file_put_contents("{$dir}/{$conversationId}.mp3", $response->body());

                return response()->json([
                    'success' => true,
                    'audio_url' => asset("storage/recordings/{$conversationId}.mp3"),
                    'source' => 'elevenlabs',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo audio', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Audio no disponible',
        ]);
    }

    /**
     * Estado de la cola en tiempo real
     */
    public function queueStatus()
    {
        $activeJobs = DB::table('jobs')
            ->where('queue', 'calls')
            ->count();

        $failedJobs = DB::table('failed_jobs')
            ->where('queue', 'calls')
            ->where('failed_at', '>=', now()->subHours(24))
            ->count();

        $activeCalls = Llamada::where('queue_status', 'processing')
            ->where('processing_started_at', '>=', now()->subMinutes(10))
            ->count();

        $pendingCalls = Llamada::where('queue_status', 'pending')->count();

        $recentCompleted = Llamada::where('queue_status', 'completed')
            ->where('processing_completed_at', '>=', now()->subHour())
            ->count();

        $recentFailed = Llamada::where('queue_status', 'failed')
            ->where('processing_completed_at', '>=', now()->subHour())
            ->count();

        $maxConcurrent = \App\Services\CallQueueManager::getMaxConcurrentCalls();
        $provider = config('services.elevenlabs.call_provider', 'zadarma');

        return response()->json([
            'success' => true,
            'queue' => [
                'active_jobs' => $activeJobs,
                'failed_jobs_24h' => $failedJobs,
                'active_calls' => $activeCalls,
                'pending_calls' => $pendingCalls,
                'completed_last_hour' => $recentCompleted,
                'failed_last_hour' => $recentFailed,
                'max_concurrent' => $maxConcurrent,
                'provider' => $provider,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Timeline de eventos de una llamada específica
     */
    public function callTimeline($llamadaId)
    {
        $llamada = Llamada::find($llamadaId);

        if (!$llamada) {
            return response()->json(['success' => false, 'message' => 'Llamada no encontrada'], 404);
        }

        $events = [];

        if ($llamada->queued_at) {
            $events[] = ['time' => $llamada->queued_at, 'event' => 'En cola', 'icon' => 'clock', 'color' => 'gray'];
        }
        if ($llamada->processing_started_at) {
            $events[] = ['time' => $llamada->processing_started_at, 'event' => 'Procesando', 'icon' => 'cog', 'color' => 'blue'];
        }
        if ($llamada->call_initiated_at) {
            $events[] = ['time' => $llamada->call_initiated_at, 'event' => 'Llamada iniciada', 'icon' => 'phone', 'color' => 'blue'];
        }
        if ($llamada->call_ringing_at) {
            $events[] = ['time' => $llamada->call_ringing_at, 'event' => 'Timbrando', 'icon' => 'bell', 'color' => 'yellow'];
        }
        if ($llamada->call_answered_at) {
            $events[] = ['time' => $llamada->call_answered_at, 'event' => 'Contestada', 'icon' => 'check', 'color' => 'green'];
        }
        if ($llamada->call_completed_at) {
            $event = 'Finalizada';
            $color = 'green';
            if ($llamada->status === 'aceptada') {
                $event = 'Viaje aceptado';
                $color = 'green';
            } elseif ($llamada->status === 'rechazada') {
                $event = 'Viaje rechazado';
                $color = 'red';
            } elseif ($llamada->call_status === 'busy') {
                $event = 'Línea ocupada';
                $color = 'orange';
            } elseif ($llamada->call_status === 'no_answer') {
                $event = 'No contestó';
                $color = 'orange';
            } elseif ($llamada->call_status === 'failed') {
                $event = 'Llamada fallida';
                $color = 'red';
            }
            $events[] = ['time' => $llamada->call_completed_at, 'event' => $event, 'icon' => 'flag', 'color' => $color];
        }
        if ($llamada->failure_reason) {
            $events[] = ['time' => $llamada->call_completed_at ?? $llamada->updated_at, 'event' => "Error: {$llamada->failure_reason}", 'icon' => 'x-circle', 'color' => 'red'];
        }
        if ($llamada->processing_completed_at) {
            $events[] = ['time' => $llamada->processing_completed_at, 'event' => 'Proceso completado', 'icon' => 'check-circle', 'color' => 'green'];
        }

        usort($events, fn($a, $b) => strtotime($a['time']) - strtotime($b['time']));

        return response()->json([
            'success' => true,
            'llamada_id' => $llamadaId,
            'events' => $events,
            'metadata' => [
                'status' => $llamada->status,
                'call_status' => $llamada->call_status,
                'queue_status' => $llamada->queue_status,
                'batch_number' => $llamada->batch_number,
                'batch_position' => $llamada->batch_position,
                'duration' => $llamada->call_duration_seconds,
                'talk_time' => $llamada->talk_duration_seconds,
                'ring_time' => $llamada->ring_duration_seconds,
                'retry_count' => $llamada->call_retry_count,
                'attempts' => $llamada->processing_attempts,
                'sip_code' => $llamada->sip_status_code,
                'sip_message' => $llamada->sip_status_message,
                'conversation_id' => $llamada->elevenlabs_conversation_id,
            ],
        ]);
    }

    /**
     * Datos principales de auditoría
     */
    private function getAuditoriaData(Request $request): array
    {
        $from = $request->input('from');
        $to = $request->input('to');

        // Lotes de llamadas agrupados por group_cotization_id, ordenados por fecha
        $batches = DB::table('llamadas_conductores as lc')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->leftJoin('llamadas as l', function ($join) {
                $join->on('lc.id', '=', 'l.conductor_id');
            })
            ->select(
                'lc.group_cotization_id',
                'gc.reference as grupo_referencia',
                'gc.type as grupo_tipo',
                'cl.cliente as cliente_nombre',
                DB::raw('MIN(lc.ciudad_origen) as ruta_origen'),
                DB::raw('MIN(lc.ciudad_destino) as ruta_destino'),
                DB::raw('COUNT(DISTINCT lc.id) as total_conductores'),
                DB::raw('COUNT(DISTINCT l.id_llamada) as total_llamadas_ejecutadas'),
                // Conteos basados en lc: usar COUNT(DISTINCT) para evitar duplicados por JOINs
                DB::raw("COUNT(DISTINCT CASE WHEN lc.estado_llamada = 'completada' THEN lc.id END) as completadas"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.estado_llamada = 'fallida' THEN lc.id END) as fallidas"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.estado_llamada = 'pendiente' THEN lc.id END) as pendientes"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.estado_llamada = 'en_progreso' THEN lc.id END) as en_progreso"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.respuesta_llamada LIKE '%Acepta%' OR lc.respuesta_llamada = 'accepted' OR lc.respuesta_llamada = 'aceptado' THEN lc.id END) as aceptaron_viaje"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.respuesta_llamada LIKE '%Rechaza%' OR lc.respuesta_llamada = 'rejected' OR lc.respuesta_llamada = 'rechazado' THEN lc.id END) as rechazaron_viaje"),
                DB::raw("COUNT(DISTINCT CASE WHEN lc.estado_llamada = 'en_progreso' AND lc.respuesta_llamada IS NULL AND lc.elevenlabs_conversation_id IS NOT NULL THEN lc.id END) as sin_decision"),
                // Conteos basados en l: usar COUNT(DISTINCT) sobre l.id_llamada
                DB::raw("COUNT(DISTINCT CASE WHEN l.call_status IN ('answered','completed') THEN l.id_llamada END) as contestadas"),
                DB::raw("COUNT(DISTINCT CASE WHEN l.call_status = 'no_answer' THEN l.id_llamada END) as sin_respuesta"),
                DB::raw("COUNT(DISTINCT CASE WHEN l.call_status = 'busy' THEN l.id_llamada END) as ocupadas"),
                DB::raw("COUNT(DISTINCT CASE WHEN l.call_status = 'failed' THEN l.id_llamada END) as llamadas_fallidas"),
                DB::raw("COUNT(DISTINCT CASE WHEN l.failure_reason IS NOT NULL AND l.failure_reason != '' THEN l.id_llamada END) as con_errores"),
                DB::raw("COUNT(DISTINCT CASE WHEN l.transcript IS NOT NULL AND l.transcript != '' THEN l.id_llamada END) as con_transcripcion"),
                DB::raw('COALESCE(AVG(CASE WHEN l.id_llamada IS NOT NULL THEN COALESCE(l.talk_duration_seconds, l.call_duration_seconds) END), 0) as promedio_duracion'),
                DB::raw('COALESCE(MAX(l.batch_number), 0) as total_batches'),
                DB::raw('MIN(COALESCE(lc.fecha_llamada, lc.created_at)) as primera_llamada'),
                DB::raw('MAX(COALESCE(lc.fecha_llamada, lc.created_at)) as ultima_llamada')
            )
            ->whereNotNull('lc.group_cotization_id')
            ->whereNull('lc.deleted_at')
            // Solo mostrar conductores cuyas llamadas ya fueron ejecutadas (no pendientes sin iniciar)
            ->where('lc.estado_llamada', '!=', 'pendiente');

        if ($from) {
            $batches->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $batches->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '<=', $to . ' 23:59:59');
        }

        $batches = $batches
            ->groupBy('lc.group_cotization_id', 'gc.reference', 'gc.type', 'cl.cliente')
            ->orderByDesc(DB::raw('MAX(COALESCE(lc.fecha_llamada, lc.created_at))'))
            ->get();

        // KPIs globales
        $totalGroups = $batches->count();
        $totalConductores = $batches->sum('total_conductores');
        $totalLlamadas = $batches->sum('total_llamadas_ejecutadas');
        $totalAceptaron = $batches->sum('aceptaron_viaje');
        $totalRechazaron = $batches->sum('rechazaron_viaje');
        $totalSinDecision = $batches->sum('sin_decision');
        $totalConError = $batches->sum('con_errores');
        $totalContestadas = $batches->sum('contestadas');
        $totalSinRespuesta = $batches->sum('sin_respuesta');
        $totalConTranscripcion = $batches->sum('con_transcripcion');
        $totalCompletadas = $batches->sum('completadas');
        $totalFallidas = $batches->sum('fallidas');
        $totalOcupadas = $batches->sum('ocupadas');
        $promedioDuracionGlobal = $totalLlamadas > 0 ? round($batches->avg('promedio_duracion'), 1) : 0;

        // Errores recientes (últimas 24h)
        $recentErrors = Llamada::whereNotNull('failure_reason')
            ->where('failure_reason', '!=', '')
            ->where('created_at', '>=', now()->subHours(24))
            ->select('id_llamada', 'numero_destino', 'failure_reason', 'call_status', 'sip_status_code', 'sip_status_message', 'created_at', 'elevenlabs_conversation_id')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Actividad de cola en tiempo real
        $queueActivity = [
            'processing' => Llamada::where('queue_status', 'processing')->count(),
            'pending' => Llamada::where('queue_status', 'pending')->count(),
            'active_jobs' => DB::table('jobs')->where('queue', 'calls')->count(),
            'failed_jobs' => DB::table('failed_jobs')->where('queue', 'calls')->where('failed_at', '>=', now()->subHours(24))->count(),
        ];

        return [
            'batches' => $batches,
            'kpis' => [
                'total_groups' => $totalGroups,
                'total_conductores' => $totalConductores,
                'total_llamadas' => $totalLlamadas,
                'total_aceptaron' => $totalAceptaron,
                'total_rechazaron' => $totalRechazaron,
                'total_sin_decision' => $totalSinDecision,
                'total_con_error' => $totalConError,
                'total_contestadas' => $totalContestadas,
                'total_sin_respuesta' => $totalSinRespuesta,
                'total_con_transcripcion' => $totalConTranscripcion,
                'tasa_exito' => $totalConductores > 0 ? min(100, round(($totalAceptaron / $totalConductores) * 100, 1)) : 0,
                'tasa_contacto' => $totalConductores > 0 ? min(100, round(($totalContestadas / $totalConductores) * 100, 1)) : 0,
                'tasa_rechazo' => $totalConductores > 0 ? min(100, round(($totalRechazaron / $totalConductores) * 100, 1)) : 0,
                'total_completadas' => $totalCompletadas,
                'total_fallidas' => $totalFallidas,
                'total_ocupadas' => $totalOcupadas,
                'promedio_duracion_global' => $promedioDuracionGlobal,
                'provider' => config('services.elevenlabs.call_provider', 'zadarma'),
            ],
            'recentErrors' => $recentErrors,
            'queueActivity' => $queueActivity,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /**
     * Búsqueda global de conductores por placa o nombre en todas las órdenes procesadas
     */
    public function searchConductor(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['success' => false, 'message' => 'Mínimo 2 caracteres para buscar']);
        }

        $results = DB::table('llamadas_conductores as lc')
            ->leftJoin('llamadas as l', 'lc.id', '=', 'l.conductor_id')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->where(function ($q) use ($query) {
                $q->where('lc.nombre_conductor', 'LIKE', "%{$query}%")
                  ->orWhere('lc.placa', 'LIKE', "%{$query}%")
                  ->orWhere('lc.telefono', 'LIKE', "%{$query}%");
            })
            ->whereNull('lc.deleted_at')
            ->where('lc.estado_llamada', '!=', 'pendiente')
            ->select(
                'lc.id',
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.tipo_vehiculo',
                'lc.placa',
                'lc.estado_llamada',
                'lc.respuesta_llamada',
                'lc.notas',
                'lc.ciudad_origen',
                'lc.ciudad_destino',
                'lc.mercancia',
                'lc.peso_carga',
                'lc.score',
                'lc.elevenlabs_conversation_id',
                'lc.group_cotization_id',
                'lc.fecha_llamada',
                'lc.created_at as lc_created_at',
                'l.id_llamada',
                'l.call_status',
                'l.call_duration_seconds',
                'l.talk_duration_seconds',
                'l.transcript',
                'l.failure_reason',
                'l.internal_notes',
                'gc.reference as grupo_referencia',
                'gc.type as grupo_tipo',
                'cl.cliente as cliente_nombre'
            )
            ->orderByDesc(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'))
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'total' => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Exportar auditoría a CSV
     */
    public function exportCsv(Request $request)
    {
        $data = $this->getAuditoriaData($request);
        $batches = $data['batches'];

        $filename = 'auditoria_llamadas_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($batches) {
            $file = fopen('php://output', 'w');
            // BOM para Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID Grupo', 'Referencia', 'Tipo', 'Cliente', 'Ruta Origen', 'Ruta Destino',
                'Total Conductores', 'Llamadas Ejecutadas', 'Completadas', 'Fallidas',
                'Contestadas', 'Sin Respuesta', 'Ocupadas',
                'Aceptaron', 'Rechazaron', 'Sin Decisión', 'Con Errores',
                'Con Transcripción', 'Duración Promedio (seg)',
                'Primera Llamada', 'Última Llamada'
            ]);

            foreach ($batches as $batch) {
                fputcsv($file, [
                    $batch->group_cotization_id,
                    $batch->grupo_referencia ?? '',
                    $batch->grupo_tipo ?? '',
                    $batch->cliente_nombre ?? '',
                    $batch->ruta_origen ?? '',
                    $batch->ruta_destino ?? '',
                    $batch->total_conductores,
                    $batch->total_llamadas_ejecutadas,
                    $batch->completadas,
                    $batch->fallidas,
                    $batch->contestadas,
                    $batch->sin_respuesta,
                    $batch->ocupadas,
                    $batch->aceptaron_viaje,
                    $batch->rechazaron_viaje,
                    $batch->sin_decision ?? 0,
                    $batch->con_errores,
                    $batch->con_transcripcion,
                    round($batch->promedio_duracion ?? 0, 1),
                    $batch->primera_llamada ?? '',
                    $batch->ultima_llamada ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Parser de transcripción desde API ElevenLabs
     */
    private function parseTranscript(array $data): ?string
    {
        $transcript = $data['transcript'] ?? $data['analysis'] ?? null;

        return $this->transcriptArrayToText($transcript);
    }

    /**
     * Asegurar que un string de transcript almacenado esté parseado a texto legible.
     * Si es JSON crudo (array de turns), lo parsea. Si ya es texto, lo retorna igual.
     */
    private function ensureParsedTranscript(string $raw): ?string
    {
        // Intentar decodificar como JSON
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
            // Es JSON - verificar que parece un array de turns
            if (isset($decoded[0]['role']) || isset($decoded[0]['message'])) {
                return $this->transcriptArrayToText($decoded);
            }
        }
        // Ya es texto plano
        return $raw;
    }

    /**
     * Convertir array de turns de ElevenLabs a texto formateado
     */
    private function transcriptArrayToText($transcript): ?string
    {
        if (is_array($transcript)) {
            $lines = [];
            foreach ($transcript as $entry) {
                $role = $entry['role'] ?? 'unknown';
                $message = $entry['message'] ?? $entry['text'] ?? '';
                if (empty(trim($message))) continue;
                $roleName = $role === 'agent' ? 'Agente' : 'Conductor';
                $lines[] = "{$roleName}: {$message}";
            }
            return !empty($lines) ? implode("\n", $lines) : null;
        }

        if (is_string($transcript)) {
            return $transcript;
        }

        return null;
    }
}
