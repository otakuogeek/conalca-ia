<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\CotizacionModel;
use App\Models\CallDriverDecision;

class ConversationalAgentService
{
    protected $openai;
    protected $systemPrompt;
    protected $cacheTtlMinutes = 30;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            // During development/setup, don't throw exception on service discovery
            $this->openai = null;
            return;
        }
        $this->openai = OpenAI::client($apiKey);
        $this->setupSystemPrompt();
    }

    /**
     * Configurar el prompt del sistema para el agente
     */
    private function setupSystemPrompt()
    {
        // Si no hay cliente OpenAI configurado, usar prompt por defecto
        if (!$this->openai) {
            $this->systemPrompt = "Servicio OpenAI no configurado. Necesitas configurar OPENAI_API_KEY en .env";
            return;
        }
        
        $this->systemPrompt = "
Eres un representante profesional de CONALCA. Sigues un GUIÓN ESTRUCTURADO para llamadas efectivas.

ESTRUCTURA DE LLAMADA OBLIGATORIA:

1. PRESENTACIÓN COMPLETA:
   - 'Buenos días Sr. [NOMBRE_COMPLETO]. Soy representante de CONALCA.'
   - 'Tenemos una propuesta de servicio de transporte que podría ser de su interés.'

2. DETALLES ESPECÍFICOS DEL TRABAJO:
   - Tipo de carga y peso exacto
   - Ruta completa (origen → destino)  
   - Fecha programada específica
   - 'Será una única vez' (si aplica)

3. PREGUNTA DE INTERÉS:
   - '¿Estaría interesado en esta propuesta, Señor?'

4. MANEJO DE RESPUESTAS:
   - Si dice SÍ: 'Perfecto en unos minutos mi compañera encargada de asignar conductor a las solicitudes de transporte se pondrá en contacto con usted para confirmar valores y asignar ruta muchas gracias'
   - Si dice NO: 'Muchas gracias por su tiempo será ya en otra ocasión que tenga un buen día'
   - Si pregunta detalles: Responder específicamente con la información disponible

PERSONALIDAD:
- PROFESIONAL y ESTRUCTURADO
- Usa NOMBRES COMPLETOS siempre
- Información COMPLETA y DETALLADA
- Lenguaje formal colombiano
- Seguir el guión EXACTAMENTE

EJEMPLO DE FLUJO:
'Buenos días Sr. Jhon Jairo Gutierrez Casabuenas. Soy representante de CONALCA. Tenemos una propuesta de servicio de transporte que podría ser de su interés.

Para realizar un transporte terrestre de granel sólido, con un peso aproximado de 2000 kg. La ruta a seguir es de Funza a Bogotá. La fecha programada para la operación es el 1 de julio del 2024 y será una única vez.

¿Estaría interesado en esta propuesta, Señor?'

IMPORTANTE: SIEMPRE usar el guión estructurado y información completa de la cotización.
";
    }

    /**
     * Verificar si el servicio está configurado correctamente
     */
    private function ensureConfigured()
    {
        if (!$this->openai) {
            throw new \Exception('OpenAI API key no configurada. Verifica OPENAI_API_KEY en .env');
        }
    }

    /**
     * Generar mensaje inicial estructurado para el conductor
     */
    public function generateInitialMessage(CotizacionModel $cotizacion, $driverName = 'conductor')
    {
        $this->ensureConfigured();
        $userPrompt = "
Genera un mensaje ESTRUCTURADO siguiendo el guión de CONALCA para llamar al conductor {$driverName}:

INFORMACIÓN DEL TRABAJO:
- Conductor: {$driverName}
- Tipo de carga: {$cotizacion->tipo_mercancia}
- Peso: {$cotizacion->peso_mercancia} kg
- Ruta: {$cotizacion->ciudad_origen} → {$cotizacion->ciudad_destino}
- Vehículo: {$cotizacion->vehiculo_requerido}
- Valor: $" . number_format($cotizacion->valor ?? 0) . "

ESTRUCTURA OBLIGATORIA:
1. 'Buenos días Sr. {$driverName}. Soy representante de CONALCA.'
2. 'Tenemos una propuesta de servicio de transporte que podría ser de su interés.'
3. Detalles específicos del trabajo (tipo, peso, ruta, fecha)
4. 'Será una única vez.' (si aplica)
5. '¿Estaría interesado en esta propuesta, Señor?'

ESTILO:
- Formal y profesional
- Información completa y detallada
- Usar el nombre completo del conductor
- Lenguaje empresarial colombiano

Genera SOLO el mensaje estructurado siguiendo este guión exactamente.
";

        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 400,
                'temperature' => 0.7
            ]);

            $message = $response->choices[0]->message->content;
            
            Log::info("Mensaje inicial generado para conductor", [
                'driver_name' => $driverName,
                'cotizacion_id' => $cotizacion->id,
                'message_length' => strlen($message)
            ]);

            return trim($message);

        } catch (\Exception $e) {
            Log::error("Error generando mensaje inicial: " . $e->getMessage());
            
            // Mensaje de fallback formal
            return "Buenos días Sr. {$driverName}. Le llamo de la empresa CONALCA para ofrecerle un servicio de transporte con {$cotizacion->vehiculo_requerido} desde Funza hasta Bogotá por un valor de $" . number_format($cotizacion->valor_declarado) . " pesos. ¿Estaría interesado en aceptar esta propuesta?";
        }
    }

    /**
     * Procesar respuesta del conductor
     */
    public function processDriverResponse($cotizacionId, $driverId, $userInput, $conversationHistory = [])
    {
        $this->ensureConfigured();
        
        $cotizacion = CotizacionModel::find($cotizacionId);
        
        if (!$cotizacion) {
            throw new \Exception("Cotización no encontrada: $cotizacionId");
        }

        Log::info('Procesando respuesta con guión estructurado', [
            'cotizacion_id' => $cotizacionId,
            'driver_id' => $driverId,
            'user_input' => $userInput,
            'input_length' => strlen($userInput)
        ]);

        // Construir contexto de conversación para AI
        $conversationContext = $this->buildConversationContext($conversationHistory, $userInput, $cotizacion);
        
        $userPrompt = "
RESPUESTA DEL CONDUCTOR: '{$userInput}'

{$conversationContext}

GUIÓN DE RESPUESTAS ESTRUCTURADAS:

1. SI ACEPTA (sí, acepto, me interesa, está bien, etc.):
   RESPUESTA: 'Perfecto en unos minutos mi compañera encargada de asignar conductor a las solicitudes de transporte se pondrá en contacto con usted para confirmar valores y asignar ruta muchas gracias'
   ACCIÓN: ACCEPT

2. SI RECHAZA (no, no puedo, no me sirve, ocupado, etc.):
   RESPUESTA: 'Muchas gracias por su tiempo será ya en otra ocasión que tenga un buen día'
   ACCIÓN: REJECT

3. SI PREGUNTA DETALLES (valor, cuánto, qué día, etc.):
   RESPONDER: Información específica disponible y luego preguntar '¿Le interesa entonces?'
   ACCIÓN: PROVIDE_INFO

4. RESPUESTA UNCLEAR/AMBIGUA:
   RESPUESTA: 'Disculpe no lo escuché bien. ¿Estaría interesado en esta propuesta de transporte?'
   ACCIÓN: CLARIFY

FORMATO DE RESPUESTA JSON:
{
  \"response_message\": \"mensaje según el guión estructurado\",
  \"intent\": \"ACCEPT|REJECT|PROVIDE_INFO|CLARIFY\",
  \"confidence\": 0.9,
  \"end_conversation\": true/false,
  \"driver_interest\": \"interested|not_interested|needs_info|unclear\"
}

Analiza la respuesta y usa el guión estructurado de CONALCA.
";

        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 300,
                'temperature' => 0.3  // Más consistente con el guión
            ]);

            $aiResponse = $response->choices[0]->message->content;
            $parsedResponse = json_decode($aiResponse, true);
            
            if (!$parsedResponse) {
                // Si no es JSON válido, crear respuesta por defecto
                $parsedResponse = $this->generateDefaultStructuredResponse($userInput);
            }

            Log::info("Respuesta estructurada procesada", [
                'cotizacion_id' => $cotizacionId,
                'intent' => $parsedResponse['intent'] ?? 'unknown',
                'confidence' => $parsedResponse['confidence'] ?? 0,
                'response_preview' => substr($parsedResponse['response_message'] ?? '', 0, 50)
            ]);

            // Guardar decisión del conductor si es clara
            if (in_array($parsedResponse['intent'], ['ACCEPT', 'REJECT'])) {
                $this->saveDriverDecision($cotizacionId, $driverId, $parsedResponse);
            }

            return $parsedResponse;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta estructurada: " . $e->getMessage());
        }

        // Construir contexto de la conversación
        $conversationContext = $this->buildConversationContext($conversationHistory, $userInput, $cotizacion);
        
        $userPrompt = "
ENTRADA DEL CONDUCTOR: '{$userInput}'

CONTEXTO:
{$conversationContext}

TRABAJO OFRECIDO:
- Ruta: {$cotizacion->ciudad_origen} → {$cotizacion->ciudad_destino}
- Vehículo: {$cotizacion->vehiculo_requerido}
- Tipo: {$cotizacion->tipo}

CASOS ESPECIALES A CONSIDERAR:
1. Si dice 'Aló?' o 'Bueno?' = Necesita que repitas la oferta
2. Si pregunta '¿Qué hora?' = Interesado, necesita detalles de horario
3. Si dice '¿Cuánto pagan?' = Interesado, necesita información de pago
4. Si no contestó nada coherente = Repite la oferta más clara

RESPUESTA REQUERIDA EN JSON:
{
    \"decision\": \"ACCEPT\" | \"REJECT\" | \"UNCLEAR\" | \"NEEDS_REPEAT\",
    \"confidence\": 0.0-1.0,
    \"response_message\": \"respuesta natural como colombiano amigable\",
    \"needs_more_info\": true/false,
    \"detected_interest\": \"low|medium|high\",
    \"reason\": \"explicación del análisis\"
}

IMPORTANTE: Si detectas confusión o respuestas vagas, usa 'NEEDS_REPEAT' y repite la oferta de manera más clara.";

        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 400,
                'temperature' => 0.3
            ]);

            $aiResponse = $response->choices[0]->message->content;
            
            // Intentar parsear JSON
            $parsedResponse = $this->parseAgentResponse($aiResponse);
            
            // Guardar decisión si está clara
            if (in_array($parsedResponse['decision'], ['ACCEPT', 'REJECT'])) {
                $this->saveDriverDecision($cotizacionId, $driverId, $parsedResponse);
            }
            
            Log::info("Respuesta del agente procesada", [
                'cotizacion_id' => $cotizacionId,
                'driver_id' => $driverId,
                'decision' => $parsedResponse['decision'],
                'confidence' => $parsedResponse['confidence']
            ]);

            return $parsedResponse;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta del conductor: " . $e->getMessage());
            
            // Respuesta de fallback
            return [
                'decision' => 'UNCLEAR',
                'confidence' => 0.0,
                'response_message' => 'Disculpe, no entendí su respuesta. ¿Puede confirmar si acepta o rechaza el trabajo? Presione 1 para aceptar o 2 para rechazar.',
                'needs_more_info' => true,
                'reason' => 'Error en procesamiento'
            ];
        }
    }

    /**
     * Personalizar respuestas con información real de la cotización
     */
    private function personalizeResponse($responseMessage, $cotizacion)
    {
        $replacements = [
            '{fecha_servicio}' => $cotizacion->fecha_hora_descargue_cargue ?? now()->format('d/m/Y'),
            '{tipo_mercancia}' => $cotizacion->tipo_mercancia ?? 'carga general',
            '{peso_mercancia}' => $cotizacion->peso_mercancia ?? 'por definir',
            '{ciudad_origen}' => ucwords($cotizacion->ciudad_origen ?? 'origen'),
            '{ciudad_destino}' => ucwords($cotizacion->ciudad_destino ?? 'destino'),
            '{regimen_tipo}' => $cotizacion->regimen_nacionalizado ?: 'nacional',
            '{frecuencia_viaje}' => $cotizacion->frecuencia ?? 'única'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $responseMessage);
    }

    /**
     * Análisis rápido para respuestas comunes
     */
    public function quickResponseAnalysis($userInput, $cotizacion = null)
    {
        $this->ensureConfigured();
        
        $input = strtolower(trim($userInput));
        $input = $this->normalizeText($input);
        
        // Patrones de respuestas comunes
        $patterns = [
            // Respuestas de saludo/confusión - necesitan repetir
            'greeting_confused' => [
                'patterns' => ['alo', 'aló', 'bueno', 'diga', 'dígame', 'hola', 'quien habla', 'quien es'],
                'response' => [
                    'decision' => 'NEEDS_REPEAT',
                    'confidence' => 0.9,
                    'response_message' => 'Buenos días. Soy de CONALCA. ¿Me escucha?',
                    'needs_more_info' => true,
                    'detected_interest' => 'medium',
                    'reason' => 'Respuesta de saludo, necesita repetir oferta'
                ]
            ],
            
            // Preguntas sobre horario - interés alto
            'time_questions' => [
                'patterns' => ['que hora', 'qué hora', 'cuando', 'cuándo', 'horario', 'hora es'],
                'response' => [
                    'decision' => 'UNCLEAR',
                    'confidence' => 0.7,
                    'response_message' => 'Horario flexible, salida temprana. ¿Acepta?',
                    'needs_more_info' => true,
                    'detected_interest' => 'high',
                    'reason' => 'Pregunta sobre horario indica interés'
                ]
            ],
            
            // Preguntas sobre pago - interés alto
            'payment_questions' => [
                'patterns' => ['cuanto', 'cuánto', 'pago', 'plata', 'precio', 'vale', 'cobran', 'pagan', 'valor', 'flete'],
                'response' => [
                    'decision' => 'UNCLEAR',
                    'confidence' => 0.8,
                    'response_message' => 'Perfecto, veo que tiene interés. Otro representante se pondrá en contacto con usted para confirmar todos los detalles incluyendo el pago. ¿Acepta el trabajo?',
                    'needs_more_info' => true,
                    'detected_interest' => 'high',
                    'reason' => 'Pregunta sobre pago indica fuerte interés'
                ]
            ],

            // Preguntas sobre fechas y horarios
            'date_time_questions' => [
                'patterns' => ['cuando', 'cuándo', 'fecha', 'día', 'hora', 'horario', 'que día'],
                'response' => [
                    'decision' => 'UNCLEAR',
                    'confidence' => 0.8,
                    'response_message' => 'Es para el {fecha_servicio}. ¿Puede ese día?',
                    'needs_more_info' => true,
                    'detected_interest' => 'high',
                    'reason' => 'Pregunta sobre fechas indica interés en el trabajo'
                ]
            ],

            // Preguntas sobre la carga
            'cargo_questions' => [
                'patterns' => ['que carga', 'qué carga', 'mercancia', 'mercancía', 'producto', 'peso', 'toneladas'],
                'response' => [
                    'decision' => 'UNCLEAR',
                    'confidence' => 0.8,
                    'response_message' => 'Es {tipo_mercancia}, {peso_mercancia} kg. ¿Le sirve?',
                    'needs_more_info' => true,
                    'detected_interest' => 'high',
                    'reason' => 'Pregunta sobre carga indica interés en detalles'
                ]
            ],

            // Preguntas sobre la ruta
            'route_questions' => [
                'patterns' => ['donde', 'dónde', 'ruta', 'desde', 'hasta', 'origen', 'destino', 'kilómetros'],
                'response' => [
                    'decision' => 'UNCLEAR',
                    'confidence' => 0.8,
                    'response_message' => 'Desde {ciudad_origen} hasta {ciudad_destino}. ¿Le sirve?',
                    'needs_more_info' => true,
                    'detected_interest' => 'high',
                    'reason' => 'Pregunta sobre ruta indica interés en el trabajo'
                ]
            ],
            
            // Aceptación clara
            'clear_accept' => [
                'patterns' => ['si', 'sí', 'acepto', 'me sirve', 'bueno', 'perfecto', 'excelente', 'de acuerdo', 'confirmado'],
                'response' => [
                    'decision' => 'ACCEPT',
                    'confidence' => 0.9,
                    'response_message' => 'Perfecto. Le llamamos en unos minutos para coordinar. Gracias.',
                    'needs_more_info' => false,
                    'detected_interest' => 'high',
                    'reason' => 'Aceptación clara detectada'
                ]
            ],
            
            // Rechazo claro
            'clear_reject' => [
                'patterns' => ['no', 'no me sirve', 'no puedo', 'no gracias', 'gracias pero no', 'ocupado', 'no me interesa'],
                'response' => [
                    'decision' => 'REJECT',
                    'confidence' => 0.9,
                    'response_message' => 'Entendido. Gracias y buen día.',
                    'needs_more_info' => false,
                    'detected_interest' => 'low',
                    'reason' => 'Rechazo claro detectado'
                ]
            ]
        ];
        
        // Verificar cada patrón
        foreach ($patterns as $type => $pattern) {
            foreach ($pattern['patterns'] as $match) {
                if (strpos($input, $match) !== false) {
                    Log::info('Patrón de respuesta detectado', [
                        'type' => $type,
                        'pattern' => $match,
                        'user_input' => $userInput
                    ]);
                    
                    $response = $pattern['response'];
                    $response['certainty'] = $response['confidence']; // Para compatibilidad
                    
                    // Personalizar el mensaje con información de la cotización
                    if ($cotizacion && isset($response['response_message'])) {
                        $response['response_message'] = $this->personalizeResponse($response['response_message'], $cotizacion);
                    }
                    
                    return $response;
                }
            }
        }
        
        return null; // No se encontró patrón específico
    }
    
    /**
     * Normalizar texto para análisis
     */
    private function normalizeText($text)
    {
        // Remover acentos y caracteres especiales
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        // Convertir a minúsculas
        $text = strtolower($text);
        // Remover caracteres no alfabéticos excepto espacios
        $text = preg_replace('/[^a-z\s]/', '', $text);
        // Normalizar espacios múltiples
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }

    /**
     * Construir contexto de conversación
     */
    private function buildConversationContext($history, $currentInput, $cotizacion)
    {
        $context = "INFORMACIÓN DEL TRABAJO:\n";
        $context .= "- Vehículo: {$cotizacion->vehiculo_requerido}\n";
        $context .= "- Valor: $" . number_format($cotizacion->valor_declarado) . " pesos\n";
        $context .= "- Ruta: Funza → Bogotá\n\n";
        
        if (!empty($history)) {
            $context .= "CONVERSACIÓN PREVIA:\n";
            foreach ($history as $message) {
                if ($message['role'] === 'assistant') {
                    $context .= "Agente: {$message['content']}\n";
                } elseif ($message['role'] === 'user') {
                    $context .= "Conductor: {$message['content']}\n";
                }
            }
        }
        
        $context .= "\nRESPUESTA ACTUAL DEL CONDUCTOR: {$currentInput}";
        
        return $context;
    }

    /**
     * Parsear respuesta del agente (JSON)
     */
    private function parseAgentResponse($aiResponse)
    {
        try {
            // Intentar extraer JSON de la respuesta
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}') + 1;
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart);
                $parsed = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->validateAgentResponse($parsed);
                }
            }
            
            // Si no se puede parsear JSON, analizar texto
            return $this->parseTextResponse($aiResponse);
            
        } catch (\Exception $e) {
            Log::warning("Error parseando respuesta del agente", ['error' => $e->getMessage()]);
            return $this->getDefaultResponse();
        }
    }

    /**
     * Validar y normalizar respuesta del agente
     */
    private function validateAgentResponse($parsed)
    {
        $decision = strtoupper($parsed['decision'] ?? 'UNCLEAR');
        $confidence = (float)($parsed['confidence'] ?? 0.5);

        $needsMoreInfoValue = $parsed['needs_more_info'] ?? null;
        if ($needsMoreInfoValue === null) {
            $needsMoreInfoValue = ($decision === 'UNCLEAR');
        }

        if (in_array($decision, ['ACCEPT', 'REJECT'], true)) {
            $needsMoreInfoValue = false;
        }

        $responseMessage = $parsed['response_message'] ?? null;
        if (!$responseMessage) {
            $responseMessage = match ($decision) {
                'ACCEPT' => 'Perfecto. He confirmado su aceptación de la propuesta de servicio. Muchas gracias por su confianza y que tenga un excelente día.',
                'REJECT' => 'Perfecto, gracias por avisar. Que esté muy bien.',
                default => 'Respuesta no disponible'
            };
        }

        return [
            'decision' => $decision,
            'confidence' => $confidence,
            'response_message' => $responseMessage,
            'needs_more_info' => (bool) $needsMoreInfoValue,
            'reason' => $parsed['reason'] ?? ''
        ];
    }

    /**
     * Parsear respuesta de texto cuando JSON falla
     */
    private function parseTextResponse($text)
    {
        $text = strtolower($text);
        
        if (strpos($text, 'acepta') !== false || strpos($text, 'accept') !== false) {
            return [
                'decision' => 'ACCEPT',
                'confidence' => 0.8,
                'response_message' => 'Perfecto, el trabajo ha sido confirmado. Gracias por aceptar.',
                'needs_more_info' => false,
                'reason' => 'Aceptó el trabajo'
            ];
        }
        
        if (strpos($text, 'rechaza') !== false || strpos($text, 'reject') !== false) {
            return [
                'decision' => 'REJECT',
                'confidence' => 0.8,
                'response_message' => 'Entendido, gracias por su tiempo. Que tenga buen día.',
                'needs_more_info' => false,
                'reason' => 'Rechazó el trabajo'
            ];
        }
        
        return $this->getDefaultResponse();
    }

    /**
     * Respuesta por defecto cuando no se puede determinar
     */
    private function getDefaultResponse()
    {
        return [
            'decision' => 'UNCLEAR',
            'confidence' => 0.0,
            'response_message' => 'No entendí su respuesta. ¿Acepta el trabajo? Presione 1 para SÍ o 2 para NO.',
            'needs_more_info' => true,
            'reason' => 'Respuesta no clara'
        ];
    }

    /**
     * Generar clave de caché para recursos precargados
     */
    private function getInitialAssetsCacheKey(int $cotizacionId, int $driverId): string
    {
        return "agent:initial:assets:cot:{$cotizacionId}:drv:{$driverId}";
    }

    private function getInitialAssetsCallKey(string $callSid): string
    {
        return "agent:initial:assets:call:{$callSid}";
    }

    /**
     * Guardar recursos precargados para una llamada
     */
    public function cacheInitialAssets(int $cotizacionId, int $driverId, array $payload): void
    {
        Cache::put(
            $this->getInitialAssetsCacheKey($cotizacionId, $driverId),
            $payload,
            now()->addMinutes($this->cacheTtlMinutes)
        );
    }

    public function cacheInitialAssetsForCall(string $callSid, array $payload): void
    {
        Cache::put(
            $this->getInitialAssetsCallKey($callSid),
            $payload,
            now()->addMinutes($this->cacheTtlMinutes)
        );
    }

    /**
     * Recuperar recursos precargados
     */
    public function getCachedInitialAssets(int $cotizacionId, int $driverId): ?array
    {
        return Cache::get($this->getInitialAssetsCacheKey($cotizacionId, $driverId));
    }

    public function getCachedInitialAssetsByCall(string $callSid): ?array
    {
        return Cache::get($this->getInitialAssetsCallKey($callSid));
    }

    /**
     * Limpiar recursos de caché si es necesario
     */
    public function forgetCachedInitialAssets(int $cotizacionId, int $driverId): void
    {
        Cache::forget($this->getInitialAssetsCacheKey($cotizacionId, $driverId));
    }

    public function forgetCachedInitialAssetsByCall(string $callSid): void
    {
        Cache::forget($this->getInitialAssetsCallKey($callSid));
    }

    /**
     * Interpretar respuestas naturales del conductor
     */
    private function interpretDTMFResponse($userInput)
    {
        $input = trim(strtolower($userInput));
        
        // Respuestas de aceptación (naturales colombianas)
        $acceptWords = ['si', 'sí', 'acepto', 'perfecto', 'bueno', 'ok', 'claro', 
                       'por supuesto', 'me parece', 'me sirve', 'de acuerdo', 
                       'está bien', 'confirmado', 'aceptado', '1'];
        
        foreach ($acceptWords as $word) {
            if (strpos($input, $word) !== false) {
                return [
                    'decision' => 'ACCEPT',
                    'confidence' => 1.0,
                    'response_message' => 'Perfecto, Sr. conductor. Le enviaremos todos los detalles del servicio y la dirección exacta. Gracias por aceptar nuestra propuesta de trabajo.',
                    'needs_more_info' => false,
                    'reason' => 'Aceptó el trabajo - Respuesta natural'
                ];
            }
        }
        
        // Respuestas de rechazo (naturales colombianas)
        $rejectWords = ['no', 'no puedo', 'no me sirve', 'no gracias', 'imposible', 
                       'ahora no', 'ocupado', 'no me interesa', 'no me conviene', 
                       'en otra oportunidad', 'en otro momento', '2'];
        
        foreach ($rejectWords as $word) {
            if (strpos($input, $word) !== false) {
                return [
                    'decision' => 'REJECT',
                    'confidence' => 1.0,
                    'response_message' => 'Entendido perfectamente. Gracias por su tiempo y atención. Que tenga un excelente día.',
                    'needs_more_info' => false,
                    'reason' => 'Rechazó el trabajo - Respuesta natural'
                ];
            }
        }
        
        return null; // No es una respuesta clara
    }

    /**
     * Guardar decisión del conductor en la base de datos
     */
    private function saveDriverDecision($cotizacionId, $driverId, $agentResponse)
    {
        try {
            if (!isset($agentResponse['decision'])) {
                Log::warning("No se pudo guardar decisión: falta clave 'decision'", [
                    'cotizacion_id' => $cotizacionId,
                    'driver_id' => $driverId,
                    'response_keys' => array_keys($agentResponse)
                ]);
                return;
            }

            $decision = $agentResponse['decision'] === 'ACCEPT' ? 1 : 0;
            
            CallDriverDecision::updateOrCreate(
                [
                    'cotizacion_model_id' => $cotizacionId,
                    'driver_id' => $driverId
                ],
                [
                    'decision' => $decision,
                    'updated_at' => now()
                ]
            );
            
            Log::info("Decisión del conductor guardada", [
                'cotizacion_id' => $cotizacionId,
                'driver_id' => $driverId,
                'decision' => $decision ? 'ACCEPT' : 'REJECT',
                'confidence' => $agentResponse['confidence'] ?? 'N/A'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error guardando decisión del conductor: " . $e->getMessage());
        }
    }

    /**
     * Generar respuesta estructurada por defecto
     */
    private function generateDefaultStructuredResponse($userInput)
    {
        $input = strtolower($userInput);
        
        // Palabras de aceptación
        if (preg_match('/\b(sí|si|acepto|está bien|ok|bueno|me interesa|perfecto|listo)\b/i', $input)) {
            return [
                'response_message' => 'Perfecto en unos minutos mi compañera encargada de asignar conductor a las solicitudes de transporte se pondrá en contacto con usted para confirmar valores y asignar ruta muchas gracias',
                'intent' => 'ACCEPT',
                'confidence' => 0.9,
                'end_conversation' => true,
                'driver_interest' => 'interested'
            ];
        }
        
        // Palabras de rechazo
        if (preg_match('/\b(no|no puedo|no me sirve|ocupado|no gracias|no me interesa)\b/i', $input)) {
            return [
                'response_message' => 'Muchas gracias por su tiempo será ya en otra ocasión que tenga un buen día',
                'intent' => 'REJECT',
                'confidence' => 0.9,
                'end_conversation' => true,
                'driver_interest' => 'not_interested'
            ];
        }
        
        // Preguntas sobre detalles
        if (preg_match('/\b(cuánto|valor|precio|qué día|cuándo|dónde|detalles)\b/i', $input)) {
            return [
                'response_message' => 'Los detalles específicos se los confirmará mi compañera cuando se comunique con usted. ¿Le interesa entonces la propuesta?',
                'intent' => 'PROVIDE_INFO',
                'confidence' => 0.8,
                'end_conversation' => false,
                'driver_interest' => 'needs_info'
            ];
        }
        
        // Respuesta por defecto para casos ambiguos
        return [
            'response_message' => 'Disculpe no lo escuché bien. ¿Estaría interesado en esta propuesta de transporte?',
            'intent' => 'CLARIFY',
            'confidence' => 0.5,
            'end_conversation' => false,
            'driver_interest' => 'unclear'
        ];
    }

}
