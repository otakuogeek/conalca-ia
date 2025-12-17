<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LlamadaConductor;
use App\Models\DriverCallResponse;
use Illuminate\Support\Facades\Log;

class SyncDriverCallResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:driver-responses 
                            {--dry-run : Mostrar qué se haría sin ejecutar cambios}
                            {--cotizacion= : Sincronizar solo una cotización específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar respuestas de conductores que tienen respuesta_llamada pero no driver_call_response_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $cotizacionId = $this->option('cotizacion');

        $this->info('🔄 Iniciando sincronización de respuestas de conductores...');
        $this->info('');

        // Query base
        $query = LlamadaConductor::query()
            ->whereNotNull('respuesta_llamada')
            ->whereNull('driver_call_response_id');

        if ($cotizacionId) {
            $query->where('cotizacion_id', $cotizacionId);
            $this->info("📌 Filtrando por cotización: {$cotizacionId}");
        }

        $conductores = $query->get();

        if ($conductores->isEmpty()) {
            $this->info('✅ No hay conductores pendientes de sincronizar');
            return 0;
        }

        $this->info("📊 Encontrados {$conductores->count()} conductores para sincronizar");
        $this->info('');

        $createdCount = 0;
        $errorCount = 0;

        foreach ($conductores as $conductor) {
            try {
                // Determinar status basado en respuesta_llamada
                $responseStatus = $this->determineResponseStatus($conductor->respuesta_llamada);

                if (!$responseStatus) {
                    $this->warn("⚠️  Conductor {$conductor->id} ({$conductor->nombre_conductor}): respuesta ambigua '{$conductor->respuesta_llamada}'");
                    continue;
                }

                $this->line("🔹 Conductor {$conductor->id}: {$conductor->nombre_conductor} - {$responseStatus}");

                if ($dryRun) {
                    $this->line("   [DRY RUN] Se crearía DriverCallResponse con status: {$responseStatus}");
                    continue;
                }

                // Crear DriverCallResponse
                $driverCallResponse = DriverCallResponse::create([
                    'cotizacion_id' => $conductor->cotizacion_id,
                    'driver_id' => $conductor->id,
                    'driver_name' => $conductor->nombre_conductor,
                    'driver_phone' => $conductor->telefono,
                    'vehicle_plate' => $conductor->placa,
                    'vehicle_type' => $conductor->tipo_vehiculo,
                    'response_status' => $responseStatus,
                    'response_time' => $conductor->fecha_llamada ?? now(),
                    'elevenlabs_conversation_id' => $conductor->elevenlabs_conversation_id,
                    'notes' => $conductor->respuesta_llamada,
                    'created_at' => $conductor->created_at,
                    'updated_at' => now()
                ]);

                // Vincular en llamadas_conductores
                $conductor->update([
                    'driver_call_response_id' => $driverCallResponse->id,
                    'estado_llamada' => 'completada'
                ]);

                $this->info("   ✅ Creado DriverCallResponse ID: {$driverCallResponse->id}");
                $createdCount++;

                Log::info("Sincronizado conductor con DriverCallResponse", [
                    'conductor_id' => $conductor->id,
                    'driver_call_response_id' => $driverCallResponse->id,
                    'response_status' => $responseStatus
                ]);

            } catch (\Exception $e) {
                $this->error("   ❌ Error: {$e->getMessage()}");
                $errorCount++;
                
                Log::error("Error sincronizando conductor", [
                    'conductor_id' => $conductor->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info('');
        $this->info('📈 Resumen de sincronización:');
        $this->info("   ✅ Creados: {$createdCount}");
        if ($errorCount > 0) {
            $this->error("   ❌ Errores: {$errorCount}");
        }

        if ($dryRun) {
            $this->warn('');
            $this->warn('⚠️  Ejecución en modo DRY RUN - No se realizaron cambios');
            $this->warn('   Ejecuta sin --dry-run para aplicar los cambios');
        }

        return 0;
    }

    /**
     * Determinar response_status basado en respuesta_llamada
     */
    private function determineResponseStatus(?string $respuestaLlamada): ?string
    {
        if (!$respuestaLlamada) {
            return null;
        }

        $respuesta = strtolower($respuestaLlamada);

        // Patrones de aceptación
        if (
            str_contains($respuesta, 'acepta') ||
            str_contains($respuesta, 'aceptó') ||
            str_contains($respuesta, 'sí') ||
            str_contains($respuesta, 'si,') ||
            str_contains($respuesta, 'está bien') ||
            str_contains($respuesta, 'ok') ||
            str_contains($respuesta, 'bueno') ||
            str_contains($respuesta, 'me interesa')
        ) {
            return 'accepted';
        }

        // Patrones de rechazo
        if (
            str_contains($respuesta, 'rechaz') ||
            str_contains($respuesta, 'no puedo') ||
            str_contains($respuesta, 'no me') ||
            str_contains($respuesta, 'ocupado') ||
            str_contains($respuesta, 'no gracias')
        ) {
            return 'rejected';
        }

        // No se pudo determinar
        return null;
    }
}
