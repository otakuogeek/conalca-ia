<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Llamada;
use App\Models\VehicleOwnerHolderDriver;

class ElevenLabsCallService
{
    private $apiKey;
    private $baseUrl;
    private $agentId;
    private $agentPhoneNumberId;

    public function __construct()
    {
        $this->apiKey = config('services.elevenlabs.api_key', env('ELEVENLABS_API_KEY'));
        $this->baseUrl = 'https://api.elevenlabs.io';
        // Usar config() en lugar de env() para obtener la configuración correcta
        $this->agentId = config('services.elevenlabs.agent_id'); 
        $this->agentPhoneNumberId = config('services.elevenlabs.agent_phone_number_id');
    }

    /**
     * Iniciar llamadas para todas las llamadas registradas de una cotización
     */
    public function startCallsForCotization($cotizacionId)
    {
        try {
            // Buscar todas las llamadas pendientes para esta cotización
            $llamadas = Llamada::where('id_cotizacion', $cotizacionId)
                ->where('status', Llamada::STATUS_PENDIENTE)
                ->get();

            if ($llamadas->isEmpty()) {
                Log::warning('No se encontraron llamadas pendientes para cotización', [
                    'cotizacion_id' => $cotizacionId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No hay llamadas pendientes para esta cotización',
                    'calls_initiated' => 0
                ];
            }

            $successfulCalls = [];
            $failedCalls = [];

            foreach ($llamadas as $llamada) {
                $result = $this->makeOutboundCall($llamada);
                
                if ($result['success']) {
                    $successfulCalls[] = $result;
                    
                    // Actualizar el status de la llamada y asegurar que el número esté guardado
                    $llamada->update([
                        'status' => Llamada::STATUS_EN_CURSO,
                        'numero_destino' => $result['telefono'], // Guardar el número formateado
                        'elevenlabs_conversation_id' => $result['conversation_id'] ?? null,
                        'elevenlabs_sip_call_id' => $result['sip_call_id'] ?? null
                    ]);
                } else {
                    $failedCalls[] = $result;
                }
            }

            Log::info('Llamadas ElevenLabs iniciadas para cotización', [
                'cotizacion_id' => $cotizacionId,
                'total_llamadas' => $llamadas->count(),
                'exitosas' => count($successfulCalls),
                'fallidas' => count($failedCalls)
            ]);

            return [
                'success' => true,
                'message' => 'Llamadas iniciadas exitosamente',
                'total_calls' => $llamadas->count(),
                'successful_calls' => count($successfulCalls),
                'failed_calls' => count($failedCalls),
                'calls_initiated' => count($successfulCalls),
                'details' => [
                    'successful' => $successfulCalls,
                    'failed' => $failedCalls
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error iniciando llamadas ElevenLabs para cotización: ' . $e->getMessage(), [
                'cotizacion_id' => $cotizacionId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error iniciando llamadas: ' . $e->getMessage(),
                'calls_initiated' => 0
            ];
        }
    }

    /**
     * Hacer una llamada individual usando ElevenLabs SIP Trunk
     */
    private function makeOutboundCall(Llamada $llamada)
    {
        try {
            // Obtener información del conductor
            $driver = VehicleOwnerHolderDriver::find($llamada->chofer_id);
            
            if (!$driver) {
                throw new \Exception("Conductor no encontrado para ID: {$llamada->chofer_id}");
            }

            // Limpiar y formatear el número de teléfono
            $phoneNumber = $this->formatPhoneNumber($driver->Telefonoconductor);
            
            if (!$phoneNumber) {
                throw new \Exception("Número de teléfono inválido para conductor: {$driver->Conductor}");
            }

            Log::info('Iniciando llamada ElevenLabs', [
                'llamada_id' => $llamada->id_llamada,
                'conductor' => $driver->Conductor,
                'telefono' => $phoneNumber,
                'cotizacion_id' => $llamada->id_cotizacion
            ]);

            // Realizar la llamada a ElevenLabs
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/v1/convai/sip-trunk/outbound-call', [
                'agent_id' => $this->agentId,
                'agent_phone_number_id' => $this->agentPhoneNumberId,
                'to_number' => $phoneNumber,
                'conversation_initiation_client_data' => [
                    'llamada_id' => $llamada->id_llamada,
                    'cotizacion_id' => $llamada->id_cotizacion,
                    'conductor_id' => $llamada->chofer_id,
                    'conductor_nombre' => $driver->Conductor
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Llamada ElevenLabs exitosa', [
                    'llamada_id' => $llamada->id_llamada,
                    'conversation_id' => $responseData['conversation_id'] ?? null,
                    'sip_call_id' => $responseData['sip_call_id'] ?? null
                ]);

                return [
                    'success' => true,
                    'llamada_id' => $llamada->id_llamada,
                    'conductor' => $driver->Conductor,
                    'telefono' => $phoneNumber,
                    'conversation_id' => $responseData['conversation_id'] ?? null,
                    'sip_call_id' => $responseData['sip_call_id'] ?? null,
                    'message' => $responseData['message'] ?? 'Llamada iniciada exitosamente'
                ];
            } else {
                throw new \Exception("Error en respuesta de ElevenLabs: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Error en llamada individual ElevenLabs: ' . $e->getMessage(), [
                'llamada_id' => $llamada->id_llamada,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'llamada_id' => $llamada->id_llamada,
                'conductor' => $driver->Conductor ?? 'Desconocido',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatear número de teléfono para llamadas internacionales
     */
    private function formatPhoneNumber($phoneNumber)
    {
        if (!$phoneNumber) {
            return null;
        }

        // Obtener el primer número si hay múltiples separados por " - "
        $firstPhone = explode(' - ', $phoneNumber)[0];
        $firstPhone = explode('-', $firstPhone)[0];
        $firstPhone = trim($firstPhone);
        
        // Si el número ya tiene el prefijo internacional '+', devolverlo tal como está
        if (str_starts_with($firstPhone, '+')) {
            // Validar que después del + haya al menos 7 dígitos
            $digitsOnly = preg_replace('/[^0-9]/', '', $firstPhone);
            if (strlen($digitsOnly) >= 7) {
                return $firstPhone;
            } else {
                return null; // Número inválido
            }
        }
        
        // Si no tiene '+', proceder con el formateo normal
        // Limpiar caracteres no numéricos
        $cleanPhone = preg_replace('/[^0-9]/', '', $firstPhone);
        
        // Limitar a 12 dígitos máximo (para números internacionales)
        $cleanPhone = substr($cleanPhone, 0, 12);
        
        // Validar que tenga al menos 7 dígitos
        if (strlen($cleanPhone) < 7) {
            return null;
        }

        // Formatear según la longitud del número
        if (strlen($cleanPhone) === 10) {
            // Número colombiano de 10 dígitos
            return '+57' . $cleanPhone;
        } else if (strlen($cleanPhone) >= 11 && str_starts_with($cleanPhone, '57')) {
            // Número que ya incluye código de país 57
            return '+' . $cleanPhone;
        } else if (strlen($cleanPhone) >= 7 && strlen($cleanPhone) <= 9) {
            // Número local colombiano (7-9 dígitos)
            return '+57' . $cleanPhone;
        } else {
            // Otros casos: asumir que es internacional y agregar +
            return '+' . $cleanPhone;
        }
    }

    /**
     * Verificar el estado de una llamada
     */
    public function getCallStatus($conversationId)
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey
            ])->get($this->baseUrl . '/v1/convai/conversations/' . $conversationId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo estado de llamada ElevenLabs: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Make outbound call via SIP trunk (New ElevenLabs Conversational AI API)
     */
    public function makeDirectSipCall(string $toNumber, array $clientData = null): array
    {
        try {
            Log::info('ElevenLabs SIP Trunk: Iniciando llamada saliente', [
                'to_number' => $toNumber,
                'agent_id' => $this->agentId,
                'agent_phone_number_id' => $this->agentPhoneNumberId
            ]);

            // Validar número de teléfono
            $formattedNumber = $this->formatPhoneNumber($toNumber);
            
            if (!$formattedNumber) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format',
                    'to_number' => $toNumber
                ];
            }
            
            $payload = [
                'agent_id' => $this->agentId,
                'agent_phone_number_id' => $this->agentPhoneNumberId,
                'to_number' => $formattedNumber
            ];

            // Agregar datos del cliente si se proporcionan
            if ($clientData) {
                $payload['conversation_initiation_client_data'] = $clientData;
            }

            $response = Http::timeout(60)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/v1/convai/sip-trunk/outbound-call', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('ElevenLabs SIP Trunk: Llamada iniciada exitosamente', [
                    'conversation_id' => $data['conversation_id'] ?? null,
                    'sip_call_id' => $data['sip_call_id'] ?? null,
                    'to_number' => $formattedNumber
                ]);

                return [
                    'success' => true,
                    'conversation_id' => $data['conversation_id'] ?? null,
                    'sip_call_id' => $data['sip_call_id'] ?? null,
                    'message' => $data['message'] ?? 'Call initiated successfully',
                    'to_number' => $formattedNumber
                ];
            } else {
                $error = $response->json();
                Log::error('ElevenLabs SIP Trunk: Error en llamada saliente', [
                    'status' => $response->status(),
                    'error' => $error,
                    'to_number' => $formattedNumber
                ]);

                return [
                    'success' => false,
                    'error' => $error['detail'] ?? 'Call failed',
                    'status_code' => $response->status(),
                    'to_number' => $formattedNumber
                ];
            }

        } catch (\Exception $e) {
            Log::error('ElevenLabs SIP Trunk: Excepción en llamada saliente', [
                'error' => $e->getMessage(),
                'to_number' => $toNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'to_number' => $toNumber
            ];
        }
    }

    /**
     * Get conversation details from ElevenLabs
     */
    public function getConversationDetails(string $conversationId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . "/v1/convai/conversations/{$conversationId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get conversation details',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test connection to ElevenLabs SIP trunk API
     */
    public function testSipTrunkConnection(): array
    {
        try {
            // Test basic API connection
            $response = Http::timeout(30)
                ->withHeaders([
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/v1/voices');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection to ElevenLabs SIP API successful',
                    'agent_id' => $this->agentId,
                    'agent_phone_number_id' => $this->agentPhoneNumberId,
                    'api_responsive' => true
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to ElevenLabs API',
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate phone number format for international calls
     */
    public function isValidPhoneNumber($phoneNumber): bool
    {
        if (!$phoneNumber) {
            return false;
        }

        // Remove all non-digit characters except +
        $cleanNumber = preg_replace('/[^\d+]/', '', $phoneNumber);

        // Must start with + and have at least 7 digits
        if (!str_starts_with($cleanNumber, '+')) {
            return false;
        }

        // Remove the + for length checking
        $digitsOnly = substr($cleanNumber, 1);
        
        // International numbers should have between 7 and 15 digits
        return strlen($digitsOnly) >= 7 && strlen($digitsOnly) <= 15;
    }
}