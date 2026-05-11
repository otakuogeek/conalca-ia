<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN FINAL COTIZACIONES 1910-1913 ===\n\n";

$ids = [1910, 1911, 1912, 1913];

// 1. DATOS DE cotizacion_models
echo "1️⃣ TABLA: cotizacion_models\n";
echo str_repeat("═", 60) . "\n";

$quotes = DB::table('cotizacion_models')
    ->whereIn('id', $ids)
    ->select('id', 'created_at', 'group_cotization_id', 'user_id', 'client_id')
    ->get();

foreach ($quotes as $quote) {
    echo "ID {$quote->id}: Creado {$quote->created_at} | Grupo {$quote->group_cotization_id} | Usuario {$quote->user_id}\n";
}

// 2. DATOS DE group_cotizations
echo "\n2️⃣ TABLA: group_cotizations\n";
echo str_repeat("═", 60) . "\n";

$groupIds = $quotes->pluck('group_cotization_id')->unique()->toArray();
$groups = DB::table('group_cotizations')
    ->whereIn('id', $groupIds)
    ->select('id', 'created_at', 'updated_at', 'status', 'type', 'operation_type')
    ->get();

foreach ($groups as $group) {
    echo "Grupo ID {$group->id}: Creado {$group->created_at} | Status: {$group->status} | Tipo: {$group->type}\n";
}

// 3. Verificar qué tablas relacionadas existen
echo "\n3️⃣ TABLAS DISPONIBLES EN LA BASE DE DATOS\n";
echo str_repeat("═", 60) . "\n";

$tables = DB::select("SHOW TABLES LIKE '%carg%'");
echo "Tablas con 'carg':\n";
foreach ($tables as $table) {
    $tableName = (array)$table;
    echo "  - " . array_values($tableName)[0] . "\n";
}

$tables = DB::select("SHOW TABLES LIKE '%extrac%'");
echo "\nTablas con 'extrac':\n";
foreach ($tables as $table) {
    $tableName = (array)$table;
    echo "  - " . array_values($tableName)[0] . "\n";
}

// 4. Verificar columnas en group_cotizations
echo "\n4️⃣ COLUMNAS EN group_cotizations QUE CONTENGAN 'fecha' o 'cargue'\n";
echo str_repeat("═", 60) . "\n";

$columns = DB::select('SHOW COLUMNS FROM group_cotizations');
$relevantCols = array_filter($columns, function($col) {
    return stripos($col->Field, 'fecha') !== false || stripos($col->Field, 'cargue') !== false;
});

if (count($relevantCols) > 0) {
    foreach ($relevantCols as $col) {
        echo "  ✓ {$col->Field} ({$col->Type})\n";
    }
} else {
    echo "  ⚠️ No hay columnas con 'fecha' o 'cargue'\n";
}

// 5. Buscar en extracted_data JSON
echo "\n5️⃣ DATOS EXTRAÍDOS (extracted_data JSON) EN group_cotizations\n";
echo str_repeat("═", 60) . "\n";

$groupsWithData = DB::table('group_cotizations')
    ->whereIn('id', $groupIds)
    ->select('id', 'extracted_data')
    ->get();

foreach ($groupsWithData as $group) {
    echo "Grupo ID {$group->id}:\n";
    if ($group->extracted_data) {
        $data = json_decode($group->extracted_data, true);
        if (is_array($data)) {
            echo "  ✓ extracted_data (JSON):\n";
            foreach ($data as $key => $value) {
                if (stripos($key, 'fecha') !== false || stripos($key, 'cargue') !== false) {
                    echo "    ⭐ {$key}: {$value}\n";
                } else if (!is_array($value) && !is_object($value)) {
                    echo "      {$key}: " . (strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value) . "\n";
                }
            }
        }
    } else {
        echo "  ⚠️ Sin datos extraídos\n";
    }
}

// 6. Verificar si fue creado por API analizando created_from_chat
echo "\n6️⃣ ANÁLISIS DE CREACIÓN (¿API o Chat?)\n";
echo str_repeat("═", 60) . "\n";

$allGroups = DB::table('group_cotizations')
    ->whereIn('id', $groupIds)
    ->select('id', 'created_from_chat', 'type', 'operation_type')
    ->get();

foreach ($allGroups as $group) {
    $createdBy = $group->created_from_chat ? 'Chat' : 'API/Manual';
    echo "Grupo {$group->id}: Creado por {$createdBy} | Tipo: {$group->type} | Operación: {$group->operation_type}\n";
}

echo "\n✅ RESUMEN: Las cotizaciones fueron creadas " . ($allGroups->where('created_from_chat', 0)->count() > 0 ? 'POR API' : 'POR CHAT') . "\n";
