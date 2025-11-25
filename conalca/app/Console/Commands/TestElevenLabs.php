<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class TestElevenLabs extends Command
{
    protected $signature = 'elevenlabs:test 
                          {--text=Hola, este es un mensaje de prueba en español desde el sistema de inteligencia artificial}
                          {--voice=JBFqnCBsd6RMkjVDRZzb}
                          {--model=eleven_multilingual_v2}
                          {--save-file : Guardar el archivo de audio}';
    protected $description = 'Prueba la configuración de ElevenLabs generando un archivo de audio';

    public function handle()
    {
        $this->info('🎤 Probando ElevenLabs Text-to-Speech...');
        $this->newLine();

        try {
            $elevenLabs = new ElevenLabsService();
            
            $text = $this->option('text');
            $voiceId = $this->option('voice') ?: env('ELEVENLABS_DEFAULT_VOICE_ID', 'JBFqnCBsd6RMkjVDRZzb');
            $modelId = $this->option('model') ?: env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2');

            $this->info("📝 Texto: {$text}");
            $this->info("🎭 Voz ID: {$voiceId}");
            $this->info("🤖 Modelo: {$modelId}");
            $this->newLine();

            $this->info('⏳ Generando audio...');
            
            $result = $elevenLabs->textToSpeech($text, $voiceId, $modelId);
            
            // Guardar archivo de prueba
            $filename = 'test_elevenlabs_' . time() . '.mp3';
            $filePath = $elevenLabs->saveAudioFile($result['audio_content'], $filename);
            
            $this->newLine();
            $this->info('✅ ¡Audio generado exitosamente!');
            $this->line("📁 Archivo guardado: storage/app/public/{$filePath}");
            $this->line("📊 Tamaño: " . number_format($result['size']) . " bytes");
            $this->line("🔗 URL: " . asset('storage/' . $filePath));
            
            // Mostrar voces recomendadas
            $this->newLine();
            $this->info('🎭 Voces recomendadas para español:');
            $spanishVoices = $elevenLabs->getSpanishVoices();
            
            foreach ($spanishVoices as $voice) {
                $current = $voice['voice_id'] === $voiceId ? ' ← ACTUAL' : '';
                $this->line("   • {$voice['name']} ({$voice['voice_id']}) - {$voice['description']}{$current}");
            }
            
            $this->newLine();
            $this->info('💡 Comandos útiles:');
            $this->line('   php artisan elevenlabs:test --voice=pNInz6obpgDQGcFmaJgB --text="Texto personalizado"');
            $this->line('   php artisan elevenlabs:test --model=eleven_flash_v2');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error al probar ElevenLabs:');
            $this->line('   ' . $e->getMessage());
            
            $this->newLine();
            $this->info('🔍 Verifica:');
            $this->line('   1. ELEVENLABS_API_KEY está configurada en .env');
            $this->line('   2. La API key es válida y tiene créditos');
            $this->line('   3. Tienes conexión a internet');
            
            return 1;
        }
    }
}
