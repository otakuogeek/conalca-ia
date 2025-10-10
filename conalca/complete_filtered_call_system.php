<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Jobs\CallDriverJob;

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SISTEMA COMPLETO DE LLAMADAS CON FILTRADO POR VEHÍCULO ===" . PHP_EOL;
echo "Fecha: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

$cotizacionId = 31;

// PASO 1: Limpiar tablas para nueva prueba
echo "🧹 LIMPIANDO DATOS ANTERIORES..." . PHP_EOL;
DB::table('driver_call_responses')->where('cotizacion_id', $cotizacionId)->delete();
DB::table('call_driver_decisions')->where('cotizacion_model_id', $cotizacionId)->delete();
DB::table('calls')->where('quotation_id', $cotizacionId)->delete();
echo "✅ Tablas limpiadas" . PHP_EOL . PHP_EOL;

// PASO 2: Ejecutar filtrado de vehículos
echo "🔍 EJECUTANDO FILTRADO DE VEHÍCULOS..." . PHP_EOL;

// Obtener vehículo requerido de la cotización
$cotizacion = DB::table('cotizacion_models')
    ->select('id', 'vehiculo_requerido', 'ciudad_origen', 'ciudad_destino')
    ->where('id', $cotizacionId)
    ->first();

if (!$cotizacion) {
    echo "❌ Error: Cotización $cotizacionId no encontrada" . PHP_EOL;
    exit(1);
}

// Primera comparación: cotizacion.vehiculo_requerido → vehicle_class.nomcoti
$vehicleClass = DB::table('vehicle_class')
    ->select('Codigo', 'Nombre', 'nomcoti')
    ->where('nomcoti', $cotizacion->vehiculo_requerido)
    ->first();

if (!$vehicleClass) {
    echo "❌ Error: Vehículo '{$cotizacion->vehiculo_requerido}' no encontrado en vehicle_class" . PHP_EOL;
    exit(1);
}

// Segunda comparación: vehicle_class.Nombre = vehicle_owner_holder_driver.Clasevehiculo
$choferes = DB::table('vehicle_owner_holder_driver')
    ->select('id', 'Conductor', 'Telefonoconductor', 'Clasevehiculo', 'Placa', 'Carroceria')
    ->where('Clasevehiculo', $vehicleClass->Nombre)
    ->get();

echo "✅ Filtrado completado: {$choferes->count()} chofer(es) elegibles" . PHP_EOL;
echo "   - Vehículo requerido: {$cotizacion->vehiculo_requerido}" . PHP_EOL;
echo "   - Clase de vehículo: {$vehicleClass->Nombre}" . PHP_EOL . PHP_EOL;

if ($choferes->count() == 0) {
    echo "❌ No hay choferes disponibles con el vehículo requerido" . PHP_EOL;
    exit(1);
}

// PASO 3: Crear registro de llamada principal
echo "📞 CREANDO REGISTRO DE LLAMADA..." . PHP_EOL;
$callId = DB::table('calls')->insertGetId([
    'quotation_id' => $cotizacionId,
    'status' => 'calling',
    'calls_made' => 0,
    'total_drivers' => $choferes->count(),
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "✅ Llamada creada con ID: $callId" . PHP_EOL . PHP_EOL;

// PASO 4: Procesar cada chofer elegible
echo "🚀 INICIANDO LLAMADAS A CHOFERES ELEGIBLES..." . PHP_EOL;

foreach ($choferes as $index => $chofer) {
    echo "   " . ($index + 1) . ". Procesando: {$chofer->Conductor}" . PHP_EOL;
    
    // Limpiar teléfono
    $telefonoOriginal = $chofer->Telefonoconductor;
    if (strpos($telefonoOriginal, '-') !== false) {
        $telefonoOriginal = explode('-', $telefonoOriginal)[0];
    }
    $telefono = preg_replace('/[^0-9]/', '', $telefonoOriginal);
    if (strlen($telefono) == 10) {
        $telefono = '+57' . $telefono;
    }
    
    // Preparar datos del conductor
    $driverData = [
        'id' => $chofer->id,
        'name' => $chofer->Conductor,
        'phone_number' => $telefono,
        'type_vehicle' => $chofer->Clasevehiculo,
        'plate' => $chofer->Placa,
        'carroceria' => $chofer->Carroceria
    ];
    
    echo "      - Teléfono: $telefono" . PHP_EOL;
    echo "      - Vehículo: {$chofer->Clasevehiculo}" . PHP_EOL;
    echo "      - Placa: {$chofer->Placa}" . PHP_EOL;
    
    try {
        // Crear y despachar job de llamada
        $job = new CallDriverJob(
            $driverData,
            "Oferta de transporte - {$cotizacion->ciudad_origen} a {$cotizacion->ciudad_destino}",
            $cotizacionId,
            $choferes->count(),
            $callId
        );
        
        dispatch($job);
        echo "      ✅ Job despachado exitosamente" . PHP_EOL;
        
    } catch (\Exception $e) {
        echo "      ❌ Error al despachar job: " . $e->getMessage() . PHP_EOL;
    }
    
    echo "      ---" . PHP_EOL;
    
    // Pequeña pausa entre llamadas
    sleep(1);
}

echo PHP_EOL . "📊 RESUMEN FINAL:" . PHP_EOL;
echo "✅ Cotización: $cotizacionId ({$cotizacion->ciudad_origen} → {$cotizacion->ciudad_destino})" . PHP_EOL;
echo "✅ Vehículo requerido: {$cotizacion->vehiculo_requerido}" . PHP_EOL;
echo "✅ Choferes elegibles: {$choferes->count()}" . PHP_EOL;
echo "✅ Jobs despachados: {$choferes->count()}" . PHP_EOL;
echo "✅ ID de llamada principal: $callId" . PHP_EOL;

echo PHP_EOL . "🔔 MONITOREO:" . PHP_EOL;
echo "1. Verificar estado: php verify_call_status.php" . PHP_EOL;
echo "2. Monitor tiempo real: php real_time_monitor.php" . PHP_EOL;
echo "3. Reporte completo: php final_call_report.php" . PHP_EOL;

echo PHP_EOL . "=== SISTEMA DE LLAMADAS INICIADO ===" . PHP_EOL;