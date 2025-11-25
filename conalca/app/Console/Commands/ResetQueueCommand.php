<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:reset-queue 
                            {--dry-run : Ver qué se va a hacer sin ejecutar}
                            {--status=completed : Estado a asignar (completed, cancelled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marcar todas las llamadas actuales como procesadas para comenzar con el nuevo sistema de cola';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 RESETEAR COLA DE LLAMADAS');
        $this->line('=' . str_repeat('=', 60));

        $dryRun = $this->option('dry-run');
        $newStatus = $this->option('status');

        // Validar estado
        $validStatuses = ['completed', 'cancelled', 'failed'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->error("Estado inválido. Use: completed, cancelled, o failed");
            return 1;
        }

        // Obtener llamadas en cola o procesando
        $llamadas = Llamada::whereIn('queue_status', ['queued', 'processing', 'pending'])
            ->get();

        if ($llamadas->isEmpty()) {
            $this->info('✅ No hay llamadas en cola para procesar');
            return 0;
        }

        $this->warn("⚠️  Se encontraron {$llamadas->count()} llamadas en cola");
        $this->line('');

        // Mostrar desglose por estado
        $byStatus = $llamadas->groupBy('queue_status');
        $this->table(
            ['Estado Actual', 'Cantidad'],
            $byStatus->map(function($items, $status) {
                return [$status, $items->count()];
            })->values()
        );

        $this->line('');

        if ($dryRun) {
            $this->info('🔍 MODO DRY-RUN: No se realizarán cambios');
            $this->line('');
            $this->info("Acción a realizar:");
            $this->line("  • Marcar {$llamadas->count()} llamadas como '{$newStatus}'");
            $this->line("  • Establecer processing_completed_at a ahora");
            $this->line("  • Agregar nota interna sobre el reseteo");
            $this->line('');
            $this->info("Para ejecutar realmente, ejecute sin --dry-run:");
            $this->line("  php artisan calls:reset-queue");
            return 0;
        }

        // Confirmar acción
        if (!$this->option('no-interaction') && !$this->confirm("¿Desea marcar estas {$llamadas->count()} llamadas como '{$newStatus}'?")) {
            $this->warn('❌ Operación cancelada por el usuario');
            return 0;
        }

        // Procesar llamadas
        $this->info('');
        $this->info('📝 Procesando llamadas...');
        $progressBar = $this->output->createProgressBar($llamadas->count());
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($llamadas as $llamada) {
                try {
                    $oldStatus = $llamada->queue_status;
                    
                    $llamada->update([
                        'queue_status' => $newStatus,
                        'processing_completed_at' => Carbon::now(),
                        'internal_notes' => ($llamada->internal_notes ?? '') . 
                            "\n[" . Carbon::now() . "] Cola reseteada: {$oldStatus} → {$newStatus} (reseteo manual para nuevo sistema)"
                    ]);
                    
                    $updated++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error en llamada #{$llamada->id_llamada}: " . $e->getMessage());
                }
                $progressBar->advance();
            }

            DB::commit();
            $progressBar->finish();
            $this->newLine(2);

            if ($errors === 0) {
                $this->info("✅ Todas las llamadas actualizadas exitosamente");
            } else {
                $this->warn("⚠️  Se actualizaron {$updated} llamadas con {$errors} errores");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();
            $this->newLine(2);
            $this->error("❌ Error durante el proceso: " . $e->getMessage());
            return 1;
        }

        // Mostrar resumen final
        $this->line('');
        $this->info('📊 RESUMEN FINAL:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Llamadas procesadas', $updated],
                ['Errores', $errors],
                ['Nuevo estado', $newStatus],
                ['Timestamp', Carbon::now()->format('Y-m-d H:i:s')]
            ]
        );

        // Verificar estado actual de la cola
        $this->line('');
        $this->info('🔍 Estado actual de la cola:');
        
        $stats = DB::table('llamadas')
            ->select('queue_status', DB::raw('COUNT(*) as total'))
            ->groupBy('queue_status')
            ->get();

        $this->table(
            ['Estado', 'Cantidad'],
            $stats->map(function($stat) {
                return [$stat->queue_status ?? 'NULL', $stat->total];
            })
        );

        $this->line('');
        $this->info('✅ Cola reseteada exitosamente');
        $this->info('💡 Las próximas llamadas usarán el nuevo sistema de cola automáticamente');
        $this->line('');
        $this->info('Próximos pasos:');
        $this->line('  1. Crear nuevas llamadas: POST /api/call-drivers');
        $this->line('  2. Procesarlas: php artisan calls:process-queue --continuous');

        return 0;
    }
}
