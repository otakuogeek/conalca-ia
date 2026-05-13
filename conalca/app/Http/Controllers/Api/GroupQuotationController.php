<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use Illuminate\Support\Facades\Log;

class GroupQuotationController extends Controller
{
    public function groupsQuotes()
    {
        try {
            $user = auth()->user();

            // Si no hay usuario autenticado, devolver vacío
            if (!$user) {
                return response()->json(['data' => []]);
            }

            // Columnas necesarias del grupo (solo las que usa el frontend)
            $groupColumns = [
                'id', 'user_id', 'client_id', 'status', 'type',
                'operation_type', 'reference', 'created_at', 'updated_at'
            ];

            // Columnas necesarias de cotizaciones (tarjeta + detalle modal)
            $cotizacionColumns = [
                'id', 'group_cotization_id', 'client_id', 'pricing_id',
                'ciudad_origen', 'ciudad_destino', 'valor', 'flete', 'porcentaje',
                'decision_cliente', 'created_at',
                // Campos de carga (usados por TransitGroupModal)
                'tipo_mercancia', 'tipo_producto', 'peso_mercancia',
                'dimensiones_exactas', 'cantidad', 'cantidad_vh',
                'tipo_embajale', 'tipo_carroceria', 'vehiculo_requerido',
                'valor_declarado', 'seguro', 'temperatura_mercancia',
                'registro_fotografico', 'fecha_hora_descargue_cargue',
            ];

            $query = GroupCotization::select($groupColumns)
                ->with([
                    'client:id,cliente,documento,telefono,direccion,ciudad,vigenciacamara,fecha',
                    'cotizaciones' => function ($q) use ($cotizacionColumns) {
                        $q->select($cotizacionColumns);
                    },
                    'cotizaciones.pricing:id,price',
                    'cotizaciones.solicitud:id,cotizacion_model_id,estado,silogtran_status',
                ])
                // Excluir borradores vacíos (sin cotizaciones)
                ->where(function ($q) {
                    $q->where('status', '!=', 'borrador')
                      ->orWhereHas('cotizaciones');
                })
                ->orderBy('created_at', 'desc');
            
            // Si NO es super admin, solo le mostramos sus grupos
            if (!$user->hasRole('SUPER ADMIN') && !$user->hasRole('GERENTE DE CUENTA')) {
                $query->where('user_id', $user->id);
            }

            $groups_quotes = $query->get()
                ->map(function ($group) {
                    $group->valor_total = $group->cotizaciones->sum(function ($quote) {
                        // Usar el valor guardado directamente o calcular si no existe
                        $valorGuardado = floatval($quote->valor ?? 0);
                        if ($valorGuardado > 0) {
                            return $valorGuardado;
                        }
                        
                        // Fallback al cálculo manual si no hay valor guardado
                        if ($quote->pricing) {
                            $precioBase = floatval($quote->pricing->price ?? 0);
                            $porcentaje = floatval($quote->porcentaje ?? 0);
                            return $precioBase + ($precioBase * $porcentaje / 100);
                        }
                        
                        return 0;
                    });

                    // Ocultar producto_label del JSON (no se usa en la tarjeta)
                    $group->cotizaciones->each(function ($cot) {
                        $cot->makeHidden('producto_label');
                    });

                    return $group;
                });

            return response()->json([
                'data' => $groups_quotes
            ]);
        } catch (\Exception $e) {
            Log::error('Error en groupsQuotes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al cargar grupos de cotizaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        $group = GroupCotization::findOrFail($id);
        $group->status = $request->status; // Puede ser 'En tránsito', 'Pre-Solicitud', etc o el nombre de una columna custom
        $group->save();
        return response()->json(['success' => true]);
    }

    public function accept($id)
    {
        // ADD NOTIFICATION  ADVISER, THE CLIENT ACCEPT THE QUOTE
        $group = GroupCotization::findOrFail($id);
        $group->status = 'aceptada'; // o 'Aprobada', como quieras
        $group->save();

        // Puedes notificar al comercial aquí si quieres

        return view('quotes.result', [
            'mensaje' => '¡Gracias por aceptar la cotización! Nuestro equipo gestionará tu transporte lo antes posible.',
            'esAceptada' => true,
        ]);
    }

    public function cancel($id)
    {
        // ADD NOTIFICATION  ADVISER, THE CLIENT REFUSE THE QUOTE

        $group = GroupCotization::findOrFail($id);
        $group->status = 'rechazada'; // o 'Cancelada'
        $group->save();

        // Puedes notificar al comercial aquí si quieres

        return view('quotes.result', [
            'mensaje' => '¡Cotización rechazada! Hemos informado al asesor comercial para gestionar una nueva propuesta o aclarar tus inquietudes.',
            'esAceptada' => false,
        ]);
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $group = GroupCotization::findOrFail($id);

            // Verificar permisos: solo el creador del grupo o admin puede eliminarlo
            if (!$user->hasRole('SUPER ADMIN') && !$user->hasRole('GERENTE DE CUENTA') && $group->user_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este grupo de cotización.'
                ], 403);
            }

            // Verificar que el grupo esté en estado Pre-Solicitud
            if ($group->status !== 'Pre-Solicitud' && $group->status !== 'borrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar grupos en estado Pre-Solicitud.'
                ], 400);
            }

            // Eliminar el grupo y sus cotizaciones relacionadas
            $group->cotizaciones()->delete();
            $group->delete();

            Log::info('Grupo de cotización eliminado', [
                'group_id' => $id,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Grupo de cotización eliminado exitosamente.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar grupo de cotización', [
                'group_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al eliminar el grupo.'
            ], 500);
        }
    }
}
