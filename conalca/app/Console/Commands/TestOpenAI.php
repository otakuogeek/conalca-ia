<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;
use OpenAI;

class TestOpenAI extends Command
{
    protected $signature = 'test:openai {--text=Hola, este es un mensaje de prueba}';
    protected $description = 'Prueba la configuración de OpenAI y el servicio personalizado';

    public function handle()
    {
        $this->info('🧠 Probando OpenAI...');
        
        try {
            $openai = OpenAI::client(env('OPENAI_API_KEY'));
            
            // Probar Whisper (transcripción)
            $this->line('📝 Probando modelos disponibles...');
            $models = $openai->models()->list();
            $this->info('✅ Modelos disponibles: ' . count($models->data));
            
            // Probar GPT-4o-mini
            $this->line('🤖 Probando GPT-4o-mini básico...');
            $response = $openai->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $this->option('text')]
                ],
                'max_tokens' => 60,
                'temperature' => 0.5
            ]);
            
            $this->info('✅ Respuesta de GPT-4o-mini:');
            $this->line('   ' . $response->choices[0]->message->content);
            
            // Probar nuestro servicio personalizado
            $this->line("\n🎯 Probando nuestro OpenAIService...");
            $openaiService = app(OpenAIService::class);
            
            $testMessages = [
                'Necesito enviar un paquete a Medellín urgente',
                'Quiero saber el precio para transportar mercancía',
                'Tengo una queja sobre mi último envío',
                'Hola, qué servicios ofrecen'
            ];
            
            foreach ($testMessages as $text) {
                $this->line("\n📝 Texto: $text");
                
                $result = $openaiService->analyzeIntent($text);
                $this->line("   Intent: " . $result['intent']);
                $this->line("   Confidence: " . number_format($result['confidence'], 2));
                $this->line("   Explanation: " . $result['explanation']);
                
                $response = $openaiService->generateContextualResponse($text, $result['intent']);
                $this->line("   Response: " . substr($response, 0, 100) . "...");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('   Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
