<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$groupId = 600;

echo "\n🔍 Verificando Grupo #$groupId\n";
echo str_repeat("=", 60) . "\n";

$group = DB::table('group_cotizations')->where('id', $groupId)->first();

if (!$group) {
    echo "❌ Grupo no encontrado\n";
    exit(1);
}

$extractedData = json_decode($group->extracted_data, true);

echo "\n📊 ESTRUCTURA ACTUAL:\n";
echo str_repeat("-", 60) . "\n";

$routes = [];
$flatFields = [];

foreach ($extractedData as $key => $value) {
    if (is_numeric($key) && is_array($value)) {
        $routes[$key] = $value;
    } else {
        $flatFields[$key] = $value;
    }
}

echo "Rutas: " . count($routes) . "\n";
echo "Campos planos: " . count($flatFields) . "\n";

if (!empty($flatFields)) {
    echo "\n⚠️  LIMPIANDO CAMPOS PLANOS...\n";
    echo "Campos a eliminar: " . implode(', ', array_keys($flatFields)) . "\n";
    
    // Limpiar: solo guardar rutas
    $cleanedData = $routes;
    
    DB::table('group_cotizations')
        ->where('id', $groupId)
        ->update([
            'extracted_data' => json_encode($cleanedData),
            'updated_at' => now()
        ]);
    
    echo "✅ Grupo limpiado\n";
} else {
    echo "✅ Grupo ya está limpio\n";
}

// Verificar resultado
$groupUpdated = DB::table('group_cotizations')->where('id', $groupId)->first();
$extractedDataUpdated = json_decode($groupUpdated->extracted_data, true);

echo "\n📊 ESTRUCTURA DESPUÉS DE LIMPIEZA:\n";
echo str_repeat("-", 60) . "\n";

foreach ($extractedDataUpdated as $key => $value) {
    if (is_numeric($key) && is_array($value)) {
        echo "\n🛣️  RUTA #$key:\n";
        echo "   Origen: " . ($value['origen'] ?? 'N/A') . "\n";
        echo "   Destino: " . ($value['destino'] ?? 'N/A') . "\n";
        echo "   Producto: " . ($value['producto'] ?? 'N/A') . "\n";
        echo "   Peso: " . ($value['peso_kg'] ?? 'N/A') . " kg\n";
        echo "   Incluye tara: " . (isset($value['incluye_tara']) ? ($value['incluye_tara'] ? 'SÍ' : 'NO') : 'N/A') . "\n";
        echo "   Valor: $" . number_format($value['valor_declarado'] ?? 0, 0, ',', '.') . "\n";
    } else {
        echo "\n⚠️  CAMPO PLANO: '$key'\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Verificación completada\n\n";
