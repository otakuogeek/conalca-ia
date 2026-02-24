<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupCotization;
use App\Models\CotizacionModel;
use App\Models\Client;
use App\Models\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ═══════════════════════════════════════════════════════════════
 * TestSeedController - API para crear cotizaciones de prueba
 * ═══════════════════════════════════════════════════════════════
 * 
 * Permite inyectar datos directamente en el sistema saltando el
 * flujo normal (chat → Silogtrans → validación) para pruebas.
 * 
 * Las cotizaciones se crean directamente en estado "En tránsito"
 * con decisión "aceptada" para que aparezcan en el tablero Kanban
 * y se pueda probar el sistema de llamadas con ElevenLabs.
 * 
 * USO: POST /api/test/seed-cotizacion
 */
class TestSeedController extends Controller
{
    /**
     * Crear un grupo de cotización completo en estado "En tránsito"
     * listo para solicitar vehículos y ejecutar llamadas.
     */
    public function seedCotizacion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Datos del grupo
            'user_id'        => 'required|integer|exists:users,id',
            'client_id'      => 'nullable|integer|exists:clients,id',
            'operation_type'  => 'nullable|string|in:DISTRIBUCION,EXPORTACION,IMPORTACION',
            'reference'      => 'nullable|string|max:255',
            'cargo_type'     => 'nullable|string',

            // Datos del cliente (si no existe, se puede crear)
            'client'                  => 'nullable|array',
            'client.nombre'           => 'nullable|string|max:255',
            'client.documento'        => 'nullable|string|max:50',
            'client.telefono'         => 'nullable|string|max:50',
            'client.direccion'        => 'nullable|string|max:255',
            'client.ciudad'           => 'nullable|string|max:100',

            // Rutas/cotizaciones (array de rutas)
            'rutas'                          => 'required|array|min:1',
            'rutas.*.ciudad_origen'          => 'required|string',
            'rutas.*.ciudad_destino'         => 'required|string',
            'rutas.*.valor'                  => 'required|numeric|min:0',
            'rutas.*.tipo_mercancia'         => 'nullable|string',
            'rutas.*.tipo_producto'          => 'nullable|string',
            'rutas.*.peso_mercancia'         => 'nullable|string',
            'rutas.*.dimensiones_exactas'    => 'nullable|string',
            'rutas.*.cantidad'               => 'nullable|string',
            'rutas.*.cantidad_vh'            => 'nullable|string',
            'rutas.*.tipo_embajale'          => 'nullable|string',
            'rutas.*.tipo_carroceria'        => 'nullable|string',
            'rutas.*.vehiculo_requerido'     => 'nullable|string',
            'rutas.*.valor_declarado'        => 'nullable|string',
            'rutas.*.seguro'                 => 'nullable|string',
            'rutas.*.temperatura_mercancia'  => 'nullable|string',
            'rutas.*.registro_fotografico'   => 'nullable|string',
            'rutas.*.fecha'                  => 'nullable|date',
            'rutas.*.porcentaje'             => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // ─── 1. Resolver o crear cliente ───
                $clientId = $request->client_id;

                if (!$clientId && $request->has('client')) {
                    $clientData = $request->input('client');
                    $client = Client::create([
                        'cliente'   => $clientData['nombre'] ?? 'Cliente de Prueba',
                        'documento' => $clientData['documento'] ?? '0000000000',
                        'telefono'  => $clientData['telefono'] ?? null,
                        'direccion' => $clientData['direccion'] ?? null,
                        'ciudad'    => $clientData['ciudad'] ?? null,
                    ]);
                    $clientId = $client->id;
                }

                if (!$clientId) {
                    // Usar el primer cliente disponible como fallback
                    $clientId = Client::first()?->id;
                    if (!$clientId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No hay clientes disponibles. Envía client_id o datos de client.',
                        ], 422);
                    }
                }

                // ─── 2. Crear grupo de cotización ───
                $group = GroupCotization::create([
                    'user_id'        => $request->user_id,
                    'client_id'      => $clientId,
                    'type'           => $request->input('type', 'Cotización'),
                    'operation_type' => $request->input('operation_type', 'DISTRIBUCION'),
                    'reference'      => $request->input('reference', 'TEST-SEED-' . now()->format('YmdHis')),
                    'status'         => 'En tránsito',  // ← Directamente en tránsito
                    'cargo_type'     => $request->input('cargo_type'),
                ]);

                Log::info('🧪 TestSeed: Grupo creado', [
                    'group_id' => $group->id,
                    'client_id' => $clientId,
                    'user_id' => $request->user_id,
                ]);

                // ─── 3. Crear cotizaciones (rutas) ───
                $cotizaciones = [];
                foreach ($request->rutas as $index => $ruta) {
                    // Intentar encontrar un pricing que coincida
                    $pricing = null;
                    if (!empty($ruta['ciudad_origen']) && !empty($ruta['ciudad_destino'])) {
                        $pricing = Pricing::where('origin', 'LIKE', '%' . $ruta['ciudad_origen'] . '%')
                            ->where('destination', 'LIKE', '%' . $ruta['ciudad_destino'] . '%')
                            ->first();
                    }

                    $cotizacion = CotizacionModel::create([
                        'group_cotization_id' => $group->id,
                        'client_id'           => $clientId,
                        'pricing_id'          => $pricing?->id,
                        'ciudad_origen'       => $ruta['ciudad_origen'],
                        'ciudad_destino'      => $ruta['ciudad_destino'],
                        'valor'               => $ruta['valor'],
                        'tipo_mercancia'      => $ruta['tipo_mercancia'] ?? 'Carga general',
                        'tipo_producto'       => $ruta['tipo_producto'] ?? null,
                        'peso_mercancia'      => $ruta['peso_mercancia'] ?? null,
                        'dimensiones_exactas' => $ruta['dimensiones_exactas'] ?? null,
                        'cantidad'            => $ruta['cantidad'] ?? '1',
                        'cantidad_vh'         => $ruta['cantidad_vh'] ?? '1',
                        'tipo_embajale'       => $ruta['tipo_embajale'] ?? null,
                        'tipo_carroceria'     => $ruta['tipo_carroceria'] ?? null,
                        'vehiculo_requerido'  => $ruta['vehiculo_requerido'] ?? 'TRACTOCAMION',
                        'valor_declarado'     => $ruta['valor_declarado'] ?? null,
                        'seguro'              => $ruta['seguro'] ?? 'No',
                        'temperatura_mercancia' => $ruta['temperatura_mercancia'] ?? 'No aplica',
                        'registro_fotografico'  => $ruta['registro_fotografico'] ?? null,
                        'porcentaje'          => $ruta['porcentaje'] ?? 0,
                        'decision_cliente'    => 'aceptada',  // ← Aceptada para que aparezca en CallPanel
                        'active'              => true,
                        'tipo'                => $request->input('operation_type', 'DISTRIBUCION'),
                    ]);

                    $cotizaciones[] = $cotizacion;

                    // Forzar decision_cliente (no está en $fillable del modelo)
                    DB::table('cotizacion_models')
                        ->where('id', $cotizacion->id)
                        ->update(['decision_cliente' => 'aceptada']);
                    $cotizacion->refresh();

                    Log::info('🧪 TestSeed: Cotización creada', [
                        'cotizacion_id' => $cotizacion->id,
                        'ruta'          => "{$ruta['ciudad_origen']} → {$ruta['ciudad_destino']}",
                        'valor'         => $ruta['valor'],
                    ]);
                }

                // ─── 4. Cargar relaciones para la respuesta ───
                $group->load([
                    'client:id,cliente,documento,telefono,direccion,ciudad',
                    'cotizaciones',
                    'cotizaciones.pricing:id,price,vehicle_type',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Grupo de cotización #{$group->id} creado exitosamente en estado 'En tránsito'",
                    'data'    => [
                        'group_id'      => $group->id,
                        'status'        => $group->status,
                        'reference'     => $group->reference,
                        'client'        => $group->client ? [
                            'id'        => $group->client->id,
                            'nombre'    => $group->client->cliente,
                            'documento' => $group->client->documento,
                        ] : null,
                        'cotizaciones'  => collect($cotizaciones)->map(function ($cot) {
                            return [
                                'id'                    => $cot->id,
                                'group_cotization_id'   => $cot->group_cotization_id,
                                'ciudad_origen'         => $cot->ciudad_origen,
                                'ciudad_destino'        => $cot->ciudad_destino,
                                'valor'                 => $cot->valor,
                                'vehiculo_requerido'    => $cot->vehiculo_requerido,
                                'tipo_mercancia'        => $cot->tipo_mercancia,
                                'peso_mercancia'        => $cot->peso_mercancia,
                                'decision_cliente'      => $cot->decision_cliente,
                            ];
                        }),
                        'valor_total'   => collect($cotizaciones)->sum('valor'),
                    ],
                    'instructions' => [
                        'kanban'    => "El grupo aparecerá en la columna 'En tránsito' del tablero Kanban.",
                        'llamadas'  => "Haz clic en el icono de camión para abrir el detalle y usar 'Registrar Llamadas'.",
                        'api_calls' => [
                            'buscar_conductores' => "POST /api/arcangel/buscar-conductores { cotizacion_id: {$cotizaciones[0]->id} }",
                            'registrar_llamadas' => "POST /api/call-drivers-group { group_cotization_id: {$group->id} }",
                            'iniciar_elevenlabs' => "POST /api/start-elevenlabs-calls/{$cotizaciones[0]->id}",
                        ],
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('🧪 TestSeed: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización de prueba',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed múltiple: crear varios grupos de una vez.
     */
    public function seedMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grupos' => 'required|array|min:1|max:20',
            'grupos.*' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $results = [];
        foreach ($request->grupos as $index => $grupoData) {
            $subRequest = new Request($grupoData);
            $response = $this->seedCotizacion($subRequest);
            $results[] = [
                'index'    => $index,
                'status'   => $response->getStatusCode(),
                'response' => json_decode($response->getContent(), true),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($results) . ' grupo(s) procesado(s)',
            'results' => $results,
        ]);
    }

    /**
     * Listar grupos de prueba creados (por referencia TEST-SEED-*)
     */
    public function listTestGroups()
    {
        $groups = GroupCotization::where('reference', 'LIKE', 'TEST-SEED-%')
            ->with([
                'client:id,cliente,documento',
                'cotizaciones:id,group_cotization_id,ciudad_origen,ciudad_destino,valor,decision_cliente,vehiculo_requerido',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $groups->count(),
            'data'    => $groups,
        ]);
    }

    /**
     * Eliminar un grupo de prueba y sus cotizaciones
     */
    public function deleteTestGroup($id)
    {
        $group = GroupCotization::where('id', $id)
            ->where('reference', 'LIKE', 'TEST-SEED-%')
            ->first();

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Grupo de prueba no encontrado. Solo se pueden eliminar grupos con referencia TEST-SEED-*',
            ], 404);
        }

        $cotizacionesCount = $group->cotizaciones()->count();
        
        // Eliminar llamadas conductores asociados
        $group->llamadasConductores()->delete();
        $group->cotizaciones()->delete();
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "Grupo #{$id} eliminado con {$cotizacionesCount} cotización(es)",
        ]);
    }

    /**
     * Limpiar TODOS los grupos de prueba
     */
    public function cleanupTestGroups()
    {
        $groups = GroupCotization::where('reference', 'LIKE', 'TEST-SEED-%')->get();
        $count = $groups->count();

        foreach ($groups as $group) {
            $group->llamadasConductores()->delete();
            $group->cotizaciones()->delete();
            $group->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} grupo(s) de prueba eliminado(s)",
        ]);
    }
}
