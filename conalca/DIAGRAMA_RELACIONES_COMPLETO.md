# Diagrama Completo de Relaciones - Sistema de Llamadas CONALCA

## 📊 Arquitectura de Relaciones

Este documento describe todas las relaciones entre los modelos del sistema de llamadas y cotizaciones.

---

## 🎯 Modelos Principales y sus Relaciones

### 1. `CotizacionModel` (cotizacion_models)

**Relaciones definidas:**

```php
// Pertenece a
public function client(): BelongsTo
    -> Client (client_id)

public function pricing(): BelongsTo
    -> Pricing (pricing_id)

public function groupCotization(): BelongsTo
    -> GroupCotization (group_cotization_id)

public function selectedDriver(): BelongsTo
    -> VehicleOwnerHolderDriver (selected_driver_id)

public function producto(): BelongsTo
    -> Product (tipo_producto -> producto_codigo)

// Tiene muchos
public function llamadas(): HasMany
    -> Llamada (id_cotizacion)
    
public function llamadasConductores(): HasMany
    -> LlamadaConductor (cotizacion_id)

public function driverCallResponses(): HasMany
    -> DriverCallResponse (cotizacion_id)

public function decisions(): HasMany
    -> CallDriverDecision (cotizacion_model_id)

public function notes(): HasMany
    -> CotizationNote (cotization_model_id)

// Tiene uno
public function solicitud(): HasOne
    -> SolicitudTransporte (cotizacion_model_id)

// Muchos a muchos
public function drivers(): BelongsToMany
    -> VehicleOwnerHolderDriver
    through: call_driver_decisions
    pivot: decision, timestamps
```

**Uso típico:**
```php
// Cargar cotización con todas sus llamadas y conductores
$cotizacion = CotizacionModel::with([
    'llamadasConductores.driverCallResponse',
    'driverCallResponses.llamadaConductor',
    'selectedDriver'
])->find($id);

// Obtener todas las llamadas completadas
$llamadasCompletadas = $cotizacion->driverCallResponses()
    ->where('call_status', 'completed')
    ->get();
```

---

### 2. `GroupCotization` (group_cotizations)

**Relaciones definidas:**

```php
// Pertenece a
public function user(): BelongsTo
    -> User (user_id)

public function client(): BelongsTo
    -> Client (client_id)

// Tiene muchos
public function cotizaciones(): HasMany
    -> CotizacionModel (group_cotization_id)
    eager load: producto

public function llamadasConductores(): HasMany
    -> LlamadaConductor (group_cotization_id)
```

**Métodos de negocio:**
```php
public function tieneAceptada(): bool
    // Verifica si alguna cotización fue aceptada

public function todasRespondidas(): bool
    // Verifica si todas las cotizaciones tienen respuesta
```

**Uso típico:**
```php
// Obtener grupo con todas las cotizaciones y conductores llamados
$grupo = GroupCotization::with([
    'cotizaciones.producto',
    'llamadasConductores.driverCallResponse'
])->find($id);

// Verificar estado del grupo
if ($grupo->tieneAceptada()) {
    // Procesar grupo aceptado
}
```

---

### 3. `LlamadaConductor` (llamadas_conductores)

**Relaciones definidas:**

```php
// Pertenece a
public function cotizacion(): BelongsTo
    -> CotizacionModel (cotizacion_id)

public function groupCotization(): BelongsTo
    -> GroupCotization (group_cotization_id)

public function driverCallResponse(): BelongsTo
    -> DriverCallResponse (driver_call_response_id)

// Tiene muchos
public function llamadas(): HasMany
    -> Llamada (conductor_id)
```

**Scopes disponibles:**
```php
scopePendientes()         // estado_llamada = 'pendiente'
scopeDisponibles()        // disponible = true
scopePorCotizacion($id)   // cotizacion_id = $id
scopePorGrupo($id)        // group_cotization_id = $id
```

**Métodos estáticos:**
```php
generarIdentificador($cotizacionId, $telefono): string
createFromArcangel($vehiculo, $ciudad, $cotizacionId, $groupId, $cotizacion): self
createFromLocal($driver, $ciudad): self
```

**Uso típico:**
```php
// Crear conductor desde Arcángel
$conductor = LlamadaConductor::createFromArcangel(
    $vehiculoData,
    'Bogotá',
    $cotizacionId,
    $groupId,
    $cotizacionData
);

// Buscar con relaciones
$conductor = LlamadaConductor::with('driverCallResponse')
    ->porCotizacion($cotizacionId)
    ->pendientes()
    ->disponibles()
    ->get();

// Obtener conversation_id
$conversationId = $conductor->driverCallResponse?->elevenlabs_conversation_id;
```

---

### 4. `DriverCallResponse` (driver_call_responses)

**Relaciones definidas:**

```php
// Pertenece a
public function cotizacion(): BelongsTo
    -> CotizacionModel (cotizacion_id)

public function driver(): BelongsTo
    -> VehicleOwnerHolderDriver (driver_id)

// Tiene uno
public function llamadaConductor(): HasOne
    -> LlamadaConductor (driver_call_response_id)
```

**Scopes disponibles:**
```php
scopeAccepted()   // response_status = 'accepted'
scopeRejected()   // response_status = 'rejected'
scopePending()    // response_status = 'pending'
scopeSelected()   // is_selected = true
```

**Uso típico:**
```php
// Buscar por conversation_id
$llamada = DriverCallResponse::where('elevenlabs_conversation_id', $convId)
    ->with('llamadaConductor')
    ->first();

// Obtener datos del conductor
$score = $llamada->llamadaConductor->score;
$placa = $llamada->llamadaConductor->placa;

// Filtrar aceptadas
$aceptadas = DriverCallResponse::accepted()
    ->where('cotizacion_id', $id)
    ->get();
```

---

### 5. `VehicleOwnerHolderDriver` (vehicle_owner_holder_driver)

**Relaciones definidas:**

```php
// Tiene muchos
public function llamadas(): HasMany
    -> Llamada (chofer_id)

public function callDecisions(): HasMany
    -> CallDriverDecision (driver_id)

public function driverCallResponses(): HasMany
    -> DriverCallResponse (driver_id)

// Muchos a muchos
public function cotizaciones(): BelongsToMany
    -> CotizacionModel
    through: call_driver_decisions
    pivot: decision, timestamps
```

**Atributos virtuales:**
```php
getNameAttribute()              -> Conductor
getPhoneNumberAttribute()       -> Telefonoconductor
getVehicleTypeAttribute()       -> Clasevehiculo
getVehiclePlateAttribute()      -> Placa
getDocumentNumberAttribute()    -> Cedula
getFormattedPhoneAttribute()    -> Teléfono en formato +57xxx
```

**Scopes:**
```php
scopeWithValidPhone()  // Filtra conductores con teléfono válido
```

**Uso típico:**
```php
// Buscar conductor con todas sus llamadas
$driver = VehicleOwnerHolderDriver::with([
    'driverCallResponses.llamadaConductor',
    'cotizaciones'
])->find($id);

// Obtener teléfono formateado
$phone = $driver->formatted_phone;

// Verificar decisiones
$aceptadas = $driver->callDecisions()->accepted()->count();
```

---

### 6. `Llamada` (llamadas) - Tabla Legacy

**Relaciones definidas:**

```php
// Pertenece a
public function cotizacion(): BelongsTo
    -> CotizacionModel (id_cotizacion)

public function chofer(): BelongsTo
    -> VehicleOwnerHolderDriver (chofer_id)

public function conductor(): BelongsTo
    -> LlamadaConductor (conductor_id)
```

**Scopes:**
```php
scopeByStatus($status)
scopeByCotizacion($cotizacionId)
scopeByChofer($choferId)
```

**Constantes de estado:**
```php
STATUS_PENDIENTE, STATUS_EN_CURSO, STATUS_FINALIZADA
STATUS_ACEPTADA, STATUS_RECHAZADA

CALL_STATUS_INITIATED, CALL_STATUS_RINGING, CALL_STATUS_ANSWERED
CALL_STATUS_BUSY, CALL_STATUS_NO_ANSWER, CALL_STATUS_FAILED
CALL_STATUS_COMPLETED, CALL_STATUS_CANCELLED
```

---

### 7. `CallDriverDecision` (call_driver_decisions)

**Relaciones definidas:**

```php
// Pertenece a
public function driver(): BelongsTo
    -> VehicleOwnerHolderDriver (driver_id)

public function cotizacion(): BelongsTo
    -> CotizacionModel (cotizacion_model_id)
```

**Scopes:**
```php
scopeAccepted()   // decision = true
scopeRejected()   // decision = false
```

---

## 🔗 Flujos de Relaciones Comunes

### Flujo 1: De Cotización a Conversation ID

```php
// Opción A: A través de LlamadaConductor
$cotizacion = CotizacionModel::with('llamadasConductores.driverCallResponse')->find($id);
foreach ($cotizacion->llamadasConductores as $conductor) {
    $convId = $conductor->driverCallResponse?->elevenlabs_conversation_id;
}

// Opción B: Directamente desde DriverCallResponse
$cotizacion = CotizacionModel::with('driverCallResponses')->find($id);
foreach ($cotizacion->driverCallResponses as $response) {
    $convId = $response->elevenlabs_conversation_id;
}
```

### Flujo 2: De Conversation ID a Datos Completos del Conductor

```php
$response = DriverCallResponse::with('llamadaConductor')
    ->where('elevenlabs_conversation_id', $convId)
    ->first();

if ($response && $response->llamadaConductor) {
    $datos = [
        'nombre' => $response->driver_name,
        'telefono' => $response->driver_phone,
        'placa' => $response->llamadaConductor->placa,
        'score' => $response->llamadaConductor->score,
        'tipo_vehiculo' => $response->llamadaConductor->tipo_vehiculo,
        'estado_llamada' => $response->call_status,
        'respuesta' => $response->response_status,
    ];
}
```

### Flujo 3: Crear Llamada Completa

```php
DB::transaction(function() use ($conductorData, $conversationId) {
    // 1. Crear/actualizar conductor
    $conductor = LlamadaConductor::updateOrCreate(
        ['telefono' => $conductorData['telefono'], 'cotizacion_id' => $cotizacionId],
        $conductorData
    );
    
    // 2. Crear respuesta de llamada
    $callResponse = DriverCallResponse::create([
        'cotizacion_id' => $cotizacionId,
        'driver_id' => $conductor->id,
        'driver_name' => $conductor->nombre_conductor,
        'driver_phone' => $conductor->telefono,
        'elevenlabs_conversation_id' => $conversationId,
        'call_status' => 'calling',
    ]);
    
    // 3. Vincular
    $conductor->update([
        'driver_call_response_id' => $callResponse->id,
        'estado_llamada' => 'en_progreso',
    ]);
});
```

### Flujo 4: Obtener Estadísticas de Grupo

```php
$grupo = GroupCotization::with([
    'cotizaciones.driverCallResponses',
    'llamadasConductores.driverCallResponse'
])->find($id);

$stats = [
    'total_cotizaciones' => $grupo->cotizaciones->count(),
    'total_conductores' => $grupo->llamadasConductores->count(),
    'llamadas_completadas' => $grupo->llamadasConductores()
        ->whereHas('driverCallResponse', function($q) {
            $q->where('call_status', 'completed');
        })->count(),
    'respuestas_aceptadas' => $grupo->llamadasConductores()
        ->whereHas('driverCallResponse', function($q) {
            $q->where('response_status', 'accepted');
        })->count(),
];
```

---

## 📝 Consultas SQL Útiles

### Ver todas las relaciones de una cotización:

```sql
SELECT 
    cm.id_cotizacion,
    cm.ciudad_origen,
    cm.ciudad_destino,
    lc.nombre_conductor,
    lc.telefono,
    lc.score,
    dcr.elevenlabs_conversation_id,
    dcr.call_status,
    dcr.response_status
FROM cotizacion_models cm
LEFT JOIN llamadas_conductores lc ON cm.id_cotizacion = lc.cotizacion_id
LEFT JOIN driver_call_responses dcr ON lc.driver_call_response_id = dcr.id
WHERE cm.id_cotizacion = ?;
```

### Conductores con llamadas activas:

```sql
SELECT 
    lc.*,
    dcr.elevenlabs_conversation_id,
    dcr.call_status,
    dcr.created_at as call_initiated_at
FROM llamadas_conductores lc
INNER JOIN driver_call_responses dcr ON lc.driver_call_response_id = dcr.id
WHERE dcr.call_status IN ('calling', 'ringing', 'answered')
ORDER BY dcr.created_at DESC;
```

### Resumen de llamadas por cotización:

```sql
SELECT 
    cm.id_cotizacion,
    COUNT(DISTINCT lc.id) as total_conductores,
    COUNT(DISTINCT dcr.id) as total_llamadas,
    SUM(CASE WHEN dcr.call_status = 'completed' THEN 1 ELSE 0 END) as completadas,
    SUM(CASE WHEN dcr.response_status = 'accepted' THEN 1 ELSE 0 END) as aceptadas,
    SUM(CASE WHEN dcr.is_selected = 1 THEN 1 ELSE 0 END) as seleccionados
FROM cotizacion_models cm
LEFT JOIN llamadas_conductores lc ON cm.id_cotizacion = lc.cotizacion_id
LEFT JOIN driver_call_responses dcr ON lc.driver_call_response_id = dcr.id
GROUP BY cm.id_cotizacion;
```

---

## 🎨 Diagrama Visual de Relaciones

```
┌─────────────────────┐
│  GroupCotization    │
│  - id               │
│  - user_id          │
│  - client_id        │
└──────────┬──────────┘
           │ hasMany
           ▼
┌─────────────────────┐         ┌──────────────────────┐
│ CotizacionModel     │ hasMany │  LlamadaConductor    │
│  - id_cotizacion    ├────────>│  - id                │
│  - group_cotization │         │  - cotizacion_id     │
│  - selected_driver  │         │  - driver_call_resp  │◄─┐
└──────────┬──────────┘         └──────────┬───────────┘  │
           │ hasMany                       │               │
           │                               │ belongsTo     │
           ▼                               ▼               │
┌─────────────────────┐         ┌──────────────────────┐  │
│ DriverCallResponse  │         │ DriverCallResponse   │  │
│  - id               │         │  - id                │  │
│  - cotizacion_id    │         │  - driver_id         │  │
│  - driver_id        │         │  - elevenlabs_conv   │  │
│  - elevenlabs_conv  │         │  - call_status       │──┘
│  - call_status      │         └──────────────────────┘
└─────────────────────┘                    │ belongsTo
           │ belongsTo                     ▼
           ▼                    ┌──────────────────────┐
┌─────────────────────┐         │VehicleOwnerHolder    │
│VehicleOwnerHolder   │         │Driver                │
│Driver               │         │  - id                │
│  - id               │         │  - Conductor         │
│  - Conductor        │         │  - Telefonoconductor │
│  - Placa            │         └──────────────────────┘
└─────────────────────┘
```

---

## ✅ Checklist de Relaciones Implementadas

- ✅ CotizacionModel → LlamadaConductor (hasMany)
- ✅ CotizacionModel → DriverCallResponse (hasMany)
- ✅ GroupCotization → LlamadaConductor (hasMany)
- ✅ LlamadaConductor → DriverCallResponse (belongsTo)
- ✅ DriverCallResponse → LlamadaConductor (hasOne)
- ✅ DriverCallResponse → CotizacionModel (belongsTo)
- ✅ DriverCallResponse → VehicleOwnerHolderDriver (belongsTo)
- ✅ VehicleOwnerHolderDriver → DriverCallResponse (hasMany)
- ✅ VehicleOwnerHolderDriver → CallDriverDecision (hasMany)
- ✅ VehicleOwnerHolderDriver → CotizacionModel (belongsToMany)

---

## 🚀 Ejemplos de Uso en Controladores

```php
// En ConversationalAgentController o similar
public function getConductorByConversationId($conversationId)
{
    return DriverCallResponse::with('llamadaConductor')
        ->where('elevenlabs_conversation_id', $conversationId)
        ->first();
}

public function getCotizacionWithCalls($cotizacionId)
{
    return CotizacionModel::with([
        'llamadasConductores.driverCallResponse',
        'driverCallResponses.llamadaConductor',
        'selectedDriver'
    ])->find($cotizacionId);
}

public function getGrupoStats($grupoId)
{
    $grupo = GroupCotization::with([
        'cotizaciones',
        'llamadasConductores.driverCallResponse'
    ])->find($grupoId);
    
    return [
        'total_conductores' => $grupo->llamadasConductores->count(),
        'completadas' => $grupo->llamadasConductores->filter(
            fn($lc) => $lc->driverCallResponse?->call_status === 'completed'
        )->count(),
        'aceptadas' => $grupo->llamadasConductores->filter(
            fn($lc) => $lc->driverCallResponse?->response_status === 'accepted'
        )->count(),
    ];
}
```

---

**Última actualización:** 5 de diciembre de 2025
**Versión del sistema:** Laravel 10 + Livewire 3
