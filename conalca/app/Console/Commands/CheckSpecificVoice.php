<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class CheckSpecificVoice extends Command
{
    protected $signature = 'elevenlabs:check-voice {voice_id}';
    protected $description = 'Verificar si una voz específica está disponible en ElevenLabs';

    public function handle()
    {
        $voiceId = $this->argument('voice_id');
        $this->info("🔍 Verificando voz: $voiceId");
        
        try {
            $elevenLabs = app(ElevenLabsService::class);
            $response = $elevenLabs->getAvailableVoices();
            $voices = $response['voices'] ?? [];
            
            if (empty($voices)) {
                $this->error('❌ No se pudieron obtener las voces de ElevenLabs');
                return;
            }
            
            // Buscar la voz específica
            $foundVoice = null;
            foreach ($voices as $voice) {
                if ($voice['voice_id'] === $voiceId) {
                    $foundVoice = $voice;
                    break;
                }
            }
            
            if ($foundVoice) {
                $this->info("✅ ¡Voz encontrada!");
                $this->table(['Propiedad', 'Valor'], [
                    ['ID', $foundVoice['voice_id']],
                    ['Nombre', $foundVoice['name'] ?? 'N/A'],
                    ['Categoría', $foundVoice['category'] ?? 'N/A'],
                    ['Descripción', $foundVoice['labels']['description'] ?? 'N/A'],
                    ['Idioma', isset($foundVoice['labels']['language']) ? implode(', ', (array)$foundVoice['labels']['language']) : 'N/A'],
                    ['Género', $foundVoice['labels']['gender'] ?? 'N/A'],
                    ['Edad', $foundVoice['labels']['age'] ?? 'N/A'],
                    ['Acento', $foundVoice['labels']['accent'] ?? 'N/A'],
                ]);
                
                // Probar generación de audio
                $this->line("\n🎤 Probando generación de audio...");
                try {
                    $testText = "Hola, esta es una prueba de la voz en español para el sistema de Conalca.";
                    $audioContent = $elevenLabs->generateSpeech($testText, $voiceId);
                    $audioSize = strlen($audioContent);
                    
                    $this->info("✅ Audio generado exitosamente");
                    $this->line("📊 Tamaño: " . number_format($audioSize / 1024, 2) . " KB");
                    
                    if ($audioSize > 1000) {
                        $this->info("✅ La voz funciona correctamente para generar audio");
                    } else {
                        $this->warn("⚠️ El audio generado es muy pequeño");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error al generar audio: " . $e->getMessage());
                }
                
            } else {
                $this->error("❌ Voz no encontrada en la cuenta");
                $this->line("\n📋 Voces disponibles similares:");
                
                // Mostrar voces que podrían ser similares (por nombre o categoría)
                $similarVoices = array_slice($voices, 0, 5);
                foreach ($similarVoices as $voice) {
                    $this->line("   🎤 {$voice['name']} (ID: {$voice['voice_id']})");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error al verificar voz: ' . $e->getMessage());
            $this->line("Detalles: " . $e->getFile() . ':' . $e->getLine());
        }
    }
}