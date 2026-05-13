<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupCotization;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GroupRecoveryController extends Controller
{
    /**
     * Recuperar datos de un grupo de cotización para continuar el proceso
     */
    public function recover($groupId)
    {
        try {
            Log::info('Recuperando datos del grupo para continuar cotización', [
                'group_id' => $groupId,
                'user_id' => auth()->id()
            ]);

            // Buscar el grupo con sus relaciones
            $group = GroupCotization::with([
                'client',
                'cotizaciones.pricing'
            ])->find($groupId);

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grupo de cotización no encontrado'
                ], 404);
            }

            // Verificar que el usuario tenga acceso al grupo
            // SUPER ADMIN y JEFE COMERCIAL pueden acceder a cualquier grupo
            $user = auth()->user();
            if ($group->user_id !== $user->id && !$user->hasAnyRole(['SUPER ADMIN', 'JEFE COMERCIAL'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a esta cotización'
                ], 403);
            }

            // Preparar los datos del cliente
            $clientData = [
                'id' => $group->client->id,
                'cliente' => $group->client->cliente,
                'name' => $group->client->cliente, // Alias para compatibilidad
                'documento' => $group->client->documento,
                'ciudad' => $group->client->ciudad,
                'telefono' => $group->client->telefono,
                'email' => $group->client->email,
                'direccion' => $group->client->direccion,
                'branch_office' => $group->client->branch_office,
                'vendedor_nombre' => $group->client->vendedor_nombre,
                'contacto' => $group->client->contacto,
                'cargo' => $group->client->cargo
            ];

            // Preparar los datos del grupo
            $groupData = [
                'id' => $group->id,
                'type' => $group->type,
                'status' => $group->status,
                'operation_type' => $group->operation_type,
                'openai_thread_id' => $group->openai_thread_id,
                'created_from_chat' => $group->created_from_chat,
                'candado_satelital' => $group->candado_satelital,
                'cargo_type' => $group->cargo_type,
                'jen_set' => $group->jen_set,
                'combustible' => $group->combustible,
                'kit_derrames' => $group->kit_derrames,
                'pictogramas' => $group->pictogramas,
                // 🚛 Incluir extracted_data para soportar formato multi-ruta
                'extracted_data' => $group->extracted_data ? json_decode($group->extracted_data, true) : null
            ];

            // Preparar los datos de las cotizaciones
            $cotizacionesData = $group->cotizaciones->map(function ($cotizacion) {
                return [
                    'id' => $cotizacion->id,
                    'ciudad_origen' => $cotizacion->ciudad_origen,
                    'ciudad_destino' => $cotizacion->ciudad_destino,
                    'ciudad_origen_dane' => $cotizacion->ciudad_origen_dane,
                    'ciudad_destino_dane' => $cotizacion->ciudad_destino_dane,
                    'peso_mercancia' => $cotizacion->peso_mercancia,
                    'cantidad' => $cotizacion->cantidad,
                    'tipo_embajale' => $cotizacion->tipo_embajale,
                    'dimensiones_exactas' => $cotizacion->dimensiones_exactas,
                    'valor_declarado' => $cotizacion->valor_declarado,
                    'tipo_mercancia' => $cotizacion->tipo_mercancia,
                    'porcentaje' => $cotizacion->porcentaje,
                    'pricing_id' => $cotizacion->pricing_id,
                    'pricing' => $cotizacion->pricing ? [
                        'id' => $cotizacion->pricing->id,
                        'vehicle_type' => $cotizacion->pricing->vehicle_type,
                        'price' => $cotizacion->pricing->price
                    ] : null,
                    // Parámetros automáticos
                    'candado_satelital' => $cotizacion->candado_satelital,
                    'jen_set' => $cotizacion->jen_set,
                    'combustible' => $cotizacion->combustible,
                    'kit_derrames' => $cotizacion->kit_derrames,
                    'pictogramas' => $cotizacion->pictogramas,
                    'valor' => $cotizacion->valor,
                    'finalValue' => $cotizacion->valor // Alias para compatibilidad
                ];
            });

            Log::info('Datos recuperados exitosamente', [
                'group_id' => $groupId,
                'client_id' => $group->client_id,
                'cotizaciones_count' => $cotizacionesData->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Datos recuperados exitosamente',
                'data' => [
                    'client' => $clientData,
                    'group' => $groupData,
                    'cotizaciones' => $cotizacionesData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al recuperar datos del grupo', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al recuperar la cotización'
            ], 500);
        }
    }
}