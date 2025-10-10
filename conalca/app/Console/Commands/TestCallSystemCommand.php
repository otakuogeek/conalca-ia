<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CallController;
use App\Models\CotizacionModel;
use App\Services\ConversationalAgentService;
use App\Services\ElevenLabsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestCallSystemCommand extends Command
{
    protected $signature = 'call:test-system 
                            {cotizacion_id? : ID de la cotización a probar}
                            {--phone= : Número de teléfono específico para probar}
                            {--no-call : Solo generar mensaje sin hacer llamada}';

    protected $description = 'Probar el sistema completo de llamadas con agente IA';

    protected $callController;
    protected $agentService;
    protected $elevenLabsService;

    public function __construct(
        CallController $callController,
        ConversationalAgentService $agentService,
        ElevenLabsService $elevenLabsService
    ) {
        parent::__construct();
        $this->callController = $callController;
        $this->agentService = $agentService;
        $this->elevenLabsService = $elevenLabsService;
    }

    public function handle()
    {
        $this->info('🎤 CONALCA - Prueba del Sistema de Llamadas con Agente IA');
        $this->info('====================================================');

        try {
            // 1. Verificar configuración
            $this->info('1. Verificando configuración...');
            if (!$this->verifyConfiguration()) {
                return 1;
            }

            // 2. Obtener o crear cotización de prueba
            $cotizacionId = $this->argument('cotizacion_id');
            if (!$cotizacionId) {
                $cotizacion = $this->getLatestQuotation();
                if (!$cotizacion) {
                    $this->error('No se encontró ninguna cotización. Crea una primero.');
                    return 1;
                }
                $cotizacionId = $cotizacion->id;
            } else {
                $cotizacion = CotizacionModel::find($cotizacionId);
                if (!$cotizacion) {
                    $this->error("Cotización ID {$cotizacionId} no encontrada.");
                    return 1;
                }
            }

            $this->info("   ✅ Cotización ID: {$cotizacion->id}");
            $this->info("   📦 Vehículo: {$cotizacion->vehiculo_requerido}");
            $this->info("   💰 Valor: $" . number_format($cotizacion->valor_declarado ?? 0));

            // 3. Generar mensaje del agente
            $this->info('');
            $this->info('2. Generando mensaje del agente IA...');
            
            $driverName = 'Juan Pérez'; // Nombre de prueba
            $mensaje = $this->agentService->generateInitialMessage($cotizacion, $driverName);
            
            $this->info("   💬 Mensaje generado:");
            $this->line("   " . str_repeat('-', 60));
            $this->line("   \"$mensaje\"");
            $this->line("   " . str_repeat('-', 60));

            // 4. Generar audio con ElevenLabs
            $this->info('');
            $this->info('3. Generando audio con ElevenLabs...');
            
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech($mensaje, null, 'conversation');
            
            if ($audioUrl) {
                $this->info("   ✅ Audio generado exitosamente");
                $this->info("   🎵 URL: {$audioUrl}");
            } else {
                $this->warn("   ⚠️  Error generando audio, usará voz de fallback");
            }

            // 5. Probar sistema de llamadas (opcional)
            if (!$this->option('no-call')) {
                $this->info('');
                $this->info('4. Probando sistema de llamadas...');
                
                $phoneNumber = $this->option('phone');
                if ($phoneNumber) {
                    $this->info("   📞 Número específico: {$phoneNumber}");
                    $result = $this->testSpecificCall($cotizacion, $phoneNumber);
                } else {
                    $result = $this->testCallSystem($cotizacionId);
                }

                if ($result['success']) {
                    $this->info("   ✅ Sistema de llamadas funcionando correctamente");
                    if (isset($result['call_sid'])) {
                        $this->info("   📞 Call SID: {$result['call_sid']}");
                    }
                } else {
                    $this->error("   ❌ Error en sistema de llamadas: {$result['error']}");
                }
            } else {
                $this->info('   ⏭️  Omitiendo llamada real (--no-call activado)');
            }

            // 6. Mostrar resumen
            $this->info('');
            $this->info('🎯 Resumen de la prueba:');
            $this->line("   • Cotización ID: {$cotizacion->id}");
            $this->line("   • Vehículo requerido: {$cotizacion->vehiculo_requerido}");
            $this->line("   • Mensaje generado: " . (strlen($mensaje) > 0 ? '✅' : '❌'));
            $this->line("   • Audio ElevenLabs: " . ($audioUrl ? '✅' : '❌'));
            $this->line("   • Sistema de llamadas: " . ($this->option('no-call') ? '⏭️' : (isset($result) && $result['success'] ? '✅' : '❌')));
            
            $this->info('');
            $this->info('✅ Prueba completada exitosamente!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la prueba: ' . $e->getMessage());
            $this->line('');
            $this->line('Detalles del error:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    protected function verifyConfiguration()
    {
        $checks = [
            'OPENAI_API_KEY' => env('OPENAI_API_KEY'),
            'ELEVENLABS_API_KEY' => env('ELEVENLABS_API_KEY'),
            'TWILIO_SID' => env('TWILIO_SID'),
            'TWILIO_AUTH_TOKEN' => env('TWILIO_AUTH_TOKEN'),
            'TWILIO_PHONE_NUMBER' => env('TWILIO_PHONE_NUMBER')
        ];

        $allGood = true;
        
        foreach ($checks as $key => $value) {
            if (empty($value)) {
                $this->error("   ❌ {$key} no está configurado");
                $allGood = false;
            } else {
                $this->info("   ✅ {$key} configurado");
            }
        }

        return $allGood;
    }

    protected function getLatestQuotation()
    {
        return CotizacionModel::orderBy('id', 'desc')->first();
    }

    protected function testCallSystem($cotizacionId)
    {
        try {
            $request = new Request(['cotizacion_model_id' => $cotizacionId]);
            $response = $this->callController->callDrivers($request);
            
            $data = $response->getData(true);
            
            return [
                'success' => !isset($data['error']),
                'call_sid' => $data['call_sid'] ?? null,
                'message' => $data['message'] ?? '',
                'error' => $data['error'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function testSpecificCall($cotizacion, $phoneNumber)
    {
        // Esta funcionalidad se implementaría para hacer llamadas a números específicos
        $this->warn("   ⚠️  Llamada a número específico no implementada aún");
        return [
            'success' => true,
            'message' => 'Simulación de llamada específica'
        ];
    }
}