<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// use App\Services\TwilioService; // REMOVED: Twilio service disabled
use App\Services\OpenAIService;
use App\Services\LocalAudioStorage;

class TestCallFlow extends Command
{
    protected $signature = 'test:call-flow {phone?}';
    protected $description = 'Probar el flujo completo de llamadas (Twilio disabled)';

    public function handle()
    {
        $this->warn('📞 Twilio ha sido removido - flujo de llamadas deshabilitado');
        $this->info('🧠 Probando solo servicios disponibles...');
        
        try {
            // Servicios disponibles
            // $twilio = app(TwilioService::class); // DISABLED
            $openai = app(OpenAIService::class);
            $audioStorage = app(LocalAudioStorage::class);
            
            // 1. Probar análisis de intención
            $this->line("\n🧠 1. Probando análisis de intención...");
            $mensaje = "Necesito cotizar un envío de Bogotá a Cali";
            $intent = $openai->analyzeIntent($mensaje);
            $this->info("   Intent detectado: " . $intent['intent'] . " (confianza: " . number_format($intent['confidence'], 2) . ")");
            
            // 2. Generar respuesta contextual
            $this->line("\n🤖 2. Generando respuesta...");
            $respuesta = $openai->generateContextualResponse($mensaje, $intent['intent']);
            $this->info("   Respuesta: " . substr($respuesta, 0, 100) . "...");
            
            // 3. Generar audio local (Disabled - Twilio removed)
            $this->line("\n🎤 3. Audio generation disabled (Twilio removed)");
            // $audioPath = $twilio->generateAndUploadAudio($respuesta, 'response');
            $this->warn("   Audio generation skipped - TwilioService removed");
            
            // 4. Obtener estadísticas de almacenamiento
            $this->line("\n📊 4. Estadísticas de almacenamiento...");
            $stats = $audioStorage->getStorageStats();
            $this->table(['Métrica', 'Valor'], [
                ['Archivos temporales', $stats['temp_files']],
                ['Archivos de llamadas', $stats['calls_files']],
                ['Tamaño total', $stats['total_size_mb'] . ' MB'],
                ['Tamaño temp', $stats['temp_size_mb'] . ' MB'],
                ['Tamaño calls', $stats['calls_size_mb'] . ' MB']
            ]);
            
            // 5. Probar llamada saliente (DISABLED)
            $phone = $this->argument('phone');
            if ($phone) {
                $this->line("\n📱 5. Llamadas deshabilitadas - TwilioService removido");
                $this->warn("   Funcionalidad de llamadas no disponible");
            } else {
                $this->line("\n📱 5. Llamadas deshabilitadas - TwilioService removido del sistema");
            }
            
            $this->line("\n✅ Servicios disponibles probados exitosamente");
            
        } catch (\Exception $e) {
            $this->error('❌ Error en el flujo: ' . $e->getMessage());
            $this->error('   ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}