<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE COTIZACIONES 1910, 1911, 1912, 1913 ===\n\n";

$ids = [1919, 1920, 1921, 1922];

foreach ($ids as $id) {
    echo "--- COTIZACIÓN ID: $id ---\n";
    
    $quote = DB::table('cotizacion_models')->where('id', $id)->first();
    
    if (!$quote) {
        echo "❌ NO ENCONTRADA\n";
    } else {
        echo "✅ ENCONTRADA\n";
        echo "   ID: {$quote->id}\n";
        echo "   Created At: {$quote->created_at}\n";
        
        // Verificar si fue creada por API
        if (isset($quote->api_source) || isset($quote->source)) {
            echo "   Source: " . ($quote->api_source ?? $quote->source ?? 'N/A') . "\n";
        }
        
        // Verificar fecha_cargue
        if (isset($quote->fecha_cargue)) {
            echo "   Fecha Cargue: {$quote->fecha_cargue}\n";
        } else {
            echo "   Fecha Cargue: ⚠️ NO DISPONIBLE\n";
        }
        
        // Mostrar todos los campos
        echo "   Campos completos:\n";
        foreach ((array)$quote as $field => $value) {
            if ($value !== null && $value !== '') {
                echo "     - {$field}: " . (is_string($value) && strlen($value) > 60 ? substr($value, 0, 60) . '...' : $value) . "\n";
            }
        }
    }
    echo "\n";
}

// Verificar si tienen información de API
echo "=== ANÁLISIS DE API SOURCE ===\n";

$quotesData = DB::table('cotizacion_models')
    ->whereIn('id', $ids)
    ->select('id', 'created_at', 'api_source', 'source', 'fecha_cargue')
    ->get();

echo "Total de cotizaciones encontradas: " . count($quotesData) . "\n\n";

foreach ($quotesData as $q) {
    $apiSource = $q->api_source ?? $q->source ?? 'SIN FUENTE';
    $fechaCargue = $q->fecha_cargue ?? 'SIN FECHA';
    echo "ID {$q->id}: API='{$apiSource}' | Fecha Cargue='{$fechaCargue}'\n";
}
