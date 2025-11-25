<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuoteAssistantService;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrphanMessageController extends Controller
{
    /**
     * Detecta y procesa mensajes huérfanos en un thread específico
     */
    public function processOrphanMessages(Request $request)
    {
        $request->validate([
            'thread_id' => 'required|string',
            'client_id' => 'required|integer'
        ]);

        try {
            $threadId = $request->thread_id;
            $clientId = $request->client_id;

            Log::info('Procesando mensajes huérfanos', [
                'thread_id' => $threadId,
                'client_id' => $clientId
            ]);

            // Obtener el cliente
            $client = Client::find($clientId);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente no encontrado'
                ], 404);
            }

            // Obtener mensajes del thread
            $messages = QuoteAssistantService::getMessages($threadId);
            if (!$messages || !is_array($messages)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudieron obtener los mensajes del thread'
                ], 500);
            }

            // Analizar mensajes
            $userMessages = array_filter($messages, function($msg) {
                return isset($msg['role']) && $msg['role'] === 'user';
            });

            $assistantMessages = array_filter($messages, function($msg) {
                return isset($msg['role']) && $msg['role'] === 'assistant';
            });

            Log::info('Análisis de mensajes:', [
                'total' => count($messages),
                'user' => count($userMessages),
                'assistant' => count($assistantMessages)
            ]);

            // Si hay mensajes del usuario pero ninguno del asistente
            if (count($userMessages) > 0 && count($assistantMessages) === 0) {
                Log::info('Detectados mensajes huérfanos, creando run...');

                // Obtener el último mensaje del usuario
                $lastUserMessage = end($userMessages);
                $messageText = $lastUserMessage['text'] ?? '';

                if (empty($messageText)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Mensaje del usuario vacío'
                    ], 400);
                }

                // Crear run para procesar el mensaje huérfano
                $run = QuoteAssistantService::runAssistant($threadId, $client->type_business ?? 'dta');
                
                if (!$run || !isset($run['id'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo crear el run para procesar el mensaje huérfano'
                    ], 500);
                }

                // Actualizar el cliente con el run activo
                $client->openai_current_run = $run['id'];
                $client->openai_thread_id = $threadId;
                $client->save();

                Log::info('Run creado para mensaje huérfano:', [
                    'run_id' => $run['id'],
                    'client_id' => $clientId,
                    'message_text' => substr($messageText, 0, 100) . '...'
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'run_id' => $run['id'],
                        'message' => 'Run creado exitosamente para procesar mensaje huérfano',
                        'user_messages_count' => count($userMessages),
                        'assistant_messages_count' => count($assistantMessages)
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'thread_id' => $threadId,
                        'message' => 'No se detectaron mensajes huérfanos',
                        'user_messages_count' => count($userMessages),
                        'assistant_messages_count' => count($assistantMessages)
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error procesando mensajes huérfanos:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'thread_id' => $request->thread_id ?? 'N/A',
                'client_id' => $request->client_id ?? 'N/A'
            ]);

            // Si es un error de OpenAI, devolver mensaje más amigable
            if (str_contains($e->getMessage(), 'OpenAI') || str_contains($e->getMessage(), 'API')) {
                return response()->json([
                    'success' => false,
                    'error' => 'openai_unavailable',
                    'message' => 'El servicio de IA está temporalmente no disponible. Los mensajes se procesarán automáticamente cuando esté disponible.',
                    'data' => [
                        'thread_id' => $request->thread_id ?? null,
                        'should_retry' => false
                    ]
                ], 503);
            }

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => 'No se pudieron procesar los mensajes huérfanos'
            ], 500);
        }
    }

    /**
     * Lista todos los clientes con posibles mensajes huérfanos
     */
    public function listOrphanCandidates()
    {
        try {
            $clients = Client::whereNotNull('openai_thread_id')
                           ->whereNull('openai_current_run')
                           ->get(['id', 'name', 'document', 'openai_thread_id', 'type_business']);

            Log::info('Clientes candidatos a tener mensajes huérfanos:', ['count' => $clients->count()]);

            $candidates = [];
            foreach ($clients as $client) {
                $candidates[] = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'client_document' => $client->document,
                    'thread_id' => $client->openai_thread_id,
                    'type_business' => $client->type_business
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'candidates' => $candidates,
                    'count' => count($candidates)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error listando candidatos a mensajes huérfanos:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}