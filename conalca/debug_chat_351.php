<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$session = \App\Models\ConversationSession::with(['messages' => function($q) {
    $q->orderBy('created_at', 'asc');
}])->find(351);

if (!$session) {
    echo "❌ Sesión 351 no encontrada\n";
    exit(1);
}

echo "📋 CONVERSACIÓN #351\n";
echo "Estado: {$session->status}\n";
echo "Grupo asociado: {$session->group_cotization_id}\n\n";

echo "📝 MENSAJES:\n";
echo str_repeat('=', 80) . "\n";
foreach ($session->messages as $msg) {
    echo "\n[{$msg->role}] ({$msg->created_at})\n";
    echo "Contenido: " . substr($msg->content, 0, 300) . (strlen($msg->content) > 300 ? '...' : '') . "\n";
    
    if ($msg->metadata) {
        $meta = json_decode($msg->metadata, true);
        if (isset($meta['extracted_data'])) {
            echo "\n🔍 DATOS EXTRAÍDOS:\n";
            echo json_encode($meta['extracted_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
    echo str_repeat('-', 80) . "\n";
}
