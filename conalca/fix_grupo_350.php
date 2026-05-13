<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GroupCotization;

echo "\n========== ESTADO ACTUAL GRUPO 350 ==========\n";

$group = GroupCotization::find(350);

if ($group) {
    echo "ID: " . $group->id . "\n";
    echo "Estado: " . $group->estado . "\n";
    echo "\nExtracted Data ANTES del fix:\n";
    
    // Si extracted_data es un string JSON, decodificarlo
    $extractedData = $group->extracted_data;
    if (is_string($extractedData)) {
        $extractedData = json_decode($extractedData, true);
    }
    
    echo json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    if (isset($extractedData['ciudad_origen']) && $extractedData['ciudad_origen'] === 'COTIZACION DE BOGOTA') {
        echo "\n🔧 Aplicando corrección...\n";
        $extractedData['ciudad_origen'] = 'BOGOTA';
        
        // También actualizar 'origen' si existe
        if (isset($extractedData['origen'])) {
            $extractedData['origen'] = 'BOGOTA';
        }
        
        $group->extracted_data = $extractedData;
        $group->save();
        
        echo "✅ Grupo 350 actualizado correctamente\n";
        echo "\nExtracted Data DESPUÉS del fix:\n";
        echo json_encode($group->extracted_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "\n✅ El grupo ya tiene el origen correcto o no necesita corrección\n";
        echo "ciudad_origen actual: " . ($extractedData['ciudad_origen'] ?? 'NO DEFINIDO') . "\n";
    }
    
} else {
    echo "❌ Grupo 350 no encontrado\n";
}

echo "\n========== FIN ==========\n";
