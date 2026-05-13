<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVESTIGACIÓN GRUPO #523 ===\n\n";

// 1. Información del grupo
$grupo = DB::table('group_cotizations')->where('id', 523)->first();
if ($grupo) {
    echo "📋 GRUPO #523:\n";
    echo "  - Usuario: {$grupo->user_id}\n";
    echo "  - Creado: {$grupo->created_at}\n";
    echo "  - Actualizado: {$grupo->updated_at}\n\n";
} else {
    echo "❌ No se encontró el grupo #523\n";
    exit;
}

// 2. Rutas del grupo
$rutas = DB::table('cotizacion_models')
    ->where('group_cotization_id', 523)
    ->orderBy('id')
    ->get();

echo "📦 RUTAS DEL GRUPO (Total: " . count($rutas) . "):\n";
foreach ($rutas as $ruta) {
    echo "\n  Ruta #{$ruta->id}:\n";
    echo "    - Origen: {$ruta->ciudad_origen}\n";
    echo "    - Destino: {$ruta->ciudad_destino}\n";
    echo "    - Peso Mercancía: {$ruta->peso_mercancia} toneladas\n";
    echo "    - Cantidad: {$ruta->cantidad}\n";
    echo "    - Vehículo: {$ruta->tipo_vehiculo}\n";
    echo "    - Producto: {$ruta->tipo_producto}\n";
    echo "    - Valor Declarado: " . number_format($ruta->valor_declarado ?? 0) . "\n";
    echo "    - Creado: {$ruta->created_at}\n";
    echo "    - Actualizado: {$ruta->updated_at}\n";
}

// 3. Mensajes del chat (últimos 20)
echo "\n\n💬 CONVERSACIÓN DEL GRUPO (últimos 20 mensajes):\n";
echo str_repeat("=", 80) . "\n";

$mensajes = DB::table('conversation_messages as cm')
    ->join('conversation_sessions as cs', 'cm.session_id', '=', 'cs.id')
    ->where('cs.group_cotization_id', 523)
    ->select('cm.*')
    ->orderBy('cm.id', 'desc')
    ->limit(20)
    ->get()
    ->reverse();

foreach ($mensajes as $msg) {
    $sender = $msg->sender === 'user' ? '👤 USUARIO' : '🤖 ASISTENTE';
    echo "\n[{$msg->id}] {$sender} - {$msg->created_at}:\n";
    echo substr($msg->content, 0, 300);
    if (strlen($msg->content) > 300) {
        echo "...\n";
    } else {
        echo "\n";
    }
    echo str_repeat("-", 80) . "\n";
}

// 4. Revisar extracted_data de los mensajes del usuario
echo "\n\n🔍 EXTRACTED DATA (mensajes del usuario con datos extraídos):\n";
echo str_repeat("=", 80) . "\n";

$mensajesConData = DB::table('conversation_messages as cm')
    ->join('conversation_sessions as cs', 'cm.session_id', '=', 'cs.id')
    ->where('cs.group_cotization_id', 523)
    ->where('cm.sender', 'user')
    ->whereNotNull('cm.extracted_data')
    ->select('cm.*')
    ->orderBy('cm.id')
    ->get();

foreach ($mensajesConData as $msg) {
    echo "\n[{$msg->id}] {$msg->created_at}:\n";
    echo "Mensaje: " . substr($msg->content, 0, 150) . "\n";
    $extractedData = json_decode($msg->extracted_data, true);
    if ($extractedData) {
        echo "Extracted Data:\n";
        echo json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo str_repeat("-", 80) . "\n";
}

echo "\n\n✅ Investigación completada\n";
