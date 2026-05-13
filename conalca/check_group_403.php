<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$group = DB::table('group_cotizations')->where('id', 403)->first();
if (!$group) {
    echo "Grupo 403 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "GRUPO 403 - VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$extracted = json_decode($group->extracted_data, true);

echo "EXTRACTED_DATA (todas las rutas):\n";
echo "-------------------------------------------------------------------------------\n";
foreach ($extracted as $idx => $ruta) {
    $num = $idx + 1;
    echo "Ruta $num:\n";
    echo "  Vehículo: " . ($ruta['vehiculo'] ?? 'NO SET') . "\n";
    echo "  Vehiculo_requerido: " . ($ruta['vehiculo_requerido'] ?? 'NO SET') . "\n";
    echo "  ClaseVehiculo: " . ($ruta['claseVehiculo'] ?? 'NO SET') . "\n";
    echo "  Producto: " . ($ruta['producto'] ?? 'NO SET') . "\n";
    echo "  Producto_mencionado: " . ($ruta['producto_mencionado'] ?? 'NO SET') . "\n";
    echo "  Cantidad: " . ($ruta['cantidad'] ?? 'NO SET') . "\n";
    echo "  Empaque: " . ($ruta['empaque'] ?? 'NO SET') . "\n";
    echo "\n";
}

echo "\nCOTIZACION_MODELS (tabla):\n";
echo "-------------------------------------------------------------------------------\n";
$cots = DB::table('cotizacion_models')
    ->where('group_cotization_id', 403)
    ->orderBy('id')
    ->get();

foreach ($cots as $idx => $cot) {
    $num = $idx + 1;
    echo "Ruta $num (ID $cot->id):\n";
    echo "  Vehículo: $cot->vehiculo_requerido\n";
    echo "  Producto: " . ($cot->producto ?? 'N/A') . "\n";
    echo "  Cantidad: $cot->cantidad\n";
    echo "\n";
}
