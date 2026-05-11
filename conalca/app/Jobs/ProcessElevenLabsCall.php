<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\LlamadaConductor;
use App\Models\CotizacionModel;
use App\Models\Llamada;
use App\Services\ElevenLabsCallService;

class ProcessElevenLabsCall implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $driverId;
    public $cotizacionId;
    public $llamadaId;

    public $tries = 1;
    public $timeout = 120;
    public $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($driverId, $cotizacionId, $llamadaId)
    {
        $this->driverId = $driverId;
        $this->cotizacionId = $cotizacionId;
        $this->llamadaId = $llamadaId;
        $this->onQueue('calls');
    }

    public function uniqueId(): string
    {
        return "elevenlabs_call_{$this->llamadaId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = null;
        $lockAcquired = false;

        try {
            $lock = Cache::lock("processing_call_{$this->llamadaId}", 120);
            $lockAcquired = $lock->get();
        } catch (\Throwable $e) {
            Log::warning('ProcessElevenLabsCall: Error al adquirir lock, continuando sin lock', [
                'llamada_id' => $this->llamadaId,
                'error' => $e->getMessage()
            ]);
            $lockAcquired = true;
            $lock = null;
        }

        if (!$lockAcquired) {
            Log::warning('ProcessElevenLabsCall: No se obtuvo lock, llamada ya en proceso', [
                'llamada_id' => $this->llamadaId
            ]);
            return;
        }

        try {
            // Verificar que la llamada sigue en estado procesable
            $llamada = Llamada::find($this->llamadaId);

            if (!$llamada) {
                Log::error('ProcessElevenLabsCall: Llamada no encontrada', [
                    'llamada_id' => $this->llamadaId
                ]);
                return;
            }

            if (!in_array($llamada->queue_status, ['pending', 'processing'])) {
                Log::info('ProcessElevenLabsCall: Llamada ya procesada, saltando', [
                    'llamada_id' => $this->llamadaId,
                    'queue_status' => $llamada->queue_status
                ]);
                return;
            }

            // Verificar si el conductor ya fue contactado y tiene respuesta (última línea de defensa)
            if ($llamada->conductor_id) {
                $conductorCheck = LlamadaConductor::find($llamada->conductor_id);
                if ($conductorCheck && $conductorCheck->hasBeenContacted()) {
                    Log::info('ProcessElevenLabsCall: Conductor ya tiene respuesta - cancelando llamada', [
                        'llamada_id' => $this->llamadaId,
                        'conductor_id' => $llamada->conductor_id,
                        'conductor' => $conductorCheck->nombre_conductor,
                        'respuesta_llamada' => $conductorCheck->respuesta_llamada,
                        'driver_call_response_id' => $conductorCheck->driver_call_response_id
                    ]);

                    $llamada->update([
                        'queue_status' => 'cancelled',
                        'call_notes' => 'Cancelada: conductor ya tiene respuesta registrada',
                        'processing_completed_at' => now()
                    ]);
                    return;
                }
            }
            
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

            if ($conductor && !self::phonesMatch($llamada->numero_destino, $conductor->telefono)) {
                Log::error('ProcessElevenLabsCall: teléfono de llamada no coincide con conductor registrado', [
                    'llamada_id' => $this->llamadaId,
                    'conductor_id' => $conductor->id,
                    'identificador_unico' => $conductor->identificador_unico,
                    'numero_destino' => $llamada->numero_destino,
                    'telefono_conductor' => $conductor->telefono,
                ]);

                $llamada->update([
                    'status' => Llamada::STATUS_FINALIZADA,
                    'call_status' => Llamada::CALL_STATUS_FAILED,
                    'queue_status' => 'failed',
                    'failure_reason' => 'Teléfono destino no coincide con conductor registrado',
                    'processing_completed_at' => now(),
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
            // Procesar fecha y hora de cargue si existen
            $fechaCargue = null;
            $horaCargue = null;
            
            if ($cotizacion->fecha_hora_descargue_cargue) {
                try {
                    $fechaHora = \DateTime::createFromFormat(
                        'Y-m-d H:i:s',
                        $cotizacion->fecha_hora_descargue_cargue
                    ) ?? \DateTime::createFromFormat('Y-m-d', $cotizacion->fecha_hora_descargue_cargue);
                    
                    if ($fechaHora) {
                        $fechaCargue = $this->formatFechaCargueNatural($fechaHora);
                        
                        if ($fechaHora->format('H') != '00' || $fechaHora->format('i') != '00') {
                            $hora = (int)$fechaHora->format('H');
                            $hora12 = $hora % 12 ?: 12;
                            $ampm = $hora < 12 ? 'AM' : 'PM';
                            $horaCargue = sprintf('%d:%s %s', $hora12, $fechaHora->format('i'), $ampm);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error al parsear fecha_hora_descargue_cargue', [
                        'fecha_hora' => $cotizacion->fecha_hora_descargue_cargue,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $clientData = [
                // IDs de referencia
                'cotizacion_id' => $cotizacion->id,
                'group_cotization_id' => $cotizacion->group_cotization_id,
                'driver_id' => $driverData['id'],
                'llamada_id' => $llamada->id_llamada,
                'identificador_unico' => $conductor?->identificador_unico,
                // Datos del conductor
                'driver_name' => $driverData['nombre'],
                'telefono_conductor' => $driverData['telefono'],
                'telefono_llamado' => $llamada->numero_destino,
                'telefono_llamado_normalizado' => self::normalizePhoneForComparison($llamada->numero_destino),
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
                // Datos de fecha y hora de cargue
                'fecha_cargue' => $fechaCargue,
                'hora_cargue' => $horaCargue,
                'fecha_hora_descargue_cargue' => $cotizacion->fecha_hora_descargue_cargue,
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
                $callMetadata = is_array($llamada->call_metadata) ? $llamada->call_metadata : [];
                $callMetadata['conductor_context'] = [
                    'conductor_id' => $conductor?->id,
                    'identificador_unico' => $conductor?->identificador_unico,
                    'cotizacion_id' => $cotizacion->id,
                    'numero_destino' => $llamada->numero_destino,
                    'telefono_conductor' => $conductor?->telefono ?? $driverData['telefono'],
                    'telefono_normalizado' => self::normalizePhoneForComparison($llamada->numero_destino),
                    'conversation_id' => $response['conversation_id'] ?? null,
                    'sip_call_id' => $response['sip_call_id'] ?? null,
                    'sent_at' => now()->toISOString(),
                ];

                // IMPORTANTE: Mantener queue_status='processing' porque la llamada está ACTIVA
                // en la línea SIP de Zadarma. Solo el webhook de finalización (onCallCompleted/onCallFailed)
                // la cambiará a 'completed'/'failed', liberando el slot para la siguiente llamada.
                $llamada->update([
                    'status' => Llamada::STATUS_EN_CURSO,
                    'call_status' => Llamada::CALL_STATUS_INITIATED,
                    'queue_status' => 'processing', // ← Se mantiene processing hasta webhook
                    'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                    'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                    'call_initiated_at' => now(),
                    'call_metadata' => $callMetadata,
                ]);

                // Actualizar también llamadas_conductores con el conversation_id Y datos de la orden
                if ($conductor) {
                    $datosAdicionales = is_array($conductor->datos_adicionales)
                        ? $conductor->datos_adicionales
                        : (json_decode((string) $conductor->datos_adicionales, true) ?: []);
                    $datosAdicionales['ultima_llamada'] = [
                        'llamada_id' => $llamada->id_llamada,
                        'numero_destino' => $llamada->numero_destino,
                        'telefono_normalizado' => self::normalizePhoneForComparison($llamada->numero_destino),
                        'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                        'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                        'fecha_llamada' => now()->toDateTimeString(),
                    ];
                    $datosAdicionales['cotizacion_llamada'] = [
                        'valor_declarado' => $cotizacion->valor_declarado,
                        'tipo_carroceria' => $cotizacion->tipo_carroceria,
                        'consolidado_expreso' => $cotizacion->consolidado_expreso,
                        'regimen_nacionalizado' => $cotizacion->regimen_nacionalizado,
                        'temperatura_mercancia' => $cotizacion->temperatura_mercancia,
                        'dimensiones_exactas' => $cotizacion->dimensiones_exactas,
                        'client_id' => $cotizacion->client_id,
                        'user_id' => $cotizacion->user_id,
                    ];

                    $conductor->update([
                        'call_id' => $response['conversation_id'] ?? null,
                        'elevenlabs_conversation_id' => $response['conversation_id'] ?? null,
                        'elevenlabs_sip_call_id' => $response['sip_call_id'] ?? null,
                        'estado_llamada' => 'en_progreso',
                        'fecha_llamada' => now(),
                        // Información de la orden/cotización
                        'cotizacion_id' => $cotizacion->id,
                        'group_cotization_id' => $cotizacion->group_cotization_id ?? null,
                        'ciudad_origen' => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        // tipo_vehiculo NO se sobreescribe: debe mantener el valor real del conductor (Arcangel)
                        'vehiculo_silogtran' => $cotizacion->vehiculo_requerido,
                        'mercancia' => $cotizacion->tipo_mercancia,
                        'peso_carga' => $cotizacion->peso_mercancia,
                        'empaque' => $cotizacion->tipo_embajale,
                        'datos_adicionales' => $datosAdicionales,
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

                // Liberar slot y despachar siguiente llamada
                \App\Services\CallQueueManager::dispatchNextCalls($this->cotizacionId);
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

                // Liberar slot y despachar siguiente llamada
                \App\Services\CallQueueManager::dispatchNextCalls($this->cotizacionId);
            }
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

    private static function normalizePhoneForComparison($phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phoneNumber);

        if (strlen($digits) > 10 && str_starts_with($digits, '57')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private static function phonesMatch($left, $right): bool
    {
        $leftClean = self::normalizePhoneForComparison($left);
        $rightClean = self::normalizePhoneForComparison($right);

        if ($leftClean === '' || $rightClean === '') {
            return false;
        }

        if ($leftClean === $rightClean) {
            return true;
        }

        if (strlen($leftClean) >= 10 && strlen($rightClean) >= 10) {
            return substr($leftClean, -10) === substr($rightClean, -10);
        }

        return str_ends_with($leftClean, $rightClean) || str_ends_with($rightClean, $leftClean);
    }

    private function formatFechaCargueNatural(\DateTimeInterface $fechaHora): string
    {
        $fecha = \Carbon\Carbon::instance($fechaHora)->timezone('America/Bogota')->startOfDay();
        $hoy = \Carbon\Carbon::now('America/Bogota')->startOfDay();
        $delta = $hoy->diffInDays($fecha, false);
        $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $nombreDia = $dias[$fecha->dayOfWeekIso - 1];

        if ($delta === 0) {
            return 'hoy';
        }

        if ($delta === 1) {
            return 'mañana';
        }

        if ($delta >= 2) {
            return "el próximo {$nombreDia} {$fecha->day}";
        }

        if ($delta === -1) {
            return 'ayer';
        }

        return "el {$nombreDia} {$fecha->day} de {$meses[$fecha->month - 1]}";
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessElevenLabsCall: Job FAILED definitivamente', [
            'llamada_id' => $this->llamadaId,
            'error' => $exception?->getMessage()
        ]);

        try {
            Llamada::where('id_llamada', $this->llamadaId)
                ->update([
                    'status' => 'failed',
                    'queue_status' => 'failed',
                    'failure_reason' => 'Job falló: ' . ($exception?->getMessage() ?? 'Error desconocido'),
                    'processing_completed_at' => now()
                ]);

            // Despachar siguiente llamada en cola (liberar slot)
            \App\Services\CallQueueManager::dispatchNextCalls($this->cotizacionId);
        } catch (\Exception $e) {
            Log::error('ProcessElevenLabsCall: Error actualizando estado en failed()', [
                'llamada_id' => $this->llamadaId,
                'error' => $e->getMessage()
            ]);
        }

        Cache::lock("processing_call_{$this->llamadaId}")->forceRelease();
    }
}
