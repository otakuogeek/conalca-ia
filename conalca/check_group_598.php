<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$group = DB::table('group_cotizations')->where('id', 598)->first();
if (!$group) {
    echo '❌ Grupo 598 no encontrado' . PHP_EOL;
    exit;
}

echo '📋 GRUPO 598' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
echo 'ID: ' . $group->id . PHP_EOL;
echo 'User ID: ' . $group->user_id . PHP_EOL;
echo 'Created: ' . $group->created_at . PHP_EOL;
echo PHP_EOL;

// Ver cotizaciones asociadas
$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', 598)
    ->orderBy('id')
    ->get();

echo '📦 COTIZACIONES (' . count($cotizaciones) . '):' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
foreach ($cotizaciones as $cot) {
    echo 'ID: ' . $cot->id . PHP_EOL;
    echo '  Origen: ' . $cot->ciudad_origen . PHP_EOL;
    echo '  Destino: ' . $cot->ciudad_destino . PHP_EOL;
    echo '  Producto: ' . ($cot->tipo_producto ?? 'N/A') . PHP_EOL;
    echo '  Peso: ' . ($cot->peso_mercancia ?? 'N/A') . ' kg' . PHP_EOL;
    echo '  Created: ' . $cot->created_at . PHP_EOL;
    echo '  Updated: ' . $cot->updated_at . PHP_EOL;
    echo PHP_EOL;
}

// Ver extracted_data
echo '📊 EXTRACTED_DATA:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
if ($group->extracted_data) {
    $data = json_decode($group->extracted_data, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

// Ver mensajes de chat del grupo
echo PHP_EOL;
echo '💬 MENSAJES DEL CHAT:' . PHP_EOL;
echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
$messages = DB::table('messages')
    ->where('group_cotization_id', 598)
    ->orderBy('id')
    ->get();

foreach ($messages as $msg) {
    echo '[' . $msg->created_at . '] ';
    echo ($msg->sender === 'user' ? '👤 Usuario' : '🤖 Asistente') . ':' . PHP_EOL;
    echo substr($msg->content, 0, 200) . (strlen($msg->content) > 200 ? '...' : '') . PHP_EOL;
    echo PHP_EOL;
}
