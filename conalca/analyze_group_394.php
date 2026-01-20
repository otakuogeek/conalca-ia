<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS GRUPO 394 - CAMBIO INESPERADO DE PESO\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// 1. Ver estado actual
$group = DB::table('group_cotizations')->where('id', 394)->first();
if (!$group) {
    echo "❌ Grupo 394 no encontrado\n";
    exit(1);
}

$extracted = json_decode($group->extracted_data, true);

echo "ESTADO ACTUAL DEL EXTRACTED_DATA:\n";
echo str_repeat('-', 79) . "\n";
if (isset($extracted[0]) && is_array($extracted[0])) {
    foreach ($extracted as $idx => $ruta) {
        echo "Ruta " . ($idx + 1) . ": {$ruta['origen']} → {$ruta['destino']}\n";
        echo "  Peso: {$ruta['peso_kg']} kg | Incluye tara: " . ($ruta['incluye_tara'] ? 'SÍ' : 'NO') . "\n";
        echo "  Producto: {$ruta['producto']} | Cantidad: {$ruta['cantidad']}\n\n";
    }
} else {
    echo "Ruta única:\n";
    echo "Origen: {$extracted['origen']} → Destino: {$extracted['destino']}\n";
    echo "Peso: {$extracted['peso_kg']} kg | Incluye tara: " . ($extracted['incluye_tara'] ? 'SÍ' : 'NO') . "\n";
}

echo "\n" . str_repeat('═', 79) . "\n";
echo "HISTORIAL DE CHAT:\n";
echo str_repeat('═', 79) . "\n\n";

// 2. Ver historial de chat
$messages = DB::table('conversation_messages')
    ->where('group_cotization_id', 394)
    ->orderBy('id', 'asc')
    ->get();

foreach ($messages as $msg) {
    $role = strtoupper($msg->role);
    $time = date('H:i:s', strtotime($msg->created_at));
    echo "[{$time}] {$role}:\n";
    echo str_repeat('-', 79) . "\n";
    echo $msg->content . "\n\n";
}

echo str_repeat('═', 79) . "\n";
echo "LOGS RELACIONADOS CON GRUPO 394:\n";
echo str_repeat('═', 79) . "\n\n";

// 3. Buscar en logs menciones al grupo 394
$logFile = '/home/ubuntu/conalca/conalca/storage/logs/laravel.log';
$logLines = file($logFile);

$foundLines = [];
foreach ($logLines as $line) {
    if (stripos($line, '394') !== false && 
        (stripos($line, 'group') !== false || 
         stripos($line, 'peso') !== false ||
         stripos($line, 'TARA') !== false ||
         stripos($line, 'edición') !== false)) {
        $foundLines[] = $line;
    }
}

$lastLines = array_slice($foundLines, -50);
foreach ($lastLines as $line) {
    echo $line;
}

if (empty($foundLines)) {
    echo "No se encontraron logs relacionados con grupo 394\n";
}
