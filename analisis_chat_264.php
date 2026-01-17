<?php

use App\Models\ConversationMessage;
use App\Models\GroupCotization;
use App\Models\ConversationSession;

require __DIR__ . '/conalca/vendor/autoload.php';

$app = require_once __DIR__ . '/conalca/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$groupId = 264;

echo "Análisis de Cotización #$groupId\n";
echo "================================\n";

$group = GroupCotization::find($groupId);

if (!$group) {
    echo "❌ Grupo no encontrado. Buscando como session_id...\n";
    $session = ConversationSession::find($groupId);
    if ($session) {
        echo "✅ Encontrado como Session ID. Group ID asociado: " . ($session->group_cotization_id ?? 'N/A') . "\n";
        $messages = $session->messages()->orderBy('created_at')->get();
    } else {
        echo "❌ Tampoco encontrado como Session ID.\n";
        exit;
    }
} else {
    echo "✅ Grupo encontrado.\n";
    // Buscar mensajes directamente por group_cotization_id
    $messages = ConversationMessage::where('group_cotization_id', $groupId)
        ->orderBy('created_at')
        ->get();
}

if ($messages->isEmpty()) {
    echo "⚠️ No se encontraron mensajes con group_cotization_id = $groupId.\n";
    echo "Buscando sesiones que contengan este grupo en metadatos...\n";
    
    // Fallback: buscar en metadata de sesiones
    $sessions = ConversationSession::where('metadata', 'LIKE', "%\"current_group_id\":$groupId%")
        ->orWhere('metadata', 'LIKE', "%\"group_id\":$groupId%")
        ->orWhere('cotizacion_id', $groupId) // A veces se usa cotizacion_id como group_id por error
        ->get();
        
    foreach ($sessions as $session) {
        echo "🔍 Sesión encontrada por metadata: ID " . $session->id . "\n";
        $msgs = $session->messages()->orderBy('created_at')->get();
        foreach ($msgs as $msg) $messages->push($msg);
    }
}

if ($messages->isNotEmpty()) {
    foreach ($messages as $msg) {
        echo "\n------------------------------------------------\n";
        echo "[$msg->role] " . $msg->created_at . "\n";
        echo "Content: " . $msg->content . "\n";
        
        // Ver si hay metadata o structured data en el mensaje del asistente
        // (Depende de cómo se guarde, a veces es JSON en content, a veces es texto)
    }
}
