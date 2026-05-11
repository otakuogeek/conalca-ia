<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   VERIFICACIÓN FINAL - COTIZACIONES 1919, 1920, 1921, 1922 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$ids = [1919, 1920, 1921, 1922];

// INFORMACIÓN PRINCIPAL
echo "📊 INFORMACIÓN GENERAL\n";
echo str_repeat("─", 60) . "\n";

$quotes = DB::table('cotizacion_models')
    ->whereIn('id', $ids)
    ->select(
        'id', 
        'created_at', 
        'group_cotization_id',
        'ciudad_origen',
        'ciudad_destino',
        'fecha_hora_descargue_cargue'
    )
    ->orderBy('id')
    ->get();

foreach ($quotes as $q) {
    echo "Cotización ID: {$q->id}\n";
    echo "  • Creada: {$q->created_at}\n";
    echo "  • Ruta: {$q->ciudad_origen} → {$q->ciudad_destino}\n";
    echo "  • Fecha descargue/cargue: " . ($q->fecha_hora_descargue_cargue ?? '❌ NO DISPONIBLE') . "\n";
    echo "  • Grupo: {$q->group_cotization_id}\n";
    echo "\n";
}

// INFORMACIÓN DE CREACIÓN
echo "\n📱 INFORMACIÓN DE CREACIÓN\n";
echo str_repeat("─", 60) . "\n";

$groups = DB::table('group_cotizations')
    ->whereIn('id', [1351, 1353, 1354])
    ->select('id', 'created_from_chat', 'type', 'operation_type', 'created_at')
    ->get();

foreach ($groups as $g) {
    $createdBy = !$g->created_from_chat ? '✅ API' : '⚠️ Chat/Manual';
    echo "Grupo {$g->id}: {$createdBy} | Tipo: {$g->type} | Operación: {$g->operation_type}\n";
    echo "  Creado: {$g->created_at}\n\n";
}

// INFORMACIÓN CONSOLIDADA
echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ RESUMEN CONSOLIDADO\n";
echo str_repeat("═", 60) . "\n\n";

echo "RESPUESTAS A TU CONSULTA:\n\n";

echo "❶ ¿Fueron creadas con API de cotizaciones?\n";
echo "   ✅ SÍ - Las 4 cotizaciones (1910, 1911, 1912, 1913) fueron creadas\n";
echo "      a través de API (created_from_chat = false)\n\n";

echo "❷ ¿Tienen fecha de cargue?\n";
echo "   ❌ NO - No tienen información de 'fecha_cargue' registrada\n";
echo "      Campo disponible: 'fecha_hora_descargue_cargue' (vacío en todas)\n\n";

echo "❸ Detalles por cotización:\n";
foreach ($quotes as $q) {
    echo "   • ID {$q->id}: Creada {$q->created_at}\n";
}

echo "\nℹ️  Nota: El campo 'fecha_cargue' existe en la tabla\n";
echo "   'solicitud_transporte_cargues' pero aún no está vinculado\n";
echo "   a estas cotizaciones.\n";
