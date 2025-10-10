<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CallQueueService;
use App\Services\ElevenLabsService;
use Illuminate\Support\Facades\Log;

class ProcessCallQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:process-queue 
                            {--continuous : Procesar continuamente en loop}
                            {--stats : Mostrar solo estadísticas}
                            {--clean : Limpiar llamadas atascadas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar cola de llamadas con límite de 3 concurrentes y retraso de 90 segundos';

    private CallQueueService $queueService;
    private ElevenLabsService $elevenLabsService;

    public function __construct(CallQueueService $queueService, ElevenLabsService $elevenLabsService)
    {
        parent::__construct();
        $this->queueService = $queueService;
        $this->elevenLabsService = $elevenLabsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 PROCESADOR DE COLA DE LLAMADAS');
        $this->line('=' . str_repeat('=', 60));

        // Si solo quiere ver estadísticas
        if ($this->option('stats')) {
            $this->showStats();
            return 0;
        }

        // Si quiere limpiar llamadas atascadas
        if ($this->option('clean')) {
            $this->cleanStuckCalls();
            return 0;
        }

        // Modo continuo
        if ($this->option('continuous')) {
            $this->info('🔁 Modo continuo activado. Presione Ctrl+C para detener.');
            $this->processContinuously();
            return 0;
        }

        // Procesar un solo lote
        $this->processSingleBatch();
        return 0;
    }

    private function processSingleBatch()
    {
        $this->info('📊 Verificando cola de llamadas...');
        
        // Mostrar estadísticas actuales
        $stats = $this->queueService->getQueueStats();
        $this->showStatsTable($stats);

        // Limpiar llamadas atascadas primero
        $cleaned = $this->queueService->cleanStuckCalls();
        if ($cleaned > 0) {
            $this->warn("⚠️  Se limpiaron {$cleaned} llamadas atascadas");
        }

        // Intentar procesar el siguiente lote
        $this->info('🚀 Procesando siguiente lote...');
        $result = $this->queueService->processNextBatch();

        if (!$result['success']) {
            $this->warn("⏸️  {$result['message']}");
            if (isset($result['wait_seconds'])) {
                $this->line("   Tiempo de espera: {$result['wait_seconds']} segundos");
            }
            return;
        }

        // Realizar las llamadas
        $this->info("✅ Lote #{$result['batch_number']} listo con {$result['calls']->count()} llamadas");
        $this->line('');

        foreach ($result['calls_data'] as $index => $llamada) {
            $callNumber = $index + 1;
            $totalCalls = $result['calls']->count();
            $this->info("📞 Llamada {$callNumber}/{$totalCalls}: {$llamada->numero_destino}");
            
            try {
                // Realizar la llamada usando ElevenLabs
                $callResult = $this->elevenLabsService->initiateCall($llamada->numero_destino, [
                    'llamada_id' => $llamada->id_llamada,
                    'cotizacion_id' => $llamada->id_cotizacion,
                    'chofer_id' => $llamada->chofer_id
                ]);

                if ($callResult['success']) {
                    $this->line("   ✅ Llamada iniciada exitosamente");
                    $this->queueService->completeCall($llamada->id_llamada, true);
                } else {
                    $this->error("   ❌ Error: " . ($callResult['message'] ?? 'Error desconocido'));
                    $this->queueService->completeCall($llamada->id_llamada, false);
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Excepción: " . $e->getMessage());
                $this->queueService->completeCall($llamada->id_llamada, false);
            }

            // Pequeño delay entre llamadas del mismo lote
            if ($index < $result['calls']->count() - 1) {
                sleep(2);
            }
        }

        $this->line('');
        $this->info('✅ Lote procesado completamente');
        
        // Mostrar estadísticas actualizadas
        $this->line('');
        $this->info('📊 Estadísticas actualizadas:');
        $stats = $this->queueService->getQueueStats();
        $this->showStatsTable($stats);
    }

    private function processContinuously()
    {
        $iteration = 0;
        
        while (true) {
            $iteration++;
            $this->line('');
            $this->info("🔄 Iteración #{$iteration} - " . now()->format('H:i:s'));
            $this->line(str_repeat('-', 60));

            try {
                $this->processSingleBatch();
            } catch (\Exception $e) {
                $this->error("❌ Error en iteración #{$iteration}: " . $e->getMessage());
                Log::error("Error en procesador continuo de cola: " . $e->getMessage());
            }

            // Esperar antes de la siguiente iteración
            $this->line('');
            $this->info('⏳ Esperando 30 segundos antes de la siguiente iteración...');
            sleep(30);
        }
    }

    private function showStats()
    {
        $this->info('📊 ESTADÍSTICAS DE LA COLA DE LLAMADAS');
        $this->line('=' . str_repeat('=', 60));

        $stats = $this->queueService->getQueueStats();
        $this->showStatsTable($stats);

        $this->line('');
        $this->info('💡 Comandos disponibles:');
        $this->line('   php artisan calls:process-queue              # Procesar un lote');
        $this->line('   php artisan calls:process-queue --continuous # Procesar continuamente');
        $this->line('   php artisan calls:process-queue --clean      # Limpiar llamadas atascadas');
        $this->line('   php artisan calls:process-queue --stats      # Ver estadísticas');
    }

    private function showStatsTable(array $stats)
    {
        $headers = ['Estado', 'Cantidad'];
        $rows = [
            ['🕐 Pendientes', $stats['pending']],
            ['📋 En cola', $stats['queued']],
            ['🔄 Procesando', $stats['processing']],
            ['✅ Completadas (24h)', $stats['completed']],
            ['❌ Fallidas (24h)', $stats['failed']],
            ['📞 Llamadas activas', $stats['active_calls']],
        ];

        $this->table($headers, $rows);

        $this->line('');
        $canProcess = $stats['can_process'] ? '✅ Sí' : '❌ No';
        $this->line("¿Puede procesar nuevo lote? {$canProcess}");
        
        if (!$stats['can_process'] && $stats['wait_time'] > 0) {
            $this->line("Tiempo de espera: {$stats['wait_time']} segundos");
        }

        if ($stats['next_batch']) {
            $this->line("Próximo lote: #{$stats['next_batch']}");
        }
    }

    private function cleanStuckCalls()
    {
        $this->info('🧹 Limpiando llamadas atascadas...');
        
        $cleaned = $this->queueService->cleanStuckCalls();
        
        if ($cleaned > 0) {
            $this->info("✅ Se limpiaron {$cleaned} llamadas atascadas");
        } else {
            $this->info('✅ No hay llamadas atascadas');
        }

        // Mostrar estadísticas después de limpiar
        $this->line('');
        $stats = $this->queueService->getQueueStats();
        $this->showStatsTable($stats);
    }
}
