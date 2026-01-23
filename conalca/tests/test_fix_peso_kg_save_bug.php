<?php
/**
 * TEST FIX #3 - Bug peso_kg guardado incorrecto
 * 
 * BUG: DataExtractionService calcula peso correcto (1800→5200 kg) pero
 * extracted_data guarda peso_kg=1.8 ton (sin tara) en lugar de 5.2 ton (con tara)
 * 
 * CAUSA: DataExtractionService retorna 'peso' en kg pero no 'peso_kg' en toneladas
 * FIX: Agregar peso_kg = peso / 1000 en DataExtractionService cuando suma tara
 * 
 * CASO DE PRUEBA - Grupo #524:
 * - Mensaje: "origen: cali, destino: santa marta, peso: 1,800 kilogramos (sin tara), cantidad: 60 cajas..."
 * - DataExtractionService debe retornar: peso=5200 kg, peso_kg=5.2 ton
 * - extracted_data debe guardar: peso_kg=5.2 ton
 * - Database debe guardar: peso_mercancia=5.2 toneladas
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Services\DataExtractionService;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║          TEST FIX #3 - Bug peso_kg Save Incorrect            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================
// CASO 1: Creación inicial con "sin tara" → Debe sumar tara
// ============================================================
echo "CASO 1: Creación inicial - 'sin tara' → suma 3400 kg\n";
echo "--------------------------------------------------------\n";

$service = new DataExtractionService();
$message = "origen: cali, destino: santa marta, peso: 1,800 kilogramos (sin tara), cantidad: 60 cajas, tipo de producto: repuestos automotrices";
$currentData = []; // Sin datos previos (modo creación)

echo "Mensaje: '$message'\n";
echo "Current Data: [vacío - modo creación]\n\n";

$result = $service->extractDataFromMessage($message, $currentData);

echo "📊 RESULTADO DE EXTRACCIÓN:\n";
echo "--------------------------------------\n";

$extracted = $result['extracted'] ?? [];
$peso = $extracted['peso'] ?? null;
$pesoKg = $extracted['peso_kg'] ?? null;
$incluyeTara = $extracted['incluye_tara'] ?? null;

echo "peso (kg):           " . ($peso !== null ? number_format($peso, 0, '.', ',') . " kg" : 'NULL') . "\n";
echo "peso_kg (toneladas): " . ($pesoKg !== null ? number_format($pesoKg, 2, '.', ',') . " ton" : 'NULL') . "\n";
echo "incluye_tara:        " . ($incluyeTara ? 'true' : 'false') . "\n\n";

// Validar resultado
$caso1Pass = true;

if ($peso !== 5200.0) {
    echo "❌ ERROR: peso debería ser 5200.0 kg (1800+3400), pero es: " . ($peso ?? 'NULL') . "\n";
    $caso1Pass = false;
}

if ($pesoKg !== 5.2) {
    echo "❌ ERROR: peso_kg debería ser 5.2 ton, pero es: " . ($pesoKg ?? 'NULL') . "\n";
    $caso1Pass = false;
}

if (!$incluyeTara) {
    echo "❌ ERROR: incluye_tara debería ser true\n";
    $caso1Pass = false;
}

if ($caso1Pass) {
    echo "✅ CASO 1 CORRECTO: Tara sumada, peso=5200 kg, peso_kg=5.2 ton\n";
} else {
    echo "❌ CASO 1 FALLÓ\n";
}

echo "\n\n";

// ============================================================
// CASO 2: Edición de campo → NO debe recalcular tara
// ============================================================
echo "CASO 2: Edición - cambio vehículo → mantiene peso\n";
echo "--------------------------------------------------------\n";

$service2 = new DataExtractionService();
$messageEdit = "vehiculo turbo";
$currentData2 = [
    'ciudad_origen' => 'CALI',
    'ciudad_destino' => 'SANTA MARTA',
    'peso_mercancia' => 5200, // Ya tiene tara sumada (5.2 ton en kg)
    'peso' => 5200,
    'cantidad' => 60,
    'producto' => 'REPUESTOS AUTOMOTRICES',
    'incluye_tara' => true
];

echo "Mensaje: '$messageEdit'\n";
echo "Current Data: [peso_mercancia=5200 kg (5.2 ton), incluye_tara=true]\n\n";

$resultEdit = $service2->extractDataFromMessage($messageEdit, $currentData2);

$extractedEdit = $resultEdit['extracted'] ?? [];
$pesoEdit = $extractedEdit['peso'] ?? null;
$pesoKgEdit = $extractedEdit['peso_kg'] ?? null;

echo "📊 RESULTADO DE EDICIÓN:\n";
echo "--------------------------------------\n";
echo "peso (kg):           " . ($pesoEdit !== null ? number_format($pesoEdit, 0, '.', ',') . " kg" : 'NULL') . "\n";
echo "peso_kg (toneladas): " . ($pesoKgEdit !== null ? number_format($pesoKgEdit, 2, '.', ',') . " ton" : 'NULL') . "\n\n";

// Validar que no haya recalculado tara
$caso2Pass = true;

if ($pesoEdit !== null) {
    echo "❌ ERROR: En modo edición NO debería extraer peso nuevo (debe mantenerse 5200 kg)\n";
    $caso2Pass = false;
}

if ($pesoKgEdit !== null) {
    echo "❌ ERROR: En modo edición NO debería extraer peso_kg nuevo\n";
    $caso2Pass = false;
}

if ($caso2Pass) {
    echo "✅ CASO 2 CORRECTO: Modo edición NO recalculó peso\n";
} else {
    echo "❌ CASO 2 FALLÓ\n";
}

echo "\n\n";

// ============================================================
// RESUMEN
// ============================================================
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                     RESUMEN DEL TEST                          ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($caso1Pass && $caso2Pass) {
    echo "🎉 TODOS LOS CASOS PASARON - FIX #3 VALIDADO\n";
    echo "\n";
    echo "✅ DataExtractionService ahora retorna peso_kg correctamente\n";
    echo "✅ Modo creación: suma tara y retorna peso_kg en toneladas\n";
    echo "✅ Modo edición: NO recalcula peso\n";
    echo "\n";
    echo "📋 PRÓXIMOS PASOS:\n";
    echo "1. Probar en producción con grupo real (crear nueva cotización)\n";
    echo "2. Verificar que peso_mercancia se guarda correctamente en DB\n";
    echo "3. Validar que frontend usa peso_kg (5.2 ton) en lugar de peso (5200 kg)\n";
    exit(0);
} else {
    echo "❌ ALGUNOS CASOS FALLARON\n";
    echo "\n";
    echo "Casos fallidos:\n";
    if (!$caso1Pass) echo "  - CASO 1: Creación con tara\n";
    if (!$caso2Pass) echo "  - CASO 2: Edición sin recálculo\n";
    echo "\n";
    echo "Por favor revisar logs en storage/logs/laravel.log\n";
    exit(1);
}
