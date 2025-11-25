<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use App\Services\ElevenLabsService;
use App\Services\OpenAIService;
use App\Services\VoiceCacheService;
use App\Models\Llamada;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VoiceAgentController extends Controller
{
    protected $elevenLabsService;
    protected $openAIService;
    protected $voiceCacheService;
    
    public function __construct(
        ElevenLabsService $elevenLabsService,
        OpenAIService $openAIService,
        VoiceCacheService $voiceCacheService
    ) {
        $this->elevenLabsService = $elevenLabsService;
        $this->openAIService = $openAIService;
        $this->voiceCacheService = $voiceCacheService;
    }
    
    /**
     * Punto de entrada principal - Mensaje de bienvenida conversacional
     */
    public function welcome(Request $request)
    {
        Log::info('Voice agent welcome called', [
            'call_sid' => $request->input('CallSid'),
            'from' => $request->input('From'),
            'to' => $request->input('To')
        ]);
        
        // Validar ElevenLabs antes de proceder
        if (!$this->validateElevenLabsAvailability()) {
            Log::error('ElevenLabs not available, cannot proceed with voice agent');
            
            $response = new VoiceResponse();
            $response->say('Lo sentimos, nuestro sistema de voz no está disponible en este momento. Te vamos a transferir con un asesor.', [
                'voice' => 'alice',
                'language' => 'es-MX'
            ]);
            
            // Transferir inmediatamente a agente humano
            $response->dial('+573105672307'); // Número de emergencia
            $response->hangup();
            
            return response($response)->header('Content-Type', 'text/xml');
        }
        
        // Verificar si estamos en horario comercial
        if ($this->shouldRejectCall($request->input('From'))) {
            return $this->buildOutOfHoursResponse();
        }
        
        // Crear nueva conversación
        $conversation = $this->createConversation($request);
        
        $response = new VoiceResponse();
        
        // Mensaje de bienvenida más natural y conversacional
        $welcomeMessage = config('elevenlabs.predefined_messages.welcome', 
            'Hola, soy Andrea, tu asistente virtual de CONALCA. ¿En qué puedo ayudarte hoy?');
        
        // Añadir mensaje de bienvenida al historial
        $conversation->addToHistory('assistant', $welcomeMessage);
        
        // Generar audio con ElevenLabs (obligatorio, no fallback)
        $audioUrl = $this->generateVoiceResponse($welcomeMessage, $conversation->id);
        
        if ($audioUrl) {
            $response->play($audioUrl);
        } else {
            // Si falló ElevenLabs, usar mensaje de error y transferir
            Log::error('Critical: ElevenLabs failed for welcome message', [
                'conversation_id' => $conversation->id,
                'fallback_used' => true
            ]);
            
            $response->say('Disculpa, estamos experimentando problemas técnicos. Te voy a transferir con un asesor.', [
                'voice' => 'alice',
                'language' => 'es-MX'
            ]);
            
            return $this->transferToAgent($conversation->id);
        }
        
        // Configurar reconocimiento de voz - Corazón del sistema conversacional
        $gather = $response->gather([
            'input' => 'speech',                   // Solo reconocimiento de voz
            'language' => 'es-MX',                 // Español de México
            'speechTimeout' => 'auto',             // Detectar fin automáticamente
            'speechModel' => 'phone_call',         // Optimizado para llamadas
            'enhanced' => 'true',                  // Reconocimiento mejorado
            'profanityFilter' => 'false',          // No filtrar (para flexibilidad)
            'action' => route('voice.conversation.process', ['conversation_id' => $conversation->id]),
            'method' => 'POST',
            'timeout' => config('elevenlabs.conversation.silence_timeout', 6)
        ]);
        
        $gather->say(config('elevenlabs.predefined_messages.listening', 'Te escucho...'), [
            'language' => 'es-MX'
        ]);
        
        // Si no detectamos habla
        $response->say(config('elevenlabs.predefined_messages.goodbye', 
            'Gracias por comunicarte con CONALCA. ¡Que tengas un excelente día!'), [
            'language' => 'es-MX'
        ]);
        
        $response->hangup();
        
        return response($response)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Procesar conversación natural con IA
     */
    public function processConversation(Request $request, $conversationId)
    {
        try {
            Log::info('Processing conversation', [
                'conversation_id' => $conversationId,
                'call_sid' => $request->input('CallSid'),
                'speech_result' => $request->input('SpeechResult')
            ]);
            
            // Recuperar la conversación
            $conversation = Conversation::findOrFail($conversationId);
            
            // Obtener el texto reconocido del usuario
            $userInput = $request->input('SpeechResult');
            
            if (empty($userInput)) {
                return $this->handleNoSpeechDetected($conversationId);
            }
            
            // Guardar la entrada del usuario en la conversación
            $conversation->addToHistory('user', $userInput);
            
            // Analizar sentiment y detectar tema
            $conversation->analyzeSentiment($userInput);
            $conversation->detectTopic();
            
            // Verificar si la conversación es muy larga
            if ($conversation->turn_count >= config('elevenlabs.conversation.max_turns', 20)) {
                return $this->handleLongConversation($conversationId);
            }
            
            // Procesar con OpenAI
            $botResponse = $this->openAIService->processConversation(
                $userInput, 
                $conversation->getOpenAIContext()
            );
            
            // Guardar la respuesta del bot
            $conversation->addToHistory('assistant', $botResponse);
            
            // Convertir la respuesta en voz usando ElevenLabs
            $audioUrl = $this->generateVoiceResponse($botResponse, $conversation->id);
            
            return $this->buildConversationalResponse($botResponse, $audioUrl, $conversationId);
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento conversacional', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->handleError($conversationId);
        }
    }

    /**
     * Construir respuesta conversacional y continuar el flujo
     */
    protected function buildConversationalResponse($text, $audioUrl = null, $conversationId)
    {
        $response = new VoiceResponse();
        
        // Reproducir respuesta de la IA con ElevenLabs obligatorio
        if ($audioUrl) {
            $response->play($audioUrl);
        } else {
            // Si ElevenLabs falló, transferir inmediatamente
            Log::error('Critical: ElevenLabs failed for conversation response', [
                'conversation_id' => $conversationId,
                'text' => $text
            ]);
            
            $response->say('Disculpa, estoy teniendo problemas técnicos. Te voy a transferir con un asesor.', [
                'voice' => 'alice',
                'language' => 'es-MX'
            ]);
            
            return $this->transferToAgent($conversationId);
        }
        
        // Verificar si la respuesta contiene una transferencia o finalización
        if ($this->shouldTransferCall($text)) {
            return $this->transferToAgent($conversationId);
        }
        
        if ($this->shouldEndCall($text)) {
            return $this->endConversation($conversationId, 'natural_end');
        }
        
        // Configurar para continuar la conversación (escuchar de nuevo)
        $gather = $response->gather([
            'input' => 'speech',
            'language' => 'es-MX',
            'speechTimeout' => 'auto',
            'speechModel' => 'phone_call',
            'enhanced' => 'true',
            'action' => route('voice.conversation.process', ['conversation_id' => $conversationId]),
            'method' => 'POST',
            'timeout' => config('elevenlabs.conversation.silence_timeout', 6)
        ]);
        
        $gather->say("¿Hay algo más en lo que pueda ayudarte?", ['language' => 'es-MX']);
        
        // Si el usuario no responde, finalizar cortésmente
        $response->say("Gracias por comunicarte con CONALCA. Si necesitas ayuda adicional, no dudes en volver a llamar.", [
            'language' => 'es-MX'
        ]);
        
        $response->hangup();
        
        return response($response)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Generar respuesta de voz usando ElevenLabs con caché y reintentos
     */
    protected function generateVoiceResponse($text, $conversationId, $maxRetries = 3)
    {
        try {
            // Validar que ElevenLabs esté disponible
            if (!$this->validateElevenLabsAvailability()) {
                throw new \Exception('ElevenLabs service not available');
            }
            
            // Asegurar que se use la voz Andrea
            $voiceId = $this->ensureAndreaVoice();
            $settings = config('elevenlabs.voice_settings.spanish');
            
            Log::info('Generating voice response with Andrea voice', [
                'conversation_id' => $conversationId,
                'voice_id' => $voiceId,
                'text_length' => strlen($text),
                'is_andrea_voice' => $voiceId === 'qHkrJuifPpn95wK3rm2A'
            ]);
            
            // Verificar caché primero
            $cachedAudio = $this->voiceCacheService->getCachedAudio($text, $voiceId, $settings);
            
            if ($cachedAudio) {
                Log::info('Using cached voice response', [
                    'conversation_id' => $conversationId,
                    'text_length' => strlen($text),
                    'cache_hit' => true
                ]);
                
                return $cachedAudio['url'];
            }
            
            // Generar nuevo audio con reintentos
            $audioContent = null;
            $lastError = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    Log::info('Attempting ElevenLabs audio generation', [
                        'conversation_id' => $conversationId,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries
                    ]);
                    
                    $startTime = microtime(true);
                    $audioContent = $this->elevenLabsService->generatePhoneOptimizedSpeech($text, $voiceId, 'conversation');
                    $generationTime = round((microtime(true) - $startTime) * 1000);
                    
                    if (!empty($audioContent)) {
                        Log::info('ElevenLabs audio generated successfully', [
                            'conversation_id' => $conversationId,
                            'attempt' => $attempt,
                            'generation_time_ms' => $generationTime,
                            'audio_size_bytes' => strlen($audioContent)
                        ]);
                        break; // Éxito, salir del bucle
                    } else {
                        $lastError = 'Empty audio content returned';
                        Log::warning('Empty audio content from ElevenLabs', [
                            'conversation_id' => $conversationId,
                            'attempt' => $attempt,
                            'text_length' => strlen($text)
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning('ElevenLabs generation attempt failed', [
                        'conversation_id' => $conversationId,
                        'attempt' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Esperar antes del siguiente intento (excepto en el último)
                    if ($attempt < $maxRetries) {
                        sleep(1);
                    }
                }
            }
            
            // Si después de todos los intentos no tenemos audio, es un error crítico
            if (empty($audioContent)) {
                Log::error('Failed to generate ElevenLabs audio after all retries', [
                    'conversation_id' => $conversationId,
                    'max_retries' => $maxRetries,
                    'last_error' => $lastError,
                    'text' => $text
                ]);
                
                // En lugar de retornar null, vamos a usar un mensaje de error específico
                throw new \Exception("No se pudo generar audio después de {$maxRetries} intentos: {$lastError}");
            }
            
            // Guardar el audio con nombre único
            $filename = "conversation_{$conversationId}_" . time() . "_" . uniqid() . ".mp3";
            $filePath = "voices/conversations/{$filename}";
            
            Storage::disk('public')->put($filePath, $audioContent);
            $audioUrl = Storage::disk('public')->url($filePath);
            
            // Cachear para futuras solicitudes similares
            $this->voiceCacheService->cacheAudio($text, $voiceId, $settings, $audioContent);
            
            // Actualizar métricas de la conversación
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                $conversation->increment('audio_generation_time_ms', $generationTime);
                $conversation->increment('total_audio_size_bytes', strlen($audioContent));
            }
            
            Log::info('Voice response generated successfully', [
                'conversation_id' => $conversationId,
                'generation_time_ms' => $generationTime,
                'audio_size_bytes' => strlen($audioContent),
                'url' => $audioUrl
            ]);
            
            return $audioUrl;
            
        } catch (\Exception $e) {
            Log::error('Error generando respuesta de voz', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Crear nueva conversación
     */
    protected function createConversation(Request $request)
    {
        return Conversation::create([
            'call_sid' => $request->input('CallSid'),
            'caller_number' => $request->input('From'),
            'caller_city' => $request->input('CallerCity'),
            'caller_country' => $request->input('CallerCountry', 'CO'),
            'direction' => 'inbound',
            'status' => 'active',
            'started_at' => now(),
            'voice_id' => config('elevenlabs.default_voice_id'),
            'context' => [
                'system_message' => $this->getSystemPrompt(),
                'history' => []
            ],
            'metadata' => [
                'twilio_data' => $request->only([
                    'AccountSid', 'CallSid', 'From', 'To', 'CallerName',
                    'CallerCity', 'CallerState', 'CallerCountry', 'CallerZip'
                ])
            ]
        ]);
    }
    
    /**
     * Manejar cuando no se detecta habla
     */
    protected function handleNoSpeechDetected($conversationId)
    {
        $response = new VoiceResponse();
        
        $repeatMessage = config('elevenlabs.predefined_messages.repeat', 
            'Disculpa, no he podido entender lo que has dicho. ¿Podrías repetirlo por favor?');
        
        $response->say($repeatMessage, ['language' => 'es-MX']);
        
        // Dar otra oportunidad
        $gather = $response->gather([
            'input' => 'speech',
            'language' => 'es-MX',
            'speechTimeout' => 'auto',
            'enhanced' => 'true',
            'action' => route('voice.conversation.process', ['conversation_id' => $conversationId]),
            'timeout' => config('elevenlabs.conversation.silence_timeout', 6)
        ]);
        
        $gather->say("Te escucho nuevamente...", ['language' => 'es-MX']);
        
        // Si aún no hay respuesta, ofrecer transferencia
        $response->say("Parece que tenemos problemas de conexión. Te voy a transferir con un asesor.", [
            'language' => 'es-MX'
        ]);
        
        return $this->transferToAgent($conversationId);
    }
    
    /**
     * Manejar conversaciones muy largas
     */
    protected function handleLongConversation($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if ($conversation) {
            $conversation->markCompleted('max_turns_reached');
        }
        
        $response = new VoiceResponse();
        $response->say("Ha sido un placer ayudarte. Para continuar con tu consulta, te voy a transferir con uno de nuestros asesores especializados.", [
            'language' => 'es-MX'
        ]);
        
        return $this->transferToAgent($conversationId);
    }
    
    /**
     * Manejar errores técnicos
     */
    protected function handleError($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if ($conversation) {
            $conversation->update(['status' => 'failed']);
        }
        
        $response = new VoiceResponse();
        $errorMessage = config('elevenlabs.predefined_messages.error', 
            'Lo siento, estamos experimentando problemas técnicos. Te transferiré con un asesor.');
        
        $response->say($errorMessage, ['language' => 'es-MX']);
        
        return $this->transferToAgent($conversationId);
    }
    
    /**
     * Transferir a agente humano
     */
    protected function transferToAgent($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if ($conversation) {
            $conversation->update([
                'status' => 'transferred',
                'completion_reason' => 'transferred_to_human',
                'completed_at' => now()
            ]);
        }
        
        $response = new VoiceResponse();
        $response->say("Un momento por favor, te estoy conectando con un asesor.", [
            'language' => 'es-MX'
        ]);
        
        // TODO: Implementar lógica de transferencia real
        // $response->dial('+57XXXXXXXXX'); // Número del centro de llamadas
        
        $response->say("En este momento todos nuestros asesores están ocupados. Por favor intenta más tarde o envíanos un WhatsApp.", [
            'language' => 'es-MX'
        ]);
        
        $response->hangup();
        
        return response($response)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Finalizar conversación
     */
    protected function endConversation($conversationId, $reason = 'natural_end')
    {
        $conversation = Conversation::find($conversationId);
        if ($conversation) {
            $conversation->markCompleted($reason, true);
        }
        
        $response = new VoiceResponse();
        $response->say(config('elevenlabs.predefined_messages.goodbye', 
            'Gracias por comunicarte con CONALCA. ¡Que tengas un excelente día!'), [
            'language' => 'es-MX'
        ]);
        
        $response->hangup();
        
        return response($response)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Verificar si debemos transferir la llamada
     */
    protected function shouldTransferCall($text)
    {
        $transferKeywords = [
            'transferir', 'operador', 'humano', 'persona', 'asesor',
            'no entiendo', 'no me ayuda', 'hablar con alguien'
        ];
        
        $text = strtolower($text);
        foreach ($transferKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si debemos finalizar la llamada
     */
    protected function shouldEndCall($text)
    {
        $endKeywords = [
            'gracias', 'adiós', 'chao', 'hasta luego', 'eso es todo',
            'ya no necesito', 'perfecto', 'listo', 'muchas gracias'
        ];
        
        $text = strtolower($text);
        foreach ($endKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar horario comercial
     */
    protected function shouldRejectCall($phoneNumber)
    {
        $now = Carbon::now('America/Bogota');
        $hour = (int)$now->format('G');
        $isWeekend = $now->isWeekend();
        
        // Horario comercial: 8am - 6pm, lunes a viernes
        $isBusinessHours = ($hour >= 8 && $hour < 18) && !$isWeekend;
        
        // Si estamos fuera de horario y no es un número especial
        if (!$isBusinessHours && !$this->isSpecialNumber($phoneNumber)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si es un número especial (emergencias, VIP, etc.)
     */
    protected function isSpecialNumber($phoneNumber)
    {
        $specialNumbers = [
            '+573105672307', // Número de prueba
            // Añadir más números VIP o de emergencia
        ];
        
        return in_array($phoneNumber, $specialNumbers);
    }
    
    /**
     * Respuesta para llamadas fuera de horario
     */
    protected function buildOutOfHoursResponse()
    {
        $response = new VoiceResponse();
        
        $outOfHoursMessage = "Gracias por comunicarte con CONALCA. En este momento nos encontramos fuera de horario de atención. " .
                           "Nuestro horario es de lunes a viernes de 8 AM a 6 PM. " .
                           "Por favor intenta llamarnos nuevamente en horario de oficina.";
        
        $response->say($outOfHoursMessage, [
            'voice' => 'alice',
            'language' => 'es-MX'
        ]);
        
        $response->hangup();
        
        return response($response)->header('Content-Type', 'text/xml');
    }
    
    /**
     * Obtener prompt del sistema para OpenAI
     */
    protected function getSystemPrompt()
    {
        return "Eres Andrea, asistente virtual de CONALCA, una empresa líder en transporte y logística en Colombia. 
        Tu objetivo es ayudar a los clientes con sus consultas sobre cotizaciones, seguimiento de cargas y servicios de transporte.
        
        IMPORTANTE - Instrucciones para respuestas:
        - Mantén tus respuestas MUY CONCISAS (máximo 2-3 oraciones) ya que estás en una llamada telefónica
        - Habla en un tono amable y profesional, como una persona real
        - No inventes información sobre precios específicos o datos técnicos
        - Si no conoces algún dato, ofrece tomar los datos del cliente para que un asesor se comunique después
        - Para cotizaciones, pregunta: origen, destino, tipo de carga, peso y dimensiones aproximadas
        - Si el cliente parece frustrado o la consulta es muy compleja, ofrece transferirlo a un asesor humano
        - Usa un lenguaje natural y conversacional, evita sonar robótica
        - Detecta cuando el cliente quiere finalizar la conversación y despídete cortésmente";
    }
    
    /**
     * Asegurar que se use siempre la voz Andrea configurada
     */
    protected function ensureAndreaVoice()
    {
        $voiceId = config('elevenlabs.default_voice_id');
        $expectedAndreaVoiceId = 'qHkrJuifPpn95wK3rm2A';
        
        if ($voiceId !== $expectedAndreaVoiceId) {
            Log::warning('Voice ID mismatch detected', [
                'configured_voice' => $voiceId,
                'expected_andrea_voice' => $expectedAndreaVoiceId
            ]);
            
            // Forzar usar la voz Andrea
            return $expectedAndreaVoiceId;
        }
        
        return $voiceId;
    }
    
    /**
     * Validar que ElevenLabs esté disponible antes de procesar
     */
    protected function validateElevenLabsAvailability()
    {
        try {
            // Verificar configuración
            if (empty(config('elevenlabs.api_key'))) {
                throw new \Exception('ElevenLabs API key not configured');
            }
            
            if (empty(config('elevenlabs.default_voice_id'))) {
                throw new \Exception('ElevenLabs voice ID not configured');
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('ElevenLabs validation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // Métodos legacy para compatibilidad con el sistema existente
    
    public function menuResponse(Request $request)
    {
        // Redirigir al nuevo sistema conversacional
        return $this->welcome($request);
    }
    
    public function startVoiceAgent(Request $request)
    {
        // Redirigir al nuevo sistema conversacional
        return $this->welcome($request);
    }
    
    public function processVoiceInput(Request $request)
    {
        // Extraer conversation_id desde la URL o crear nueva conversación
        $conversationId = $request->route('conversation_id') ?? $this->createConversation($request)->id;
        return $this->processConversation($request, $conversationId);
    }
}