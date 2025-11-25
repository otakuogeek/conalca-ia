<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\CallDriverJob;
use App\Models\CotizacionModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

if ($argc < 4) {
    echo "Uso: php direct_improved_call.php <driver_id> <phone_number> <quotation_id>\n";
    echo "Ejemplo: php direct_improved_call.php 1 +573123254544 31\n";
    exit(1);
}

$driverId = intval($argv[1]);
$phoneNumber = $argv[2];
$quotationId = intval($argv[3]);

echo "=== PRUEBA DE LLAMADA MEJORADA ===\n";
echo "Driver ID: {$driverId}\n";
echo "Teléfono: {$phoneNumber}\n";
echo "Cotización ID: {$quotationId}\n\n";

// Verificar que existe la cotización
$quotation = CotizacionModel::find($quotationId);
if (!$quotation) {
    echo "ERROR: No se encuentra la cotización con ID {$quotationId}\n";
    exit(1);
}

echo "✅ Cotización encontrada: {$quotation->ciudad_origen} → {$quotation->ciudad_destino}\n";
echo "   Tipo: {$quotation->tipo}\n\n";

// Buscar el conductor en la tabla vehicle_owner_holder_driver
$driverInfo = DB::table('vehicle_owner_holder_driver')->where('id', $driverId)->first();

if (!$driverInfo) {
    echo "❌ Error: Conductor con ID $driverId no encontrado en vehicle_owner_holder_driver\n";
    exit(1);
}

echo "✅ Conductor encontrado: {$driverInfo->Conductor}\n";
echo "   Documento: {$driverInfo->Cedula}\n";
echo "   Placa: {$driverInfo->Placa}\n";
echo "   Teléfono: {$phoneNumber}\n";
echo "   Tipo de vehículo: {$driverInfo->Clasevehiculo}\n\n";

// Construir el array del conductor en el formato correcto
$driverArray = [
    'id' => $driverInfo->id,
    'name' => $driverInfo->Conductor,
    'phone_number' => $phoneNumber,
    'type_vehicle' => $driverInfo->Clasevehiculo,
    'vehicle_plate' => $driverInfo->Placa ?? 'N/A',
    'identity_document' => $driverInfo->Cedula
];

// Crear registro de llamada
echo "📞 Creando registro de llamada...\n";
$callId = DB::table('calls')->insertGetId([
    'quotation_id' => $quotationId,
    'total_drivers' => 1,
    'calls_made' => 0,
    'status' => 'calling',
    'created_at' => now(),
    'updated_at' => now()
]);

echo "✅ Llamada creada con ID: $callId\n\n";

try {
    // Ejecutar el job directamente (sin queue) con los parámetros correctos
    $job = new CallDriverJob(
        $driverArray,                               // Array del conductor
        "Llamada de prueba con agente mejorado",    // Prompt
        $quotationId,                               // ID de cotización
        1,                                          // Total de conductores
        $callId                                     // ID de llamada
    );
    
    $job->handle();
    
    echo "\n✅ Llamada iniciada exitosamente!\n";
    echo "Verifica los logs en storage/logs/laravel.log para seguimiento.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error al procesar la llamada:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    
    Log::error('Error en prueba directa de llamada', [
        'driver_id' => $driverId,
        'phone' => $phoneNumber,
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}