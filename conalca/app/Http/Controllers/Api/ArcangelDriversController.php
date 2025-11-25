<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CotizacionModel;
use App\Services\ArcangelService;
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
            $minScore = $request->input('min_score', 7);
            $limit = $request->input('limit', 50);
            
            // Obtener cotización
            $cotizacion = CotizacionModel::findOrFail($cotizacionId);
            
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
            
            // Convertir tipo de vehículo al formato de Arcángel (puede ser múltiples variantes)
            $variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido);
            
            Log::info('ArcangelDriversController: Buscando conductores', [
                'cotizacion_id' => $cotizacionId,
                'ciudad_origen' => $ciudadOrigen,
                'vehiculo_requerido_original' => $cotizacion->vehiculo_requerido,
                'variantes_vehiculo' => $variantesVehiculo,
                'min_score' => $minScore,
                'limit' => $limit
            ]);
            
            // Buscar en Arcángel API con todas las variantes del vehículo
            $resultado = $this->arcangelService->getVehiculosFiltrados(
                ciudad: $ciudadOrigen,
                clases: $variantesVehiculo, // Ahora es un array de variantes
                minScore: $minScore,
                limit: $limit,
                useCache: true,
                cacheTTL: 15 // 15 minutos de cache
            );
            
            $vehiculos = $resultado['vehiculos'] ?? [];
            
            // Formatear datos para el frontend
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
                    'ultima_actualizacion' => $vehiculo['ultimaActualizacion'] ?? null,
                ];
            }, $vehiculos);
            
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
                    ]
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
                'message' => 'Error al buscar conductores: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
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
            $minScore = $request->input('min_score', 7);
            $limit = $request->input('limit', 50);
            
            $resultado = $this->arcangelService->getVehiculosFiltrados(
                ciudad: $ciudad,
                clases: $vehiculo,
                minScore: $minScore,
                limit: $limit,
                useCache: true,
                cacheTTL: 15
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
     * Retorna array de posibles variantes en Arcángel
     */
    private function convertirTipoVehiculo(string $tipoLocal): array
    {
        $tipoNormalizado = $this->normalizarTexto($tipoLocal);
        
        // Mapeo de nuestros tipos a los de Arcángel
        $mapeo = [
            // Tractomulas
            'TRACTO MULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
            'TRACTO MULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
            'TRACTOMULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
            'TRACTOMULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
            
            // Sencillos
            'SENCILLO' => ['SENCILLO'],
            'CAMION SENCILLO' => ['SENCILLO'],
            
            // Doble troque
            'DOBLE TROQUE' => ['DOBLE TROQUE', 'DOBLETROQUE'],
            
            // Camionetas
            'CAMIONETA' => ['CAMIONETA', 'TURBO'],
            'TURBO' => ['TURBO', 'CAMIONETA'],
            
            // Patinetas
            'PATINETA' => ['PATINETA', 'PATINETA2', 'PATINETA3'],
            'PATINETA 2' => ['PATINETA2', 'PATINETA 2'],
            'PATINETA 3' => ['PATINETA3', 'PATINETA 3'],
        ];
        
        // Buscar coincidencia en el mapeo
        foreach ($mapeo as $clave => $variantes) {
            if (str_contains($tipoNormalizado, $clave) || $tipoNormalizado === $clave) {
                return $variantes;
            }
        }
        
        // Si no hay mapeo, devolver el tipo normalizado
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
