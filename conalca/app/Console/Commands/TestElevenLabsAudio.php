<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class TestElevenLabsAudio extends Command
{
    protected $signature = 'test:elevenlabs-audio {text?}';
    protected $description = 'Probar generación de audio con ElevenLabs';

    public function handle()
    {
        $text = $this->argument('text') ?? 'Hola, esta es una prueba de la voz de Andrea en ElevenLabs para el sistema de Conalca. ¿Se escucha bien el español?';
        
        $this->info('🎤 Probando generación de audio con ElevenLabs...');
        $this->line("📝 Texto: $text");
        
        try {
            $elevenLabs = app(ElevenLabsService::class);
            
            $this->line("🔄 Generando audio...");
            $audioContent = $elevenLabs->generateSpeech($text);
            
            $audioSize = strlen($audioContent);
            $this->info("✅ Audio generado exitosamente");
            $this->line("📊 Tamaño: " . number_format($audioSize / 1024, 2) . " KB");
            
            // Verificar que el contenido sea válido
            if ($audioSize > 1000) {
                $this->info("✅ El audio parece tener contenido válido");
            } else {
                $this->warn("⚠️ El audio es muy pequeño, podría haber un problema");
            }
            
            // Información sobre la voz actual
            $voiceId = config('services.elevenlabs.default_voice_id');
            $this->line("🎯 Voz utilizada: Andrea (ID: $voiceId)");
            
        } catch (\Exception $e) {
            $this->error('❌ Error al generar audio: ' . $e->getMessage());
            $this->line("Archivo: " . $e->getFile() . ':' . $e->getLine());
        }
    }
}