<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Models\LlamadaConductor;
use App\Jobs\ProcessElevenLabsCall;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitorCallQueue extends Command
{
    protected $signature = 'calls:monitor
                            {--interval=15 : Segundos entre cada verificación}
                            {--stuck-timeout=180 : Segundos para considerar una llamada atascada en processing}
                            {--max-retries=3 : Máximo de reintentos para llamadas fallidas}
                            {--max-global=5 : Máximo de llamadas procesando simultáneamente (global, todas las órdenes)}
                            {--max-per-order=2 : Máximo de llamadas procesando por orden}
                            {--once : Ejecutar solo una vez y salir}';

    protected $description = 'Monitor continuo de la cola de llamadas: detecta y resuelve llamadas atascadas, procesa pendientes y actualiza estados';

    private int $iteration = 0;
    private int $maxGlobalConcurrent = 5;
    private int $maxPerOrder = 2;

    public function handle()
    {
        $interval = (int) $this->option('interval');
        $stuckTimeout = (int) $this->option('stuck-timeout');
        $maxRetries = (int) $this->option('max-retries');
        $this->maxGlobalConcurrent = (int) $this->option('max-global');
        $this->maxPerOrder = (int) $this->option('max-per-order');
        $runOnce = $this->option('once');

        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   MONITOR CONTINUO DE COLA DE LLAMADAS          ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->line("Intervalo: {$interval}s | Timeout stuck: {$stuckTimeout}s | Max retries: {$maxRetries}");
        $this->line("Concurrencia global: {$this->maxGlobalConcurrent} | Max por orden: {$this->maxPerOrder}");
        
        if (!$runOnce) {
            $this->info('Modo daemon - se detendrá automáticamente cuando no haya llamadas activas.');
        }
        $this->line('');

        $idleCycles = 0;
        $maxIdleCycles = 10; // Después de 10 ciclos sin actividad (150s con interval=15), se detiene

        while (true) {
            $this->iteration++;
            
            try {
                $this->runMonitorCycle($stuckTimeout, $maxRetries);
            } catch (\Exception $e) {
                $this->error("[Iteración #{$this->iteration}] Error: " . $e->getMessage());
                Log::error('MonitorCallQueue: Error en ciclo', [
                    'iteration' => $this->iteration,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            if ($runOnce) {
                $this->info('Ejecución única completada.');
                return 0;
            }

            // Auto-detener si no hay actividad por varios ciclos
            $snapshot = $this->getQueueSnapshot();
            $hasActivity = ($snapshot['pending'] + $snapshot['queued'] + $snapshot['processing']) > 0;
            
            if ($hasActivity) {
                $idleCycles = 0; // Resetear contador
            } else {
                $idleCycles++;
                if ($idleCycles >= $maxIdleCycles) {
                    $this->info("Sin actividad por {$idleCycles} ciclos. Monitor detenido automáticamente.");
                    Log::info('MonitorCallQueue: Detenido automáticamente por inactividad', [
                        'idle_cycles' => $idleCycles,
                        'total_iterations' => $this->iteration
                    ]);
                    return 0;
                }
                $this->line("  (sin actividad, ciclo idle {$idleCycles}/{$maxIdleCycles})");
            }

            sleep($interval);
        }

        return 0;
    }

    private function runMonitorCycle(int $stuckTimeout, int $maxRetries): void
    {
        $now = Carbon::now();
        $this->line("[{$now->format('H:i:s')}] ── Ciclo #{$this->iteration} ──");

        // PASO 1: Resolver llamadas atascadas en "processing"
        $unstuck = $this->resolveStuckProcessing($stuckTimeout, $maxRetries);

        // PASO 2: Resolver llamadas atascadas con queue_status=processing pero que ya tienen conversation_id y response
        $resolvedCompleted = $this->resolveCompletedButStuck();

        // PASO 3: Procesar llamadas pendientes en cola
        $dispatched = $this->dispatchPendingCalls();

        // PASO 4: Re-encolar llamadas fallidas con reintentos disponibles
        $requeued = $this->requeueRetryableCalls($maxRetries);

        // PASO 5: Sincronizar estados inconsistentes
        $synced = $this->syncInconsistentStates();

        // Mostrar resumen
        $stats = $this->getQueueSnapshot();
        $this->line(sprintf(
            "  Atascadas: %d resueltas | Completadas pendientes: %d | Despachadas: %d | Re-encoladas: %d | Sincronizadas: %d",
            $unstuck, $resolvedCompleted, $dispatched, $requeued, $synced
        ));
        $this->line(sprintf(
            "  Cola global: pendientes=%d | en_cola=%d | procesando=%d | completadas(24h)=%d | fallidas(24h)=%d",
            $stats['pending'], $stats['queued'], $stats['processing'], $stats['completed'], $stats['failed']
        ));
        
        // Desglose por orden si hay actividad
        if ($stats['pending'] > 0 || $stats['processing'] > 0) {
            $this->showOrderBreakdown();
        }
        $this->line('');
    }

    /**
     * Detectar y resolver llamadas atascadas en estado "processing" por más tiempo del timeout
     */
    private function resolveStuckProcessing(int $stuckTimeout, int $maxRetries): int
    {
        $cutoff = Carbon::now()->subSeconds($stuckTimeout);

        $stuckCalls = Llamada::where('queue_status', 'processing')
            ->where(function ($q) use ($cutoff) {
                $q->where('processing_started_at', '<', $cutoff)
                  ->orWhereNull('processing_started_at');
            })
            ->get();

        $resolved = 0;
        foreach ($stuckCalls as $call) {
            $elapsed = $call->processing_started_at
                ? Carbon::now()->diffInSeconds($call->processing_started_at)
                : 'N/A (sin timestamp)';

            Log::warning('MonitorCallQueue: Llamada atascada detectada', [
                'llamada_id' => $call->id_llamada,
                'cotizacion_id' => $call->id_cotizacion,
                'elapsed_seconds' => $elapsed,
                'processing_attempts' => $call->processing_attempts,
                'conversation_id' => $call->elevenlabs_conversation_id,
            ]);

            // Si tiene conversation_id, la llamada se inició pero no se actualizó estado
            if ($call->elevenlabs_conversation_id) {
                $call->update([
                    'queue_status' => 'completed',
                    'processing_completed_at' => Carbon::now(),
                    'internal_notes' => ($call->internal_notes ?? '') . 
                        "\n[{$this->timestamp()}] Monitor: Marcada como completada (tenía conversation_id pero estaba atascada en processing)"
                ]);
                $this->warn("  → Llamada #{$call->id_llamada}: tenía conversation_id, marcada como completada");
            } elseif ($call->processing_attempts < $maxRetries) {
                // Re-encolar para reintento - resetear TODOS los campos para que el dispatch la recoja
                $call->update([
                    'queue_status' => 'pending',
                    'status' => Llamada::STATUS_PENDIENTE,
                    'call_status' => null,
                    'failure_reason' => null,
                    'processing_started_at' => null,
                    'processing_completed_at' => null,
                    'internal_notes' => ($call->internal_notes ?? '') . 
                        "\n[{$this->timestamp()}] Monitor: Re-encolada (intento {$call->processing_attempts}/{$maxRetries}, atascada {$elapsed}s)"
                ]);
                $this->warn("  → Llamada #{$call->id_llamada}: re-encolada (intento {$call->processing_attempts}/{$maxRetries})");
            } else {
                // Marcar como fallida definitivamente
                $call->update([
                    'queue_status' => 'failed',
                    'status' => 'finalizada',
                    'call_status' => 'failed',
                    'failure_reason' => "Atascada en processing por {$elapsed}s, max reintentos alcanzado",
                    'processing_completed_at' => Carbon::now(),
                    'internal_notes' => ($call->internal_notes ?? '') . 
                        "\n[{$this->timestamp()}] Monitor: Fallida definitivamente (max reintentos alcanzado, atascada {$elapsed}s)"
                ]);
                $this->error("  → Llamada #{$call->id_llamada}: fallida definitivamente (max reintentos)");
            }

            $resolved++;
        }

        return $resolved;
    }

    /**
     * Resolver llamadas que ya tienen respuesta del conductor pero siguen en processing
     */
    private function resolveCompletedButStuck(): int
    {
        $resolved = 0;

        // Buscar llamadas en processing que tienen conductor con respuesta
        $processingCalls = Llamada::where('queue_status', 'processing')
            ->whereNotNull('conductor_id')
            ->get();

        foreach ($processingCalls as $call) {
            $conductor = LlamadaConductor::find($call->conductor_id);
            
            if (!$conductor) continue;

            // Si el conductor ya tiene una respuesta registrada
            if ($conductor->respuesta_llamada && in_array($conductor->respuesta_llamada, ['aceptado', 'rechazado', 'no_contesto', 'ocupado', 'no_disponible'])) {
                $status = ($conductor->respuesta_llamada === 'aceptado') ? 'aceptada' : 'finalizada';
                
                $call->update([
                    'queue_status' => 'completed',
                    'status' => $status,
                    'call_status' => 'completed',
                    'processing_completed_at' => Carbon::now(),
                    'internal_notes' => ($call->internal_notes ?? '') . 
                        "\n[{$this->timestamp()}] Monitor: Completada (conductor respondió: {$conductor->respuesta_llamada})"
                ]);
                
                $this->info("  → Llamada #{$call->id_llamada}: conductor respondió '{$conductor->respuesta_llamada}', marcada completada");
                $resolved++;
            }

            // Si tiene driver_call_response_id, ya completó
            if ($conductor->driver_call_response_id) {
                $call->update([
                    'queue_status' => 'completed',
                    'processing_completed_at' => Carbon::now(),
                    'internal_notes' => ($call->internal_notes ?? '') . 
                        "\n[{$this->timestamp()}] Monitor: Completada (tiene driver_call_response_id)"
                ]);
                $resolved++;
            }
        }

        return $resolved;
    }

    /**
     * Despachar llamadas pendientes usando CallQueueManager centralizado.
     * 
     * El CallQueueManager controla el límite de 2 llamadas simultáneas (Zadarma SIP).
     * El monitor solo actúa como trigger de seguridad por si un webhook no disparó el siguiente despacho.
     */
    private function dispatchPendingCalls(): int
    {
        $dispatched = 0;

        // Obtener todas las cotizaciones con llamadas pendientes
        $ordenesPendientes = Llamada::where('queue_status', 'pending')
            ->select('id_cotizacion', DB::raw('COUNT(*) as total_pendientes'))
            ->groupBy('id_cotizacion')
            ->orderBy(DB::raw('MIN(created_at)'))
            ->get();

        if ($ordenesPendientes->isEmpty()) {
            return 0;
        }

        $activeNow = \App\Services\CallQueueManager::getActiveCallsCount();
        $slotsDisponibles = \App\Services\CallQueueManager::getAvailableSlots();
        
        $this->line("  [dispatch] {$ordenesPendientes->count()} órdenes con pendientes | {$slotsDisponibles} slots disponibles (activas: {$activeNow}/" . \App\Services\CallQueueManager::getMaxConcurrentCalls() . ")");

        if ($slotsDisponibles <= 0) {
            $this->line("  [dispatch] Sin slots disponibles, esperando que termine una llamada");
            return 0;
        }

        // Usar CallQueueManager para despachar respetando el límite de 2 concurrentes
        foreach ($ordenesPendientes as $orden) {
            if ($slotsDisponibles <= 0) break;

            try {
                $result = \App\Services\CallQueueManager::dispatchNextCalls($orden->id_cotizacion);
                $dispatchedNow = $result['dispatched'] ?? 0;

                if ($dispatchedNow > 0) {
                    $this->info("  → Orden #{$orden->id_cotizacion}: {$dispatchedNow} llamada(s) despachada(s) (pendientes: {$orden->total_pendientes})");
                    $dispatched += $dispatchedNow;
                    $slotsDisponibles -= $dispatchedNow;
                }

                // Si la razón es 7_confirmed, informar
                if (($result['reason'] ?? '') === '7_confirmed') {
                    $this->info("  → Orden #{$orden->id_cotizacion}: 7 conductores confirmados, pendientes canceladas");
                }
            } catch (\Exception $e) {
                Log::error('MonitorCallQueue: Error despachando via CallQueueManager', [
                    'cotizacion_id' => $orden->id_cotizacion,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $dispatched;
    }
    /**
     * Re-encolar llamadas fallidas que aún tienen reintentos disponibles
     */
    private function requeueRetryableCalls(int $maxRetries): int
    {
        $requeued = 0;

        $retryableCalls = Llamada::where('queue_status', 'failed')
            ->where('processing_attempts', '<', $maxRetries)
            ->where(function ($q) {
                // Solo re-encolar las que fallaron hace más de 2 minutos (evitar loops)
                $q->where('processing_completed_at', '<', Carbon::now()->subMinutes(2))
                  ->orWhereNull('processing_completed_at');
            })
            ->whereNull('elevenlabs_conversation_id') // No re-encolar las que ya tuvieron conversación
            ->limit(5) // Procesar de a máximo 5 para no saturar
            ->get();

        foreach ($retryableCalls as $call) {
            $nextAttempt = $call->processing_attempts + 1;
            $ts = $this->timestamp();
            $call->update([
                'queue_status' => 'pending',
                'status' => Llamada::STATUS_PENDIENTE,
                'call_status' => null,
                'failure_reason' => null,
                'processing_started_at' => null,
                'processing_completed_at' => null,
                'processing_attempts' => $nextAttempt,
                'last_attempt_at' => Carbon::now(),
                'internal_notes' => ($call->internal_notes ?? '') . 
                    "\n[{$ts}] Monitor: Re-encolada automáticamente (intento {$nextAttempt}/{$maxRetries})"
            ]);
            $requeued++;
        }

        return $requeued;
    }

    /**
     * Sincronizar estados inconsistentes entre llamada y conductor
     */
    private function syncInconsistentStates(): int
    {
        $synced = 0;

        // Llamadas con status "en_curso" pero queue_status "completed" o "failed" hace más de 5 minutos
        $inconsistent = Llamada::where('status', 'en_curso')
            ->whereIn('queue_status', ['completed', 'failed'])
            ->where('processing_completed_at', '<', Carbon::now()->subMinutes(5))
            ->get();

        foreach ($inconsistent as $call) {
            // Verificar si el conductor tiene respuesta
            if ($call->conductor_id) {
                $conductor = LlamadaConductor::find($call->conductor_id);
                if ($conductor && $conductor->respuesta_llamada) {
                    $newStatus = match ($conductor->respuesta_llamada) {
                        'aceptado' => 'aceptada',
                        'rechazado' => 'rechazada',
                        default => 'finalizada',
                    };
                    $call->update([
                        'status' => $newStatus,
                        'call_status' => 'completed',
                        'internal_notes' => ($call->internal_notes ?? '') .
                            "\n[{$this->timestamp()}] Monitor: Sync estado a '{$newStatus}' (conductor respondió: {$conductor->respuesta_llamada})"
                    ]);
                    $synced++;
                    continue;
                }
            }

            // Si lleva más de 10 minutos en en_curso sin respuesta, finalizar
            $completedAt = $call->processing_completed_at ? Carbon::parse($call->processing_completed_at) : null;
            if ($completedAt && $completedAt->diffInMinutes(Carbon::now()) > 10) {
                $call->update([
                    'status' => 'finalizada',
                    'call_status' => $call->call_status ?: 'completed',
                    'internal_notes' => ($call->internal_notes ?? '') .
                        "\n[{$this->timestamp()}] Monitor: Finalizada (en_curso > 10 min sin respuesta)"
                ]);
                $synced++;
            }
        }

        // Llamadas con queue_status "queued" que nunca se movieron (legacy del sistema anterior)
        $legacyQueued = Llamada::where('queue_status', 'queued')
            ->where('queued_at', '<', Carbon::now()->subMinutes(10))
            ->get();

        foreach ($legacyQueued as $call) {
            $call->update([
                'queue_status' => 'pending',
                'internal_notes' => ($call->internal_notes ?? '') .
                    "\n[{$this->timestamp()}] Monitor: Migrada de 'queued' a 'pending' (legacy)"
            ]);
            $synced++;
        }

        return $synced;
    }

    /**
     * Mostrar desglose de cola por orden/cotización
     */
    private function showOrderBreakdown(): void
    {
        $orders = Llamada::whereIn('queue_status', ['pending', 'processing', 'queued'])
            ->select(
                'id_cotizacion',
                DB::raw("SUM(CASE WHEN queue_status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN queue_status = 'processing' THEN 1 ELSE 0 END) as processing"),
                DB::raw("SUM(CASE WHEN queue_status = 'queued' THEN 1 ELSE 0 END) as queued")
            )
            ->groupBy('id_cotizacion')
            ->orderByDesc(DB::raw("SUM(CASE WHEN queue_status = 'pending' THEN 1 ELSE 0 END)"))
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) return;

        $this->line("  ┌─ Desglose por orden:");
        foreach ($orders as $order) {
            $this->line(sprintf(
                "  │  Orden #%d → pend:%d | proc:%d | cola:%d",
                $order->id_cotizacion,
                $order->pending,
                $order->processing,
                $order->queued
            ));
        }
        $this->line("  └─");
    }

    private function getQueueSnapshot(): array
    {
        return [
            'pending' => Llamada::where('queue_status', 'pending')->count(),
            'queued' => Llamada::where('queue_status', 'queued')->count(),
            'processing' => Llamada::where('queue_status', 'processing')->count(),
            'completed' => Llamada::where('queue_status', 'completed')
                ->where('processing_completed_at', '>', Carbon::now()->subHours(24))
                ->count(),
            'failed' => Llamada::where('queue_status', 'failed')
                ->where('processing_completed_at', '>', Carbon::now()->subHours(24))
                ->count(),
        ];
    }

    private function timestamp(): string
    {
        return Carbon::now()->format('Y-m-d H:i:s');
    }
}
