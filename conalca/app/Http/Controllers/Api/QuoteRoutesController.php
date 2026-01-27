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
        // Log de datos recibidos para diagnosticar el error 422
        // Log::info('saveQuoteRoutes - Datos recibidos:', [
        //     'body' => $request->all(),
        //     'has_group_id' => $request->has('group_id'),
        //     'has_routes' => $request->has('routes'),
        //     'routes_type' => gettype($request->input('routes')),
        //     'routes_count' => is_array($request->input('routes')) ? count($request->input('routes')) : 'no es array'
        // ]);

        $request->validate([
            'group_id' => 'required|integer|exists:group_cotizations,id',
            'routes' => 'required|array|min:1',
            'routes.*.ciudad_origen' => 'required|string',
            'routes.*.ciudad_destino' => 'required|string',
            'routes.*.id'             => 'nullable|integer',
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

            Log::info('🔍 Guardando rutas del chat - DATOS COMPLETOS', [
                'group_id' => $request->group_id,
                'routes_count' => count($request->routes),
                'todas_las_rutas' => $request->routes // 🔥 Ver TODAS las rutas que llegan
            ]);

            // ⚠️ PREVENIR DUPLICACIÓN: Obtener IDs existentes y eliminar los que no vienen en la petición
            $existingIds = $group->cotizaciones()->pluck('id')->toArray();
            $incomingIds = collect($request->routes)->pluck('id')->filter()->toArray();
            $idsToDelete = array_diff($existingIds, $incomingIds);
            
            if (!empty($idsToDelete)) {
                Log::info('Eliminando rutas que ya no existen en el frontend', [
                    'ids_to_delete' => $idsToDelete
                ]);
                CotizacionModel::whereIn('id', $idsToDelete)
                    ->where('group_cotization_id', $group->id)
                    ->delete();
            }

            $savedRoutes = [];

            foreach ($request->routes as $index => $routeData) {
                // 🔍 DEBUG: Log completo para diagnosticar problema de peso
                Log::info('🔍 Procesando ruta - DETALLE COMPLETO', [
                    'index'   => $index,
                    'id'      => $routeData['id'] ?? null,
                    'origen'  => $routeData['ciudad_origen'] ?? 'no definido',
                    'destino' => $routeData['ciudad_destino'] ?? 'no definido',
                    'peso_mercancia_recibido' => $routeData['peso_mercancia'] ?? 'NO ENVIADO',
                    'peso_mercancia_parseado' => $this->parseNumericField($routeData['peso_mercancia'] ?? '0'),
                    'vehiculo_recibido' => $routeData['vehiculo_requerido'] ?? 'NO ENVIADO',
                    'producto_recibido' => $routeData['producto'] ?? $routeData['tipo_producto'] ?? 'NO ENVIADO'
                ]);

                $cotization = null;

                if (!empty($routeData['id'])) {
                    $cotization = CotizacionModel::where('id', $routeData['id'])
                        ->where('group_cotization_id', $group->id)
                        ->first();
                }

                // 🆕 Extraer producto con prioridad: producto_mencionado > producto > tipo_producto
                $producto = mb_substr(
                    $routeData['producto_mencionado'] 
                        ?? $routeData['producto'] 
                        ?? $routeData['tipo_producto'] 
                        ?? 'Mercancía general',
                    0, 191
                );

                if ($cotization) {
                    // UPDATE existing
                    $cotization->update([
                        'ciudad_origen'    => $routeData['ciudad_origen'],
                        'ciudad_destino'   => $routeData['ciudad_destino'],
                        'peso_mercancia'   => $this->parseNumericField($routeData['peso_mercancia'] ?? '0'),
                        'cantidad'         => $this->parseNumericField($routeData['cantidad'] ?? '1'),
                        'tipo_embajale'    => $routeData['tipo_embajale'] ?? 'Caja',
                        'tipo_producto'    => $producto, // 🔥 Usar producto con prioridad correcta
                        'vehiculo_requerido' => $routeData['vehiculo_requerido'] ?? 'Sencillo',
                        'valor_declarado'  => $this->parseMoneyField($routeData['valor_declarado'] ?? '0'),
                        'pricing_id'       => $routeData['pricing_id'] ?? null,
                        'porcentaje'       => $this->parseNumericField($routeData['porcentaje'] ?? '0'),
                        'valor_cliente'    => $this->parseMoneyField($routeData['valor_cliente'] ?? '0'),
                        // add any extra fields you need to persist (candado_satelital, etc.)
                    ]);
                } else {
                    // CREATE new
                    $cotization = CotizacionModel::create([
                        'group_cotization_id' => $group->id,
                        'user_id'             => Auth::id(),
                        'client_id'           => $group->client_id,
                        'ciudad_origen'       => $routeData['ciudad_origen'],
                        'ciudad_destino'      => $routeData['ciudad_destino'],
                        'peso_mercancia'      => $this->parseNumericField($routeData['peso_mercancia'] ?? '0'),
                        'cantidad'            => $this->parseNumericField($routeData['cantidad'] ?? '1'),
                        'tipo_embajale'       => $routeData['tipo_embajale'] ?? 'Caja',
                        'tipo_producto'       => $producto, // 🔥 Usar producto con prioridad correcta
                        'vehiculo_requerido'  => $routeData['vehiculo_requerido'] ?? 'Sencillo',
                        'valor_declarado'     => $this->parseMoneyField($routeData['valor_declarado'] ?? '0'),
                        'pricing_id'          => $routeData['pricing_id'] ?? null,
                        'porcentaje'          => $this->parseNumericField($routeData['porcentaje'] ?? '0'),
                        'valor_cliente'       => $this->parseMoneyField($routeData['valor_cliente'] ?? '0'),
                        'decision_cliente'    => 'pendiente',
                        'active'              => 1,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }

                $savedRoutes[] = [
                    'id'               => $cotization->id,
                    'ciudad_origen'    => $cotization->ciudad_origen,
                    'ciudad_destino'   => $cotization->ciudad_destino,
                    'peso_mercancia'   => $cotization->peso_mercancia,
                    'cantidad'         => $cotization->cantidad,
                    'tipo_producto'    => $cotization->tipo_producto,
                    'vehiculo_requerido' => $cotization->vehiculo_requerido,
                    'valor_declarado'  => $cotization->valor_declarado,
                    'pricing_id'       => $cotization->pricing_id,
                    'porcentaje'       => $cotization->porcentaje,
                    'valor_cliente'    => $cotization->valor_cliente,
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

            // 🆕 Obtener extracted_data del grupo para incluir producto_mencionado y otros campos IA
            $extractedData = [];
            if ($group->extracted_data) {
                $extractedData = is_string($group->extracted_data) 
                    ? json_decode($group->extracted_data, true) 
                    : $group->extracted_data;
                
                // Asegurar que es un array indexado si tiene índice 0
                if (is_array($extractedData) && isset($extractedData[0])) {
                    $extractedData = array_values($extractedData);
                }
            }

            $cotizaciones = $group->cotizaciones()->get();
            
            // 🔧 FIX #558: Si NO hay cotizaciones guardadas, construir rutas desde extracted_data
            if ($cotizaciones->isEmpty() && !empty($extractedData)) {
                Log::info('🔧 getQuoteRoutes: No hay cotizaciones, construyendo desde extracted_data', [
                    'group_id' => $groupId,
                    'extracted_data' => $extractedData
                ]);
                
                // 🚛 DETECTAR MULTI-RUTA PRIMERO
                $routesArray = [];
                if (isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
                    // Es multi-ruta, usar el array 'rutas'
                    $routesArray = $extractedData['rutas'];
                    Log::info('🚛 Multi-ruta detectada en extracted_data', ['total' => count($routesArray)]);
                } elseif (isset($extractedData[0])) {
                    // Es un array indexado de rutas
                    $routesArray = $extractedData;
                } else {
                    // Es un objeto único (ruta única)
                    $routesArray = [$extractedData];
                }
                
                $routes = collect($routesArray)->map(function ($extracted, $index) {
                    return [
                        'id' => null,
                        'ruta_numero' => $index + 1,
                        'ciudad_origen' => $extracted['ciudad_origen'] ?? $extracted['origen'] ?? null,
                        'ciudadOrigen' => $extracted['ciudad_origen'] ?? $extracted['origen'] ?? null,
                        'ciudad_destino' => $extracted['ciudad_destino'] ?? $extracted['destino'] ?? null,
                        'ciudadDestino' => $extracted['ciudad_destino'] ?? $extracted['destino'] ?? null,
                        'peso_mercancia' => $extracted['peso_kg'] ?? $extracted['peso'] ?? null,
                        'pesoMercancia' => $extracted['peso_kg'] ?? $extracted['peso'] ?? null,
                        'peso_kg' => $extracted['peso_kg'] ?? $extracted['peso'] ?? null,
                        'incluye_tara' => $extracted['incluye_tara'] ?? true,
                        'cantidad' => $extracted['cantidad'] ?? 1,
                        'cantidadMercancia' => $extracted['cantidad'] ?? 1,
                        'tipo_embajale' => $extracted['empaque'] ?? 'Caja',
                        'empaque' => $extracted['empaque'] ?? 'Caja',
                        'empaque_id' => $extracted['empaque_id'] ?? null,
                        'producto' => $extracted['producto_mencionado'] ?? $extracted['producto'] ?? $extracted['tipo_producto'] ?? null,
                        'producto_mencionado' => $extracted['producto_mencionado'] ?? $extracted['producto'] ?? null,
                        'tipo_producto' => $extracted['tipo_producto'] ?? $extracted['producto'] ?? null,
                        'producto_codigo' => $extracted['producto_codigo'] ?? null,
                        'producto_nombre' => $extracted['producto_nombre'] ?? null,
                        'vehiculo_requerido' => $extracted['vehiculo_requerido'] ?? $extracted['vehiculo'] ?? 'Sencillo',
                        'vehiculo' => $extracted['vehiculo_requerido'] ?? $extracted['vehiculo'] ?? 'Sencillo',
                        'claseVehiculo' => $extracted['claseVehiculo'] ?? $extracted['vehiculo'] ?? 'Sencillo',
                        'valor_declarado' => $extracted['valor_declarado'] ?? $extracted['valor'] ?? null,
                        'valorMercancia' => $extracted['valor_declarado'] ?? $extracted['valor'] ?? null,
                        'active' => 1,
                        'decision_cliente' => 'pendiente',
                        'incluye_tara' => $extracted['incluye_tara'] ?? false,
                    ];
                });
            } else {
                // Hay cotizaciones guardadas, usar lógica normal
                $routes = $cotizaciones->map(function ($cotization, $index) use ($extractedData) {
                // 🔧 FIX #558: Buscar datos extraídos correspondientes a esta ruta
                // Si extracted_data es un objeto único (no tiene índice 0), usarlo directamente para la primera ruta
                $extracted = [];
                if (!empty($extractedData)) {
                    if (isset($extractedData[$index])) {
                        // Es un array de rutas
                        $extracted = $extractedData[$index];
                    } elseif ($index === 0 && !isset($extractedData[0])) {
                        // Es un objeto único (ruta simple), usar todo para la primera ruta
                        $extracted = $extractedData;
                    }
                }
                
                return [
                    'id' => $cotization->id,
                    'ruta_numero' => $index + 1,
                    // 🔧 FIX: Priorizar origen/destino de extracted_data (fuente de verdad) sobre tabla cotizacion_models
                    'ciudad_origen' => $extracted['ciudad_origen'] ?? $extracted['origen'] ?? $cotization->ciudad_origen,
                    'ciudadOrigen' => $extracted['ciudad_origen'] ?? $extracted['origen'] ?? $cotization->ciudad_origen,
                    'ciudad_destino' => $extracted['ciudad_destino'] ?? $extracted['destino'] ?? $cotization->ciudad_destino,
                    'ciudadDestino' => $extracted['ciudad_destino'] ?? $extracted['destino'] ?? $cotization->ciudad_destino,
                    // 🔧 FIX: Priorizar peso_kg de extracted_data (fuente de verdad) sobre peso_mercancia de BD
                    'peso_mercancia' => $extracted['peso_kg'] ?? $cotization->peso_mercancia,
                    'pesoMercancia' => $extracted['peso_kg'] ?? $cotization->peso_mercancia,
                    'peso_kg' => $extracted['peso_kg'] ?? $cotization->peso_mercancia,
                    'incluye_tara' => $extracted['incluye_tara'] ?? true,
                    // 🔧 FIX: Priorizar cantidad de extracted_data
                    'cantidad' => $extracted['cantidad'] ?? $cotization->cantidad,
                    'cantidadMercancia' => $extracted['cantidad'] ?? $cotization->cantidad,
                    'tipo_embajale' => $cotization->tipo_embajale,
                    'empaque' => $extracted['empaque'] ?? $cotization->tipo_embajale,
                    'empaque_id' => $extracted['empaque_id'] ?? null,
                    // 🔥 CRÍTICO: Priorizar producto_mencionado del extracted_data sobre tipo_producto de BD
                    'producto' => $extracted['producto_mencionado'] ?? $extracted['producto'] ?? $cotization->tipo_producto,
                    'producto_mencionado' => $extracted['producto_mencionado'] ?? $extracted['producto'] ?? null,
                    'tipo_producto' => $extracted['tipo_producto'] ?? $cotization->tipo_producto,
                    'producto_codigo' => $extracted['producto_codigo'] ?? null,
                    'producto_nombre' => $extracted['producto_nombre'] ?? null,
                    // 🔧 FIX: Priorizar vehículo de extracted_data
                    'vehiculo_requerido' => $extracted['vehiculo_requerido'] ?? $extracted['vehiculo'] ?? $cotization->vehiculo_requerido,
                    'vehiculo' => $extracted['vehiculo_requerido'] ?? $extracted['vehiculo'] ?? $cotization->vehiculo_requerido,
                    'claseVehiculo' => $extracted['claseVehiculo'] ?? $extracted['vehiculo'] ?? $cotization->vehiculo_requerido,
                    // 🔧 FIX: Priorizar valor_declarado de extracted_data
                    'valor_declarado' => $extracted['valor_declarado'] ?? $cotization->valor_declarado,
                    'valorMercancia' => $extracted['valor_declarado'] ?? $cotization->valor_declarado,
                    'active' => $cotization->active,
                    'decision_cliente' => $cotization->decision_cliente,
                    'incluye_tara' => $extracted['incluye_tara'] ?? false,
                ];
            });
            } // Fin del else (cotizaciones existentes)

            return response()->json([
                'success' => true,
                'group_id' => $group->id,
                'routes_count' => $routes->count(),
                'routes' => $routes
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