<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver grupo 522
$grupo = DB::table('group_cotizations')->where('id', 522)->first();
if (!$grupo) {
    echo "Grupo 522 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "GRUPO #522\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Creado: {$grupo->created_at}\n";
echo "User ID: {$grupo->user_id}\n\n";

// Ver rutas
$rutas = DB::table('cotizacion_models')
    ->where('group_cotization_id', 522)
    ->orderBy('id')
    ->get();

echo "RUTAS (" . count($rutas) . "):\n";
echo "───────────────────────────────────────────────────────────────\n";
foreach ($rutas as $i => $ruta) {
    echo "Ruta #{$ruta->id}:\n";
    echo "  Origen: {$ruta->ciudad_origen}\n";
    echo "  Destino: {$ruta->ciudad_destino}\n";
    echo "  Peso: {$ruta->peso_kg} kg\n";
    echo "  Tara: {$ruta->tara_kg} kg\n";
    echo "  Producto: " . ($ruta->tipo_producto ?? 'N/A') . "\n";
    echo "  Creada: {$ruta->created_at}\n";
    echo "  Actualizada: {$ruta->updated_at}\n\n";
}

// Ver conversación asociada al grupo
$conversations = DB::table('conversation_sessions')
    ->join('conversation_messages', 'conversation_sessions.id', '=', 'conversation_messages.conversation_session_id')
    ->where('conversation_sessions.user_id', $grupo->user_id)
    ->whereDate('conversation_sessions.created_at', '>=', date('Y-m-d', strtotime($grupo->created_at . ' -1 day')))
    ->select('conversation_sessions.id as session_id', 'conversation_sessions.session_id as session_key', 'conversation_messages.*')
    ->orderBy('conversation_messages.id')
    ->get();

echo "═══════════════════════════════════════════════════════════════\n";
echo "MENSAJES DE CONVERSACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($conversations->isEmpty()) {
    echo "No se encontraron mensajes\n";
} else {
    $currentSession = null;
    $msgNum = 1;
    
    foreach ($conversations as $msg) {
        if ($currentSession !== $msg->session_key) {
            if ($currentSession !== null) {
                echo "\n";
            }
            $currentSession = $msg->session_key;
            echo "Session: {$msg->session_key}\n";
            echo "───────────────────────────────────────────────────────────────\n";
            $msgNum = 1;
        }
        
        echo "\n[{$msgNum}] {$msg->role} ({$msg->created_at}):\n";
        echo str_repeat("─", 60) . "\n";
        echo $msg->content . "\n";
        $msgNum++;
    }
}
