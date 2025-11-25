<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuoteAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
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
            'type_business' => 'required|string'
        ]);

        try {
            Log::info('Quote chat request iniciado', [
                'client_id' => $request->client_id,
                'thread_id' => $request->thread_id,
                'type_business' => $request->type_business,
                'message_length' => strlen($request->message)
            ]);

            // Obtener o crear el thread de OpenAI
            $client = \App\Models\Client::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ], 404);
            }

            // Obtener o crear thread
            $threadId = $request->thread_id;
            
            try {
                if (!$threadId) {
                    $threadId = QuoteAssistantService::getThread($client);
                }
                
                if (!$threadId) {
                    throw new \Exception('No se pudo crear/obtener el thread de conversación');
                }
            } catch (\Exception $openaiError) {
                Log::warning('OpenAI no disponible para chat quote', [
                    'error' => $openaiError->getMessage(),
                    'client_id' => $client->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Servicio de chat temporalmente no disponible. Intenta nuevamente en unos momentos.',
                    'error_type' => 'openai_unavailable'
                ], 503);
            }

            // Verificar si hay un run activo antes de crear el mensaje
            if ($request->thread_id && $client->openai_current_run) {
                try {
                    $runStatus = QuoteAssistantService::checkRunStatus($threadId, $client->openai_current_run);
                    
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
            $userMessage = QuoteAssistantService::createMessage($threadId, $enhancedMessage);
            
            // Caso especial: el thread ya no existe en OpenAI
            if ($userMessage === 'THREAD_NOT_FOUND') {
                Log::warning('🔄 Thread no encontrado, recreando...', [
                    'old_thread_id' => $threadId,
                    'client_id' => $client->id
                ]);
                
                // Limpiar thread del cliente y cancelar runs si existía
                if ($client->openai_thread_id) {
                    QuoteAssistantService::cancelActiveRuns($client->openai_thread_id);
                }
                $client->openai_thread_id = null;
                $client->openai_current_run = null;
                $client->save();
                
                // Crear nuevo thread
                $newThreadId = QuoteAssistantService::getThread($client);
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
                $userMessage = QuoteAssistantService::createMessage($newThreadId, $request->message);
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
            $run = QuoteAssistantService::runAssistant($threadId, $request->type_business);
            if (!$run || !isset($run['id'])) {
                // Si OpenAI falla, implementar fallback
                Log::warning('OpenAI assistant falló, implementando fallback', [
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
                'thread_id' => $threadId
            ]);

            // Obtener todos los mensajes actualizados
            $messages = QuoteAssistantService::getMessages($threadId);

            return response()->json([
                'success' => true,
                'data' => [
                    'thread_id' => $threadId,
                    'run_id' => $run['id'],
                    'user_message' => $userMessage,
                    'messages' => $messages
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

    public function getMessages($threadId)
    {
        try {
            Log::info('Get messages request', [
                'thread_id' => $threadId
            ]);

            $messages = QuoteAssistantService::getMessages($threadId);

            return response()->json([
                'success' => true,
                'data' => [
                    'thread_id' => $threadId,
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

            $runData = QuoteAssistantService::checkRunStatus($threadId, $runId);
            
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
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'quote_data' => $runData,
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
}