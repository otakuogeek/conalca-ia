<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ESTRUCTURA DE TABLA cotizacion_models ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM cotizacion_models');
foreach($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\n=== MUESTRA DE DATOS EXISTENTES ===\n\n";

$sample = DB::table('cotizacion_models')->limit(1)->first();
if ($sample) {
    echo "Campos en registro existente:\n";
    foreach((array)$sample as $field => $value) {
        echo "- {$field}: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
    }
} else {
    echo "No hay registros en la tabla\n";
}