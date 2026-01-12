<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ConversationSession;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de Asistente IA con MCP (Model Context Protocol)
 * 
 * Este servicio usa OpenAI ChatGPT API directamente
 * y se comunica con el servidor MCP en https://conalcaia.conalca.com.co/mcp/
 * 
 * Funcionalidades:
 * - Chat completions con OpenAI ChatGPT
 * - Integración con herramientas MCP
 * - Gestión de conversaciones persistentes
 * - Tool calling para create_cotizacion, search_products, get_empaques
 */
class MCPAssistantService
{
    // Groq Configuration (para chat agéntico)
    private static $groq_api_key = null;
    private static $groq_model = null;
    
    // OpenAI Configuration (para otros servicios)
    private static $openai_api_key = null;
    private static $openai_model = null;
    private static $reasoning_effort = 'medium'; // GPT-5 reasoning: none, low, medium, high
    
    // Proveedor activo para chat
    private static $chat_provider = 'groq'; // 'groq' o 'openai'
    private static $mcp_base_url = null;

    /**
     * Inicializar configuraciones
     */
    private static function initConfig()
    {
        if (!self::$groq_api_key) {
            // Groq Configuration (proveedor principal para chat agéntico)
            self::$groq_api_key = env('GROQ_API_KEY');
            self::$groq_model = env('GROQ_MODEL', 'compound-beta'); // Modelo agéntico de Groq
            
            // OpenAI Configuration (respaldo y otros servicios)
            self::$openai_api_key = env('OPENAI_API_KEY');
            self::$openai_model = env('OPENAI_MODEL', 'gpt-5-mini');
            self::$reasoning_effort = env('OPENAI_REASONING_EFFORT', 'medium');
            
            // Proveedor de chat activo
            self::$chat_provider = env('CHAT_PROVIDER', 'groq');
            self::$mcp_base_url = env('MCP_BASE_URL', 'https://conalcaia.conalca.com.co/mcp/');
            
            Log::info('MCPAssistantService inicializado', [
                'chat_provider' => self::$chat_provider,
                'groq_model' => self::$groq_model,
                'openai_model' => self::$openai_model,
                'mcp_url' => self::$mcp_base_url
            ]);
        }
    }

    /**
     * Obtener o crear thread (conversación) para el cliente
     * En este caso, usamos la tabla conversation_sessions
     */
    public static function getThread(Client $client)
    {
        self::initConfig();
        
        // Buscar o crear sesión de conversación
        $session = ConversationSession::firstOrCreate(
            ['client_id' => $client->id],
            [
                'session_id' => 'mcp_' . $client->id . '_' . time(),
                'status' => 'active',
                'metadata' => json_encode([
                    'client_name' => $client->nombre_cliente,
                    'client_document' => $client->documento_cliente,
                    'created_at' => now()->toIso8601String()
                ])
            ]
        );

        Log::info('Thread obtenido/creado', [
            'client_id' => $client->id,
            'session_id' => $session->session_id
        ]);

        return $session->session_id;
    }

    /**
     * Crear un mensaje en la conversación
     */
    public static function createMessage($threadId, $message, $groupId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            Log::warning('Thread no encontrado en BD, retornando THREAD_NOT_FOUND', [
                'thread_id' => $threadId
            ]);
            return 'THREAD_NOT_FOUND';
        }

        // Guardar mensaje del usuario CON group_cotization_id
        $conversationMessage = ConversationMessage::create([
            'session_id' => $session->id,
            'group_cotization_id' => $groupId, // 🆕 Guardar group_id
            'role' => 'user',
            'content' => $message,
            'timestamp' => now()
        ]);

        Log::info('Mensaje creado', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'message_id' => $conversationMessage->id,
            'message_length' => strlen($message)
        ]);

        return $conversationMessage;
    }

    /**
     * Ejecutar el asistente - procesar mensajes y obtener respuesta
     */
    public static function runAssistant($threadId, $typeBusiness = 'dta', $groupId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            Log::warning('Thread no encontrado en runAssistant, retornando THREAD_NOT_FOUND', [
                'thread_id' => $threadId
            ]);
            return 'THREAD_NOT_FOUND';
        }
        
        // 🆕 Guardar group_id en la sesión para usar en mensajes posteriores
        if ($groupId) {
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['current_group_id'] = $groupId;
            $session->metadata = json_encode($metadata);
            $session->save();
            
            Log::info('Group ID guardado en sesión', [
                'thread_id' => $threadId,
                'group_id' => $groupId
            ]);
        }

        // Obtener historial de mensajes FILTRADO POR GRUPO
        $messages = self::getMessagesArray($threadId, $groupId);

        // Detectar empaque mencionado en el último mensaje del usuario
        $detectedEmpaque = self::detectEmpaqueInMessage($messages);
        
        // NUEVO: Extraer TODOS los datos del último mensaje
        $extractedData = self::extractAllDataFromMessage($messages);

        // 🆕 DETECTAR MÚLTIPLES RUTAS y guardar en metadata
        $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
        if ($isMultiRoute) {
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['multi_route_data'] = $extractedData;
            $metadata['is_multi_route'] = true;
            $metadata['route_count'] = count($extractedData);
            $session->metadata = json_encode($metadata);
            $session->save();
            
            Log::info('🚚 MÚLTIPLES RUTAS detectadas y guardadas en metadata', [
                'thread_id' => $threadId,
                'route_count' => count($extractedData),
                'routes' => $extractedData
            ]);
        }

        // NUEVO: Si se detectó empaque, obtener su ID de la base de datos
        $dataToCheck = $isMultiRoute ? $extractedData[0] : $extractedData;
        if (!empty($dataToCheck['empaque'])) {
            $empaqueId = self::getEmpaqueIdFromDB($dataToCheck['empaque']);
            if ($empaqueId) {
                if ($isMultiRoute) {
                    // Aplicar empaque_id a todas las rutas
                    foreach ($extractedData as &$ruta) {
                        $ruta['empaque_id'] = $empaqueId;
                    }
                    unset($ruta);
                } else {
                    $extractedData['empaque_id'] = $empaqueId;
                }
                Log::info('Embalaje detectado y consultado en BD', [
                    'empaque' => $dataToCheck['empaque'],
                    'empaque_id' => $empaqueId,
                    'is_multi_route' => $isMultiRoute
                ]);
            }
        }

        // Construir system prompt según tipo de negocio
        $systemPrompt = self::getSystemPrompt($typeBusiness, $detectedEmpaque, $extractedData);

        // Agregar system prompt al inicio
        array_unshift($messages, [
            'role' => 'system',
            'content' => $systemPrompt
        ]);

        // Definir las herramientas MCP disponibles
        $tools = self::getMCPTools();

        Log::info('Ejecutando asistente MCP', [
            'thread_id' => $threadId,
            'type_business' => $typeBusiness,
            'messages_count' => count($messages),
            'tools_count' => count($tools),
            'detected_empaque' => $detectedEmpaque,
            'extracted_fields' => count($extractedData)
        ]);

        try {
            // Seleccionar proveedor de IA según configuración
            if (self::$chat_provider === 'groq') {
                // 🚀 GROQ API con modelo agéntico compound-beta
                $payload = [
                    'model' => self::$groq_model,
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 4096, // Groq soporta más tokens
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                ];
                
                Log::info('Groq API Request (agéntico)', [
                    'model' => self::$groq_model,
                    'provider' => 'groq',
                    'messages_count' => count($messages)
                ]);

                $response = Http::timeout(60) // Más tiempo para compound-beta
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . self::$groq_api_key,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload)
                    ->json();
            } else {
                // OpenAI GPT-5 API (respaldo)
                $payload = [
                    'model' => self::$openai_model,
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'parallel_tool_calls' => true,
                ];
                
                Log::info('OpenAI GPT-5 API Request', [
                    'model' => self::$openai_model,
                    'provider' => 'openai'
                ]);

                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . self::$openai_api_key,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', $payload)
                    ->json();
            }
            
            $assistantMessage = $response['choices'][0]['message'] ?? null;

            Log::info('Mensaje del asistente ChatGPT recibido', [
                'has_tool_calls' => isset($assistantMessage['tool_calls']) && !empty($assistantMessage['tool_calls']),
                'has_content' => !empty($assistantMessage['content'])
            ]);

            // Verificar si hay tool calls
            if (isset($assistantMessage['tool_calls']) && !empty($assistantMessage['tool_calls'])) {
                return self::processToolCalls($threadId, $assistantMessage, $response, $extractedData);
            }

            // Si no hay tool calls, guardar respuesta normal
            if (isset($assistantMessage['content'])) {
                // 🆕 Obtener group_id de la metadata de la sesión
                $metadata = json_decode($session->metadata ?? '{}', true);
                $currentGroupId = $metadata['current_group_id'] ?? null;
                
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $currentGroupId, // 🆕 Guardar group_id
                    'role' => 'assistant',
                    'content' => $assistantMessage['content'],
                    'timestamp' => now()
                ]);
            }

            // Crear run ID ficticio para compatibilidad
            $runId = 'run_mcp_' . time();
            
            Log::info('Creando run_id para respuesta con datos extraídos', [
                'run_id' => $runId,
                'thread_id' => $threadId,
                'extracted_data_count' => count($extractedData)
            ]);
            
            // Guardar run_id y datos extraídos en metadata de sesión
            $metadata = json_decode($session->metadata ?? '{}', true);
            $metadata['last_run_id'] = $runId;
            $metadata['last_run_status'] = 'completed';
            $metadata['last_run_at'] = now()->toIso8601String();
            $metadata['extracted_data'] = $extractedData;
            $session->metadata = json_encode($metadata);
            $session->save();

            return [
                'id' => $runId,
                'status' => 'completed',
                'extracted_data' => $extractedData  // NUEVO: enviar datos al frontend
            ];

        } catch (\Exception $e) {
            Log::error('Error ejecutando asistente MCP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'thread_id' => $threadId
            ]);
            throw $e;
        }
    }

    /**
     * Procesar tool calls de OpenAI
     */
    private static function processToolCalls($threadId, $assistantMessage, $openaiResponse, $extractedData = [])
    {
        $session = ConversationSession::where('session_id', $threadId)->first();
        $toolCalls = $assistantMessage['tool_calls'];

        Log::info('Procesando tool calls', [
            'thread_id' => $threadId,
            'tool_calls_count' => count($toolCalls)
        ]);

        $toolResults = [];
        $hasMultipleProducts = false;
        $searchProductsFound = false;

        // PRIMERA PASADA: Detectar si hay búsqueda de productos con múltiples resultados
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            
            if (empty($arguments) || (is_array($arguments) && count($arguments) === 0)) {
                $arguments = new \stdClass();
            }

            Log::info('Pre-validación herramienta MCP', [
                'function' => $functionName,
                'arguments' => $arguments
            ]);

            // Ejecutar search_products para validar
            if ($functionName === 'search_products') {
                $tempResult = self::callMCPTool($functionName, $arguments);
                $searchProductsFound = true;
                
                // Verificar si devolvió múltiples productos
                if (isset($tempResult['productos']) && is_array($tempResult['productos'])) {
                    $productCount = count($tempResult['productos']);
                    if ($productCount > 1) {
                        $hasMultipleProducts = true;
                        Log::warning('⚠️ VALIDACIÓN: Se encontraron múltiples productos', [
                            'cantidad' => $productCount,
                            'bloqueando_create_cotizacion' => true
                        ]);
                    }
                }
            }
        }

        // SEGUNDA PASADA: Ejecutar herramientas con validación
        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            
            if (empty($arguments) || (is_array($arguments) && count($arguments) === 0)) {
                $arguments = new \stdClass();
            }

            // ⛔ BLOQUEAR create_cotizacion si hay múltiples productos sin seleccionar
            if ($functionName === 'create_cotizacion' && $hasMultipleProducts) {
                Log::error('⛔ BLOQUEADO: Intento de crear cotización con múltiples productos sin seleccionar', [
                    'productos_encontrados' => 'múltiples',
                    'accion' => 'bloqueado'
                ]);
                
                $result = [
                    'error' => 'VALIDACIÓN REQUERIDA',
                    'message' => 'Hay múltiples opciones de productos disponibles. El usuario debe seleccionar una opción específica antes de crear la cotización.',
                    'blocked' => true
                ];
                
                $toolResults[] = [
                    'tool_call_id' => $toolCall['id'],
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode($result)
                ];
                
                // 🆕 Obtener group_id de la metadata
                $metadata = json_decode($session->metadata ?? '{}', true);
                $currentGroupId = $metadata['current_group_id'] ?? null;
                
                ConversationMessage::create([
                    'session_id' => $session->id,
                    'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
                    'role' => 'tool',
                    'content' => json_encode($result),
                    'metadata' => json_encode([
                        'tool_call_id' => $toolCall['id'],
                        'function' => $functionName,
                        'arguments' => $arguments,
                        'blocked' => true,
                        'reason' => 'multiple_products_pending_selection'
                    ]),
                    'timestamp' => now()
                ]);
                
                continue; // Saltar al siguiente tool call
            }

            Log::info('Ejecutando herramienta MCP', [
                'function' => $functionName,
                'arguments' => $arguments
            ]);

            // Llamar a la herramienta MCP
            $result = self::callMCPTool($functionName, $arguments);

            $toolResults[] = [
                'tool_call_id' => $toolCall['id'],
                'role' => 'tool',
                'name' => $functionName,
                'content' => json_encode($result)
            ];

            // 🆕 Obtener group_id de la metadata
            $metadata = json_decode($session->metadata ?? '{}', true);
            $currentGroupId = $metadata['current_group_id'] ?? null;

            // Guardar la ejecución de la herramienta CON tool_call_id
            ConversationMessage::create([
                'session_id' => $session->id,
                'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
                'role' => 'tool',
                'content' => json_encode($result),
                'metadata' => json_encode([
                    'tool_call_id' => $toolCall['id'],
                    'function' => $functionName,
                    'arguments' => $arguments
                ]),
                'timestamp' => now()
            ]);
        }

        // 🆕 Obtener group_id de la metadata para mensaje final
        $metadata = json_decode($session->metadata ?? '{}', true);
        $currentGroupId = $metadata['current_group_id'] ?? null;

        // Guardar el mensaje del asistente con tool calls
        ConversationMessage::create([
            'session_id' => $session->id,
            'group_cotization_id' => $currentGroupId, // 🆕 Agregar group_id
            'role' => 'assistant',
            'content' => json_encode([
                'tool_calls' => $toolCalls
            ]),
            'timestamp' => now()
        ]);

        // Crear run ID
        $runId = 'run_mcp_tools_' . time();
        
        // Combinar datos extraídos del mensaje con datos de tool calls
        $quoteDataFromTools = self::extractQuoteData($toolResults);
        $mergedData = array_merge($extractedData, $quoteDataFromTools);
        
        // Determinar si hay datos disponibles (mergedData O extracted_data)
        $hasData = !empty($mergedData) || !empty($extractedData);
        
        $metadata = json_decode($session->metadata ?? '{}', true);
        $metadata['last_run_id'] = $runId;
        $metadata['last_run_status'] = $hasData ? 'completed_with_data' : 'completed';
        $metadata['last_run_at'] = now()->toIso8601String();
        $metadata['tool_results'] = $toolResults;
        
        // CRÍTICO: Guardar tanto quote_data como extracted_data
        if (!empty($mergedData)) {
            $metadata['quote_data'] = $mergedData;
        }
        
        // NUEVO: Siempre guardar extracted_data si existe
        if (!empty($extractedData)) {
            $metadata['extracted_data'] = $extractedData;
            Log::info('Guardando extracted_data en metadata', [
                'run_id' => $runId,
                'extracted_count' => count($extractedData)
            ]);
        }
        
        $session->metadata = json_encode($metadata);
        $session->save();

        Log::info('Tool calls procesados exitosamente', [
            'run_id' => $runId,
            'has_data' => $hasData,
            'has_quote_data' => !empty($mergedData),
            'has_extracted_data' => !empty($extractedData),
            'tools_executed' => count($toolResults),
            'extracted_count' => count($extractedData),
            'merged_count' => count($mergedData)
        ]);

        return [
            'id' => $runId,
            'status' => $hasData ? 'completed_with_data' : 'completed',
            'quote_data' => $mergedData,
            'extracted_data' => $extractedData  // NUEVO: incluir datos extraídos por regex
        ];
    }

    /**
     * Llamar a una herramienta del servidor MCP
     */
    private static function callMCPTool($toolName, $arguments)
    {
        try {
            Log::info('Llamando herramienta MCP', [
                'tool' => $toolName,
                'mcp_url' => self::$mcp_base_url
            ]);

            // JSON-RPC 2.0 format según protocolo MCP
            $payload = [
                'jsonrpc' => '2.0',
                'id' => uniqid(),
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments
                ]
            ];

            Log::info('Payload MCP', ['payload' => $payload]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::$mcp_base_url, $payload);

            if (!$response->successful()) {
                Log::warning('Error llamando herramienta MCP', [
                    'tool' => $toolName,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['error' => 'Error al llamar herramienta MCP'];
            }

            $data = $response->json();
            
            Log::info('Respuesta MCP recibida', [
                'tool' => $toolName,
                'response' => $data
            ]);

            // Respuesta JSON-RPC 2.0: extraer el texto del content
            if (isset($data['result']['content'][0]['text'])) {
                $textResult = $data['result']['content'][0]['text'];
                return json_decode($textResult, true) ?? $textResult;
            }
            
            return $data['result'] ?? $data;

        } catch (\Exception $e) {
            Log::error('Excepción llamando herramienta MCP', [
                'tool' => $toolName,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Extraer datos de cotización de los resultados de las herramientas
     */
    private static function extractQuoteData($toolResults)
    {
        $quoteData = [];

        foreach ($toolResults as $result) {
            $content = json_decode($result['content'], true);
            
            // Si la herramienta es create_cotizacion, extraer los datos
            if ($result['name'] === 'create_cotizacion' && isset($content['cotizacion'])) {
                $quoteData[] = $content['cotizacion'];
            }
        }

        return $quoteData;
    }

    /**
     * Obtener mensajes de una conversación
     */
    public static function getMessages($threadId, $groupId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            \Log::warning('Session no encontrada para threadId', ['thread_id' => $threadId]);
            return [];
        }

        // Filtrar mensajes por session_id y opcionalmente por group_id
        $query = ConversationMessage::where('session_id', $session->id);
        
        if ($groupId) {
            $query->where('group_cotization_id', $groupId);
            \Log::info('Filtrando mensajes por group_id', [
                'session_id' => $session->id,
                'group_id' => $groupId
            ]);
        }
        
        $messages = $query->orderBy('timestamp', 'asc')->get();
        
        \Log::info('Mensajes recuperados', [
            'thread_id' => $threadId,
            'group_id' => $groupId,
            'count' => $messages->count()
        ]);

        return $messages->map(function($msg) {
            // Si es un mensaje de función, no mostrarlo al usuario
            if ($msg->role === 'function') {
                return null;
            }
            
            // Si es un mensaje de tool, formatearlo para el frontend
            if ($msg->role === 'tool') {
                $metadata = json_decode($msg->metadata ?? '{}', true);
                $result = json_decode($msg->content, true);
                
                return [
                    'role' => 'assistant',
                    'text' => json_encode([
                        'function' => $metadata['function'] ?? 'unknown',
                        'arguments' => $metadata['arguments'] ?? [],
                        'result' => $result
                    ]),
                    'created_at' => $msg->timestamp->format('H:i'),
                    'status' => 'sent'
                ];
            }

            // Si el contenido es JSON de tool_calls, extraer el contenido real
            $content = $msg->content;
            if (is_string($content) && str_starts_with($content, '{')) {
                $decoded = json_decode($content, true);
                if (isset($decoded['tool_calls'])) {
                    $content = "Procesando información...";
                }
            }

            return [
                'role' => $msg->role,
                'text' => $content,
                'created_at' => $msg->timestamp->format('H:i'),
                'status' => 'sent'
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Obtener mensajes como array para OpenAI
     * @param string $threadId ID del thread
     * @param int|null $groupId ID del grupo de cotización para filtrar mensajes
     */
    private static function getMessagesArray($threadId, $groupId = null)
    {
        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            return [];
        }

        // 🔴 FILTRAR POR group_cotization_id si se proporciona
        $query = ConversationMessage::where('session_id', $session->id)
            ->where('role', '!=', 'function'); // Excluir mensajes de función
        
        if ($groupId) {
            $query->where('group_cotization_id', $groupId);
            Log::info('Filtrando mensajes por grupo', [
                'thread_id' => $threadId,
                'group_id' => $groupId
            ]);
        }
        
        $messages = $query->orderBy('timestamp', 'asc')->get();

        $result = [];
        $pendingToolCalls = [];
        $tempAssistantWithTools = null;

        foreach ($messages as $msg) {
            $content = $msg->content;
            
            // Si tenemos un assistant con tool_calls pendiente y llega un mensaje que NO es tool
            if ($tempAssistantWithTools && $msg->role !== 'tool') {
                // Descartar el assistant incompleto
                Log::warning('Descartando assistant con tool_calls incompletos', [
                    'expected_calls' => count($pendingToolCalls),
                    'session_id' => $session->id
                ]);
                $tempAssistantWithTools = null;
                $pendingToolCalls = [];
            }
            
            // Si es un mensaje de tool
            if ($msg->role === 'tool') {
                if (empty($pendingToolCalls)) {
                    // No hay tool_calls pendientes, saltar mensaje huérfano
                    Log::warning('Mensaje tool huérfano omitido', [
                        'message_id' => $msg->id,
                        'session_id' => $session->id
                    ]);
                    continue;
                }
                
                $metadata = json_decode($msg->metadata ?? '{}', true);
                $toolCallId = $metadata['tool_call_id'] ?? '';
                
                // Verificar si este tool_call_id está en los pendientes
                $key = array_search($toolCallId, $pendingToolCalls);
                if ($key !== false) {
                    unset($pendingToolCalls[$key]);
                }
                
                // Agregar el mensaje tool (temporal)
                if ($tempAssistantWithTools) {
                    $tempAssistantWithTools['tool_responses'][] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCallId,
                        'content' => $content
                    ];
                    
                    // Si ya tenemos todas las respuestas, agregar todo
                    if (empty($pendingToolCalls)) {
                        $result[] = $tempAssistantWithTools['assistant'];
                        foreach ($tempAssistantWithTools['tool_responses'] as $toolResp) {
                            $result[] = $toolResp;
                        }
                        $tempAssistantWithTools = null;
                    }
                }
                continue;
            }
            
            // Si es JSON de tool_calls
            if (is_string($content) && str_starts_with($content, '{')) {
                $decoded = json_decode($content, true);
                if (isset($decoded['tool_calls']) && is_array($decoded['tool_calls'])) {
                    // Guardar temporalmente y esperar las respuestas
                    $tempAssistantWithTools = [
                        'assistant' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => $decoded['tool_calls']
                        ],
                        'tool_responses' => []
                    ];
                    
                    // Extraer los IDs de tool_calls esperados
                    $pendingToolCalls = array_column($decoded['tool_calls'], 'id');
                    continue;
                }
            }

            // Mensaje normal (user o assistant sin tool_calls)
            $result[] = [
                'role' => $msg->role,
                'content' => $content
            ];
        }

        return $result;
    }

    /**
     * Verificar estado de un run
     */
    public static function checkRunStatus($threadId, $runId)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            return [
                'status' => 'failed',
                'error' => 'Thread no encontrado'
            ];
        }

        $metadata = json_decode($session->metadata ?? '{}', true);
        
        if (isset($metadata['last_run_id']) && $metadata['last_run_id'] === $runId) {
            $status = $metadata['last_run_status'] ?? 'completed';
            
            // Determinar si hay datos (quote_data O extracted_data)
            $hasQuoteData = isset($metadata['quote_data']) && !empty($metadata['quote_data']);
            $hasExtractedData = isset($metadata['extracted_data']) && !empty($metadata['extracted_data']);
            $hasAnyData = $hasQuoteData || $hasExtractedData;
            
            // Actualizar status basado en disponibilidad de datos
            if ($status === 'completed' && $hasAnyData) {
                $status = 'completed_with_data';
                Log::info('checkRunStatus: Actualizando status a completed_with_data', [
                    'run_id' => $runId,
                    'has_quote_data' => $hasQuoteData,
                    'has_extracted_data' => $hasExtractedData
                ]);
            }
            
            $result = [
                'status' => $status,
                'thread_id' => $threadId,
                'run_id' => $runId
            ];

            // Si hay datos de cotización, incluirlos
            if ($hasQuoteData) {
                $result['quote_data'] = $metadata['quote_data'];
            }

            // NUEVO: Incluir datos extraídos automáticamente si existen
            if ($hasExtractedData) {
                // NORMALIZAR datos antes de devolver
                $extractedData = $metadata['extracted_data'];
                
                // � MEJORAR DETECCIÓN: Verificar si es array de rutas o una ruta única
                // Un array de rutas tiene claves numéricas (0, 1, 2...)
                // Una ruta única tiene claves con nombres ('origen', 'destino', 'peso_kg'...)
                $keys = array_keys($extractedData);
                $allNumericKeys = !empty($keys) && count(array_filter($keys, 'is_int')) === count($keys);
                $firstElement = reset($extractedData);
                
                // Es multi-ruta si:
                // 1. Todas las claves son numéricas (0, 1, 2...)
                // 2. Y el primer elemento es un array con datos de ruta
                $isMultiRoute = $allNumericKeys && is_array($firstElement) && 
                    (isset($firstElement['origen']) || isset($firstElement['destino']) || 
                     isset($firstElement['peso_kg']) || isset($firstElement['producto']));
                
                Log::info('checkRunStatus: Detectando tipo de datos', [
                    'keys_sample' => array_slice($keys, 0, 5),
                    'all_numeric_keys' => $allNumericKeys,
                    'first_element_is_array' => is_array($firstElement),
                    'is_multi_route' => $isMultiRoute,
                    'total_elements' => count($extractedData)
                ]);
                
                if (!$isMultiRoute) {
                    // Es una ruta única - convertir a array de una ruta
                    $extractedData = [$extractedData];
                    Log::info('checkRunStatus: Convertido a array de 1 ruta');
                }
                
                foreach ($extractedData as $index => $ruta) {
                    if (!is_array($ruta)) continue; // Saltar si no es un array válido
                    
                    // Normalizar valor_mercancia -> valor_declarado
                    if (isset($ruta['valor_mercancia']) && !isset($ruta['valor_declarado'])) {
                        $extractedData[$index]['valor_declarado'] = $ruta['valor_mercancia'];
                    }
                    // Normalizar producto -> tipo_producto
                    if (isset($ruta['producto']) && !isset($ruta['tipo_producto'])) {
                        $extractedData[$index]['tipo_producto'] = $ruta['producto'];
                    }
                }
                
                $result['extracted_data'] = $extractedData;
                Log::info('checkRunStatus: Incluyendo extracted_data normalizado', [
                    'run_id' => $runId,
                    'extracted_count' => count($extractedData),
                    'is_multi_route' => $isMultiRoute,
                    'primer_ruta' => $extractedData[0] ?? null
                ]);
            }
            
            if ($status === 'completed_with_data') {
                Log::info('checkRunStatus: Run completado CON datos', [
                    'run_id' => $runId,
                    'quote_data_count' => $hasQuoteData ? count($metadata['quote_data']) : 0,
                    'extracted_data_count' => $hasExtractedData ? count($metadata['extracted_data']) : 0
                ]);
            }

            return $result;
        }

        return [
            'status' => 'completed',
            'thread_id' => $threadId,
            'run_id' => $runId
        ];
    }

    /**
     * Limpiar/resetear una conversación
     */
    public static function clearThread($threadId, $clientId = null)
    {
        self::initConfig();

        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if ($session) {
            // Eliminar mensajes
            ConversationMessage::where('session_id', $session->id)->delete();
            
            // Resetear metadata
            $session->metadata = json_encode([
                'cleared_at' => now()->toIso8601String()
            ]);
            $session->save();

            Log::info('Thread limpiado', [
                'thread_id' => $threadId,
                'session_id' => $session->id
            ]);
        }

        return true;
    }

    /**
     * Definir herramientas MCP disponibles en formato OpenAI
     */
    private static function getMCPTools()
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_cotizacion',
                    'description' => 'Crea una nueva cotización de transporte con todos los datos recopilados del usuario',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'pricing_id' => [
                                'type' => 'integer',
                                'description' => 'ID del pricing a usar (por defecto: 43214)',
                                'default' => 43214
                            ],
                            'ciudad_origen' => [
                                'type' => 'string',
                                'description' => 'Ciudad de origen del envío (ej: Bogotá, Medellín)'
                            ],
                            'ciudad_destino' => [
                                'type' => 'string',
                                'description' => 'Ciudad de destino del envío'
                            ],
                            'peso_mercancia' => [
                                'type' => 'string',
                                'description' => 'Peso de la mercancía en kilogramos (ej: "500", "1500")'
                            ],
                            'cantidad' => [
                                'type' => 'string',
                                'description' => 'Cantidad de unidades o bultos'
                            ],
                            'tipo_embajale' => [
                                'type' => 'string',
                                'description' => 'Tipo de embalaje (ej: CAJA, PALLET, ESTIBA, SACO)'
                            ],
                            'tipo_producto' => [
                                'type' => 'string',
                                'description' => 'Tipo o nombre del producto a transportar'
                            ],
                            'vehiculo_requerido' => [
                                'type' => 'string',
                                'description' => 'Tipo de vehículo requerido (ej: Turbo, Sencillo, Camión)'
                            ],
                            'valor_declarado' => [
                                'type' => 'string',
                                'description' => 'Valor declarado de la mercancía en pesos colombianos'
                            ]
                        ],
                        'required' => ['ciudad_origen', 'ciudad_destino', 'peso_mercancia']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Busca productos en el catálogo para ayudar al usuario a especificar el tipo de mercancía',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'search_term' => [
                                'type' => 'string',
                                'description' => 'Término de búsqueda para encontrar productos (ej: "neumaticos", "alimentos", "electrodomésticos")'
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Número máximo de resultados a devolver',
                                'default' => 10
                            ]
                        ],
                        'required' => ['search_term']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_empaques',
                    'description' => 'Obtiene la lista de tipos de empaque disponibles en el sistema',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'filter' => [
                                'type' => 'string',
                                'description' => 'Filtro opcional para buscar empaques específicos'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtener system prompt según tipo de negocio
     */
    /**
     * Detectar si el usuario mencionó un tipo de empaque en su mensaje
     */
    private static function detectEmpaqueInMessage($messages)
    {
        // Mapeo de palabras clave a nombres de empaques
        $empaqueKeywords = [
            'caja' => 'CAJAS',
            'cajas' => 'CAJAS',
            'paquete' => 'PAQUETES',
            'paquetes' => 'PAQUETES',
            'bulto' => 'BULTOS',
            'bultos' => 'BULTOS',
            'estiba' => 'ESTIBAS',
            'estibas' => 'ESTIBAS',
            'pallet' => 'PALLET',
            'pallets' => 'PALLET',
            'saco' => 'SACOS',
            'sacos' => 'SACOS',
            'tonel' => 'TONEL',
            'toneles' => 'TONEL',
            'contenedor' => 'CONTENEDOR',
            'contenedores' => 'CONTENEDOR',
            'granel' => 'GRANEL',
            'cilindro' => 'CILINDROS',
            'cilindros' => 'CILINDROS',
            'rollo' => 'ROLLOS',
            'rollos' => 'ROLLOS',
            'bolsa' => 'BOLSAS',
            'bolsas' => 'BOLSAS',
            'guacal' => 'GUACALES',
            'guacales' => 'GUACALES',
        ];

        // Buscar en los últimos 3 mensajes del usuario
        $userMessages = array_filter($messages, function($msg) {
            return isset($msg['role']) && $msg['role'] === 'user';
        });

        // Tomar los últimos 3 mensajes del usuario
        $recentUserMessages = array_slice($userMessages, -3);

        foreach ($recentUserMessages as $message) {
            $text = strtolower($message['content']);
            
            // Buscar palabras clave de empaque
            foreach ($empaqueKeywords as $keyword => $empaqueName) {
                if (strpos($text, $keyword) !== false) {
                    Log::info('Empaque detectado automáticamente', [
                        'keyword' => $keyword,
                        'empaque' => $empaqueName,
                        'message' => substr($text, 0, 100)
                    ]);
                    return $empaqueName;
                }
            }
        }

        return null;
    }

    /**
     * Obtener empaque desde la base de datos con búsqueda fuzzy mejorada
     * Retorna array con id, nome o null si no encuentra
     */
    private static function getEmpaqueFromDB($empaqueName)
    {
        try {
            // Normalizar nombre
            $nombreNormalizado = strtoupper(trim($empaqueName));
            
            // 1️⃣ Intentar coincidencia exacta primero
            $empaque = \DB::table('tb_empaque')
                ->whereRaw('UPPER(nome) = ?', [$nombreNormalizado])
                ->first();

            // 2️⃣ Si no hay coincidencia exacta, buscar por LIKE
            if (!$empaque) {
                $empaque = \DB::table('tb_empaque')
                    ->whereRaw('UPPER(nome) LIKE ?', ['%' . $nombreNormalizado . '%'])
                    ->first();
            }

            // 3️⃣ Si aún no hay coincidencia, buscar por palabras individuales
            if (!$empaque) {
                $palabras = explode(' ', $nombreNormalizado);
                $query = \DB::table('tb_empaque');
                
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) >= 3) { // Solo palabras de 3+ caracteres
                        $query->where('nome', 'LIKE', '%' . $palabra . '%');
                    }
                }
                
                $empaque = $query->first();
            }
            
            // 4️⃣ Búsqueda fuzzy por similitud (levenshtein)
            if (!$empaque) {
                $todosEmpaques = \DB::table('tb_empaque')->get();
                $mejorMatch = null;
                $mejorSimilitud = 0;
                
                foreach ($todosEmpaques as $emp) {
                    similar_text(strtoupper($emp->nome), $nombreNormalizado, $percent);
                    if ($percent > $mejorSimilitud && $percent >= 60) { // Mínimo 60% similitud
                        $mejorSimilitud = $percent;
                        $mejorMatch = $emp;
                    }
                }
                
                if ($mejorMatch) {
                    $empaque = $mejorMatch;
                    Log::info('📦 Empaque encontrado por similitud', [
                        'buscado' => $empaqueName,
                        'encontrado' => $mejorMatch->nome,
                        'similitud' => round($mejorSimilitud, 2) . '%'
                    ]);
                }
            }

            if ($empaque) {
                Log::info('✅ Empaque encontrado en BD', [
                    'nombre_buscado' => $empaqueName,
                    'id' => $empaque->id,
                    'nome' => $empaque->nome
                ]);
                
                return [
                    'id' => $empaque->id,
                    'nome' => $empaque->nome
                ];
            }

            Log::warning('⚠️ Empaque NO encontrado en BD', [
                'nombre_buscado' => $empaqueName
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Error consultando empaque en BD', [
                'empaque' => $empaqueName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * LEGACY: Mantener compatibilidad con código existente
     * Obtener solo ID de empaque desde la base de datos
     */
    private static function getEmpaqueIdFromDB($empaqueName)
    {
        $result = self::getEmpaqueFromDB($empaqueName);
        return $result ? $result['id'] : null;
    }

    /**
     * Buscar producto en base de datos
     * @param string $productoName Nombre del producto detectado
     * @return array|null Array con código y nombre del producto, o null si no se encuentra
     */
    private static function getProductoFromDB($productoName)
    {
        try {
            Log::info('Buscando producto en BD', [
                'producto_buscado' => $productoName
            ]);

            // Intentar búsqueda exacta primero
            $producto = \DB::table('products')
                ->where('producto_nombre', 'LIKE', '%' . $productoName . '%')
                ->first();

            // Si no encuentra exacto, buscar por similitud con SOUNDEX o partes del nombre
            if (!$producto) {
                // Separar palabras y buscar por cada una
                $palabras = explode(' ', $productoName);
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) > 3) { // Ignorar palabras muy cortas
                        $producto = \DB::table('products')
                            ->where('producto_nombre', 'LIKE', '%' . $palabra . '%')
                            ->first();
                        if ($producto) break;
                    }
                }
            }

            if ($producto) {
                Log::info('✅ Producto encontrado en BD', [
                    'nombre_buscado' => $productoName,
                    'codigo' => $producto->producto_codigo,
                    'nombre' => $producto->producto_nombre
                ]);
                return [
                    'codigo' => $producto->producto_codigo,
                    'nombre' => $producto->producto_nombre,
                    'tipo' => $producto->tippro_nombre ?? null
                ];
            }

            Log::warning('⚠️ Producto NO encontrado en BD', [
                'nombre_buscado' => $productoName
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Error consultando producto en BD', [
                'producto' => $productoName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 🚚 DETECTAR MÚLTIPLES RUTAS EN EL TEXTO
     * Retorna array de rutas con origen, destino, peso, cantidad y valor EXPLÍCITOS por cada ruta
     */
    private static function detectMultipleRoutes($text)
    {
        $routes = [];
        
        Log::info('🔍 Detectando múltiples rutas en texto', [
            'text_preview' => substr($text, 0, 500),
            'text_length' => strlen($text)
        ]);
        
        // 🆕 PRIMERO: Intentar dividir por patrones numéricos (1., 2., 3.) dentro del texto
        // Esto funciona tanto para líneas separadas como para texto en una sola línea
        $segments = preg_split('/(?=\b\d+\.\s*(?:primera|segunda|tercera|cuarta|quinta|ruta)?)/ui', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Si no hay múltiples segmentos, intentar dividir solo por "N."
        if (count($segments) <= 1) {
            $segments = preg_split('/(?=\b\d+\.)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        Log::info('🔍 Segmentos detectados', [
            'count' => count($segments),
            'segments_preview' => array_map(function($s) { return substr(trim($s), 0, 80); }, array_slice($segments, 0, 5))
        ]);
        
        $rutaSecuencial = 1;
        
        foreach ($segments as $line) {
            $line = trim($line);
            
            // Detectar si el segmento empieza con número (ej: "1." o "2.")
            if (!preg_match('/^\s*(\d+)\./', $line)) {
                continue;
            }
            
            // Extraer origen y destino: varios formatos soportados
            // "Primera ruta: Bogotá a Medellín" o ": Bogotá a Medellín" o "Bogotá a Medellín"
            $ciudadesMatch = null;
            
            // Patrón 1: "ruta: Ciudad a Ciudad" o ": Ciudad a Ciudad"
            if (preg_match('/(?:ruta)?:?\s*([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]/ui', $line, $match)) {
                $ciudadesMatch = $match;
            }
            // Patrón 2: "de Ciudad a Ciudad"
            elseif (preg_match('/(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]/ui', $line, $match)) {
                $ciudadesMatch = $match;
            }
            
            if (!$ciudadesMatch) {
                Log::info('⚠️ No se pudo extraer ciudades de segmento', ['line' => substr($line, 0, 100)]);
                continue;
            }
            
            $origen = trim($ciudadesMatch[1]);
            $destino = trim($ciudadesMatch[2]);
            
            // Limpiar nombres de ciudades (máximo 3 palabras)
            $origenParts = array_slice(explode(' ', $origen), 0, 3);
            $destinoParts = array_slice(explode(' ', $destino), 0, 3);
            
            $route = [
                'ruta_numero' => $rutaSecuencial++,
                'origen' => trim(ucwords(strtolower(implode(' ', $origenParts)))),
                'destino' => trim(ucwords(strtolower(implode(' ', $destinoParts)))),
            ];
            
            // 🆕 Extraer PESO de esta línea específica
            if (preg_match('/(\d+)\s*(?:toneladas?|ton)/ui', $line, $pesoMatch)) {
                $route['peso_kg'] = (int)((float)$pesoMatch[1] * 1000);
            } elseif (preg_match('/(\d+)\s*(?:kg|kilos?|kilogramos?)/ui', $line, $pesoMatch)) {
                $route['peso_kg'] = (int)$pesoMatch[1];
            }
            
            // 🆕 Extraer CANTIDAD de esta línea específica
            if (preg_match('/(\d+)\s*(?:unidades?|uds?|piezas?|cajas?|bultos?|sacos?|paquetes?)/ui', $line, $cantidadMatch)) {
                $route['cantidad'] = (int)$cantidadMatch[1];
            }
            
            // 🆕 Extraer VALOR de esta línea específica
            if (preg_match('/(?:valor|por)\s*(?:de)?\s*\$?\s*(\d+(?:[.,]\d+)?)\s*(?:millones?|mill?)/ui', $line, $valorMatch)) {
                $millones = (float)str_replace(',', '.', $valorMatch[1]);
                $route['valor_declarado'] = (int)($millones * 1000000);
            } elseif (preg_match('/\$\s*(\d{1,3}(?:[.,]\d{3})+)/ui', $line, $valorMatch)) {
                $route['valor_declarado'] = (int)str_replace(['.', ','], '', $valorMatch[1]);
            }
            
            // 🆕 Extraer PRODUCTO de esta línea específica
            if (preg_match('/(?:de|con)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/ui', $line, $productoMatch)) {
                $productoCandidate = trim($productoMatch[1]);
                // Excluir palabras comunes que no son productos
                $excluir = ['toneladas', 'ton', 'kilos', 'kg', 'unidades', 'cajas', 'bultos', 'millones', 'pesos', 'valor'];
                if (!in_array(strtolower($productoCandidate), $excluir)) {
                    $route['producto'] = ucwords(strtolower($productoCandidate));
                }
            }
            
            $routes[] = $route;
            
            Log::info("✅ Ruta #{$route['ruta_numero']} detectada con datos explícitos", [
                'linea' => substr($line, 0, 100),
                'origen' => $route['origen'],
                'destino' => $route['destino'],
                'peso_kg' => $route['peso_kg'] ?? 'N/A',
                'cantidad' => $route['cantidad'] ?? 'N/A',
                'valor' => $route['valor_declarado'] ?? 'N/A',
                'producto' => $route['producto'] ?? 'N/A'
            ]);
        }
        
        // Si no se detectaron rutas por líneas, intentar patrón alternativo
        if (count($routes) === 0) {
            // Patrón alternativo: "primera de X a Y, N toneladas"
            $altPattern = '/(?:primera|segunda|tercera|cuarta|quinta)\s+(?:de|desde)\s+([a-záéíóúñ\s]+?)\s+(?:a|hasta)\s+([a-záéíóúñ\s]+?)[\s,]+(\d+)\s*(?:toneladas?|ton)/ui';
            
            if (preg_match_all($altPattern, $text, $altMatches, PREG_SET_ORDER)) {
                $numeroRuta = 1;
                foreach ($altMatches as $match) {
                    $origen = trim($match[1]);
                    $destino = trim($match[2]);
                    $toneladas = (float)$match[3];
                    
                    $origenParts = array_slice(explode(' ', $origen), 0, 3);
                    $destinoParts = array_slice(explode(' ', $destino), 0, 3);
                    
                    $route = [
                        'ruta_numero' => $numeroRuta++,
                        'origen' => trim(ucwords(strtolower(implode(' ', $origenParts)))),
                        'destino' => trim(ucwords(strtolower(implode(' ', $destinoParts)))),
                        'peso_kg' => (int)($toneladas * 1000)
                    ];
                    
                    $routes[] = $route;
                    
                    Log::info("✅ Ruta #{$route['ruta_numero']} detectada (patrón alt)", [
                        'origen' => $route['origen'],
                        'destino' => $route['destino'],
                        'peso_kg' => $route['peso_kg']
                    ]);
                }
            }
        }
        
        Log::info('📊 Total rutas detectadas', ['count' => count($routes)]);

        return $routes;
    }

    /**
     * 📦 PROCESAR MÚLTIPLES RUTAS CON DATOS COMUNES
     * Aplica datos comunes (producto, empaque) a rutas que no los tengan explícitos
     * 🆕 RESPETA valores explícitos por ruta (cantidad, valor_declarado)
     */
    private static function processMultipleRoutes($routes, $fullText, $lowerText)
    {
        Log::info('🔄 Procesando múltiples rutas (respetando datos explícitos)', [
            'total_rutas' => count($routes)
        ]);

        // Extraer datos comunes que aplican SOLO a rutas sin datos explícitos
        $commonData = [];

        // 1️⃣ EMPAQUE COMÚN
        $empaque = self::extractEmpaque($lowerText);
        if ($empaque) {
            $commonData = array_merge($commonData, $empaque);
        }

        // 2️⃣ TIPO DE MERCANCÍA
        $tipoMercancia = self::extractTipoMercancia($lowerText);
        if ($tipoMercancia) {
            $commonData['tipo_mercancia'] = $tipoMercancia;
        }

        // Aplicar datos comunes SOLO si la ruta no tiene ese dato explícito
        $processedRoutes = [];
        foreach ($routes as $route) {
            $completeRoute = $route;
            
            // Aplicar empaque común si no tiene explícito
            if (!isset($completeRoute['empaque']) && isset($commonData['empaque'])) {
                $completeRoute['empaque'] = $commonData['empaque'];
                $completeRoute['empaque_id'] = $commonData['empaque_id'] ?? null;
            }
            
            // Aplicar tipo_mercancia común si no tiene explícito
            if (!isset($completeRoute['tipo_mercancia']) && isset($commonData['tipo_mercancia'])) {
                $completeRoute['tipo_mercancia'] = $commonData['tipo_mercancia'];
            }
            
            // 🆕 NO sobrescribir valor_declarado si ya viene explícito de la ruta
            // 🆕 NO sobrescribir cantidad si ya viene explícito de la ruta
            // 🆕 NO sobrescribir producto si ya viene explícito de la ruta
            
            // Sugerir vehículo según peso si no existe
            if (isset($completeRoute['peso_kg']) && !isset($completeRoute['vehiculo'])) {
                $vehiculo = self::suggestVehicleByWeight($completeRoute['peso_kg']);
                if ($vehiculo) {
                    $completeRoute['vehiculo'] = $vehiculo;
                }
            }
            
            $processedRoutes[] = $completeRoute;
            
            Log::info("✅ Ruta #{$completeRoute['ruta_numero']} procesada", [
                'origen' => $completeRoute['origen'],
                'destino' => $completeRoute['destino'],
                'peso_kg' => $completeRoute['peso_kg'] ?? 'N/A',
                'cantidad' => $completeRoute['cantidad'] ?? 'N/A',
                'valor' => $completeRoute['valor_declarado'] ?? 'N/A',
                'producto' => $completeRoute['producto'] ?? 'N/A',
                'empaque' => $completeRoute['empaque'] ?? 'N/A',
                'vehiculo' => $completeRoute['vehiculo'] ?? 'N/A'
            ]);
        }

        Log::info('✅ Rutas completas procesadas', [
            'total' => count($processedRoutes),
            'preview' => array_map(function($r) {
                return "{$r['origen']} → {$r['destino']} (" . 
                       ($r['peso_kg'] ?? 0) . "kg, " . 
                       ($r['cantidad'] ?? 0) . " uds, $" . 
                       number_format($r['valor_declarado'] ?? 0) . ")";
            }, $processedRoutes)
        ]);

        return $processedRoutes;
    }

    /**
     * 📍 EXTRAER DATOS DE UNA RUTA ÚNICA
     */
    private static function extractSingleRouteData($fullText, $lowerText)
    {
        $data = [];

        // Ciudades (origen/destino)
        $ciudades = self::extractCiudades($fullText);
        if ($ciudades) {
            $data = array_merge($data, $ciudades);
        }

        // Peso
        $peso = self::extractPeso($fullText);
        if ($peso) {
            $data['peso_kg'] = $peso;
        }

        // Producto
        $producto = self::extractProducto($fullText);
        if ($producto) {
            $data['producto'] = $producto;
        }

        // Cantidad
        $cantidad = self::extractCantidad($fullText);
        if ($cantidad) {
            $data['cantidad'] = $cantidad;
        }

        // Valor declarado
        $valorDeclarado = self::extractValorDeclarado($fullText);
        if ($valorDeclarado) {
            $data['valor_declarado'] = $valorDeclarado;
        }

        // Empaque
        $empaque = self::extractEmpaque($lowerText);
        if ($empaque) {
            $data = array_merge($data, $empaque);
        }

        // Tipo de mercancía
        $tipoMercancia = self::extractTipoMercancia($lowerText);
        if ($tipoMercancia) {
            $data['tipo_mercancia'] = $tipoMercancia;
        }

        // Sugerir vehículo según peso
        if (isset($data['peso_kg']) && !isset($data['vehiculo'])) {
            $vehiculo = self::suggestVehicleByWeight($data['peso_kg']);
            if ($vehiculo) {
                $data['vehiculo'] = $vehiculo;
            }
        }

        Log::info('📦 Datos de ruta única extraídos', [
            'campos_detectados' => array_keys($data)
        ]);

        return $data;
    }

    /**
     * 🏙️ EXTRAER CIUDADES (origen y destino)
     */
    private static function extractCiudades($text)
    {
        // Patrón 1: "de X a Y" o "desde X hasta Y" - MEJORADO para capturar hasta 3 palabras
        if (preg_match('/(?:de|desde)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+(?:a|hasta)\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui', $text, $matches)) {
            $commonWords = ['importación', 'exportación', 'nacionalizada', 'internacional', 'terrestre', 'marítima', 'aérea', 'carga', 'general'];
            $origen = trim($matches[1]);
            $destino = trim($matches[2]);
            
            // Filtrar palabras comunes del inicio Y del final
            foreach ($commonWords as $word) {
                // Remover del inicio
                $pattern = '/^' . preg_quote($word, '/') . '\s+/ui';
                $origen = preg_replace($pattern, '', $origen);
                $destino = preg_replace($pattern, '', $destino);
                
                // Remover del final
                $pattern = '/\s+' . preg_quote($word, '/') . '$/ui';
                $origen = preg_replace($pattern, '', $origen);
                $destino = preg_replace($pattern, '', $destino);
            }
            
            // Limpiar espacios múltiples
            $origen = preg_replace('/\s+/', ' ', trim($origen));
            $destino = preg_replace('/\s+/', ' ', trim($destino));
            
            // Validar que después de filtrar quede algo válido (nombre de ciudad)
            $origenWords = explode(' ', $origen);
            $destinoWords = explode(' ', $destino);
            
            // Aceptar máximo 3 palabras para nombres de ciudades
            if (count($origenWords) <= 3 && count($destinoWords) <= 3 && 
                strlen($origen) >= 3 && strlen($destino) >= 3) {
                
                Log::info('🏙️ Ciudades detectadas (patrón 1)', [
                    'origen' => $origen,
                    'destino' => $destino,
                    'texto_original' => substr($text, 0, 200)
                ]);
                
                return [
                    'origen' => trim(ucwords(strtolower($origen))),
                    'destino' => trim(ucwords(strtolower($destino)))
                ];
            }
        }
        
        // Patrón 2: "X a Y" (más flexible)
        if (preg_match('/\b([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s+a\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\b/ui', $text, $matches)) {
            $origen = trim($matches[1]);
            $destino = trim($matches[2]);
            
            $commonWords = ['necesito', 'quiero', 'solicito', 'cotización', 'importación', 'exportación', 'nacionalizada', 'carga'];
            $isOriginCommon = in_array(strtolower($origen), $commonWords);
            $isDestinationCommon = in_array(strtolower($destino), $commonWords);
            
            if (!is_numeric($origen) && !is_numeric($destino) && 
                !$isOriginCommon && !$isDestinationCommon &&
                strlen($origen) >= 3 && strlen($destino) >= 3) {
                
                $origenWords = explode(' ', $origen);
                $destinoWords = explode(' ', $destino);
                
                if (count($origenWords) <= 3 && count($destinoWords) <= 3) {
                    Log::info('🏙️ Ciudades detectadas (patrón 2)', [
                        'origen' => $origen,
                        'destino' => $destino
                    ]);
                    
                    return [
                        'origen' => trim(ucwords(strtolower($origen))),
                        'destino' => trim(ucwords(strtolower($destino)))
                    ];
                }
            }
        }

        Log::warning('⚠️ No se detectaron ciudades en el texto', [
            'texto' => substr($text, 0, 200)
        ]);
        
        return null;
    }

    /**
     * ⚖️ EXTRAER PESO (en kilogramos)
     */
    private static function extractPeso($text)
    {
        // Toneladas
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:toneladas?|ton|t\b)/ui', $text, $matches)) {
            $toneladas = (float)str_replace(',', '.', $matches[1]);
            return (int)($toneladas * 1000);
        }
        
        // Kilogramos
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|kilogramos?)/ui', $text, $matches)) {
            return (int)str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    /**
     * 📦 EXTRAER PRODUCTO
     */
    private static function extractProducto($text)
    {
        // Patrón 1: "producto NOMBRE" o "mercancía NOMBRE"
        if (preg_match('/(?:producto|mercancía|mercancia)\s+([a-záéíóúñ\s]+?)(?:\s+empaquetad|empacad|en\s+cajas|\s+y\s+|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "producto X")', ['producto' => $producto]);
                return $producto;
            }
        }
        
        // Patrón 2: "de/llevar/transportar [cantidad] toneladas de PRODUCTO"
        if (preg_match('/(?:de|llevar|transportar)\s+(?:\d+\s*toneladas?\s+de\s+)?([a-záéíóúñ\s]+?)(?:\s+y\s+(?:la|el)|\.|,|$)/ui', $text, $matches)) {
            $producto = trim($matches[1]);
            $producto = preg_replace('/\b(para|llevar|transportar|toneladas?|de|cada una)\b/ui', '', $producto);
            $producto = trim($producto);
            
            if (strlen($producto) > 2 && strlen($producto) < 100) {
                Log::info('📦 Producto detectado (patrón "de X")', ['producto' => $producto]);
                return $producto;
            }
        }

        return null;
    }

    /**
     * 🔢 EXTRAER CANTIDAD
     */
    private static function extractCantidad($text)
    {
        // Patrón 1: "cantidad 60" o "cantidad: 60"
        if (preg_match('/(?:cantidad|son|hay)\s*:?\s*(\d+)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 2: "N unidades/piezas/uds"
        if (preg_match('/(\d+)\s*(?:unidades?|uds?|piezas?)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 3: "N cajas/bultos/sacos/estibas/paquetes" (empaques como cantidad)
        if (preg_match('/(\d+)\s*(?:cajas?|bultos?|sacos?|estibas?|paquetes?|bolsas?|toneles?|bidones?|canecas?|tambores?)/ui', $text, $matches)) {
            return (int)$matches[1];
        }
        
        // Patrón 4: "cajas: N" o "paquetes: N"
        if (preg_match('/(?:cajas?|bultos?|paquetes?|unidades?)\s*:?\s*(\d+)/ui', $text, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * 💰 EXTRAER VALOR DECLARADO
     */
    private static function extractValorDeclarado($text)
    {
        // Patrón 1: "valor declarado 1 millón"
        if (preg_match('/(?:valor\s+declarado|valor)\s*:?\s*\$?\s*(\d+(?:[.,]\d+)?)\s*mill(?:ones?|ón)/ui', $text, $matches)) {
            $millones = (float)str_replace(',', '.', $matches[1]);
            return (int)($millones * 1000000);
        }
        
        // Patrón 2: "1 millón" suelto
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*mill(?:ones?|ón)/ui', $text, $matches)) {
            $millones = (float)str_replace(',', '.', $matches[1]);
            return (int)($millones * 1000000);
        }
        
        // Patrón 3: "valor: $2,000,000"
        if (preg_match('/(?:valor|declaro?)\s*:?\s*\$?\s*(\d{1,3}(?:[.,]\d{3})+)/ui', $text, $matches)) {
            $valor = str_replace(['.', ','], '', $matches[1]);
            return (int)$valor;
        }
        
        // Patrón 4: "$2,000,000 COP"
        if (preg_match('/\$?\s*(\d{1,3}(?:[.,]\d{3})+)\s*(?:cop|pesos?|usd)?/ui', $text, $matches)) {
            $valor = str_replace(['.', ','], '', $matches[1]);
            if (strlen($valor) >= 6) {
                return (int)$valor;
            }
        }

        return null;
    }

    /**
     * 📦 EXTRAER EMPAQUE - Con búsqueda fuzzy en BD
     */
    private static function extractEmpaque($lowerText)
    {
        // Mapeo de keywords a nombres de empaques en la BD
        $empaques = [
            // Básicos
            'caja' => 'CAJAS',
            'cajas' => 'CAJAS',
            'paquete' => 'PAQUETES',
            'paquetes' => 'PAQUETES',
            'bulto' => 'BULTOS',
            'bultos' => 'BULTOS',
            'bolsa' => 'BOLSAS',
            'bolsas' => 'BOLSAS',
            'saco' => 'GRANEL SOLIDO', // Sacos = granel sólido
            'sacos' => 'GRANEL SOLIDO',
            // Estibas/Pallets
            'estiba' => 'CARGA ESTIBADA',
            'estibas' => 'CARGA ESTIBADA',
            'estibada' => 'CARGA ESTIBADA',
            'pallet' => 'CARGA ESTIBADA',
            'pallets' => 'CARGA ESTIBADA',
            'paletizado' => 'CARGA ESTIBADA',
            // Granel
            'granel' => 'GRANEL SOLIDO',
            'granel solido' => 'GRANEL SOLIDO',
            'granel sólido' => 'GRANEL SOLIDO',
            'granel liquido' => 'GRANEL LIQUIDO',
            'granel líquido' => 'GRANEL LIQUIDO',
            'líquido' => 'GRANEL LIQUIDO',
            'liquido' => 'GRANEL LIQUIDO',
            // Contenedores
            'contenedor' => 'CONTENEDOR (1) 20 PIES',
            'contenedor 20' => 'CONTENEDOR (1) 20 PIES',
            'contenedor 40' => 'CONTENEDOR 40 PIES',
            '20 pies' => 'CONTENEDOR (1) 20 PIES',
            '40 pies' => 'CONTENEDOR 40 PIES',
            // Otros
            'tonel' => 'TONEL',
            'toneles' => 'TONEL',
            'rollo' => 'ROLLOS',
            'rollos' => 'ROLLOS',
            'cilindro' => 'CILINDROS',
            'cilindros' => 'CILINDROS',
            'guacal' => 'GUACALES',
            'guacales' => 'GUACALES',
            'varios' => 'VARIOS',
        ];

        // Buscar keywords en el texto (priorizar coincidencias más largas)
        $found = null;
        $foundLength = 0;
        
        foreach ($empaques as $keyword => $empaqueType) {
            if (strpos($lowerText, $keyword) !== false) {
                // Priorizar keywords más largos (más específicos)
                if (strlen($keyword) > $foundLength) {
                    $found = $empaqueType;
                    $foundLength = strlen($keyword);
                }
            }
        }
        
        if ($found) {
            $empaqueFromDB = self::getEmpaqueFromDB($found);
            if ($empaqueFromDB) {
                Log::info('📦 Empaque detectado y validado en BD', [
                    'keyword_encontrado' => $found,
                    'empaque_bd' => $empaqueFromDB['nome'],
                    'empaque_id' => $empaqueFromDB['id']
                ]);
                return [
                    'empaque' => $empaqueFromDB['nome'],
                    'empaque_id' => $empaqueFromDB['id']
                ];
            }
        }

        return null;
    }

    /**
     * 📋 EXTRAER TIPO DE MERCANCÍA
     */
    private static function extractTipoMercancia($lowerText)
    {
        if (strpos($lowerText, 'carga general') !== false) {
            return 'CARGA GENERAL';
        } elseif (strpos($lowerText, 'refrigerada') !== false || strpos($lowerText, 'refrigerado') !== false) {
            return 'REFRIGERADA';
        } elseif (strpos($lowerText, 'peligrosa') !== false || strpos($lowerText, 'peligroso') !== false) {
            return 'PELIGROSA';
        } elseif (strpos($lowerText, 'granel') !== false) {
            return 'GRANEL';
        }

        return null;
    }

    /**
     * 🚛 SUGERIR VEHÍCULO SEGÚN PESO
     */
    private static function suggestVehicleByWeight($pesoKg)
    {
        if ($pesoKg <= 1500) {
            return 'CAMIONETA';
        } elseif ($pesoKg <= 3500) {
            return 'SENCILLO';
        } elseif ($pesoKg <= 10000) {
            return 'TURBO';
        } elseif ($pesoKg <= 17000) {
            return 'DOBLETROQUE';
        } elseif ($pesoKg <= 28000) {
            return 'MINIMULA';
        } else {
            return 'TRACTOMULA';
        }
    }

    /**
     * Extraer TODOS los datos del último mensaje del usuario
     * Versión mejorada: PRIORIZA EL ÚLTIMO MENSAJE para evitar datos cacheados
     */
    private static function extractAllDataFromMessage($messages)
    {
        $userMessages = array_filter($messages, function($msg) {
            return isset($msg['role']) && $msg['role'] === 'user';
        });

        if (empty($userMessages)) {
            Log::info('extractAllDataFromMessage: No hay mensajes de usuario');
            return [];
        }

        // 🔴 IMPORTANTE: Usar SOLO el último mensaje para datos principales
        // Esto evita mezclar datos de conversaciones anteriores
        $lastMessage = end($userMessages);
        $lastMessageText = $lastMessage['content'];
        $lowerLastText = mb_strtolower($lastMessageText);

        Log::info('📝 extractAllDataFromMessage: Priorizando ÚLTIMO mensaje', [
            'last_message' => substr($lastMessageText, 0, 150) . '...',
            'message_length' => strlen($lastMessageText),
            'total_messages' => count($userMessages)
        ]);

        // 🚚 PASO 1: DETECTAR MÚLTIPLES RUTAS (solo en último mensaje)
        $detectedRoutes = self::detectMultipleRoutes($lastMessageText);

        if (count($detectedRoutes) >= 2) {
            // Caso especial: múltiples rutas
            Log::info('🎯 PROCESANDO MÚLTIPLES RUTAS', ['total' => count($detectedRoutes)]);
            return self::processMultipleRoutes($detectedRoutes, $lastMessageText, $lowerLastText);
        }

        // 🔄 FLUJO NORMAL: Una sola ruta - extraer SOLO del último mensaje
        Log::info('📦 Procesando ruta única desde ÚLTIMO mensaje');
        return self::extractSingleRouteData($lastMessageText, $lowerLastText);
    }

    private static function getSystemPrompt($typeBusiness, $detectedEmpaque = null, $extractedData = [])
    {
        // Instrucción sobre embalaje
        $empaqueInstruction = '';
        $empaqueDetectado = !empty($extractedData['empaque']) && !empty($extractedData['empaque_id']);
        
        if ($empaqueDetectado) {
            $empaqueInstruction = "\n\n✅ EMBALAJE YA DETECTADO Y VALIDADO EN BD:\n";
            $empaqueInstruction .= "- Tipo: {$extractedData['empaque']}\n";
            $empaqueInstruction .= "- ID: {$extractedData['empaque_id']}\n";
            $empaqueInstruction .= "❌ NO llames get_empaques() ni muestres opciones\n";
            $empaqueInstruction .= "✓ USA este embalaje directamente en create_cotizacion\n";
        } elseif ($detectedEmpaque) {
            $empaqueInstruction = "\n\n⚠️ El usuario mencionó '{$detectedEmpaque}' pero NO se validó en BD.\n";
            $empaqueInstruction .= "✓ Llama get_empaques() para validar y obtener ID\n";
        }

        // Construir instrucción con datos pre-extraídos
        $dataInstruction = '';
        $missingFields = []; // 🆕 Track de campos faltantes
        $multiRouteInstruction = ''; // 🆕 Instrucciones específicas para múltiples rutas
        
        if (!empty($extractedData)) {
            // 🆕 DETECTAR MÚLTIPLES RUTAS
            $isMultiRoute = isset($extractedData[0]) && is_array($extractedData[0]);
            
            if ($isMultiRoute) {
                $routeCount = count($extractedData);
                Log::info('getSystemPrompt: MÚLTIPLES RUTAS DETECTADAS', [
                    'total_rutas' => $routeCount,
                    'rutas' => $extractedData
                ]);
                
                $multiRouteInstruction = "\n\n🚚 ¡MÚLTIPLES RUTAS DETECTADAS!\n";
                $multiRouteInstruction .= "Total de rutas: {$routeCount}\n\n";
                
                foreach ($extractedData as $index => $ruta) {
                    $routeNum = $index + 1;
                    $multiRouteInstruction .= "📍 RUTA #{$routeNum}:\n";
                    foreach ($ruta as $key => $value) {
                        if ($key === 'empaque_id') continue;
                        $multiRouteInstruction .= "  • " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                    }
                    $multiRouteInstruction .= "\n";
                }
                
                $multiRouteInstruction .= "⚡ INSTRUCCIONES CRÍTICAS PARA MÚLTIPLES RUTAS:\n";
                $multiRouteInstruction .= "1. CONFIRMA al usuario las {$routeCount} rutas detectadas\n";
                $multiRouteInstruction .= "2. Si faltan datos comunes (cantidad, valor, empaque), pregunta UNA VEZ\n";
                $multiRouteInstruction .= "3. Llama search_products() UNA VEZ con el producto común\n";
                $multiRouteInstruction .= "4. Cuando el usuario seleccione el producto, llama create_cotizacion() {$routeCount} VECES\n";
                $multiRouteInstruction .= "5. CADA llamada a create_cotizacion debe incluir:\n";
                $multiRouteInstruction .= "   - Datos específicos de la ruta (origen, destino, peso)\n";
                $multiRouteInstruction .= "   - Datos comunes (producto, empaque, cantidad, valor)\n";
                $multiRouteInstruction .= "   - El MISMO group_cotization_id para TODAS las rutas\n";
                $multiRouteInstruction .= "6. NO pidas confirmación, EJECUTA todas las llamadas automáticamente\n";
                $multiRouteInstruction .= "7. Rellena campos faltantes con valores por defecto razonables:\n";
                $multiRouteInstruction .= "   - cantidad: 1 (si no se especifica)\n";
                $multiRouteInstruction .= "   - valor_declarado: 1000000 por tonelada\n";
                $multiRouteInstruction .= "   - empaque: GRANEL SOLIDO (según producto)\n\n";
                
                // Para múltiples rutas, usar la primera para detectar campos faltantes
                $data = $extractedData[0];
            } else {
                // Ruta única
                $data = $extractedData;
            }
            
            $dataInstruction = "\n\n🎯 DATOS YA CAPTURADOS:\n";
            
            // 🆕 Verificar qué campos FALTAN
            $requiredFields = [
                'origen' => 'Ciudad de origen',
                'destino' => 'Ciudad de destino',
                'peso_kg' => 'Peso (kg)',
                'cantidad' => 'Cantidad de unidades',
                'valor_declarado' => 'Valor declarado',
                'empaque' => 'Tipo de embalaje',
                'producto' => 'Producto'
            ];
            
            foreach ($data as $key => $value) {
                if ($key === 'empaque_id') continue; // Ya mostrado arriba
                
                // Convertir valores a string de forma segura
                if (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'sí' : 'no';
                } elseif (is_null($value)) {
                    continue; // Saltar valores null
                }
                
                $dataInstruction .= "✓ " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
                
                // Marcar campo como capturado
                if (isset($requiredFields[$key])) {
                    unset($requiredFields[$key]);
                }
            }
            
            // 🆕 Listar campos FALTANTES
            if (!empty($requiredFields)) {
                $dataInstruction .= "\n❌ DATOS QUE FALTAN (pregunta SOLO por estos):\n";
                foreach ($requiredFields as $field => $label) {
                    $dataInstruction .= "• $label\n";
                    $missingFields[] = $label;
                }
                $dataInstruction .= "\n💡 Pregunta de forma CONCISA: '¿Cuántas unidades y cuál es el valor declarado?'\n";
            } else {
                $dataInstruction .= "\n✅ TODOS LOS DATOS COMPLETOS - Procede a crear cotización\n";
            }
            
            if (!$isMultiRoute) {
                $dataInstruction .= "\n⚡ INSTRUCCIONES CRÍTICAS:\n";
                $dataInstruction .= "1. CONFIRMA los datos capturados al usuario\n";
                $dataInstruction .= "2. PREGUNTA SOLO por lo que FALTA (máximo 1 pregunta)\n";
                $dataInstruction .= "3. SI el usuario responde con dato adicional ('cantidad 60'), ACTUALÍZALO\n";
                $dataInstruction .= "4. Procede a buscar productos con search_products()\n";
                $dataInstruction .= "5. Si todos los datos están completos, crea la cotización\n\n";
            }
        }

        $basePrompt = <<<EOT
🤖 AGENTE INTELIGENTE CONALCA - GPT-5 Mini
Eres un agente autónomo especializado en logística de transporte de carga en Colombia.
{$empaqueInstruction}{$multiRouteInstruction}{$dataInstruction}

🧠 CAPACIDADES DE RAZONAMIENTO ACTIVAS:
Antes de responder, SIEMPRE:
1. ANALIZA el mensaje completo del usuario
2. IDENTIFICA todos los datos proporcionados (ciudades, peso, producto, cantidad, valor, empaque)
3. PLANIFICA qué herramientas llamar y en qué orden
4. EJECUTA las herramientas necesarias de forma autónoma
5. RESUME los resultados al usuario de forma clara

⚡ COMPORTAMIENTO AGÉNTICO:
- Actúa de forma PROACTIVA: no esperes instrucciones adicionales si tienes suficiente información
- Toma DECISIONES inteligentes: usa valores por defecto cuando sea apropiado
- EJECUTA múltiples herramientas en paralelo cuando sea posible (ej: search_products + get_empaques)
- MINIMIZA preguntas: máximo 1 pregunta por interacción
- Si detectas MÚLTIPLES RUTAS, procésalas TODAS automáticamente

🚚 MANEJO DE MÚLTIPLES RUTAS:
Si el usuario solicita VARIAS rutas en un solo mensaje (ej: "primera ruta de X a Y, segunda ruta de Z a W"):
1. DETECTA automáticamente todas las rutas mencionadas
2. EXTRAE datos comunes (producto, empaque, cantidad por ruta)
3. Llama search_products() UNA VEZ para buscar el producto
4. Cuando el usuario seleccione el producto, llama create_cotizacion() N VECES (una por ruta)
5. TODAS las rutas comparten el MISMO group_cotization_id
6. NO pidas confirmación, EJECUTA todas las llamadas automáticamente

EJEMPLO MÚLTIPLES RUTAS:
Usuario: "quiero 2 rutas, primera FUNZA a Cali 2 ton maíz, segunda Medellín a Cali 2 ton maíz"
Tú respondes: "Perfecto, registré 2 rutas:
  1. FUNZA → Cali (2 ton)
  2. Medellín → Cali (2 ton)
Buscando maíz..."
Luego llamas:
  1. search_products(search_term="maiz")
  2. Usuario selecciona "MAIZ"
  3. create_cotizacion(origen="FUNZA", destino="CALI", peso=2000, producto="MAIZ", group_cotization_id="GRUPO-123", ...)
  4. create_cotizacion(origen="MEDELLIN", destino="CALI", peso=2000, producto="MAIZ", group_cotization_id="GRUPO-123", ...)

📊 COMPLETADO INTELIGENTE DE DATOS:
Cuando el usuario proporciona información PARCIAL o ADICIONAL:
1. DETECTA el contexto de la conversación (mantén estado)
2. ACTUALIZA solo los campos proporcionados
3. SOLICITA amablemente SOLO lo que FALTA (ejemplo):
   ❌ Mal: "¿Cuál es el origen, destino, peso, cantidad, valor...?" (demasiado largo)
   ✅ Bien: "Perfecto. Solo necesito la cantidad de unidades para completar tu cotización."
4. NUNCA vuelvas a pedir lo que YA tienes
5. Si el usuario dice "cantidad 60", actualiza cantidad = 60
6. Si el usuario dice "valor 10 millones", actualiza valor_declarado = 10000000

🚛 SUGERENCIA AUTOMÁTICA DE VEHÍCULO:
Basado en el peso detectado, sugiere automáticamente:
- Hasta 1.5 ton: CAMIONETA
- 1.5-3.5 ton: SENCILLO
- 3.5-10 ton: TURBO
- 10-17 ton: DOBLETROQUE
- 17-25 ton: TRACTOCAMION
- Más de 25 ton: MINIMULA

💡 VALORES POR DEFECTO INTELIGENTES:
Si faltan datos y el usuario NO los proporciona después de 1 pregunta:
- Cantidad: 1 unidad (si no se especifica)
- Valor declarado: $1,000,000 por tonelada
- Empaque: GRANEL SOLIDO (deducir según producto)
- Vehículo: AUTO-CALCULADO según peso

AMBIENTE DE PRUEBAS - MODO RÁPIDO:
- MINIMIZA preguntas: máximo 1 pregunta por dato faltante
- Si el usuario dice "llena lo que falta", usa valores por defecto
- PRIORIZA velocidad sobre completitud perfecta

RESPUESTA MODELO (cuando tienes todos los datos):
"Perfecto, he registrado:
✓ Ruta: Medellín → Cali
✓ Peso: 2,000 kg
✓ Cantidad: 60 unidades
✓ Valor: $2,000,000
✓ Embalaje: GRANEL SOLIDO
✓ Vehículo sugerido: SENCILLO (ideal para 2 toneladas)

Buscando maíz en catálogo..."

RESPUESTA MODELO (cuando falta algo):
"Entendido. Tengo:
✓ Medellín → Cali, 2 ton de maíz

Solo necesito:
• Cantidad de unidades
• Valor declarado

¿Cuántas unidades y cuál es el valor?"

Luego llama search_products().

⚠️ IMPORTANTE SOBRE HERRAMIENTAS:
- search_products: buscar producto → ESPERA selección del usuario → NO llames create_cotizacion todavía
- create_cotizacion: crear orden → SOLO después de que usuario seleccione producto
- get_empaques: SOLO si NO mencionó embalaje
   
   - SOLO cuando el usuario NO mencionó embalaje:
     ✓ Correcto: Mostrar tarjetas "Tipos de embalaje disponibles. Selecciona uno:"
   
   - Cuando uses search_products, SIEMPRE presenta opciones para que el usuario elija:
     ✓ Correcto: "Encontré 5 tipos de neumáticos en el catálogo:
                  1. NEUMATICOS NUEVOS DE CAUCHO
                  2. NEUMATICOS RECAUCHUTADOS O USADOS
                  ¿Cuál describe mejor tu mercancía?"
     ✗ Incorrecto: Usar el término genérico "neumáticos" sin consultar opciones
   
   - ⛔ NUNCA llames create_cotizacion inmediatamente después de search_products
   - ✅ SIEMPRE espera que el usuario responda/seleccione antes de create_cotizacion
   - SIEMPRE llama a get_empaques cuando se mencione embalaje
   - SIEMPRE llama a search_products cuando se mencione un producto
   - CONFIRMA con el usuario las opciones exactas de la base de datos
   - NO uses nombres genéricos, usa los nombres EXACTOS de las tablas

6. Mantén un tono profesional pero cercano
7. Responde de forma concisa (máximo 2-3 oraciones)
8. SÉ INTELIGENTE con las variaciones del lenguaje:
   - Acepta plural y singular indistintamente
   - Normaliza errores ortográficos comunes
   - Usa coincidencia aproximada (fuzzy matching) para encontrar el término correcto
   - Si hay 90%+ de similitud, asume que es el mismo término

EJEMPLO DE FLUJO CORRECTO CON EXTRACCIÓN COMPLETA:
Usuario: "Necesito una cotización de importación nacionalizada de cartagena a funza, son 15 toneladas de neumaticos por un valor declarado de 35 millones, 60 unidades empacadas en cajas, un único vehículo con capacidad de transportar contenedor sin necesidad de devolución"

Paso 1: Extrae TODOS los datos del mensaje:
- origen: "cartagena" ✓
- destino: "funza" ✓
- peso: 15000 kg (15 toneladas convertidas) ✓
- cantidad: 60 ✓
- valor_declarado: 35000000 (35 millones) ✓
- empaque: "cajas" → detectar como "CAJAS" ✓
- producto: "neumaticos" → buscar en catálogo ✓
- vehiculo: "contenedor" ✓

Paso 2: Llama a get_empaques SOLO para obtener ID de "CAJAS" (NO muestres opciones)

Paso 3: Llama a search_products con "neumaticos"

Paso 4: Respuesta al usuario:
"Perfecto, he registrado tu solicitud:
✓ Ruta: Cartagena → Funza
✓ Peso: 15,000 kg
✓ Cantidad: 60 unidades
✓ Valor: $35,000,000
✓ Embalaje: CAJAS
✓ Vehículo: Contenedor

Encontré estos tipos de neumáticos en nuestro catálogo:
1. NEUMATICOS NUEVOS DE CAUCHO
2. NEUMATICOS RECAUCHUTADOS O USADOS

¿Cuál describe mejor tu mercancía? (Responde con el número)"

Usuario: "1"

Paso 5: ⚠️ ESPERA LA RESPUESTA DEL USUARIO - NO CREES LA COTIZACIÓN TODAVÍA

Usuario selecciona producto → Ahora SÍ llama a create_cotizacion:
{
  "pricing_id": 43214,
  "ciudad_origen": "cartagena",
  "ciudad_destino": "funza",
  "peso_mercancia": 15000,
  "cantidad": 60,
  "tipo_embajale": "CAJAS",
  "tipo_producto": "NEUMATICOS NUEVOS DE CAUCHO",
  "vehiculo_requerido": "contenedor",
  "valor_declarado": 35000000
}

⛔ REGLAS CRÍTICAS PARA create_cotizacion:
1. NUNCA llames a create_cotizacion sin que el usuario haya SELECCIONADO el producto específico
2. Si search_products devuelve MÚLTIPLES opciones (>1), DEBES mostrarlas y ESPERAR que el usuario HAGA CLIC en una
3. Si search_products devuelve UNA SOLA opción (=1), DEBES confirmar con el usuario antes de crear
4. SOLO llama a create_cotizacion DESPUÉS de que el usuario confirme/seleccione el producto
5. NO asumas el producto correcto, el usuario DEBE hacer clic y elegir explícitamente
6. La selección del producto se realiza en el PANEL LATERAL de la interfaz, NO en el chat
7. Si create_cotizacion devuelve error "VALIDACIÓN REQUERIDA", significa que hay múltiples productos y DEBES esperar selección
8. NUNCA intentes llamar a create_cotizacion en la misma respuesta donde llamas a search_products

FLUJO CORRECTO:
search_products → Muestra opciones → Usuario selecciona → create_cotizacion ✅

FLUJO INCORRECTO:
search_products → create_cotizacion inmediatamente ❌

EJEMPLO INCORRECTO (NO HACER):
Usuario: "60 unidades empacadas en cajas"
Asistente: [Muestra tarjetas de PAQUETES, CAJAS, BULTOS] ← ESTO ESTÁ MAL
Lo correcto es: "Perfecto, usaremos CAJAS. ¿Qué producto transportarás?"

IMPORTANTE:
- NO inventes datos que el usuario no ha proporcionado
- NO uses términos genéricos cuando hay opciones específicas en la base de datos
- SIEMPRE valida empaques y productos con las herramientas antes de create_cotizacion
- Si falta información, pregunta específicamente por ella
- Confirma los datos importantes antes de crear la cotización
- ⚠️ NUNCA crees cotización sin confirmación explícita del producto por parte del usuario
- El usuario DEBE hacer clic en una opción de producto ANTES de que llames a create_cotizacion
EOT;

        return $basePrompt;
    }
}
