<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $apiKey;
    protected $organization;
    protected $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->organization = config('services.openai.organization');
    }
    
    /**
     * Generar respuesta a partir de un mensaje utilizando OpenAI
     */
    public function generateResponse($message, $context = [])
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($this->organization)) {
                $headers['OpenAI-Organization'] = $this->organization;
            }
            
            $systemPrompt = "Eres un asistente virtual de Conalca, una empresa especializada en servicios de transporte y logística. " .
                            "Debes responder de manera profesional, concisa y útil. " .
                            "Puedes ayudar con: consultas sobre servicios de transporte, seguimiento de envíos, " .
                            "cotizaciones, información general de la empresa, y resolver dudas de clientes. " .
                            "Mantén un tono amable y profesional en español.";
            
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];
            
            // Añadir contexto previo si existe
            foreach ($context as $entry) {
                $messages[] = $entry;
            }
            
            // Añadir mensaje actual
            $messages[] = ['role' => 'user', 'content' => $message];
            
            Log::info("Enviando solicitud a OpenAI", [
                'message_length' => strlen($message),
                'context_entries' => count($context)
            ]);
            
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => 'gpt-4',
                    'messages' => $messages,
                    'max_tokens' => 200,
                    'temperature' => 0.7,
                    'presence_penalty' => 0.1,
                    'frequency_penalty' => 0.1
                ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                $generatedText = $responseData['choices'][0]['message']['content'] ?? 'Lo siento, no pude generar una respuesta adecuada.';
                
                Log::info("Respuesta generada exitosamente con OpenAI");
                return $generatedText;
            } else {
                Log::error('Error en OpenAI API', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return 'Lo siento, ocurrió un error al procesar su solicitud. Por favor, intente nuevamente.';
            }
        } catch (\Exception $e) {
            Log::error('Excepción en OpenAI API: ' . $e->getMessage());
            return 'Lo siento, experimentamos dificultades técnicas. Por favor, intente más tarde.';
        }
    }
    
    /**
     * Analizar intención del usuario a partir de un texto
     */
    public function analyzeIntent($text)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analiza el siguiente texto y determina la intención del usuario. ' .
                                    'Clasifica ÚNICAMENTE en una de estas categorías: ' .
                                    'consulta_estado, solicitud_servicio, cotizacion, reclamo, informacion_general, emergencia, otro. ' .
                                    'Responde SOLO con la categoría exacta, sin explicaciones adicionales.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 20,
                'temperature' => 0.1,
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                $intent = trim($responseData['choices'][0]['message']['content'] ?? 'otro');
                
                // Validar que la respuesta sea una categoría válida
                $validIntents = ['consulta_estado', 'solicitud_servicio', 'cotizacion', 'reclamo', 'informacion_general', 'emergencia', 'otro'];
                if (!in_array($intent, $validIntents)) {
                    $intent = 'otro';
                }
                
                // Calcular confianza basada en la claridad del mensaje
                $confidence = $this->calculateConfidence($text, $intent);
                
                Log::info("Intención analizada", ['text' => substr($text, 0, 100), 'intent' => $intent, 'confidence' => $confidence]);
                
                return [
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'explanation' => $this->getIntentExplanation($intent)
                ];
            } else {
                Log::error('Error al analizar intención: ' . $response->body());
                return [
                    'intent' => 'otro',
                    'confidence' => 0.1,
                    'explanation' => 'No se pudo determinar la intención debido a un error de API'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Excepción al analizar intención: ' . $e->getMessage());
            return [
                'intent' => 'otro',
                'confidence' => 0.1,
                'explanation' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular nivel de confianza basado en la claridad del mensaje
     */
    private function calculateConfidence($text, $intent)
    {
        $confidence = 0.5; // Base
        
        // Aumentar confianza por palabras clave específicas
        $keywords = [
            'consulta_estado' => ['estado', 'seguimiento', 'tracking', 'donde está', 'ubicación'],
            'solicitud_servicio' => ['necesito', 'quiero', 'solicitar', 'contratar', 'servicio'],
            'cotizacion' => ['cotización', 'precio', 'costo', 'cuanto', 'tarifa'],
            'reclamo' => ['problema', 'queja', 'reclamo', 'mal', 'error'],
            'informacion_general' => ['información', 'qué es', 'servicios', 'empresa'],
            'emergencia' => ['urgente', 'emergencia', 'ayuda', 'problema grave']
        ];
        
        if (isset($keywords[$intent])) {
            foreach ($keywords[$intent] as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    $confidence += 0.2;
                }
            }
        }
        
        // Ajustar por longitud del mensaje
        $length = strlen($text);
        if ($length > 20 && $length < 200) {
            $confidence += 0.1;
        }
        
        return min(1.0, $confidence);
    }
    
    /**
     * Obtener explicación de la intención detectada
     */
    private function getIntentExplanation($intent)
    {
        $explanations = [
            'consulta_estado' => 'El usuario está consultando sobre el estado de un envío o servicio',
            'solicitud_servicio' => 'El usuario quiere solicitar un nuevo servicio de transporte',
            'cotizacion' => 'El usuario está pidiendo información sobre precios o cotizaciones',
            'reclamo' => 'El usuario tiene una queja o problema que reportar',
            'informacion_general' => 'El usuario busca información general sobre la empresa',
            'emergencia' => 'El usuario tiene una situación urgente que requiere atención inmediata',
            'otro' => 'La intención del mensaje no se pudo clasificar claramente'
        ];
        
        return $explanations[$intent] ?? $explanations['otro'];
    }
    
    /**
     * Generar respuesta contextual basada en la intención detectada
     */
    public function generateContextualResponse($text, $intent)
    {
        $contextPrompts = [
            'consulta_estado' => 'El usuario está consultando el estado de un envío o servicio. Proporciona una respuesta profesional explicando cómo pueden verificar el estado de su servicio.',
            'solicitud_servicio' => 'El usuario quiere solicitar un servicio de transporte. Explica brevemente los servicios disponibles y cómo pueden hacer una solicitud.',
            'cotizacion' => 'El usuario está solicitando una cotización. Explica el proceso para obtener una cotización personalizada.',
            'reclamo' => 'El usuario tiene una queja o reclamo. Responde de manera empática y profesional, explicando cómo pueden registrar su reclamo.',
            'informacion_general' => 'El usuario busca información general sobre la empresa. Proporciona información útil sobre Conalca y sus servicios.',
            'emergencia' => 'El usuario tiene una situación urgente. Responde con prioridad y proporciona información de contacto directo.',
            'otro' => 'Responde de manera general y profesional, ofreciendo ayuda adicional.'
        ];
        
        $contextPrompt = $contextPrompts[$intent] ?? $contextPrompts['otro'];
        
        return $this->generateResponse($text, [
            ['role' => 'system', 'content' => $contextPrompt]
        ]);
    }
    
    /**
     * Procesar conversación telefónica con contexto e historial
     */
    public function processConversation($userInput, $conversationContext)
    {
        try {
            Log::info('Processing conversation with OpenAI', [
                'user_input_length' => strlen($userInput),
                'has_context' => !empty($conversationContext),
                'history_length' => count($conversationContext['history'] ?? [])
            ]);
            
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($this->organization)) {
                $headers['OpenAI-Organization'] = $this->organization;
            }
            
            // Construir mensajes para la conversación
            $messages = [];
            
            // Añadir mensaje del sistema (contexto principal)
            $systemMessage = $conversationContext['system_message'] ?? $this->getDefaultSystemPrompt();
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
            
            // Añadir historial de conversación (últimos 10 turnos para no exceder límites)
            $history = $conversationContext['history'] ?? [];
            $recentHistory = array_slice($history, -10); // Solo los últimos 10 mensajes
            
            foreach ($recentHistory as $message) {
                $messages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
            
            // Añadir mensaje actual del usuario
            $messages[] = ['role' => 'user', 'content' => $userInput];
            
            $response = Http::withHeaders($headers)
                ->timeout(25) // Timeout más corto para conversaciones telefónicas
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => config('services.openai.model', 'gpt-4-turbo-preview'),
                    'messages' => $messages,
                    'max_tokens' => 150, // Respuestas más cortas para conversaciones telefónicas
                    'temperature' => 0.7,
                    'presence_penalty' => 0.2,
                    'frequency_penalty' => 0.1,
                    'stop' => ["\n\n", "---"] // Detener en saltos de línea dobles
                ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                $aiResponse = trim($responseData['choices'][0]['message']['content'] ?? '');
                
                // Verificar que la respuesta no esté vacía
                if (empty($aiResponse)) {
                    $aiResponse = 'Disculpa, no pude procesar tu solicitud correctamente. ¿Podrías repetir tu pregunta?';
                }
                
                // Limpiar respuesta para conversación telefónica
                $aiResponse = $this->cleanResponseForPhone($aiResponse);
                
                Log::info('OpenAI conversation response generated', [
                    'response_length' => strlen($aiResponse),
                    'token_usage' => $responseData['usage'] ?? null
                ]);
                
                return $aiResponse;
                
            } else {
                Log::error('Error en OpenAI conversation API', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return 'Lo siento, estoy teniendo dificultades para procesar tu solicitud. ¿Podrías intentar de nuevo?';
            }
            
        } catch (\Exception $e) {
            Log::error('Exception in OpenAI processConversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 'Disculpa, estoy experimentando problemas técnicos. ¿Te puedo transferir con un asesor?';
        }
    }
    
    /**
     * Limpiar respuesta para conversación telefónica
     */
    protected function cleanResponseForPhone($response)
    {
        // Remover markdown y caracteres especiales
        $response = preg_replace('/\*\*(.*?)\*\*/', '$1', $response); // Remover bold
        $response = preg_replace('/\*(.*?)\*/', '$1', $response); // Remover italics
        $response = preg_replace('/`(.*?)`/', '$1', $response); // Remover code
        $response = str_replace(['#', '-', '*', '_'], '', $response); // Remover caracteres especiales
        
        // Limpiar espacios extras y saltos de línea
        $response = preg_replace('/\s+/', ' ', $response);
        $response = trim($response);
        
        // Limitar longitud para conversaciones telefónicas (máximo 200 caracteres)
        if (strlen($response) > 200) {
            $response = substr($response, 0, 197) . '...';
        }
        
        return $response;
    }
    
    /**
     * Obtener prompt por defecto para conversaciones telefónicas
     */
    protected function getDefaultSystemPrompt()
    {
        return "Eres Andrea, asistente virtual de CONALCA, empresa líder en transporte y logística en Colombia. 
        Estás en una conversación telefónica, por lo que:
        - Mantén respuestas MUY BREVES (máximo 2-3 oraciones)
        - Usa lenguaje natural y conversacional
        - No menciones enlaces, formularios o elementos visuales
        - Si necesitas información específica, pregunta directamente
        - Para cotizaciones: pregunta origen, destino, tipo de carga, peso aproximado
        - Si el cliente parece frustrado, ofrece transferir a un humano
        - Detecta despedidas y responde apropiadamente";
    }
    
    /**
     * Resumir conversación para contexto
     */
    public function summarizeConversation($messages)
    {
        try {
            $conversationText = '';
            foreach ($messages as $message) {
                $role = $message['role'] === 'user' ? 'Cliente' : 'Asistente';
                $conversationText .= "{$role}: {$message['content']}\n";
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Resume la siguiente conversación en máximo 2-3 oraciones, ' .
                                    'enfocándote en los puntos clave y la resolución proporcionada.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $conversationText
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.3,
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                return $responseData['choices'][0]['message']['content'] ?? 'Conversación resumida.';
            } else {
                return 'No se pudo resumir la conversación.';
            }
        } catch (\Exception $e) {
            Log::error('Error al resumir conversación: ' . $e->getMessage());
            return 'Error al resumir conversación.';
        }
    }
}
