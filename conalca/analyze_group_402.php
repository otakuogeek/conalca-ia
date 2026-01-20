<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$group = DB::table('group_cotizations')->where('id', 402)->first();
if (!$group) {
    echo "Grupo 402 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "GRUPO 402 - ANÁLISIS COMPLETO\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

echo "EXTRACTED_DATA ACTUAL:\n";
echo "-------------------------------------------------------------------------------\n";
if (is_array($extracted) && isset($extracted[0])) {
    foreach ($extracted as $idx => $ruta) {
        $num = $idx + 1;
        echo "Ruta $num:\n";
        echo "  Origen: " . ($ruta['origen'] ?? $ruta['ciudad_origen'] ?? 'N/A') . "\n";
        echo "  Destino: " . ($ruta['destino'] ?? $ruta['ciudad_destino'] ?? 'N/A') . "\n";
        echo "  Peso: " . ($ruta['peso_kg'] ?? 'N/A') . " kg\n";
        echo "  Vehículo: " . ($ruta['vehiculo_requerido'] ?? $ruta['vehiculo'] ?? 'N/A') . "\n";
        echo "  Cantidad: " . ($ruta['cantidad'] ?? 'N/A') . "\n";
        echo "  Valor: " . ($ruta['valor_declarado'] ?? 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "  Origen: " . ($extracted['origen'] ?? $extracted['ciudad_origen'] ?? 'N/A') . "\n";
    echo "  Destino: " . ($extracted['destino'] ?? $extracted['ciudad_destino'] ?? 'N/A') . "\n";
    echo "  Peso: " . ($extracted['peso_kg'] ?? 'N/A') . " kg\n";
    echo "  Vehículo: " . ($extracted['vehiculo_requerido'] ?? $extracted['vehiculo'] ?? 'N/A') . "\n";
}

echo "\n\nCHAT COMPLETO:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";

// Buscar en conversation_sessions
$conv = DB::table('conversation_sessions')
    ->where('cotizacion_id', 402)
    ->orderBy('created_at', 'desc')
    ->first();
    
if ($conv) {
    $messages = json_decode($conv->messages, true) ?? [];
    
    foreach ($messages as $idx => $msg) {
        $num = $idx + 1;
        $role = strtoupper($msg['role'] ?? 'unknown');
        $content = $msg['content'] ?? '';
        
        echo "\n[$num] $role:\n";
        echo "───────────────────────────────────────────────────────────────────────────────\n";
        echo "$content\n";
    }
} else {
    echo "No se encontró conversación en conversation_sessions\n";
}

echo "\n\n═══════════════════════════════════════════════════════════════════════════════\n";
