<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupCotization;
use App\Models\CotizacionModel;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuoteSaveController extends Controller
{
    public function saveQuoteFromChat(Request $request)
    {
        // $request->validate([
        //     'client_id' => 'required|integer|exists:clients,id',
        //     'quote_data' => 'required|array|min:1',
        //     'quote_data.*.ciudad_origen' => 'required|string',
        //     'quote_data.*.ciudad_destino' => 'required|string',
        //     'quote_data.*.peso_mercancia' => 'nullable|string',
        //     'quote_data.*.tipo_producto' => 'nullable|string',
        //     'quote_data.*.vehiculo_requerido' => 'nullable|string',
        //     'quote_data.*.valor_declarado' => 'nullable|string',
        //     'thread_id' => 'nullable|string',
        //     'type_business' => 'required|string',
        //     'operation_type' => 'nullable|string'
        // ]);

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'quote_data' => 'required|array|min:1',
            'quote_data.*.id' => 'nullable|integer', // <-- allow existing cotización id
            'quote_data.*.precio_pricing_id' => 'nullable|integer|exists:pricings,id',
            'quote_data.*.pricing_id' => 'nullable|integer|exists:pricings,id',
            'quote_data.*.ciudad_origen' => 'required|string',
            'quote_data.*.ciudad_destino' => 'required|string',
            'quote_data.*.peso_mercancia' => 'nullable|string',
            'quote_data.*.tipo_producto' => 'nullable|string',
            'quote_data.*.vehiculo_requerido' => 'nullable|string',
            'quote_data.*.valor_declarado' => 'nullable|string',
            'thread_id' => 'nullable|string',
            'type_business' => 'required|string',
            'operation_type' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            $userId = auth()->id();
            $client = Client::findOrFail($request->client_id);
            
            Log::info('Guardando cotización desde chat', [
                'user_id' => $userId,
                'client_id' => $request->client_id,
                'routes_count' => count($request->quote_data),
                'thread_id' => $request->thread_id
            ]);

            // Buscar grupo borrador existente (creado por QuoteCreationController)
            $group = GroupCotization::where('client_id', $request->client_id)
                ->where('user_id', $userId)
                ->where('status', 'borrador')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($group) {
                // REUTILIZAR el grupo borrador existente, actualizando SOLO el estado y thread
                // Mantener los datos importantes que ya tiene (candado_satelital, jen_set, etc.)
                $updateData = [
                    'status' => 'Pre-Solicitud',
                    'openai_thread_id' => $request->thread_id,
                    'created_from_chat' => true
                ];
                
                // Solo actualizar type y operation_type si no los tiene
                if (!$group->type) {
                    $updateData['type'] = $request->type_business;
                }
                if (!$group->operation_type) {
                    $updateData['operation_type'] = $request->operation_type;
                }
                
                $group->update($updateData);
                
                Log::info('✅ Grupo borrador REUTILIZADO y actualizado a Pre-Solicitud', [
                    'group_id' => $group->id,
                    'created_at' => $group->created_at,
                    'mantiene_datos' => [
                        'operation_type' => $group->operation_type,
                        'candado_satelital' => $group->candado_satelital,
                        'jen_set' => $group->jen_set,
                        'cargo_type' => $group->cargo_type
                    ]
                ]);
            } else {
                // Solo si NO existe borrador reciente, crear uno nuevo
                $group = GroupCotization::create([
                    'user_id' => $userId,
                    'client_id' => $request->client_id,
                    'type' => $request->type_business,
                    'operation_type' => $request->operation_type,
                    'status' => 'Pre-Solicitud',
                    'openai_thread_id' => $request->thread_id,
                    'created_from_chat' => true
                ]);
                
                Log::info('✅ Grupo NUEVO creado (no había borrador previo)', [
                    'group_id' => $group->id
                ]);
            }

            // Crear las cotizaciones individuales para cada ruta
            foreach ($request->quote_data as $index => $routeData) {
                // Calcular precio final si se envió desde el frontend
                $finalValue = $routeData['finalValue'] ?? 0;
                $porcentaje = $routeData['porcentaje'] ?? 0;
                
                // NO guardar cotizaciones con valor 0 o NULL
                if ($finalValue <= 0) {
                    Log::info('⏭️ Omitiendo ruta con valor 0', [
                        'ruta' => $routeData['ciudad_origen'] . ' → ' . $routeData['ciudad_destino'],
                        'valor' => $finalValue
                    ]);
                    continue; // Saltar esta ruta
                }
                
                // Obtener un pricing por defecto para las cotizaciones del chat
                // $defaultPricing = \App\Models\Pricing::first();
                
                // $cotizacion = CotizacionModel::create([
                //     'pricing_id' => $defaultPricing ? $defaultPricing->id : 61,
                //     'group_cotization_id' => $group->id,
                //     'client_id' => $request->client_id,
                //     'ciudad_origen' => $routeData['ciudad_origen'],
                //     'ciudad_destino' => $routeData['ciudad_destino'],
                //     'peso_mercancia' => $this->extractNumericValue($routeData['peso_mercancia'] ?? ''),
                //     'tipo_producto' => $routeData['tipo_producto'] ?? '',
                //     'vehiculo_requerido' => $routeData['vehiculo_requerido'] ?? 'Camión sencillo',
                //     'valor_declarado' => $this->extractNumericValue($routeData['valor_declarado'] ?? '0'),
                //     'valor' => $finalValue, // Guardar el precio final calculado
                //     'porcentaje' => $porcentaje, // Guardar el porcentaje aplicado
                //     'cantidad' => $routeData['cantidad'] ?? '1',
                //     'tipo_embajale' => $routeData['tipo_embajale'] ?? 'Bultos',
                //     'dimensiones_exactas' => $routeData['dimensiones_exactas'] ?? 'No especificado',
                //     'registro_fotografico' => $routeData['registro_fotografico'] ?? 'No requerido',
                //     'regimen_nacionalizado' => '1',
                //     'descargue_cargue' => '1',
                //     'consolidado_expreso' => '0',
                //     'fcl_lcl' => 'LCL',
                //     'sitio_devolucion_contenedor' => 'No aplica',
                //     'fecha_hora_descargue_cargue' => now()->format('d-m-Y'),
                //     'cantidad_vh' => '1',
                //     'un' => 'N/A',
                //     'ruta' => strtolower($routeData['ciudad_origen']) . '-' . strtolower($routeData['ciudad_destino']),
                //     'frecuencia' => 'Única vez',
                //     'esquema_seguridad' => 'Básico',
                //     'tipo_carroceria' => 'Estacas',
                //     'tipo_mercancia' => 'Carga seca',
                //     'seguro' => '1',
                //     'decision_cliente' => 'pendiente',
                //     'active' => 1,
                //     'created_at' => now(),
                //     'updated_at' => now()
                // ]);

                // Log::info('Cotización creada desde chat', [
                //     'cotizacion_id' => $cotizacion->id,
                //     'group_id' => $group->id,
                //     'ruta' => $routeData['ciudad_origen'] . ' → ' . $routeData['ciudad_destino']
                // ]);




                // // Obtener un pricing por defecto si no viene en la ruta
                // $defaultPricing = \App\Models\Pricing::first();

                // foreach ($request->quote_data as $index => $routeData) {
                //     $finalValue = $routeData['finalValue'] 
                //         ?? $routeData['valor_final'] 
                //         ?? $routeData['valor'] 
                //         ?? 0;

                //     // NO guardar cotizaciones con valor 0 o NULL
                //     if ($finalValue <= 0) {
                //         Log::info('⏭️ Omitiendo ruta con valor 0', [
                //             'ruta' => ($routeData['ciudad_origen'] ?? '') . ' → ' . ($routeData['ciudad_destino'] ?? ''),
                //             'valor' => $finalValue
                //         ]);
                //         continue;
                //     }

                //     $pricingId = $routeData['precio_pricing_id']
                //         ?? $routeData['pricing_id']
                //         ?? ($defaultPricing ? $defaultPricing->id : 61);

                //     // Intentar encontrar una cotización existente en este grupo
                //     $cotizacion = null;
                //     if (!empty($routeData['id'])) {
                //         $cotizacion = CotizacionModel::where('id', $routeData['id'])
                //             ->where('group_cotization_id', $group->id)
                //             ->first();
                //     }

                //     $payload = [
                //         'pricing_id'            => $pricingId,
                //         'group_cotization_id'   => $group->id,
                //         'client_id'             => $request->client_id,
                //         'ciudad_origen'         => $routeData['ciudad_origen'],
                //         'ciudad_destino'        => $routeData['ciudad_destino'],
                //         'peso_mercancia'        => $this->extractNumericValue($routeData['peso_mercancia'] ?? ''),
                //         'tipo_producto'         => $routeData['tipo_producto'] ?? '',
                //         'vehiculo_requerido'    => $routeData['vehiculo_requerido'] ?? 'Camión sencillo',
                //         'valor_declarado'       => $this->extractNumericValue($routeData['valor_declarado'] ?? '0'),
                //         'valor'                 => $finalValue,
                //         'porcentaje'            => $routeData['porcentaje'] ?? 0,
                //         'cantidad'              => $routeData['cantidad'] ?? '1',
                //         'tipo_embajale'         => $routeData['tipo_embajale'] ?? 'Bultos',
                //         'dimensiones_exactas'   => $routeData['dimensiones_exactas'] ?? 'No especificado',
                //         'registro_fotografico'  => $routeData['registro_fotografico'] ?? 'No requerido',
                //         'candado_satelital'     => $routeData['candado_satelital'] ?? 0,
                //         'jen_set'               => $routeData['jen_set'] ?? 0,
                //         'combustible'           => $routeData['combustible'] ?? 0,
                //         'kit_derrames'          => $routeData['kit_derrames'] ?? 0,
                //         'pictogramas'           => $routeData['pictogramas'] ?? 0,
                //         'itesoltra_acompanamientovalor' => $routeData['itesoltra_acompanamientovalor'] ?? 0,
                //         'decision_cliente'      => $cotizacion->decision_cliente ?? 'pendiente',
                //         'active'                => 1,
                //     ];

                //     if ($cotizacion) {
                //         $cotizacion->update($payload);
                //         Log::info('Cotización actualizada desde chat', [
                //             'cotizacion_id' => $cotizacion->id,
                //             'group_id' => $group->id,
                //         ]);
                //     } else {
                //         $payload['user_id'] = $userId;
                //         $cotizacion = CotizacionModel::create($payload);
                //         Log::info('Cotización creada desde chat', [
                //             'cotizacion_id' => $cotizacion->id,
                //             'group_id' => $group->id,
                //         ]);
                //     }
                // }

                // Crear/actualizar las cotizaciones individuales para cada ruta
                // $defaultPricing = \App\Models\Pricing::first();

                // $savedRoutes = [];

                // foreach ($request->quote_data as $index => $routeData) {
                //     $finalValue = $routeData['finalValue']
                //         ?? $routeData['valor_final']
                //         ?? $routeData['valor']
                //         ?? 0;

                //     if ($finalValue <= 0) {
                //         Log::info('⏭️ Omitiendo ruta con valor 0', [
                //             'ruta' => ($routeData['ciudad_origen'] ?? '') . ' → ' . ($routeData['ciudad_destino'] ?? ''),
                //             'valor' => $finalValue
                //         ]);
                //         continue;
                //     }

                //     $pricingId = $routeData['precio_pricing_id']
                //         ?? $routeData['pricing_id']
                //         ?? ($defaultPricing ? $defaultPricing->id : 61);

                //     // Buscar cotización existente por id y grupo
                //     $cotizacion = null;
                //     if (!empty($routeData['id'])) {
                //         $cotizacion = CotizacionModel::where('id', $routeData['id'])
                //             ->where('group_cotization_id', $group->id)
                //             ->first();
                //     }

                //     $payload = [
                //         'pricing_id'            => $pricingId,
                //         'group_cotization_id'   => $group->id,
                //         'client_id'             => $request->client_id,
                //         'ciudad_origen'         => $routeData['ciudad_origen'],
                //         'ciudad_destino'        => $routeData['ciudad_destino'],
                //         'peso_mercancia'        => $this->extractNumericValue($routeData['peso_mercancia'] ?? ''),
                //         'tipo_producto'         => $routeData['tipo_producto'] ?? '',
                //         'vehiculo_requerido'    => $routeData['vehiculo_requerido'] ?? 'Camión sencillo',
                //         'valor_declarado'       => $this->extractNumericValue($routeData['valor_declarado'] ?? '0'),
                //         'valor'                 => $finalValue,
                //         'porcentaje'            => $routeData['porcentaje'] ?? 0,
                //         'cantidad'              => $routeData['cantidad'] ?? '1',
                //         'tipo_embajale'         => $routeData['tipo_embajale'] ?? 'Bultos',
                //         'dimensiones_exactas'   => $routeData['dimensiones_exactas'] ?? 'No especificado',
                //         'registro_fotografico'  => $routeData['registro_fotografico'] ?? 'No requerido',
                //         'candado_satelital'     => $routeData['candado_satelital'] ?? 0,
                //         'jen_set'               => $routeData['jen_set'] ?? 0,
                //         'combustible'           => $routeData['combustible'] ?? 0,
                //         'kit_derrames'          => $routeData['kit_derrames'] ?? 0,
                //         'pictogramas'           => $routeData['pictogramas'] ?? 0,
                //         'itesoltra_acompanamientovalor' => $routeData['itesoltra_acompanamientovalor'] ?? 0,
                //         'decision_cliente'      => $cotizacion->decision_cliente ?? 'pendiente',
                //         'active'                => 1,
                //     ];

                //     if ($cotizacion) {
                //         $cotizacion->update($payload);
                //         Log::info('Cotización actualizada desde chat', [
                //             'cotizacion_id' => $cotizacion->id,
                //             'group_id'      => $group->id,
                //         ]);
                //     } else {
                //         $payload['user_id'] = $userId;
                //         $cotizacion = CotizacionModel::create($payload);
                //         Log::info('Cotización creada desde chat', [
                //             'cotizacion_id' => $cotizacion->id,
                //             'group_id'      => $group->id,
                //         ]);
                //     }

                //     $savedRoutes[] = [
                //         'id'             => $cotizacion->id,
                //         'ciudad_origen'  => $cotizacion->ciudad_origen,
                //         'ciudad_destino' => $cotizacion->ciudad_destino,
                //         'valor'          => $cotizacion->valor,
                //     ];
                // }

                // Opcional: devolver las rutas guardadas para refrescar IDs en frontend

                // Crear/actualizar las cotizaciones individuales para cada ruta
                $defaultPricing = \App\Models\Pricing::first();
                $savedRoutes = [];

                foreach ($request->quote_data as $index => $routeData) {
                    $finalValue = $routeData['finalValue']
                        ?? $routeData['valor_final']
                        ?? $routeData['valor']
                        ?? 0;

                    if ($finalValue <= 0) {
                        Log::info('⏭️ Omitiendo ruta con valor 0', [
                            'ruta'  => ($routeData['ciudad_origen'] ?? '') . ' → ' . ($routeData['ciudad_destino'] ?? ''),
                            'valor' => $finalValue
                        ]);
                        continue;
                    }

                    $pricingId = $routeData['precio_pricing_id']
                        ?? $routeData['pricing_id']
                        ?? ($defaultPricing ? $defaultPricing->id : 61);

                    // Buscar cotización existente por id y grupo
                    $cotizacion = null;
                    if (!empty($routeData['id'])) {
                        $cotizacion = CotizacionModel::where('id', $routeData['id'])
                            ->where('group_cotization_id', $group->id)
                            ->first();
                    }

                    // 🆕 Extraer producto con prioridad: producto_mencionado > producto > tipo_producto
                    // Esto asegura que productos personalizados (ej: "PRODUCTOS DE ASEO") se guarden correctamente
                    $producto = $routeData['producto_mencionado'] 
                        ?? $routeData['producto'] 
                        ?? $routeData['tipo_producto'] 
                        ?? '';

                    $payload = [
                        'pricing_id'            => $pricingId,
                        'group_cotization_id'   => $group->id,
                        'client_id'             => $request->client_id,
                        'ciudad_origen'         => $routeData['ciudad_origen'],
                        'ciudad_destino'        => $routeData['ciudad_destino'],
                        'peso_mercancia'        => $this->extractNumericValue($routeData['peso_mercancia'] ?? ''),
                        'tipo_producto'         => $producto, // 🔥 Usar el producto con prioridad correcta
                        'vehiculo_requerido'    => $routeData['vehiculo_requerido'] ?? 'Camión sencillo',
                        'valor_declarado'       => $this->extractNumericValue($routeData['valor_declarado'] ?? '0'),
                        'valor'                 => $finalValue,
                        'porcentaje'            => $routeData['porcentaje'] ?? 0,
                        'cantidad'              => $routeData['cantidad'] ?? '1',
                        'tipo_embajale'         => $routeData['tipo_embajale'] ?? 'Bultos',
                        'dimensiones_exactas'   => $routeData['dimensiones_exactas'] ?? 'No especificado',
                        'registro_fotografico'  => $routeData['registro_fotografico'] ?? 'No requerido',
                        'candado_satelital'     => $routeData['candado_satelital'] ?? 0,
                        'jen_set'               => $routeData['jen_set'] ?? 0,
                        'combustible'           => $routeData['combustible'] ?? 0,
                        'kit_derrames'          => $routeData['kit_derrames'] ?? 0,
                        'pictogramas'           => $routeData['pictogramas'] ?? 0,
                        'itesoltra_acompanamientovalor' => $routeData['itesoltra_acompanamientovalor'] ?? 0,
                        'decision_cliente'      => $cotizacion->decision_cliente ?? 'pendiente',
                        'active'                => 1,
                    ];

                    if ($cotizacion) {
                        $cotizacion->update($payload);
                        Log::info('Cotización actualizada desde chat', [
                            'cotizacion_id' => $cotizacion->id,
                            'group_id'      => $group->id,
                        ]);
                    } else {
                        $payload['user_id'] = $userId;
                        $cotizacion = CotizacionModel::create($payload);
                        Log::info('Cotización creada desde chat', [
                            'cotizacion_id' => $cotizacion->id,
                            'group_id'      => $group->id,
                        ]);
                    }

                    $savedRoutes[] = [
                        'id'             => $cotizacion->id,
                        'ciudad_origen'  => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        'valor'          => $cotizacion->valor,
                    ];
                }

                // Actualizar el thread_id en el cliente si se proporcionó
                if ($request->thread_id) {
                    $client->update(['openai_thread_id' => $request->thread_id]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'group_id'     => $group->id,
                        'total_routes' => count($savedRoutes),
                        'status'       => $group->status,
                        'routes'       => $savedRoutes, // <-- returned IDs
                        'message'      => 'Cotización guardada exitosamente'
                    ]
                ]);
            }

            // Actualizar el thread_id en el cliente si se proporcionó
            if ($request->thread_id) {
                $client->update(['openai_thread_id' => $request->thread_id]);
            }

            DB::commit();

            Log::info('Grupo de cotización guardado exitosamente', [
                'group_id' => $group->id,
                'total_routes' => count($request->quote_data)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'total_routes' => count($request->quote_data),
                    'status' => $group->status,
                    'routes'       => $savedRoutes,
                    'message' => 'Cotización guardada exitosamente'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error guardando cotización desde chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'no_auth',
                'client_id' => $request->client_id ?? 'no_client'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrae valores numéricos de strings que pueden contener formato
     */
    private function extractNumericValue($value)
    {
        if (empty($value)) {
            return 0;
        }
        
        // Remover caracteres no numéricos excepto puntos y comas
        $cleaned = preg_replace('/[^\d.,]/', '', $value);
        
        // Convertir comas a puntos para decimales
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Si hay múltiples puntos, tomar solo el último como decimal
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $integer = implode('', array_slice($parts, 0, -1));
            $decimal = end($parts);
            $cleaned = $integer . '.' . $decimal;
        }
        
        return (float) $cleaned;
    }
}