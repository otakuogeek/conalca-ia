<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Llamada;
use App\Jobs\ProcessElevenLabsCall;

class ProcessBatchElevenLabsCalls implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cotizacionId;
    public $batchNumber;
    public $maxConcurrentCalls;
    public $delayBetweenBatches;

    public $tries = 3;
    public $backoff = [10, 30];
    public $timeout = 300;
    public $uniqueFor = 300;

    public function __construct($cotizacionId, $batchNumber = 1, $maxConcurrentCalls = 2, $delayBetweenBatches = 60)
    {
        $this->cotizacionId = $cotizacionId;
        $this->batchNumber = $batchNumber;
        $this->maxConcurrentCalls = $maxConcurrentCalls;
        $this->delayBetweenBatches = $delayBetweenBatches;
    }

    public function uniqueId(): string
    {
        return "batch_calls_{$this->cotizacionId}_{$this->batchNumber}";
    }

    public function handle(): void
    {
        $lockKey = "processing_batch_{$this->cotizacionId}_{$this->batchNumber}";
        $lock = null;
        $lockAcquired = false;

        try {
            // Asegurar que el directorio de caché existe
            $cacheDir = storage_path('framework/cache/data');
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }

            $lock = Cache::lock($lockKey, 240);
            $lockAcquired = $lock->get();
        } catch (\Throwable $e) {
            Log::warning('ProcessBatchElevenLabsCalls: No se pudo adquirir lock de caché, continuando sin lock', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage()
            ]);
            // Continuar sin lock - ShouldBeUnique ya protege contra duplicados
            $lockAcquired = true;
            $lock = null;
        }

        if (!$lockAcquired) {
            Log::warning('ProcessBatchElevenLabsCalls: Lock no adquirido - otro proceso ya maneja este lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber
            ]);
            return;
        }

        try {
            Log::info('ProcessBatchElevenLabsCalls: Iniciando lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'max_concurrent' => $this->maxConcurrentCalls
            ]);

            $llamadas = Llamada::where('id_cotizacion', $this->cotizacionId)
                ->where('queue_status', 'pending')
                ->where('batch_number', $this->batchNumber)
                ->limit($this->maxConcurrentCalls)
                ->get();

            if ($llamadas->isEmpty()) {
                Log::info('ProcessBatchElevenLabsCalls: No hay llamadas pendientes para este lote', [
                    'cotizacion_id' => $this->cotizacionId,
                    'batch_number' => $this->batchNumber
                ]);
                return;
            }

            // Marcar como processing de forma atómica (solo las que aún estén pending)
            $llamadaIds = $llamadas->pluck('id_llamada')->toArray();
            $updated = Llamada::whereIn('id_llamada', $llamadaIds)
                ->where('queue_status', 'pending')
                ->update(['queue_status' => 'processing', 'processing_started_at' => now()]);

            if ($updated === 0) {
                Log::warning('ProcessBatchElevenLabsCalls: Llamadas ya tomadas por otro proceso', [
                    'cotizacion_id' => $this->cotizacionId,
                    'batch_number' => $this->batchNumber
                ]);
                return;
            }

            Log::info('ProcessBatchElevenLabsCalls: Llamadas marcadas processing', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'updated' => $updated
            ]);

            // Verificar si ya hay 7 confirmados
            $confirmados = \App\Models\LlamadaConductor::where('cotizacion_id', $this->cotizacionId)
                ->whereHas('driverCallResponse', function($q) {
                    $q->where('response_status', 'accepted');
                })
                ->count();
            
            if ($confirmados >= 7) {
                Log::info('ProcessBatchElevenLabsCalls: 7 confirmados - DETENIENDO', [
                    'cotizacion_id' => $this->cotizacionId,
                    'confirmados' => $confirmados
                ]);
                
                Llamada::where('id_cotizacion', $this->cotizacionId)
                    ->whereIn('queue_status', ['pending', 'processing'])
                    ->update([
                        'queue_status' => 'cancelled',
                        'call_notes' => 'Cancelada: ya se alcanzaron 7 confirmados',
                        'processing_completed_at' => now()
                    ]);
                
                return;
            }
            
            // Despachar llamadas individuales con delay escalonado
            foreach ($llamadas as $index => $llamada) {
                $delay = $index * 60;
                
                ProcessElevenLabsCall::dispatch(
                    $llamada->conductor_id ?? $llamada->chofer_id, 
                    $this->cotizacionId, 
                    $llamada->id_llamada
                )->delay(now()->addSeconds($delay));

                Log::info('ProcessBatchElevenLabsCalls: Llamada programada', [
                    'llamada_id' => $llamada->id_llamada,
                    'batch' => $this->batchNumber,
                    'delay_s' => $delay
                ]);
            }

            // Verificar siguiente lote
            $this->dispatchNextBatchIfExists();

        } catch (\Exception $e) {
            Log::error('ProcessBatchElevenLabsCalls: Error en lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Aún con error, intentar despachar siguiente lote para no romper la cadena
            $this->dispatchNextBatchIfExists();
        } finally {
            if ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // Ignorar errores al liberar lock
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessBatchElevenLabsCalls: Job FALLIDO', [
            'cotizacion_id' => $this->cotizacionId,
            'batch_number' => $this->batchNumber,
            'error' => $exception->getMessage()
        ]);

        Llamada::where('id_cotizacion', $this->cotizacionId)
            ->where('batch_number', $this->batchNumber)
            ->where('queue_status', 'processing')
            ->update([
                'queue_status' => 'failed',
                'failure_reason' => 'Batch falló: ' . substr($exception->getMessage(), 0, 200),
                'processing_completed_at' => now()
            ]);

        // Aún con fallo, despachar siguiente lote para no romper la cadena
        $this->dispatchNextBatchIfExists();
    }

    /**
     * Despachar el siguiente lote si existe, para no romper la cadena de procesamiento.
     */
    private function dispatchNextBatchIfExists(): void
    {
        try {
            $nextBatchExists = Llamada::where('id_cotizacion', $this->cotizacionId)
                ->where('queue_status', 'pending')
                ->where('batch_number', $this->batchNumber + 1)
                ->exists();

            if ($nextBatchExists) {
                ProcessBatchElevenLabsCalls::dispatch(
                    $this->cotizacionId,
                    $this->batchNumber + 1,
                    $this->maxConcurrentCalls,
                    $this->delayBetweenBatches
                )->delay(now()->addSeconds($this->delayBetweenBatches));

                Log::info('ProcessBatchElevenLabsCalls: Siguiente lote programado', [
                    'cotizacion_id' => $this->cotizacionId,
                    'next_batch' => $this->batchNumber + 1,
                    'delay_s' => $this->delayBetweenBatches
                ]);
            } else {
                // Buscar cualquier lote pendiente posterior (no necesariamente el siguiente número)
                $nextPendingBatch = Llamada::where('id_cotizacion', $this->cotizacionId)
                    ->where('queue_status', 'pending')
                    ->where('batch_number', '>', $this->batchNumber)
                    ->orderBy('batch_number')
                    ->value('batch_number');

                if ($nextPendingBatch) {
                    ProcessBatchElevenLabsCalls::dispatch(
                        $this->cotizacionId,
                        $nextPendingBatch,
                        $this->maxConcurrentCalls,
                        $this->delayBetweenBatches
                    )->delay(now()->addSeconds($this->delayBetweenBatches));

                    Log::info('ProcessBatchElevenLabsCalls: Siguiente lote pendiente programado (salto)', [
                        'cotizacion_id' => $this->cotizacionId,
                        'next_batch' => $nextPendingBatch,
                        'delay_s' => $this->delayBetweenBatches
                    ]);
                } else {
                    Log::info('ProcessBatchElevenLabsCalls: Todos los lotes completados', [
                        'cotizacion_id' => $this->cotizacionId,
                        'final_batch' => $this->batchNumber
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ProcessBatchElevenLabsCalls: Error al despachar siguiente lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage()
            ]);
        }
    }
}
