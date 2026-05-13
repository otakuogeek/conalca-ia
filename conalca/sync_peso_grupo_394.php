<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "SINCRONIZANDO peso_mercancia DE COTIZACIONES CON peso_kg DE EXTRACTED_DATA\n";
echo str_repeat('=', 79) . "\n\n";

$group = DB::table('group_cotizations')->where('id', 394)->first();
$extracted = json_decode($group->extracted_data, true);

$cotizaciones = DB::table('cotizacion_models')
    ->where('group_cotization_id', 394)
    ->orderBy('id')
    ->get();

foreach ($cotizaciones as $index => $cot) {
    $extractedRoute = $extracted[$index] ?? null;
    
    if (!$extractedRoute) {
        echo "⚠️  Cotización ID {$cot->id} no tiene extracted_data correspondiente\n";
        continue;
    }
    
    $pesoActual = $cot->peso_mercancia;
    $pesoCorrect = $extractedRoute['peso_kg'];
    
    if ($pesoActual != $pesoCorrect) {
        echo "🔧 Cotización ID {$cot->id} (Ruta " . ($index + 1) . "):\n";
        echo "   Peso actual: {$pesoActual} kg → Corrigiendo a: {$pesoCorrect} kg\n";
        
        DB::table('cotizacion_models')
            ->where('id', $cot->id)
            ->update(['peso_mercancia' => $pesoCorrect]);
        
        echo "   ✅ Actualizado\n\n";
    } else {
        echo "✅ Cotización ID {$cot->id} (Ruta " . ($index + 1) . "): peso correcto ({$pesoActual} kg)\n";
    }
}

echo "\n" . str_repeat('=', 79) . "\n";
echo "SINCRONIZACIÓN COMPLETADA\n";
