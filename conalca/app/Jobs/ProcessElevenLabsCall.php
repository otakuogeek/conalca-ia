<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\LlamadaConductor;
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
            Log::info('==================================================');
            Log::info('ProcessElevenLabsCall: INICIANDO PROCESAMIENTO');
            Log::info('==================================================');
            Log::info('IDs recibidos', [
                'driver_id' => $this->driverId,
                'cotizacion_id' => $this->cotizacionId,
                'llamada_id' => $this->llamadaId
            ]);
            
            $llamada = Llamada::find($this->llamadaId);
            
            if (!$llamada) {
                Log::error('ProcessElevenLabsCall: Llamada no encontrada', [
                    'llamada_id' => $this->llamadaId
                ]);
                return;
            }
            
            Log::info('Llamada encontrada', [
                'id_llamada' => $llamada->id_llamada,
                'conductor_id' => $llamada->conductor_id,
                'chofer_id' => $llamada->chofer_id,
                'numero_destino' => $llamada->numero_destino,
                'status' => $llamada->status
            ]);
            
            // Intentar obtener conductor de la nueva tabla primero
            $conductor = null;
            $driverData = null;
            
            if ($llamada->conductor_id) {
                Log::info('Buscando en tabla llamadas_conductores...', ['conductor_id' => $llamada->conductor_id]);
                $conductor = LlamadaConductor::find($llamada->conductor_id);
                
                if ($conductor) {
                    Log::info('Conductor encontrado en nueva tabla', [
                        'id' => $conductor->id,
                        'nombre' => $conductor->nombre_conductor,
                        'telefono' => $conductor->telefono,
                        'placa' => $conductor->placa
                    ]);
                    
                    $driverData = [
                        'id' => $conductor->id,
                        'nombre' => $conductor->nombre_conductor,
                        'telefono' => $conductor->telefono,
                        'placa' => $conductor->placa
                    ];
                }
            }
            
            // Fallback a tabla vieja si no se encuentra en nueva tabla
            if (!$conductor && $this->driverId) {
                Log::info('Buscando en tabla vehicle_owner_holder_driver...', ['driver_id' => $this->driverId]);
                $driver = VehicleOwnerHolderDriver::find($this->driverId);
                
                if ($driver) {
                    Log::info('Driver encontrado en tabla vieja', [
                        'id' => $driver->id,
                        'nombre' => $driver->Conductor ?? 'N/A'
                    ]);
                    
                    $driverData = [
                        'id' => $driver->id,
                        'nombre' => $driver->Conductor ?? 'N/A',
                        'telefono' => $llamada->numero_destino,
                        'placa' => $driver->Placa ?? 'N/A'
                    ];
                }
            }
            
            $cotizacion = CotizacionModel::find($this->cotizacionId);
            
            if (!$driverData || !$cotizacion) {
                Log::error('ProcessElevenLabsCall: Modelos no encontrados', [
                    'driver_found' => $driverData ? 'yes' : 'no',
                    'conductor_found' => $conductor ? 'yes' : 'no',
                    'cotizacion_found' => $cotizacion ? 'yes' : 'no',
                    'driver_id' => $this->driverId,
                    'cotizacion_id' => $this->cotizacionId,
                    'llamada_id' => $this->llamadaId
                ]);
                
                $llamada->update([
                    'status' => Llamada::STATUS_FINALIZADA,
                    'call_status' => Llamada::CALL_STATUS_FAILED,
                    'failure_reason' => 'Conductor o cotización no encontrados',
                    'queue_status' => 'failed',
                    'processing_completed_at' => now()
                ]);
                return;
            }
            
            Log::info('Modelos encontrados, actualizando estado de llamada...');

            // Actualizar estado de llamada
            $llamada->update([
                'queue_status' => 'processing',
                'processing_started_at' => now()
            ]);

            Log::info('Preparando datos para ElevenLabs...');

            // Usar el servicio de ElevenLabs
            $elevenLabsService = app(ElevenLabsCallService::class);
            
            // Preparar datos del cliente para la llamada
            $clientData = [
                'cotizacion_id' => $cotizacion->id,
                'driver_id' => $driverData['id'],
                'driver_name' => $driverData['nombre'],
                'llamada_id' => $llamada->id_llamada,
                'placa' => $driverData['placa'],
                'origen' => $cotizacion->ciudad_origen ?? 'No especificado',
                'destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado'
            ];
            
            Log::info('Client data preparado', $clientData);
            Log::info('Llamando a elevenLabsService->makeDirectSipCall()...', [
                'numero_destino' => $llamada->numero_destino
            ]);
            
            $response = $elevenLabsService->makeDirectSipCall(
                $llamada->numero_destino,
                $clientData
            );
            
            Log::info('Respuesta de ElevenLabs', [
                'success' => $response['success'] ?? false,
                'conversation_id' => $response['conversation_id'] ?? null,
                'sip_call_id' => $response['sip_call_id'] ?? null,
                'error' => $response['error'] ?? null
            ]);

            if ($response['success']) {
                $llamada->update([
                    'status' => Llamada::STATUS_EN_CURSO,
                    'call_status' => Llamada::CALL_STATUS_INITIATED,
                    'queue_status' => 'completed',
                    'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                    'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                    'call_initiated_at' => now(),
                    'processing_completed_at' => now()
                ]);

                Log::info('✅ ProcessElevenLabsCall: Llamada iniciada exitosamente', [
                    'llamada_id' => $this->llamadaId,
                    'conversation_id' => $response['conversation_id'] ?? null,
                    'sip_call_id' => $response['sip_call_id'] ?? null
                ]);
            } else {
                $llamada->update([
                    'status' => Llamada::STATUS_FINALIZADA,
                    'call_status' => Llamada::CALL_STATUS_FAILED,
                    'queue_status' => 'failed',
                    'failure_reason' => $response['error'] ?? 'Error desconocido',
                    'processing_completed_at' => now()
                ]);

                Log::error('❌ ProcessElevenLabsCall: Error al iniciar llamada', [
                    'llamada_id' => $this->llamadaId,
                    'error' => $response['error'] ?? 'Error desconocido'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('EXCEPCIÓN en ProcessElevenLabsCall', [
                'llamada_id' => $this->llamadaId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
