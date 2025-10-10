<?php

namespace App\Services;

use App\Models\Llamada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio para gestionar la cola de llamadas con límite de concurrencia
 * 
 * Características:
 * - Límite de 3 llamadas concurrentes
 * - Retraso de 90 segundos entre lotes
 * - Gestión automática de prioridades
 * - Reintentos automáticos en caso de fallo
 */
class CallQueueService
{
    const MAX_CONCURRENT_CALLS = 3;
    const BATCH_DELAY_SECONDS = 90;
    const MAX_PROCESSING_ATTEMPTS = 3;
    const CALL_TIMEOUT_SECONDS = 120; // 2 minutos
    
    /**
     * Agregar una llamada a la cola
     */
    public function enqueueCall(Llamada $llamada, int $priority = 10): bool
    {
        try {
            DB::beginTransaction();
            
            // Obtener el próximo número de lote disponible
            $nextBatch = $this->getNextAvailableBatch();
            $batchPosition = $this->getNextBatchPosition($nextBatch);
            
            // Calcular tiempo estimado de espera
            $estimatedWait = $this->calculateEstimatedWait($nextBatch, $batchPosition);
            
            // Actualizar la llamada con información de cola
            $llamada->update([
                'queue_status' => 'queued',
                'batch_number' => $nextBatch,
                'batch_position' => $batchPosition,
                'queue_priority' => $priority,
                'queued_at' => Carbon::now(),
                'estimated_wait_seconds' => $estimatedWait,
                'processing_attempts' => 0
            ]);
            
            DB::commit();
            
            Log::info("Llamada #{$llamada->id_llamada} agregada a la cola", [
                'batch_number' => $nextBatch,
                'batch_position' => $batchPosition,
                'estimated_wait' => $estimatedWait,
                'priority' => $priority
            ]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al agregar llamada a la cola: " . $e->getMessage(), [
                'llamada_id' => $llamada->id_llamada,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Procesar el siguiente lote de llamadas
     */
    public function processNextBatch(): array
    {
        try {
            // Verificar si hay espacio para procesar (máximo 3 llamadas activas)
            $activeCalls = $this->getActiveCallsCount();
            
            if ($activeCalls >= self::MAX_CONCURRENT_CALLS) {
                Log::info("Sistema ocupado: {$activeCalls} llamadas activas. Esperando...");
                return [
                    'success' => false,
                    'message' => 'Sistema ocupado con llamadas activas',
                    'active_calls' => $activeCalls
                ];
            }
            
            // Verificar si el último lote necesita más tiempo (90 segundos)
            if (!$this->canProcessNewBatch()) {
                $waitTime = $this->getTimeUntilNextBatch();
                Log::info("Esperando {$waitTime} segundos antes del siguiente lote");
                return [
                    'success' => false,
                    'message' => 'Esperando tiempo de retraso entre lotes',
                    'wait_seconds' => $waitTime
                ];
            }
            
            // Obtener el siguiente lote de llamadas pendientes
            $nextBatch = $this->getNextBatchToProcess();
            
            if (!$nextBatch) {
                return [
                    'success' => false,
                    'message' => 'No hay llamadas pendientes en la cola'
                ];
            }
            
            // Obtener hasta 3 llamadas del siguiente lote
            $calls = Llamada::where('queue_status', 'queued')
                ->where('batch_number', $nextBatch)
                ->orderBy('batch_position')
                ->limit(self::MAX_CONCURRENT_CALLS)
                ->get();
            
            if ($calls->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron llamadas en el lote'
                ];
            }
            
            $processedCalls = [];
            
            // Marcar llamadas como "en procesamiento"
            foreach ($calls as $call) {
                $call->update([
                    'queue_status' => 'processing',
                    'processing_started_at' => Carbon::now(),
                    'processing_attempts' => $call->processing_attempts + 1,
                    'last_attempt_at' => Carbon::now()
                ]);
                
                $processedCalls[] = [
                    'id' => $call->id_llamada,
                    'numero_destino' => $call->numero_destino,
                    'batch_number' => $call->batch_number,
                    'batch_position' => $call->batch_position
                ];
            }
            
            Log::info("Lote #{$nextBatch} listo para procesar", [
                'calls_count' => $calls->count(),
                'calls' => $processedCalls
            ]);
            
            return [
                'success' => true,
                'message' => 'Lote listo para procesamiento',
                'batch_number' => $nextBatch,
                'calls' => $processedCalls,
                'calls_data' => $calls
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al procesar siguiente lote: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar lote: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Marcar una llamada como completada
     */
    public function completeCall(int $llamadaId, bool $success = true): bool
    {
        try {
            $llamada = Llamada::findOrFail($llamadaId);
            
            $llamada->update([
                'queue_status' => $success ? 'completed' : 'failed',
                'processing_completed_at' => Carbon::now()
            ]);
            
            Log::info("Llamada #{$llamadaId} marcada como " . ($success ? 'completada' : 'fallida'));
            
            // Si falló y aún tiene intentos disponibles, reencolar
            if (!$success && $llamada->processing_attempts < self::MAX_PROCESSING_ATTEMPTS) {
                $this->requeueCall($llamada);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al completar llamada #{$llamadaId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reencolar una llamada fallida
     */
    public function requeueCall(Llamada $llamada): bool
    {
        try {
            // Incrementar prioridad (menor número = mayor prioridad)
            $newPriority = max(1, $llamada->queue_priority - 2);
            
            $llamada->update([
                'queue_status' => 'queued',
                'queue_priority' => $newPriority,
                'queued_at' => Carbon::now()
            ]);
            
            Log::info("Llamada #{$llamada->id_llamada} reencolada con prioridad {$newPriority}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error al reencolar llamada: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener el siguiente número de lote disponible
     */
    private function getNextAvailableBatch(): int
    {
        // Obtener el lote con espacio disponible o crear uno nuevo
        $currentBatch = Llamada::where('queue_status', 'queued')
            ->max('batch_number') ?? 0;
        
        // Contar llamadas en el lote actual
        $callsInCurrentBatch = Llamada::where('queue_status', 'queued')
            ->where('batch_number', $currentBatch)
            ->count();
        
        // Si el lote actual está lleno (3 llamadas), crear uno nuevo
        if ($callsInCurrentBatch >= self::MAX_CONCURRENT_CALLS) {
            return $currentBatch + 1;
        }
        
        // Si es el primer lote o el lote actual tiene espacio
        return max(1, $currentBatch);
    }
    
    /**
     * Obtener la siguiente posición dentro del lote
     */
    private function getNextBatchPosition(int $batchNumber): int
    {
        $position = Llamada::where('batch_number', $batchNumber)
            ->where('queue_status', 'queued')
            ->max('batch_position') ?? 0;
        
        return min(3, $position + 1);
    }
    
    /**
     * Calcular tiempo estimado de espera
     */
    private function calculateEstimatedWait(int $batchNumber, int $batchPosition): int
    {
        // Lotes completamente pendientes antes de este
        $completeBatchesBefore = $batchNumber - 1;
        
        // Cada lote toma aproximadamente 90 segundos
        $waitFromBatches = $completeBatchesBefore * self::BATCH_DELAY_SECONDS;
        
        // Agregar tiempo por posición dentro del lote (distribución equitativa)
        $positionDelay = ($batchPosition - 1) * 5; // 5 segundos entre cada llamada del mismo lote
        
        return $waitFromBatches + $positionDelay;
    }
    
    /**
     * Obtener el número de llamadas activas
     */
    private function getActiveCallsCount(): int
    {
        return Llamada::where('queue_status', 'processing')
            ->where('processing_started_at', '>', Carbon::now()->subSeconds(self::CALL_TIMEOUT_SECONDS))
            ->count();
    }
    
    /**
     * Verificar si se puede procesar un nuevo lote
     */
    private function canProcessNewBatch(): bool
    {
        $lastBatchTime = Llamada::where('queue_status', 'processing')
            ->orWhere(function($query) {
                $query->where('queue_status', 'completed')
                      ->where('processing_completed_at', '>', Carbon::now()->subMinutes(5));
            })
            ->max('processing_started_at');
        
        if (!$lastBatchTime) {
            return true; // No hay lotes previos
        }
        
        $timeSinceLastBatch = Carbon::now()->diffInSeconds($lastBatchTime);
        return $timeSinceLastBatch >= self::BATCH_DELAY_SECONDS;
    }
    
    /**
     * Obtener tiempo restante hasta el siguiente lote
     */
    private function getTimeUntilNextBatch(): int
    {
        $lastBatchTime = Llamada::where('queue_status', 'processing')
            ->orWhere(function($query) {
                $query->where('queue_status', 'completed')
                      ->where('processing_completed_at', '>', Carbon::now()->subMinutes(5));
            })
            ->max('processing_started_at');
        
        if (!$lastBatchTime) {
            return 0;
        }
        
        $timeSinceLastBatch = Carbon::now()->diffInSeconds($lastBatchTime);
        return max(0, self::BATCH_DELAY_SECONDS - $timeSinceLastBatch);
    }
    
    /**
     * Obtener el siguiente lote a procesar
     */
    private function getNextBatchToProcess(): ?int
    {
        return Llamada::where('queue_status', 'queued')
            ->orderBy('queue_priority')
            ->orderBy('batch_number')
            ->value('batch_number');
    }
    
    /**
     * Obtener estadísticas de la cola
     */
    public function getQueueStats(): array
    {
        return [
            'pending' => Llamada::where('queue_status', 'pending')->count(),
            'queued' => Llamada::where('queue_status', 'queued')->count(),
            'processing' => Llamada::where('queue_status', 'processing')->count(),
            'completed' => Llamada::where('queue_status', 'completed')
                ->where('created_at', '>', Carbon::now()->subDay())
                ->count(),
            'failed' => Llamada::where('queue_status', 'failed')
                ->where('created_at', '>', Carbon::now()->subDay())
                ->count(),
            'active_calls' => $this->getActiveCallsCount(),
            'can_process' => $this->canProcessNewBatch(),
            'wait_time' => $this->getTimeUntilNextBatch(),
            'next_batch' => $this->getNextBatchToProcess()
        ];
    }
    
    /**
     * Limpiar llamadas atascadas (timeout)
     */
    public function cleanStuckCalls(): int
    {
        $timeout = Carbon::now()->subSeconds(self::CALL_TIMEOUT_SECONDS);
        
        $stuckCalls = Llamada::where('queue_status', 'processing')
            ->where('processing_started_at', '<', $timeout)
            ->get();
        
        $cleaned = 0;
        foreach ($stuckCalls as $call) {
            $call->update([
                'queue_status' => 'failed',
                'processing_completed_at' => Carbon::now(),
                'internal_notes' => ($call->internal_notes ?? '') . "\n[" . Carbon::now() . "] Llamada marcada como fallida por timeout"
            ]);
            
            // Reencolar si aún tiene intentos disponibles
            if ($call->processing_attempts < self::MAX_PROCESSING_ATTEMPTS) {
                $this->requeueCall($call);
            }
            
            $cleaned++;
        }
        
        if ($cleaned > 0) {
            Log::warning("Se limpiaron {$cleaned} llamadas atascadas");
        }
        
        return $cleaned;
    }
}
