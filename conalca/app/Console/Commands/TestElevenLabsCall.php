<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsCallService;
use App\Models\DriverCallResponse;

class TestElevenLabsCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elevenlabs:test-call {phone} {--test-mode} {--driver-id=1} {--cotizacion-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test ElevenLabs SIP trunk outbound call';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $driverId = $this->option('driver-id');
        $cotizacionId = $this->option('cotizacion-id');
        $testMode = $this->option('test-mode');

        $this->info("=== Prueba de llamada ElevenLabs SIP Trunk ===");
        $this->info("Teléfono: {$phone}");
        $this->info("Driver ID: {$driverId}");
        $this->info("Cotización ID: {$cotizacionId}");
        $this->info("Modo prueba: " . ($testMode ? 'SÍ' : 'NO'));
        $this->line('');

        try {
            // Obtener el servicio
            $elevenLabsCallService = app(ElevenLabsCallService::class);
            
            // Primero probar la conexión
            $this->info("1. Probando conexión a ElevenLabs SIP Trunk...");
            $connectionTest = $elevenLabsCallService->testSipTrunkConnection();
            
            if ($connectionTest['success']) {
                $this->info("✅ Conexión exitosa");
                $this->line("   Agent ID: " . ($connectionTest['agent_id'] ?? 'No configurado'));
                $this->line("   Phone Number ID: " . ($connectionTest['agent_phone_number_id'] ?? 'No configurado'));
            } else {
                $this->error("❌ Error de conexión: " . $connectionTest['error']);
                return 1;
            }
            
            $this->line('');
            
            // Validar número
            $this->info("2. Validando número de teléfono...");
            if (!$elevenLabsCallService->isValidPhoneNumber($phone)) {
                $this->error("❌ Número de teléfono inválido: {$phone}");
                return 1;
            }
            $this->info("✅ Número válido");
            
            $this->line('');

            if ($testMode) {
                $this->warn("Modo prueba activado - No se realizará la llamada real");
                return 0;
            }

            // Preparar datos del cliente
            $clientData = [
                'driver_id' => $driverId,
                'driver_name' => 'Test Driver',
                'cotizacion_id' => $cotizacionId,
                'test_call' => true,
                'initiated_by' => 'artisan_command'
            ];

            // Realizar llamada
            $this->info("3. Realizando llamada...");
            $result = $elevenLabsCallService->makeDirectSipCall($phone, $clientData);

            if ($result['success']) {
                $this->info("✅ Llamada iniciada exitosamente");
                $this->line("   Conversation ID: " . $result['conversation_id']);
                $this->line("   SIP Call ID: " . ($result['sip_call_id'] ?? 'N/A'));
                
                // Crear registro en la base de datos
                $callResponse = DriverCallResponse::create([
                    'cotizacion_id' => $cotizacionId,
                    'driver_id' => $driverId,
                    'driver_name' => 'Test Driver (Artisan)',
                    'driver_phone' => $phone,
                    'vehicle_type' => 'Test Vehicle',
                    'call_status' => 'calling',
                    'response_status' => 'pending',
                    'elevenlabs_conversation_id' => $result['conversation_id'],
                    'elevenlabs_sip_call_id' => $result['sip_call_id'] ?? null,
                    'notes' => 'Prueba desde comando artisan'
                ]);
                
                $this->line("   Registro DB ID: " . $callResponse->id);
                
            } else {
                $this->error("❌ Error en la llamada: " . $result['error']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Excepción: " . $e->getMessage());
            $this->line("Archivo: " . $e->getFile());
            $this->line("Línea: " . $e->getLine());
            return 1;
        }

        $this->line('');
        $this->info("✅ Prueba completada exitosamente");
        return 0;
    }
}
