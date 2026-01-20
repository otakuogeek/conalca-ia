<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$group = DB::table('group_cotizations')->where('id', 397)->first();
if (!$group) {
    echo "Grupo 397 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "GRUPO 397 - ANÁLISIS COMPLETO DE TARA\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

echo "EXTRACTED_DATA:\n";
echo "-------------------------------------------------------------------------------\n";
if (isset($extracted['rutas'])) {
    foreach ($extracted['rutas'] as $idx => $ruta) {
        $num = $idx + 1;
        echo "RUTA $num:\n";
        echo "  Origen: " . ($ruta['origen'] ?? $ruta['ciudad_origen'] ?? 'N/A') . "\n";
        echo "  Destino: " . ($ruta['destino'] ?? $ruta['ciudad_destino'] ?? 'N/A') . "\n";
        echo "  Peso: " . ($ruta['peso_kg'] ?? 'N/A') . " kg\n";
        echo "  Vehículo: " . ($ruta['vehiculo_requerido'] ?? $ruta['vehiculo'] ?? 'N/A') . "\n";
        
        if (isset($ruta['peso_kg'])) {
            $peso = $ruta['peso_kg'];
            $peso_sin_tara = $peso - 3400;
            $modulo = $peso_sin_tara % 1000;
            echo "  Análisis: ($peso - 3400) % 1000 = $modulo";
            if ($modulo == 0) {
                echo " ⚠️ POSIBLE TARA DUPLICADA\n";
                $peso_correcto = $peso - 3400;
                echo "  Peso correcto debería ser: $peso_correcto kg\n";
            } else {
                echo " ✅ Tara OK\n";
            }
        }
        echo "\n";
    }
}

echo "\nCHAT HISTORY COMPLETO:\n";
echo "-------------------------------------------------------------------------------\n";
$messages = json_decode($group->chat_history, true) ?? [];

foreach ($messages as $idx => $msg) {
    $num = $idx + 1;
    $role = strtoupper($msg['role'] ?? 'unknown');
    $content = $msg['content'] ?? '';
    
    echo "\n[$num] $role:\n";
    echo "$content\n";
    echo str_repeat('-', 79) . "\n";
}

echo "\n\nRESUMEN:\n";
echo "===============================================================================\n";
echo "⚠️ Rutas con posible duplicación de tara:\n";

if (isset($extracted['rutas'])) {
    foreach ($extracted['rutas'] as $idx => $ruta) {
        $num = $idx + 1;
        if (isset($ruta['peso_kg'])) {
            $peso = $ruta['peso_kg'];
            $peso_sin_tara = $peso - 3400;
            $modulo = $peso_sin_tara % 1000;
            if ($modulo == 0) {
                $peso_correcto = $peso - 3400;
                echo "  Ruta $num: $peso kg → debería ser $peso_correcto kg\n";
            }
        }
    }
}
