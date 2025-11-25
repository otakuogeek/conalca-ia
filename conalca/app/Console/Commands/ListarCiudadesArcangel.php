<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;

class ListarCiudadesArcangel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arcangel:listar-ciudades 
                            {--filtro= : Filtrar ciudades por texto}
                            {--formato=table : Formato de salida (table, list, json)}
                            {--sin-cache : No usar caché}
                            {--por-pagina=50 : Número de resultados por página}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista todas las ciudades disponibles en Arcángel';

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
        $filtro = $this->option('filtro');
        $formato = $this->option('formato');
        $sinCache = $this->option('sin-cache');
        $porPagina = (int) $this->option('por-pagina');
        $useCache = !$sinCache;

        $this->info("🏙️  Obteniendo ciudades disponibles de Arcángel");
        
        if ($filtro) {
            $this->line("🔍 Filtro: {$filtro}");
        }
        
        $this->newLine();

        try {
            // Obtener todas las ciudades
            $this->line('⏳ Consultando...');
            $ciudades = $this->arcangel->getCiudades($useCache);

            if (empty($ciudades)) {
                $this->warn("No se encontraron ciudades");
                return Command::SUCCESS;
            }

            // Aplicar filtro si existe
            if ($filtro) {
                $filtroNormalizado = $this->normalizarTexto($filtro);
                $ciudades = array_filter($ciudades, function($ciudad) use ($filtroNormalizado) {
                    return stripos($this->normalizarTexto($ciudad), $filtroNormalizado) !== false;
                });
                $ciudades = array_values($ciudades); // Reindexar
            }

            if (empty($ciudades)) {
                $this->warn("No se encontraron ciudades con el filtro: {$filtro}");
                return Command::SUCCESS;
            }

            // Ordenar alfabéticamente
            sort($ciudades);

            // Mostrar según formato
            $this->mostrarCiudades($ciudades, $formato, $porPagina);

            $this->newLine();
            $this->info('✅ Consulta completada');
            $this->line("Total de ciudades: " . count($ciudades));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al obtener las ciudades');
            $this->error("Mensaje: {$e->getMessage()}");
            
            Log::error('Error en comando arcangel:listar-ciudades', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Mostrar ciudades según formato
     *
     * @param array $ciudades
     * @param string $formato
     * @param int $porPagina
     * @return void
     */
    protected function mostrarCiudades(array $ciudades, string $formato, int $porPagina)
    {
        switch ($formato) {
            case 'json':
                $this->mostrarJson($ciudades);
                break;
            
            case 'list':
                $this->mostrarLista($ciudades);
                break;
            
            case 'table':
            default:
                $this->mostrarTabla($ciudades, $porPagina);
                break;
        }
    }

    /**
     * Mostrar ciudades en formato tabla
     *
     * @param array $ciudades
     * @param int $porPagina
     * @return void
     */
    protected function mostrarTabla(array $ciudades, int $porPagina)
    {
        $total = count($ciudades);
        $paginas = ceil($total / $porPagina);
        
        $this->newLine();
        $this->info("📋 Lista de ciudades disponibles ({$total} total)");
        $this->newLine();

        for ($pagina = 1; $pagina <= $paginas; $pagina++) {
            $inicio = ($pagina - 1) * $porPagina;
            $ciudadesPagina = array_slice($ciudades, $inicio, $porPagina);
            
            $headers = ['#', 'Ciudad'];
            $rows = [];
            
            foreach ($ciudadesPagina as $index => $ciudad) {
                $numero = $inicio + $index + 1;
                $rows[] = [$numero, $ciudad];
            }
            
            if ($paginas > 1) {
                $this->line("--- Página {$pagina} de {$paginas} ---");
            }
            
            $this->table($headers, $rows);
            
            // Preguntar si continuar si hay más páginas
            if ($pagina < $paginas && !$this->confirm("¿Mostrar siguiente página?", true)) {
                $this->line("... " . ($total - ($inicio + $porPagina)) . " ciudades más");
                break;
            }
        }
    }

    /**
     * Mostrar ciudades en formato lista
     *
     * @param array $ciudades
     * @return void
     */
    protected function mostrarLista(array $ciudades)
    {
        $this->newLine();
        
        $columnas = 3;
        $chunks = array_chunk($ciudades, $columnas);
        
        foreach ($chunks as $chunk) {
            $linea = '';
            foreach ($chunk as $ciudad) {
                $linea .= sprintf('%-30s', "• {$ciudad}");
            }
            $this->line($linea);
        }
    }

    /**
     * Mostrar ciudades en formato JSON
     *
     * @param array $ciudades
     * @return void
     */
    protected function mostrarJson(array $ciudades)
    {
        $this->line(json_encode([
            'total' => count($ciudades),
            'ciudades' => $ciudades
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Normalizar texto removiendo acentos
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
