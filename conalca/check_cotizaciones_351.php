<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GroupCotization;

echo "========== COTIZACIONES DEL GRUPO 351 ==========\n\n";

$grupo = GroupCotization::with('cotizaciones')->find(351);

if (!$grupo) {
    echo "❌ Grupo 351 no encontrado\n";
    exit(1);
}

echo "GRUPO #351:\n";
echo "  Cliente ID: {$grupo->client_id}\n";
echo "  Estado: {$grupo->status}\n";
echo "  Creado: {$grupo->created_at}\n";
echo "  Total cotizaciones: " . $grupo->cotizaciones->count() . "\n\n";

if ($grupo->cotizaciones->count() > 0) {
    foreach ($grupo->cotizaciones as $index => $cot) {
        $num = $index + 1;
        echo str_repeat('=', 80) . "\n";
        echo "COTIZACIÓN #{$num} (ID: {$cot->id}):\n";
        echo "  Origen: {$cot->ciudad_origen}\n";
        echo "  Destino: {$cot->ciudad_destino}\n";
        echo "  Peso: {$cot->peso_mercancia} kg\n";
        echo "  Tipo Producto: " . ($cot->tipo_producto ?: 'NULL') . "\n";
        echo "  Product ID: " . ($cot->product_id ?: 'NULL') . "\n";
        echo "  Valor declarado: {$cot->valor_declarado}\n";
        echo str_repeat('=', 80) . "\n\n";
    }
} else {
    echo "❌ No hay cotizaciones en este grupo\n";
}
