<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Services\CallQueueManager;

class ProcessCallQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elevenlabs:process-queue {--limit=10 : Maximum number of calls to process} {--dry-run : Simulate without making real calls}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha llamadas pendientes usando el CallQueueManager centralizado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        
        $this->info("=== Procesando cola de llamadas ElevenLabs ===");
        $this->info("Límite: {$limit} llamadas");
        $this->info("Modo: " . ($dryRun ? 'SIMULACIÓN' : 'REAL'));
        $this->line('');

        try {
            $pendingCalls = Llamada::where('status', Llamada::STATUS_PENDIENTE)
                ->where('queue_status', 'pending')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            if ($pendingCalls->isEmpty()) {
                $this->info("✅ No hay llamadas pendientes en la cola");
                return 0;
            }

            $this->info("📞 Encontradas {$pendingCalls->count()} llamadas pendientes");
            $this->line('');

            if ($dryRun) {
                foreach ($pendingCalls as $index => $llamada) {
                    $this->line(sprintf(
                        '  #%d llamada %s | cotización %s | teléfono %s',
                        $index + 1,
                        $llamada->id_llamada,
                        $llamada->id_cotizacion,
                        $llamada->numero_destino
                    ));
                }

                return 0;
            }

            $result = CallQueueManager::dispatchNextCalls();
            $dispatched = $result['dispatched'] ?? 0;

            if ($dispatched > 0) {
                $this->info("✅ {$dispatched} llamada(s) despachada(s) por la cola central");
            } else {
                $this->warn('No se despacharon llamadas: ' . ($result['reason'] ?? 'sin slots o sin pendientes procesables'));
            }

            $this->line('');
            $this->info('Estado de cola:');
            $this->line(json_encode(CallQueueManager::getQueueStatus(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error general: " . $e->getMessage());
            return 1;
        }
    }
}
