<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use App\Models\Client;
use App\Models\ConversationSession;
use App\Services\MCPAssistantService;
use App\Services\QuoteAssistantService;
use Illuminate\Support\Facades\Log;

class QuoteCreationController extends Controller
{
    public function createQuoteGroup(Request $request)
    {
        // Verificar autenticación
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], 401);
        }

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'client_type' => 'required|string',
            'operation_type' => 'required|string',
            'type_business' => 'required|string',
            'cargo_type' => 'nullable|string',
            'candado_satelital' => 'nullable|boolean',
            'jen_set' => 'nullable|boolean',
            'combustible' => 'nullable|boolean',
            'kit_derrames' => 'nullable|boolean',
            'pictogramas' => 'nullable|boolean',
        ]);

        try {
            $userId = auth()->id();
            
            Log::info('Verificando grupo borrador existente', [
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'type_business' => $request->type_business,
            ]);

            // 🆕 CAMBIO: Solo reutilizar un borrador si:
            // 1. NO tiene mensajes asociados
            // 2. Fue creado hace más de 2 minutos (evita race condition con múltiples pestañas)
            $existingDraft = GroupCotization::where('client_id', $request->client_id)
                ->where('user_id', $userId)
                ->where('status', 'borrador')
                ->where('created_at', '>=', now()->subDay())
                ->where('created_at', '<=', now()->subMinutes(2)) // 🆕 Solo si tiene más de 2 minutos
                ->whereDoesntHave('messages') // 🆕 Solo si NO tiene mensajes
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingDraft) {
                // Actualizar el borrador existente VACÍO y ANTIGUO en lugar de crear uno nuevo
                $existingDraft->update([
                    'type' => $request->type_business,
                    'operation_type' => $request->operation_type,
                    'candado_satelital' => $request->candado_satelital ?? false,
                    'cargo_type' => $request->cargo_type,
                    'load_type' => $request->load_type,
                    'jen_set' => $request->jen_set ?? false,
                    'combustible' => $request->combustible ?? false,
                    'kit_derrames' => $request->kit_derrames ?? false,
                    'pictogramas' => $request->pictogramas ?? false,
                    'extracted_data' => null, // 🆕 Limpiar datos extraídos
                ]);
                
                $group = $existingDraft;
                
                Log::info('✅ Grupo BORRADOR vacío y antiguo reutilizado', [
                    'group_id' => $group->id,
                    'created_at' => $group->created_at,
                    'age_minutes' => now()->diffInMinutes($group->created_at)
                ]);
            } else {
                // 🆕 SIEMPRE crear nuevo grupo si no hay borrador válido para reutilizar
                $group = GroupCotization::create([
                    'user_id' => $userId,
                    'client_id' => $request->client_id,
                    'type' => $request->type_business,
                    'operation_type' => $request->operation_type,
                    'status' => 'borrador',
                    'candado_satelital' => $request->candado_satelital ?? false,
                    'cargo_type' => $request->cargo_type,
                    'load_type' => $request->load_type,
                    'jen_set' => $request->jen_set ?? false,
                    'combustible' => $request->combustible ?? false,
                    'kit_derrames' => $request->kit_derrames ?? false,
                    'pictogramas' => $request->pictogramas ?? false,
                ]);
                
                Log::info('✅ Grupo BORRADOR creado (nuevo)', ['group_id' => $group->id]);
            }
            
            // Guardar group_id en sesión para que Livewire lo encuentre
            session()->put('current_group_id', $group->id);

            // Obtener el cliente y crear/obtener thread de MCP
            $client = Client::find($request->client_id);
            
            try {
                // Usar MCPAssistantService para el thread
                $threadId = MCPAssistantService::getThread($client);
                
                // 🆕 CRÍTICO: Limpiar extracted_data de la sesión para evitar mezcla de datos
                if ($threadId) {
                    $session = ConversationSession::where('session_id', $threadId)->first();
                    if ($session) {
                        $metadata = json_decode($session->metadata ?? '{}', true);
                        
                        // Limpiar datos extraídos anteriores
                        $metadata['extracted_data'] = [];
                        $metadata['current_group_id'] = $group->id;
                        $metadata['group_started_at'] = now()->toIso8601String();
                        
                        $session->metadata = json_encode($metadata);
                        $session->save();
                        
                        Log::info('🧹 Sesión limpiada para nuevo grupo', [
                            'thread_id' => $threadId,
                            'group_id' => $group->id,
                            'client_id' => $client->id
                        ]);
                    }
                }
            } catch (\Exception $openaiError) {
                Log::warning('MCP/OpenAI no disponible, continuando sin thread', [
                    'error' => $openaiError->getMessage(),
                    'client_id' => $client->id
                ]);
                $threadId = null;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'thread_id' => $threadId,
                    'client' => $client,
                    'openai_available' => !is_null($threadId) && !str_starts_with($threadId, 'local_thread_'),
                    'parametros_aplicados' => [
                        'candado_satelital' => $group->candado_satelital,
                        'jen_set' => $group->jen_set,
                        'combustible' => $group->combustible,
                        'kit_derrames' => $group->kit_derrames,
                        'pictogramas' => $group->pictogramas,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando grupo de cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'no_auth'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Limpiar thread de OpenAI cuando se inicia una nueva cotización
     * Esto asegura que cada cotización empiece con un chat en blanco
     */
    public function clearThread(Request $request)
    {
        try {
            $request->validate([
                'thread_id' => 'nullable|string',
                'client_id' => 'nullable|integer|exists:clients,id'
            ]);
            
            $threadId = $request->thread_id;
            $clientId = $request->client_id;
            
            // Localizar cliente por ID explícito o por thread
            $client = null;
            if ($clientId) {
                $client = Client::find($clientId);
            } elseif ($threadId) {
                $client = Client::where('openai_thread_id', $threadId)->first();
            }
            
            if (!$threadId && !$client) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay thread ni cliente para limpiar'
                ]);
            }
            
            Log::info('🗑️ Limpiando thread de OpenAI', [
                'thread_id' => $threadId,
                'client_id' => $client?->id,
                'user_id' => auth()->id()
            ]);
            
            if ($threadId) {
                // Intentar eliminar el thread de OpenAI
                try {
                    QuoteAssistantService::deleteThread($threadId);
                    Log::info('✅ Thread eliminado exitosamente de OpenAI', ['thread_id' => $threadId]);
                } catch (\Exception $openaiError) {
                    Log::warning('⚠️ No se pudo eliminar thread de OpenAI (puede no existir)', [
                        'thread_id' => $threadId,
                        'error' => $openaiError->getMessage()
                    ]);
                }
            }
            
            $clientReset = false;
            if ($client) {
                $client->openai_current_run = null;
                if (!$threadId || $client->openai_thread_id === $threadId) {
                    $client->openai_thread_id = null;
                }
                $client->save();
                $clientReset = true;
                Log::info('🧼 Cliente reiniciado para nuevo chat', [
                    'client_id' => $client->id
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Thread limpiado exitosamente',
                'client_reset' => $clientReset
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error limpiando thread', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error limpiando thread: ' . $e->getMessage()
            ], 500);
        }
    }
}
