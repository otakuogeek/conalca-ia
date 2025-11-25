<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN GRUPO #66 (RECIÉN CREADO) ===\n\n";

// Obtener datos del grupo #66
$group66 = DB::table('group_cotizations')
    ->where('id', 66)
    ->first();

if ($group66) {
    echo "GRUPO #66:\n";
    echo "- ID: {$group66->id}\n";
    echo "- User ID: {$group66->user_id}\n";
    echo "- Client ID: {$group66->client_id}\n";
    echo "- Type: {$group66->type}\n";
    echo "- Status: {$group66->status}\n";
    echo "- Reference: {$group66->reference}\n";
    echo "- Created from chat: " . ($group66->created_from_chat ? 'SÍ ✅' : 'NO ❌') . "\n";
    echo "- OpenAI Thread ID: " . ($group66->openai_thread_id ?? 'NULL') . "\n";
    echo "- Created at: {$group66->created_at}\n\n";

    // Obtener cotizaciones relacionadas
    $cotizaciones66 = DB::table('cotizacion_models')
        ->where('group_cotization_id', 66)
        ->get();

    echo "COTIZACIONES ASOCIADAS AL GRUPO #66:\n";
    echo "Total: " . count($cotizaciones66) . "\n\n";

    if (count($cotizaciones66) > 0) {
        foreach ($cotizaciones66 as $index => $cotizacion) {
            echo "Cotización #" . ($index + 1) . ":\n";
            echo "- ID: {$cotizacion->id}\n";
            echo "- Group ID: {$cotizacion->group_cotization_id}\n";
            echo "- Pricing ID: {$cotizacion->pricing_id}\n";
            echo "- Origen: {$cotizacion->ciudad_origen}\n";
            echo "- Destino: {$cotizacion->ciudad_destino}\n";
            echo "- Peso: {$cotizacion->peso_mercancia}\n";
            echo "- Vehículo: {$cotizacion->vehiculo_requerido}\n";
            echo "- Valor declarado: {$cotizacion->valor_declarado}\n";
            echo "- Decisión cliente: {$cotizacion->decision_cliente}\n";
            echo "- Active: " . ($cotizacion->active ? 'SÍ' : 'NO') . "\n";
            echo "- Created at: {$cotizacion->created_at}\n";
            echo "---\n";
        }
        
        echo "\n✅ ¡EXCELENTE! El grupo #66 tiene " . count($cotizaciones66) . " cotizaciones asociadas.\n";
        echo "✅ Esto significa que las rutas DEBERÍAN aparecer en el drag & drop.\n";
        
    } else {
        echo "❌ PROBLEMA: El grupo #66 no tiene cotizaciones asociadas.\n";
        echo "❌ Esto significa que las rutas NO aparecerán en el drag & drop.\n";
    }

    // Verificar el cliente asociado
    $client = DB::table('clients')->where('id', $group66->client_id)->first();
    if ($client) {
        echo "\nCLIENTE ASOCIADO:\n";
        echo "- Nombre: {$client->cliente}\n";
        echo "- Documento: {$client->documento}\n";
        echo "- Teléfono: {$client->telefono}\n";
    }

    // Análisis del sistema usado
    echo "\n=== ANÁLISIS DEL SISTEMA USADO ===\n";
    if ($group66->created_from_chat) {
        echo "✅ NUEVO SISTEMA: Grupo creado usando nuestro QuoteSaveController\n";
        echo "✅ Thread ID presente: " . ($group66->openai_thread_id ? 'SÍ' : 'NO') . "\n";
        echo "✅ Status correcto: {$group66->status}\n";
    } else {
        echo "❌ SISTEMA ANTERIOR: Grupo creado con el sistema previo\n";
        echo "⚠️  Puede que no tenga cotizaciones asociadas\n";
    }

    // Comparar con los grupos anteriores
    echo "\n=== COMPARACIÓN CON OTROS GRUPOS ===\n";
    
    $comparison = [
        ['id' => 44, 'name' => 'Grupo #44 (Funcional)'],
        ['id' => 62, 'name' => 'Grupo #62 (Problemático)'], 
        ['id' => 65, 'name' => 'Grupo #65 (Nuestro test)'],
        ['id' => 66, 'name' => 'Grupo #66 (Recién creado)']
    ];
    
    foreach ($comparison as $comp) {
        $group = DB::table('group_cotizations')->where('id', $comp['id'])->first();
        if ($group) {
            $cotizaciones = DB::table('cotizacion_models')->where('group_cotization_id', $comp['id'])->count();
            $status = $group->created_from_chat ? '🟢 Nuevo' : '🔵 Anterior';
            echo "- {$comp['name']}: {$cotizaciones} rutas | {$status} | Status: {$group->status}\n";
        }
    }

} else {
    echo "❌ Grupo #66 no encontrado\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";