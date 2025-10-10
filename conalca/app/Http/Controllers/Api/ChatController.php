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
}