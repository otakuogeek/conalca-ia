<?php

/**
 * Ejemplo práctico: Crear una llamada completa vinculando ambas tablas
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LlamadaConductor;
use App\Models\DriverCallResponse;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  Ejemplo: Crear llamada vinculando ambas tablas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Datos de ejemplo
$conductorData = [
    'nombre' => 'Juan Pérez',
    'telefono' => '+573001234567',
    'placa' => 'ABC123',
    'tipo_vehiculo' => 'Camión',
    'score' => 8.5,
    'cotizacion_id' => 1,
];

$conversationId = 'conv_ejemplo_' . time();
$sipCallId = 'SIP_ejemplo_' . time();

try {
    DB::beginTransaction();
    
    // 1. Crear o buscar conductor en llamadas_conductores
    echo "1️⃣  Creando/actualizando conductor en llamadas_conductores...\n";
    
    $conductor = LlamadaConductor::updateOrCreate(
        [
            'telefono' => $conductorData['telefono'],
            'cotizacion_id' => $conductorData['cotizacion_id'],
        ],
        [
            'identificador_unico' => LlamadaConductor::generarIdentificador(
                $conductorData['cotizacion_id'],
                $conductorData['telefono']
            ),
            'nombre_conductor' => $conductorData['nombre'],
            'placa' => $conductorData['placa'],
            'tipo_vehiculo' => $conductorData['tipo_vehiculo'],
            'score' => $conductorData['score'],
            'estado_llamada' => 'pendiente',
            'fuente' => 'local',
            'disponible' => true,
        ]
    );
    
    echo "   ✅ Conductor creado/actualizado (ID: {$conductor->id})\n\n";
    
    // 2. Crear registro en driver_call_responses
    echo "2️⃣  Creando registro de llamada en driver_call_responses...\n";
    
    $callResponse = DriverCallResponse::create([
        'cotizacion_id' => $conductor->cotizacion_id,
        'driver_id' => $conductor->id,
        'driver_name' => $conductor->nombre_conductor,
        'driver_phone' => $conductor->telefono,
        'vehicle_type' => $conductor->tipo_vehiculo,
        'vehicle_plate' => $conductor->placa,
        'call_status' => 'calling',
        'response_status' => 'pending',
        'elevenlabs_conversation_id' => $conversationId,
        'elevenlabs_sip_call_id' => $sipCallId,
        'notes' => 'Llamada de ejemplo creada por script',
    ]);
    
    echo "   ✅ Llamada creada (ID: {$callResponse->id})\n";
    echo "   📞 Conversation ID: {$callResponse->elevenlabs_conversation_id}\n\n";
    
    // 3. Vincular conductor con la llamada
    echo "3️⃣  Vinculando conductor con llamada...\n";
    
    $conductor->update([
        'driver_call_response_id' => $callResponse->id,
        'estado_llamada' => 'en_progreso',
        'call_id' => $conversationId,
        'fecha_llamada' => now(),
    ]);
    
    echo "   ✅ Vinculación completada\n\n";
    
    DB::commit();
    
    // 4. Verificar la relación
    echo "4️⃣  Verificando relaciones...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    // Recargar con relaciones
    $conductor->load('driverCallResponse');
    $callResponse->load('llamadaConductor');
    
    echo "Desde LlamadaConductor:\n";
    echo "  - ID: {$conductor->id}\n";
    echo "  - Nombre: {$conductor->nombre_conductor}\n";
    echo "  - Conversation ID: {$conductor->driverCallResponse->elevenlabs_conversation_id}\n";
    echo "  - Estado llamada: {$conductor->driverCallResponse->call_status}\n\n";
    
    echo "Desde DriverCallResponse:\n";
    echo "  - ID: {$callResponse->id}\n";
    echo "  - Conductor: {$callResponse->driver_name}\n";
    echo "  - Score del conductor: {$callResponse->llamadaConductor->score}\n";
    echo "  - Placa: {$callResponse->llamadaConductor->placa}\n\n";
    
    echo "✅ Relaciones funcionando correctamente!\n\n";
    
    // 5. Ejemplo de consulta con JOIN
    echo "5️⃣  Consulta combinada (con JOIN)...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $result = DB::table('llamadas_conductores as lc')
        ->leftJoin('driver_call_responses as dcr', 'lc.driver_call_response_id', '=', 'dcr.id')
        ->where('lc.id', $conductor->id)
        ->select([
            'lc.id',
            'lc.nombre_conductor',
            'lc.telefono',
            'lc.score',
            'lc.estado_llamada',
            'dcr.elevenlabs_conversation_id',
            'dcr.call_status',
            'dcr.response_status',
        ])
        ->first();
    
    echo "Resultado de JOIN:\n";
    echo "  ID: {$result->id}\n";
    echo "  Conductor: {$result->nombre_conductor}\n";
    echo "  Teléfono: {$result->telefono}\n";
    echo "  Score: {$result->score}\n";
    echo "  Estado Conductor: {$result->estado_llamada}\n";
    echo "  Conversation ID: {$result->elevenlabs_conversation_id}\n";
    echo "  Estado Llamada: {$result->call_status}\n";
    echo "  Respuesta: {$result->response_status}\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ EJEMPLO COMPLETADO EXITOSAMENTE\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "Archivo: {$e->getFile()}:{$e->getLine()}\n";
}
