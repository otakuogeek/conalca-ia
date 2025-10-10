<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Services\ElevenLabsCallService;
use App\Models\VehicleOwnerHolderDriver;
use App\Models\CotizacionModel;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Process pending calls in the queue using ElevenLabs SIP trunk';

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
            // Obtener servicio ElevenLabs
            $elevenLabsCallService = app(ElevenLabsCallService::class);

            // Buscar llamadas pendientes
            $pendingCalls = Llamada::where('status', Llamada::STATUS_PENDIENTE)
                ->whereNull('elevenlabs_conversation_id')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            if ($pendingCalls->isEmpty()) {
                $this->info("✅ No hay llamadas pendientes en la cola");
                return 0;
            }

            $this->info("📞 Encontradas {$pendingCalls->count()} llamadas pendientes");
            $this->line('');

            $processed = 0;
            $successful = 0;
            $failed = 0;

            foreach ($pendingCalls as $llamada) {
                $processed++;
                
                $this->info("Procesando llamada {$processed}/{$pendingCalls->count()}:");
                $this->line("  ID: {$llamada->id_llamada}");
                $this->line("  Teléfono: {$llamada->numero_destino}");
                $this->line("  Cotización: {$llamada->id_cotizacion}");
                $this->line("  Conductor: {$llamada->chofer_id}");

                if ($dryRun) {
                    $this->warn("  [SIMULACIÓN] Llamada no realizada");
                    $this->line('');
                    continue;
                }

                try {
                    // Obtener datos del conductor y cotización
                    $driver = VehicleOwnerHolderDriver::find($llamada->chofer_id);
                    $cotizacion = CotizacionModel::find($llamada->id_cotizacion);

                    if (!$driver || !$cotizacion) {
                        throw new \Exception('Driver o cotización no encontrados');
                    }

                    // Preparar datos del cliente
                    $clientData = [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->Conductor,
                        'cotizacion_id' => $cotizacion->id,
                        'cotizacion' => [
                            'origen' => $cotizacion->ciudad_origen,
                            'destino' => $cotizacion->ciudad_destino,
                            'vehiculo' => $cotizacion->vehiculo_requerido
                        ]
                    ];

                    // Realizar llamada
                    $result = $elevenLabsCallService->makeDirectSipCall($llamada->numero_destino, $clientData);

                    if ($result['success']) {
                        // Actualizar llamada
                        $llamada->update([
                            'status' => Llamada::STATUS_EN_CURSO,
                            'call_status' => 'initiated',
                            'elevenlabs_conversation_id' => $result['conversation_id'],
                            'elevenlabs_sip_call_id' => $result['sip_call_id'] ?? null,
                            'call_initiated_at' => now(),
                            'call_notes' => 'Llamada iniciada desde cola - ' . ($result['message'] ?? 'Sin mensaje')
                        ]);

                        $this->info("  ✅ Llamada iniciada exitosamente");
                        $this->line("     Conversation ID: {$result['conversation_id']}");
                        if (!empty($result['sip_call_id'])) {
                            $this->line("     SIP Call ID: {$result['sip_call_id']}");
                        }
                        
                        $successful++;
                    } else {
                        throw new \Exception($result['error'] ?? 'Error desconocido');
                    }

                } catch (\Exception $e) {
                    $llamada->update([
                        'status' => Llamada::STATUS_FALLIDA,
                        'call_status' => 'failed',
                        'failure_reason' => substr($e->getMessage(), 0, 190),
                        'call_completed_at' => now(),
                        'call_notes' => 'Error procesando desde cola: ' . $e->getMessage()
                    ]);

                    $this->error("  ❌ Error: " . $e->getMessage());
                    $failed++;
                }

                $this->line('');
                
                // Pausa entre llamadas para no saturar
                if ($processed < $pendingCalls->count()) {
                    $this->info("  ⏳ Esperando 5 segundos antes de la siguiente llamada...");
                    sleep(5);
                }
            }

            $this->line('');
            $this->info("=== Resumen del procesamiento ===");
            $this->line("Llamadas procesadas: {$processed}");
            $this->line("Exitosas: {$successful}");
            $this->line("Fallidas: {$failed}");
            
            if ($successful > 0) {
                $successRate = round(($successful / $processed) * 100, 1);
                $this->info("Tasa de éxito: {$successRate}%");
            }

            return $failed > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("❌ Error general: " . $e->getMessage());
            return 1;
        }
    }
}
