<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;

class ConsultarLocalidadArcangel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arcangel:consultar-localidad 
                            {ciudad : Nombre de la ciudad a consultar}
                            {--formato=table : Formato de salida (table, json, simple)}
                            {--cache : Usar caché para la consulta}
                            {--cache-time=60 : Tiempo de caché en minutos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta la localidad de una ciudad usando la API de Arcángel';

    /**
     * Arcangel Service instance
     *
     * @var ArcangelService
     */
    protected $arcangel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ArcangelService $arcangel)
    {
        parent::__construct();
        $this->arcangel = $arcangel;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ciudad = $this->argument('ciudad');
        $formato = $this->option('formato');
        $useCache = $this->option('cache');
        $cacheTime = (int) $this->option('cache-time');

        $this->info("🔍 Buscando ciudad: {$ciudad}");
        
        if ($useCache) {
            $this->line("📦 Usando caché (tiempo: {$cacheTime} minutos)");
        }

        $this->line('⏳ Procesando...');
        $this->newLine();

        try {
            // Obtener todas las ciudades disponibles de Arcángel
            $ciudades = $this->arcangel->getCiudades($useCache, $cacheTime);

            // Verificar si hay resultados
            if (empty($ciudades)) {
                $this->error("❌ No se pudieron obtener las ciudades de Arcángel");
                return Command::FAILURE;
            }

            // Buscar la ciudad solicitada (búsqueda flexible sin acentos)
            $ciudadBuscada = $this->normalizarTexto($ciudad);
            $ciudadesEncontradas = array_filter($ciudades, function($c) use ($ciudadBuscada) {
                return stripos($this->normalizarTexto($c), $ciudadBuscada) !== false;
            });

            if (empty($ciudadesEncontradas)) {
                $this->error("❌ No se encontró la ciudad: {$ciudad}");
                $this->newLine();
                $this->line("💡 Ciudades disponibles:");
                $this->mostrarCiudadesDisponibles($ciudades);
                return Command::FAILURE;
            }

            // Preparar respuesta simulando estructura de localidades
            $response = [
                'data' => array_map(function($nombreCiudad, $index) {
                    return [
                        'id' => $index + 1,
                        'nombre' => $nombreCiudad,
                        'codigo' => 'N/A',
                        'departamento' => 'Colombia',
                        'pais' => 'Colombia',
                    ];
                }, array_values($ciudadesEncontradas), array_keys($ciudadesEncontradas))
            ];

            // Mostrar resultados según el formato solicitado
            $this->mostrarResultados($response, $formato, $ciudad);

            $this->newLine();
            $this->info('✅ Consulta completada exitosamente');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al consultar la API de Arcángel');
            $this->error("Mensaje: {$e->getMessage()}");
            
            Log::error('Error en comando arcangel:consultar-localidad', [
                'ciudad' => $ciudad,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Mostrar resultados según el formato especificado
     *
     * @param mixed $response
     * @param string $formato
     * @param string $ciudad
     * @return void
     */
    protected function mostrarResultados($response, $formato, $ciudad)
    {
        switch ($formato) {
            case 'json':
                $this->mostrarJson($response);
                break;
            
            case 'simple':
                $this->mostrarSimple($response, $ciudad);
                break;
            
            case 'table':
            default:
                $this->mostrarTabla($response, $ciudad);
                break;
        }
    }

    /**
     * Mostrar resultados en formato tabla
     *
     * @param mixed $response
     * @param string $ciudad
     * @return void
     */
    protected function mostrarTabla($response, $ciudad)
    {
        $this->info("📍 Resultados para: {$ciudad}");
        $this->newLine();

        // Si la respuesta es un array de localidades
        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
            $localidades = $response['data'];
            
            if (empty($localidades)) {
                $this->warn('No hay localidades disponibles');
                return;
            }

            // Preparar datos para la tabla
            $headers = ['ID', 'Nombre', 'Código', 'Departamento', 'País'];
            $rows = [];

            foreach ($localidades as $localidad) {
                $rows[] = [
                    $localidad['id'] ?? 'N/A',
                    $localidad['nombre'] ?? $localidad['name'] ?? 'N/A',
                    $localidad['codigo'] ?? $localidad['code'] ?? 'N/A',
                    $localidad['departamento'] ?? $localidad['department'] ?? 'N/A',
                    $localidad['pais'] ?? $localidad['country'] ?? 'Colombia',
                ];
            }

            $this->table($headers, $rows);
            $this->info("Total: " . count($rows) . " localidad(es) encontrada(s)");
            
        } elseif (is_array($response)) {
            // Si es un array simple, intentar mostrar como tabla
            $headers = ['Campo', 'Valor'];
            $rows = [];
            
            foreach ($response as $key => $value) {
                if (is_scalar($value)) {
                    $rows[] = [$key, $value];
                }
            }
            
            if (!empty($rows)) {
                $this->table($headers, $rows);
            } else {
                $this->warn('No se pudo mostrar la información en formato tabla');
                $this->mostrarJson($response);
            }
        } else {
            $this->warn('Formato de respuesta no reconocido');
            $this->mostrarJson($response);
        }
    }

    /**
     * Mostrar resultados en formato JSON
     *
     * @param mixed $response
     * @return void
     */
    protected function mostrarJson($response)
    {
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Mostrar resultados en formato simple
     *
     * @param mixed $response
     * @param string $ciudad
     * @return void
     */
    protected function mostrarSimple($response, $ciudad)
    {
        $this->info("Resultados para: {$ciudad}");
        $this->newLine();

        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
            $localidades = $response['data'];
            
            foreach ($localidades as $index => $localidad) {
                $numero = $index + 1;
                $nombre = $localidad['nombre'] ?? $localidad['name'] ?? 'N/A';
                $codigo = $localidad['codigo'] ?? $localidad['code'] ?? 'N/A';
                
                $this->line("{$numero}. {$nombre} (Código: {$codigo})");
            }
            
            $this->newLine();
            $this->info("Total: " . count($localidades) . " ciudad(es)");
        } else {
            $this->warn('No se pudo procesar la respuesta');
            $this->mostrarJson($response);
        }
    }

    /**
     * Mostrar lista de ciudades disponibles
     *
     * @param array $ciudades
     * @return void
     */
    protected function mostrarCiudadesDisponibles(array $ciudades)
    {
        $total = count($ciudades);
        $mostrar = array_slice($ciudades, 0, 20);
        
        foreach ($mostrar as $ciudad) {
            $this->line("   • {$ciudad}");
        }
        
        if ($total > 20) {
            $this->line("   ... y " . ($total - 20) . " más");
        }
        
        $this->newLine();
        $this->line("Total de ciudades disponibles: {$total}");
    }

    /**
     * Normalizar texto removiendo acentos y caracteres especiales
     *
     * @param string $texto
     * @return string
     */
    protected function normalizarTexto(string $texto): string
    {
        $texto = strtoupper($texto);
        $acentos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ñ' => 'N', 'ñ' => 'n'
        ];
        return strtr($texto, $acentos);
    }
}
