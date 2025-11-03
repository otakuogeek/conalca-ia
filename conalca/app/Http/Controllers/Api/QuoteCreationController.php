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
            
            Log::info('Creando grupo de cotización con parámetros automáticos', [
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'type_business' => $request->type_business,
                'cargo_type' => $request->cargo_type,
                'parametros_automaticos' => [
                    'candado_satelital' => $request->candado_satelital,
                    'jen_set' => $request->jen_set,
                    'combustible' => $request->combustible,
                    'kit_derrames' => $request->kit_derrames,
                    'pictogramas' => $request->pictogramas,
                ]
            ]);

            // Crear el grupo de cotización con los parámetros automáticos
            $group = GroupCotization::create([
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'type' => $request->type_business,
                'status' => 'borrador',
                'candado_satelital' => $request->candado_satelital ?? false,
                'cargo_type' => $request->cargo_type,
                'jen_set' => $request->jen_set ?? false,
                'combustible' => $request->combustible ?? false,
                'kit_derrames' => $request->kit_derrames ?? false,
                'pictogramas' => $request->pictogramas ?? false,
            ]);

            // Obtener el cliente y crear/obtener thread de OpenAI
            $client = Client::find($request->client_id);
            $threadId = QuoteAssistantService::getThread($client);

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'thread_id' => $threadId,
                    'client' => $client,
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
}
