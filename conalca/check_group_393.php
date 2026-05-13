<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$group = DB::table('group_cotizations')->where('id', 393)->first();

if (!$group) {
    echo "Grupo 393 no encontrado\n";
    exit(1);
}

$extracted = json_decode($group->extracted_data, true);

echo "GRUPO 393 - EXTRACTED DATA:\n";
echo str_repeat('=', 80) . "\n\n";

if (isset($extracted[0]) && is_array($extracted[0])) {
    foreach ($extracted as $idx => $ruta) {
        echo "RUTA " . ($idx + 1) . ":\n";
        echo str_repeat('-', 80) . "\n";
        echo "Origen: " . ($ruta['origen'] ?? 'N/A') . "\n";
        echo "Destino: " . ($ruta['destino'] ?? 'N/A') . "\n";
        echo "Peso (kg): " . ($ruta['peso_kg'] ?? 'N/A') . "\n";
        echo "Incluye tara: " . (isset($ruta['incluye_tara']) ? ($ruta['incluye_tara'] ? 'SÍ' : 'NO') : 'N/A') . "\n";
        echo "Cantidad: " . ($ruta['cantidad'] ?? 'N/A') . "\n";
        echo "Empaque: " . ($ruta['empaque'] ?? 'N/A') . "\n";
        echo "Vehículo: " . ($ruta['vehiculo'] ?? 'N/A') . "\n";
        echo "Producto: " . ($ruta['producto'] ?? 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "Formato incorrecto o ruta única\n";
    print_r($extracted);
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "ANÁLISIS DE PESOS:\n";
echo str_repeat('=', 80) . "\n\n";

$pesosEsperados = [
    ['ruta' => 1, 'origen' => 'BOGOTA', 'destino' => 'BUCARAMANGA', 'peso_esperado' => 6000, 'incluye_tara' => true, 'razon' => '6 ton, YA incluye tara'],
    ['ruta' => 2, 'origen' => 'CALI', 'destino' => 'RIOHACHA', 'peso_esperado' => 15400, 'incluye_tara' => false, 'razon' => '12 ton + 3400kg tara = 15400kg'],
    ['ruta' => 3, 'origen' => 'CARTAGENA', 'destino' => 'FUNZA', 'peso_esperado' => 27400, 'incluye_tara' => false, 'razon' => '24 ton + 3400kg tara = 27400kg'],
];

foreach ($pesosEsperados as $esperado) {
    $idx = $esperado['ruta'] - 1;
    $rutaActual = $extracted[$idx] ?? null;
    
    if (!$rutaActual) {
        echo "❌ Ruta {$esperado['ruta']}: NO ENCONTRADA\n\n";
        continue;
    }
    
    $pesoActual = $rutaActual['peso_kg'] ?? 0;
    $incluyeTaraActual = $rutaActual['incluye_tara'] ?? false;
    
    $pesoOk = ($pesoActual == $esperado['peso_esperado']);
    $taraOk = ($incluyeTaraActual == $esperado['incluye_tara']);
    
    echo ($pesoOk && $taraOk ? "✅" : "❌") . " Ruta {$esperado['ruta']}: {$esperado['origen']} → {$esperado['destino']}\n";
    echo "   Peso esperado: {$esperado['peso_esperado']} kg | Peso actual: {$pesoActual} kg " . ($pesoOk ? "✅" : "❌") . "\n";
    echo "   Incluye tara esperado: " . ($esperado['incluye_tara'] ? 'SÍ' : 'NO') . " | Actual: " . ($incluyeTaraActual ? 'SÍ' : 'NO') . " " . ($taraOk ? "✅" : "❌") . "\n";
    echo "   Razón: {$esperado['razon']}\n\n";
}
