<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\CotizacionModel;
use App\Models\Llamada;
use App\Services\ElevenLabsCallService;

class ProcessElevenLabsCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $driverId;
    public $cotizacionId;
    public $llamadaId;

    /**
     * Create a new job instance.
     */
    public function __construct($driverId, $cotizacionId, $llamadaId)
    {
        $this->driverId = $driverId;
        $this->cotizacionId = $cotizacionId;
        $this->llamadaId = $llamadaId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $driver = VehicleOwnerHolderDriver::find($this->driverId);
            $cotizacion = CotizacionModel::find($this->cotizacionId);
            $llamada = Llamada::find($this->llamadaId);

            if (!$driver || !$cotizacion || !$llamada) {
                Log::error('ProcessElevenLabsCall: Modelos no encontrados', [
                    'driver_id' => $this->driverId,
                    'cotizacion_id' => $this->cotizacionId,
                    'llamada_id' => $this->llamadaId
                ]);
                return;
            }

            // Actualizar estado de llamada
            $llamada->update([
                'queue_status' => 'processing',
                'processing_started_at' => now()
            ]);

            // Usar el servicio de ElevenLabs
            $elevenLabsService = app(ElevenLabsCallService::class);
            
            // Preparar datos del cliente para la llamada
            $clientData = [
                'cotizacion_id' => $cotizacion->id,
                'driver_id' => $driver->id,
                'llamada_id' => $llamada->id_llamada,
                'origen' => $cotizacion->ciudad_origen ?? 'No especificado',
                'destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado'
            ];
            
            $response = $elevenLabsService->makeDirectSipCall(
                $llamada->numero_destino,
                $clientData
            );

            if ($response['success']) {
                $llamada->update([
                    'status' => 'initiated',
                    'queue_status' => 'completed',
                    'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                    'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                    'processing_completed_at' => now()
                ]);

                Log::info('ProcessElevenLabsCall: Llamada iniciada exitosamente', [
                    'llamada_id' => $this->llamadaId,
                    'conversation_id' => $response['conversation_id'] ?? null
                ]);
            } else {
                $llamada->update([
                    'status' => 'failed',
                    'queue_status' => 'failed',
                    'failure_reason' => $response['error'] ?? 'Error desconocido',
                    'processing_completed_at' => now()
                ]);

                Log::error('ProcessElevenLabsCall: Error al iniciar llamada', [
                    'llamada_id' => $this->llamadaId,
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessElevenLabsCall: Excepción durante procesamiento', [
                'llamada_id' => $this->llamadaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($llamada)) {
                $llamada->update([
                    'status' => 'failed',
                    'queue_status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                    'processing_completed_at' => now()
                ]);
            }
        }
    }
}
