<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$group = DB::table('group_cotizations')->where('id', 461)->first();
if (!$group) {
    echo "❌ Grupo 461 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DETALLADO GRUPO 461\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

echo "INSTRUCCIÓN ORIGINAL DEL USUARIO:\n";
echo "-------------------------------------------------------------------------------\n";
$promptField = $group->initial_prompt ?? $group->prompt ?? $group->user_prompt ?? 'N/A';
echo $promptField . "\n\n";

echo "DATOS EXTRAÍDOS:\n";
echo "-------------------------------------------------------------------------------\n";
if (is_array($extracted)) {
    foreach ($extracted as $idx => $ruta) {
        $num = $idx + 1;
        echo "Ruta $num:\n";
        echo "  Origen: " . ($ruta['origen'] ?? 'N/A') . "\n";
        echo "  Destino: " . ($ruta['destino'] ?? 'N/A') . "\n";
        echo "  Peso: " . ($ruta['peso_kg'] ?? 'N/A') . " kg\n";
        echo "  Incluye tara: " . (($ruta['incluye_tara'] ?? false) ? 'SÍ' : 'NO') . "\n";
        echo "  Producto: " . ($ruta['producto'] ?? 'N/A') . "\n";
        echo "  Cantidad: " . ($ruta['cantidad'] ?? 'N/A') . "\n";
        echo "  Empaque: " . ($ruta['empaque'] ?? 'N/A') . "\n";
        echo "  Vehículo: " . ($ruta['vehiculo'] ?? 'N/A') . "\n";
        echo "  Valor declarado: " . ($ruta['valor_declarado'] ?? 'N/A') . "\n\n";
    }
} else {
    print_r($extracted);
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE PROBLEMAS:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Analizar problemas de tara
foreach ($extracted as $idx => $ruta) {
    $num = $idx + 1;
    $peso = $ruta['peso_kg'] ?? 0;
    $incluyeTara = $ruta['incluye_tara'] ?? false;
    
    echo "Ruta $num - ANÁLISIS DE TARA:\n";
    echo "  Peso actual: $peso kg\n";
    echo "  Incluye tara: " . ($incluyeTara ? 'SÍ' : 'NO') . "\n";
    
    // El usuario dijo "sin tara" en el prompt
    if (!$incluyeTara && $peso > 0) {
        $pesoConTara = $peso + 3400;
        echo "  ⚠️ PROBLEMA: El usuario indicó 'sin tara' pero NO se sumó la tara\n";
        echo "  ✅ SOLUCIÓN: El peso debería ser $pesoConTara kg ($peso kg + 3400 kg tara)\n";
    }
    echo "\n";
}

// Analizar problema de producto (televisores)
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS DE PRODUCTO:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

foreach ($extracted as $idx => $ruta) {
    $num = $idx + 1;
    $producto = $ruta['producto'] ?? 'N/A';
    
    echo "Ruta $num:\n";
    echo "  Producto extraído: $producto\n";
    
    // La primera ruta habla de televisores
    if ($idx === 0) {
        if (stripos($producto, 'televisor') === false && stripos($producto, 'electrónico') === false) {
            echo "  ⚠️ PROBLEMA: El usuario mencionó 'televisores' pero se extrajo '$producto'\n";
            echo "  ✅ SOLUCIÓN: Debería reconocer 'TELEVISORES' o 'PRODUCTOS ELECTRÓNICOS'\n";
        } else {
            echo "  ✅ OK: El producto fue reconocido correctamente\n";
        }
    }
    echo "\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "COTIZACIONES CREADAS:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', 461)
    ->orderBy('id')
    ->get();

foreach ($cotizaciones as $idx => $cot) {
    $num = $idx + 1;
    echo "Cotización $num (ID: $cot->id):\n";
    echo "  Origen: $cot->ciudad_origen\n";
    echo "  Destino: $cot->ciudad_destino\n";
    echo "  Peso: $cot->peso_mercancia kg\n";
    echo "  Producto: $cot->producto\n";
    echo "  Vehículo: $cot->vehiculo_requerido\n\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "HISTORIAL DE CHAT:\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$chatHistory = DB::table('chat_histories')
    ->where('group_cotization_id', 461)
    ->orderBy('created_at')
    ->get();

foreach ($chatHistory as $chat) {
    echo "[" . strtoupper($chat->role) . "]: $chat->message\n\n";
}
