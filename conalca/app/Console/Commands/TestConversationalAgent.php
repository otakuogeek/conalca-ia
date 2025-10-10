<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;
use App\Services\OpenAIService;
use App\Services\VoiceCacheService;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestConversationalAgent extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:conversational-agent {input?} {--no-audio} {--show-cache}';

    /**
     * The console command description.
     */
    protected $description = 'Probar el agente conversacional de CONALCA de manera directa';

    protected $elevenLabsService;
    protected $openAIService;
    protected $voiceCacheService;

    public function __construct(
        ElevenLabsService $elevenLabsService,
        OpenAIService $openAIService,
        VoiceCacheService $voiceCacheService
    ) {
        parent::__construct();
        $this->elevenLabsService = $elevenLabsService;
        $this->openAIService = $openAIService;
        $this->voiceCacheService = $voiceCacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Iniciando prueba del Agente Conversacional CONALCA');
        $this->newLine();

        // Verificar servicios
        if (!$this->checkServices()) {
            return 1;
        }

        // Crear conversación de prueba
        $conversation = $this->createTestConversation();
        $this->info("📞 Conversación creada - ID: {$conversation->id}");

        // Mensaje de bienvenida
        $welcomeMessage = config('elevenlabs.predefined_messages.welcome', 
            'Hola, soy Andrea, tu asistente virtual de CONALCA. ¿En qué puedo ayudarte hoy?');
        
        $conversation->addToHistory('assistant', $welcomeMessage);
        $this->info("🎙️  Andrea: {$welcomeMessage}");

        if (!$this->option('no-audio')) {
            $this->generateAndPlayAudio($welcomeMessage, $conversation->id);
        }

        // Mostrar estadísticas de caché si se solicita
        if ($this->option('show-cache')) {
            $this->showCacheStats();
        }

        // Bucle de conversación interactiva
        $this->newLine();
        $this->info('💬 Iniciando conversación interactiva (escribe "salir" para terminar)');
        $this->newLine();

        while (true) {
            // Obtener input del usuario
            $userInput = $this->argument('input') ?? $this->ask('👤 Tú');

            if (strtolower($userInput) === 'salir' || strtolower($userInput) === 'exit') {
                $this->finalizarConversacion($conversation);
                break;
            }

            if (empty($userInput)) {
                $this->warn('Por favor, ingresa un mensaje válido.');
                continue;
            }

            // Procesar con el agente conversacional
            $this->processUserInput($userInput, $conversation);

            // Si se proporcionó input como argumento, salir después de procesar
            if ($this->argument('input')) {
                $this->finalizarConversacion($conversation);
                break;
            }
        }

        return 0;
    }

    /**
     * Verificar que todos los servicios estén configurados
     */
    protected function checkServices()
    {
        $this->info('🔍 Verificando servicios...');

        // Verificar ElevenLabs
        if (empty(config('elevenlabs.api_key'))) {
            $this->error('❌ ElevenLabs API key no configurada');
            return false;
        }

        // Verificar OpenAI
        if (empty(config('services.openai.api_key'))) {
            $this->error('❌ OpenAI API key no configurada');
            return false;
        }

        // Verificar voice ID
        if (empty(config('elevenlabs.default_voice_id'))) {
            $this->warn('⚠️  Voice ID por defecto no configurado, usando voz predeterminada');
        }

        $this->info('✅ Todos los servicios están configurados correctamente');
        return true;
    }

    /**
     * Crear conversación de prueba
     */
    protected function createTestConversation()
    {
        return Conversation::create([
            'call_sid' => 'TEST_' . uniqid(),
            'caller_number' => '+573105672307',
            'caller_city' => 'Bogotá',
            'caller_country' => 'CO',
            'direction' => 'inbound',
            'status' => 'active',
            'started_at' => now(),
            'voice_id' => config('elevenlabs.default_voice_id'),
            'context' => [
                'system_message' => $this->getSystemPrompt(),
                'history' => []
            ],
            'metadata' => [
                'test_mode' => true,
                'console_command' => true
            ]
        ]);
    }

    /**
     * Procesar entrada del usuario
     */
    protected function processUserInput($userInput, $conversation)
    {
        $startTime = microtime(true);

        $this->line("👤 Usuario: {$userInput}");

        // Guardar entrada del usuario
        $conversation->addToHistory('user', $userInput);

        // Analizar sentimiento y detectar tema
        $conversation->analyzeSentiment($userInput);
        $conversation->detectTopic();

        try {
            // Procesar con OpenAI
            $botResponse = $this->openAIService->processConversation(
                $userInput, 
                $conversation->getOpenAIContext()
            );

            // Guardar respuesta del bot
            $conversation->addToHistory('assistant', $botResponse);

            $processingTime = round((microtime(true) - $startTime) * 1000);

            $this->info("🤖 Andrea: {$botResponse}");
            $this->comment("⏱️  Tiempo de procesamiento: {$processingTime}ms");

            // Generar audio si está habilitado
            if (!$this->option('no-audio')) {
                $this->generateAndPlayAudio($botResponse, $conversation->id);
            }

            // Mostrar métricas de la conversación
            $this->showConversationMetrics($conversation);

        } catch (\Exception $e) {
            $this->error("❌ Error procesando entrada: {$e->getMessage()}");
            Log::error('Error en test conversational agent', [
                'error' => $e->getMessage(),
                'user_input' => $userInput,
                'conversation_id' => $conversation->id
            ]);
        }

        $this->newLine();
    }

    /**
     * Generar y "reproducir" audio
     */
    protected function generateAndPlayAudio($text, $conversationId)
    {
        try {
            $this->comment('🔊 Generando audio...');
            $startTime = microtime(true);

            $voiceId = config('elevenlabs.default_voice_id');
            $settings = config('elevenlabs.voice_settings.spanish');

            // Verificar caché
            $cachedAudio = $this->voiceCacheService->getCachedAudio($text, $voiceId, $settings);

            if ($cachedAudio) {
                $this->comment('💾 Audio encontrado en caché');
                return;
            }

            // Generar nuevo audio
            $audioContent = $this->elevenLabsService->generateSpeech($text, $voiceId);
            $generationTime = round((microtime(true) - $startTime) * 1000);

            if ($audioContent) {
                // Guardar archivo
                $filename = "test_conversation_{$conversationId}_" . time() . ".mp3";
                $filePath = "voices/test/{$filename}";
                
                Storage::disk('public')->put($filePath, $audioContent);
                
                // Cachear para futuras solicitudes
                $this->voiceCacheService->cacheAudio($text, $voiceId, $settings, $audioContent);

                $this->comment("🎵 Audio generado en {$generationTime}ms - Tamaño: " . $this->formatBytes(strlen($audioContent)));
                $this->comment("📁 Guardado en: storage/app/public/{$filePath}");
            } else {
                $this->warn('⚠️  No se pudo generar audio');
            }

        } catch (\Exception $e) {
            $this->warn("⚠️  Error generando audio: {$e->getMessage()}");
        }
    }

    /**
     * Mostrar métricas de conversación
     */
    protected function showConversationMetrics($conversation)
    {
        $conversation = $conversation->fresh(); // Recargar desde BD
        
        $this->comment("📊 Métricas: Turnos: {$conversation->turn_count} | " . 
                      "Sentimiento: {$conversation->sentiment_score} | " . 
                      "Tema: {$conversation->topic}");
    }

    /**
     * Mostrar estadísticas de caché
     */
    protected function showCacheStats()
    {
        try {
            $stats = $this->voiceCacheService->getCacheStatistics();
            
            $this->newLine();
            $this->info('📊 Estadísticas de caché de audio:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Elementos en caché', $stats['total_items']],
                    ['Tamaño total', $this->formatBytes($stats['total_size'])],
                    ['Hits de caché', $stats['cache_hits']],
                    ['Tasa de hit', round($stats['hit_rate'], 2) . '%'],
                    ['Último cleanup', $stats['last_cleanup'] ?? 'Nunca'],
                ]
            );
            $this->newLine();

        } catch (\Exception $e) {
            $this->warn("⚠️  No se pudieron obtener estadísticas de caché: {$e->getMessage()}");
        }
    }

    /**
     * Finalizar conversación
     */
    protected function finalizarConversacion($conversation)
    {
        $conversation->markCompleted('user_ended', true);
        
        $this->newLine();
        $this->info('👋 Conversación finalizada');
        $this->comment("📊 Resumen: {$conversation->turn_count} turnos en " . 
                      $conversation->started_at->diffForHumans());
        
        $this->newLine();
        $this->info('🎯 Gracias por probar el agente conversacional de CONALCA');
    }

    /**
     * Obtener prompt del sistema
     */
    protected function getSystemPrompt()
    {
        return "Eres Andrea, asistente virtual de CONALCA, empresa líder en transporte y logística en Colombia. 
        IMPORTANTE - Este es un modo de prueba por consola:
        - Mantén respuestas concisas pero informativas (máximo 3-4 oraciones)
        - Usa lenguaje natural y profesional
        - Ayuda con consultas sobre cotizaciones, seguimiento, servicios de transporte
        - Para cotizaciones pregunta: origen, destino, tipo de carga, peso aproximado
        - Si no tienes información específica, explica cómo pueden obtenerla
        - Mantén un tono amable y útil
        - Responde siempre en español";
    }

    /**
     * Formatear bytes a formato legible
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}