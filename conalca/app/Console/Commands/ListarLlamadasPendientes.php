<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ArcangelService;
use Illuminate\Support\Facades\Log;

class ListarLlamadasPendientes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arcangel:listar-llamadas 
                            {--estado=pendiente : Estado de las llamadas (pendiente, aceptada, rechazada, todas)}
                            {--limite=10 : Número máximo de resultados}
                            {--formato=table : Formato de salida (table, json, simple)}
                            {--sin-cache : No usar caché}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista las llamadas de conductores según el estado especificado';

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
        $estado = $this->option('estado');
        $limite = (int) $this->option('limite');
        $formato = $this->option('formato');
        $sinCache = $this->option('sin-cache');
        $useCache = !$sinCache;

        $this->info("📋 Listando llamadas de conductores");
        $this->line("Estado: " . strtoupper($estado));
        $this->line("Límite: {$limite}");
        $this->newLine();

        try {
            // Preparar parámetros de consulta
            $params = [
                'limite' => $limite,
            ];

            if ($estado !== 'todas') {
                $params['estado'] = $estado;
            }

            // Consultar llamadas
            $this->line('⏳ Consultando llamadas...');
            $response = $this->arcangel->get('llamadas/conductores', $params, $useCache, 5);

            if (empty($response)) {
                $this->warn("No se encontraron llamadas con estado: {$estado}");
                return Command::SUCCESS;
            }

            // Mostrar resultados
            $this->mostrarResultados($response, $formato, $estado);

            $this->newLine();
            $this->info('✅ Consulta completada');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al consultar las llamadas');
            $this->error("Mensaje: {$e->getMessage()}");
            
            Log::error('Error en comando arcangel:listar-llamadas', [
                'estado' => $estado,
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
     * @param string $estado
     * @return void
     */
    protected function mostrarResultados($response, $formato, $estado)
    {
        switch ($formato) {
            case 'json':
                $this->mostrarJson($response);
                break;
            
            case 'simple':
                $this->mostrarSimple($response);
                break;
            
            case 'table':
            default:
                $this->mostrarTabla($response, $estado);
                break;
        }
    }

    /**
     * Mostrar resultados en formato tabla
     *
     * @param mixed $response
     * @param string $estado
     * @return void
     */
    protected function mostrarTabla($response, $estado)
    {
        $llamadas = $this->extraerLlamadas($response);

        if (empty($llamadas)) {
            $this->warn('No hay llamadas disponibles');
            return;
        }

        $headers = ['ID', 'Origen', 'Destino', 'Peso (kg)', 'Vehículo', 'Estado', 'Fecha'];
        $rows = [];

        foreach ($llamadas as $llamada) {
            $rows[] = [
                $llamada['id'] ?? 'N/A',
                $llamada['ciudad_origen'] ?? $llamada['origin'] ?? 'N/A',
                $llamada['ciudad_destino'] ?? $llamada['destination'] ?? 'N/A',
                $llamada['peso_mercancia'] ?? $llamada['weight'] ?? 'N/A',
                $llamada['vehiculo_requerido'] ?? $llamada['vehicle'] ?? 'N/A',
                $this->formatearEstado($llamada['estado'] ?? $llamada['status'] ?? 'N/A'),
                $this->formatearFecha($llamada['fecha_registro'] ?? $llamada['created_at'] ?? 'N/A'),
            ];
        }

        $this->newLine();
        $this->table($headers, $rows);
        $this->info("Total: " . count($rows) . " llamada(s) encontrada(s)");
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
     * @return void
     */
    protected function mostrarSimple($response)
    {
        $llamadas = $this->extraerLlamadas($response);

        if (empty($llamadas)) {
            $this->warn('No hay llamadas disponibles');
            return;
        }

        foreach ($llamadas as $index => $llamada) {
            $numero = $index + 1;
            $id = $llamada['id'] ?? 'N/A';
            $origen = $llamada['ciudad_origen'] ?? $llamada['origin'] ?? 'N/A';
            $destino = $llamada['ciudad_destino'] ?? $llamada['destination'] ?? 'N/A';
            $estado = $llamada['estado'] ?? $llamada['status'] ?? 'N/A';
            
            $this->line("{$numero}. [{$id}] {$origen} → {$destino} ({$estado})");
        }

        $this->newLine();
        $this->info("Total: " . count($llamadas) . " llamada(s)");
    }

    /**
     * Extraer array de llamadas de la respuesta
     *
     * @param mixed $response
     * @return array
     */
    protected function extraerLlamadas($response)
    {
        if (is_array($response)) {
            if (isset($response['data']) && is_array($response['data'])) {
                return $response['data'];
            }
            
            // Si la respuesta es directamente un array de llamadas
            if (isset($response[0])) {
                return $response;
            }
        }

        return [];
    }

    /**
     * Formatear estado con colores
     *
     * @param string $estado
     * @return string
     */
    protected function formatearEstado($estado)
    {
        $estados = [
            'pendiente' => '🟡',
            'aceptada' => '🟢',
            'rechazada' => '🔴',
            'en_proceso' => '🔵',
        ];

        $icono = $estados[$estado] ?? '⚪';
        return "{$icono} {$estado}";
    }

    /**
     * Formatear fecha
     *
     * @param string $fecha
     * @return string
     */
    protected function formatearFecha($fecha)
    {
        if ($fecha === 'N/A') {
            return $fecha;
        }

        try {
            return \Carbon\Carbon::parse($fecha)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $fecha;
        }
    }
}
