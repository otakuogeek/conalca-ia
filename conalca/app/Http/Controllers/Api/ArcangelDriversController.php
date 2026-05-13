<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CotizacionModel;
use App\Models\LlamadaConductor;
use App\Models\GroupCotization;
use App\Services\ArcangelService;
use App\Services\CotizacionCallWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ArcangelDriversController extends Controller
{
    protected ArcangelService $arcangelService;
    
    public function __construct(ArcangelService $arcangelService)
    {
        $this->arcangelService = $arcangelService;
    }
    
    /**
     * Buscar conductores disponibles para una cotización en Arcángel
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function buscarConductores(Request $request)
    {
        try {
            Log::info('🔍 ArcangelDriversController::buscarConductores - Inicio', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'user_authenticated' => auth()->check(),
            ]);
            
            $validator = Validator::make($request->all(), [
                'cotizacion_id' => 'required|integer|exists:cotizacion_models,id',
                'min_score' => 'sometimes|integer|min:0|max:10',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $cotizacionId = $request->input('cotizacion_id');
            $minScore = $request->input('min_score', 0);
            $limit = $request->input('limit', 50);
            
            // Obtener cotización
            $cotizacion = CotizacionModel::findOrFail($cotizacionId);

            $callRestriction = app(CotizacionCallWindowService::class)->getCallRestriction($cotizacion);
            if ($callRestriction) {
                Log::warning('Búsqueda de conductores bloqueada por fecha/hora de cargue vencida', [
                    'cotizacion_id' => $cotizacionId,
                    'restriction' => $callRestriction,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $callRestriction['message'],
                    'code' => $callRestriction['code'],
                    'cotizacion_id' => $cotizacionId,
                    'loading_at' => $callRestriction['loading_at'],
                    'loading_at_label' => $callRestriction['loading_at_label'],
                    'checked_at' => $callRestriction['checked_at'],
                    'checked_at_label' => $callRestriction['checked_at_label'],
                ], 422);
            }
            
            if (!$cotizacion->ciudad_origen) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no tiene ciudad de origen definida'
                ], 400);
            }
            
            if (!$cotizacion->vehiculo_requerido) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no tiene tipo de vehículo definido'
                ], 400);
            }
            
            // Normalizar datos
            $ciudadOrigen = $this->normalizarTexto($cotizacion->ciudad_origen);
            
            // Obtener el peso de la mercancía (convertir de string a float, en kg)
            $pesoCarga = 0;
            if ($cotizacion->peso_mercancia) {
                // Limpiar el string y convertir a float (puede venir como "18400" o "18,400" o "18400 kg")
                $pesoCarga = floatval(str_replace([',', ' kg', ' KG'], '', $cotizacion->peso_mercancia));
            }
            
            // Convertir tipo de vehículo al formato de Arcángel (puede ser múltiples variantes)
            // Filtra solo vehículos que soporten el peso de la carga
            $variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido, $pesoCarga);
            
            Log::info('ArcangelDriversController: Buscando conductores', [
                'cotizacion_id' => $cotizacionId,
                'ciudad_origen' => $ciudadOrigen,
                'vehiculo_requerido_original' => $cotizacion->vehiculo_requerido,
                'variantes_vehiculo' => $variantesVehiculo,
                'min_score' => $minScore,
                'limit' => $limit
            ]);
            
            // Buscar en Arcángel API con todas las variantes del vehículo
            $vehiculos = [];
            $fromCache = false;
            $sourceStats = [
                'city' => $ciudadOrigen,
                'vehicle_requested' => $cotizacion->vehiculo_requerido,
                'vehicle_variants' => $variantesVehiculo,
                'min_score' => $minScore,
                'city_total' => 0,
                'filtered_total' => 0,
            ];
            
            try {
                $resultado = $this->arcangelService->getVehiculosFiltrados(
                    ciudad: $ciudadOrigen,
                    clases: $variantesVehiculo,
                    minScore: $minScore,
                    limit: $limit,
                    useCache: false
                );
                $vehiculos = $resultado['vehiculos'] ?? [];
                $vehiculos = $this->deduplicateVehicles($vehiculos);
                $sourceStats['city_total'] = (int) ($resultado['total_original'] ?? count($vehiculos));
                $sourceStats['filtered_total'] = count($vehiculos);
            } catch (\Exception $arcErr) {
                Log::warning('⚠️ Arcángel no disponible, buscando conductores en BD local', [
                    'error' => substr($arcErr->getMessage(), 0, 150),
                    'cotizacion_id' => $cotizacionId
                ]);
                
                // Fallback: buscar conductores ya guardados en BD para esta cotización
                $conductoresDB = LlamadaConductor::where('cotizacion_id', $cotizacionId)
                    ->where('disponible', true)
                    ->orderBy('score', 'desc')
                    ->limit($limit)
                    ->get();
                
                if ($conductoresDB->isEmpty()) {
                    // Buscar por ciudad y tipo de vehículo (cualquier cotización)
                    $conductoresDB = LlamadaConductor::where('ciudad_actual', 'LIKE', "%{$ciudadOrigen}%")
                        ->whereIn('tipo_vehiculo', $variantesVehiculo)
                        ->where('disponible', true)
                        ->orderBy('score', 'desc')
                        ->limit($limit)
                        ->get();
                }
                
                if ($conductoresDB->isNotEmpty()) {
                    $vehiculos = $conductoresDB->map(function ($c) {
                        return [
                            'placa' => $c->placa,
                            'conductor' => $c->nombre_conductor,
                            'telefono' => $c->telefono,
                            'clase' => $c->tipo_vehiculo ?? $c->clase_vehiculo,
                            'carroceria' => $c->carroceria,
                            'capacidad' => $c->capacidad,
                            'score' => $c->score,
                        ];
                    })->toArray();
                    $vehiculos = $this->deduplicateVehicles($vehiculos);
                    $fromCache = true;
                    $sourceStats['city_total'] = count($vehiculos);
                    $sourceStats['filtered_total'] = count($vehiculos);
                    
                    Log::info('✅ Conductores obtenidos desde BD local (fallback)', [
                        'total' => count($vehiculos),
                        'cotizacion_id' => $cotizacionId
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'El servicio de Arcángel no está disponible y no hay conductores guardados previamente. Intente de nuevo en unos minutos.',
                        'arcangel_error' => true
                    ], 503);
                }
            }
            
            // Obtener el grupo de cotización desde la cotización (la relación correcta)
            $groupCotization = $cotizacion->group_cotization_id 
                ? GroupCotization::find($cotizacion->group_cotization_id) 
                : null;
            
            // Guardar conductores filtrados en la base de datos
            $conductoresGuardados = [];
            foreach ($vehiculos as $vehiculo) {
                try {
                    $conductorGuardado = LlamadaConductor::createFromArcangel(
                        $vehiculo,
                        $ciudadOrigen,
                        $cotizacionId,
                        $groupCotization ? $groupCotization->id : null,
                        [
                            'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                            'ciudad_origen' => $cotizacion->ciudad_origen,
                            'ciudad_destino' => $cotizacion->ciudad_destino,
                            'tipo_mercancia' => $cotizacion->tipo_mercancia,
                            'peso_mercancia' => $cotizacion->peso_mercancia,
                            'empaque' => $cotizacion->empaque,
                        ]
                    );
                    $conductoresGuardados[] = $conductorGuardado;
                } catch (\Exception $e) {
                    Log::warning('⚠️ No se pudo guardar conductor en DB', [
                        'vehiculo' => $vehiculo,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('✅ Conductores guardados en BD', [
                'total_buscados' => count($vehiculos),
                'total_guardados' => count($conductoresGuardados)
            ]);
            
            // Formatear datos para el frontend usando los registros guardados en BD
            // Esto incluye estado_llamada y fecha_llamada de llamadas previas
            $conductores = [];
            
            if (!empty($conductoresGuardados)) {
                foreach ($conductoresGuardados as $cg) {
                    $yaLlamado = !in_array($cg->estado_llamada, ['pendiente', null]);
                    $arcangelVehicleType = data_get($cg->datos_adicionales, 'clase')
                        ?? data_get($cg->datos_adicionales, 'tipo_vehiculo')
                        ?? $cg->clase_vehiculo
                        ?? $cg->tipo_vehiculo
                        ?? 'N/A';

                    $conductores[] = [
                        'id' => $cg->id,
                        'placa' => $cg->placa ?? 'N/A',
                        'conductor' => $cg->nombre_conductor ?? 'N/A',
                        'telefono' => $cg->telefono ?? 'N/A',
                        'clase_vehiculo' => $cg->clase_vehiculo ?? 'N/A',
                        'tipo_vehiculo_arcangel' => $arcangelVehicleType,
                        'carroceria' => $cg->carroceria ?? 'N/A',
                        'capacidad' => $cg->capacidad ?? null,
                        'score' => $cg->score ?? 0,
                        'disponible' => $cg->disponible ?? true,
                        'ultima_actualizacion' => $cg->ultima_actualizacion,
                        'ya_llamado' => $yaLlamado,
                        'estado_llamada' => $cg->estado_llamada,
                        'fecha_llamada' => $cg->fecha_llamada ? $cg->fecha_llamada->format('Y-m-d H:i') : null,
                        'respuesta_llamada' => $cg->respuesta_llamada,
                    ];
                }
                $conductores = $this->deduplicateFormattedDrivers($conductores);
            } else {
                // Fallback: formatear directamente desde la respuesta de Arcángel
                foreach ($vehiculos as $vehiculo) {
                    $conductores[] = [
                        'placa' => $vehiculo['placa'] ?? 'N/A',
                        'conductor' => $vehiculo['conductor'] ?? 'N/A',
                        'telefono' => $vehiculo['telefono'] ?? 'N/A',
                        'clase_vehiculo' => $vehiculo['clase'] ?? 'N/A',
                        'tipo_vehiculo_arcangel' => $vehiculo['clase'] ?? $vehiculo['tipo_vehiculo'] ?? 'N/A',
                        'carroceria' => $vehiculo['carroceria'] ?? 'N/A',
                        'capacidad' => $vehiculo['capacidad'] ?? null,
                        'score' => $vehiculo['score'] ?? 0,
                        'disponible' => true,
                        'ultima_actualizacion' => $vehiculo['ultimaActualizacion'] ?? null,
                        'ya_llamado' => false,
                        'estado_llamada' => null,
                        'fecha_llamada' => null,
                        'respuesta_llamada' => null,
                    ];
                }
                $conductores = $this->deduplicateFormattedDrivers($conductores);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cotizacion' => [
                        'id' => $cotizacion->id,
                        'ciudad_origen' => $cotizacion->ciudad_origen,
                        'ciudad_destino' => $cotizacion->ciudad_destino,
                        'vehiculo_requerido' => $cotizacion->vehiculo_requerido,
                        'peso_mercancia' => $cotizacion->peso_mercancia,
                    ],
                    'conductores' => $conductores,
                    'total' => count($conductores),
                    'filtros' => [
                        'ciudad' => $ciudadOrigen,
                        'vehiculo_original' => $cotizacion->vehiculo_requerido,
                        'variantes_buscadas' => $variantesVehiculo,
                        'min_score' => $minScore
                    ],
                    'source_stats' => $sourceStats,
                ],
                'message' => count($conductores) > 0 
                    ? "Se encontraron " . count($conductores) . " conductores disponibles"
                    : "No se encontraron conductores disponibles en {$ciudadOrigen} con vehículos tipo: " . implode(', ', $variantesVehiculo)
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ ArcangelDriversController: Error buscando conductores', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la búsqueda de conductores. Intente de nuevo.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function deduplicateVehicles(array $vehiculos): array
    {
        $uniqueVehicles = [];

        foreach ($vehiculos as $vehiculo) {
            $telefono = preg_replace('/\D+/', '', (string) ($vehiculo['telefono'] ?? ''));
            $placa = strtoupper(trim((string) ($vehiculo['placa'] ?? '')));
            $nombre = strtoupper(trim((string) ($vehiculo['conductor'] ?? '')));
            $key = $placa !== ''
                ? "placa:{$placa}"
                : ($telefono !== '' ? "telefono:{$telefono}" : "nombre:{$nombre}");

            if (!isset($uniqueVehicles[$key])) {
                $uniqueVehicles[$key] = $vehiculo;
                continue;
            }

            if (($vehiculo['score'] ?? 0) > ($uniqueVehicles[$key]['score'] ?? 0)) {
                $uniqueVehicles[$key] = $vehiculo;
            }
        }

        return array_values($uniqueVehicles);
    }

    private function deduplicateFormattedDrivers(array $conductores): array
    {
        $uniqueDrivers = [];

        foreach ($conductores as $conductor) {
            $telefono = preg_replace('/\D+/', '', (string) ($conductor['telefono'] ?? ''));
            $placa = strtoupper(trim((string) ($conductor['placa'] ?? '')));
            $nombre = strtoupper(trim((string) ($conductor['conductor'] ?? '')));
            $key = $placa !== '' && $placa !== 'N/A'
                ? "placa:{$placa}"
                : ($telefono !== '' ? "telefono:{$telefono}" : "nombre:{$nombre}");

            if (!isset($uniqueDrivers[$key])) {
                $uniqueDrivers[$key] = $conductor;
                continue;
            }

            if (($conductor['score'] ?? 0) > ($uniqueDrivers[$key]['score'] ?? 0)) {
                $uniqueDrivers[$key] = $conductor;
            }
        }

        return array_values($uniqueDrivers);
    }
    
    /**
     * Buscar conductores por ciudad y tipo de vehículo (sin cotización)
     */
    public function buscarPorFiltros(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ciudad' => 'required|string|min:3',
                'vehiculo' => 'required|string|min:3',
                'min_score' => 'sometimes|integer|min:0|max:10',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $ciudad = $this->normalizarTexto($request->input('ciudad'));
            $vehiculo = $this->normalizarTexto($request->input('vehiculo'));
            $minScore = $request->input('min_score', 0);
            $limit = $request->input('limit', 50);
            
            $resultado = $this->arcangelService->getVehiculosFiltrados(
                ciudad: $ciudad,
                clases: $vehiculo,
                minScore: $minScore,
                limit: $limit,
                useCache: false
            );
            
            $vehiculos = $resultado['vehiculos'] ?? [];
            
            $conductores = array_map(function ($vehiculo) {
                return [
                    'placa' => $vehiculo['placa'] ?? 'N/A',
                    'conductor' => $vehiculo['conductor'] ?? 'N/A',
                    'telefono' => $vehiculo['telefono'] ?? 'N/A',
                    'clase_vehiculo' => $vehiculo['clase'] ?? 'N/A',
                    'carroceria' => $vehiculo['carroceria'] ?? 'N/A',
                    'capacidad' => $vehiculo['capacidad'] ?? null,
                    'score' => $vehiculo['score'] ?? 0,
                    'disponible' => $vehiculo['disponible'] ?? false,
                ];
            }, $vehiculos);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'conductores' => $conductores,
                    'total' => count($conductores),
                    'filtros' => [
                        'ciudad' => $ciudad,
                        'vehiculo' => $vehiculo,
                        'min_score' => $minScore
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('ArcangelDriversController: Error en búsqueda por filtros', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar conductores: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Normalizar texto removiendo tildes y convirtiendo a mayúsculas
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = strtoupper(trim($texto));
        $acentos = [
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'À'=>'A', 'È'=>'E', 'Ì'=>'I', 'Ò'=>'O', 'Ù'=>'U',
            'Ä'=>'A', 'Ë'=>'E', 'Ï'=>'I', 'Ö'=>'O', 'Ü'=>'U',
            'Â'=>'A', 'Ê'=>'E', 'Î'=>'I', 'Ô'=>'O', 'Û'=>'U',
            'Ñ'=>'N'
        ];
        return strtr($texto, $acentos);
    }
    
    /**
     * Convertir tipos de vehículos de nuestra nomenclatura a la de Arcángel
     * Retorna array de posibles variantes en Arcángel usando tabla vehiculos_relaciones
     * Solo incluye vehículos que soporten el peso de la carga
     * 
     * @param string $tipoLocal Tipo de vehículo en nomenclatura local
     * @param float $pesoCarga Peso de la carga en kg (0 si no se filtra por peso)
     * @return array Variantes de vehículos en Arcángel que cumplen con peso máximo
     */
    private function convertirTipoVehiculo(string $tipoLocal, float $pesoCarga = 0): array
    {
        $tipoNormalizado = $this->normalizarTexto($tipoLocal);
        
        Log::info('🔍 Convirtiendo tipo de vehículo con filtro de peso', [
            'vehiculo_local' => $tipoLocal,
            'peso_carga' => $pesoCarga . ' kg'
        ]);
        
        try {
            // ESTRATEGIA 1: Buscar coincidencia exacta en vehiculo_silogtran
            $query1 = \DB::table('vehiculos_relaciones')
                ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                ->join('vehiculos_arcangel', 'vehiculos_relaciones.vehiculo_arcangel_id', '=', 'vehiculos_arcangel.id')
                ->where(\DB::raw('UPPER(vehiculos_pricing.vehiculo_silogtran)'), '=', $tipoNormalizado);
            
            // Filtrar por peso máximo si se especifica
            if ($pesoCarga > 0) {
                $query1->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
            }
            
            $vehiculosArcangel = $query1->pluck('vehiculos_arcangel.nombre')
                ->unique()
                ->toArray();
            
            if (!empty($vehiculosArcangel)) {
                Log::info('✅ Vehículos Arcangel encontrados (coincidencia exacta vehiculo_silogtran)', [
                    'vehiculo_local' => $tipoLocal,
                    'vehiculo_normalizado' => $tipoNormalizado,
                    'vehiculos_arcangel' => $vehiculosArcangel
                ]);
                return $vehiculosArcangel;
            }
            
            // ESTRATEGIA 2: Buscar coincidencia en tabla_pricing
            $query2 = \DB::table('vehiculos_relaciones')
                ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                ->join('vehiculos_arcangel', 'vehiculos_relaciones.vehiculo_arcangel_id', '=', 'vehiculos_arcangel.id')
                ->where(\DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%' . $tipoNormalizado . '%');
            
            // Filtrar por peso máximo si se especifica
            if ($pesoCarga > 0) {
                $query2->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
            }
            
            $vehiculosArcangel = $query2->pluck('vehiculos_arcangel.nombre')
                ->unique()
                ->toArray();
            
            if (!empty($vehiculosArcangel)) {
                Log::info('✅ Vehículos Arcangel encontrados (coincidencia tabla_pricing)', [
                    'vehiculo_local' => $tipoLocal,
                    'vehiculo_normalizado' => $tipoNormalizado,
                    'vehiculos_arcangel' => $vehiculosArcangel
                ]);
                return $vehiculosArcangel;
            }
            
            // ESTRATEGIA 3: Buscar coincidencia parcial en vehiculo_silogtran o tabla_pricing
            $query3 = \DB::table('vehiculos_relaciones')
                ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                ->join('vehiculos_arcangel', 'vehiculos_relaciones.vehiculo_arcangel_id', '=', 'vehiculos_arcangel.id')
                ->where(function($query) use ($tipoNormalizado) {
                    $query->where(\DB::raw('UPPER(vehiculos_pricing.vehiculo_silogtran)'), 'LIKE', '%' . $tipoNormalizado . '%')
                          ->orWhere(\DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%' . $tipoNormalizado . '%');
                });
            
            // Filtrar por peso máximo si se especifica
            if ($pesoCarga > 0) {
                $query3->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
            }
            
            $vehiculosArcangel = $query3->pluck('vehiculos_arcangel.nombre')
                ->unique()
                ->toArray();
            
            if (!empty($vehiculosArcangel)) {
                Log::info('✅ Vehículos Arcangel encontrados (coincidencia parcial)', [
                    'vehiculo_local' => $tipoLocal,
                    'vehiculo_normalizado' => $tipoNormalizado,
                    'vehiculos_arcangel' => $vehiculosArcangel
                ]);
                return $vehiculosArcangel;
            }
            
            Log::warning('⚠️ No se encontró relación en BD, usando mapeo de respaldo', [
                'vehiculo_local' => $tipoLocal,
                'vehiculo_normalizado' => $tipoNormalizado
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error consultando tabla vehiculos_relaciones', [
                'error' => $e->getMessage(),
                'vehiculo' => $tipoLocal,
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // MAPEO DE RESPALDO (fallback) si no hay relación en BD
        // ⚠️ IMPORTANTE: Búsqueda ESTRICTA - Solo busca el tipo exacto solicitado
        $mapeoRespaldo = [
            // Tractomulas
            'TRACTO MULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
            'TRACTO MULA' => ['TRACTOMULA'],
            'TRACTOMULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
            'TRACTOMULA3' => ['TRACTOMULA3', 'TRACTOMULA 3'],
            'TRACTOMULA' => ['TRACTOMULA'],
            'ARTICULADO' => ['TRACTOMULA3', 'TRACTOMULA 3'],
            'TRACTOCAMION' => ['TRACTOMULA3', 'TRACTOMULA 3'],
            
            // Sencillos
            'SENCILLO' => ['SENCILLO'],
            'CAMION SENCILLO' => ['SENCILLO'],
            
            // Doble troque
            'DOBLE TROQUE' => ['DOBLE TROQUE', 'DOBLETROQUE'],
            'DOBLETROQUE' => ['DOBLE TROQUE', 'DOBLETROQUE'],
            
            // Camionetas y Turbo - BÚSQUEDA ESTRICTA
            'CAMIONETA' => ['CAMIONETA'],
            'TURBO' => ['TURBO'],
            
            // Patinetas
            'PATINETA' => ['PATINETA'],
            'PATINETA 2' => ['PATINETA2', 'PATINETA 2'],
            'PATINETA2' => ['PATINETA2', 'PATINETA 2'],
            'PATINETA 3' => ['PATINETA3', 'PATINETA 3'],
            'PATINETA3' => ['PATINETA3', 'PATINETA 3'],
            
            // Contenedores
            'CONTENEDOR 20' => ['TRACTOMULA 3', 'TRACTOMULA3'],
            'CONTENEDOR 40' => ['TRACTOMULA 3', 'TRACTOMULA3'],
        ];
        
        // Buscar coincidencia en el mapeo de respaldo
        foreach ($mapeoRespaldo as $clave => $variantes) {
            if (str_contains($tipoNormalizado, $clave) || $tipoNormalizado === $clave) {
                Log::info('✅ Usando mapeo de respaldo', [
                    'clave' => $clave,
                    'variantes' => $variantes
                ]);
                return $variantes;
            }
        }
        
        // Si no hay mapeo, devolver el tipo normalizado
        Log::warning('⚠️ No se encontró mapeo, usando tipo original normalizado', [
            'tipo_normalizado' => $tipoNormalizado
        ]);
        return [$tipoNormalizado];
    }
    
    /**
     * Iniciar llamada a un conductor específico
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function llamarConductor(Request $request)
    {
        try {
            Log::info('📞 ArcangelDriversController::llamarConductor - Inicio', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
            ]);
            
            $validator = Validator::make($request->all(), [
                'cotizacion_id' => 'required|integer|exists:cotizacion_models,id',
                'conductor_nombre' => 'required|string',
                'telefono' => 'required|string',
                'placa' => 'sometimes|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $cotizacionId = $request->input('cotizacion_id');
            $conductorNombre = $request->input('conductor_nombre');
            $telefono = $request->input('telefono');
            $placa = $request->input('placa', 'N/A');
            
            // Formatear teléfono con código de país +57
            $telefonoFormateado = $this->formatearTelefono($telefono);
            
            if (!$telefonoFormateado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Número de teléfono inválido: ' . $telefono
                ], 400);
            }
            
            Log::info('Teléfono formateado para llamada', [
                'telefono_original' => $telefono,
                'telefono_formateado' => $telefonoFormateado,
                'conductor' => $conductorNombre
            ]);
            
            // Obtener cotización
            $cotizacion = CotizacionModel::findOrFail($cotizacionId);

            // Verificar si el conductor ya fue contactado y tiene respuesta para esta cotización
            $conductorExistente = \App\Models\LlamadaConductor::where('cotizacion_id', $cotizacionId)
                ->where('telefono', 'LIKE', '%' . substr(preg_replace('/\D+/', '', $telefono), -10) . '%')
                ->first();

            if ($conductorExistente && $conductorExistente->hasBeenContacted()) {
                return response()->json([
                    'success' => false,
                    'message' => "El conductor {$conductorNombre} ya fue contactado y tiene una respuesta registrada para este servicio. No se volverá a llamar.",
                    'already_contacted' => true,
                    'respuesta' => $conductorExistente->respuesta_llamada
                ], 409);
            }
            
            // Registrar llamada en base de datos
            $llamada = \App\Models\Llamada::create([
                'id_cotizacion' => $cotizacionId,
                'chofer_id' => null, // No tenemos ID del chofer de Arcangel
                'chofer_nombre' => $conductorNombre,
                'telefono' => $telefonoFormateado,
                'placa' => $placa,
                'status' => 'iniciando',
                'origen' => 'arcangel_modal',
                'fecha_hora' => now(),
            ]);
            
            // Iniciar llamada con ElevenLabs
            $elevenLabsService = app(\App\Services\ElevenLabsCallService::class);
            
            $callResult = $elevenLabsService->initiateCall(
                phoneNumber: $telefonoFormateado,
                cotizacionId: $cotizacionId,
                conductorNombre: $conductorNombre,
                metadata: [
                    'placa' => $placa,
                    'origen' => 'arcangel_modal',
                    'llamada_id' => $llamada->id_llamada
                ]
            );
            
            if ($callResult['success']) {
                // Actualizar llamada con conversation_id
                $llamada->update([
                    'elevenlabs_conversation_id' => $callResult['conversation_id'] ?? null,
                    'status' => 'en_progreso',
                ]);
                
                Log::info('✅ Llamada iniciada exitosamente', [
                    'conversation_id' => $callResult['conversation_id'],
                    'conductor' => $conductorNombre,
                    'telefono' => $telefonoFormateado
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Llamada iniciada a {$conductorNombre}",
                    'data' => [
                        'llamada_id' => $llamada->id_llamada,
                        'conversation_id' => $callResult['conversation_id'] ?? null,
                        'telefono' => $telefonoFormateado,
                        'conductor' => $conductorNombre,
                        'status' => 'en_progreso'
                    ]
                ]);
            } else {
                // Error al iniciar llamada
                $llamada->update([
                    'status' => 'fallida',
                    'notas' => 'Error: ' . ($callResult['message'] ?? 'Error desconocido')
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al iniciar llamada: ' . ($callResult['message'] ?? 'Error desconocido')
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Error al llamar conductor', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar llamada: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Formatear teléfono agregando código de país +57 si no lo tiene
     * 
     * @param string $telefono
     * @return string|null
     */
    private function formatearTelefono($telefono)
    {
        if (!$telefono || $telefono === 'N/A') {
            return null;
        }
        
        // Limpiar el teléfono
        $telefonoLimpio = trim($telefono);
        
        // Si ya tiene +, verificar que sea válido
        if (str_starts_with($telefonoLimpio, '+')) {
            // Limpiar todo excepto + y números
            $formateado = preg_replace('/[^+0-9]/', '', $telefonoLimpio);
            
            // Validar longitud mínima
            $soloNumeros = str_replace('+', '', $formateado);
            if (strlen($soloNumeros) >= 10) {
                return $formateado;
            }
        }
        
        // Extraer solo números
        $soloNumeros = preg_replace('/[^0-9]/', '', $telefonoLimpio);
        
        // Si tiene 10 dígitos, es número colombiano
        if (strlen($soloNumeros) === 10) {
            return '+57' . $soloNumeros;
        }
        
        // Si tiene 7-9 dígitos, agregar +57
        if (strlen($soloNumeros) >= 7 && strlen($soloNumeros) < 10) {
            return '+57' . $soloNumeros;
        }
        
        // Si tiene más de 10 dígitos, agregar + solamente
        if (strlen($soloNumeros) > 10) {
            return '+' . $soloNumeros;
        }
        
        return null;
    }
}
