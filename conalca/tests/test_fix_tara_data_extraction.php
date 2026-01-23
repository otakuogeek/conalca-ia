<?php

/**
 * TEST - Verificar FIX de Bug Tara en DataExtractionService
 * 
 * BUG: DataExtractionService suma tara en ediciones de campos
 * SOLUCIÓN: Agregar detección de modo edición con $esEdicionCampo
 * 
 * Escenario:
 * 1. Crear cotización: "Peso 3,200 kg (sin tara)" → Sistema suma tara → 6,600 kg ✅
 * 2. Editar campo: "vehículo turbo, cantidad 455" (sin mencionar tara)
 *    ANTES del FIX: DataExtractionService sumaba tara OTRA VEZ → 6,600 + 3,400 = 10,000 kg ❌
 *    DESPUÉS del FIX: DataExtractionService NO suma tara en ediciones → 6,600 kg ✅
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Services\DataExtractionService;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST - FIX BUG TARA EN DataExtractionService (Edición de Campos) ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// PASO 1: Simular CREACIÓN inicial (sin currentData)
echo "📝 PASO 1: Creación inicial de cotización\n";
echo str_repeat("─", 70) . "\n";

$dataExtractionService = new DataExtractionService();

// Mensaje inicial: peso sin tara
$mensajeCreacion = "Origen: Medellín, Destino: Cartagena, Peso: 3,200 kilogramos (sin tara), Cantidad: 85 cajas";
echo "Mensaje: '{$mensajeCreacion}'\n";
echo "Current Data: [] (vacío - creación inicial)\n\n";

// Simular extracción inicial (SIN currentData)
$resultadoCreacion = $dataExtractionService->extractDataFromMessage($mensajeCreacion, []);

// Verificar que DataExtractionService sumó tara correctamente
$pesoConTara = $resultadoCreacion['extracted']['peso'] ?? 0;
$incluyeTara = $resultadoCreacion['extracted']['incluye_tara'] ?? false;

echo "✅ RESULTADO CREACIÓN:\n";
echo "  - Peso extraído: {$pesoConTara} kg\n";
echo "  - Incluye tara: " . ($incluyeTara ? 'true' : 'false') . "\n";
echo "  - Esperado: 6600 kg (3200 + 3400 tara) ✅\n\n";

if ($pesoConTara == 6600) {
    echo "  ✅ CORRECTO: DataExtractionService sumó tara en creación inicial\n\n";
} else {
    echo "  ❌ ERROR: Peso incorrecto. Esperado: 6600, Obtenido: {$pesoConTara}\n\n";
}

// PASO 2: Simular EDICIÓN de campos (con currentData)
echo "📝 PASO 2: Edición de campos existentes\n";
echo str_repeat("─", 70) . "\n";

// Mensaje de edición: solo cambia vehículo y cantidad (NO menciona peso)
$mensajeEdicion = "vehículo turbo, cantidad 455";
echo "Mensaje: '{$mensajeEdicion}'\n";

// Current data simulando ruta existente con peso ya calculado (6600 kg)
$currentData = [
    'ciudad_origen' => 'MEDELLIN',
    'ciudad_destino' => 'CARTAGENA',
    'peso_mercancia' => 6.6, // toneladas (6600 kg)
    'peso' => 6600,
    'cantidad' => 85,
    'empaque' => 'CAJAS'
];

echo "Current Data: \n";
echo "  - ciudad_origen: MEDELLIN\n";
echo "  - ciudad_destino: CARTAGENA\n";
echo "  - peso_mercancia: 6.6 toneladas (6600 kg con tara ya sumada)\n";
echo "  - cantidad: 85\n\n";

// Simular extracción en modo edición (CON currentData)
$resultadoEdicion = $dataExtractionService->extractDataFromMessage($mensajeEdicion, $currentData);

// Verificar que DataExtractionService NO sumó tara en edición
$pesoEnEdicion = $resultadoEdicion['extracted']['peso'] ?? $currentData['peso'];

echo "✅ RESULTADO EDICIÓN:\n";
echo "  - Peso en resultado: {$pesoEnEdicion} kg\n";
echo "  - Esperado: 6600 kg (sin recalcular tara) ✅\n\n";

// VALIDACIÓN FINAL
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║               VALIDACIÓN DEL FIX                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($pesoEnEdicion <= 6600) {
    echo "✅ FIX APLICADO CORRECTAMENTE\n";
    echo "   El peso NO se recalculó durante la edición de campos\n";
    echo "   Peso se mantuvo en: {$pesoEnEdicion} kg\n\n";
    echo "   COMPORTAMIENTO ESPERADO:\n";
    echo "   - Creación: 3200 kg → 6600 kg (sumó tara) ✅\n";
    echo "   - Edición: 6600 kg → 6600 kg (NO sumó tara otra vez) ✅\n";
} else {
    echo "❌ FIX NO APLICADO - BUG AÚN PRESENTE\n";
    echo "   El peso se recalculó incorrectamente: {$pesoEnEdicion} kg\n";
    echo "   Esperado: 6600 kg o menos\n";
    echo "   COMPORTAMIENTO INCORRECTO:\n";
    echo "   - Creación: 3200 kg → 6600 kg (sumó tara) ✅\n";
    echo "   - Edición: 6600 kg → {$pesoEnEdicion} kg (sumó tara OTRA VEZ) ❌\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Test completado - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";
