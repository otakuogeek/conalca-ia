<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ElevenLabsCallService;
use App\Models\Llamada;

echo "=== Verificar estado de última llamada en ElevenLabs ===\n\n";

try {
    // Obtener la última llamada
    $llamada = Llamada::orderBy('id_llamada', 'desc')->first();
    
    if (!$llamada) {
        echo "❌ No se encontraron llamadas\n";
        exit(1);
    }
    
    echo "Llamada ID: {$llamada->id_llamada}\n";
    echo "Número destino: {$llamada->numero_destino}\n";
    echo "Estado actual: {$llamada->status}\n";
    echo "Call Status: {$llamada->call_status}\n";
    echo "Conversation ID: {$llamada->elevenlabs_conversation_id}\n";
    echo "SIP Call ID: {$llamada->elevenlabs_sip_call_id}\n\n";
    
    if (!$llamada->elevenlabs_conversation_id) {
        echo "⚠️ Esta llamada no tiene Conversation ID de ElevenLabs\n";
        exit(0);
    }
    
    echo "Consultando estado en ElevenLabs...\n\n";
    
    $elevenLabsService = app(ElevenLabsCallService::class);
    
    $details = $elevenLabsService->getConversationDetails($llamada->elevenlabs_conversation_id);
    
    if (isset($details['success']) && $details['success']) {
        echo "✅ Detalles de la conversación:\n\n";
        
        $conversation = $details['conversation'] ?? [];
        
        echo "ID: " . ($conversation['conversation_id'] ?? 'N/A') . "\n";
        echo "Agent ID: " . ($conversation['agent_id'] ?? 'N/A') . "\n";
        echo "Status: " . ($conversation['status'] ?? 'N/A') . "\n";
        
        if (isset($conversation['metadata'])) {
            echo "\nMetadata:\n";
            foreach ($conversation['metadata'] as $key => $value) {
                echo "  {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }
        
        if (isset($conversation['analysis'])) {
            echo "\nAnálisis:\n";
            $analysis = $conversation['analysis'];
            echo "  Call exitosa: " . (($analysis['call_successful'] ?? false) ? 'SÍ' : 'NO') . "\n";
            
            if (isset($analysis['transcript'])) {
                echo "\nTranscripción:\n";
                echo "  " . substr($analysis['transcript'], 0, 200) . "...\n";
            }
        }
        
        if (isset($conversation['call_details'])) {
            echo "\nDetalles de la llamada:\n";
            $callDetails = $conversation['call_details'];
            echo "  Duración: " . ($callDetails['duration_seconds'] ?? 'N/A') . " segundos\n";
            echo "  Costo: $" . ($callDetails['cost'] ?? 'N/A') . "\n";
        }
        
    } else {
        echo "❌ Error al obtener detalles:\n";
        print_r($details);
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
