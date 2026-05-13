<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\SilogtranService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncClientStatus extends Command
{
    protected $signature = 'clients:sync-status
                            {--dry-run : Muestra cambios sin aplicarlos}
                            {--chunk=50 : Cantidad de clientes por lote}';

    protected $description = 'Sincroniza el estado de los clientes locales con Silogtran (consultarCliente)';

    public function handle(SilogtranService $silog): int
    {
        $dryRun  = $this->option('dry-run');
        $chunk   = (int) $this->option('chunk');

        $total      = Client::count();
        $updated    = 0;
        $unchanged  = 0;
        $notFound   = 0;
        $errors     = 0;

        $this->info("Sincronizando {$total} clientes con Silogtran" . ($dryRun ? ' (DRY-RUN)' : '') . '...');
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Client::select(['id', 'documento', 'cliente', 'estado'])
            ->whereNotNull('documento')
            ->where('documento', '!=', '')
            ->chunkById($chunk, function ($clients) use ($silog, $dryRun, &$updated, &$unchanged, &$notFound, &$errors, $bar) {
                foreach ($clients as $client) {
                    try {
                        $doc = preg_replace('/\s+/', '', $client->documento);
                        if (empty($doc)) {
                            $notFound++;
                            $bar->advance();
                            continue;
                        }

                        $result = $silog->consultarCliente($doc);

                        if (($result['success'] ?? false) && !empty($result['data'])) {
                            $first       = array_values($result['data'])[0];
                            $silogEstado = strtoupper(trim($first['estado_cliente'] ?? ''));

                            if ($silogEstado && strtoupper(trim($client->estado ?? '')) !== $silogEstado) {
                                if (!$dryRun) {
                                    $client->update(['estado' => $silogEstado]);
                                }
                                $updated++;
                            } else {
                                $unchanged++;
                            }
                        } else {
                            $notFound++;
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::warning('[SyncClientStatus] Error con cliente ' . $client->documento, [
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $bar->advance();

                    // Pequeña pausa para no saturar la API
                    usleep(100_000); // 100ms
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total procesados', $updated + $unchanged + $notFound + $errors],
                ['Actualizados',     $updated],
                ['Sin cambio',       $unchanged],
                ['No encontrados',   $notFound],
                ['Errores',          $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY-RUN: No se aplicaron cambios. Ejecuta sin --dry-run para sincronizar.');
        } else {
            $this->info("Sincronización completada. {$updated} clientes actualizados.");
            Log::info('[SyncClientStatus] Sincronización completada', compact('updated', 'unchanged', 'notFound', 'errors'));
        }

        return self::SUCCESS;
    }
}
