<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Services\CallQueueService;
use Illuminate\Support\Facades\Log;

class EnqueuePendingCallsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:enqueue-pending {--priority=5 : Prioridad para las llamadas (1-10)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encolar llamadas pendientes al sistema de gestión de cola';

    private CallQueueService $queueService;

    public function __construct(CallQueueService $queueService)
    {
        parent::__construct();
        $this->queueService = $queueService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 ENCOLANDO LLAMADAS PENDIENTES');
        $this->line('=' . str_repeat('=', 60));

        $priority = (int) $this->option('priority');
        
        // Obtener llamadas pendientes
        $pendingCalls = Llamada::where('queue_status', 'pending')
            ->orWhereNull('queue_status')
            ->get();

        if ($pendingCalls->isEmpty()) {
            $this->info('✅ No hay llamadas pendientes para encolar');
            return 0;
        }

        $this->info("📊 Se encontraron {$pendingCalls->count()} llamadas pendientes");
        $this->line('');

        $enqueued = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($pendingCalls->count());
        $progressBar->start();

        foreach ($pendingCalls as $llamada) {
            if ($this->queueService->enqueueCall($llamada, $priority)) {
                $enqueued++;
            } else {
                $failed++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        $this->info("✅ Proceso completado:");
        $this->line("   Encoladas exitosamente: {$enqueued}");
        if ($failed > 0) {
            $this->warn("   Fallidas: {$failed}");
        }

        // Mostrar estadísticas actualizadas
        $this->line('');
        $this->info('📊 Estadísticas actualizadas:');
        $stats = $this->queueService->getQueueStats();
        
        $headers = ['Estado', 'Cantidad'];
        $rows = [
            ['🕐 Pendientes', $stats['pending']],
            ['📋 En cola', $stats['queued']],
            ['🔄 Procesando', $stats['processing']],
        ];

        $this->table($headers, $rows);

        $this->line('');
        $this->info('💡 Siguiente paso: php artisan calls:process-queue --continuous');

        return 0;
    }
}
