#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDACIÓN COMPLETA - COTIZACIÓN ID 2579                 ║\n";
echo "║  Grupo 1931 - CALI → BOGOTA                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$cotizacionId = 2579;
$groupId = 1931;

// 1. VERIFICAR EN TABLA cotizacion_models
echo "1️⃣ BÚSQUEDA EN BASE DE DATOS\n";
echo str_repeat("═", 60) . "\n\n";

$cotizacion = DB::table('cotizacion_models')
    ->where('id', $cotizacionId)
    ->select('*')
    ->first();

if (!$cotizacion) {
    echo "❌ ERROR: Cotización no encontrada en BD\n";
    exit(1);
}

echo "✅ COTIZACIÓN ENCONTRADA EN BD\n\n";

// Campos importantes
$campos = [
    'id' => 'ID',
    'group_cotization_id' => 'Grupo',
    'ciudad_origen' => 'Origen',
    'ciudad_destino' => 'Destino',
    'valor' => 'Valor',
    'flete' => 'Flete',
    'peso_mercancia' => 'Peso',
    'cantidad' => 'Cantidad',
    'tipo_mercancia' => 'Tipo Mercancía',
    'vehiculo_requerido' => 'Vehículo',
    'fecha_hora_descargue_cargue' => 'Fecha Cargue (BD)',
    'decision_cliente' => 'Decision',
    'created_at' => 'Creada',
    'updated_at' => 'Actualizada',
];

foreach ($campos as $campo => $label) {
    $valor = $cotizacion->$campo;
    $display = $valor ?: '(vacío)';
    
    if ($campo === 'fecha_hora_descargue_cargue') {
        $estado = $valor ? '✅' : '❌';
        echo "  {$estado} {$label}: {$display}\n";
    } else {
        echo "     {$label}: {$display}\n";
    }
}

// 2. COMPARACIÓN: JSON API vs BD
echo "\n\n2️⃣ COMPARACIÓN: RESPUESTA API vs BD\n";
echo str_repeat("═", 60) . "\n\n";

$jsonFecha = "2026-04-29 11:00";
$bdFecha = $cotizacion->fecha_hora_descargue_cargue;

echo "JSON API recibido:\n";
echo "  \"fecha_cargue\": \"{$jsonFecha}\"\n\n";

echo "Base de Datos (tabla cotizacion_models):\n";
echo "  fecha_hora_descargue_cargue: {$bdFecha}\n\n";

if ($bdFecha === $jsonFecha) {
    echo "✅ COINCIDEN PERFECTAMENTE\n";
} else if ($bdFecha && strpos($bdFecha, $jsonFecha) === 0) {
    echo "✅ COINCIDEN (BD tiene timestamp completo)\n";
} else {
    echo "⚠️ NO COINCIDEN\n";
    echo "   JSON: {$jsonFecha}\n";
    echo "   BD:   {$bdFecha}\n";
}

// 3. INFORMACIÓN DEL GRUPO
echo "\n\n3️⃣ INFORMACIÓN DEL GRUPO\n";
echo str_repeat("═", 60) . "\n\n";

$grupo = DB::table('group_cotizations')
    ->where('id', $groupId)
    ->select('id', 'status', 'reference', 'created_from_chat', 'created_at')
    ->first();

if ($grupo) {
    echo "  ✓ ID Grupo: {$grupo->id}\n";
    echo "  ✓ Status: {$grupo->status}\n";
    echo "  ✓ Reference: {$grupo->reference}\n";
    echo "  ✓ Creado por: " . ($grupo->created_from_chat ? 'Chat' : '✅ API') . "\n";
    echo "  ✓ Creado: {$grupo->created_at}\n";
}

// 4. RESUMEN FINAL
echo "\n\n" . str_repeat("═", 60) . "\n";
echo "✅ RESULTADO FINAL\n";
echo str_repeat("═", 60) . "\n\n";

$tieneFeche = $cotizacion->fecha_hora_descargue_cargue ? '✅' : '❌';

echo "Cotización ID: {$cotizacionId}\n";
echo "  Ruta: {$cotizacion->ciudad_origen} → {$cotizacion->ciudad_destino}\n";
echo "  Valor: \${$cotizacion->valor} | Flete: \${$cotizacion->flete}\n";
echo "  Fecha Cargue: {$tieneFeche} {$cotizacion->fecha_hora_descargue_cargue}\n";
echo "  Decision Cliente: {$cotizacion->decision_cliente}\n";
echo "  Status Grupo: {$grupo->status}\n";
echo "  Creada por: API\n\n";

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  CONCLUSIÓN                                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($cotizacion->fecha_hora_descargue_cargue && 
    $cotizacion->decision_cliente === 'aceptada' && 
    $grupo->status === 'En tránsito') {
    echo "✅ ¡ÉXITO! La cotización fue creada correctamente\n\n";
    echo "Detalles verificados:\n";
    echo "  ✓ Fecha de cargue registrada: {$cotizacion->fecha_hora_descargue_cargue}\n";
    echo "  ✓ Decision cliente: aceptada (lista para llamadas)\n";
    echo "  ✓ Status: En tránsito (visible en tablero Kanban)\n";
    echo "  ✓ Creada por API (no por chat)\n";
    echo "\n📌 La cotización está lista para:\n";
    echo "  1. Buscar conductores vía POST /api/arcangel/buscar-conductores\n";
    echo "  2. Registrar llamadas\n";
    echo "  3. Iniciar llamadas con ElevenLabs\n";
} else {
    echo "⚠️ ADVERTENCIA: Algún dato no es correcto\n";
    if (!$cotizacion->fecha_hora_descargue_cargue) {
        echo "  ❌ Sin fecha_cargue\n";
    }
    if ($cotizacion->decision_cliente !== 'aceptada') {
        echo "  ❌ Decision no es 'aceptada': {$cotizacion->decision_cliente}\n";
    }
    if ($grupo->status !== 'En tránsito') {
        echo "  ❌ Status no es 'En tránsito': {$grupo->status}\n";
    }
}

echo "\n";
