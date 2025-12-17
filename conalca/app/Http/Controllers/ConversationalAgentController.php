<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\ConversationalAgentService;
use App\Services\ElevenLabsService;
use App\Services\CallQueueService;
use App\Services\ArcangelService;
use App\Models\DriverCallResponse;
use App\Models\CotizacionModel;
use App\Models\ConversationSession;
use App\Models\ConversationMessage;
use App\Models\VehicleClass;
use App\Models\Llamada;
use App\Models\LlamadaConductor;
use App\Jobs\ProcessElevenLabsCall;
use App\Jobs\ProcessBatchElevenLabsCalls;

class ConversationalAgentController extends Controller
{
    protected $agentService;
    protected $elevenLabsService;
    protected $arcangelService;

    public function __construct(
        ConversationalAgentService $agentService, 
        ElevenLabsService $elevenLabsService,
        ArcangelService $arcangelService
    )
    {
        $this->agentService = $agentService;
        $this->elevenLabsService = $elevenLabsService;
        $this->arcangelService = $arcangelService;
    }

    /**
     * Formatear número de teléfono para llamadas internacionales
     * Si ya tiene prefijo de país (+58, +57, etc.) lo mantiene
     * Si no tiene prefijo, agrega +57 (Colombia) por defecto
     */
    private function formatPhoneNumber($phoneNumber)
    {
        if (!$phoneNumber || $phoneNumber === 'N/A') {
            return null;
        }

        // Limpiar el número pero preservar el signo +
        $cleanNumber = trim($phoneNumber);
        
        // Tomar solo el primer número si hay varios separados por ' - ' o '-'
        $firstPhone = explode(' - ', $cleanNumber)[0];
        $firstPhone = explode('-', $firstPhone)[0];
        
        // Si ya tiene el signo + al inicio, es un número internacional
        if (str_starts_with($firstPhone, '+')) {
            // Limpiar todo excepto números y el signo +
            $formattedPhone = preg_replace('/[^+0-9]/', '', $firstPhone);
            
            // Validar que tenga al menos 10 dígitos después del +
            $numbersOnly = str_replace('+', '', $formattedPhone);
            if (strlen($numbersOnly) >= 10) {
                return $formattedPhone;
            }
        }
        
        // Si no tiene + o es muy corto, tratar como número nacional
        $numbersOnly = preg_replace('/[^0-9]/', '', $firstPhone);
        
        // Si tiene 10 dígitos, es número nacional colombiano
        if (strlen($numbersOnly) === 10) {
            return '+57' . $numbersOnly;
        }
        
        // Si tiene más de 10 dígitos, podría ser internacional sin +
        if (strlen($numbersOnly) > 10) {
            return '+' . $numbersOnly;
        }
        
        // Si es muy corto, agregar +57 y rellenar si es necesario
        if (strlen($numbersOnly) >= 7) {
            return '+57' . $numbersOnly;
        }
        
        return null; // Número inválido
    }

    /**
     * Endpoint mejorado para manejar respuestas del conductor durante la llamada
     * Sistema conversacional avanzado con IA que puede responder cualquier pregunta
     */
    public function handleDriverResponse(Request $request)
    {
        Log::info("Nueva respuesta conversacional del conductor recibida", [
            'call_sid' => $request->input('CallSid'),
            'digits' => $request->input('Digits'),
            'speech_result' => $request->input('SpeechResult'),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Obtener información de la llamada
            $callSid = $request->input('CallSid');
            $digits = $request->input('Digits');
            $speechResult = $request->input('SpeechResult');
            $sessionId = $request->input('session_id'); // Para llamadas directas
            
            // Buscar o crear sesión de conversación
            if ($sessionId) {
                $session = ConversationSession::find($sessionId);
            } else {
                $session = $this->getOrCreateConversationSession($callSid);
            }
            
            if (!$session) {
                Log::error("No se pudo crear/encontrar sesión de conversación para SID: $callSid");
                return $this->generateErrorTwiML();
            }

            // Determinar la entrada del usuario (dígitos o voz)
            $userInput = $digits ?: $speechResult;
            
            if (!$userInput) {
                Log::info("No se recibió entrada del usuario, solicitando respuesta");
                return $this->generateRequestInputTwiML($session);
            }

            Log::info("Procesando respuesta conversacional", [
                'call_sid' => $callSid,
                'session_id' => $session->id,
                'user_input' => $userInput,
                'conversation_turn' => $session->turn_count + 1
            ]);

            // Guardar mensaje del usuario en la conversación
            ConversationMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $userInput,
                'timestamp' => now()
            ]);

            // Procesar respuesta con el agente conversacional mejorado
            $agentResponse = $this->agentService->processDriverResponse(
                $session->cotizacion_id,
                $session->driver_id,
                $userInput,
                $this->getFullConversationHistory($session)
            );

            Log::info("Respuesta del agente conversacional generada", [
                'session_id' => $session->id,
                'decision' => $agentResponse['decision'] ?? 'CONTINUE',
                'confidence' => $agentResponse['confidence'] ?? 'N/A',
                'conversation_ended' => $agentResponse['end_conversation'] ?? false,
                'message_preview' => substr($agentResponse['response_message'] ?? '', 0, 100) . '...'
            ]);

            // Guardar mensaje del agente en la conversación
            ConversationMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $agentResponse['response_message'],
                'metadata' => json_encode([
                    'decision' => $agentResponse['decision'] ?? null,
                    'confidence' => $agentResponse['confidence'] ?? null,
                    'intent' => $agentResponse['intent'] ?? null
                ]),
                'timestamp' => now()
            ]);

            // Actualizar estado de la sesión
            $this->updateConversationSession($session, $agentResponse);

            // Generar TwiML de respuesta mejorado
            return $this->generateConversationalTwiML($agentResponse, $session);

        } catch (\Exception $e) {
            Log::error("Error manejando respuesta conversacional del conductor: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Endpoint mejorado para manejar la llamada inicial de Twilio
     * Ahora con sistema conversacional avanzado
     */
    public function handleInitialCall(Request $request)
    {
        try {
            $callSid = $request->input('CallSid');
            $from = $request->input('From');
            $to = $request->input('To');
            $driverId = $request->input('driver_id');
            $cotizacionId = $request->input('cotizacion_id');
            $isDirectCall = $request->input('direct_call', false);
            $phoneNumber = $request->input('phone_number');
            $initialMessage = $request->input('initial_message');
            
            Log::info('Llamada inicial conversacional recibida de Twilio', [
                'call_sid' => $callSid,
                'from' => $from,
                'to' => $to,
                'driver_id' => $driverId,
                'cotizacion_id' => $cotizacionId,
                'is_direct_call' => $isDirectCall,
                'phone_number' => $phoneNumber
            ]);

            // Manejar llamadas directas
            if ($isDirectCall && $phoneNumber && $initialMessage) {
                return $this->handleDirectCallInitial($callSid, $phoneNumber, $initialMessage);
            }

            // Crear nueva sesión de conversación para cotizaciones
            $session = $this->createConversationSession($callSid, $cotizacionId, $driverId);
            
            if (!$session) {
                Log::error("No se pudo crear sesión de conversación para SID: $callSid");
                return $this->generateErrorTwiML();
            }

            // Obtener información de la cotización
            $cotizacion = CotizacionModel::find($cotizacionId);
            
            if (!$cotizacion) {
                Log::error("No se encontró cotización: {$cotizacionId}");
                return $this->generateErrorTwiML();
            }

            // Obtener información del conductor
            $driver = \App\Models\VehicleOwnerHolderDriver::find($driverId);
            $driverName = $driver ? $vehiculo['conductor'] ?? 'N/A' : 'conductor';
            
            // Generar mensaje inicial conversacional
            $conversationalMessage = $this->agentService->generateInitialMessage(
                $cotizacion, 
                $driverName
            );

            // Guardar mensaje inicial en la conversación
            ConversationMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $conversationalMessage,
                'timestamp' => now()
            ]);

            Log::info('Mensaje inicial conversacional preparado', [
                'call_sid' => $callSid,
                'session_id' => $session->id,
                'cotizacion_id' => $cotizacionId,
                'driver_name' => $driverName,
                'message_length' => strlen($conversationalMessage)
            ]);

            // Generar TwiML inicial conversacional
            return $this->generateInitialConversationalTwiML($conversationalMessage, $session);

        } catch (\Exception $e) {
            Log::error("Error en llamada inicial conversacional: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Manejar llamadas directas (sin cotización)
     */
    private function handleDirectCallInitial($callSid, $phoneNumber, $message)
    {
        try {
            // Crear sesión de conversación para llamada directa (sin cotización ni conductor específico)
            $session = ConversationSession::create([
                'call_sid' => $callSid,
                'cotizacion_id' => null,
                'driver_id' => null,
                'driver_phone' => $phoneNumber,
                'conversation_type' => 'direct_call',
                'status' => 'active',
                'started_at' => now()
            ]);

            // Guardar mensaje inicial en la conversación
            ConversationMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $message,
                'timestamp' => now()
            ]);

            Log::info('Llamada directa procesada', [
                'call_sid' => $callSid,
                'session_id' => $session->id,
                'phone_number' => $phoneNumber,
                'message' => $message
            ]);

            // Generar TwiML para llamada directa
            return $this->generateInitialConversationalTwiML($message, $session);

        } catch (\Exception $e) {
            Log::error("Error en llamada directa inicial: " . $e->getMessage());
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Crear nueva sesión de conversación
     */
    private function createConversationSession($callSid, $cotizacionId, $driverId)
    {
        try {
            // Generar call_sid temporal si no se proporciona (para pruebas o llamadas directas)
            if (empty($callSid)) {
                $callSid = 'TEMP_' . uniqid() . '_' . time();
                Log::warning("CallSid vacío, generando temporal: {$callSid}");
            }

            return ConversationSession::create([
                'call_sid' => $callSid,
                'cotizacion_id' => $cotizacionId,
                'driver_id' => $driverId,
                'status' => 'active',
                'turn_count' => 0,
                'started_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error("Error creando sesión de conversación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener o crear sesión de conversación existente
     */
    private function getOrCreateConversationSession($callSid)
    {
        try {
            // Generar call_sid temporal si no se proporciona
            if (empty($callSid)) {
                $callSid = 'TEMP_' . uniqid() . '_' . time();
                Log::warning("CallSid vacío en getOrCreate, generando temporal: {$callSid}");
            }

            $session = ConversationSession::where('call_sid', $callSid)->first();
            
            if (!$session) {
                // NOTE: Twilio lookup disabled - service removed
                Log::warning('ConversationalAgentController: Twilio lookup disabled - call_sid not found', ['call_sid' => $callSid]);
                $callResponse = null; // DriverCallResponse::where('twilio_call_sid', $callSid)->first();
                
                if ($callResponse) {
                    $session = $this->createConversationSession(
                        $callSid, 
                        $callResponse->cotizacion_id, 
                        $callResponse->driver_id
                    );
                }
            }
            
            return $session;
        } catch (\Exception $e) {
            Log::error("Error obteniendo/creando sesión de conversación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar estado de la sesión de conversación
     */
    private function updateConversationSession($session, $agentResponse)
    {
        try {
            $updateData = [
                'turn_count' => $session->turn_count + 1,
                'updated_at' => now()
            ];

            // Actualizar estado basado en la respuesta del agente
            if (isset($agentResponse['end_conversation']) && $agentResponse['end_conversation']) {
                $updateData['status'] = 'completed';
                $updateData['ended_at'] = now();
            }

            if (isset($agentResponse['decision'])) {
                $updateData['final_decision'] = $agentResponse['decision'];
            }

            $session->update($updateData);

            Log::info("Sesión de conversación actualizada", [
                'session_id' => $session->id,
                'turn_count' => $updateData['turn_count'],
                'status' => $updateData['status'] ?? 'active'
            ]);

        } catch (\Exception $e) {
            Log::error("Error actualizando sesión de conversación: " . $e->getMessage());
        }
    }

    /**
     * Obtener historial completo de conversación
     */
    private function getFullConversationHistory($session)
    {
        try {
            return ConversationMessage::where('session_id', $session->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content,
                        'timestamp' => $message->created_at->toISOString(),
                        'metadata' => json_decode($message->metadata, true)
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Error obteniendo historial de conversación: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar TwiML inicial conversacional usando ElevenLabs con streaming
     */
    private function generateInitialConversationalTwiML($message, $session)
    {
        try {
            if (!$message) {
                $message = 'Hola, le hablamos de Conalca. ¿Cómo está usted?';
            }

            Log::info('Generando TwiML conversacional inicial', [
                'session_id' => $session->id,
                'message_length' => strlen($message)
            ]);

            $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
            $twiml .= '<Response>';
            
            // URL para capturar respuesta DESPUÉS de que termine el audio
            $waitForResponseUrl = route('agent.wait-for-response') . '?' . http_build_query([
                'session_id' => $session->id
            ]);
            
            // PRIORIDAD 1: Intentar usar audio pre-generado desde el cache
            $audioUrl = null;
            $usedPregenerated = false;
            
            // Buscar audio pre-generado por cotización y conductor
            if ($session->cotizacion_id && $session->driver_id) {
                $cachedAssets = $this->agentService->getCachedInitialAssets(
                    $session->cotizacion_id, 
                    $session->driver_id
                );
                
                if ($cachedAssets && isset($cachedAssets['audio_url'])) {
                    $audioUrl = $cachedAssets['audio_url'];
                    $usedPregenerated = true;
                    Log::info('Usando audio pre-generado desde cache', [
                        'url' => $audioUrl,
                        'session_id' => $session->id,
                        'cotizacion_id' => $session->cotizacion_id,
                        'driver_id' => $session->driver_id
                    ]);
                }
            }
            
            // PRIORIDAD 2: Si no hay pre-generado, generar en tiempo real (con delay)
            if (!$audioUrl) {
                Log::info('No se encontró audio pre-generado, generando en tiempo real', [
                    'session_id' => $session->id
                ]);
                
                $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                    $message, 
                    null, 
                    'greeting'
                );
                
                if ($audioUrl) {
                    Log::info('Audio generado en tiempo real (con delay)', [
                        'url' => $audioUrl,
                        'session_id' => $session->id,
                        'message_length' => strlen($message)
                    ]);
                }
            }
            
            // PASO 1: Reproducir audio inicial completo
            if ($audioUrl) {
                $twiml .= '<Play>' . $audioUrl . '</Play>';
                
                if ($usedPregenerated) {
                    Log::info('✅ LLAMADA SIN DELAY - Audio pre-generado usado exitosamente');
                } else {
                    Log::warning('⚠️ DELAY EN LLAMADA - Audio generado durante la llamada');
                }
            } else {
                // Fallback final
                Log::error('Error crítico: No se pudo generar audio con ElevenLabs - usando fallback Polly', [
                    'session_id' => $session->id
                ]);
                $twiml .= '<Say voice="Polly.Lupe" language="es-MX">' . htmlspecialchars($message) . '</Say>';
            }
            
            // PASO 2: Después de que termine el audio, redirigir para capturar respuesta
            $twiml .= '<Redirect>' . $waitForResponseUrl . '</Redirect>';
            $twiml .= '<Hangup/>';
            $twiml .= '</Response>';

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error generando TwiML conversacional inicial', [
                'error' => $e->getMessage(),
                'session_id' => $session->id ?? 'unknown'
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Endpoint para esperar respuesta DESPUÉS de que termine el audio inicial
     */
    public function waitForResponse(Request $request)
    {
        try {
            $sessionId = $request->query('session_id');
            $session = ConversationSession::find($sessionId);
            
            if (!$session) {
                Log::error('Sesión no encontrada para wait-for-response', ['session_id' => $sessionId]);
                return $this->generateErrorTwiML();
            }

            Log::info('Generando TwiML para capturar respuesta después del audio', [
                'session_id' => $session->id,
                'current_turn_count' => $session->turn_count
            ]);

            $webhookUrl = route('agent.handle-response') . '?' . http_build_query([
                'session_id' => $session->id
            ]);

            $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
            $twiml .= '<Response>';
            
            // AHORA SÍ capturar respuesta - el audio ya terminó completamente
            $twiml .= '<Gather input="speech dtmf" timeout="5" speechTimeout="5" language="es-CO" action="' . $webhookUrl . '" method="POST" enhanced="true">';
            $twiml .= '</Gather>';
            
            // Si no responde después de 5 segundos, verificar si es primer o segundo intento
            if ($session->turn_count == 0) {
                // Primera vez sin respuesta - dar segunda oportunidad
                $secondChanceUrl = route('agent.second-chance') . '?' . http_build_query([
                    'session_id' => $session->id
                ]);
                $twiml .= '<Redirect>' . $secondChanceUrl . '</Redirect>';
            } else {
                // Segunda vez sin respuesta - finalizar llamada
                $noResponseUrl = asset('storage/audios/static/static_no_response.mp3');
                $twiml .= '<Play>' . $noResponseUrl . '</Play>';
                $twiml .= '<Hangup/>';
            }
            
            $twiml .= '</Response>';

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error en wait-for-response', [
                'error' => $e->getMessage(),
                'session_id' => $request->query('session_id')
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Endpoint para dar segunda oportunidad cuando no responde la primera vez
     */
    public function secondChance(Request $request)
    {
        try {
            $sessionId = $request->query('session_id');
            $session = ConversationSession::find($sessionId);
            
            if (!$session) {
                Log::error('Sesión no encontrada para second-chance', ['session_id' => $sessionId]);
                return $this->generateErrorTwiML();
            }

            // Incrementar contador de turnos
            $session->increment('turn_count');
            
            Log::info('Dando segunda oportunidad al conductor', [
                'session_id' => $session->id,
                'turn_count' => $session->turn_count
            ]);

            // Generar mensaje de segunda oportunidad
            $secondChanceMessage = "Disculpe, no escuché su respuesta. ¿Puede decirme si acepta o no acepta esta oferta de trabajo?";
            
            // Intentar generar con ElevenLabs
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                $secondChanceMessage,
                null,  // voice_id por defecto
                'urgent'  // prioridad alta
            );

            $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
            $twiml .= '<Response>';
            
            if ($audioUrl) {
                // Reproducir pregunta de segunda oportunidad
                $twiml .= '<Play>' . $audioUrl . '</Play>';
                
                // Redirigir a wait-for-response para la segunda oportunidad
                $waitForResponseUrl = route('agent.wait-for-response') . '?' . http_build_query([
                    'session_id' => $session->id
                ]);
                $twiml .= '<Redirect>' . $waitForResponseUrl . '</Redirect>';
            } else {
                // Fallback: usar audio estático
                $fallbackUrl = asset('storage/audios/calls/conalca_pregunta.mp3');
                $twiml .= '<Play>' . $fallbackUrl . '</Play>';
                
                $waitForResponseUrl = route('agent.wait-for-response') . '?' . http_build_query([
                    'session_id' => $session->id
                ]);
                $twiml .= '<Redirect>' . $waitForResponseUrl . '</Redirect>';
            }
            
            $twiml .= '</Response>';

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error en second-chance', [
                'error' => $e->getMessage(),
                'session_id' => $request->query('session_id')
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Generar TwiML conversacional para respuestas usando ElevenLabs
     */
    private function generateConversationalTwiML($agentResponse, $session)
    {
        try {
            $responseMessage = $agentResponse['response_message'] ?? 'Gracias por su respuesta.';
            $endConversation = $agentResponse['end_conversation'] ?? false;
            $intent = $agentResponse['intent'] ?? 'unknown';
            
            Log::info('Generando TwiML conversacional', [
                'session_id' => $session->id,
                'intent' => $intent,
                'end_conversation' => $endConversation,
                'message_preview' => substr($responseMessage, 0, 50) . '...'
            ]);
            
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
            $twiml .= '<Response>';
            
            if (!$endConversation) {
                // Continuar conversación - permitir nueva respuesta con timeout estándar de 5 segundos
                $webhookUrl = route('agent.handle-response') . '?' . http_build_query([
                    'session_id' => $session->id
                ]);
                
                $twiml .= '<Gather input="speech dtmf" timeout="5" speechTimeout="5" language="es-CO" action="' . $webhookUrl . '" method="POST" enhanced="true">';
                
                // Generar audio optimizado con ElevenLabs para conversaciones más fluidas
                $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                    $responseMessage, 
                    null, 
                    'conversation'
                );
                
                if ($audioUrl) {
                    Log::info('Usando audio optimizado de ElevenLabs para respuesta conversacional', [
                        'url' => $audioUrl,
                        'session_id' => $session->id,
                        'text_length' => strlen($responseMessage)
                    ]);
                    $twiml .= '<Play>' . $audioUrl . '</Play>';
                } else {
                    // Fallback al método optimizado estándar
                    Log::warning('Fallback al método optimizado estándar de ElevenLabs');
                    $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech($responseMessage, null, 'conversation');
                    
                    if ($audioUrl) {
                        $twiml .= '<Play>' . $audioUrl . '</Play>';
                    } else {
                        Log::warning('Fallback final a Polly para respuesta conversacional', [
                            'session_id' => $session->id
                        ]);
                        $twiml .= '<Say voice="Polly.Lupe" language="es-MX">' . htmlspecialchars($responseMessage) . '</Say>';
                    }
                }
                
                $twiml .= '</Gather>';
                
                // Mensaje de cierre si no responde - usar audio pregenerado
                $noResponseUrl = asset('storage/audios/static/static_no_response.mp3');
                $twiml .= '<Play>' . $noResponseUrl . '</Play>';
            } else {
                // Finalizar conversación usando audios pregenerados cuando sea posible
                $staticAudioUrl = $this->getStaticAudioUrlForResponse($agentResponse);
                
                if ($staticAudioUrl) {
                    Log::info('Usando audio estático pregenerado', [
                        'url' => $staticAudioUrl,
                        'intent' => $intent,
                        'session_id' => $session->id
                    ]);
                    $twiml .= '<Play>' . $staticAudioUrl . '</Play>';
                } else {
                    // Generar audio en tiempo real si no hay audio estático
                    $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                        $responseMessage, 
                        null, 
                        'conversation'
                    );
                    
                    if ($audioUrl) {
                        $twiml .= '<Play>' . $audioUrl . '</Play>';
                    } else {
                        $twiml .= '<Say voice="Polly.Lupe" language="es-MX">' . htmlspecialchars($responseMessage) . '</Say>';
                    }
                }
            }
            
            $twiml .= '<Hangup/>';
            $twiml .= '</Response>';

            Log::info("TwiML conversacional generado", [
                'session_id' => $session->id,
                'continues_conversation' => !$endConversation
            ]);

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error generando TwiML conversacional', [
                'error' => $e->getMessage(),
                'session_id' => $session->id ?? 'unknown'
            ]);
            return $this->generateErrorTwiML();
        }
    }

    /**
     * Obtener URL de audio estático basado en la respuesta del agente
     */
    private function getStaticAudioUrlForResponse($agentResponse)
    {
        $intent = $agentResponse['intent'] ?? null;
        $decision = $agentResponse['decision'] ?? null;
        $responseMessage = $agentResponse['response_message'] ?? '';
        
        // Mapear intenciones/decisiones a audios estáticos
        if ($decision === 'ACCEPT' || $intent === 'ACCEPT') {
            $filename = 'static_accept_response.mp3';
        } elseif ($decision === 'REJECT' || $intent === 'REJECT') {
            $filename = 'static_reject_response.mp3';
        } elseif ($decision === 'UNCLEAR' || $intent === 'CLARIFY') {
            $filename = 'static_clarify_response.mp3';
        } elseif (stripos($responseMessage, 'no escuché') !== false || 
                  stripos($responseMessage, 'escuchar') !== false ||
                  stripos($responseMessage, 'interesado') !== false) {
            $filename = 'static_clarify_response.mp3';
        } else {
            return null; // Usar audio dinámico
        }
        
        $filePath = storage_path('app/public/audios/static/' . $filename);
        
        if (file_exists($filePath)) {
            return asset('storage/audios/static/' . $filename);
        }
        
        return null;
    }

    /**
     * Generar TwiML para solicitar entrada cuando no se recibe respuesta
     */
    private function generateRequestInputTwiML($session)
    {
        try {
            $message = "¿Me escucha? Soy de CONALCA. ¿Puede responderme por favor?";
            
            Log::info('Generando TwiML de solicitud de entrada', [
                'session_id' => $session->id
            ]);
            
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
            $twiml .= '<Response>';
            $twiml .= '<Gather input="speech dtmf" timeout="5" speechTimeout="5" language="es-CO" action="' . route('agent.handle-response') . '" method="POST" enhanced="true">';
            
            // Usar audio optimizado para solicitud urgente
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                $message, 
                null, 
                'urgent'
            );
            
            if ($audioUrl) {
                $twiml .= '<Play>' . $audioUrl . '</Play>';
            } else {
                $twiml .= '<Say voice="Polly.Lupe" language="es-MX">' . htmlspecialchars($message) . '</Say>';
            }
            
            $twiml .= '</Gather>';
            
            $closingMessage = "Muchas gracias por atender. Que esté muy bien.";
            $closingAudioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech(
                $closingMessage, 
                null, 
                'conversation'
            );
            
            if ($closingAudioUrl) {
                $twiml .= '<Play>' . $closingAudioUrl . '</Play>';
            } else {
                $twiml .= '<Say voice="Polly.Lupe" language="es-MX">' . htmlspecialchars($closingMessage) . '</Say>';
            }
            
            $twiml .= '<Hangup/>';
            $twiml .= '</Response>';

            return response($twiml, 200)->header('Content-Type', 'text/xml');

        } catch (\Exception $e) {
            Log::error('Error generando TwiML de request input', [
                'error' => $e->getMessage(),
                'session_id' => $session->id ?? 'unknown'
            ]);
            return $this->generateErrorTwiML();
        }
    }



    /**
     * Obtener mensaje de cierre basado en el intent
     */
    private function getClosingMessageBasedOnIntent($intent)
    {
        $closingMessages = [
            'greeting' => "Bueno, si no puede hablar ahora, lo contactamos después. Buen día.",
            'question' => "Si tiene más preguntas, nos puede llamar después. Que esté bien.",
            'clarification' => "Listo, no se preocupe. Muchas gracias por su tiempo.",
            'negotiation' => "Perfecto, evaluamos y le respondemos pronto. Gracias.",
            'decision' => "Entendido. Muchas gracias por su tiempo y que tenga buen día.",
            'default' => "Muchas gracias por atender. Que tenga un excelente día."
        ];

        return $closingMessages[$intent] ?? $closingMessages['default'];
    }

    /**
     * Generar TwiML de error
     */
    private function generateErrorTwiML()
    {
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        $twiml .= '<Say voice="Polly.Lupe" language="es-MX">Ha ocurrido un error. Por favor intente más tarde.</Say>';
        $twiml .= '<Hangup/>';
        $twiml .= '</Response>';

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Endpoint mejorado para iniciar llamada conversacional al conductor
     * Compatible con el comando curl del usuario
     */
    public function initiateConversationalCall(Request $request)
    {
        try {
            Log::info('========================================');
            Log::info('INICIO DE LLAMADA CONVERSACIONAL DESDE FRONTEND');
            Log::info('========================================');
            
            $cotizacionId = $request->input('cotizacion_model_id');

            Log::info('Request recibido', [
                'cotizacion_id' => $cotizacionId,
                'all_request_data' => $request->all(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip()
            ]);

            if (!$cotizacionId) {
                Log::error('ERROR: ID de cotización no proporcionado');
                return response()->json(['error' => 'ID de cotización requerido'], 400);
            }

            Log::info('Validando cotización ID: ' . $cotizacionId);

            // Obtener la cotización completa
            Log::info('Buscando cotización en BD...');
            $cotizacion = CotizacionModel::find($cotizacionId);
            
            if (!$cotizacion) {
                Log::error('ERROR: Cotización no encontrada', ['cotizacion_id' => $cotizacionId]);
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }
            
            Log::info('Cotización encontrada', [
                'id' => $cotizacion->id,
                'origen' => $cotizacion->ciudad_origen,
                'destino' => $cotizacion->ciudad_destino,
                'vehiculo' => $cotizacion->vehiculo_requerido
            ]);

            if (!$cotizacion->ciudad_origen) {
                Log::error('ERROR: Cotización sin ciudad de origen', ['cotizacion_id' => $cotizacionId]);
                return response()->json([
                    'error' => 'La cotización no tiene ciudad de origen definida',
                    'cotizacion_id' => $cotizacionId
                ], 400);
            }

            // Usar ArcangelService para buscar conductores (igual que ArcangelDriversController)
            Log::info('Buscando conductores con ArcangelService', [
                'cotizacion_id' => $cotizacionId,
                'ciudad_origen' => $cotizacion->ciudad_origen,
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido
            ]);

            $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
            $variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido);
            
            Log::info('Parámetros de búsqueda preparados', [
                'ciudad_normalizada' => $ciudadOrigen,
                'variantes_vehiculo' => $variantesVehiculo
            ]);
            
            // Buscar en Arcángel API con todas las variantes del vehículo
            Log::info('Consultando API de Arcángel...');
            $resultado = $this->arcangelService->getVehiculosFiltrados(
                ciudad: $ciudadOrigen,
                clases: $variantesVehiculo,
                minScore: 0, // Sin filtro de score para llamadas
                limit: 5, // Máximo 5 conductores
                useCache: true,
                cacheTTL: 15
            );
            
            $vehiculos = $resultado['vehiculos'] ?? [];

            if (empty($vehiculos)) {
                Log::warning('No se encontraron conductores en Arcángel', [
                    'cotizacion_id' => $cotizacionId,
                    'ciudad' => $ciudadOrigen,
                    'variantes' => $variantesVehiculo
                ]);
                
                return response()->json([
                    'error' => 'No se encontraron conductores disponibles para esta cotización',
                    'cotizacion_id' => $cotizacionId,
                    'ciudad' => $ciudadOrigen,
                    'vehiculo_requerido' => $cotizacion->vehiculo_requerido
                ], 404);
            }

            Log::info('Conductores encontrados en Arcángel', [
                'total' => count($vehiculos),
                'ciudad' => $ciudadOrigen
            ]);

            $callResults = [];
            
            // Realizar llamadas a conductores
            foreach ($vehiculos as $vehiculo) {
                try {
                    // Obtener teléfono del vehiculo de Arcángel
                    $phoneNumber = $vehiculo['telefono'] ?? 'N/A';
                    $formattedPhone = $this->formatPhoneNumber($phoneNumber);

                    if (!$formattedPhone) {
                        Log::warning('Número de teléfono inválido para conductor', [
                            'conductor' => $vehiculo['conductor'] ?? 'N/A',
                            'placa' => $vehiculo['placa'] ?? 'N/A',
                            'original_phone' => $phoneNumber
                        ]);
                        continue;
                    }

                    // Preparar datos de cotización
                    $cotizacionData = [
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
                        'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
                        'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                        'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
                        'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
                        'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
                    ];

                    // Crear o actualizar registro del conductor CON información de cotización
                    Log::info('Creando/actualizando conductor en BD con datos de cotización...');
                    $conductor = \App\Models\LlamadaConductor::createFromArcangel(
                        $vehiculo,
                        $ciudadOrigen,
                        $cotizacion->id,
                        $cotizacion->group_cotization_id,
                        $cotizacionData
                    );
                    
                    Log::info('Conductor registrado con cotización', [
                        'conductor_id' => $conductor->id,
                        'nombre' => $conductor->nombre_conductor,
                        'telefono' => $conductor->telefono,
                        'placa' => $conductor->placa,
                        'cotizacion_id' => $conductor->cotizacion_id,
                        'group_cotization_id' => $conductor->group_cotization_id
                    ]);

                    Log::info('Preparando llamada a conductor', [
                        'conductor_id' => $conductor->id,
                        'conductor' => $conductor->nombre_conductor,
                        'placa' => $conductor->placa,
                        'phone' => $formattedPhone,
                        'clase' => $conductor->clase_vehiculo,
                        'cotizacion_id' => $cotizacionId
                    ]);

                    // Crear registro de llamada en tabla llamadas
                    Log::info('Creando registro de llamada en BD...');
                    $llamada = Llamada::create([
                        'id_cotizacion' => $cotizacionId,
                        'conductor_id' => $conductor->id,
                        'numero_destino' => $formattedPhone,
                        'status' => Llamada::STATUS_PENDIENTE,
                        'call_started_at' => now()
                    ]);
                    
                    Log::info('Llamada registrada', [
                        'llamada_id' => $llamada->id_llamada,
                        'numero_destino' => $llamada->numero_destino,
                        'status' => $llamada->status
                    ]);

                    // Crear objeto driver simulado con datos de Arcángel para makeConversationalCall
                    $driverData = (object)[
                        'id' => $conductor->id,
                        'Conductor' => $conductor->nombre_conductor,
                        'Placa' => $conductor->placa,
                        'Telefonoconductor' => $formattedPhone,
                        'Clasevehiculo' => $conductor->clase_vehiculo
                    ];

                    // Realizar llamada usando ElevenLabs
                    Log::info('>>> Iniciando llamada con ElevenLabs', [
                        'llamada_id' => $llamada->id_llamada,
                        'conductor' => $driverData->Conductor,
                        'telefono' => $driverData->Telefonoconductor
                    ]);
                    
                    $callResult = $this->makeConversationalCall($driverData, $cotizacion, $llamada);
                    
                    Log::info('<<< Resultado de ElevenLabs', [
                        'llamada_id' => $llamada->id_llamada,
                        'success' => $callResult['success'] ?? false,
                        'conversation_id' => $callResult['conversation_id'] ?? null,
                        'sip_call_id' => $callResult['sip_call_id'] ?? null,
                        'error' => $callResult['error'] ?? null
                    ]);
                    
                    $callResults[] = [
                        'conductor_id' => $conductor->id,
                        'conductor' => $conductor->nombre_conductor,
                        'placa' => $conductor->placa,
                        'phone' => $formattedPhone,
                        'call_result' => $callResult,
                        'status' => $callResult['status'] ?? 'unknown'
                    ];
                    
                } catch (\Exception $e) {
                    Log::error("Error llamando a conductor {$driver->id}: " . $e->getMessage());
                    
                    $errorPhone = $this->formatPhoneNumber($driver->Telefonoconductor ?? $driver->Telefonopropietario ?? $driver->Telefonoposeedor ?? 'N/A') ?? 'INVALID';
                    
                    $callResults[] = [
                        'driver_id' => $driver->id,
                        'driver_name' => $vehiculo['conductor'] ?? 'N/A',
                        'phone' => $errorPhone,
                        'error' => $e->getMessage(),
                        'status' => 'error'
                    ];
                }
            }

            Log::info('Llamadas conversacionales completadas', [
                'cotizacion_id' => $cotizacionId,
                'total_drivers' => count($drivers),
                'calls_made' => count($callResults)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Llamadas conversacionales iniciadas exitosamente',
                'cotizacion_id' => $cotizacionId,
                'cotizacion_details' => [
                    'origen' => $cotizacion->ciudad_origen,
                    'destino' => $cotizacion->ciudad_destino,
                    'vehiculo' => $cotizacion->vehiculo_requerido,
                    'valor' => $cotizacion->valor_declarado
                ],
                'drivers_called' => $callResults,
                'total_calls' => count($callResults)
            ]);

        } catch (\Exception $e) {
            Log::error("Error iniciando llamadas conversacionales: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar llamada conversacional usando ElevenLabs
     */
    private function makeConversationalCall($driver, $cotizacion, $llamada)
    {
        try {
            Log::info('==================================================');
            Log::info('ENTRANDO A makeConversationalCall()');
            Log::info('==================================================');
            Log::info('Parámetros recibidos', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->Conductor,
                'driver_phone' => $driver->Telefonoconductor,
                'cotizacion_id' => $cotizacion->id,
                'llamada_id' => $llamada->id_llamada,
                'numero_destino' => $llamada->numero_destino
            ]);
            
            // Ejecutar backfill automático de llamadas pendientes
            Log::info('Ejecutando backfill automático...');
            $this->runAutomaticBackfill();

            // Obtener el teléfono del conductor desde la llamada ya formateado
            $phoneNumber = $llamada->numero_destino;
            
            Log::info('Teléfono extraído de llamada', ['phone' => $phoneNumber]);
            
            if (!$phoneNumber) {
                Log::error('ERROR: Teléfono vacío o null');
                throw new \Exception('El conductor no tiene un número de teléfono válido');
            }

            Log::info('Preparando datos para ElevenLabs');

            // Preparar datos de contexto para el agente
            $clientData = [
                'cotizacion_id' => $cotizacion->id,
                'llamada_id' => $llamada->id_llamada,
                'conductor_id' => $driver->id,
                'conductor_nombre' => $driver->Conductor ?? 'conductor',
                'placa' => $driver->Placa ?? 'N/A',
                'origen' => $cotizacion->ciudad_origen ?? 'No especificado',
                'destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                'vehiculo' => $cotizacion->vehiculo_requerido ?? 'N/A',
                'valor_flete' => $cotizacion->valor_flete ?? 'Por negociar'
            ];
            
            Log::info('Client data preparado', $clientData);

            // Usar el servicio de ElevenLabs para hacer la llamada
            Log::info('Obteniendo instancia de ElevenLabsCallService...');
            $elevenLabsService = app(\App\Services\ElevenLabsCallService::class);
            
            Log::info('Llamando a elevenLabsService->makeDirectSipCall()...', [
                'phone' => $phoneNumber,
                'client_data' => $clientData
            ]);
            
            $callResult = $elevenLabsService->makeDirectSipCall(
                $phoneNumber,
                $clientData
            );
            
            Log::info('Respuesta de makeDirectSipCall', [
                'success' => $callResult['success'] ?? false,
                'conversation_id' => $callResult['conversation_id'] ?? null,
                'sip_call_id' => $callResult['sip_call_id'] ?? null,
                'error' => $callResult['error'] ?? null
            ]);

            if ($callResult['success']) {
                Log::info('Llamada exitosa, actualizando registro en BD...');
                
                // Actualizar registro con la información de ElevenLabs
                $llamada->update([
                    'status' => Llamada::STATUS_EN_CURSO,
                    'call_status' => Llamada::CALL_STATUS_INITIATED,
                    'elevenlabs_conversation_id' => $callResult['conversation_id'] ?? null,
                    'elevenlabs_sip_call_id' => $callResult['sip_call_id'] ?? null,
                    'call_initiated_at' => now(),
                    'elevenlabs_response' => $callResult
                ]);

                Log::info('Registro actualizado correctamente', [
                    'llamada_id' => $llamada->id_llamada,
                    'status' => $llamada->status,
                    'conversation_id' => $llamada->elevenlabs_conversation_id
                ]);

                return [
                    'success' => true,
                    'conversation_id' => $callResult['conversation_id'] ?? null,
                    'sip_call_id' => $callResult['sip_call_id'] ?? null,
                    'message' => 'Llamada iniciada exitosamente',
                    'to' => $phoneNumber,
                    'status' => 'initiated'
                ];
            } else {
                Log::error('Llamada falló', ['error' => $callResult['error'] ?? 'Unknown error']);
                throw new \Exception($callResult['error'] ?? 'Error desconocido al iniciar llamada');
            }

        } catch (\Exception $e) {
            Log::error("EXCEPCIÓN en makeConversationalCall: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Determinar el tipo de error y código SIP si es aplicable
            $errorMessage = $e->getMessage();
            $failureReason = $errorMessage;
            $sipCode = null;
            $callStatus = Llamada::CALL_STATUS_FAILED;
            
            // Detectar errores específicos
            if (strpos($errorMessage, '486') !== false || strpos($errorMessage, 'Busy') !== false) {
                $sipCode = Llamada::SIP_CODE_BUSY;
                $callStatus = Llamada::CALL_STATUS_BUSY;
                $failureReason = 'Línea ocupada';
            } elseif (strpos($errorMessage, '408') !== false || strpos($errorMessage, 'timeout') !== false) {
                $sipCode = Llamada::SIP_CODE_NO_ANSWER;
                $callStatus = Llamada::CALL_STATUS_NO_ANSWER;
                $failureReason = 'Sin respuesta';
            } elseif (strpos($errorMessage, '404') !== false || strpos($errorMessage, 'Not Found') !== false) {
                $sipCode = Llamada::SIP_CODE_NOT_FOUND;
                $callStatus = Llamada::CALL_STATUS_FAILED;
                $failureReason = 'Número no encontrado';
            }
            
            // Actualizar registro con error detallado
            $llamada->update([
                'status' => Llamada::STATUS_FINALIZADA,
                'call_status' => $callStatus,
                'sip_status_code' => $sipCode,
                'sip_status_message' => $sipCode ? Llamada::getSipStatusMessages()[$sipCode] ?? $errorMessage : null,
                'failure_reason' => $failureReason,
                'call_completed_at' => now(),
                'call_notes' => 'Error ElevenLabs: ' . $errorMessage,
                'internal_notes' => 'Call failed during API request: ' . $errorMessage,
                'call_metadata' => [
                    'error_time' => now()->toISOString(),
                    'error_type' => 'api_error',
                    'full_error_message' => $errorMessage
                ]
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'sip_code' => $sipCode
            ];
        }
    }

    /**
     * Actualizar estado de la llamada
     */
    private function updateCallStatus($callResponse, $agentResponse)
    {
        try {
            $updateData = [
                'updated_at' => now()
            ];

            // Actualizar según la decisión del agente
            if (isset($agentResponse['decision'])) {
                switch ($agentResponse['decision']) {
                    case 'ACCEPT':
                        $updateData['response_status'] = 'accepted';
                        $updateData['call_status'] = 'answered'; // Usar valor válido del ENUM
                        $updateData['notes'] = 'Conductor aceptó el trabajo - ' . ($agentResponse['reason'] ?? '');
                        break;
                        
                    case 'REJECT':
                        $updateData['response_status'] = 'rejected';
                        $updateData['call_status'] = 'answered'; // Usar valor válido del ENUM
                        $updateData['notes'] = 'Conductor rechazó el trabajo - ' . ($agentResponse['reason'] ?? '');
                        break;
                        
                    case 'UNCLEAR':
                        $updateData['response_status'] = 'pending';
                        $updateData['call_status'] = 'answered'; // Usar valor válido del ENUM
                        $updateData['notes'] = 'Respuesta no clara - requiere aclaración';
                        break;
                }
            }

            // Agregar información adicional
            if (isset($agentResponse['confidence'])) {
                $updateData['notes'] = ($updateData['notes'] ?? '') . " (Confianza: {$agentResponse['confidence']})";
            }

            $callResponse->update($updateData);

            Log::info("Estado de llamada actualizado", [
                'call_response_id' => $callResponse->id,
                'new_status' => $updateData['call_status'] ?? 'sin cambio',
                'response_status' => $updateData['response_status'] ?? 'sin cambio'
            ]);

        } catch (\Exception $e) {
            Log::error("Error actualizando estado de llamada: " . $e->getMessage());
        }
    }

    /**
     * Realizar una llamada directa a cualquier número de teléfono
     * Endpoint para hacer llamadas conversacionales a números específicos
     */
    public function makeDirectCall(Request $request)
    {
        try {
            $phoneNumber = $request->input('phone_number');
            $message = $request->input('message', 'Hola, soy Andrea, tu asistente virtual de ConAlca. Te llamo para saludarte y probar nuestro nuevo sistema de conversación inteligente. ¿Cómo estás hoy?');

            if (!$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Número de teléfono requerido'
                ], 400);
            }

            // Formatear número para Twilio (agregar código de país para Colombia si no lo tiene)
            if (!str_starts_with($phoneNumber, '+')) {
                // Solo agregar +57 si el número no tiene código de país
                $phoneNumber = '+57' . ltrim($phoneNumber, '0'); // Colombia +57
            }
            // Si ya tiene + pero no es +57, lo dejamos como está (números internacionales)

            $twilioSid = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $twilioNumber = config('services.twilio.phone_number');

            if (!$twilioSid || !$twilioToken || !$twilioNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración de Twilio incompleta'
                ], 500);
            }

            $client = new \Twilio\Rest\Client($twilioSid, $twilioToken);
            
            // URL del webhook que manejará la llamada directa
            $webhookUrl = route('agent.initial-call') . '?' . http_build_query([
                'direct_call' => true,
                'phone_number' => $phoneNumber,
                'initial_message' => $message
            ]);

            Log::info('Creando llamada directa con Twilio', [
                'to' => $phoneNumber,
                'from' => $twilioNumber,
                'webhook_url' => $webhookUrl,
                'message' => $message
            ]);

            // Realizar la llamada
            $call = $client->calls->create(
                $phoneNumber, // To
                $twilioNumber, // From
                [
                    'url' => $webhookUrl,
                    'method' => 'POST',
                    'timeout' => 60,
                    'record' => true,
                    'recordingChannels' => 'dual',
                    'recordingStatusCallback' => route('twilio.webhook.recording'),
                    'statusCallback' => route('twilio.webhook.status'),
                    'statusCallbackMethod' => 'POST'
                ]
            );

            Log::info('Llamada directa creada exitosamente', [
                'call_sid' => $call->sid,
                'to' => $phoneNumber,
                'status' => $call->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Llamada directa iniciada exitosamente',
                'call_sid' => $call->sid,
                'phone_number' => $phoneNumber,
                'status' => $call->status,
                'initial_message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error en llamada directa: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la llamada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar llamadas conversacionales para todas las cotizaciones de un grupo (Asíncrono)
     */
    public function initiateGroupConversationalCall(Request $request)
    {
        try {
            $groupCotizationId = $request->input('group_cotization_id');
            $singleCotizationId = $request->input('cotizacion_model_id');

            // Si se proporciona un ID individual, usarlo como un "grupo de uno"
            if ($singleCotizationId && !$groupCotizationId) {
                Log::info('Procesando cotización individual como grupo', [
                    'cotizacion_model_id' => $singleCotizationId,
                    'request_data' => $request->all()
                ]);
                
                // Buscar la cotización individual
                $cotizacion = CotizacionModel::find($singleCotizationId);
                
                if (!$cotizacion) {
                    return response()->json([
                        'error' => 'Cotización no encontrada: ' . $singleCotizationId,
                        'cotizacion_model_id' => $singleCotizationId
                    ], 404);
                }
                
                $cotizaciones = collect([$cotizacion]);
            } elseif ($groupCotizationId) {
                Log::info('Iniciando llamadas asíncronas para grupo', [
                    'group_cotization_id' => $groupCotizationId,
                    'request_data' => $request->all()
                ]);

                // Buscar todas las cotizaciones ACEPTADAS del grupo
                $cotizaciones = CotizacionModel::where('group_cotization_id', $groupCotizationId)
                    ->where('decision_cliente', 'aceptada')
                    ->get();
                
                if ($cotizaciones->isEmpty()) {
                    return response()->json([
                        'error' => 'No se encontraron cotizaciones para el grupo: ' . $groupCotizationId,
                        'group_cotization_id' => $groupCotizationId
                    ], 404);
                }
            } else {
                return response()->json(['error' => 'ID de grupo de cotización o ID de cotización individual requerido'], 400);
            }

            Log::info('Cotizaciones encontradas', [
                'total_cotizaciones' => $cotizaciones->count(),
                'cotizacion_ids' => $cotizaciones->pluck('id')->toArray(),
                'source' => $singleCotizationId ? 'individual' : 'grupo'
            ]);

            $callsScheduled = 0;
            $errors = [];
            
            // Procesar cada cotización de forma asíncrona
            foreach ($cotizaciones as $cotizacion) {
                try {
                    Log::info('Procesando cotización del grupo', [
                        'cotizacion_id' => $cotizacion->id,
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                        'origen' => $cotizacion->ciudad_origen,
                        'destino' => $cotizacion->ciudad_destino
                    ]);

                    // Verificar que la cotización no esté rechazada
                    if ($cotizacion->decision_cliente === 'rechazada') {
                        Log::warning('Cotización rechazada - saltando', [
                            'cotizacion_id' => $cotizacion->id
                        ]);
                        continue;
                    }

                    // Validar ciudad de origen
                    if (!$cotizacion->ciudad_origen) {
                        Log::warning('Cotización sin ciudad de origen - saltando', [
                            'cotizacion_id' => $cotizacion->id
                        ]);
                        continue;
                    }

                    // Buscar conductores desde la base de datos (ya fueron filtrados y guardados)
                    Log::info('Buscando conductores en BD para llamadas', [
                        'cotizacion_id' => $cotizacion->id
                    ]);
                    
                    $conductoresDB = LlamadaConductor::where('cotizacion_id', $cotizacion->id)
                        ->where('estado_llamada', 'pendiente')
                        ->where('disponible', true)
                        ->orderBy('score', 'desc')
                        ->limit(10)
                        ->get();
                    
                    // Si no hay conductores en BD, intentar buscar en Arcángel como respaldo
                    if ($conductoresDB->isEmpty()) {
                        Log::warning('No hay conductores en BD, buscando en Arcángel como respaldo', [
                            'cotizacion_id' => $cotizacion->id
                        ]);
                        
                        $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
                        $pesoCarga = 0;
                        if ($cotizacion->peso_mercancia) {
                            $pesoCarga = floatval(str_replace([',', ' kg', ' KG'], '', $cotizacion->peso_mercancia));
                        }
                        
                        $variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido, $pesoCarga);
                        
                        $resultado = $this->arcangelService->getVehiculosFiltrados(
                            ciudad: $ciudadOrigen,
                            clases: $variantesVehiculo,
                            minScore: 0,
                            limit: 10,
                            useCache: true,
                            cacheTTL: 15
                        );
                        
                        $vehiculos = $resultado['vehiculos'] ?? [];
                        
                        if (empty($vehiculos)) {
                            Log::warning('No se encontraron conductores ni en BD ni en Arcángel', [
                                'cotizacion_id' => $cotizacion->id
                            ]);
                            continue;
                        }
                    } else {
                        // Convertir los conductores de BD a formato compatible
                        $vehiculos = $conductoresDB->map(function($conductor) {
                            return [
                                'placa' => $conductor->placa,
                                'conductor' => $conductor->nombre_conductor,
                                'telefono' => $conductor->telefono,
                                'clase' => $conductor->tipo_vehiculo ?? $conductor->clase_vehiculo,
                                'carroceria' => $conductor->carroceria,
                                'capacidad' => $conductor->capacidad,
                                'score' => $conductor->score,
                                'disponible' => $conductor->disponible,
                                'id_bd' => $conductor->id,
                                'identificador' => $conductor->identificador_unico
                            ];
                        })->toArray();
                        
                        Log::info('Conductores obtenidos desde BD', [
                            'cotizacion_id' => $cotizacion->id,
                            'total' => count($vehiculos)
                        ]);
                    }

                    // Sistema de lotes: máximo 3 llamadas concurrentes
                    $maxConcurrentCalls = 3;
                    $totalVehiculos = count($vehiculos);
                    $batchCount = ceil($totalVehiculos / $maxConcurrentCalls);
                    
                    Log::info('Configurando sistema de lotes para cotización', [
                        'cotizacion_id' => $cotizacion->id,
                        'total_vehiculos' => $totalVehiculos,
                        'max_concurrent' => $maxConcurrentCalls,
                        'total_batches' => $batchCount
                    ]);

                    // Crear registros de llamadas organizadas por lotes
                    foreach ($vehiculos as $index => $vehiculo) {
                        $phoneNumber = $vehiculo['telefono'] ?? 'N/A';
                        $formattedPhone = $this->formatPhoneNumber($phoneNumber);

                        if (!$formattedPhone) {
                            Log::warning('Número de teléfono inválido para Arcángel', [
                                'conductor' => $vehiculo['conductor'] ?? 'N/A',
                                'placa' => $vehiculo['placa'] ?? 'N/A',
                                'original_phone' => $phoneNumber
                            ]);
                            continue;
                        }

                        // Preparar datos de cotización
                        $cotizacionData = [
                            'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
                            'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
                            'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                            'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
                            'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
                            'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
                        ];

                        // Crear o actualizar registro del conductor CON información de cotización
                        $conductor = \App\Models\LlamadaConductor::createFromArcangel(
                            $vehiculo,
                            $ciudadOrigen,
                            $cotizacion->id,
                            $cotizacion->group_cotization_id,
                            $cotizacionData
                        );

                        // Calcular número de lote (1, 2, 3, etc.)
                        $batchNumber = floor($index / $maxConcurrentCalls) + 1;
                        $batchPosition = ($index % $maxConcurrentCalls) + 1;

                        // Crear registro de llamada con información de lote
                        $llamada = \App\Models\Llamada::create([
                            'id_cotizacion' => $cotizacion->id,
                            'conductor_id' => $conductor->id,
                            'numero_destino' => $formattedPhone,
                            'status' => \App\Models\Llamada::STATUS_PENDIENTE,
                            'queue_status' => 'pending',
                            'batch_number' => $batchNumber,
                            'batch_position' => $batchPosition,
                            'call_started_at' => now(),
                            'call_notes' => "Llamada programada en lote {$batchNumber}, posición {$batchPosition} - Conductor Arcángel"
                        ]);

                        $callsScheduled++;
                        
                        Log::info('Llamada registrada en sistema de lotes desde Arcángel', [
                            'llamada_id' => $llamada->id_llamada,
                            'conductor_id' => $conductor->id,
                            'conductor' => $conductor->nombre_conductor,
                            'placa' => $conductor->placa,
                            'phone' => $formattedPhone,
                            'batch_number' => $batchNumber,
                            'batch_position' => $batchPosition
                        ]);
                    }

                    // Iniciar el procesamiento del primer lote
                    if ($callsScheduled > 0) {
                        ProcessBatchElevenLabsCalls::dispatch($cotizacion->id, 1, $maxConcurrentCalls, 150)
                            ->delay(now()->addSeconds(5)); // Pequeño delay inicial
                        
                        Log::info('Sistema de lotes iniciado para cotización desde Arcángel', [
                            'cotizacion_id' => $cotizacion->id,
                            'total_batches' => $batchCount,
                            'calls_per_batch' => $maxConcurrentCalls,
                            'delay_between_batches' => 150
                        ]);
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'cotizacion_id' => $cotizacion->id,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Error procesando cotización', [
                        'cotizacion_id' => $cotizacion->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Llamadas programadas exitosamente para ejecución asíncrona',
                'group_cotization_id' => $groupCotizationId,
                'total_cotizaciones' => $cotizaciones->count(),
                'calls_scheduled' => $callsScheduled,
                'estimated_completion_time' => now()->addSeconds($callsScheduled * 10)->toISOString(),
                'errors' => $errors,
                'summary' => [
                    'total_drivers_called' => $callsScheduled,
                    'successful_cotizaciones' => $cotizaciones->count() - count($errors),
                    'failed_cotizaciones' => count($errors),
                    'execution_mode' => 'asynchronous'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error iniciando llamadas asíncronas para grupo: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar llamadas para una cotización en la tabla llamadas (sin hacer llamadas reales)
     */
    private function registerCallsForCotization($cotizacion)
    {
        try {
            // Verificar que la cotización no esté rechazada
            if ($cotizacion->decision_cliente === 'rechazada') {
                Log::warning('Cotización rechazada - no se registrarán llamadas', [
                    'cotizacion_id' => $cotizacion->id,
                    'decision_cliente' => $cotizacion->decision_cliente
                ]);
                
                return [
                    'success' => false,
                    'cotizacion_id' => $cotizacion->id,
                    'error' => 'No se pueden registrar llamadas para cotizaciones rechazadas',
                    'decision_cliente' => $cotizacion->decision_cliente,
                    'total_calls' => 0
                ];
            }

            // Implementar búsqueda de conductores usando ArcangelService
            Log::info('Buscando conductores para registrar llamadas con ArcangelService', [
                'cotizacion_id' => $cotizacion->id,
                'ciudad_origen' => $cotizacion->ciudad_origen,
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                'decision_cliente' => $cotizacion->decision_cliente
            ]);

            if (!$cotizacion->ciudad_origen) {
                return [
                    'success' => false,
                    'cotizacion_id' => $cotizacion->id,
                    'error' => 'La cotización no tiene ciudad de origen definida',
                    'total_calls' => 0
                ];
            }

            $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
            $variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido);
            
            // Buscar en Arcángel API
            $resultado = $this->arcangelService->getVehiculosFiltrados(
                ciudad: $ciudadOrigen,
                clases: $variantesVehiculo,
                minScore: 0,
                limit: 5,
                useCache: true,
                cacheTTL: 15
            );
            
            $vehiculos = $resultado['vehiculos'] ?? [];

            Log::info('Conductores encontrados en Arcángel para registro', [
                'total' => count($vehiculos),
                'ciudad' => $ciudadOrigen,
                'variantes' => $variantesVehiculo
            ]);

            if (empty($vehiculos)) {
                return [
                    'success' => false,
                    'cotizacion_id' => $cotizacion->id,
                    'error' => 'No se encontraron conductores disponibles en Arcángel',
                    'total_calls' => 0
                ];
            }

            $registeredCalls = [];
            
            // Sistema de lotes: máximo 3 llamadas concurrentes
            $maxConcurrentCalls = 3;
            $totalVehiculos = count($vehiculos);
            $batchCount = ceil($totalVehiculos / $maxConcurrentCalls);
            
            Log::info('Configurando sistema de lotes para registro y llamadas', [
                'cotizacion_id' => $cotizacion->id,
                'total_vehiculos' => $totalVehiculos,
                'max_concurrent' => $maxConcurrentCalls,
                'total_batches' => $batchCount
            ]);
            
            // Registrar llamadas para cada conductor de Arcángel
            foreach ($vehiculos as $index => $vehiculo) {
                try {
                    // Obtener y formatear el teléfono
                    $phoneNumber = $vehiculo['telefono'] ?? 'N/A';
                    $formattedPhone = $this->formatPhoneNumber($phoneNumber);

                    if (!$formattedPhone) {
                        Log::warning('Número de teléfono inválido para conductor de Arcángel', [
                            'conductor' => $vehiculo['conductor'] ?? 'N/A',
                            'placa' => $vehiculo['placa'] ?? 'N/A',
                            'original_phone' => $phoneNumber
                        ]);
                        continue; // Saltar a siguiente conductor
                    }

                    // Preparar datos de cotización con valores por defecto para campos NULL
                    $cotizacionData = [
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
                        'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
                        'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
                        'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
                        'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
                        'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
                    ];

                    // LOG: Datos de cotización antes de crear conductor
                    Log::info('🔍 Datos de cotización para createFromArcangel', array_merge(
                        ['cotizacion_id' => $cotizacion->id, 'group_cotization_id' => $cotizacion->group_cotization_id],
                        $cotizacionData
                    ));

                    // Crear o actualizar registro del conductor CON información completa de la orden
                    $conductor = \App\Models\LlamadaConductor::createFromArcangel(
                        $vehiculo, 
                        $ciudadOrigen,
                        $cotizacion->id,
                        $cotizacion->group_cotization_id,
                        $cotizacionData
                    );

                    // LOG: Verificar que los datos se guardaron
                    Log::info('✅ Conductor creado con datos', [
                        'conductor_id' => $conductor->id,
                        'cotizacion_id_guardado' => $conductor->cotizacion_id,
                        'group_cotization_id_guardado' => $conductor->group_cotization_id,
                        'ciudad_origen_guardado' => $conductor->ciudad_origen,
                        'ciudad_destino_guardado' => $conductor->ciudad_destino,
                        'mercancia_guardado' => $conductor->mercancia,
                        'peso_carga_guardado' => $conductor->peso_carga,
                        'empaque_guardado' => $conductor->empaque,
                    ]);

                    // Calcular número de lote (1, 2, 3, etc.)
                    $batchNumber = floor($index / $maxConcurrentCalls) + 1;
                    $batchPosition = ($index % $maxConcurrentCalls) + 1;

                    Log::info('Registrando llamada para conductor', [
                        'conductor_id' => $conductor->id,
                        'driver_name' => $conductor->nombre_conductor,
                        'phone' => $formattedPhone,
                        'placa' => $conductor->placa,
                        'cotizacion_id' => $cotizacion->id,
                        'group_cotization_id' => $cotizacion->group_cotization_id ?? null,
                        'batch_number' => $batchNumber,
                        'batch_position' => $batchPosition
                    ]);

                    // Crear registro en la tabla llamadas CON información de lote
                    $llamada = \App\Models\Llamada::create([
                        'id_cotizacion' => $cotizacion->id,
                        'conductor_id' => $conductor->id,
                        'numero_destino' => $formattedPhone,
                        'status' => \App\Models\Llamada::STATUS_PENDIENTE,
                        'queue_status' => 'pending',
                        'batch_number' => $batchNumber,
                        'batch_position' => $batchPosition,
                        'queued_at' => now(),
                        'call_notes' => "Llamada en lote {$batchNumber}, posición {$batchPosition}"
                    ]);

                    $registeredCalls[] = [
                        'llamada_id' => $llamada->id_llamada,
                        'conductor_id' => $conductor->id,
                        'driver_name' => $conductor->nombre_conductor,
                        'phone' => $formattedPhone,
                        'status' => 'registrado',
                        'vehicle_type' => $conductor->clase_vehiculo,
                        'vehicle_plate' => $conductor->placa,
                        'batch_number' => $batchNumber,
                        'batch_position' => $batchPosition
                    ];
                    
                } catch (\Exception $e) {
                    Log::error("Error registrando llamada para conductor: " . $e->getMessage(), [
                        'vehiculo' => $vehiculo,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $registeredCalls[] = [
                        'conductor_id' => null,
                        'driver_name' => $vehiculo['conductor'] ?? 'N/A',
                        'phone' => $errorPhone,
                        'error' => $e->getMessage(),
                        'status' => 'error'
                    ];
                }
            }

            Log::info('Llamadas registradas para cotización', [
                'cotizacion_id' => $cotizacion->id,
                'total_drivers' => count($drivers),
                'calls_registered' => count($registeredCalls)
            ]);

            // ======================================
            // EJECUTAR LAS LLAMADAS DESPUÉS DEL REGISTRO
            // ======================================
            
            if (!empty($registeredCalls)) {
                Log::info('Iniciando ejecución de llamadas para cotización', [
                    'cotizacion_id' => $cotizacion->id,
                    'total_calls_to_execute' => count($registeredCalls),
                    'batch_count' => $batchCount
                ]);

                // Programar las llamadas comenzando por el primer lote
                // El job se auto-encadenará para procesar los siguientes lotes
                \App\Jobs\ProcessBatchElevenLabsCalls::dispatch(
                    $cotizacion->id,
                    1, // Comenzar con batch número 1
                    $maxConcurrentCalls,
                    150 // Delay de 150 segundos entre lotes (2.5 minutos)
                )->onQueue('calls');

                Log::info('Job de llamadas despachado', [
                    'cotizacion_id' => $cotizacion->id,
                    'job' => 'ProcessBatchElevenLabsCalls',
                    'queue' => 'calls',
                    'starting_batch' => 1,
                    'total_batches' => $batchCount
                ]);
            }

            return [
                'success' => true,
                'cotizacion_id' => $cotizacion->id,
                'cotizacion_details' => [
                    'origen' => $cotizacion->ciudad_origen,
                    'destino' => $cotizacion->ciudad_destino,
                    'vehiculo' => $cotizacion->vehiculo_requerido,
                    'valor' => $cotizacion->valor_declarado
                ],
                'drivers_registered' => $registeredCalls,
                'total_calls' => count($registeredCalls),
                'calls_dispatched' => !empty($registeredCalls),
                'batch_system' => [
                    'enabled' => true,
                    'total_batches' => $batchCount ?? 0,
                    'max_concurrent' => $maxConcurrentCalls
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error registrando llamadas para cotización {$cotizacion->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'cotizacion_id' => $cotizacion->id,
                'error' => 'Error interno del servidor: ' . $e->getMessage(),
                'total_calls' => 0
            ];
        }
    }

    /**
     * Procesar una cotización individual (extraído del método principal)
     */
    private function processSingleCotization($cotizacion)
    {
        try {
            // Implementar nuevo flujo de búsqueda usando vehicle_class como intermediaria
            Log::info('Iniciando nuevo flujo de búsqueda de conductores', [
                'cotizacion_id' => $cotizacion->id,
                'vehiculo_requerido' => $cotizacion->vehiculo_requerido
            ]);

            // Paso 1: Buscar en vehicle_class por Nombre usando variantes
            $variantes = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido);
            $vehicleClass = \App\Models\VehicleClass::whereIn('Nombre', $variantes)->first();
            
            if (!$vehicleClass) {
                Log::warning('No se encontró vehicle_class para vehiculo_requerido', [
                    'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                    'variantes_probadas' => $variantes
                ]);
                
                return [
                    'success' => false,
                    'cotizacion_id' => $cotizacion->id,
                    'error' => 'No se encontró clase de vehículo para: ' . $cotizacion->vehiculo_requerido,
                    'total_calls' => 0
                ];
            }

            Log::info('Clase de vehículo encontrada', [
                'vehicle_class_codigo' => $vehicleClass->Codigo,
                'vehicle_class_nombre' => $vehicleClass->Nombre,
                'variantes_usadas' => $variantes
            ]);

            // Paso 2: Usar el campo Nombre de vehicle_class para buscar en vehicle_owner_holder_driver
            // Sin filtrar por estado, solo por tipo de vehículo y teléfono válido
            $drivers = \App\Models\VehicleOwnerHolderDriver::where('Clasevehiculo', $vehicleClass->Nombre)
                ->whereNotNull('Telefonoconductor')
                ->where('Telefonoconductor', '!=', '')
                ->limit(5)
                ->get();

            Log::info('Conductores encontrados con nuevo flujo', [
                'clase_vehiculo_buscada' => $vehicleClass->Nombre,
                'conductores_encontrados' => $drivers->count()
            ]);

            if ($drivers->isEmpty()) {
                return [
                    'success' => false,
                    'cotizacion_id' => $cotizacion->id,
                    'error' => 'No se encontraron conductores disponibles para esta cotización',
                    'total_calls' => 0
                ];
            }

            $callResults = [];
            
            // Realizar llamadas a conductores usando el sistema conversacional
            foreach ($drivers as $driver) {
                try {
                    // Obtener el primer teléfono válido y limpiarlo
                    $phoneNumber = $driver->Telefonoconductor ?? $driver->Telefonopropietario ?? $driver->Telefonoposeedor ?? 'N/A';
                    $firstPhone = explode(' - ', $phoneNumber)[0]; // Tomar solo el primer número
                    $firstPhone = explode('-', $firstPhone)[0]; // Remover guiones adicionales
                    $firstPhone = preg_replace('/[^0-9]/', '', $firstPhone); // Solo números
                    $firstPhone = substr($firstPhone, 0, 10); // Máximo 10 dígitos para Colombia

                    Log::info('Llamando a conductor', [
                        'driver_id' => $driver->id,
                        'driver_name' => $vehiculo['conductor'] ?? 'N/A',
                        'phone' => $firstPhone,
                        'cotizacion_id' => $cotizacion->id
                    ]);

                    // Crear registro de llamada en tabla llamadas
                    $llamada = Llamada::create([
                        'id_cotizacion' => $cotizacion->id,
                        'chofer_id' => $driver->id,
                        'numero_destino' => $firstPhone,
                        'status' => Llamada::STATUS_PENDIENTE,
                        'call_started_at' => now()
                    ]);

                    // Realizar llamada usando ElevenLabs
                    $callResult = $this->makeConversationalCall($driver, $cotizacion, $llamada);
                    
                    $callResults[] = [
                        'driver_id' => $driver->id,
                        'driver_name' => $vehiculo['conductor'] ?? 'N/A',
                        'phone' => $firstPhone,
                        'call_result' => $callResult,
                        'status' => $callResult['status'] ?? 'unknown'
                    ];

                    // Esperar un poco entre llamadas para no saturar
                    // sleep(2);
                    
                } catch (\Exception $e) {
                    Log::error("Error llamando a conductor {$driver->id}: " . $e->getMessage());
                    
                    $errorFirstPhone = explode(' - ', ($driver->Telefonoconductor ?? $driver->Telefonopropietario ?? $driver->Telefonoposeedor ?? 'N/A'))[0];
                    $errorFirstPhone = explode('-', $errorFirstPhone)[0];
                    $errorFirstPhone = preg_replace('/[^0-9]/', '', $errorFirstPhone);
                    $errorFirstPhone = substr($errorFirstPhone, 0, 10);
                    
                    $callResults[] = [
                        'driver_id' => $driver->id,
                        'driver_name' => $vehiculo['conductor'] ?? 'N/A',
                        'phone' => $errorFirstPhone,
                        'error' => $e->getMessage(),
                        'status' => 'error'
                    ];
                }
            }

            Log::info('Llamadas conversacionales completadas para cotización', [
                'cotizacion_id' => $cotizacion->id,
                'total_drivers' => count($drivers),
                'calls_made' => count($callResults)
            ]);

            return [
                'success' => true,
                'cotizacion_id' => $cotizacion->id,
                'cotizacion_details' => [
                    'origen' => $cotizacion->ciudad_origen,
                    'destino' => $cotizacion->ciudad_destino,
                    'vehiculo' => $cotizacion->vehiculo_requerido,
                    'valor' => $cotizacion->valor_declarado
                ],
                'drivers_called' => $callResults,
                'total_calls' => count($callResults)
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando cotización {$cotizacion->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'cotizacion_id' => $cotizacion->id,
                'error' => 'Error interno del servidor: ' . $e->getMessage(),
                'total_calls' => 0
            ];
        }
    }

    /**
     * Obtener las llamadas registradas para una cotización
     */
    public function getLlamadasPorCotizacion(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            $groupCotizationId = $request->input('group_cotization_id');

            if (!$cotizacionId && !$groupCotizationId) {
                return response()->json(['error' => 'ID de cotización o grupo de cotización requerido'], 400);
            }

            $query = Llamada::with(['cotizacion', 'chofer']);

            if ($cotizacionId) {
                $query->where('id_cotizacion', $cotizacionId);
            } elseif ($groupCotizationId) {
                // Obtener todas las cotizaciones del grupo
                $cotizacionIds = CotizacionModel::where('group_cotization_id', $groupCotizationId)->pluck('id');
                $query->whereIn('id_cotizacion', $cotizacionIds);
            }

            $llamadas = $query->orderBy('created_at', 'desc')->get();

            $result = $llamadas->map(function ($llamada) {
                return [
                    'id_llamada' => $llamada->id_llamada,
                    'id_cotizacion' => $llamada->id_cotizacion,
                    'cotizacion' => [
                        'origen' => $llamada->cotizacion->ciudad_origen ?? 'N/A',
                        'destino' => $llamada->cotizacion->ciudad_destino ?? 'N/A',
                        'vehiculo' => $llamada->cotizacion->vehiculo_requerido ?? 'N/A',
                        'valor' => $llamada->cotizacion->valor_declarado ?? 'N/A'
                    ],
                    'chofer' => [
                        'id' => $llamada->chofer_id,
                        'nombre' => $llamada->chofer->Conductor ?? 'N/A',
                        'telefono' => $llamada->chofer->Telefonoconductor ?? 'N/A',
                        'placa' => $llamada->chofer->Placa ?? 'N/A',
                        'clase_vehiculo' => $llamada->chofer->Clasevehiculo ?? 'N/A'
                    ],
                    'status' => $llamada->status,
                    'status_label' => Llamada::getStatusOptions()[$llamada->status] ?? $llamada->status,
                    'created_at' => $llamada->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $llamada->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'llamadas' => $result,
                'total' => $result->count(),
                'statistics' => [
                    'pendientes' => $llamadas->where('status', Llamada::STATUS_PENDIENTE)->count(),
                    'en_curso' => $llamadas->where('status', Llamada::STATUS_EN_CURSO)->count(),
                    'finalizadas' => $llamadas->where('status', Llamada::STATUS_FINALIZADA)->count(),
                    'aceptadas' => $llamadas->where('status', Llamada::STATUS_ACEPTADA)->count(),
                    'rechazadas' => $llamadas->where('status', Llamada::STATUS_RECHAZADA)->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo llamadas: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado de una llamada
     */
    public function updateLlamadaStatus(Request $request)
    {
        try {
            $llamadaId = $request->input('llamada_id');
            $status = $request->input('status');

            if (!$llamadaId || !$status) {
                return response()->json(['error' => 'ID de llamada y status requeridos'], 400);
            }

            $validStatuses = array_keys(Llamada::getStatusOptions());
            if (!in_array($status, $validStatuses)) {
                return response()->json(['error' => 'Status inválido'], 400);
            }

            $llamada = Llamada::find($llamadaId);
            if (!$llamada) {
                return response()->json(['error' => 'Llamada no encontrada'], 404);
            }

            $llamada->status = $status;
            $llamada->save();

            Log::info('Estado de llamada actualizado', [
                'llamada_id' => $llamadaId,
                'old_status' => $llamada->getOriginal('status'),
                'new_status' => $status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'llamada' => [
                    'id_llamada' => $llamada->id_llamada,
                    'status' => $llamada->status,
                    'status_label' => Llamada::getStatusOptions()[$llamada->status]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error actualizando estado de llamada: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar llamadas reales usando ElevenLabs para una cotización específica
     */
    public function startElevenLabsCalls($cotizacionId)
    {
        try {
            Log::info('Iniciando llamadas ElevenLabs para cotización', [
                'cotizacion_id' => $cotizacionId
            ]);

            // Verificar que la cotización existe
            $cotizacion = CotizacionModel::find($cotizacionId);
            if (!$cotizacion) {
                return response()->json([
                    'error' => 'Cotización no encontrada'
                ], 404);
            }

            // Verificar que la cotización no esté rechazada
            if ($cotizacion->decision_cliente === 'rechazada') {
                return response()->json([
                    'error' => 'No se pueden iniciar llamadas para cotizaciones rechazadas',
                    'decision_cliente' => $cotizacion->decision_cliente
                ], 400);
            }

            // Buscar llamadas pendientes para esta cotización
            $llamadas = \App\Models\Llamada::where('id_cotizacion', $cotizacionId)
                ->where('status', \App\Models\Llamada::STATUS_PENDIENTE)
                ->get();

            if ($llamadas->isEmpty()) {
                Log::info('No hay llamadas pendientes para esta cotización', [
                    'cotizacion_id' => $cotizacionId
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'No hay llamadas pendientes para esta cotización',
                    'cotizacion_id' => $cotizacionId,
                    'llamadas_programadas' => 0,
                    'conductores' => []
                ], 200);
            }

            // Procesar llamadas de forma asíncrona para evitar timeouts
            $maxConcurrentCalls = 3;
            $totalLlamadas = $llamadas->count();
            $batchCount = ceil($totalLlamadas / $maxConcurrentCalls);
            $callsScheduled = 0;
            $drivers = [];

            Log::info('Configurando sistema de lotes para llamadas existentes', [
                'cotizacion_id' => $cotizacionId,
                'total_llamadas' => $totalLlamadas,
                'max_concurrent' => $maxConcurrentCalls,
                'total_batches' => $batchCount
            ]);

            // Organizar llamadas en lotes y actualizar sus registros
            foreach ($llamadas as $index => $llamada) {
                // Buscar información del conductor (priorizar nueva tabla)
                $conductor = null;
                $driverName = 'N/A';
                
                if ($llamada->conductor_id) {
                    $conductor = \App\Models\LlamadaConductor::find($llamada->conductor_id);
                    if ($conductor) {
                        $driverName = $conductor->nombre_conductor;
                    }
                }
                
                // Fallback a tabla vieja si no hay conductor_id
                if (!$conductor && $llamada->chofer_id) {
                    $driver = \App\Models\VehicleOwnerHolderDriver::find($llamada->chofer_id);
                    if ($driver) {
                        $driverName = $driver->Conductor ?? 'N/A';
                    }
                }
                
                // Si no se encuentra conductor en ninguna tabla, saltar
                if (!$conductor && !isset($driver)) {
                    Log::warning('Conductor no encontrado para llamada', [
                        'llamada_id' => $llamada->id_llamada,
                        'conductor_id' => $llamada->conductor_id,
                        'chofer_id' => $llamada->chofer_id
                    ]);
                    continue;
                }

                // Calcular número de lote (1, 2, 3, etc.)
                $batchNumber = floor($index / $maxConcurrentCalls) + 1;
                $batchPosition = ($index % $maxConcurrentCalls) + 1;

                // Actualizar estado de llamada con información de lote
                $llamada->update([
                    'queue_status' => 'pending',
                    'batch_number' => $batchNumber,
                    'batch_position' => $batchPosition,
                    'queued_at' => now(),
                    'call_notes' => "Llamada en lote {$batchNumber}, posición {$batchPosition}"
                ]);

                $callsScheduled++;
                $drivers[] = [
                    'driver_id' => $conductor ? $conductor->id : ($driver->id ?? null),
                    'nombre' => $driverName,
                    'telefono' => $llamada->numero_destino,
                    'batch_number' => $batchNumber,
                    'batch_position' => $batchPosition
                ];
                
                Log::info('Llamada organizada en lote', [
                    'llamada_id' => $llamada->id_llamada,
                    'conductor_id' => $llamada->conductor_id,
                    'driver_id' => $conductor ? $conductor->id : ($driver->id ?? null),
                    'driver_name' => $driverName,
                    'batch_number' => $batchNumber,
                    'batch_position' => $batchPosition
                ]);
            }

            // Iniciar el procesamiento del primer lote si hay llamadas
            if ($callsScheduled > 0) {
                ProcessBatchElevenLabsCalls::dispatch($cotizacionId, 1, $maxConcurrentCalls, 150)
                    ->delay(now()->addSeconds(5)); // Pequeño delay inicial
                
                Log::info('Sistema de lotes iniciado para cotización existente', [
                    'cotizacion_id' => $cotizacionId,
                    'total_batches' => $batchCount,
                    'calls_per_batch' => $maxConcurrentCalls,
                    'delay_between_batches' => 150
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Llamadas organizadas en lotes y programadas exitosamente',
                'cotizacion_id' => $cotizacionId,
                'calls_scheduled' => $callsScheduled,
                'total_batches' => $batchCount,
                'calls_per_batch' => $maxConcurrentCalls,
                'delay_between_batches_seconds' => 150,
                'estimated_completion_time' => now()->addSeconds($batchCount * 150),
                'drivers' => $drivers,
                'execution_mode' => 'batch_asynchronous'
            ]);

        } catch (\Exception $e) {
            Log::error("Error iniciando llamadas ElevenLabs: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacionId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear número de teléfono para almacenamiento (igual que en ElevenLabsCallService)
     */
    private function formatPhoneNumberForStorage($phoneNumber)
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
     * Webhook para recibir actualizaciones de estado de ElevenLabs
     */
    public function elevenLabsWebhook(Request $request)
    {
        try {
            Log::info('Webhook de ElevenLabs recibido', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $eventType = $request->input('event_type');
            $conversationId = $request->input('conversation_id');
            $sipCallId = $request->input('sip_call_id');
            $status = $request->input('status');
            $sipCode = $request->input('sip_status_code');
            $eventData = $request->input('data', []);

            // Buscar la llamada por conversation_id o sip_call_id
            $llamada = Llamada::where('elevenlabs_conversation_id', $conversationId)
                ->orWhere('elevenlabs_sip_call_id', $sipCallId)
                ->first();

            if (!$llamada) {
                Log::warning('Llamada no encontrada para webhook', [
                    'conversation_id' => $conversationId,
                    'sip_call_id' => $sipCallId
                ]);
                return response()->json(['status' => 'not_found'], 404);
            }

            // Actualizar estado según el tipo de evento
            $updateData = [
                'call_metadata' => array_merge($llamada->call_metadata ?? [], [
                    'webhook_received' => now()->toISOString(),
                    'event_type' => $eventType,
                    'webhook_data' => $eventData
                ])
            ];

            switch ($eventType) {
                case 'call_initiated':
                case 'call_started':
                    $updateData['call_status'] = Llamada::CALL_STATUS_INITIATED;
                    $updateData['call_initiated_at'] = now();
                    break;

                case 'call_ringing':
                    $updateData['call_status'] = Llamada::CALL_STATUS_RINGING;
                    $updateData['call_ringing_at'] = now();
                    if ($llamada->call_initiated_at) {
                        $updateData['ring_duration_seconds'] = now()->diffInSeconds($llamada->call_initiated_at);
                    }
                    break;

                case 'call_answered':
                case 'call_connected':
                    $updateData['call_status'] = Llamada::CALL_STATUS_ANSWERED;
                    $updateData['call_answered_at'] = now();
                    $updateData['status'] = Llamada::STATUS_EN_CURSO;
                    if ($llamada->call_ringing_at) {
                        $updateData['ring_duration_seconds'] = now()->diffInSeconds($llamada->call_ringing_at);
                    }
                    break;

                case 'call_ended':
                case 'call_completed':
                    $updateData['call_status'] = Llamada::CALL_STATUS_COMPLETED;
                    $updateData['call_completed_at'] = now();
                    $updateData['status'] = Llamada::STATUS_FINALIZADA;
                    if ($llamada->call_initiated_at) {
                        $updateData['call_duration_seconds'] = now()->diffInSeconds($llamada->call_initiated_at);
                    }
                    if ($llamada->call_answered_at) {
                        $updateData['talk_duration_seconds'] = now()->diffInSeconds($llamada->call_answered_at);
                    }
                    break;

                case 'call_failed':
                case 'call_error':
                    $updateData['call_status'] = Llamada::CALL_STATUS_FAILED;
                    $updateData['call_completed_at'] = now();
                    $updateData['status'] = Llamada::STATUS_FINALIZADA;
                    $updateData['failure_reason'] = $eventData['error_message'] ?? 'Error no especificado';
                    break;

                case 'call_busy':
                    $updateData['call_status'] = Llamada::CALL_STATUS_BUSY;
                    $updateData['call_completed_at'] = now();
                    $updateData['status'] = Llamada::STATUS_FINALIZADA;
                    $updateData['sip_status_code'] = Llamada::SIP_CODE_BUSY;
                    $updateData['sip_status_message'] = 'Busy Here - Línea ocupada';
                    $updateData['failure_reason'] = 'Línea ocupada';
                    break;

                case 'call_no_answer':
                    $updateData['call_status'] = Llamada::CALL_STATUS_NO_ANSWER;
                    $updateData['call_completed_at'] = now();
                    $updateData['status'] = Llamada::STATUS_FINALIZADA;
                    $updateData['sip_status_code'] = Llamada::SIP_CODE_NO_ANSWER;
                    $updateData['sip_status_message'] = 'Request Timeout - Sin respuesta';
                    $updateData['failure_reason'] = 'Sin respuesta';
                    break;
            }

            // Agregar código SIP si está disponible
            if ($sipCode) {
                $updateData['sip_status_code'] = $sipCode;
                $updateData['sip_status_message'] = Llamada::getSipStatusMessages()[$sipCode] ?? "SIP Code: $sipCode";
            }

            $llamada->update($updateData);

            Log::info('Estado de llamada actualizado via webhook', [
                'llamada_id' => $llamada->id_llamada,
                'event_type' => $eventType,
                'new_status' => $updateData['call_status'] ?? null
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de ElevenLabs: ' . $e->getMessage(), [
                'request_data' => $request->all()
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener estadísticas detalladas de llamadas
     */
    public function getCallStatistics(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            $startDate = $request->input('start_date', now()->subDays(7));
            $endDate = $request->input('end_date', now());

            $query = Llamada::query();

            if ($cotizacionId) {
                $query->where('id_cotizacion', $cotizacionId);
            }

            $query->whereBetween('created_at', [$startDate, $endDate]);

            $statistics = [
                'total_calls' => $query->count(),
                'by_status' => $query->groupBy('call_status')->selectRaw('call_status, count(*) as count')->get(),
                'by_sip_code' => $query->whereNotNull('sip_status_code')->groupBy('sip_status_code')->selectRaw('sip_status_code, count(*) as count')->get(),
                'average_duration' => $query->whereNotNull('call_duration_seconds')->avg('call_duration_seconds'),
                'average_ring_time' => $query->whereNotNull('ring_duration_seconds')->avg('ring_duration_seconds'),
                'success_rate' => $query->where('call_status', Llamada::CALL_STATUS_COMPLETED)->count() / max($query->count(), 1) * 100,
                'busy_rate' => $query->where('call_status', Llamada::CALL_STATUS_BUSY)->count() / max($query->count(), 1) * 100,
            ];

            return response()->json(['success' => true, 'statistics' => $statistics]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de llamadas: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Completar información faltante de llamadas existentes
     */
    public function backfillMissingCallData()
    {
        try {
            Log::info('Iniciando backfill de datos de llamadas faltantes');

            // Obtener llamadas que tienen conversation_id pero les falta información
            $llamadas = Llamada::whereNotNull('elevenlabs_conversation_id')
                ->where(function ($query) {
                    $query->whereNull('call_status')
                        ->orWhereNull('call_initiated_at')
                        ->orWhereNull('call_direction')
                        ->orWhereNull('call_type');
                })
                ->get();

            $updated = 0;
            $errors = 0;

            foreach ($llamadas as $llamada) {
                try {
                    if ($this->updateCallDataFromElevenLabs($llamada)) {
                        $updated++;
                        Log::info("Llamada {$llamada->id_llamada} actualizada correctamente");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error actualizando llamada {$llamada->id_llamada}: " . $e->getMessage());
                }
            }

            Log::info("Backfill completado: {$updated} llamadas actualizadas, {$errors} errores");

            return response()->json([
                'success' => true,
                'message' => "Backfill completado: {$updated} llamadas actualizadas, {$errors} errores",
                'stats' => [
                    'total_processed' => $llamadas->count(),
                    'updated' => $updated,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en backfill de llamadas: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar datos de una llamada específica desde ElevenLabs
     */
    private function updateCallDataFromElevenLabs(Llamada $llamada)
    {
        if (!$llamada->elevenlabs_conversation_id) {
            return false;
        }

        try {
            // Obtener información de ElevenLabs sobre la conversación
            $conversationInfo = $this->getElevenLabsConversationInfo($llamada->elevenlabs_conversation_id);
            
            if (!$conversationInfo) {
                Log::warning("No se pudo obtener información de ElevenLabs para conversation_id: {$llamada->elevenlabs_conversation_id}");
                return false;
            }

            // Preparar datos para actualizar
            $updateData = [];

            // Completar información básica faltante
            if (!$llamada->call_initiated_at && $llamada->created_at) {
                $updateData['call_initiated_at'] = $llamada->created_at;
            }

            if (!$llamada->call_direction) {
                $updateData['call_direction'] = Llamada::DIRECTION_OUTBOUND;
            }

            if (!$llamada->call_type) {
                $updateData['call_type'] = Llamada::TYPE_AGENT;
            }

            // Determinar estado basándose en información disponible
            if (!$llamada->call_status) {
                $status = $this->determineCallStatusFromContext($llamada, $conversationInfo);
                if ($status) {
                    $updateData['call_status'] = $status;
                }
            }

            // Actualizar metadata si no existe
            if (!$llamada->call_metadata) {
                $updateData['call_metadata'] = [
                    'backfill_update' => now()->toISOString(),
                    'conversation_id' => $llamada->elevenlabs_conversation_id,
                    'sip_call_id' => $llamada->elevenlabs_sip_call_id,
                    'original_status' => $llamada->status
                ];
            }

            // Agregar notas internas si no existen
            if (!$llamada->internal_notes && $llamada->call_notes) {
                $updateData['internal_notes'] = 'Backfilled from call_notes: ' . $llamada->call_notes;
            }

            // Actualizar solo si hay cambios
            if (!empty($updateData)) {
                $llamada->update($updateData);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Error actualizando datos de ElevenLabs para llamada {$llamada->id_llamada}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información de conversación desde ElevenLabs API
     */
    private function getElevenLabsConversationInfo($conversationId)
    {
        try {
            $elevenLabsApiKey = config('services.elevenlabs.api_key');
            
            if (!$elevenLabsApiKey) {
                return null;
            }

            // Intentar obtener información de la conversación
            $response = Http::withHeaders([
                'xi-api-key' => $elevenLabsApiKey,
                'Content-Type' => 'application/json'
            ])->get("https://api.elevenlabs.io/v1/convai/conversations/{$conversationId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Error obteniendo información de conversación {$conversationId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determinar estado de llamada basándose en contexto disponible
     */
    private function determineCallStatusFromContext(Llamada $llamada, $conversationInfo = null)
    {
        // Si hay notas de llamada, intentar inferir el estado
        if ($llamada->call_notes) {
            $notes = strtolower($llamada->call_notes);
            
            if (str_contains($notes, '486') || str_contains($notes, 'busy')) {
                return Llamada::CALL_STATUS_BUSY;
            }
            
            if (str_contains($notes, '503') || str_contains($notes, 'service unavailable')) {
                return Llamada::CALL_STATUS_FAILED;
            }
            
            if (str_contains($notes, '408') || str_contains($notes, 'timeout')) {
                return Llamada::CALL_STATUS_NO_ANSWER;
            }
            
            if (str_contains($notes, 'initiated')) {
                return Llamada::CALL_STATUS_INITIATED;
            }
        }

        // Basarse en el estado actual del sistema
        switch ($llamada->status) {
            case Llamada::STATUS_EN_CURSO:
                return Llamada::CALL_STATUS_INITIATED;
            case Llamada::STATUS_FINALIZADA:
                return Llamada::CALL_STATUS_COMPLETED;
            case Llamada::STATUS_ACEPTADA:
                return Llamada::CALL_STATUS_COMPLETED;
            case Llamada::STATUS_RECHAZADA:
                return Llamada::CALL_STATUS_FAILED;
            default:
                return Llamada::CALL_STATUS_INITIATED;
        }
    }

    /**
     * Ejecutar backfill automáticamente al iniciar una nueva llamada
     */
    private function runAutomaticBackfill()
    {
        try {
            // Solo ejecutar si hay llamadas pendientes de actualizar
            $pendingCount = Llamada::whereNotNull('elevenlabs_conversation_id')
                ->where(function ($query) {
                    $query->whereNull('call_status')
                        ->orWhereNull('call_initiated_at')
                        ->orWhereNull('call_direction')
                        ->orWhereNull('call_type');
                })
                ->count();

            if ($pendingCount > 0) {
                Log::info("Ejecutando backfill automático para {$pendingCount} llamadas pendientes");
                
                // Ejecutar backfill en background para no bloquear la llamada actual
                dispatch(function () {
                    $this->backfillMissingCallData();
                })->afterResponse();
            }

        } catch (\Exception $e) {
            Log::error("Error en backfill automático: " . $e->getMessage());
        }
    }

    /**
     * Convertir tipos de vehículos de nuestra nomenclatura a la de Arcángel
     * Retorna array de posibles variantes en Arcángel usando tabla vehiculos_relaciones
     * Solo incluye vehículos que soporten el peso de la carga
     * 
     * @param string $tipoLocal Tipo de vehículo en nomenclatura local
     * @param float $pesoCarga Peso de la carga en kg (0 si no se filtra por peso)
     * @return array Variantes de vehículos en Arcángel que cumplen con peso máximo
     */
    private function convertirTipoVehiculo(string $tipoLocal, float $pesoCarga = 0): array
    {
        $tipoNormalizado = $this->normalizarTexto($tipoLocal);
        
        Log::info('🔍 Convirtiendo tipo de vehículo con filtro de peso', [
            'vehiculo_local' => $tipoLocal,
            'peso_carga' => $pesoCarga . ' kg'
        ]);
        
        try {
            // ESTRATEGIA 2: Buscar coincidencia en tabla_pricing (la más común)
            $query2 = \DB::table('vehiculos_relaciones')
                ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                ->join('vehiculos_arcangel', 'vehiculos_relaciones.vehiculo_arcangel_id', '=', 'vehiculos_arcangel.id')
                ->where(\DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%' . $tipoNormalizado . '%');
            
            // Filtrar por peso máximo si se especifica
            if ($pesoCarga > 0) {
                $query2->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
            }
            
            $vehiculosArcangel = $query2->pluck('vehiculos_arcangel.nombre')
                ->unique()
                ->toArray();
            
            if (!empty($vehiculosArcangel)) {
                Log::info('✅ Vehículos Arcangel encontrados (tabla_pricing)', [
                    'vehiculo_local' => $tipoLocal,
                    'peso_carga' => $pesoCarga,
                    'vehiculos_arcangel' => $vehiculosArcangel
                ]);
                return $vehiculosArcangel;
            }
            
            Log::warning('⚠️ No se encontró relación en BD, usando mapeo de respaldo');
            
        } catch (\Exception $e) {
            Log::error('❌ Error consultando tabla vehiculos_relaciones', [
                'error' => $e->getMessage(),
                'vehiculo' => $tipoLocal
            ]);
        }
        
        // MAPEO DE RESPALDO (fallback) si no hay relación en BD
        $mapeo = [
            // Tractomulas
            'TRACTO MULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3', 'TRACTOMULA 4'],
            'TRACTO MULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
            'TRACTOMULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
            'TRACTOMULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
            'ARTICULADO' => ['TRACTOMULA3', 'TRACTOMULA 3'],
            'TRACTOCAMION' => ['TRACTOMULA3', 'TRACTOMULA 3'],
            
            // Sencillos
            'SENCILLO' => ['SENCILLO'],
            'CAMION SENCILLO' => ['SENCILLO'],
            'RIGIDO' => ['SENCILLO'],
            
            // Doble troque
            'DOBLE TROQUE' => ['DOBLE TROQUE', 'DOBLETROQUE'],
            
            // Camionetas
            'CAMIONETA' => ['CAMIONETA', 'TURBO'],
            'TURBO' => ['TURBO', 'CAMIONETA'],
            
            // Patinetas
            'PATINETA' => ['PATINETA', 'PATINETA2', 'PATINETA3'],
            'PATINETA 2' => ['PATINETA2', 'PATINETA 2'],
            'PATINETA 3' => ['PATINETA3', 'PATINETA 3'],
        ];
        
        // Buscar coincidencia en el mapeo
        foreach ($mapeo as $clave => $variantes) {
            if (str_contains($tipoNormalizado, $clave) || $tipoNormalizado === $clave) {
                return $variantes;
            }
        }
        
        // Si no hay mapeo, intentar normalización simple y devolverlo
        $tipoSimple = str_replace([' ', '-'], '', $tipoNormalizado);
        return [$tipoNormalizado, $tipoSimple];
    }

    /**
     * Normalizar texto eliminando acentos y caracteres especiales
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = trim(strtoupper($texto));
        $acentos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ñ' => 'N', 'ñ' => 'n'
        ];
        return strtr($texto, $acentos);
    }
}
