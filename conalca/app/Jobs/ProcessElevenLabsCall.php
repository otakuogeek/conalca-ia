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
            
            // Fallback: si no se encontró conductor en ninguna tabla, usar datos de la llamada directamente
            if (!$driverData && $llamada->numero_destino) {
                Log::warning('ProcessElevenLabsCall: Usando datos directos de la llamada como fallback', [
                    'llamada_id' => $this->llamadaId,
                    'numero_destino' => $llamada->numero_destino
                ]);
                
                $driverData = [
                    'id' => $llamada->conductor_id ?? $this->driverId ?? 0,
                    'nombre' => 'Conductor',
                    'telefono' => $llamada->numero_destino,
                    'placa' => 'N/A'
                ];
                
                // Intentar obtener nombre del conductor desde llamadas_conductores por número
                $conductorByPhone = LlamadaConductor::where('telefono', 'LIKE', '%' . substr(preg_replace('/[^0-9]/', '', $llamada->numero_destino), -10) . '%')
                    ->where('cotizacion_id', $this->cotizacionId)
                    ->first();
                    
                if ($conductorByPhone) {
                    $driverData['id'] = $conductorByPhone->id;
                    $driverData['nombre'] = $conductorByPhone->nombre_conductor;
                    $driverData['placa'] = $conductorByPhone->placa ?? 'N/A';
                    $conductor = $conductorByPhone;
                    
                    Log::info('Conductor encontrado por teléfono', [
                        'conductor_id' => $conductorByPhone->id,
                        'nombre' => $conductorByPhone->nombre_conductor
                    ]);
                }
            }
            
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
            
            // Preparar datos del cliente para la llamada - datos completos de la orden
            $clientData = [
                // IDs de referencia
                'cotizacion_id' => $cotizacion->id,
                'group_cotization_id' => $cotizacion->group_cotization_id,
                'driver_id' => $driverData['id'],
                'llamada_id' => $llamada->id_llamada,
                // Datos del conductor
                'driver_name' => $driverData['nombre'],
                'placa' => $driverData['placa'],
                'tipo_vehiculo' => $conductor?->tipo_vehiculo ?? $cotizacion->vehiculo_requerido ?? 'No especificado',
                // Datos de la orden/cotización
                'origen' => $cotizacion->ciudad_origen ?? 'No especificado',
                'destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
                'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
                'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
                'tipo_embajale' => $cotizacion->tipo_embajale ?? 'No especificado',
                'tipo_carroceria' => $cotizacion->tipo_carroceria ?? 'No especificado',
                'valor_declarado' => $cotizacion->valor_declarado ?? '0',
                'valor_flete' => $cotizacion->flete ?? $cotizacion->valor ?? '0',
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

                // Actualizar también llamadas_conductores con el conversation_id Y datos de la orden
                if ($conductor) {
                    $conductor->update([
                        'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                        'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                        'estado_llamada' => 'en_progreso',
                        'fecha_llamada' => now(),
                        // Información de la orden/cotización
                        'cotizacion_id' => $cotizacion->id,
                        'group_cotization_id' => $cotizacion->group_cotization_id ?? null,
                        'ciudad_origen' => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        'tipo_vehiculo' => $cotizacion->vehiculo_requerido,
                        'mercancia' => $cotizacion->tipo_mercancia,
                        'peso_carga' => $cotizacion->peso_mercancia,
                        'empaque' => $cotizacion->tipo_embajale,
                        // Datos adicionales en JSON
                        'datos_adicionales' => json_encode([
                            'valor_declarado' => $cotizacion->valor_declarado,
                            'tipo_carroceria' => $cotizacion->tipo_carroceria,
                            'consolidado_expreso' => $cotizacion->consolidado_expreso,
                            'regimen_nacionalizado' => $cotizacion->regimen_nacionalizado,
                            'temperatura_mercancia' => $cotizacion->temperatura_mercancia,
                            'dimensiones_exactas' => $cotizacion->dimensiones_exactas,
                            'fecha_llamada' => now()->toDateTimeString(),
                            'client_id' => $cotizacion->client_id,
                            'user_id' => $cotizacion->user_id,
                        ])
                    ]);

                    Log::info('✅ Conversation ID y datos de orden guardados en llamadas_conductores', [
                        'conductor_id' => $conductor->id,
                        'conversation_id' => $response['conversation_id'] ?? null,
                        'cotizacion_id' => $cotizacion->id,
                        'group_cotization_id' => $cotizacion->group_cotization_id ?? null
                    ]);
                }

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
