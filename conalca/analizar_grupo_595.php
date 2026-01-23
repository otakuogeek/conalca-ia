<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$g = App\Models\GroupCotization::find(595);
if (!$g) {
    echo "❌ Grupo 595 no encontrado" . PHP_EOL;
    exit;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📋 GRUPO 595 - ANÁLISIS COMPLETO" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo PHP_EOL;

echo "📩 MENSAJE ORIGINAL:" . PHP_EOL;
echo $g->original_message . PHP_EOL;
echo PHP_EOL;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📊 EXTRACTED_DATA:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
$extractedData = json_decode($g->extracted_data, true);
echo json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo PHP_EOL;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "🚚 COTIZACIONES CREADAS:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;

$cotizaciones = App\Models\CotizacionModel::where('group_cotization_id', 595)->get();
echo "Total rutas: " . $cotizaciones->count() . PHP_EOL;
echo PHP_EOL;

foreach ($cotizaciones as $index => $cot) {
    echo "Ruta " . ($index + 1) . ":" . PHP_EOL;
    echo "  Origen: " . ($cot->ciudad_origen ?? 'N/A') . PHP_EOL;
    echo "  Destino: " . ($cot->ciudad_destino ?? 'N/A') . PHP_EOL;
    echo "  Producto: " . ($cot->producto ?? 'N/A') . PHP_EOL;
    echo "  Peso: " . ($cot->peso ?? 'N/A') . " " . ($cot->peso_unit ?? '') . PHP_EOL;
    echo "  Empaque: " . ($cot->empaque ?? 'N/A') . PHP_EOL;
    echo "  Valor declarado: " . ($cot->valor_declarado ?? 'N/A') . PHP_EOL;
    echo PHP_EOL;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "📝 LOGS RECIENTES DEL GRUPO 595:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo PHP_EOL;

// Buscar logs relacionados con el grupo 595
exec('tail -500 storage/logs/laravel.log | grep -E "group.*595|Grupo 595" | tail -30', $output);
if (!empty($output)) {
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
} else {
    echo "No se encontraron logs recientes para el grupo 595" . PHP_EOL;
}
