<?php

namespace App\Services;

use App\Models\Llamada;
use App\Models\LlamadaConductor;
use App\Jobs\ProcessElevenLabsCall;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CallQueueManager
{
    /**
     * Máximo de llamadas simultáneas permitidas.
     * - Zadarma SIP trunk: 2 líneas (si se excede, Zadarma rechaza llamadas)
     * - Twilio: hasta 10 líneas concurrentes
     * Se lee de config según el proveedor activo en ELEVENLABS_CALL_PROVIDER
     */
    const MAX_CONCURRENT_CALLS_ZADARMA = 2;
    const MAX_CONCURRENT_CALLS_TWILIO = 3;

    /**
     * Delay entre despachos de llamadas (segundos).
     * - Zadarma: 0 (sin delay, solo 2 concurrentes)
     * - Twilio: 1 segundo entre llamadas
     */
    const DISPATCH_DELAY_ZADARMA = 0;
    const DISPATCH_DELAY_TWILIO = 1;

    /**
     * Tiempo máximo (segundos) para considerar una llamada como "stuck".
     * Si una llamada lleva más de este tiempo activa sin webhook de cierre, se marca fallida.
     */
    const STUCK_CALL_TIMEOUT = 300; // 5 minutos

    /**
     * Duración del lock de despacho (segundos).
     * Evita que dos webhooks simultáneos despachen llamadas al mismo tiempo.
     */
    const DISPATCH_LOCK_TTL = 10;

    /**
     * Obtener el máximo de llamadas concurrentes según el proveedor activo
     */
    public static function getMaxConcurrentCalls(): int
    {
        $provider = config('services.elevenlabs.call_provider', 'zadarma');
        return $provider === 'twilio' ? self::MAX_CONCURRENT_CALLS_TWILIO : self::MAX_CONCURRENT_CALLS_ZADARMA;
    }

    /**
     * Obtener el delay entre despachos según el proveedor activo (segundos)
     */
    public static function getDispatchDelay(): int
    {
        $provider = config('services.elevenlabs.call_provider', 'zadarma');
        return $provider === 'twilio' ? self::DISPATCH_DELAY_TWILIO : self::DISPATCH_DELAY_ZADARMA;
    }

    /**
     * Obtener el número de llamadas REALMENTE activas en las líneas de Zadarma.
     * 
     * Una llamada se considera "activa" (ocupando una línea SIP) si:
     * - queue_status = 'processing' (fue despachada pero aún no recibió webhook de finalización)
     * - Incluye: pendiente de iniciar (job en cola), iniciada, timbrando, en conversación
     * - Excluye: completed, failed, cancelled (ya liberaron la línea)
     */
    public static function getActiveCallsCount(): int
    {
        // Limpiar llamadas stuck antes de contar
        self::cleanStuckCalls();

        // Contar todas las llamadas en queue_status='processing'
        // Esto incluye: despachadas al job, iniciadas en ElevenLabs, timbrando, en conversación
        // Un slot se libera SOLO cuando el webhook marca completed/failed o cleanStuckCalls la marca
        return Llamada::where('queue_status', 'processing')->count();
    }

    /**
     * Obtener número de slots disponibles para nuevas llamadas
     */
    public static function getAvailableSlots(): int
    {
        return max(0, self::getMaxConcurrentCalls() - self::getActiveCallsCount());
    }

    /**
     * Despachar las siguientes llamadas pendientes hasta llenar los slots disponibles.
     * 
     * Usa un lock global para garantizar que solo un proceso despacha a la vez.
     * Se llama desde:
     * 1. startElevenLabsCalls (inicio manual)
     * 2. onCallCompleted (webhook de llamada terminada)
     * 3. onCallFailed (webhook de llamada fallida)
     * 4. ProcessElevenLabsCall en caso de error/fallo
     * 5. MonitorCallQueue (ciclo de monitoreo)
     * 
     * @param int|null $cotizacionId Si se especifica, prioriza llamadas de esa cotización
     * @return array Resumen de lo despachado
     */
    public static function dispatchNextCalls(?int $cotizacionId = null): array
    {
        // Lock global para evitar race conditions entre webhooks simultáneos
        $lockKey = 'call_queue_dispatch_lock';
        $lock = null;
        $lockAcquired = false;

        try {
            $lock = Cache::lock($lockKey, self::DISPATCH_LOCK_TTL);
            $lockAcquired = $lock->get();
        } catch (\Throwable $e) {
            Log::warning('CallQueueManager: Error al adquirir lock de despacho, usando fallback DB', [
                'error' => $e->getMessage(),
            ]);
            // Fallback: usar lock de BD para máxima fiabilidad
            $lockAcquired = self::acquireDbLock();
        }

        if (!$lockAcquired) {
            Log::info('CallQueueManager: Otro proceso ya está despachando, saltando', [
                'cotizacion_id' => $cotizacionId,
            ]);
            return ['dispatched' => 0, 'calls' => [], 'reason' => 'locked'];
        }

        try {
            return self::doDispatch($cotizacionId);
        } finally {
            try {
                if ($lock) {
                    $lock->release();
                } else {
                    self::releaseDbLock();
                }
            } catch (\Throwable $e) {
                // Ignorar errores al liberar lock
            }
        }
    }

    /**
     * Lógica interna de despacho (ejecutada dentro del lock)
     */
    private static function doDispatch(?int $cotizacionId): array
    {
        $activeCalls = self::getActiveCallsCount();
        $maxConcurrent = self::getMaxConcurrentCalls();
        $availableSlots = max(0, $maxConcurrent - $activeCalls);
        $dispatched = [];

        if ($availableSlots <= 0) {
            Log::info('CallQueueManager: No hay slots disponibles', [
                'active_calls' => $activeCalls,
                'max_concurrent' => $maxConcurrent,
            ]);
            return ['dispatched' => 0, 'calls' => []];
        }

        // Verificar si ya hay 7 conductores confirmados para esta cotización
        if ($cotizacionId) {
            $confirmados = LlamadaConductor::where('cotizacion_id', $cotizacionId)
                ->whereHas('driverCallResponse', function ($q) {
                    $q->where('response_status', 'accepted');
                })
                ->count();

            if ($confirmados >= 7) {
                Log::info('CallQueueManager: 7 conductores confirmados - cancelando pendientes', [
                    'cotizacion_id' => $cotizacionId,
                    'confirmados' => $confirmados,
                ]);

                Llamada::where('id_cotizacion', $cotizacionId)
                    ->where('queue_status', 'pending')
                    ->update([
                        'queue_status' => 'cancelled',
                        'call_notes' => 'Cancelada: ya se alcanzaron 7 confirmados',
                        'processing_completed_at' => now(),
                    ]);

                return ['dispatched' => 0, 'calls' => [], 'reason' => '7_confirmed'];
            }
        }

        // Buscar las siguientes llamadas pendientes (priorizar cotización especificada)
        $query = Llamada::where('queue_status', 'pending')
            ->where('status', Llamada::STATUS_PENDIENTE)
            ->orderBy('batch_number', 'asc')
            ->orderBy('batch_position', 'asc')
            ->orderBy('id_llamada', 'asc');

        if ($cotizacionId) {
            $query->where('id_cotizacion', $cotizacionId);
        }

        // Tomar más candidatos de los slots para compensar los que se salten (ya contactados)
        $candidates = $query->limit($availableSlots + 5)->get();

        if ($candidates->isEmpty() && $cotizacionId) {
            // Si no hay pendientes para esta cotización, buscar de cualquier cotización
            $candidates = Llamada::where('queue_status', 'pending')
                ->where('status', Llamada::STATUS_PENDIENTE)
                ->orderBy('batch_number', 'asc')
                ->orderBy('batch_position', 'asc')
                ->orderBy('id_llamada', 'asc')
                ->limit($availableSlots + 5)
                ->get();
        }

        if ($candidates->isEmpty()) {
            Log::info('CallQueueManager: No hay llamadas pendientes en cola');
            return ['dispatched' => 0, 'calls' => []];
        }

        foreach ($candidates as $llamada) {
            // Verificar si ya llenamos los slots
            if (count($dispatched) >= $availableSlots) {
                break;
            }

            // Verificar que el conductor no haya sido contactado ya
            if ($llamada->conductor_id) {
                $conductor = LlamadaConductor::find($llamada->conductor_id);
                if ($conductor && $conductor->hasBeenContacted()) {
                    Log::info('CallQueueManager: Conductor ya contactado, saltando', [
                        'llamada_id' => $llamada->id_llamada,
                        'conductor_id' => $conductor->id,
                    ]);
                    $llamada->update([
                        'queue_status' => 'cancelled',
                        'call_notes' => 'Cancelada: conductor ya contactado',
                        'processing_completed_at' => now(),
                    ]);
                    continue;
                }
            }

            // Marcar como processing de forma atómica (solo si sigue pending)
            $updated = Llamada::where('id_llamada', $llamada->id_llamada)
                ->where('queue_status', 'pending')
                ->update([
                    'queue_status' => 'processing',
                    'processing_started_at' => now(),
                ]);

            if ($updated === 0) {
                // Otra instancia ya tomó esta llamada
                continue;
            }

            // Despachar el job con delay según proveedor (Twilio: 1s entre cada llamada)
            $dispatchDelay = self::getDispatchDelay() * (count($dispatched) - 1);
            if ($dispatchDelay > 0) {
                ProcessElevenLabsCall::dispatch(
                    $llamada->conductor_id ?? $llamada->chofer_id,
                    $llamada->id_cotizacion,
                    $llamada->id_llamada
                )->delay(now()->addSeconds($dispatchDelay));
            } else {
                ProcessElevenLabsCall::dispatch(
                    $llamada->conductor_id ?? $llamada->chofer_id,
                    $llamada->id_cotizacion,
                    $llamada->id_llamada
                );
            }

            $dispatched[] = [
                'llamada_id' => $llamada->id_llamada,
                'cotizacion_id' => $llamada->id_cotizacion,
                'conductor_id' => $llamada->conductor_id,
                'numero' => $llamada->numero_destino,
            ];

            Log::info('CallQueueManager: Llamada despachada', [
                'llamada_id' => $llamada->id_llamada,
                'cotizacion_id' => $llamada->id_cotizacion,
                'active_calls_before' => $activeCalls + count($dispatched) - 1,
                'slots_remaining' => $availableSlots - count($dispatched),
            ]);
        }

        Log::info('CallQueueManager: Resumen de despacho', [
            'dispatched' => count($dispatched),
            'active_calls_after' => $activeCalls + count($dispatched),
            'max_concurrent' => self::getMaxConcurrentCalls(),
            'provider' => config('services.elevenlabs.call_provider', 'zadarma'),
        ]);

        return ['dispatched' => count($dispatched), 'calls' => $dispatched];
    }

    /**
     * Marcar una llamada como finalizada y despachar la siguiente.
     * Se llama desde el webhook cuando una llamada termina.
     * 
     * IMPORTANTE: Primero marca la llamada como completed (libera el slot)
     * y luego dispara dispatchNextCalls para llenar el slot vacío.
     */
    public static function onCallCompleted(string $conversationId, array $webhookData = []): void
    {
        $llamada = Llamada::where('elevenlabs_conversation_id', $conversationId)->first();

        if (!$llamada) {
            Log::warning('CallQueueManager::onCallCompleted: Llamada no encontrada', [
                'conversation_id' => $conversationId,
            ]);
            return;
        }

        $cotizacionId = $llamada->id_cotizacion;

        // Actualizar estado de la llamada (libera el slot de Zadarma)
        $callDuration = $webhookData['metadata']['call_duration_secs'] ?? null;
        $transcript = $webhookData['transcript'] ?? null;
        $analysis = $webhookData['analysis'] ?? null;

        $llamada->update([
            'status' => Llamada::STATUS_FINALIZADA,
            'call_status' => Llamada::CALL_STATUS_COMPLETED,
            'queue_status' => 'completed',
            'call_completed_at' => now(),
            'call_ended_at' => now(),
            'processing_completed_at' => now(),
            'call_duration_seconds' => $callDuration,
            'talk_duration_seconds' => $callDuration,
            'transcript' => $transcript ? self::parseTranscriptToText($transcript) : null,
            'call_notes' => 'Completada via webhook post_call_transcription',
        ]);

        // Actualizar LlamadaConductor
        if ($llamada->conductor_id) {
            $conductor = LlamadaConductor::find($llamada->conductor_id);
            if ($conductor) {
                $conductor->update([
                    'estado_llamada' => 'completada',
                    'fecha_llamada' => now(),
                ]);
            }
        }

        Log::info('CallQueueManager: Llamada completada via webhook - slot liberado', [
            'llamada_id' => $llamada->id_llamada,
            'conversation_id' => $conversationId,
            'duration' => $callDuration,
            'cotizacion_id' => $cotizacionId,
            'active_calls_after' => self::getActiveCallsCount(),
        ]);

        // Despachar siguiente llamada (para llenar el slot que se acaba de liberar)
        self::dispatchNextCalls($cotizacionId);
    }

    /**
     * Parsear transcript de ElevenLabs (array de turns) a texto legible
     */
    public static function parseTranscriptToText($transcript): ?string
    {
        if (is_string($transcript)) {
            $decoded = json_decode($transcript, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $transcript = $decoded;
            } else {
                return $transcript; // Ya es texto plano
            }
        }

        if (!is_array($transcript)) {
            return null;
        }

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

    /**
     * Marcar una llamada como fallida y despachar la siguiente.
     * Se llama desde el webhook cuando una llamada falla al iniciar.
     */
    public static function onCallFailed(string $conversationId, string $failureReason, array $metadata = []): void
    {
        $llamada = Llamada::where('elevenlabs_conversation_id', $conversationId)->first();

        if (!$llamada) {
            Log::warning('CallQueueManager::onCallFailed: Llamada no encontrada', [
                'conversation_id' => $conversationId,
            ]);
            return;
        }

        $cotizacionId = $llamada->id_cotizacion;

        // Mapear failure_reason a SIP codes
        $sipCode = match ($failureReason) {
            'busy' => Llamada::SIP_CODE_BUSY,
            'no-answer' => Llamada::SIP_CODE_NO_ANSWER,
            default => null,
        };

        // Intentar obtener SIP code del metadata
        $body = $metadata['body'] ?? [];
        if (isset($body['sip_status_code'])) {
            $sipCode = (string) $body['sip_status_code'];
        }

        $callStatus = match ($failureReason) {
            'busy' => Llamada::CALL_STATUS_BUSY,
            'no-answer' => Llamada::CALL_STATUS_NO_ANSWER,
            default => Llamada::CALL_STATUS_FAILED,
        };

        // Marcar como fallida (libera el slot de Zadarma)
        $llamada->update([
            'status' => Llamada::STATUS_FINALIZADA,
            'call_status' => $callStatus,
            'queue_status' => 'failed',
            'sip_status_code' => $sipCode,
            'sip_status_message' => $body['error_reason'] ?? $body['sip_status'] ?? $failureReason,
            'failure_reason' => $failureReason,
            'call_ended_at' => now(),
            'processing_completed_at' => now(),
            'call_notes' => "Fallo via webhook: {$failureReason}",
        ]);

        // Actualizar LlamadaConductor
        if ($llamada->conductor_id) {
            $conductor = LlamadaConductor::find($llamada->conductor_id);
            if ($conductor) {
                $conductor->update([
                    'estado_llamada' => 'fallida',
                    'fecha_llamada' => now(),
                    'notas' => "Llamada falló: {$failureReason}",
                ]);
            }
        }

        Log::info('CallQueueManager: Llamada fallida via webhook - slot liberado', [
            'llamada_id' => $llamada->id_llamada,
            'conversation_id' => $conversationId,
            'failure_reason' => $failureReason,
            'sip_code' => $sipCode,
            'cotizacion_id' => $cotizacionId,
            'active_calls_after' => self::getActiveCallsCount(),
        ]);

        // Despachar siguiente llamada (para llenar el slot que se acaba de liberar)
        self::dispatchNextCalls($cotizacionId);
    }

    /**
     * Limpiar llamadas que llevan demasiado tiempo en estado "activo" sin recibir webhook.
     * 
     * Detecta dos tipos de stuck:
     * 1. Llamadas en curso (initiated/ringing) que nunca recibieron webhook de fin
     * 2. Llamadas marcadas como processing pero con call_status NULL (job nunca inició la llamada)
     */
    private static function cleanStuckCalls(): void
    {
        $cutoff = now()->subSeconds(self::STUCK_CALL_TIMEOUT);

        // Caso 1: Llamadas en curso que nunca recibieron webhook
        $stuckActive = Llamada::where('status', Llamada::STATUS_EN_CURSO)
            ->whereIn('call_status', [
                Llamada::CALL_STATUS_INITIATED,
                Llamada::CALL_STATUS_RINGING,
            ])
            ->where('queue_status', 'processing')
            ->where('processing_started_at', '<', $cutoff)
            ->get();

        // Caso 2: Llamadas en processing que nunca iniciaron (job falló silenciosamente)
        $stuckNotStarted = Llamada::where('queue_status', 'processing')
            ->where('status', Llamada::STATUS_PENDIENTE)
            ->whereNull('call_status')
            ->where('processing_started_at', '<', $cutoff)
            ->get();

        $stuckCalls = $stuckActive->merge($stuckNotStarted);

        foreach ($stuckCalls as $llamada) {
            Log::warning('CallQueueManager: Llamada stuck detectada, liberando slot', [
                'llamada_id' => $llamada->id_llamada,
                'started_at' => $llamada->processing_started_at,
                'status' => $llamada->status,
                'call_status' => $llamada->call_status,
                'elapsed_seconds' => now()->diffInSeconds($llamada->processing_started_at),
            ]);

            $llamada->update([
                'status' => Llamada::STATUS_FINALIZADA,
                'call_status' => Llamada::CALL_STATUS_FAILED,
                'queue_status' => 'failed',
                'failure_reason' => 'Timeout: no se recibió webhook en ' . self::STUCK_CALL_TIMEOUT . ' segundos',
                'call_ended_at' => now(),
                'processing_completed_at' => now(),
            ]);
        }
    }

    /**
     * Obtener estado actual de la cola
     */
    public static function getQueueStatus(?int $cotizacionId = null): array
    {
        $query = Llamada::query();
        if ($cotizacionId) {
            $query->where('id_cotizacion', $cotizacionId);
        }

        return [
            'active_calls' => self::getActiveCallsCount(),
            'max_concurrent' => self::getMaxConcurrentCalls(),
            'available_slots' => self::getAvailableSlots(),
            'pending' => (clone $query)->where('queue_status', 'pending')->count(),
            'processing' => (clone $query)->where('queue_status', 'processing')->count(),
            'completed' => (clone $query)->where('queue_status', 'completed')->count(),
            'failed' => (clone $query)->where('queue_status', 'failed')->count(),
            'cancelled' => (clone $query)->where('queue_status', 'cancelled')->count(),
        ];
    }

    /**
     * Fallback: Lock usando MySQL GET_LOCK (funciona si file cache falla)
     */
    private static function acquireDbLock(): bool
    {
        try {
            $result = DB::select("SELECT GET_LOCK('call_queue_dispatch', 5) as acquired");
            return ($result[0]->acquired ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function releaseDbLock(): void
    {
        try {
            DB::select("SELECT RELEASE_LOCK('call_queue_dispatch')");
        } catch (\Throwable $e) {
            // Ignorar
        }
    }
}
