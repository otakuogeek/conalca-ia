<?php

namespace App\Http\Controllers;

use App\Models\LlamadaConductor;
use App\Models\Llamada;
use App\Models\DriverCallResponse;
use App\Exports\CallAnalyticsExport;
use App\Services\ElevenLabsCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $query->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '<=', $to . ' 23:59:59');
        }

        $data['rawData'] = $query->orderByDesc(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'))->get();

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
                'transcript',
                'observaciones',
                'created_at'
            );

        if ($from) {
            $llamadasQuery->where(DB::raw('COALESCE(call_initiated_at, created_at)'), '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $llamadasQuery->where(DB::raw('COALESCE(call_initiated_at, created_at)'), '<=', $to . ' 23:59:59');
        }

        $data['rawLlamadas'] = $llamadasQuery->orderByDesc(DB::raw('COALESCE(call_initiated_at, created_at)'))->get();

        // Group calls with transcripts for export
        $groupTranscriptsQuery = DB::table('llamadas_conductores as lc')
            ->leftJoin('group_cotizations as gc', 'lc.group_cotization_id', '=', 'gc.id')
            ->leftJoin('clients as cl', 'gc.client_id', '=', 'cl.id')
            ->leftJoin('llamadas as l', function ($join) {
                $join->on('lc.elevenlabs_conversation_id', '=', 'l.elevenlabs_conversation_id')
                     ->whereNotNull('lc.elevenlabs_conversation_id');
            })
            ->select(
                'lc.group_cotization_id',
                'gc.reference as grupo_referencia',
                'gc.type as grupo_tipo',
                'cl.cliente',
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.tipo_vehiculo',
                'lc.placa',
                'lc.estado_llamada',
                'lc.disponible',
                'lc.elevenlabs_conversation_id',
                'lc.fecha_llamada',
                'lc.created_at as lc_created_at',
                'l.call_status',
                'l.talk_duration_seconds',
                'l.transcript'
            )
            ->whereNotNull('lc.group_cotization_id')
            ->whereNull('lc.deleted_at');

        if ($from) {
            $groupTranscriptsQuery->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $groupTranscriptsQuery->where(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'), '<=', $to . ' 23:59:59');
        }

        $data['groupTranscripts'] = $groupTranscriptsQuery
            ->orderBy('lc.group_cotization_id', 'desc')
            ->orderByDesc(DB::raw('COALESCE(lc.fecha_llamada, lc.created_at)'))
            ->get();

        // Backfill missing transcripts from ElevenLabs API
        $data['groupTranscripts'] = $this->backfillTranscripts($data['groupTranscripts']);

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
        // Helper: apply date range filter consistently using COALESCE(fecha_llamada, created_at)
        // This ensures filtering matches the actual call date, not just record creation
        $applyDateFilter = function ($query, string $table = 'llamadas_conductores', string $dateCol = 'fecha_llamada', string $fallbackCol = 'created_at') use ($from, $to) {
            if ($from) {
                $query->where(DB::raw("COALESCE({$table}.{$dateCol}, {$table}.{$fallbackCol})"), '>=', $from . ' 00:00:00');
            }
            if ($to) {
                $query->where(DB::raw("COALESCE({$table}.{$dateCol}, {$table}.{$fallbackCol})"), '<=', $to . ' 23:59:59');
            }
            return $query;
        };

        $applyDateFilterAlias = function ($query, string $alias = 'lc') use ($from, $to) {
            if ($from) {
                $query->where(DB::raw("COALESCE({$alias}.fecha_llamada, {$alias}.created_at)"), '>=', $from . ' 00:00:00');
            }
            if ($to) {
                $query->where(DB::raw("COALESCE({$alias}.fecha_llamada, {$alias}.created_at)"), '<=', $to . ' 23:59:59');
            }
            return $query;
        };

        $applyLlamadaDateFilter = function ($query) use ($from, $to) {
            if ($from) {
                $query->where(DB::raw('COALESCE(call_initiated_at, created_at)'), '>=', $from . ' 00:00:00');
            }
            if ($to) {
                $query->where(DB::raw('COALESCE(call_initiated_at, created_at)'), '<=', $to . ' 23:59:59');
            }
            return $query;
        };

        // 1. KPIs generales (filtered by date range)
        $conductorQuery = LlamadaConductor::query();
        $applyDateFilter($conductorQuery);
        $totalCalls = (clone $conductorQuery)->count();
        $completedCalls = (clone $conductorQuery)->where('estado_llamada', 'completada')->count();
        $failedCalls = (clone $conductorQuery)->where('estado_llamada', 'fallida')->count();
        $pendingCalls = (clone $conductorQuery)->where('estado_llamada', 'pendiente')->count();
        $inProgressCalls = (clone $conductorQuery)->where('estado_llamada', 'en_progreso')->count();

        $llamadaQuery = Llamada::query();
        $applyLlamadaDateFilter($llamadaQuery);
        $totalLlamadas = (clone $llamadaQuery)->count();
        $answeredCalls = (clone $llamadaQuery)->where(function ($q) {
            $q->where('call_status', 'answered')
              ->orWhere('call_status', 'completed');
        })->count();
        $noAnswerCalls = (clone $llamadaQuery)->where(function ($q) {
            $q->where('call_status', 'no_answer')
              ->orWhere('call_status', 'busy')
              ->orWhere('call_status', 'failed');
        })->count();

        // 2. Distribución por estado de llamada
        $statusQuery = LlamadaConductor::select('estado_llamada', DB::raw('COUNT(*) as total'));
        $applyDateFilter($statusQuery);
        $statusDistribution = $statusQuery
            ->groupBy('estado_llamada')
            ->orderByDesc('total')
            ->get();

        // 3. Distribución detallada (call_status de tabla llamadas)
        $callStatusQuery = Llamada::select('call_status', DB::raw('COUNT(*) as total'))
            ->whereNotNull('call_status');
        $applyLlamadaDateFilter($callStatusQuery);
        $callStatusDistribution = $callStatusQuery
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
                DB::raw('MIN(COALESCE(lc.fecha_llamada, lc.created_at)) as primera_llamada'),
                DB::raw('MAX(COALESCE(lc.fecha_llamada, lc.created_at)) as ultima_llamada')
            )
            ->whereNotNull('lc.group_cotization_id')
            ->whereNull('lc.deleted_at');
        $applyDateFilterAlias($groupAnalysis);
        $groupAnalysis = $groupAnalysis
            ->groupBy('lc.group_cotization_id', 'gc.reference', 'gc.type', 'cl.cliente')
            ->orderByDesc('total_llamadas')
            ->get();

        // 5. Detalle de conductores llamados
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
                DB::raw('MAX(COALESCE(lc.fecha_llamada, lc.created_at)) as ultima_llamada')
            )
            ->whereNull('lc.deleted_at');
        $applyDateFilterAlias($driverDetails);
        $driverDetails = $driverDetails
            ->groupBy('lc.nombre_conductor', 'lc.telefono', 'lc.tipo_vehiculo', 'lc.ciudad_actual', 'lc.ciudad_origen', 'lc.ciudad_destino')
            ->orderByDesc('veces_llamado')
            ->get();

        // 6. Llamadas por día
        $dailyQuery = LlamadaConductor::select(
                DB::raw('DATE(COALESCE(fecha_llamada, created_at)) as fecha'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas"),
                DB::raw("SUM(CASE WHEN estado_llamada = 'fallida' THEN 1 ELSE 0 END) as fallidas")
            );
        if ($from) {
            $dailyQuery->where(DB::raw('COALESCE(fecha_llamada, created_at)'), '>=', $from . ' 00:00:00');
        } else {
            $dailyQuery->where(DB::raw('COALESCE(fecha_llamada, created_at)'), '>=', Carbon::now()->subDays(30));
        }
        if ($to) {
            $dailyQuery->where(DB::raw('COALESCE(fecha_llamada, created_at)'), '<=', $to . ' 23:59:59');
        }
        $dailyCalls = $dailyQuery
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // 7. Respuestas de conductores (driver_call_responses)
        $driverResponsesQuery = DriverCallResponse::select(
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
            );
        if ($from) {
            $driverResponsesQuery->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $driverResponsesQuery->where('created_at', '<=', $to . ' 23:59:59');
        }
        $driverResponses = $driverResponsesQuery->orderByDesc('created_at')->get();

        // 8. Top conductores más contactados
        $topDriversQuery = DB::table('llamadas_conductores')
            ->select(
                'nombre_conductor',
                'telefono',
                DB::raw('COUNT(*) as total_llamadas'),
                DB::raw("SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) as completadas"),
                DB::raw("ROUND(SUM(CASE WHEN estado_llamada = 'completada' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as tasa_respuesta")
            )
            ->whereNull('deleted_at');
        $applyDateFilter($topDriversQuery);
        $topDrivers = $topDriversQuery
            ->groupBy('nombre_conductor', 'telefono')
            ->orderByDesc('total_llamadas')
            ->limit(10)
            ->get();

        // 9. Conversations de ElevenLabs
        $elevenlabsQuery = LlamadaConductor::whereNotNull('elevenlabs_conversation_id');
        $applyDateFilter($elevenlabsQuery);
        $elevenlabsCalls = $elevenlabsQuery->count();

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

    /**
     * Obtener la transcripción de una llamada específica
     */
    public function getTranscript(Request $request, $llamadaId)
    {
        $llamada = Llamada::where('id_llamada', $llamadaId)->first();

        if (!$llamada) {
            return response()->json(['success' => false, 'message' => 'Llamada no encontrada'], 404);
        }

        // If transcript is already stored, return it
        if ($llamada->transcript) {
            return response()->json([
                'success' => true,
                'transcript' => $llamada->transcript,
                'source' => 'database',
                'llamada' => [
                    'id' => $llamada->id_llamada,
                    'numero_destino' => $llamada->numero_destino,
                    'call_status' => $llamada->call_status,
                    'duration' => $llamada->talk_duration_seconds,
                    'date' => $llamada->call_initiated_at,
                ],
            ]);
        }

        // Try to fetch from ElevenLabs API if conversation_id exists
        if ($llamada->elevenlabs_conversation_id) {
            try {
                $callService = app(\App\Services\ElevenLabsCallService::class);
                $convDetails = $callService->getConversationDetails($llamada->elevenlabs_conversation_id);

                if ($convDetails['success'] && !empty($convDetails['data'])) {
                    $controller = app(\App\Http\Controllers\ConversationalAgentController::class);
                    $transcript = $this->parseTranscriptFromApi($convDetails['data']);

                    if ($transcript) {
                        // Save for future requests
                        $llamada->update(['transcript' => $transcript]);

                        return response()->json([
                            'success' => true,
                            'transcript' => $transcript,
                            'source' => 'elevenlabs_api',
                            'llamada' => [
                                'id' => $llamada->id_llamada,
                                'numero_destino' => $llamada->numero_destino,
                                'call_status' => $llamada->call_status,
                                'duration' => $llamada->talk_duration_seconds,
                                'date' => $llamada->call_initiated_at,
                            ],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo transcripción de ElevenLabs', [
                    'llamada_id' => $llamadaId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No hay transcripción disponible para esta llamada',
            'llamada' => [
                'id' => $llamada->id_llamada,
                'numero_destino' => $llamada->numero_destino,
                'call_status' => $llamada->call_status,
            ],
        ]);
    }

    /**
     * Obtener las llamadas de un grupo específico con transcripciones
     */
    public function getGroupCalls($groupId)
    {
        $calls = DB::table('llamadas_conductores as lc')
            ->leftJoin('llamadas as l', function ($join) {
                $join->on('lc.elevenlabs_conversation_id', '=', 'l.elevenlabs_conversation_id')
                     ->whereNotNull('lc.elevenlabs_conversation_id');
            })
            ->where('lc.group_cotization_id', $groupId)
            ->select(
                'lc.id',
                'lc.nombre_conductor',
                'lc.telefono',
                'lc.tipo_vehiculo',
                'lc.placa',
                'lc.estado_llamada',
                'lc.disponible',
                'lc.elevenlabs_conversation_id',
                'lc.created_at',
                'l.id_llamada',
                'l.call_status',
                'l.call_duration_seconds',
                'l.talk_duration_seconds',
                'l.transcript',
                'l.call_initiated_at',
                'l.call_completed_at'
            )
            ->orderByDesc('lc.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'group_id' => $groupId,
            'calls' => $calls,
        ]);
    }

    /**
     * Parse transcript from ElevenLabs API response
     */
    private function parseTranscriptFromApi(array $data): ?string
    {
        $transcript = '';

        $messages = $data['transcript']
            ?? $data['messages']
            ?? $data['conversation']['messages']
            ?? $data['conversation']['transcript']
            ?? null;

        if (is_array($messages)) {
            foreach ($messages as $msg) {
                $role = $msg['role'] ?? $msg['speaker'] ?? 'unknown';
                $content = $msg['message'] ?? $msg['content'] ?? $msg['text'] ?? '';
                if ($content) {
                    $label = ($role === 'agent' || $role === 'assistant') ? 'Agente' : 'Conductor';
                    $transcript .= "[{$label}]: {$content}\n";
                }
            }
        }

        if (empty($transcript) && !empty($data['analysis']['transcript_summary'])) {
            $transcript = $data['analysis']['transcript_summary'];
        }

        return $transcript ?: null;
    }

    /**
     * Backfill missing transcripts from ElevenLabs API for export.
     * Fetches conversation details for calls that have an elevenlabs_conversation_id
     * but no transcript stored yet, saves them to DB, and updates the collection.
     */
    private function backfillTranscripts($groupTranscripts)
    {
        $missing = $groupTranscripts->filter(function ($call) {
            return !empty($call->elevenlabs_conversation_id) && empty($call->transcript);
        });

        if ($missing->isEmpty()) {
            return $groupTranscripts;
        }

        // Deduplicate by conversation_id to avoid fetching the same conversation multiple times
        $uniqueConversationIds = $missing->pluck('elevenlabs_conversation_id')->unique()->values();

        Log::info("Backfilling {$uniqueConversationIds->count()} missing transcripts for export");

        $fetchedTranscripts = [];

        try {
            $callService = app(ElevenLabsCallService::class);

            foreach ($uniqueConversationIds as $conversationId) {
                try {
                    $convDetails = $callService->getConversationDetails($conversationId);

                    if ($convDetails['success'] && !empty($convDetails['data'])) {
                        $transcript = $this->parseTranscriptFromApi($convDetails['data']);
                        if ($transcript) {
                            $fetchedTranscripts[$conversationId] = $transcript;

                            // Save to llamadas table for future use
                            Llamada::where('elevenlabs_conversation_id', $conversationId)
                                ->whereNull('transcript')
                                ->update(['transcript' => $transcript]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch transcript for conversation {$conversationId}: {$e->getMessage()}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error initializing ElevenLabsCallService for backfill: {$e->getMessage()}");
        }

        if (empty($fetchedTranscripts)) {
            return $groupTranscripts;
        }

        Log::info("Successfully fetched " . count($fetchedTranscripts) . " transcripts from ElevenLabs");

        // Update the collection with fetched transcripts
        return $groupTranscripts->map(function ($call) use ($fetchedTranscripts) {
            if (empty($call->transcript) && !empty($call->elevenlabs_conversation_id)
                && isset($fetchedTranscripts[$call->elevenlabs_conversation_id])) {
                $call->transcript = $fetchedTranscripts[$call->elevenlabs_conversation_id];
            }
            return $call;
        });
    }
}
