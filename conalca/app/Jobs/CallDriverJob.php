<?php

namespace App\Jobs;

use App\Http\Controllers\CallController;
use App\Http\Controllers\TransportController;
use App\Services\ConversationalAgentService;
use App\Services\ElevenLabsService;
use App\Services\ElevenLabsCallService;
use App\Models\DriverCallResponse;
use App\Models\CotizacionModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use OpenAI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CallDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $driver;
    protected $prompt;
    protected $quotation_id;
    protected $total_drivers;
    protected $callId;

    public $timeout = 600;

    public function __construct(array $driver, $prompt, $quotation_id, $total_drivers, $callId)
    {
        $this->driver = $driver;
        $this->prompt  = $prompt;
        $this->quotation_id = $quotation_id;
        $this->total_drivers = $total_drivers;
        $this->callId = $callId;
    }

    public function handle()
    {
        // Verificar si las llamadas han sido canceladas antes de ejecutar
        $callStatus = DB::table('calls')
            ->where('quotation_id', $this->quotation_id)
            ->value('status');

        // Solo realiza la acción si el registro existe y el estado es 'canceled'
        if ($callStatus && $callStatus === 'canceled') {
            return;
        }

        try {
            Log::info('=== INICIANDO CallDriverJob ===', [
                'driver_data' => $this->driver,
                'quotation_id' => $this->quotation_id,
                'callId' => $this->callId
            ]);
            Log::info('Iniciando CallDriversJob para el conductor: ' . json_encode($this->driver));

            // Por eficiencia, verifica si ya se completaron las aceptaciones necesarias:
            // $acceptedCount = DB::table('call_drivers')
            //     ->where('call_id', $this->callId)
            //     ->where('response', 'accepted')
            //     ->count();

            // // Si ya hay suficientes aceptados, no sigas llamando
            // if ($acceptedCount >= 2) {
            //     DB::table('calls')
            //         ->where('id', $this->callId)
            //         ->update(['status' => 'completed']);
            //     Log::info('2 drivers have accepted. Stopping further calls.');
            //     return;
            // }

            // NOTE: Using ElevenLabs SIP Trunk instead of removed TwilioService
            Log::info('CallDriverJob: Iniciando llamada con ElevenLabs SIP Trunk');

            $agentService = null;
            try {
                $agentService = app(ConversationalAgentService::class);
            } catch (\Throwable $e) {
                Log::warning('No se pudo inicializar ConversationalAgentService', [
                    'error' => $e->getMessage()
                ]);
            }

            $elevenLabsService = null;
            try {
                $elevenLabsService = app(ElevenLabsService::class);
            } catch (\Throwable $e) {
                Log::warning('No se pudo inicializar ElevenLabsService', [
                    'error' => $e->getMessage()
                ]);
            }

            $elevenLabsCallService = null;
            try {
                $elevenLabsCallService = app(ElevenLabsCallService::class);
            } catch (\Throwable $e) {
                Log::error('No se pudo inicializar ElevenLabsCallService', [
                    'error' => $e->getMessage()
                ]);
                return;
            }
            
            Log::info('Preparando llamada ElevenLabs SIP Trunk', [
                'conductor' => $this->driver['name'],
                'telefono' => $this->driver['phone_number']
            ]);

            // Obtener información de la cotización para el mensaje
            $cotizacion = CotizacionModel::find($this->quotation_id);

            $initialAssets = null;
            $initialMessage = null;
            $initialAudioUrl = null;

            if ($cotizacion && $agentService && $elevenLabsService) {
                try {
                    $initialMessage = $agentService->generateInitialMessage(
                        $cotizacion,
                        $this->driver['name'] ?? 'conductor'
                    );

                    if ($initialMessage) {
                        $initialAudioUrl = $elevenLabsService->generatePhoneOptimizedSpeech($initialMessage, null, 'greeting');

                        if ($initialAudioUrl) {
                            $initialAssets = [
                                'message' => $initialMessage,
                                'audio_url' => $initialAudioUrl,
                                'driver_id' => $this->driver['id'],
                                'cotizacion_id' => $this->quotation_id,
                                'generated_at' => now()->toISOString()
                            ];

                            if ($agentService) {
                                $agentService->cacheInitialAssets(
                                    $this->quotation_id,
                                    $this->driver['id'],
                                    $initialAssets
                                );
                            }

                            Log::info('Audio inicial precargado para la llamada', [
                                'driver_id' => $this->driver['id'],
                                'cotizacion_id' => $this->quotation_id,
                                'audio_url' => $initialAudioUrl
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo precargar el audio inicial', [
                        'error' => $e->getMessage(),
                        'driver_id' => $this->driver['id'],
                        'cotizacion_id' => $this->quotation_id
                    ]);
                }
            }

            // Formatear número de teléfono para Colombia
            $phoneNumber = $this->formatPhoneNumber($this->driver['phone_number']);
            
            // Generar mensaje inicial para la llamada
            $mensaje = $initialMessage ?? (
                $cotizacion 
                    ? "Hola, le hablamos de Conalca. Tenemos una oferta de transporte desde {$cotizacion->municipio_origen} hasta {$cotizacion->municipio_destino}. ¿Está interesado?"
                    : "Hola, le hablamos de Conalca. Tenemos una oferta de transporte disponible. ¿Está interesado?"
            );
            
            // **CREAR REGISTRO ANTES DE LA LLAMADA** para evitar problemas de sincronización
            $callResponse = DriverCallResponse::create([
                'cotizacion_id' => $this->quotation_id,
                'driver_id' => $this->driver['id'],
                'driver_name' => $this->driver['name'],
                'driver_phone' => $phoneNumber,
                'vehicle_type' => $this->driver['type_vehicle'] ?? 'N/A',
                'call_status' => 'calling',
                'response_status' => 'pending',
                'notes' => 'Preparando llamada con ElevenLabs SIP Trunk' . ($initialAudioUrl ? ' | Audio precargado' : '')
            ]);
            
            Log::info('Registro de llamada creado, iniciando ElevenLabs SIP Trunk', [
                'record_id' => $callResponse->id,
                'phone' => $phoneNumber,
                'driver_id' => $this->driver['id'],
                'cotizacion_id' => $this->quotation_id,
                'mensaje' => substr($mensaje, 0, 100) . '...'
            ]);
            
            // Realizar llamada con ElevenLabs SIP Trunk
            $result = ['success' => false, 'error' => 'Service not available', 'conversation_id' => null, 'sip_call_id' => null];
            
            if ($elevenLabsCallService) {
                try {
                    // Preparar datos del cliente para la llamada
                    $clientData = [
                        'driver_id' => $this->driver['id'],
                        'driver_name' => $this->driver['name'],
                        'cotizacion_id' => $this->quotation_id
                    ];

                    if ($cotizacion) {
                        $clientData['cotizacion'] = [
                            'id' => $cotizacion->id,
                            'origen' => $cotizacion->ciudad_origen,
                            'destino' => $cotizacion->ciudad_destino,
                            'tipo_mercancia' => $cotizacion->tipo_mercancia,
                            'vehiculo_requerido' => $cotizacion->vehiculo_requerido
                        ];
                    }

                    $result = $elevenLabsCallService->makeDirectSipCall($phoneNumber, $clientData);
                    
                    Log::info('Resultado llamada ElevenLabs', [
                        'success' => $result['success'],
                        'conversation_id' => $result['conversation_id'] ?? null,
                        'sip_call_id' => $result['sip_call_id'] ?? null,
                        'error' => $result['error'] ?? null
                    ]);
                    
                } catch (\Throwable $e) {
                    Log::error('Error en llamada ElevenLabs SIP Trunk', [
                        'error' => $e->getMessage(),
                        'phone' => $phoneNumber,
                        'driver_id' => $this->driver['id']
                    ]);
                    $result = [
                        'success' => false,
                        'error' => 'Exception: ' . $e->getMessage(),
                        'conversation_id' => null,
                        'sip_call_id' => null
                    ];
                }
            }

            // Actualizar el registro con el resultado de la llamada
            $updateData = [
                'call_status' => $result['success'] ? 'calling' : 'failed',
                'notes' => $result['success'] 
                    ? 'Llamada iniciada con ElevenLabs SIP Trunk - Conversation ID: ' . ($result['conversation_id'] ?? 'N/A')
                    : 'Error en llamada ElevenLabs: ' . ($result['error'] ?? 'Unknown error')
            ];

            if ($result['success'] && !empty($result['conversation_id'])) {
                $updateData['elevenlabs_conversation_id'] = $result['conversation_id'];
                if (!empty($result['sip_call_id'])) {
                    $updateData['elevenlabs_sip_call_id'] = $result['sip_call_id'];
                }
            }

            $callResponse->update($updateData);

            if (($result['success'] ?? false) && !empty($result['conversation_id']) && $initialAssets && $agentService) {
                $agentService->cacheInitialAssetsForCall($result['conversation_id'], $initialAssets);
            }

            $status = $result['success'] ? 'called' : 'failed';

            Log::info('Llamada ElevenLabs ejecutada, status: ' . $status, [
                'conversation_id' => $result['conversation_id'] ?? null,
                'sip_call_id' => $result['sip_call_id'] ?? null,
                'error' => $result['error'] ?? null
            ]);

            // (OPCIONAL) Puedes llevar un registro en 'calls_made', si tu lógica lo requiere
            DB::table('calls')
                ->where('id', $this->callId)
                ->increment('calls_made', 1);

            // El AGI será el encargado de registrar la respuesta real mediante un POST a tu endpoint en Laravel.

            // Si deseas, podrías dejar el resto de control de "all_called" si lo necesitas aún:
            // $callsMade = DB::table('calls')
            //     ->where('id', $this->callId)
            //     ->value('calls_made');

            // if ($this->total_drivers == $callsMade) {
            //     DB::table('calls')
            //         ->where('id', $this->callId)
            //         ->update(['status' => 'all_called']);

            //     Log::info("All drivers have been called. Only $acceptedCount accepted out of 2 required.");
            // }

        } catch (\Exception $e) {
            Log::error('=== ERROR EN CallDriverJob ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'driver_data' => $this->driver ?? 'NO DATA'
            ]);
            throw $e;
        }
    }

    /**
     * Formatear número de teléfono para Colombia
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Limpiar el número
        $cleanNumber = preg_replace('/\D/', '', $phoneNumber);
        
        // Si empieza con 57, usar tal como está
        if (substr($cleanNumber, 0, 2) === '57') {
            return '+' . $cleanNumber;
        }
        
        // Si es número de 10 dígitos de Colombia, agregar +57
        if (strlen($cleanNumber) == 10) {
            return '+57' . $cleanNumber;
        }
        
        // Si es número de 7 dígitos, agregar código de área (1 para Bogotá por defecto)
        if (strlen($cleanNumber) == 7) {
            return '+571' . $cleanNumber;
        }
        
        // Para otros casos, intentar agregar +57
        return '+57' . $cleanNumber;
    }
}
