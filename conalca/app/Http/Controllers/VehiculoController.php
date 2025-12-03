<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            // Obtener tipos de vehículos de la base de datos
            $tiposVehiculos = DB::table('vehiculos_arcangel')
                ->orderBy('nombre')
                ->get();
            
            return view('vehiculos.index', [
                'tiposVehiculos' => $tiposVehiculos,
                'error' => null
            ]);
        } catch (\Exception $e) {
            Log::error('VehiculoController: Error obteniendo tipos de vehículos', [
                'error' => $e->getMessage()
            ]);
            
            return view('vehiculos.index', [
                'tiposVehiculos' => collect([]),
                'error' => 'Error al obtener tipos de vehículos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincronizar tipos de vehículos desde Arcangel con progreso en tiempo real (SSE)
     */
    public function sincronizar(Request $request)
    {
        // Aumentar tiempo de ejecución para procesar todas las ciudades
        set_time_limit(600); // 10 minutos
        ini_set('memory_limit', '256M');
        
        return new StreamedResponse(function () {
            // Desactivar buffer de salida
            if (ob_get_level()) ob_end_clean();
            
            $this->sendSSE('init', [
                'message' => 'Iniciando sincronización...',
                'step' => 'Obteniendo lista de ciudades'
            ]);

            try {
                // 1. Obtener todas las ciudades de Arcangel
                $ciudades = $this->arcangelService->getCiudades(false);
                $totalCiudades = count($ciudades);
                
                $this->sendSSE('ciudades', [
                    'total' => $totalCiudades,
                    'message' => "Se encontraron {$totalCiudades} ciudades para escanear",
                    'estimatedTime' => $this->estimateTime($totalCiudades)
                ]);

                $tiposUnicos = [];
                $vehiculosTotal = 0;
                $procesadas = 0;
                $errores = 0;
                $startTime = microtime(true);

                // 2. Consultar vehículos de cada ciudad
                foreach ($ciudades as $index => $ciudad) {
                    $procesadas++;
                    $porcentaje = round(($procesadas / $totalCiudades) * 100, 1);
                    
                    // Calcular tiempo restante
                    $elapsed = microtime(true) - $startTime;
                    $avgTimePerCity = $procesadas > 0 ? $elapsed / $procesadas : 1;
                    $remaining = ($totalCiudades - $procesadas) * $avgTimePerCity;
                    $remainingFormatted = $this->formatTime($remaining);
                    
                    try {
                        $response = $this->arcangelService->getVehiculosCercanos($ciudad, false);
                        $vehiculos = $response['vehiculos'] ?? [];
                        $cantidadVehiculos = count($vehiculos);
                        $vehiculosTotal += $cantidadVehiculos;
                        
                        foreach ($vehiculos as $vehiculo) {
                            if (is_array($vehiculo) && isset($vehiculo['clase'])) {
                                $clase = trim($vehiculo['clase']);
                                if ($clase && !isset($tiposUnicos[$clase])) {
                                    $tiposUnicos[$clase] = true;
                                }
                            }
                        }
                        
                        // Enviar progreso cada ciudad
                        $this->sendSSE('progress', [
                            'current' => $procesadas,
                            'total' => $totalCiudades,
                            'percent' => $porcentaje,
                            'ciudad' => $ciudad,
                            'vehiculosEnCiudad' => $cantidadVehiculos,
                            'vehiculosTotal' => $vehiculosTotal,
                            'tiposEncontrados' => count($tiposUnicos),
                            'tiempoRestante' => $remainingFormatted,
                            'errores' => $errores
                        ]);
                        
                    } catch (\Exception $e) {
                        $errores++;
                        $this->sendSSE('progress', [
                            'current' => $procesadas,
                            'total' => $totalCiudades,
                            'percent' => $porcentaje,
                            'ciudad' => $ciudad,
                            'vehiculosEnCiudad' => 0,
                            'vehiculosTotal' => $vehiculosTotal,
                            'tiposEncontrados' => count($tiposUnicos),
                            'tiempoRestante' => $remainingFormatted,
                            'errores' => $errores,
                            'error' => "Error en {$ciudad}"
                        ]);
                    }
                    
                    // Pequeña pausa para no saturar la API
                    usleep(100000); // 0.1 segundos
                }

                // 3. Insertar tipos únicos en la base de datos
                $this->sendSSE('saving', [
                    'message' => 'Guardando tipos de vehículos en base de datos...',
                    'tiposUnicos' => count($tiposUnicos)
                ]);

                $tiposNuevos = 0;
                $tiposExistentes = 0;
                $tiposAgregados = [];

                foreach (array_keys($tiposUnicos) as $tipo) {
                    $existe = DB::table('vehiculos_arcangel')
                        ->where('nombre', $tipo)
                        ->exists();
                    
                    if (!$existe) {
                        DB::table('vehiculos_arcangel')->insert([
                            'nombre' => $tipo,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $tiposNuevos++;
                        $tiposAgregados[] = $tipo;
                    } else {
                        $tiposExistentes++;
                    }
                }

                $tiempoTotal = round(microtime(true) - $startTime, 1);

                // 4. Enviar resultado final
                $this->sendSSE('complete', [
                    'success' => true,
                    'message' => 'Sincronización completada exitosamente',
                    'ciudadesConsultadas' => $totalCiudades,
                    'vehiculosEncontrados' => $vehiculosTotal,
                    'tiposUnicos' => count($tiposUnicos),
                    'tiposNuevos' => $tiposNuevos,
                    'tiposExistentes' => $tiposExistentes,
                    'tiposAgregados' => $tiposAgregados,
                    'errores' => $errores,
                    'tiempoTotal' => $tiempoTotal . ' segundos'
                ]);

                Log::info('VehiculoController: Sincronización completada', [
                    'ciudades' => $totalCiudades,
                    'vehiculos' => $vehiculosTotal,
                    'tipos' => count($tiposUnicos),
                    'nuevos' => $tiposNuevos,
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
            'X-Accel-Buffering' => 'no', // Desactiva buffering en nginx
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
}
