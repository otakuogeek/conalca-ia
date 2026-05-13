<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\GroupCotization;

echo "========== REVISIÓN GRUPO 351 ==========\n\n";

// Buscar por ID 351
$grupo = GroupCotization::find(351);

if (!$grupo) {
    echo "❌ No se encontró el grupo 351\n";
    
    // Mostrar últimos grupos creados
    echo "\nÚltimos 5 grupos creados:\n";
    $ultimos = GroupCotization::orderBy('id', 'desc')->take(5)->get(['id', 'product', 'is_custom', 'product_custom']);
    foreach ($ultimos as $g) {
        echo "  - ID {$g->id}: product={$g->product}, custom={$g->is_custom}, product_custom={$g->product_custom}\n";
    }
    exit(1);
}

$rutas = [$grupo];

echo "Total de rutas encontradas: " . count($rutas) . "\n\n";

foreach ($rutas as $ruta) {
    echo str_repeat('=', 80) . "\n";
    echo "RUTA ID: {$ruta->id}\n";
    echo "Origen: {$ruta->origin_city_id} -> Destino: {$ruta->destination_city_id}\n";
    echo "Producto: " . ($ruta->product ?: 'NULL') . "\n";
    echo "Product ID: " . ($ruta->product_id ?: 'NULL') . "\n";
    echo "Es personalizado: " . ($ruta->is_custom ? 'SÍ' : 'NO') . "\n";
    echo "Producto personalizado: " . ($ruta->product_custom ?: 'NULL') . "\n";
    echo "Estado: {$ruta->status}\n";
    echo str_repeat('=', 80) . "\n\n";
}
