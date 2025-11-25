<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\VoiceAgentController;
use Illuminate\Http\Request;

class TestVoiceAgent extends Command
{
    protected $signature = 'voice:test {--speech=} {--digits=} {--call-sid=}';
    protected $description = 'Probar el agente de voz con entrada simulada';

    public function handle()
    {
        $this->info('🎙️  CONALCA Voice Agent - Simulador de Pruebas');
        $this->info('================================================');

        $callSid = $this->option('call-sid') ?? 'TEST_' . time();
        $speech = $this->option('speech');
        $digits = $this->option('digits');

        $controller = new VoiceAgentController(app(\App\Services\OpenAIService::class));

        if ($speech) {
            $this->info("🗣️  Probando reconocimiento de voz...");
            $this->info("💬 Entrada: $speech");
            $this->info("📞 Call SID: $callSid");
            
            $request = new Request([
                'SpeechResult' => $speech,
                'CallSid' => $callSid
            ]);

            $response = $controller->processVoiceInput($request);
            $content = $response->getContent();
            
            $this->info("📤 Respuesta TwiML:");
            $this->line($this->formatXml($content));
            
        } elseif ($digits) {
            $this->info("🔢 Probando entrada DTMF...");
            $this->info("🎹 Dígitos: $digits");
            
            $request = new Request([
                'Digits' => $digits
            ]);

            $response = $controller->menuResponse($request);
            $content = $response->getContent();
            
            $this->info("📤 Respuesta TwiML:");
            $this->line($this->formatXml($content));
            
        } else {
            $this->info("🎯 Probando mensaje de bienvenida...");
            
            $request = new Request();
            $response = $controller->welcome($request);
            $content = $response->getContent();
            
            $this->info("📤 TwiML de bienvenida:");
            $this->line($this->formatXml($content));
            
            $this->info("");
            $this->info("💡 Ejemplos de uso:");
            $this->info("   php artisan voice:test --digits=1");
            $this->info("   php artisan voice:test --speech='Quiero una cotización para enviar una carga'");
            $this->info("   php artisan voice:test --speech='¿Cuáles son sus tarifas?' --call-sid=CA123456");
        }

        $this->info("");
        $this->info("✅ Prueba completada");
    }

    private function formatXml($xml)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}