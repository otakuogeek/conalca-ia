<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Llamada;
use App\Jobs\ProcessElevenLabsCall;

class ProcessBatchElevenLabsCalls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cotizacionId;
    public $batchNumber;
    public $maxConcurrentCalls;
    public $delayBetweenBatches;

    /**
     * Create a new job instance.
     */
    public function __construct($cotizacionId, $batchNumber = 1, $maxConcurrentCalls = 3, $delayBetweenBatches = 150)
    {
        $this->cotizacionId = $cotizacionId;
        $this->batchNumber = $batchNumber;
        $this->maxConcurrentCalls = $maxConcurrentCalls;
        $this->delayBetweenBatches = $delayBetweenBatches;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('ProcessBatchElevenLabsCalls: Iniciando procesamiento de lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'max_concurrent' => $this->maxConcurrentCalls
            ]);

            // Buscar llamadas pendientes para este lote específico
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

            Log::info('ProcessBatchElevenLabsCalls: Procesando llamadas del lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'total_llamadas' => $llamadas->count(),
                'llamadas_ids' => $llamadas->pluck('id_llamada')->toArray()
            ]);

            // Procesar cada llamada del lote con un pequeño delay entre ellas
            foreach ($llamadas as $index => $llamada) {
                // Delay escalonado de 5 segundos entre llamadas del mismo lote
                $delay = $index * 5;
                
                ProcessElevenLabsCall::dispatch(
                    $llamada->chofer_id, 
                    $this->cotizacionId, 
                    $llamada->id_llamada
                )->delay(now()->addSeconds($delay));

                Log::info('ProcessBatchElevenLabsCalls: Llamada individual programada', [
                    'llamada_id' => $llamada->id_llamada,
                    'batch_number' => $this->batchNumber,
                    'delay_seconds' => $delay
                ]);
            }

            // Verificar si hay más lotes pendientes
            $nextBatchLlamadas = Llamada::where('id_cotizacion', $this->cotizacionId)
                ->where('queue_status', 'pending')
                ->where('batch_number', $this->batchNumber + 1)
                ->exists();

            if ($nextBatchLlamadas) {
                // Programar el siguiente lote con el delay configurado
                ProcessBatchElevenLabsCalls::dispatch(
                    $this->cotizacionId,
                    $this->batchNumber + 1,
                    $this->maxConcurrentCalls,
                    $this->delayBetweenBatches
                )->delay(now()->addSeconds($this->delayBetweenBatches));

                Log::info('ProcessBatchElevenLabsCalls: Siguiente lote programado', [
                    'cotizacion_id' => $this->cotizacionId,
                    'next_batch_number' => $this->batchNumber + 1,
                    'delay_seconds' => $this->delayBetweenBatches
                ]);
            } else {
                Log::info('ProcessBatchElevenLabsCalls: Todos los lotes completados', [
                    'cotizacion_id' => $this->cotizacionId,
                    'final_batch_number' => $this->batchNumber
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessBatchElevenLabsCalls: Error procesando lote', [
                'cotizacion_id' => $this->cotizacionId,
                'batch_number' => $this->batchNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
