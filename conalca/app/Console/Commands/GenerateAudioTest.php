<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class GenerateAudioTest extends Command
{
    protected $signature = 'elevenlabs:generate 
                          {--text=Hola, este es un mensaje de prueba del sistema de inteligencia artificial para transporte de carga}
                          {--voice=} 
                          {--save : Guardar archivo de audio}';

    protected $description = 'Genera audio usando ElevenLabs y opcionalmente lo guarda';

    public function handle()
    {
        $this->info('🎤 Generando audio con ElevenLabs...');
        $this->newLine();
        
        try {
            $elevenLabs = new ElevenLabsService();
            
            $text = $this->option('text');
            $voiceId = $this->option('voice') ?: env('ELEVENLABS_DEFAULT_VOICE_ID', 'JBFqnCBsd6RMkjVDRZzb');
            
            $this->line("📝 Texto: {$text}");
            $this->line("🎭 Voz ID: {$voiceId}");
            $this->newLine();
            
            $this->info('⏳ Generando audio...');
            $result = $elevenLabs->textToSpeech($text, $voiceId);
            
            $sizeKB = round(strlen($result['audio_content']) / 1024, 2);
            $this->info("✅ Audio generado: {$sizeKB} KB");
            
            if ($this->option('save')) {
                $filename = 'tts_' . date('Y-m-d_H-i-s') . '.mp3';
                $filePath = $elevenLabs->saveAudioFile($result['audio_content'], $filename);
                
                $fullPath = storage_path('app/public/' . $filePath);
                $this->info("💾 Archivo guardado en: {$fullPath}");
                $this->line("🌐 URL: " . asset('storage/' . $filePath));
            }
            
            $this->newLine();
            $this->info('🎉 ¡Generación exitosa!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
