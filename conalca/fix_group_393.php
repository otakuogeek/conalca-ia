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

echo "ESTADO ANTES:\n";
echo "Ruta 2: Peso={$extracted[1]['peso_kg']}, incluye_tara=" . ($extracted[1]['incluye_tara'] ? 'true' : 'false') . "\n";
echo "Ruta 3: Peso={$extracted[2]['peso_kg']}, incluye_tara=" . ($extracted[2]['incluye_tara'] ? 'true' : 'false') . "\n\n";

// Corregir Ruta 2: 12 ton + 3.4 ton tara = 15.4 ton = 15400 kg
$extracted[1]['peso_kg'] = 15400;
$extracted[1]['incluye_tara'] = true; // Después de sumar tara, sí incluye tara

// Corregir Ruta 3: 24 ton + 3.4 ton tara = 27.4 ton = 27400 kg  
$extracted[2]['peso_kg'] = 27400;
$extracted[2]['incluye_tara'] = true; // Después de sumar tara, sí incluye tara

// Corregir vehículo de Ruta 3: debe ser TRACTOMULA no MINIMULA
$extracted[2]['vehiculo'] = 'TRACTOMULA';
$extracted[2]['vehiculo_requerido'] = 'TRACTOMULA';
$extracted[2]['claseVehiculo'] = 'TRACTOMULA';

DB::table('group_cotizations')
    ->where('id', 393)
    ->update(['extracted_data' => json_encode($extracted)]);

echo "ESTADO DESPUÉS:\n";
echo "Ruta 2: Peso={$extracted[1]['peso_kg']}, incluye_tara=" . ($extracted[1]['incluye_tara'] ? 'true' : 'false') . "\n";
echo "Ruta 3: Peso={$extracted[2]['peso_kg']}, incluye_tara=" . ($extracted[2]['incluye_tara'] ? 'true' : 'false') . ", vehículo={$extracted[2]['vehiculo']}\n\n";

echo "✅ Grupo 393 corregido\n";
