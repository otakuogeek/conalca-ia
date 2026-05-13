<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehiculoController extends Controller
{
    protected $arcangelService;

    public function __construct(ArcangelService $arcangelService)
    {
        $this->arcangelService = $arcangelService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Obtener tipos de vehículos con sus relaciones
            $tiposVehiculos = DB::table('vehiculos_arcangel')
                ->orderBy('nombre')
                ->get();
            
            // Agregar relaciones a cada vehículo
            foreach ($tiposVehiculos as $vehiculo) {
                $vehiculo->relaciones = DB::table('vehiculos_relaciones')
                    ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                    ->where('vehiculos_relaciones.vehiculo_arcangel_id', $vehiculo->id)
                    ->select('vehiculos_pricing.*', 'vehiculos_relaciones.id as relacion_id')
                    ->get();
            }
            
            // Obtener todos los vehículos pricing para el modal
            $vehiculosPricing = DB::table('vehiculos_pricing')
                ->orderBy('vehiculo_silogtran')
                ->orderBy('peso_maximo')
                ->get();
            
            return view('vehiculos.index', [
                'tiposVehiculos' => $tiposVehiculos,
                'vehiculosPricing' => $vehiculosPricing,
                'error' => null
            ]);
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error obteniendo tipos de vehículos', [
                'error' => $e->getMessage()
            ]);
            
            return view('vehiculos.index', [
                'tiposVehiculos' => collect([]),
                'vehiculosPricing' => collect([]),
                'error' => 'Error al obtener tipos de vehículos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener lista de ciudades disponibles en Arcangel
     */
    public function getCiudades()
    {
        try {
            $ciudades = $this->arcangelService->getCiudades(false);
            
            // Ordenar alfabéticamente
            sort($ciudades);
            
            return response()->json([
                'success' => true,
                'ciudades' => $ciudades,
                'total' => count($ciudades)
            ]);
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error obteniendo ciudades', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ciudades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar vehículos disponibles en una ciudad específica (sin almacenar)
     */
    public function buscarVehiculosCiudad(Request $request)
    {
        try {
            $ciudad = $request->input('ciudad');
            
            if (!$ciudad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar una ciudad'
                ], 400);
            }
            
            $response = $this->arcangelService->getVehiculosCercanos($ciudad, false);
            $vehiculos = $response['vehiculos'] ?? [];
            
            // Agrupar por tipo de vehículo y contar
            $vehiculosPorTipo = [];
            foreach ($vehiculos as $vehiculo) {
                if (is_array($vehiculo) && isset($vehiculo['clase'])) {
                    $clase = $vehiculo['clase'];
                    if (!isset($vehiculosPorTipo[$clase])) {
                        $vehiculosPorTipo[$clase] = [
                            'tipo' => $clase,
                            'cantidad' => 0,
                            'vehiculos' => []
                        ];
                    }
                    $vehiculosPorTipo[$clase]['cantidad']++;
                    $vehiculosPorTipo[$clase]['vehiculos'][] = [
                        'placa' => $vehiculo['placa'] ?? 'N/A',
                        'conductor' => $vehiculo['conductor'] ?? 'N/A',
                        'telefono' => $vehiculo['celular'] ?? $vehiculo['telefono'] ?? 'N/A',
                        'empresa' => $vehiculo['empresa'] ?? 'N/A',
                        'score' => $vehiculo['score'] ?? 0,
                        'disponibilidad' => $vehiculo['disponibilidad'] ?? 'Disponible',
                        'ubicacion' => $vehiculo['ubicacion'] ?? $ciudad,
                        'latitud' => $vehiculo['latitud'] ?? null,
                        'longitud' => $vehiculo['longitud'] ?? null,
                    ];
                }
            }
            
            // Ordenar por cantidad descendente
            usort($vehiculosPorTipo, function($a, $b) {
                return $b['cantidad'] - $a['cantidad'];
            });
            
            return response()->json([
                'success' => true,
                'ciudad' => $ciudad,
                'totalVehiculos' => count($vehiculos),
                'tiposUnicos' => count($vehiculosPorTipo),
                'vehiculosPorTipo' => array_values($vehiculosPorTipo),
                'usingFallback' => (bool) ($response['fallback'] ?? false),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error buscando vehículos en ciudad', [
                'ciudad' => $request->input('ciudad'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No fue posible consultar Arcángel en este momento. Intenta nuevamente en unos minutos.'
            ], 500);
        }
    }

    /**
     * Agregar relación entre vehículo Arcangel y vehículo Pricing
     */
    public function agregarRelacion(Request $request)
    {
        try {
            $vehiculoArcangelId = $request->input('vehiculo_arcangel_id');
            $vehiculoPricingId = $request->input('vehiculo_pricing_id');
            
            // Verificar que no exista la relación
            $existe = DB::table('vehiculos_relaciones')
                ->where('vehiculo_arcangel_id', $vehiculoArcangelId)
                ->where('vehiculo_pricing_id', $vehiculoPricingId)
                ->exists();
            
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta relación ya existe'
                ], 400);
            }
            
            DB::table('vehiculos_relaciones')->insert([
                'vehiculo_arcangel_id' => $vehiculoArcangelId,
                'vehiculo_pricing_id' => $vehiculoPricingId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Obtener el vehículo pricing agregado
            $vehiculoPricing = DB::table('vehiculos_pricing')
                ->where('id', $vehiculoPricingId)
                ->first();
            
            $relacion = DB::table('vehiculos_relaciones')
                ->where('vehiculo_arcangel_id', $vehiculoArcangelId)
                ->where('vehiculo_pricing_id', $vehiculoPricingId)
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Relación agregada exitosamente',
                'relacion' => [
                    'id' => $relacion->id,
                    'vehiculo_silogtran' => $vehiculoPricing->vehiculo_silogtran,
                    'tabla_pricing' => $vehiculoPricing->tabla_pricing,
                    'peso_maximo' => $vehiculoPricing->peso_maximo
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error agregando relación', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar relación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar relación
     */
    public function eliminarRelacion(Request $request, $id)
    {
        try {
            $deleted = DB::table('vehiculos_relaciones')
                ->where('id', $id)
                ->delete();
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Relación eliminada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Relación no encontrada'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error eliminando relación', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar relación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener relaciones de un vehículo Arcangel
     */
    public function obtenerRelaciones($id)
    {
        try {
            $relaciones = DB::table('vehiculos_relaciones')
                ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
                ->where('vehiculos_relaciones.vehiculo_arcangel_id', $id)
                ->select('vehiculos_pricing.*', 'vehiculos_relaciones.id as relacion_id')
                ->get();
            
            return response()->json([
                'success' => true,
                'relaciones' => $relaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener relaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar tipos de vehículos desde Arcangel con progreso en tiempo real (SSE)
     * OPTIMIZADO: Omite tipos ya existentes y detiene si no encuentra nuevos
     */
    public function sincronizar(Request $request)
    {
        // Aumentar tiempo de ejecución
        set_time_limit(600);
        ini_set('memory_limit', '256M');
        
        return new StreamedResponse(function () {
            // Desactivar buffer de salida
            if (ob_get_level()) ob_end_clean();
            
            // 1. OPTIMIZACIÓN: Cargar tipos existentes al inicio
            $tiposExistentesDB = DB::table('vehiculos_arcangel')
                ->pluck('nombre')
                ->toArray();
            $tiposExistentesMap = array_flip($tiposExistentesDB);
            $cantidadExistentes = count($tiposExistentesDB);
            
            $this->sendSSE('init', [
                'message' => 'Iniciando sincronización optimizada...',
                'step' => 'Analizando tipos existentes',
                'tiposExistentes' => $cantidadExistentes
            ]);

            try {
                // 2. Obtener todas las ciudades de Arcangel
                $ciudades = $this->arcangelService->getCiudades(false);
                $totalCiudades = count($ciudades);
                
                // Mezclar ciudades para mayor diversidad en el escaneo
                shuffle($ciudades);
                
                $this->sendSSE('ciudades', [
                    'total' => $totalCiudades,
                    'message' => "Se encontraron {$totalCiudades} ciudades. Ya tienes {$cantidadExistentes} tipos registrados.",
                    'estimatedTime' => $this->estimateTime($totalCiudades),
                    'tiposExistentes' => $cantidadExistentes
                ]);

                $tiposNuevosEncontrados = [];
                $vehiculosTotal = 0;
                $procesadas = 0;
                $errores = 0;
                $omitidos = 0;
                $startTime = microtime(true);
                $ciudadesSinNuevosTipos = 0;
                $maxCiudadesSinNuevos = 50; // Detener si 50 ciudades seguidas no tienen tipos nuevos

                // 3. Consultar vehículos de cada ciudad
                foreach ($ciudades as $index => $ciudad) {
                    $procesadas++;
                    $porcentaje = round(($procesadas / $totalCiudades) * 100, 1);
                    
                    // Calcular tiempo restante
                    $elapsed = microtime(true) - $startTime;
                    $avgTimePerCity = $procesadas > 0 ? $elapsed / $procesadas : 1;
                    $remaining = ($totalCiudades - $procesadas) * $avgTimePerCity;
                    $remainingFormatted = $this->formatTime($remaining);
                    
                    $nuevosEnEstaCiudad = 0;
                    
                    try {
                        $response = $this->arcangelService->getVehiculosCercanos($ciudad, false);
                        $vehiculos = $response['vehiculos'] ?? [];
                        $cantidadVehiculos = count($vehiculos);
                        $vehiculosTotal += $cantidadVehiculos;
                        
                        foreach ($vehiculos as $vehiculo) {
                            if (is_array($vehiculo) && isset($vehiculo['clase'])) {
                                $clase = trim($vehiculo['clase']);
                                
                                if (!$clase) continue;
                                
                                // OPTIMIZACIÓN: Verificar si ya existe en DB o ya lo encontramos
                                if (isset($tiposExistentesMap[$clase])) {
                                    $omitidos++;
                                    continue; // Ya existe en DB, omitir
                                }
                                
                                if (isset($tiposNuevosEncontrados[$clase])) {
                                    continue; // Ya lo encontramos en otra ciudad
                                }
                                
                                // ¡Nuevo tipo encontrado!
                                $tiposNuevosEncontrados[$clase] = true;
                                $nuevosEnEstaCiudad++;
                                
                                // OPTIMIZACIÓN: Guardar inmediatamente en DB
                                try {
                                    DB::table('vehiculos_arcangel')->insert([
                                        'nombre' => $clase,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    
                                    $this->sendSSE('nuevo_tipo', [
                                        'tipo' => $clase,
                                        'ciudad' => $ciudad,
                                        'total_nuevos' => count($tiposNuevosEncontrados)
                                    ]);
                                } catch (\Exception $e) {
                                    // Posible duplicado por race condition, ignorar
                                }
                            }
                        }
                        
                        // Verificar si encontramos tipos nuevos en esta ciudad
                        if ($nuevosEnEstaCiudad > 0) {
                            $ciudadesSinNuevosTipos = 0; // Resetear contador
                        } else {
                            $ciudadesSinNuevosTipos++;
                        }
                        
                        // Enviar progreso
                        $this->sendSSE('progress', [
                            'current' => $procesadas,
                            'total' => $totalCiudades,
                            'percent' => $porcentaje,
                            'ciudad' => $ciudad,
                            'vehiculosEnCiudad' => $cantidadVehiculos,
                            'vehiculosTotal' => $vehiculosTotal,
                            'tiposNuevos' => count($tiposNuevosEncontrados),
                            'tiposExistentes' => $cantidadExistentes,
                            'omitidos' => $omitidos,
                            'tiempoRestante' => $remainingFormatted,
                            'errores' => $errores,
                            'ciudadesSinNuevos' => $ciudadesSinNuevosTipos
                        ]);
                        
                        // OPTIMIZACIÓN: Detener temprano si no hay tipos nuevos
                        if ($ciudadesSinNuevosTipos >= $maxCiudadesSinNuevos && $procesadas > 100) {
                            $this->sendSSE('early_stop', [
                                'message' => "Deteniendo: {$maxCiudadesSinNuevos} ciudades sin tipos nuevos",
                                'ciudadesProcesadas' => $procesadas,
                                'tiposNuevos' => count($tiposNuevosEncontrados)
                            ]);
                            break;
                        }
                        
                    } catch (\Exception $e) {
                        $errores++;
                        $this->sendSSE('progress', [
                            'current' => $procesadas,
                            'total' => $totalCiudades,
                            'percent' => $porcentaje,
                            'ciudad' => $ciudad,
                            'vehiculosEnCiudad' => 0,
                            'vehiculosTotal' => $vehiculosTotal,
                            'tiposNuevos' => count($tiposNuevosEncontrados),
                            'tiposExistentes' => $cantidadExistentes,
                            'omitidos' => $omitidos,
                            'tiempoRestante' => $remainingFormatted,
                            'errores' => $errores,
                            'error' => "Error en {$ciudad}"
                        ]);
                    }
                    
                    // Pausa más corta ya que el proceso es más eficiente
                    usleep(50000); // 0.05 segundos
                }

                $tiempoTotal = round(microtime(true) - $startTime, 1);
                $totalTiposEnDB = $cantidadExistentes + count($tiposNuevosEncontrados);

                // 4. Enviar resultado final
                $this->sendSSE('complete', [
                    'success' => true,
                    'message' => 'Sincronización completada exitosamente',
                    'ciudadesConsultadas' => $procesadas,
                    'ciudadesTotal' => $totalCiudades,
                    'vehiculosEncontrados' => $vehiculosTotal,
                    'tiposNuevos' => count($tiposNuevosEncontrados),
                    'tiposExistentes' => $cantidadExistentes,
                    'totalTiposEnDB' => $totalTiposEnDB,
                    'tiposAgregados' => array_keys($tiposNuevosEncontrados),
                    'omitidos' => $omitidos,
                    'errores' => $errores,
                    'tiempoTotal' => $tiempoTotal . ' segundos',
                    'detencionTemprana' => $ciudadesSinNuevosTipos >= $maxCiudadesSinNuevos
                ]);

                Log::info('VehiculoController: Sincronización completada', [
                    'ciudades' => $procesadas,
                    'vehiculos' => $vehiculosTotal,
                    'nuevos' => count($tiposNuevosEncontrados),
                    'existentes' => $cantidadExistentes,
                    'tiempo' => $tiempoTotal
                ]);

            } catch (\Exception $e) {
                Log::error('VehiculoController: Error en sincronización', [
                    'error' => $e->getMessage()
                ]);

                $this->sendSSE('error', [
                    'success' => false,
                    'message' => 'Error durante la sincronización: ' . $e->getMessage()
                ]);
            }
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Enviar evento SSE
     */
    private function sendSSE($event, $data)
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        if (ob_get_level()) ob_flush();
        flush();
    }

    /**
     * Estimar tiempo de escaneo
     */
    private function estimateTime($totalCiudades)
    {
        // Aproximadamente 1 segundo por ciudad (incluyendo delays)
        $segundos = $totalCiudades * 1.1;
        return $this->formatTime($segundos);
    }

    /**
     * Formatear tiempo en formato legible
     */
    private function formatTime($segundos)
    {
        if ($segundos < 60) {
            return round($segundos) . ' segundos';
        } elseif ($segundos < 3600) {
            $minutos = floor($segundos / 60);
            $segs = round($segundos % 60);
            return "{$minutos}m {$segs}s";
        } else {
            $horas = floor($segundos / 3600);
            $minutos = floor(($segundos % 3600) / 60);
            return "{$horas}h {$minutos}m";
        }
    }

    /**
     * Cambiar modo de Arcángel (production/development)
     */
    public function cambiarModoArcangel(Request $request)
    {
        try {
            $nuevoModo = $request->input('modo'); // 'production' o 'development'
            
            if (!in_array($nuevoModo, ['production', 'development'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modo inválido. Use "production" o "development"'
                ], 400);
            }

            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // Eliminar todas las líneas ARCANGEL_MODE existentes y dejar solo una
            $envContent = preg_replace('/^#?ARCANGEL_MODE=.*\n?/m', '', $envContent);
            // Insertar el nuevo valor después del comentario de ARCANGEL
            $envContent = preg_replace(
                '/(# ARCANGEL - INTEGRACIÓN API\n# ============================================================================\n)/',
                '$1ARCANGEL_MODE=' . $nuevoModo . "\n",
                $envContent
            );

            file_put_contents($envPath, $envContent);

            // Limpiar caches específicos de Arcángel (tokens, ciudades, vehículos de ambos modos)
            foreach (['production', 'development'] as $m) {
                Cache::forget("arcangel_auth_token_{$m}");
                Cache::forget("arcangel_token_expires_at_{$m}");
                Cache::forget("arcangel_ciudades_{$m}");
                Cache::forget("arcangel_ciudades_stale_{$m}");
            }

            // Limpiar caché de configuración y re-cachear con los nuevos valores
            \Artisan::call('config:clear');
            \Artisan::call('config:cache');
            \Artisan::call('cache:clear');

            // SIEMPRE ejecutar eliminación del archivo hot para restablecer CSS
            $hotFile = public_path('hot');
            $hotExistedBefore = file_exists($hotFile);
            \Artisan::call('vite:remove-hot');
            $hotExistsAfter = file_exists($hotFile);
            Log::info('Eliminación archivo hot ejecutada', [
                'existia_antes' => $hotExistedBefore,
                'existe_despues' => $hotExistsAfter,
                'artisan_output' => trim(\Artisan::output()),
            ]);

            Log::info('Modo Arcángel cambiado', [
                'modo_anterior' => config('arcangel.mode'),
                'modo_nuevo' => $nuevoModo,
                'usuario' => auth()->user()->email ?? 'desconocido',
                'hot_eliminado' => !$hotExistsAfter,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modo cambiado exitosamente a ' . strtoupper($nuevoModo),
                'modo' => $nuevoModo,
                'url' => $nuevoModo === 'production' 
                    ? config('arcangel.base_url') 
                    : config('arcangel.base_url_dev'),
                'api_key' => $nuevoModo === 'production' 
                    ? substr(config('arcangel.api_key'), 0, 10) . '...'
                    : substr(config('arcangel.api_key_dev'), 0, 10) . '...'
            ]);

        } catch (\Exception $e) {
            Log::error('Error cambiando modo Arcángel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar modo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener modo actual de Arcángel
     */
    public function obtenerModoActual()
    {
        try {
            $modo = config('arcangel.mode', 'production');
            $baseUrl = $modo === 'production' 
                ? config('arcangel.base_url') 
                : config('arcangel.base_url_dev');
            $apiKey = $modo === 'production' 
                ? config('arcangel.api_key') 
                : config('arcangel.api_key_dev');

            return response()->json([
                'success' => true,
                'modo' => $modo,
                'url' => $baseUrl,
                'api_key_preview' => substr($apiKey, 0, 10) . '...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener modo actual: ' . $e->getMessage()
            ], 500);
        }
    }
}

