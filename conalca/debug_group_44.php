<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ANÁLISIS GRUPO #44 (FUNCIONAL) ===\n\n";

// Obtener datos del grupo #44
$group44 = DB::table('group_cotizations')
    ->where('id', 44)
    ->first();

if ($group44) {
    echo "GRUPO #44:\n";
    echo "- ID: {$group44->id}\n";
    echo "- User ID: {$group44->user_id}\n";
    echo "- Client ID: {$group44->client_id}\n";
    echo "- Type: {$group44->type}\n";
    echo "- Status: {$group44->status}\n";
    echo "- Reference: {$group44->reference}\n";
    echo "- Created from chat: " . ($group44->created_from_chat ?? 'NULL') . "\n";
    echo "- OpenAI Thread ID: " . ($group44->openai_thread_id ?? 'NULL') . "\n";
    echo "- Created at: {$group44->created_at}\n\n";

    // Obtener cotizaciones relacionadas
    $cotizaciones44 = DB::table('cotizacion_models')
        ->where('group_cotization_id', 44)
        ->get();

    echo "COTIZACIONES ASOCIADAS AL GRUPO #44:\n";
    echo "Total: " . count($cotizaciones44) . "\n\n";

    foreach ($cotizaciones44 as $index => $cotizacion) {
        echo "Cotización #" . ($index + 1) . ":\n";
        echo "- ID: {$cotizacion->id}\n";
        echo "- Group ID: {$cotizacion->group_cotization_id}\n";
        echo "- Origen: {$cotizacion->ciudad_origen}\n";
        echo "- Destino: {$cotizacion->ciudad_destino}\n";
        echo "- Peso: {$cotizacion->peso_mercancia}\n";
        echo "- Vehículo: {$cotizacion->vehiculo_requerido}\n";
        echo "- Created at: {$cotizacion->created_at}\n";
        echo "---\n";
    }

    // Comparar estructura con grupo #62
    echo "\n=== COMPARACIÓN CON GRUPO #62 ===\n\n";
    
    $group62 = DB::table('group_cotizations')
        ->where('id', 62)
        ->first();
    
    $cotizaciones62 = DB::table('cotizacion_models')
        ->where('group_cotization_id', 62)
        ->get();

    echo "DIFERENCIAS:\n";
    echo "Grupo #44 - Cotizaciones: " . count($cotizaciones44) . "\n";
    echo "Grupo #62 - Cotizaciones: " . count($cotizaciones62) . "\n";
    
    if (count($cotizaciones44) > 0 && count($cotizaciones62) == 0) {
        echo "\n❌ PROBLEMA IDENTIFICADO: El grupo #62 no tiene cotizaciones asociadas\n";
        echo "El grupo #44 SÍ tiene cotizaciones, por eso muestra las rutas correctamente.\n";
    }

    // Verificar estructura de campos en group_cotizations
    echo "\n=== VERIFICACIÓN DE CAMPOS ===\n";
    $columns = DB::select("SHOW COLUMNS FROM group_cotizations");
    echo "Campos disponibles en group_cotizations:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }

} else {
    echo "❌ Grupo #44 no encontrado\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";