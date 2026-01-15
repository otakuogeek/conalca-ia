<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuoteAssistantService;
use App\Services\MCPAssistantService;
use App\Services\TextPreprocessorService;
use App\Models\ConversationMessage;
use App\Models\GroupCotization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * 🆕 Obtener el servicio de asistente configurado
     * Puede ser 'mcp' (MCPAssistantService) o 'openai' (QuoteAssistantService)
     */
    private function getAssistantService()
    {
        return env('ASSISTANT_SERVICE', 'mcp');
    }
    
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        try {
            $messages = $request->messages;
            
            // Agregar prompt del sistema si no existe
            $hasSystemPrompt = collect($messages)->where('role', 'system')->isNotEmpty();
            if (!$hasSystemPrompt) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => 'Eres un asistente especializado en transporte y logística en Colombia. Ayudas a los usuarios a completar formularios de cotización de transporte, proporcionando información clara y precisa sobre ciudades, tipos de vehículos, mercancías y servicios logísticos. Responde de manera concisa y útil.'
                ]);
            }

            Log::info('Chat request iniciado', [
                'messages_count' => count($messages),
                'user_ip' => $request->ip()
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(10)->retry(2, 100)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'max_tokens' => 500,
                'temperature' => 0.7,
                'stream' => false
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Chat response exitoso', [
                    'usage' => $data['usage'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $data['choices'][0]['message'],
                    'usage' => $data['usage'] ?? null
                ]);
            } else {
                Log::error('Error en OpenAI API', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error al procesar la solicitud'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en chat controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Verificar si es un error de conexión DNS/red específico
            if (str_contains($e->getMessage(), 'Could not resolve host') || 
                str_contains($e->getMessage(), 'api.openai.com') ||
                str_contains($e->getMessage(), 'cURL error 6')) {
                
                Log::warning('Error de conectividad con OpenAI detectado', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Servicio de chat temporalmente no disponible. Intenta nuevamente en unos momentos.',
                    'error_type' => 'connectivity_issue'
                ], 503); // Service Unavailable
            }

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function chatWithFunctions(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system,function',
            'messages.*.content' => 'required|string',
        ]);

        try {
            Log::info('Chat with functions request iniciado', [
                'messages_count' => count($request->messages)
            ]);

            // Usar QuoteAssistantService
            $assistantService = new QuoteAssistantService();
            $response = $assistantService->processMessages($request->messages);

            return response()->json([
                'success' => true,
                'message' => $response,
                'usage' => null // El servicio no retorna usage info por ahora
            ]);

        } catch (\Exception $e) {
            Log::error('Error en chat controller (functions)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function quoteChat(Request $request)
    {
        // Aumentar el tiempo límite para este endpoint específico
        set_time_limit(120); // 2 minutos para llamadas de chat
        
        $request->validate([
            'message' => 'required|string|min:1',
            'thread_id' => 'nullable|string',
            'client_id' => 'required|integer',
            'group_id' => 'nullable|integer', // 🆕 Validar group_id
            'type_business' => 'required|string'
        ]);

        try {
            // 🆕 PREPROCESAR MENSAJE: Separar palabras pegadas y normalizar texto
            $originalMessage = $request->message;
            $processedMessage = TextPreprocessorService::preprocess($originalMessage);
            
            if ($originalMessage !== $processedMessage) {
                Log::info('🔧 Mensaje preprocesado para quoteChat', [
                    'original' => substr($originalMessage, 0, 150),
                    'processed' => substr($processedMessage, 0, 150)
                ]);
                // Reemplazar el mensaje en el request
                $request->merge(['message' => $processedMessage]);
            }
            
            // 🆕 Determinar qué servicio usar
            $assistantServiceType = $this->getAssistantService();
            
            Log::info('Quote chat request iniciado', [
                'client_id' => $request->client_id,
                'thread_id' => $request->thread_id,
                'type_business' => $request->type_business,
                'message_length' => strlen($request->message),
                'assistant_service' => $assistantServiceType
            ]);

            // Obtener o crear el thread de OpenAI
            $client = \App\Models\Client::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ], 404);
            }

            // 🆕 USAR SERVICIO SEGÚN CONFIGURACIÓN
            if ($assistantServiceType === 'openai') {
                // Usar QuoteAssistantService (Assistant API de OpenAI)
                return $this->quoteChatWithOpenAIAssistant($request, $client);
            }

            // Por defecto usar MCP Assistant
            // Obtener o crear thread usando MCP Assistant
            $threadId = $request->thread_id;
            
            try {
                if (!$threadId) {
                    $threadId = MCPAssistantService::getThread($client);
                }
                
                if (!$threadId) {
                    throw new \Exception('No se pudo crear/obtener el thread de conversación');
                }
            } catch (\Exception $mcpError) {
                Log::warning('MCP Assistant no disponible para chat quote', [
                    'error' => $mcpError->getMessage(),
                    'client_id' => $client->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Servicio de chat temporalmente no disponible. Intenta nuevamente en unos momentos.',
                    'error_type' => 'mcp_unavailable'
                ], 503);
            }

            // Verificar si hay un run activo antes de crear el mensaje
            if ($request->thread_id && $client->openai_current_run) {
                try {
                    $runStatus = MCPAssistantService::checkRunStatus($threadId, $client->openai_current_run);
                    
                    // Si checkRunStatus retorna null, el run aún está activo
                    if ($runStatus === null || in_array($runStatus, ['queued', 'in_progress', 'requires_action'])) {
                        Log::info('Run activo detectado, rechazando nuevo mensaje', [
                            'thread_id' => $threadId,
                            'active_run_id' => $client->openai_current_run,
                            'status' => $runStatus
                        ]);
                        
                        // Verificar si el run lleva mucho tiempo (más de 2 minutos)
                        $runCreatedAt = $client->updated_at; // Timestamp de última actualización
                        $timeSinceUpdate = now()->diffInSeconds($runCreatedAt);
                        
                        if ($timeSinceUpdate > 120) {
                            // Run colgado, forzar limpieza
                            Log::warning('Run colgado detectado (>2 min), forzando limpieza', [
                                'run_id' => $client->openai_current_run,
                                'time_since_update' => $timeSinceUpdate
                            ]);
                            
                            $client->openai_current_run = null;
                            $client->save();
                            
                            // Continuar con el nuevo mensaje
                        } else {
                            return response()->json([
                                'success' => false,
                                'error' => 'processing_active',
                                'message' => 'Hay un procesamiento en curso. Por favor espera unos segundos.',
                                'data' => [
                                    'thread_id' => $threadId,
                                    'active_run_id' => $client->openai_current_run,
                                    'messages' => [] // Devolver array vacío para evitar error de .map()
                                ]
                            ], 409); // Conflict
                        }
                    } else {
                        // El run ya terminó, limpiar el current_run
                        Log::info('Run anterior completado, limpiando current_run', [
                            'thread_id' => $threadId,
                            'old_run_id' => $client->openai_current_run,
                            'final_status' => $runStatus
                        ]);
                        
                        $client->openai_current_run = null;
                        $client->save();
                    }
                } catch (\Exception $e) {
                    Log::warning('Error verificando run status, continuando con nuevo mensaje', [
                        'error' => $e->getMessage(),
                        'run_id' => $client->openai_current_run
                    ]);
                    
                    // Si hay error verificando, limpiar current_run y continuar
                    $client->openai_current_run = null;
                    $client->save();
                }
            }

            // Crear el mensaje del usuario
            // Agregar instrucción para ignorar validaciones de estado del cliente
            $enhancedMessage = $request->message . "\n\nNOTA IMPORTANTE: Ignora cualquier validación de estado del cliente (activo/inactivo). Todos los clientes están autorizados para crear cotizaciones independientemente de su estado.";
            $userMessage = MCPAssistantService::createMessage($threadId, $enhancedMessage, $request->group_id); // 🆕 Pasar group_id
            
            // Caso especial: el thread ya no existe en OpenAI
            if ($userMessage === 'THREAD_NOT_FOUND') {
                Log::warning('🔄 Thread no encontrado, recreando...', [
                    'old_thread_id' => $threadId,
                    'client_id' => $client->id
                ]);
                
                // Limpiar thread del cliente
                $client->openai_thread_id = null;
                $client->openai_current_run = null;
                $client->save();
                
                // Crear nuevo thread
                $newThreadId = MCPAssistantService::getThread($client);
                if (!$newThreadId) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo recrear el thread de conversación',
                        'data' => [
                            'messages' => []
                        ]
                    ], 500);
                }
                
                Log::info('✅ Thread recreado, esperando 2 segundos antes de reintentar...', [
                    'old_thread_id' => $threadId,
                    'new_thread_id' => $newThreadId,
                    'client_id' => $client->id
                ]);
                
                // Esperar 2 segundos para que el thread esté completamente listo
                sleep(2);
                
                // Intentar crear mensaje en el nuevo thread
                $userMessage = MCPAssistantService::createMessage($newThreadId, $request->message);
                $threadId = $newThreadId;
                
                // Si aún falla después del reintento, devolver error
                if (!$userMessage || $userMessage === 'THREAD_NOT_FOUND') {
                    Log::error('❌ No se pudo crear mensaje ni después de recrear thread', [
                        'thread_id' => $newThreadId,
                        'client_id' => $client->id
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo iniciar la conversación. Por favor, intenta nuevamente.',
                        'data' => [
                            'messages' => []
                        ]
                    ], 500);
                }
            }

            
            if (!$userMessage) {
                // Verificar si es porque hay runs activos
                if ($request->thread_id) {
                    Log::info('No se pudo crear mensaje (posible run activo)', [
                        'thread_id' => $threadId
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'processing_active',
                        'message' => 'Hay un procesamiento en curso. Por favor espera unos segundos e intenta nuevamente.',
                        'data' => [
                            'thread_id' => $threadId,
                            'should_retry' => true,
                            'messages' => [] // Devolver array vacío para evitar error de .map()
                        ]
                    ], 409); // Conflict
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo crear el mensaje',
                    'data' => [
                        'messages' => [] // Devolver array vacío para evitar error de .map()
                    ]
                ], 500);
            }

            // Ejecutar el asistente
            // 🆕 Pasar índice de ruta seleccionada para edición individual
            $selectedRouteIndex = $request->input('selected_route_index');
            $existingRoutesCount = $request->input('existing_routes_count', 0);
            
            Log::info('🎯 ChatController: Parámetros de ruta seleccionada', [
                'selected_route_index_raw' => $request->input('selected_route_index'),
                'selected_route_index_type' => gettype($selectedRouteIndex),
                'selected_route_index_value' => $selectedRouteIndex,
                'is_null' => $selectedRouteIndex === null,
                'is_zero' => $selectedRouteIndex === 0
            ]);
            
            $run = MCPAssistantService::runAssistant(
                $threadId, 
                $request->type_business, 
                $request->group_id,
                $selectedRouteIndex,
                $existingRoutesCount
            );
            
            // Caso especial: el thread ya no existe en OpenAI
            if ($run === 'THREAD_NOT_FOUND') {
                Log::warning('🔄 Thread no encontrado al ejecutar asistente, recreando...', [
                    'old_thread_id' => $threadId,
                    'client_id' => $client->id
                ]);
                
                // Limpiar thread del cliente
                $client->openai_thread_id = null;
                $client->openai_current_run = null;
                $client->save();
                
                // Crear nuevo thread
                $newThreadId = MCPAssistantService::getThread($client);
                if (!$newThreadId) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo recrear el thread de conversación',
                        'data' => [
                            'messages' => []
                        ]
                    ], 500);
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'thread_recreated',
                    'message' => 'Se ha creado una nueva conversación. Por favor, envía tu mensaje nuevamente.',
                    'data' => [
                        'thread_id' => $newThreadId,
                        'should_retry' => true,
                        'retry_after' => 2,
                        'messages' => []
                    ]
                ], 409); // Conflict - requiere reintento con nuevo thread
            }
            
            if (!$run || !isset($run['id'])) {
                // Si MCP falla, implementar fallback
                Log::warning('MCP assistant falló, implementando fallback', [
                    'client_id' => $request->client_id,
                    'thread_id' => $threadId
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'openai_timeout',
                    'message' => 'El asistente está experimentando demoras. Por favor, intenta de nuevo en unos momentos.',
                    'data' => [
                        'thread_id' => $threadId,
                        'should_retry' => true,
                        'retry_after' => 10, // segundos
                        'messages' => [] // Devolver array vacío para evitar error de .map()
                    ]
                ], 503); // Service Unavailable
            }

            // Actualizar el cliente con el run_id activo
            $client->openai_current_run = $run['id'];
            $client->openai_thread_id = $threadId;
            $client->save();
            
            Log::info('Cliente actualizado con run activo:', [
                'client_id' => $client->id,
                'run_id' => $run['id'],
                'thread_id' => $threadId,
                'has_extracted_data' => isset($run['extracted_data'])
            ]);

            // Obtener todos los mensajes actualizados - FILTRAR POR GROUP_ID si existe
            $messages = MCPAssistantService::getMessages($threadId, $request->group_id);

            // 🆕 Si usamos MCP síncrono, el run ya está completado - NO enviar run_id para evitar polling innecesario
            // El polling solo es necesario para runs asíncronos de OpenAI
            $isCompleted = !empty($messages) && count($messages) > 0;
            
            // 🆕 Obtener extracted_data - PRIMERO del grupo específico, luego del run
            $extractedData = $run['extracted_data'] ?? null;
            
            // 🔧 CRÍTICO: Si extracted_data es STRING JSON, decodificar
            if (is_string($extractedData)) {
                $extractedData = json_decode($extractedData, true);
            }
            
            // Intentar obtener del GRUPO específico (nueva lógica - evita mezcla entre cotizaciones)
            if ($request->group_id) {
                $group = \App\Models\GroupCotization::find($request->group_id);
                if ($group && $group->extracted_data) {
                    $groupExtractedData = json_decode($group->extracted_data, true);
                    if (!empty($groupExtractedData)) {
                        // 🔧 FIX: NO usar array_merge con rutas numéricas - solo usar datos del grupo
                        // El grupo ya tiene los datos más actualizados guardados por processToolCalls
                        $extractedData = $groupExtractedData;
                        Log::info('📦 extracted_data obtenido del GRUPO (sin merge)', [
                            'group_id' => $request->group_id,
                            'fields' => array_keys($extractedData)
                        ]);
                    }
                }
            }
            
            // 🔧 SEGURIDAD: Convertir a array si es necesario (puede ser objeto o null)
            if (is_object($extractedData)) {
                $extractedData = (array) $extractedData;
            } elseif (!is_array($extractedData)) {
                $extractedData = [];
            }
            
            // 🆕 CRÍTICO: Si es multi-ruta con claves numéricas (0, 1, 2...), 
            // convertir a array indexado para que JSON lo envíe como [...]  no como {"0": ..., "1": ...}
            if (!empty($extractedData)) {
                $keys = array_keys($extractedData);
                $allNumeric = count($keys) > 0 && array_reduce($keys, function($carry, $key) {
                    return $carry && is_numeric($key);
                }, true);
                
                if ($allNumeric && isset($extractedData[0])) {
                    // Es multi-ruta - reindexar para asegurar array secuencial
                    $extractedData = array_values($extractedData);
                    Log::info('🔄 Multi-ruta convertida a array indexado', [
                        'rutas' => count($extractedData)
                    ]);
                }
            }
            
            Log::info('Respuesta final del chat', [
                'is_completed' => $isCompleted,
                'messages_count' => count($messages),
                'will_send_run_id' => !$isCompleted,
                'has_extracted_data' => !empty($extractedData),
                'extracted_data_keys' => !empty($extractedData) ? array_keys($extractedData) : [],
                'is_multi_route' => is_array($extractedData) && isset($extractedData[0]) && is_array($extractedData[0]),
                'group_id' => $request->group_id
            ]);

            // 🆕 Obtener productos pendientes de selección (si existen)
            // SOLO enviar si el usuario está buscando productos, NO si edita otros campos
            $productosPendientes = null;
            if ($threadId) {
                // Buscar en la metadata de la sesión usando session_id (que almacena el thread_id)
                $session = \App\Models\ConversationSession::where('session_id', $threadId)->first();
                if ($session && $session->metadata) {
                    $metadata = json_decode($session->metadata, true);
                    
                    // 🆕 Verificar si el usuario está pidiendo cambio de producto
                    $mensajeLower = strtolower($request->message ?? '');
                    $pideCambioProducto = preg_match('/(?:producto|cambiar\s+producto|opci[oó]n\s*\d|selecciono?\s+\d)/ui', $mensajeLower);
                    
                    if (isset($metadata['productos_pendientes'])) {
                        // Solo enviar productos pendientes si el usuario está interactuando con productos
                        if ($pideCambioProducto) {
                            $productosPendientes = $metadata['productos_pendientes'];
                            Log::info('📦 Productos pendientes enviados (usuario pidió producto)', [
                                'count' => count($productosPendientes)
                            ]);
                        } else {
                            // Limpiar productos pendientes si el usuario está editando otro campo
                            unset($metadata['productos_pendientes']);
                            unset($metadata['producto_search_term']);
                            $session->metadata = json_encode($metadata);
                            $session->save();
                            Log::info('🧹 Productos pendientes limpiados (usuario editando otro campo)');
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'thread_id' => $threadId,
                    'run_id' => $isCompleted ? null : $run['id'],
                    'completed' => $isCompleted,
                    'user_message' => $userMessage,
                    'messages' => $messages,
                    'extracted_data' => $extractedData,
                    'productos_pendientes' => $productosPendientes // 🆕 Productos para mostrar como opciones
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en quote chat controller', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $request->client_id ?? 'no definido'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage(),
                'data' => [
                    'messages' => [] // Devolver array vacío para evitar error de .map()
                ]
            ], 500);
        }
    }

    public function getMessages(Request $request, $threadId)
    {
        try {
            $groupId = $request->query('group_id');
            
            Log::info('Get messages request', [
                'thread_id' => $threadId,
                'group_id' => $groupId
            ]);

            $messages = MCPAssistantService::getMessages($threadId, $groupId);

            return response()->json([
                'success' => true,
                'data' => [
                    'thread_id' => $threadId,
                    'group_id' => $groupId,
                    'messages' => $messages
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en get messages controller', [
                'message' => $e->getMessage(),
                'thread_id' => $threadId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkRunStatus($threadId, $runId)
    {
        try {
            Log::info('Check run status request', [
                'thread_id' => $threadId,
                'run_id' => $runId
            ]);

            $runData = MCPAssistantService::checkRunStatus($threadId, $runId);
            
            // Función helper para limpiar el run activo del cliente
            $clearClientActiveRun = function() use ($runId) {
                try {
                    $client = \App\Models\Client::where('openai_current_run', $runId)->first();
                    if ($client) {
                        $client->openai_current_run = null;
                        $client->save();
                        Log::info('Cliente run activo limpiado:', ['client_id' => $client->id, 'run_id' => $runId]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error limpiando run activo del cliente:', ['error' => $e->getMessage()]);
                }
            };
            
            if ($runData && is_array($runData)) {
                // Si hay datos extraídos del tool call
                $clearClientActiveRun();
                
                // Separar quote_data de extracted_data si vienen en el runData
                $extractedData = $runData['extracted_data'] ?? null;
                $quoteData = $runData['quote_data'] ?? $runData;
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'quote_data' => $quoteData,
                        'extracted_data' => $extractedData,  // NUEVO: incluir datos extraídos
                        'status' => 'completed_with_data'
                    ]
                ]);
            } else if ($runData === 'finished_with_indication') {
                // El asistente indica que las cotizaciones están completadas
                $clearClientActiveRun();
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'status' => 'completed_with_indication',
                        'message' => 'Las cotizaciones han sido creadas exitosamente'
                    ]
                ]);
            } else if ($runData === 'finished') {
                // El run está completado pero sin tool calls
                $clearClientActiveRun();
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'status' => 'completed'
                    ]
                ]);
            } else {
                // El run aún está en proceso o no hay datos
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'status' => 'in_progress'
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error en check run status controller', [
                'message' => $e->getMessage(),
                'thread_id' => $threadId,
                'run_id' => $runId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar runs colgados de un cliente específico
     */
    public function clearStuckRuns(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer'
        ]);

        try {
            $client = \App\Models\Client::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ], 404);
            }

            // Limpiar run actual si existe
            if ($client->openai_current_run) {
                Log::info('Limpiando run colgado', [
                    'client_id' => $client->id,
                    'old_run_id' => $client->openai_current_run
                ]);

                $client->openai_current_run = null;
                $client->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Runs limpiados exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando runs colgados', [
                'error' => $e->getMessage(),
                'client_id' => $request->client_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Limpiar mensajes de un grupo específico para empezar chat limpio
     */
    public function clearGroupMessages(Request $request)
    {
        try {
            $groupId = $request->group_id;
            
            if (!$groupId) {
                return response()->json([
                    'success' => false,
                    'error' => 'group_id es requerido'
                ], 400);
            }

            $deleted = ConversationMessage::where('group_cotization_id', $groupId)->delete();
            
            Log::info('Mensajes del grupo eliminados', [
                'group_id' => $groupId,
                'messages_deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deleted} mensajes del grupo {$groupId}",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando mensajes del grupo', [
                'error' => $e->getMessage(),
                'group_id' => $request->group_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al limpiar mensajes'
            ], 500);
        }
    }
    
    /**
     * 🆕 CHAT USANDO ASSISTANT API DE OPENAI
     * Usa el asistente asst_MnJ08tJG6NKOjbqFLvsYMqEp con herramientas configuradas
     */
    private function quoteChatWithOpenAIAssistant(Request $request, $client)
    {
        try {
            Log::info('🤖 Usando OpenAI Assistant Service', [
                'client_id' => $client->id,
                'type_business' => $request->type_business
            ]);
            
            // Obtener o crear thread - VALIDAR formato correcto (debe empezar con 'thread_')
            $threadId = $request->thread_id;
            
            // 🆕 Si el thread_id no tiene formato válido de OpenAI, ignorarlo y crear uno nuevo
            if ($threadId && !str_starts_with($threadId, 'thread_')) {
                Log::warning('⚠️ Thread ID inválido para OpenAI Assistant, creando nuevo', [
                    'invalid_thread_id' => $threadId
                ]);
                $threadId = null;
                // Limpiar thread del cliente también
                $client->openai_thread_id = null;
                $client->save();
            }
            
            if (!$threadId) {
                $threadId = QuoteAssistantService::getThread($client);
            }
            
            if (!$threadId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo crear el thread de conversación'
                ], 500);
            }
            
            // Crear mensaje del usuario
            $userMessage = QuoteAssistantService::createMessage($threadId, $request->message);
            
            if ($userMessage === 'THREAD_NOT_FOUND') {
                // Recrear thread
                $client->openai_thread_id = null;
                $client->save();
                $threadId = QuoteAssistantService::getThread($client);
                
                if (!$threadId) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo recrear el thread'
                    ], 500);
                }
                
                $userMessage = QuoteAssistantService::createMessage($threadId, $request->message);
            }
            
            if (!$userMessage) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo crear el mensaje'
                ], 500);
            }
            
            // Ejecutar el asistente
            $run = QuoteAssistantService::runAssistant($threadId, $request->type_business);
            
            if (!$run || !isset($run['id'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo ejecutar el asistente'
                ], 500);
            }
            
            // Guardar run_id
            $client->openai_current_run = $run['id'];
            $client->save();
            
            // Esperar respuesta (con timeout)
            $extractedData = QuoteAssistantService::waitForRunAndExtractData($threadId, $run['id'], 60);
            
            Log::info('📊 Datos extraídos del Assistant', [
                'has_data' => !empty($extractedData),
                'data_preview' => is_array($extractedData) ? array_keys($extractedData[0] ?? []) : 'not_array'
            ]);
            
            // Obtener mensajes actualizados
            $messages = QuoteAssistantService::getMessages($threadId);
            
            // Formatear respuesta
            return response()->json([
                'success' => true,
                'data' => [
                    'thread_id' => $threadId,
                    'run_id' => $run['id'],
                    'messages' => $messages,
                    'extracted_data' => $extractedData,
                    'assistant_service' => 'openai'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en quoteChatWithOpenAIAssistant', [
                'error' => $e->getMessage(),
                'client_id' => $client->id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error procesando mensaje: ' . $e->getMessage()
            ], 500);
        }
    }
}