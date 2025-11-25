<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pricing = DB::table('pricings')->first();
echo $pricing ? 'Pricing ID: ' . $pricing->id : 'No hay pricings';