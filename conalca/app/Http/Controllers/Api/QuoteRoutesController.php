<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupCotization;
use App\Models\CotizacionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuoteRoutesController extends Controller
{
    /**
     * Guarda las rutas extraídas del chat como registros individuales de cotización
     */
    public function saveQuoteRoutes(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer|exists:group_cotizations,id',
            'routes' => 'required|array|min:1',
            'routes.*.ciudad_origen' => 'required|string',
            'routes.*.ciudad_destino' => 'required|string',
        ]);

        try {
            $group = GroupCotization::findOrFail($request->group_id);
            
            // Verificar que el usuario tenga permisos sobre este grupo
            if ($group->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para modificar esta cotización'
                ], 403);
            }

            Log::info('Guardando rutas del chat', [
                'group_id' => $request->group_id,
                'routes_count' => count($request->routes)
            ]);

            $savedRoutes = [];

            foreach ($request->routes as $index => $routeData) {
                Log::info('Procesando ruta', [
                    'index' => $index,
                    'origen' => $routeData['ciudad_origen'] ?? 'no definido',
                    'destino' => $routeData['ciudad_destino'] ?? 'no definido'
                ]);

                // Crear registro de cotización individual
                $cotization = CotizacionModel::create([
                    'group_cotization_id' => $group->id,
                    'user_id' => Auth::id(),
                    'client_id' => $group->client_id,
                    'ciudad_origen' => $routeData['ciudad_origen'],
                    'ciudad_destino' => $routeData['ciudad_destino'],
                    'peso_mercancia' => $this->parseNumericField($routeData['peso_mercancia'] ?? '0'),
                    'cantidad' => $this->parseNumericField($routeData['cantidad'] ?? '1'),
                    'tipo_embajale' => $routeData['tipo_embajale'] ?? 'Caja',
                    'tipo_producto' => $routeData['tipo_producto'] ?? 'Mercancía general',
                    'vehiculo_requerido' => $routeData['vehiculo_requerido'] ?? 'Sencillo',
                    'valor_declarado' => $this->parseMoneyField($routeData['valor_declarado'] ?? '0'),
                    'estado' => 'creada',
                    'decision_cliente' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $savedRoutes[] = [
                    'id' => $cotization->id,
                    'ciudad_origen' => $cotization->ciudad_origen,
                    'ciudad_destino' => $cotization->ciudad_destino,
                    'peso_mercancia' => $cotization->peso_mercancia,
                    'cantidad' => $cotization->cantidad,
                    'tipo_producto' => $cotization->tipo_producto,
                    'vehiculo_requerido' => $cotization->vehiculo_requerido,
                    'valor_declarado' => $cotization->valor_declarado,
                ];
            }

            Log::info('Rutas guardadas exitosamente', [
                'group_id' => $group->id,
                'saved_routes_count' => count($savedRoutes)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'routes_saved' => count($savedRoutes),
                    'routes' => $savedRoutes
                ],
                'message' => 'Rutas guardadas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando rutas del chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $request->group_id ?? 'no definido'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las rutas de un grupo de cotización
     */
    public function getQuoteRoutes($groupId)
    {
        try {
            $group = GroupCotization::findOrFail($groupId);
            
            // Verificar permisos
            if ($group->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para ver esta cotización'
                ], 403);
            }

            $routes = $group->cotizaciones()->get()->map(function ($cotization) {
                return [
                    'id' => $cotization->id,
                    'ciudad_origen' => $cotization->ciudad_origen,
                    'ciudad_destino' => $cotization->ciudad_destino,
                    'peso_mercancia' => $cotization->peso_mercancia,
                    'cantidad' => $cotization->cantidad,
                    'tipo_producto' => $cotization->tipo_producto,
                    'vehiculo_requerido' => $cotization->vehiculo_requerido,
                    'valor_declarado' => $cotization->valor_declarado,
                    'estado' => $cotization->estado,
                    'decision_cliente' => $cotization->decision_cliente,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'routes_count' => $routes->count(),
                    'routes' => $routes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo rutas del grupo', [
                'error' => $e->getMessage(),
                'group_id' => $groupId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convierte campos de texto a numéricos
     */
    private function parseNumericField($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Extraer solo números del texto
        $numeric = preg_replace('/[^0-9.]/', '', $value);
        return $numeric ? (float) $numeric : 0;
    }

    /**
     * Convierte campos monetarios removiendo separadores
     */
    private function parseMoneyField($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remover caracteres no numéricos excepto punto decimal
        $numeric = preg_replace('/[^0-9.]/', '', $value);
        return $numeric ? (float) $numeric : 0;
    }
}