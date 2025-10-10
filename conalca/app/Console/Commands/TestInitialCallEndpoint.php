<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConversationalAgentController;
use App\Services\ConversationalAgentService;
use App\Services\ElevenLabsService;
use Illuminate\Console\Command;

class TestInitialCallEndpoint extends Command
{
    protected $signature = 'test:initial-call-endpoint';
    protected $description = 'Test the initial call endpoint directly';

    private $agentService;
    private $elevenLabsService;

    public function __construct(ConversationalAgentService $agentService, ElevenLabsService $elevenLabsService)
    {
        parent::__construct();
        $this->agentService = $agentService;
        $this->elevenLabsService = $elevenLabsService;
    }

    public function handle()
    {
        $this->info('🔍 Probando endpoint initial-call...');
        
        try {
            // Instanciar controlador
            $controller = new ConversationalAgentController($this->agentService, $this->elevenLabsService);
            $this->info('✅ Controlador instanciado correctamente');
            
            // Simular request
            $request = request();
            $request->merge([
                'CallSid' => 'CA123456789',
                'From' => '+573105672307',
                'To' => '+576013790311',
                'CallStatus' => 'in-progress'
            ]);
            $request->query->add(['cotizacion_id' => 57, 'driver_id' => 965]);
            
            $this->info('✅ Request simulado');
            
            // Ejecutar método
            $this->info('🎯 Ejecutando handleInitialCall...');
            $response = $controller->handleInitialCall($request);
            
            $this->info('✅ Respuesta obtenida');
            $this->info('📄 Contenido: ' . substr($response->getContent(), 0, 200) . '...');
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('🔍 Trace: ' . $e->getTraceAsString());
        }
        
        return 0;
    }
}