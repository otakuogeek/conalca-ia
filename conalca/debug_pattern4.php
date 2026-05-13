<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 DEBUG PATRÓN 4\n";
echo str_repeat("=", 80) . "\n\n";

$mensaje = 'cali, medellín, ipiales a cota';
echo "Mensaje: '{$mensaje}'\n\n";

// Test del regex principal
$pattern = '/^([^a]+?)\s+(?:a|hacia)\s+(.+?)$/ui';
if (preg_match($pattern, $mensaje, $m)) {
    echo "✅ Regex principal MATCH:\n";
    echo "  m[0] (full match): '{$m[0]}'\n";
    echo "  m[1] (orígenes): '{$m[1]}'\n";
    echo "  m[2] (destinos): '{$m[2]}'\n";
    
    $origenesText = trim($m[1]);
    $destinosText = trim($m[2]);
    
    echo "\nValidaciones:\n";
    echo "  ¿Hay coma en orígenes? " . (strpos($origenesText, ',') !== false ? 'SÍ' : 'NO') . "\n";
    echo "  ¿Hay coma en destinos? " . (strpos($destinosText, ',') !== false ? 'SÍ' : 'NO') . "\n";
    echo "  ¿Hay 'y' en orígenes? " . (preg_match('/\s+y\s+/ui', $origenesText) ? 'SÍ' : 'NO') . "\n";
    echo "  ¿Hay 'y' en destinos? " . (preg_match('/\s+y\s+/ui', $destinosText) ? 'SÍ' : 'NO') . "\n";
    
    // Split ciudades
    $origenes = preg_split('/\s*(?:y|,)\s*/ui', $origenesText);
    $destinos = preg_split('/\s*(?:y|,)\s*/ui', $destinosText);
    
    echo "\nCiudades detectadas:\n";
    echo "  Orígenes (" . count($origenes) . "): " . implode(' | ', $origenes) . "\n";
    echo "  Destinos (" . count($destinos) . "): " . implode(' | ', $destinos) . "\n";
    
} else {
    echo "❌ Regex principal NO MATCH\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

// Ahora test los otros mensajes
echo "\n🧪 TEST #2: 'bogota y cali a medellin'\n";
$mensaje2 = 'bogota y cali a medellin';
if (preg_match($pattern, $mensaje2, $m2)) {
    echo "✅ MATCH: orígenes='{$m2[1]}', destinos='{$m2[2]}'\n";
} else {
    echo "❌ NO MATCH\n";
}

echo "\n🧪 TEST #3: 'barranquilla hacia cota y pasto'\n";
$mensaje3 = 'barranquilla hacia cota y pasto';
if (preg_match($pattern, $mensaje3, $m3)) {
    echo "✅ MATCH: orígenes='{$m3[1]}', destinos='{$m3[2]}'\n";
} else {
    echo "❌ NO MATCH\n";
}
