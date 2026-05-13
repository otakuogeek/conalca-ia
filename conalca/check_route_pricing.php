<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\PricingController;

$origin = isset($argv[1]) ? strtoupper(trim($argv[1])) : null;
$destination = isset($argv[2]) ? strtoupper(trim($argv[2])) : null;
$cargoWeight = isset($argv[3]) && $argv[3] !== '' ? (float) $argv[3] : null;
$condition = isset($argv[4]) && $argv[4] !== '' ? strtoupper(trim($argv[4])) : null;

if (!$origin || !$destination) {
    echo "Uso: php check_route_pricing.php ORIGEN DESTINO [PESO_KG] [CONDICION]" . PHP_EOL;
    echo "Ejemplo: php check_route_pricing.php CARTAGENA IBAGUE 25900" . PHP_EOL;
    echo "Ejemplo: php check_route_pricing.php BUENAVENTURA BOGOTA 27400 IMPORTACION" . PHP_EOL;
    exit(1);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "🔎 Diagnóstico de ruta pricing" . PHP_EOL;
echo "Origen: {$origin}" . PHP_EOL;
echo "Destino: {$destination}" . PHP_EOL;
echo "Peso: " . ($cargoWeight !== null ? $cargoWeight . ' kg' : 'N/A') . PHP_EOL;
echo "Condición solicitada: " . ($condition ?: 'N/A') . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;

$rawQuery = DB::table('pricings')
    ->where('origin', $origin)
    ->where('destination', $destination);

if ($condition) {
    $rawQuery->where('condition', $condition);
}

$rawRows = $rawQuery
    ->orderByDesc('updated_at')
    ->get([
        'id',
        'origin',
        'destination',
        'vehicle_type',
        'price',
        'weight',
        'condition',
        'type_pricing',
        'updated_at',
    ]);

echo "📦 Filas crudas en pricings: " . $rawRows->count() . PHP_EOL;
if ($rawRows->count() > 0) {
    foreach ($rawRows as $row) {
        echo "- ID {$row->id} | {$row->vehicle_type} | $" . number_format((float) $row->price, 0, ',', '.')
            . " | peso=" . ($row->weight ?? 'NULL')
            . " | condition=" . ($row->condition ?? 'NULL')
            . " | type_pricing=" . ($row->type_pricing ?? 'NULL')
            . PHP_EOL;
    }
}

echo PHP_EOL;

$requestPayload = [
    'origin' => $origin,
    'destination' => $destination,
];

if ($cargoWeight !== null) {
    $requestPayload['cargo_weight'] = $cargoWeight;
}

if ($condition) {
    $requestPayload['condition'] = $condition;
}

$request = new Request($requestPayload);
$response = app(PricingController::class)->latestByRoute($request);
$endpointRows = collect(json_decode($response->getContent(), true) ?: []);

echo "🚀 Resultado endpoint latestByRoute: " . $endpointRows->count() . " opciones" . PHP_EOL;
if ($endpointRows->count() > 0) {
    foreach ($endpointRows as $row) {
        echo "- ID {$row['id']} | {$row['vehicle_type']} | $" . number_format((float) $row['price'], 0, ',', '.')
            . " | peso=" . ($row['weight'] ?? 'NULL')
            . " | condition=" . ($row['condition'] ?? 'NULL')
            . " | type_pricing=" . ($row['type_pricing'] ?? 'NULL')
            . PHP_EOL;
    }
}

echo PHP_EOL;

if ($rawRows->count() === 0) {
    echo "⚠️ No hay datos base en tabla pricings para esta ruta." . PHP_EOL;
} elseif ($endpointRows->count() === 0) {
    echo "⚠️ Hay datos en pricings, pero latestByRoute los filtró (peso/condición/type_pricing)." . PHP_EOL;
} else {
    echo "✅ Hay precios disponibles y el endpoint los está entregando correctamente." . PHP_EOL;
}
