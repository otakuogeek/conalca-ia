<?php

namespace App\Console\Commands;

use App\Services\ElevenLabsService;
use Illuminate\Console\Command;

class DebugElevenLabsConfig extends Command
{
    protected $signature = 'debug:elevenlabs-config';
    protected $description = 'Debug la configuración de ElevenLabs';

    public function handle()
    {
        $this->info('🔍 Debuggeando configuración ElevenLabs...');
        
        // Directamente desde env()
        $envKey = env('ELEVENLABS_API_KEY');
        $this->info("📋 env('ELEVENLABS_API_KEY'): " . ($envKey ? substr($envKey, 0, 15) . '...' : 'NULL'));
        
        // Desde config
        $configKey = config('services.elevenlabs.api_key');
        $this->info("📋 config('services.elevenlabs.api_key'): " . ($configKey ? substr($configKey, 0, 15) . '...' : 'NULL'));
        
        // Desde config/elevenlabs.php
        $elevenConfig = config('elevenlabs.api_key');
        $this->info("📋 config('elevenlabs.api_key'): " . ($elevenConfig ? substr($elevenConfig, 0, 15) . '...' : 'NULL'));
        
        // Instanciar el servicio y ver qué obtiene
        $this->info('🔧 Instanciando ElevenLabsService...');
        $service = app(ElevenLabsService::class);
        
        // Usar reflexión para acceder a la propiedad privada
        $reflection = new \ReflectionClass($service);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $serviceApiKey = $apiKeyProperty->getValue($service);
        
        $this->info("📋 ElevenLabsService->apiKey: " . ($serviceApiKey ? substr($serviceApiKey, 0, 15) . '...' : 'NULL'));
        
        return 0;
    }
}