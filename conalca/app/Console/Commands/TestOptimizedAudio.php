<?php

namespace App\Console\Commands;

use App\Services\ElevenLabsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestOptimizedAudio extends Command
{
    protected $signature = 'test:optimized-audio {--text=Hola, soy Andrea de CONALCA. ¿Cómo está usted hoy?}';
    protected $description = 'Prueba el sistema de audio optimizado de ElevenLabs';

    private $elevenLabsService;

    public function __construct(ElevenLabsService $elevenLabsService)
    {
        parent::__construct();
        $this->elevenLabsService = $elevenLabsService;
    }

    public function handle()
    {
        $this->info('🎤 Probando sistema de audio optimizado...');
        
        $text = $this->option('text');
        $this->info("📝 Texto: {$text}");
        
        // Test audio con contexto de saludo
        $this->info('🔊 Probando audio optimizado con contexto "greeting"...');
        try {
            $startTime = microtime(true);
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech($text, null, 'greeting');
            $duration = round((microtime(true) - $startTime) * 1000);
            
            if ($audioUrl) {
                $this->info("✅ Audio generado exitosamente en {$duration}ms");
                $this->info("🔗 URL: {$audioUrl}");
            } else {
                $this->error('❌ Error generando audio optimizado');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test audio con contexto conversacional
        $this->info('🔊 Probando audio optimizado con contexto "conversation"...');
        try {
            $startTime = microtime(true);
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech($text, null, 'conversation');
            $duration = round((microtime(true) - $startTime) * 1000);
            
            if ($audioUrl) {
                $this->info("✅ Audio conversacional generado exitosamente en {$duration}ms");
                $this->info("🔗 URL: {$audioUrl}");
            } else {
                $this->error('❌ Error generando audio conversacional');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        // Test audio con contexto urgente
        $this->info('🔊 Probando audio optimizado con contexto "urgent"...');
        try {
            $startTime = microtime(true);
            $audioUrl = $this->elevenLabsService->generatePhoneOptimizedSpeech($text, null, 'urgent');
            $duration = round((microtime(true) - $startTime) * 1000);
            
            if ($audioUrl) {
                $this->info("✅ Audio urgente generado exitosamente en {$duration}ms");
                $this->info("🔗 URL: {$audioUrl}");
            } else {
                $this->error('❌ Error generando audio urgente');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        $this->info('🎯 Prueba de audio optimizado completada');
        
        return 0;
    }
}