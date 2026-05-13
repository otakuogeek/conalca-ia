<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Script temporal para sincronizar extracted_data -> cotizacion_models del grupo 690
$groupId = 690;
$group = DB::table('group_cotizations')->where('id', $groupId)->first();
$extracted = json_decode($group->extracted_data, true);

echo "📦 extracted_data del grupo {$groupId}:\n";
print_r($extracted[0] ?? 'NO HAY DATOS');
echo "\n\n";

if (!empty($extracted[0])) {
    $routeData = $extracted[0];
    
    // Verificar si ya existe
    $existingCount = \App\Models\CotizacionModel::where('group_cotization_id', $groupId)->count();
    echo "Registros existentes en cotizacion_models: {$existingCount}\n";
    
    if ($existingCount > 0) {
        echo "⚠️  Ya existen registros, ELIMINANDO primero...\n";
        \App\Models\CotizacionModel::where('group_cotization_id', $groupId)->delete();
    }
    
    // Crear cotizacion_models desde extracted_data
    $cotizacion = new \App\Models\CotizacionModel();
    $cotizacion->group_cotization_id = $groupId;
    $cotizacion->ciudad_origen = $routeData['origen'] ?? null;
    $cotizacion->ciudad_destino = $routeData['destino'] ?? null;
    $cotizacion->peso_mercancia = $routeData['peso_kg'] ?? $routeData['pesoMercancia'] ?? $routeData['peso_mercancia'] ?? 0;
    $cotizacion->cantidad = $routeData['cantidad'] ?? 0;
    $cotizacion->tipo_producto = $routeData['producto'] ?? null;
    $cotizacion->valor_declarado = $routeData['valor_declarado'] ?? 0;
    $cotizacion->vehiculo_requerido = $routeData['vehiculo'] ?? null;
    $cotizacion->save();
    
    echo "\n✅ cotizacion_models CREADO desde extracted_data\n";
    echo "ID: {$cotizacion->id}\n";
    echo "peso_mercancia: {$cotizacion->peso_mercancia} kg\n";
    echo "origen: {$cotizacion->ciudad_origen}\n";
    echo "destino: {$cotizacion->ciudad_destino}\n";
} else {
    echo "❌ No hay datos en extracted_data\n";
}
