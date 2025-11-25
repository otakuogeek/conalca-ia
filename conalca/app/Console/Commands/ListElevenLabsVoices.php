<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class ListElevenLabsVoices extends Command
{
    protected $signature = 'elevenlabs:list-voices';
    protected $description = 'Listar todas las voces disponibles en ElevenLabs';

    public function handle()
    {
        $this->info('🎤 Listando voces disponibles en ElevenLabs...');
        
        try {
            $elevenLabs = app(ElevenLabsService::class);
            $response = $elevenLabs->getAvailableVoices();
            $voices = $response['voices'] ?? [];
            
            if (empty($voices)) {
                $this->error('❌ No se pudieron obtener las voces de ElevenLabs');
                $this->line('Respuesta: ' . json_encode($response));
                return;
            }
            
            $this->line("\n📋 Voces encontradas (" . count($voices) . "):");
            $this->table(['ID', 'Nombre', 'Categoría', 'Descripción'], 
                array_map(function($voice) {
                    return [
                        substr($voice['voice_id'] ?? 'N/A', 0, 20),
                        $voice['name'] ?? 'N/A', 
                        $voice['category'] ?? 'N/A',
                        isset($voice['labels']['description']) ? substr($voice['labels']['description'], 0, 30) . '...' : 'N/A'
                    ];
                }, $voices)
            );
            
            $this->line("\n🔍 Buscando voces en español...");
            $spanishVoices = array_filter($voices, function($voice) {
                $labels = $voice['labels'] ?? [];
                return isset($labels['language']) && 
                       (in_array('spanish', (array)$labels['language']) || 
                        in_array('Spanish', (array)$labels['language']));
            });
            
            if (!empty($spanishVoices)) {
                $this->info("✅ Encontradas " . count($spanishVoices) . " voces en español:");
                foreach ($spanishVoices as $voice) {
                    $this->line("   🇪🇸 {$voice['name']} (ID: {$voice['voice_id']})");
                }
            } else {
                $this->warn("⚠️ No se encontraron voces específicamente marcadas como español");
            }
            
            // Verificar la voz actual configurada
            $currentVoiceId = config('elevenlabs.default_voice_id');
            $this->line("\n🎯 Voz configurada actualmente: $currentVoiceId");
            
            $currentVoice = array_filter($voices, function($voice) use ($currentVoiceId) {
                return $voice['voice_id'] === $currentVoiceId;
            });
            
            if (!empty($currentVoice)) {
                $voice = array_values($currentVoice)[0];
                $this->info("✅ Voz encontrada: {$voice['name']}");
            } else {
                $this->error("❌ La voz configurada no se encuentra en la cuenta");
                
                // Sugerir voces alternativas
                $this->line("\n💡 Voces recomendadas:");
                $recommended = array_slice($voices, 0, 5);
                foreach ($recommended as $voice) {
                    $this->line("   🎤 {$voice['name']} (ID: {$voice['voice_id']})");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error al listar voces: ' . $e->getMessage());
            $this->line("Detalles: " . $e->getFile() . ':' . $e->getLine());
        }
    }
}