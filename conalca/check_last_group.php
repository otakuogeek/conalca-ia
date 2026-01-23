<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Buscar el último grupo creado
$lastGroup = App\Models\GroupCotization::orderBy('id', 'desc')->first();

if (!$lastGroup) {
    echo "❌ No hay grupos" . PHP_EOL;
    exit;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📋 ÚLTIMO GRUPO: " . $lastGroup->id . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo PHP_EOL;

echo "📩 Mensaje original:" . PHP_EOL;
echo $lastGroup->original_message . PHP_EOL;
echo PHP_EOL;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📊 extracted_data:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
$extractedData = json_decode($lastGroup->extracted_data, true);
echo json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo PHP_EOL;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "🚚 Cotizaciones en base de datos:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;

$cotizaciones = App\Models\CotizacionModel::where('group_cotization_id', $lastGroup->id)->get();
echo "Total: " . $cotizaciones->count() . " rutas" . PHP_EOL;
echo PHP_EOL;

foreach ($cotizaciones as $index => $cot) {
    echo "Ruta " . ($index + 1) . ":" . PHP_EOL;
    echo "  Origen: " . ($cot->ciudad_origen ?? 'N/A') . PHP_EOL;
    echo "  Destino: " . ($cot->ciudad_destino ?? 'N/A') . PHP_EOL;
    echo "  Producto: " . ($cot->tipo_producto ?? 'N/A') . PHP_EOL;
    echo "  Peso: " . ($cot->peso_mercancia ?? 'N/A') . PHP_EOL;
    echo "  Empaque: " . ($cot->tipo_embajale ?? 'N/A') . PHP_EOL;
    echo "  Valor: " . ($cot->valor_declarado ?? 'N/A') . PHP_EOL;
    echo PHP_EOL;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📱 ESTRUCTURA PARA EL FRONTEND:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo PHP_EOL;
echo "¿Es array indexado? " . (isset($extractedData[0]) ? "✅ Sí" : "❌ No") . PHP_EOL;
echo "¿Tiene múltiples rutas? " . (count($extractedData) > 1 && isset($extractedData[0]) ? "✅ Sí (" . count($extractedData) . " rutas)" : "❌ No") . PHP_EOL;
