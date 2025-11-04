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
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
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
            $threadId = $request->thread_id ?: QuoteAssistantService::getThread($client);
            if (!$threadId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo crear/obtener el thread de conversación'
                ], 500);
            }

            // Verificar si hay un run activo antes de crear el mensaje
            if ($request->thread_id && $client->openai_current_run) {
                $runStatus = QuoteAssistantService::checkRunStatus($threadId, $client->openai_current_run);
                if ($runStatus === null) {
                    // El run aún está activo, devolver error amigable
                    return response()->json([
                        'success' => false,
                        'error' => 'processing_active',
                        'message' => 'Hay un procesamiento en curso. Por favor espera unos segundos.',
                        'data' => [
                            'thread_id' => $threadId,
                            'active_run_id' => $client->openai_current_run
                        ]
                    ], 409); // Conflict
                }
            }

            // Crear el mensaje del usuario
            $userMessage = QuoteAssistantService::createMessage($threadId, $request->message);
            if (!$userMessage) {
                // Verificar si es porque hay runs activos
                if ($request->thread_id) {
                    return response()->json([
                        'success' => false,
                        'error' => 'processing_active',
                        'message' => 'Hay un procesamiento en curso. Por favor espera unos segundos e intenta nuevamente.',
                        'data' => [
                            'thread_id' => $threadId,
                            'should_retry' => true
                        ]
                    ], 409); // Conflict
                }
                
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
                'error' => 'Error interno del servidor: ' . $e->getMessage()
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
}