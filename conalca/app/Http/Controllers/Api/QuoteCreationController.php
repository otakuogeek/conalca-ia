<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use App\Models\Client;
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

            // Buscar si ya existe un grupo borrador reciente (últimas 24 horas)
            $existingDraft = GroupCotization::where('client_id', $request->client_id)
                ->where('user_id', $userId)
                ->where('status', 'borrador')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingDraft) {
                // Actualizar el borrador existente en lugar de crear uno nuevo
                $existingDraft->update([
                    'type' => $request->type_business,
                    'operation_type' => $request->operation_type,
                    'candado_satelital' => $request->candado_satelital ?? false,
                    'cargo_type' => $request->cargo_type,
                    'jen_set' => $request->jen_set ?? false,
                    'combustible' => $request->combustible ?? false,
                    'kit_derrames' => $request->kit_derrames ?? false,
                    'pictogramas' => $request->pictogramas ?? false,
                ]);
                
                $group = $existingDraft;
                
                Log::info('✅ Grupo BORRADOR actualizado (reutilizado)', [
                    'group_id' => $group->id,
                    'created_at' => $group->created_at
                ]);
            } else {
                // Crear nuevo grupo borrador solo si no existe uno reciente
                $group = GroupCotization::create([
                    'user_id' => $userId,
                    'client_id' => $request->client_id,
                    'type' => $request->type_business,
                    'operation_type' => $request->operation_type,
                    'status' => 'borrador',
                    'candado_satelital' => $request->candado_satelital ?? false,
                    'cargo_type' => $request->cargo_type,
                    'jen_set' => $request->jen_set ?? false,
                    'combustible' => $request->combustible ?? false,
                    'kit_derrames' => $request->kit_derrames ?? false,
                    'pictogramas' => $request->pictogramas ?? false,
                ]);
                
                Log::info('✅ Grupo BORRADOR creado (nuevo)', ['group_id' => $group->id]);
            }
            
            // Guardar group_id en sesión para que Livewire lo encuentre
            session()->put('current_group_id', $group->id);

            // Obtener el cliente y crear/obtener thread de OpenAI
            $client = Client::find($request->client_id);
            
            try {
                $threadId = QuoteAssistantService::getThread($client);
            } catch (\Exception $openaiError) {
                Log::warning('OpenAI no disponible, continuando sin thread', [
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
