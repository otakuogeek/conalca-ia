#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDACIÓN DE COTIZACIONES 1927-1930                     ║\n";
echo "║  Verificar si fueron creadas con fecha_cargue             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$ids = [1927, 1928, 1929, 1930];

// Buscar cotizaciones
$cotizaciones = DB::table('cotizacion_models')
    ->whereIn('id', $ids)
    ->select(
        'id',
        'group_cotization_id',
        'ciudad_origen',
        'ciudad_destino',
        'valor',
        'flete',
        'fecha_hora_descargue_cargue',
        'decision_cliente',
        'created_at',
        'updated_at'
    )
    ->orderBy('id')
    ->get();

echo "Cotizaciones encontradas: " . count($cotizaciones) . "\n\n";

if (count($cotizaciones) === 0) {
    echo "❌ No se encontraron cotizaciones con estos IDs\n";
    exit(1);
}

// Mostrar detalles
foreach ($cotizaciones as $cot) {
    $tieneFeche = $cot->fecha_hora_descargue_cargue ? '✅' : '❌';
    
    echo "════════════════════════════════════════════════════════════\n";
    echo "ID COTIZACIÓN: {$cot->id}\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "  📍 Ruta: {$cot->ciudad_origen} → {$cot->ciudad_destino}\n";
    echo "  💰 Valor: \${$cot->valor} | Flete: \${$cot->flete}\n";
    echo "  📅 Fecha Cargue: {$tieneFeche} " . ($cot->fecha_hora_descargue_cargue ? $cot->fecha_hora_descargue_cargue : 'NO DISPONIBLE') . "\n";
    echo "  ✋ Decision Cliente: {$cot->decision_cliente}\n";
    echo "  👥 Grupo: {$cot->group_cotization_id}\n";
    echo "  🕐 Creada: {$cot->created_at}\n";
    echo "  🔄 Actualizada: {$cot->updated_at}\n";
    echo "\n";
}

// RESUMEN
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMEN                                                   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$conFecha = $cotizaciones->where('fecha_hora_descargue_cargue', '!=', null)->count();
$sinFecha = $cotizaciones->where('fecha_hora_descargue_cargue', null)->count();

echo "Total de cotizaciones: " . count($cotizaciones) . "\n";
echo "  ✅ Con fecha_cargue: {$conFecha}\n";
echo "  ❌ Sin fecha_cargue: {$sinFecha}\n\n";

if ($conFecha > 0) {
    echo "✅ SUCCESS: Las cotizaciones llegaron con fecha_cargue\n\n";
    
    echo "Detalles de cotizaciones con fecha:\n";
    foreach ($cotizaciones as $cot) {
        if ($cot->fecha_hora_descargue_cargue) {
            echo "  • ID {$cot->id}: {$cot->fecha_hora_descargue_cargue} ({$cot->ciudad_origen} → {$cot->ciudad_destino})\n";
        }
    }
} else {
    echo "⚠️  ADVERTENCIA: Las cotizaciones NO tienen fecha_cargue registrada\n\n";
    echo "Acciones recomendadas:\n";
    echo "  1. Verificar que el JSON incluya 'fecha_cargue': 'YYYY-MM-DD HH:MM'\n";
    echo "  2. Revisar que se envió en rutas[*].fecha_cargue (no en nivel grupo)\n";
    echo "  3. Validar formato: debe ser exactamente 'YYYY-MM-DD HH:MM'\n";
    echo "  4. Revisar logs del API para errores de validación\n";
}

echo "\n";
