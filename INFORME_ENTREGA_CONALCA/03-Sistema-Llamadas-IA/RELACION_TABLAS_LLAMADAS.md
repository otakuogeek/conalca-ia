# Relación entre Tablas de Llamadas

## Resumen

Se ha establecido una relación bidireccional entre las tablas `llamadas_conductores` y `driver_call_responses` para permitir el acceso completo a los datos de ElevenLabs y la información del conductor.

## Estructura de las Tablas

### `llamadas_conductores`
Esta tabla almacena la información completa del conductor y su disponibilidad:

```sql
- id (PK)
- identificador_unico
- cotizacion_id
- group_cotization_id
- nombre_conductor
- telefono
- placa
- tipo_vehiculo
- score (decimal 0-10)
- estado_llamada (pendiente, en_progreso, completada, fallida)
- driver_call_response_id (FK) ← NUEVA COLUMNA
- call_id (puede almacenar conversation_id legacy)
- ... otros campos de conductor y vehículo
```

### `driver_call_responses`
Esta tabla almacena los detalles de la llamada de ElevenLabs:

```sql
- id (PK)
- cotizacion_id
- driver_id
- driver_name
- driver_phone
- call_status (calling, completed, failed)
- response_status (pending, accepted, rejected)
- elevenlabs_conversation_id ← IMPORTANTE
- elevenlabs_sip_call_id ← IMPORTANTE
- call_duration
- is_selected
- ... otros campos de llamada
```

## Relaciones Eloquent

### Modelo `LlamadaConductor`

```php
// Obtener la respuesta de llamada asociada
public function driverCallResponse()
{
    return $this->belongsTo(DriverCallResponse::class, 'driver_call_response_id');
}
```

### Modelo `DriverCallResponse`

```php
// Obtener el conductor asociado (relación inversa)
public function llamadaConductor()
{
    return $this->hasOne(LlamadaConductor::class, 'driver_call_response_id');
}
```

## Casos de Uso

### 1. Obtener Conversation ID desde un Conductor

```php
use App\Models\LlamadaConductor;

// Cargar conductor con su respuesta de llamada
$conductor = LlamadaConductor::with('driverCallResponse')
    ->where('telefono', '+584263774021')
    ->first();

if ($conductor && $conductor->driverCallResponse) {
    $conversationId = $conductor->driverCallResponse->elevenlabs_conversation_id;
    $sipCallId = $conductor->driverCallResponse->elevenlabs_sip_call_id;
    $callStatus = $conductor->driverCallResponse->call_status;
    
    echo "Conversation ID: $conversationId\n";
    echo "Estado: $callStatus\n";
}
```

### 2. Obtener Datos del Conductor desde una Llamada

```php
use App\Models\DriverCallResponse;

// Cargar llamada con datos del conductor
$llamada = DriverCallResponse::with('llamadaConductor')
    ->where('elevenlabs_conversation_id', 'conv_xxx')
    ->first();

if ($llamada && $llamada->llamadaConductor) {
    $score = $llamada->llamadaConductor->score;
    $placa = $llamada->llamadaConductor->placa;
    $tipoVehiculo = $llamada->llamadaConductor->tipo_vehiculo;
    
    echo "Conductor: {$llamada->driver_name}\n";
    echo "Score: $score\n";
    echo "Placa: $placa\n";
}
```

### 3. Listar Llamadas Completadas con Datos del Conductor

```php
use App\Models\DriverCallResponse;

$llamadasCompletadas = DriverCallResponse::with('llamadaConductor')
    ->where('call_status', 'completed')
    ->where('cotizacion_id', $cotizacionId)
    ->get();

foreach ($llamadasCompletadas as $llamada) {
    echo "Conductor: {$llamada->driver_name}\n";
    echo "Conversation ID: {$llamada->elevenlabs_conversation_id}\n";
    
    if ($llamada->llamadaConductor) {
        echo "Score: {$llamada->llamadaConductor->score}\n";
        echo "Vehículo: {$llamada->llamadaConductor->tipo_vehiculo}\n";
    }
    
    echo "---\n";
}
```

### 4. Actualizar Estado de Llamada y Conductor Simultáneamente

```php
use App\Models\LlamadaConductor;
use App\Models\DriverCallResponse;
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($conductorId, $conversationId) {
    // Buscar conductor
    $conductor = LlamadaConductor::find($conductorId);
    
    // Crear registro de llamada
    $callResponse = DriverCallResponse::create([
        'cotizacion_id' => $conductor->cotizacion_id,
        'driver_id' => $conductor->chofer_id_local ?? $conductor->id,
        'driver_name' => $conductor->nombre_conductor,
        'driver_phone' => $conductor->telefono,
        'vehicle_type' => $conductor->tipo_vehiculo,
        'vehicle_plate' => $conductor->placa,
        'call_status' => 'calling',
        'response_status' => 'pending',
        'elevenlabs_conversation_id' => $conversationId,
        'elevenlabs_sip_call_id' => $sipCallId ?? null,
    ]);
    
    // Vincular conductor con la llamada
    $conductor->update([
        'driver_call_response_id' => $callResponse->id,
        'estado_llamada' => 'en_progreso',
        'call_id' => $conversationId, // También guardarlo aquí para compatibilidad
        'fecha_llamada' => now(),
    ]);
});
```

### 5. Buscar por Conversation ID

```php
// Método 1: Desde DriverCallResponse
$llamada = DriverCallResponse::where('elevenlabs_conversation_id', 'conv_xxx')->first();

// Método 2: Desde LlamadaConductor (usando join)
$conductor = LlamadaConductor::whereHas('driverCallResponse', function($query) use ($conversationId) {
    $query->where('elevenlabs_conversation_id', $conversationId);
})->with('driverCallResponse')->first();
```

### 6. Obtener Estadísticas de Llamadas por Cotización

```php
use App\Models\DriverCallResponse;

$stats = DriverCallResponse::where('cotizacion_id', $cotizacionId)
    ->selectRaw('
        COUNT(*) as total_llamadas,
        SUM(CASE WHEN call_status = "completed" THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN response_status = "accepted" THEN 1 ELSE 0 END) as aceptadas,
        SUM(CASE WHEN is_selected = 1 THEN 1 ELSE 0 END) as seleccionados,
        AVG(call_duration) as duracion_promedio
    ')
    ->first();

echo "Total llamadas: {$stats->total_llamadas}\n";
echo "Completadas: {$stats->completadas}\n";
echo "Aceptadas: {$stats->aceptadas}\n";
echo "Seleccionados: {$stats->seleccionados}\n";
```

## Flujo de Trabajo Recomendado

### Cuando se inicia una llamada con ElevenLabs:

1. **Buscar o crear** el conductor en `llamadas_conductores`
2. **Crear** el registro en `driver_call_responses` con el `elevenlabs_conversation_id`
3. **Actualizar** `llamadas_conductores` vinculando el `driver_call_response_id`

### Ejemplo completo:

```php
use App\Services\ElevenLabsCallService;
use App\Models\LlamadaConductor;
use App\Models\DriverCallResponse;

// 1. Obtener conductor
$conductor = LlamadaConductor::find($id);

// 2. Iniciar llamada con ElevenLabs
$elevenLabsService = app(ElevenLabsCallService::class);
$result = $elevenLabsService->makeDirectSipCall($conductor->telefono, [
    'conductor_id' => $conductor->id,
    'conductor_nombre' => $conductor->nombre_conductor,
    'cotizacion_id' => $conductor->cotizacion_id,
]);

if ($result['success']) {
    // 3. Crear registro de llamada
    $callResponse = DriverCallResponse::create([
        'cotizacion_id' => $conductor->cotizacion_id,
        'driver_id' => $conductor->chofer_id_local ?? $conductor->id,
        'driver_name' => $conductor->nombre_conductor,
        'driver_phone' => $conductor->telefono,
        'vehicle_type' => $conductor->tipo_vehiculo,
        'vehicle_plate' => $conductor->placa,
        'call_status' => 'calling',
        'response_status' => 'pending',
        'elevenlabs_conversation_id' => $result['conversation_id'],
        'elevenlabs_sip_call_id' => $result['sip_call_id'],
    ]);
    
    // 4. Vincular conductor con llamada
    $conductor->update([
        'driver_call_response_id' => $callResponse->id,
        'estado_llamada' => 'en_progreso',
        'call_id' => $result['conversation_id'],
        'fecha_llamada' => now(),
    ]);
}
```

## Consultas Útiles SQL

### Ver llamadas con todos los datos relacionados:

```sql
SELECT 
    lc.id,
    lc.nombre_conductor,
    lc.telefono,
    lc.placa,
    lc.score,
    lc.estado_llamada,
    dcr.elevenlabs_conversation_id,
    dcr.elevenlabs_sip_call_id,
    dcr.call_status,
    dcr.response_status,
    dcr.call_duration
FROM llamadas_conductores lc
LEFT JOIN driver_call_responses dcr ON lc.driver_call_response_id = dcr.id
WHERE lc.cotizacion_id = ?;
```

### Encontrar conductores sin llamada asignada:

```sql
SELECT * FROM llamadas_conductores 
WHERE driver_call_response_id IS NULL 
AND estado_llamada = 'pendiente';
```

### Llamadas con conversation_id pero sin conductor vinculado:

```sql
SELECT * FROM driver_call_responses dcr
LEFT JOIN llamadas_conductores lc ON dcr.id = lc.driver_call_response_id
WHERE lc.id IS NULL;
```

## Migración Aplicada

```php
Schema::table('llamadas_conductores', function (Blueprint $table) {
    $table->unsignedBigInteger('driver_call_response_id')->nullable()->after('call_id');
    $table->index('driver_call_response_id');
});
```

## Notas Importantes

1. **driver_call_response_id** es nullable porque:
   - Conductores pueden existir antes de ser llamados
   - Permite flexibilidad en el flujo de trabajo

2. **call_id** se mantiene para compatibilidad legacy
   - Puede almacenar conversation_id directamente
   - Útil para búsquedas rápidas sin JOIN

3. **Eager Loading** recomendado:
   - Siempre usar `with('driverCallResponse')` o `with('llamadaConductor')`
   - Evita el problema N+1 de consultas

4. **Índices**:
   - `driver_call_response_id` está indexado para búsquedas rápidas
   - `elevenlabs_conversation_id` debería tener índice si se busca frecuentemente

## Testing

Para probar la relación:

```bash
php test_relacion_llamadas.php
```

O usar Tinker:

```bash
php artisan tinker

>>> $llamada = \App\Models\LlamadaConductor::with('driverCallResponse')->first();
>>> $llamada->driverCallResponse->elevenlabs_conversation_id;
```
