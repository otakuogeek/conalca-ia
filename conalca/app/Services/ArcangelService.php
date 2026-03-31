<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ArcangelService
{
    protected const FALLBACK_CIUDADES = [
        'ARMENIA', 'BARRANCABERMEJA', 'BARRANQUILLA', 'BOGOTA', 'BUCARAMANGA',
        'BUENAVENTURA', 'BUGA', 'CALI', 'CARTAGENA', 'CARTAGO', 'CHIQUINQUIRA',
        'CUCUTA', 'DOSQUEBRADAS', 'DUITAMA', 'ENVIGADO', 'FLORENCIA', 'FUNZA',
        'GIRARDOT', 'IBAGUE', 'IPIALES', 'ITAGUI', 'LETICIA', 'MANIZALES',
        'MEDELLIN', 'MONTERIA', 'NEIVA', 'PALMIRA', 'PASTO', 'PEREIRA',
        'POPAYAN', 'RIOHACHA', 'RIONEGRO', 'SABANETA', 'SANTA MARTA',
        'SINCELEJO', 'SOACHA', 'TUNJA', 'VALLEDUPAR', 'VILLAVICENCIO', 'YUMBO',
    ];

    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $mode;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;

    public function __construct()
    {
        $this->mode = config('arcangel.mode', 'production');
        
        // Seleccionar URL y API Key según el modo
        $this->baseUrl = $this->mode === 'development' 
            ? config('arcangel.base_url_dev')
            : config('arcangel.base_url');
            
        $this->apiKey = $this->mode === 'development'
            ? config('arcangel.api_key_dev', '')
            : config('arcangel.api_key', '');
            
        $this->timeout = config('arcangel.timeout', 30);
        $this->retryTimes = config('arcangel.retry_times', 3);
        $this->retryDelay = config('arcangel.retry_delay', 100);

        Log::info('ArcangelService initialized', [
            'mode' => $this->mode,
            'base_url' => $this->baseUrl,
        ]);
    }

    /**
     * Realizar una petición GET a la API de Arcangel
     *
     * @param string $endpoint
     * @param array $params
     * @param bool $useCache
     * @param int $cacheTTL Cache TTL in minutes
     * @return array
     */
    public function get(string $endpoint, array $params = [], bool $useCache = false, int $cacheTTL = 60): array
    {
        $cacheKey = "arcangel_{$this->mode}_{$endpoint}_" . md5(json_encode($params));

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('ArcangelService: Cache hit', ['endpoint' => $endpoint]);
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . ltrim($endpoint, '/'), $params);

            $data = $this->handleResponse($response, $endpoint);

            if ($useCache) {
                Cache::put($cacheKey, $data, now()->addMinutes($cacheTTL));
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('ArcangelService GET error', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Realizar una petición POST a la API de Arcangel
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders())
                ->post($this->baseUrl . ltrim($endpoint, '/'), $data);

            return $this->handleResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('ArcangelService POST error', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Realizar una petición PUT a la API de Arcangel
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders())
                ->put($this->baseUrl . ltrim($endpoint, '/'), $data);

            return $this->handleResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('ArcangelService PUT error', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Realizar una petición DELETE a la API de Arcangel
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function delete(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders())
                ->delete($this->baseUrl . ltrim($endpoint, '/'), $data);

            return $this->handleResponse($response, $endpoint);
        } catch (\Exception $e) {
            Log::error('ArcangelService DELETE error', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener los headers necesarios para las peticiones
     *
     * @param bool $includeToken Si se debe incluir el token de autorización
     * @return array
     */
    protected function getHeaders(bool $includeToken = false, ?string $token = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
        ];

        if ($includeToken && $token) {
            $headers['Authorization'] = 'Token ' . $token;
        }

        return $headers;
    }

    /**
     * Manejar la respuesta de la API
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $endpoint
     * @return array
     * @throws \Exception
     */
    protected function handleResponse($response, string $endpoint): array
    {
        if ($response->successful()) {
            Log::info('ArcangelService: Request successful', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return $response->json() ?? [];
        }

        // Log del error
        Log::error('ArcangelService: Request failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // Lanzar excepción con mensaje apropiado
        if ($response->status() === 401) {
            throw new \Exception('Arcangel API: No autorizado. Verifica la API Key.');
        }

        if ($response->status() === 404) {
            throw new \Exception('Arcangel API: Endpoint no encontrado.');
        }

        if ($response->status() >= 500) {
            throw new \Exception('Arcangel API: Error del servidor.');
        }

        throw new \Exception('Arcangel API: Error en la petición. Status: ' . $response->status());
    }

    /**
     * Limpiar la caché de un endpoint específico
     *
     * @param string $endpoint
     * @param array $params
     * @return void
     */
    public function clearCache(string $endpoint, array $params = []): void
    {
        $cacheKey = "arcangel_{$endpoint}_" . md5(json_encode($params));
        Cache::forget($cacheKey);
    }

    /**
     * Limpiar toda la caché de Arcangel
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }

    /**
     * Verificar si la API está disponible
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ArcangelService health check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtener información del modo actual
     *
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'mode' => $this->mode,
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retry_times' => $this->retryTimes,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // MÉTODOS ESPECÍFICOS DE ARCANGEL API
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Generar un nuevo token de autenticación
     * El token tiene validez de 1 hora
     * Método: POST
     * Headers requeridos: X-API-KEY
     *
     * @return array ['token' => string, 'expires_at' => string]
     * @throws \Exception
     */
    public function generateToken(): array
    {
        try {
            // Solo enviar X-API-KEY, sin token de autorización
            // NOTA: El manual dice GET pero el API real usa POST
            $headers = [
                'X-API-KEY' => $this->apiKey,
            ];

            $response = Http::timeout(max($this->timeout, 45))
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($headers)
                ->post($this->baseUrl . 'GenerateToken/');

            $data = $this->handleResponse($response, 'GenerateToken');

            // Cachear el token por 55 minutos (5 min antes de expirar)
            // La clave incluye el modo para evitar cruce entre prod/dev
            if (isset($data['token'])) {
                Cache::put("arcangel_auth_token_{$this->mode}", $data['token'], now()->addMinutes(55));
                Cache::put("arcangel_token_expires_at_{$this->mode}", $data['expires_at'] ?? null, now()->addMinutes(55));
                
                Log::info('ArcangelService: Token generado y cacheado exitosamente', [
                    'expires_at' => $data['expires_at'] ?? 'N/A',
                    'token_preview' => substr($data['token'], 0, 10) . '...',
                ]);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('ArcangelService: Error generando token', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);

            throw $e;
        }
    }

    /**
     * Obtener el token de autenticación actual
     * Si no existe o está por expirar, genera uno nuevo
     *
     * @return string
     * @throws \Exception
     */
    public function getToken(): string
    {
        $token = Cache::get("arcangel_auth_token_{$this->mode}");

        if (!$token) {
            $data = $this->generateToken();
            $token = $data['token'];
        }

        return $token;
    }

    /**
     * Obtener listado de ciudades disponibles en Arcangel
     *
     * @param bool $useCache Si debe usar caché
     * @param int $cacheTTL Tiempo de caché en minutos (por defecto 60)
     * @return array
     * @throws \Exception
     */
    public function getCiudades(bool $useCache = false, int $cacheTTL = 60): array
    {
        $cacheKey = "arcangel_ciudades_{$this->mode}";
        $staleCacheKey = "arcangel_ciudades_stale_{$this->mode}";

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('ArcangelService: Ciudades desde caché');
            return Cache::get($cacheKey);
        }

        try {
            $token = $this->getToken();

            $response = Http::timeout(max($this->timeout, 45))
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders(true, $token))
                ->get($this->baseUrl . 'getCiudadesNombres/');

            $data = $this->handleResponse($response, 'getCiudadesNombres');

            // Extraer solo el array de ciudades
            $ciudades = $data['data']['ciudades'] ?? [];

            if ($useCache) {
                Cache::put($cacheKey, $ciudades, now()->addMinutes($cacheTTL));
            }

            Cache::put($staleCacheKey, $ciudades, now()->addDays(7));

            Log::info('ArcangelService: Ciudades obtenidas', [
                'total' => count($ciudades),
            ]);

            return $ciudades;
        } catch (\Exception $e) {
            Log::error('ArcangelService: Error obteniendo ciudades', [
                'error' => $e->getMessage(),
            ]);

            if (Cache::has($staleCacheKey)) {
                Log::warning('ArcangelService: Usando caché stale de ciudades por timeout/fallo');
                return Cache::get($staleCacheKey);
            }

            Log::warning('ArcangelService: Usando lista fallback local de ciudades');
            return self::FALLBACK_CIUDADES;
        }
    }

    /**
     * Obtener vehículos cercanos a una ciudad
     * Método: GET con body (no query string)
     *
     * @param string $ciudad Nombre de la ciudad (debe coincidir con las de getCiudades)
     * @param bool $useCache Si debe usar caché
     * @param int $cacheTTL Tiempo de caché en minutos (por defecto 5)
     * @return array
     * @throws \Exception
     */
    public function getVehiculosCercanos(string $ciudad, bool $useCache = false, int $cacheTTL = 5): array
    {
        $cacheKey = "arcangel_vehiculos_{$this->mode}_" . md5(strtoupper($ciudad));
        $staleCacheKey = $cacheKey . '_stale';

        if ($useCache && Cache::has($cacheKey)) {
            Log::info('ArcangelService: Vehículos desde caché', ['ciudad' => $ciudad]);
            return Cache::get($cacheKey);
        }

        try {
            $token = $this->getToken();

            // Enviar data en el body según manual v1.0
            $body = [
                'data' => [
                    'ciudad' => strtoupper($ciudad),
                ],
            ];

            $response = Http::timeout(max($this->timeout, 45))
                ->retry($this->retryTimes, $this->retryDelay)
                ->withHeaders($this->getHeaders(true, $token))
                ->withBody(json_encode($body), 'application/json')
                ->get($this->baseUrl . 'getVehiculosCercanos/');

            $responseData = $this->handleResponse($response, 'getVehiculosCercanos');

            // Según manual: data.data.vehiculos contiene el array de vehículos
            $vehiculos = $responseData['data']['data']['vehiculos'] ?? [];
            $totalVehiculos = $responseData['data']['data']['total_vehiculos'] ?? count($vehiculos);

            // Retornar en formato consistente
            $result = [
                'vehiculos' => $vehiculos,
                'total_vehiculos' => $totalVehiculos,
                'ciudad' => strtoupper($ciudad),
            ];

            if ($useCache && !empty($vehiculos)) {
                Cache::put($cacheKey, $result, now()->addMinutes($cacheTTL));
            }

            if (!empty($vehiculos)) {
                Cache::put($staleCacheKey, $result, now()->addDays(2));
            }

            Log::info('ArcangelService: Vehículos cercanos obtenidos', [
                'ciudad' => $ciudad,
                'total' => $totalVehiculos,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('ArcangelService: Error obteniendo vehículos cercanos', [
                'ciudad' => $ciudad,
                'error' => $e->getMessage(),
            ]);

            if (Cache::has($staleCacheKey)) {
                Log::warning('ArcangelService: Usando caché stale de vehículos por timeout/fallo', [
                    'ciudad' => $ciudad,
                ]);
                return Cache::get($staleCacheKey);
            }

            return [
                'vehiculos' => [],
                'total_vehiculos' => 0,
                'ciudad' => strtoupper($ciudad),
                'fallback' => true,
            ];
        }
    }

    /**
     * Filtrar vehículos cercanos por tipo de clase
     *
     * @param string $ciudad Ciudad a consultar
     * @param string|array|null $clases Clase(s) de vehículo a filtrar (ej: "TURBO", ["TURBO", "CAMIONETA"])
     * @param int|null $minScore Score mínimo del vehículo
     * @param int|null $limit Límite de resultados
     * @param bool $useCache Usar caché para la consulta
     * @param int $cacheTTL Tiempo de vida del caché en minutos
     * @return array
     */
    public function getVehiculosFiltrados(
        string $ciudad,
        string|array|null $clases = null,
        ?int $minScore = null,
        ?int $limit = null,
        bool $useCache = false,
        int $cacheTTL = 30
    ): array {
        try {
            // Obtener todos los vehículos de la ciudad
            $result = $this->getVehiculosCercanos($ciudad, $useCache, $cacheTTL);
            $vehiculos = $result['vehiculos'] ?? [];

            // Aplicar filtros
            $vehiculosFiltrados = $vehiculos;

            // Filtrar por clase(s)
            if ($clases !== null) {
                $clasesArray = is_array($clases) ? array_map('strtoupper', $clases) : [strtoupper($clases)];
                
                $vehiculosFiltrados = array_filter($vehiculosFiltrados, function ($vehiculo) use ($clasesArray) {
                    return in_array(strtoupper($vehiculo['clase'] ?? ''), $clasesArray);
                });
            }

            // Filtrar por score mínimo
            if ($minScore !== null) {
                $vehiculosFiltrados = array_filter($vehiculosFiltrados, function ($vehiculo) use ($minScore) {
                    return ($vehiculo['score'] ?? 0) >= $minScore;
                });
            }

            // Reindexar array
            $vehiculosFiltrados = array_values($vehiculosFiltrados);

            // Aplicar límite
            if ($limit !== null && $limit > 0) {
                $vehiculosFiltrados = array_slice($vehiculosFiltrados, 0, $limit);
            }

            Log::info('ArcangelService: Vehículos filtrados', [
                'ciudad' => $ciudad,
                'clases' => $clases,
                'min_score' => $minScore,
                'limit' => $limit,
                'total_original' => count($vehiculos),
                'total_filtrado' => count($vehiculosFiltrados),
            ]);

            return [
                'ciudad' => $result['ciudad'] ?? $ciudad,
                'filtros' => [
                    'clases' => $clases,
                    'min_score' => $minScore,
                    'limit' => $limit,
                ],
                'vehiculos' => $vehiculosFiltrados,
                'total_vehiculos' => count($vehiculosFiltrados),
                'total_original' => count($vehiculos),
            ];
        } catch (\Exception $e) {
            Log::error('ArcangelService: Error filtrando vehículos', [
                'ciudad' => $ciudad,
                'clases' => $clases,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener tipos de clases de vehículos disponibles en una ciudad
     *
     * @param string $ciudad
     * @param bool $useCache
     * @param int $cacheTTL
     * @return array
     */
    public function getClasesDisponibles(string $ciudad, bool $useCache = false, int $cacheTTL = 60): array
    {
        try {
            $result = $this->getVehiculosCercanos($ciudad, $useCache, $cacheTTL);
            $vehiculos = $result['vehiculos'] ?? [];

            // Extraer clases únicas y contar vehículos por clase
            $clases = [];
            foreach ($vehiculos as $vehiculo) {
                $clase = $vehiculo['clase'] ?? 'DESCONOCIDO';
                if (!isset($clases[$clase])) {
                    $clases[$clase] = 0;
                }
                $clases[$clase]++;
            }

            // Ordenar por cantidad descendente
            arsort($clases);

            Log::info('ArcangelService: Clases de vehículos disponibles', [
                'ciudad' => $ciudad,
                'total_clases' => count($clases),
            ]);

            return [
                'ciudad' => $result['ciudad'] ?? $ciudad,
                'clases' => $clases,
                'total_clases' => count($clases),
                'total_vehiculos' => count($vehiculos),
            ];
        } catch (\Exception $e) {
            Log::error('ArcangelService: Error obteniendo clases disponibles', [
                'ciudad' => $ciudad,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
