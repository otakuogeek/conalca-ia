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
use App\Services\CallQueueManager;

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
            Log::info('ProcessBatchElevenLabsCalls: Delegando lote legacy al CallQueueManager', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'max_concurrent' => $this->maxConcurrentCalls
            ]);

            $result = CallQueueManager::dispatchNextCalls($this->cotizacionId);

            Log::info('ProcessBatchElevenLabsCalls: Resultado de despacho centralizado', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessBatchElevenLabsCalls: Error en lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

        CallQueueManager::dispatchNextCalls($this->cotizacionId);
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
