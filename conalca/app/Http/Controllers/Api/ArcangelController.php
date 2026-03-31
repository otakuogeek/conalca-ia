<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ArcangelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArcangelController extends Controller
{
    protected ArcangelService $arcangelService;

    public function __construct(ArcangelService $arcangelService)
    {
        $this->arcangelService = $arcangelService;
    }

    /**
     * Probar la conexión con Arcangel API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck()
    {
        try {
            $isHealthy = $this->arcangelService->healthCheck();
            $info = $this->arcangelService->getInfo();

            return response()->json([
                'success' => $isHealthy,
                'message' => $isHealthy ? 'API Arcangel disponible' : 'API Arcangel no disponible',
                'info' => $info,
            ], $isHealthy ? 200 : 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar la API de Arcangel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejemplo: Consultar datos desde Arcangel
     * GET /api/arcangel/consultar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'params' => 'sometimes|array',
                'use_cache' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $endpoint = $request->input('endpoint');
            $params = $request->input('params', []);
            $useCache = $request->input('use_cache', false);

            $data = $this->arcangelService->get($endpoint, $params, $useCache);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar la API de Arcangel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejemplo: Crear/Enviar datos a Arcangel
     * POST /api/arcangel/crear
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function crear(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $endpoint = $request->input('endpoint');
            $data = $request->input('data');

            $response = $this->arcangelService->post($endpoint, $data);

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar datos a la API de Arcangel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejemplo: Actualizar datos en Arcangel
     * PUT /api/arcangel/actualizar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function actualizar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'data' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $endpoint = $request->input('endpoint');
            $data = $request->input('data');

            $response = $this->arcangelService->put($endpoint, $data);

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar datos en la API de Arcangel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejemplo: Eliminar datos en Arcangel
     * DELETE /api/arcangel/eliminar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function eliminar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'data' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $endpoint = $request->input('endpoint');
            $data = $request->input('data', []);

            $response = $this->arcangelService->delete($endpoint, $data);

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar datos en la API de Arcangel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Limpiar la caché de Arcangel
     * POST /api/arcangel/clear-cache
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache(Request $request)
    {
        try {
            if ($request->has('endpoint')) {
                $endpoint = $request->input('endpoint');
                $params = $request->input('params', []);
                $this->arcangelService->clearCache($endpoint, $params);
                
                return response()->json([
                    'success' => true,
                    'message' => "Caché limpiada para el endpoint: {$endpoint}",
                ]);
            } else {
                $this->arcangelService->clearAllCache();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Toda la caché de Arcangel ha sido limpiada',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar la caché',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener vehículos cercanos filtrados por tipo de clase
     * GET /api/arcangel/vehiculos/filtrar
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtrarVehiculos(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ciudad' => 'required|string|max:100',
                'clases' => 'sometimes|array',
                'clases.*' => 'string',
                'clase' => 'sometimes|string',
                'min_score' => 'sometimes|integer|min:0|max:100',
                'limit' => 'sometimes|integer|min:1|max:500',
                'use_cache' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ciudad = $request->input('ciudad');
            $clases = $request->input('clases');
            $clase = $request->input('clase');
            $minScore = $request->input('min_score');
            $limit = $request->input('limit');
            $useCache = $request->input('use_cache', false);

            // Si se envió 'clase' (singular), convertirlo a array
            if ($clase && !$clases) {
                $clases = $clase;
            }

            $result = $this->arcangelService->getVehiculosFiltrados(
                $ciudad,
                $clases,
                $minScore,
                $limit,
                $useCache
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar vehículos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener clases de vehículos disponibles en una ciudad
     * GET /api/arcangel/vehiculos/clases
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerClasesDisponibles(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ciudad' => 'required|string|max:100',
                'use_cache' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ciudad = $request->input('ciudad');
            $useCache = $request->input('use_cache', false);

            $result = $this->arcangelService->getClasesDisponibles($ciudad, $useCache);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clases disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener ciudades disponibles
     * GET /api/arcangel/ciudades
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerCiudades(Request $request)
    {
        try {
            $useCache = $request->input('use_cache', false);
            $result = $this->arcangelService->getCiudades($useCache);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ciudades',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener vehículos cercanos a una ciudad
     * GET /api/arcangel/vehiculos/cercanos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerVehiculosCercanos(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ciudad' => 'required|string|max:100',
                'use_cache' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ciudad = $request->input('ciudad');
            $useCache = $request->input('use_cache', false);

            $result = $this->arcangelService->getVehiculosCercanos($ciudad, $useCache);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener vehículos cercanos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
