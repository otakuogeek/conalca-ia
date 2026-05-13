<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "CORRECCIÓN GRUPO 397 - TARA DUPLICADA\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$group = DB::table('group_cotizations')->where('id', 397)->first();
if (!$group) {
    echo "❌ Grupo 397 no encontrado\n";
    exit(1);
}

$extracted = json_decode($group->extracted_data, true);

echo "ANTES DE LA CORRECCIÓN:\n";
echo "-------------------------------------------------------------------------------\n";
foreach ($extracted as $idx => $ruta) {
    $num = $idx + 1;
    $peso = $ruta['peso_kg'] ?? 0;
    $peso_sin_tara = $peso - 3400;
    $modulo = $peso_sin_tara % 1000;
    
    echo "Ruta $num:\n";
    echo "  Origen: " . ($ruta['origen'] ?? 'N/A') . " → Destino: " . ($ruta['destino'] ?? 'N/A') . "\n";
    echo "  Peso actual: $peso kg\n";
    echo "  Análisis: ($peso - 3400) % 1000 = $modulo";
    
    if ($modulo == 0 && $peso > 3400) {
        echo " ⚠️ TARA DUPLICADA\n";
        $peso_correcto = $peso - 3400;
        echo "  ✅ Peso correcto: $peso_correcto kg\n";
    } else {
        echo " ✅ OK\n";
    }
    echo "\n";
}

echo "\nAPLICANDO CORRECCIÓN...\n";
echo "-------------------------------------------------------------------------------\n";

$correcciones = 0;
foreach ($extracted as $idx => $ruta) {
    if (!isset($ruta['peso_kg'])) continue;
    
    $peso = $ruta['peso_kg'];
    $peso_sin_tara = $peso - 3400;
    $modulo = $peso_sin_tara % 1000;
    
    // Si es múltiplo de 1000, la tara está duplicada
    if ($modulo == 0 && $peso > 3400) {
        $peso_correcto = $peso - 3400;
        $num = $idx + 1;
        echo "  Ruta $num: $peso kg → $peso_correcto kg (removiendo tara duplicada)\n";
        $extracted[$idx]['peso_kg'] = $peso_correcto;
        $correcciones++;
    }
}

if ($correcciones > 0) {
    echo "\n📝 Guardando correcciones en la base de datos...\n";
    
    DB::table('group_cotizations')
        ->where('id', 397)
        ->update([
            'extracted_data' => json_encode($extracted),
            'updated_at' => now()
        ]);
    
    echo "✅ Corrección completada: $correcciones rutas corregidas\n\n";
    
    echo "DESPUÉS DE LA CORRECCIÓN:\n";
    echo "-------------------------------------------------------------------------------\n";
    foreach ($extracted as $idx => $ruta) {
        $num = $idx + 1;
        $peso = $ruta['peso_kg'] ?? 0;
        $peso_sin_tara = $peso - 3400;
        $modulo = $peso_sin_tara % 1000;
        
        echo "Ruta $num: " . ($ruta['origen'] ?? 'N/A') . " → " . ($ruta['destino'] ?? 'N/A');
        echo " | Peso: $peso kg";
        echo " | ($peso - 3400) % 1000 = $modulo";
        if ($modulo == 0) {
            echo " ⚠️";
        } else {
            echo " ✅";
        }
        echo "\n";
    }
    
    echo "\n🎯 Ahora actualiza también la tabla cotizacion_models...\n";
    
    // Obtener las rutas de la tabla
    $cotizations = DB::table('cotizacion_models')
        ->where('group_cotization_id', 397)
        ->orderBy('id')
        ->get();
    
    if ($cotizations->count() === count($extracted)) {
        foreach ($cotizations as $idx => $cot) {
            $nuevoPeso = $extracted[$idx]['peso_kg'];
            
            DB::table('cotizacion_models')
                ->where('id', $cot->id)
                ->update([
                    'peso_mercancia' => $nuevoPeso,
                    'updated_at' => now()
                ]);
            
            echo "  Ruta ID $cot->id: peso_mercancia actualizado a $nuevoPeso kg\n";
        }
        echo "\n✅ Tabla cotizacion_models sincronizada\n";
    } else {
        echo "⚠️ Número de rutas no coincide, actualiza manualmente\n";
    }
    
} else {
    echo "✅ No se encontraron rutas con tara duplicada\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════════════\n";
echo "CORRECCIÓN COMPLETA\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
