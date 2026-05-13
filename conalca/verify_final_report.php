<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BÚSQUEDA DE FECHA CARGUE EN OTRAS TABLAS ===\n\n";

$cotizacionIds = [1910, 1911, 1912, 1913];

// Buscar en solicitud_transporte_cargues
echo "1️⃣ TABLA: solicitud_transporte_cargues\n";
echo str_repeat("═", 60) . "\n";

try {
    // Primero ver columnas
    $columns = DB::select('SHOW COLUMNS FROM solicitud_transporte_cargues');
    echo "Columnas disponibles:\n";
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
    
    // Buscar relación con cotizacion_id
    echo "\nBuscando relación con cotizacion_models...\n";
    $cargues = DB::table('solicitud_transporte_cargues')
        ->whereIn('cotizacion_id', $cotizacionIds)
        ->get();
    
    if (count($cargues) > 0) {
        echo "✓ Encontrados " . count($cargues) . " registros de cargue\n";
        foreach ($cargues as $cargue) {
            echo "\n  Cargue ID: {$cargue->id}\n";
            foreach ((array)$cargue as $field => $value) {
                if ($value !== null && $value !== '') {
                    echo "    - {$field}: {$value}\n";
                }
            }
        }
    } else {
        echo "⚠️ No hay relación directa con cotizacion_id en esta tabla\n";
        
        // Buscar por group_id si existe
        echo "\nIntentando buscar por grupo...\n";
        $groupIds = [1351, 1353, 1354];
        $cargues = DB::table('solicitud_transporte_cargues')
            ->whereIn('group_cotization_id', $groupIds)
            ->limit(5)
            ->get();
        
        if (count($cargues) > 0) {
            echo "✓ Encontrados " . count($cargues) . " registros por grupo\n";
        } else {
            echo "⚠️ No hay registros por grupo tampoco\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Buscar en otras tablas que puedan tener fecha_cargue
echo "\n\n2️⃣ BÚSQUEDA EN TODAS LAS TABLAS\n";
echo str_repeat("═", 60) . "\n";

$allTables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");

$tablesWithFechaCargue = [];
foreach ($allTables as $tableObj) {
    $table = $tableObj->TABLE_NAME;
    try {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        foreach ($columns as $col) {
            if (stripos($col->Field, 'fecha_cargue') !== false || 
                (stripos($col->Field, 'fecha') !== false && stripos($col->Field, 'cargue') !== false)) {
                $tablesWithFechaCargue[$table] = $col->Field;
            }
        }
    } catch (\Exception $e) {
        // Ignorar tablas con error
    }
}

if (count($tablesWithFechaCargue) > 0) {
    echo "✓ Encontradas tablas con 'fecha_cargue':\n";
    foreach ($tablesWithFechaCargue as $table => $column) {
        echo "  - Tabla: {$table} | Columna: {$column}\n";
    }
} else {
    echo "⚠️ No hay tablas con columna 'fecha_cargue' en la base de datos\n";
}

// RESUMEN FINAL
echo "\n\n" . str_repeat("═", 60) . "\n";
echo "📋 RESUMEN FINAL PARA IDS 1910, 1911, 1912, 1913\n";
echo str_repeat("═", 60) . "\n\n";

$quotes = DB::table('cotizacion_models')
    ->whereIn('id', $cotizacionIds)
    ->select('id', 'created_at', 'group_cotization_id')
    ->orderBy('id')
    ->get();

$groups = DB::table('group_cotizations')
    ->whereIn('id', [1351, 1353, 1354])
    ->select('id', 'created_from_chat', 'type')
    ->get();

$groupMap = $groups->keyBy('id');

foreach ($quotes as $q) {
    $group = $groupMap->get($q->group_cotization_id);
    $createdBy = $group && !$group->created_from_chat ? 'API' : 'Manual/Chat';
    
    echo "ID {$q->id}:\n";
    echo "  ✓ Creada: {$q->created_at}\n";
    echo "  ✓ Creada por: {$createdBy}\n";
    echo "  ❌ Fecha de Cargue: NO DISPONIBLE (campo no existe en BD)\n";
    echo "\n";
}
