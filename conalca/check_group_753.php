<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "   ANÁLISIS GRUPO 753\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$group = DB::table('group_cotizations')->where('id', 753)->first();
if (!$group) {
    echo "❌ Grupo 753 no encontrado\n";
    exit(1);
}

echo "📊 EXTRACTED_DATA:\n";
echo "─────────────────────────────────────────────────────────────\n";
if ($group->extracted_data) {
    $data = json_decode($group->extracted_data, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if (isset($data['multi_ruta']) && $data['multi_ruta'] === true) {
        echo "✅ Es multi-ruta con " . count($data['rutas'] ?? []) . " rutas\n\n";
    }
} else {
    echo "NULL\n\n";
}

echo "🗄️ COTIZACIONES EN BD:\n";
echo "─────────────────────────────────────────────────────────────\n";
$cots = DB::table('cotizacion_models')->where('group_cotization_id', 753)->orderBy('ruta_numero')->get();
echo "Total: " . $cots->count() . " cotizaciones\n\n";

foreach ($cots as $cot) {
    echo "Ruta #{$cot->ruta_numero} (ID: {$cot->id}):\n";
    echo "  Origen: " . ($cot->ciudad_origen ?? 'NULL') . "\n";
    echo "  Destino: " . ($cot->ciudad_destino ?? 'NULL') . "\n";
    echo "  Peso: " . ($cot->peso_kg ?? 'NULL') . " kg\n";
    echo "  Producto: " . ($cot->tipo_producto ?? 'NULL') . "\n";
    echo "  Vehiculo: " . ($cot->vehiculo_requerido ?? 'NULL') . "\n";
    echo "  Created: {$cot->created_at}\n";
    echo "  Updated: {$cot->updated_at}\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
