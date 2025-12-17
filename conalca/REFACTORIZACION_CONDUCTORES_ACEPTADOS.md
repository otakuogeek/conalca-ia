# Refactorización: Sistema de Conductores Aceptados

## 📋 Cambio Realizado (6 de diciembre, 2025)

**Objetivo**: Modificar la pantalla "Gestión de Conductores" para mostrar únicamente los conductores que **aceptaron el viaje** desde la tabla `llamadas_conductores`, en lugar de usar la tabla maestra `vehicle_owner_holder_driver`.

---

## 🔄 Cambios en el Backend

### 1. `CallStatusController::show()` - Refactorizado Completo

**Ubicación**: `app/Http/Controllers/Api/CallStatusController.php` (líneas 95-139)

#### Antes (❌):
```php
// Consultaba vehicle_owner_holder_driver + CallDriverDecision
$decisions = CallDriverDecision::where('cotizacion_id', $cotizacionId)
    ->where('decision', 'aceptado')
    ->get();
```

#### Después (✅):
```php
// Ahora consulta llamadas_conductores con whereHas
$acceptedDrivers = \App\Models\LlamadaConductor::query()
    ->where('cotizacion_id', $cotizacionId)
    ->whereHas('driverCallResponse', function ($query) {
        $query->where('response_status', 'accepted');
    })
    ->with('driverCallResponse')
    ->get();
```

#### Campos Devueltos:
```json
{
  "total_to_call": 3,
  "accepted": [
    {
      "id": 100,
      "name": "SEBASTIAN DELORIAN",
      "phone": "3105672307",
      "placa": "ABC123",
      "tipo_vehiculo": "TRACTOMULA3",
      "ciudad_origen": "cartagena",
      "ciudad_destino": "funza",
      "decision_date": "2025-12-06 14:52:17"
    }
  ],
  "percentage": 33.33,
  "selected_driver_id": null
}
```

---

### 2. `CallStatusController::selectDriver()` - Validación Actualizada

**Ubicación**: Líneas 141-167

#### Cambios:
- **Validación**: Cambió de `exists:vehicle_owner_holder_driver` a `exists:llamadas_conductores`
- **Verificación**: Ahora verifica que el conductor tenga `response_status = 'accepted'` en `driver_call_responses`

```php
$llamada = \App\Models\LlamadaConductor::where('cotizacion_id', $cotizacionId)
    ->where('id', $conductorId)
    ->whereHas('driverCallResponse', function ($query) {
        $query->where('response_status', 'accepted');
    })
    ->firstOrFail();
```

---

### 3. Rutas API Agregadas

**Ubicación**: `routes/api.php`

```php
// Obtener conductores aceptados por cotización
Route::get('/calls/{cotizacionId}', [CallStatusController::class, 'show'])
    ->name('api.calls.accepted');

// Seleccionar conductor específico
Route::post('/calls/{cotizacionId}/select-driver', [CallStatusController::class, 'selectDriver'])
    ->name('api.calls.select-driver');
```

---

## 🗄️ Estructura de Base de Datos

### Tabla `llamadas_conductores`
Almacena conductores registrados para cada cotización desde llamadas ElevenLabs.

**Campos Clave**:
- `id` - ID del conductor en llamadas_conductores
- `cotizacion_id` - FK a cotizacion_models
- `nombre_conductor` - Nombre del conductor
- `telefono` - Teléfono del conductor
- `placa` - Placa del vehículo
- `tipo_vehiculo` - Tipo de vehículo (TRACTOMULA3, etc.)
- `driver_call_response_id` - FK a driver_call_responses (nullable)
- `ciudad_origen`, `ciudad_destino` - Rutas del viaje
- `estado_llamada` - Estado: pendiente, completada, fallida, cancelada

### Tabla `driver_call_responses`
Registra las decisiones de los conductores (aceptar/rechazar).

**Campos Clave**:
- `id` - ID de la respuesta
- `cotizacion_id` - FK a cotizacion_models
- `driver_id` - ID del conductor en llamadas_conductores
- `driver_name`, `driver_phone`, `vehicle_plate` - Datos del conductor
- `response_status` - **IMPORTANTE**: `pending` | `accepted` | `rejected`
- `response_time` - Timestamp de la decisión
- `elevenlabs_conversation_id` - ID de conversación ElevenLabs

### Relación en `LlamadaConductor` Model

```php
public function driverCallResponse()
{
    return $this->belongsTo(DriverCallResponse::class, 'driver_call_response_id');
}
```

---

## 🧪 Pruebas y Verificación

### Script de Verificación
**Archivo**: `test_conductores_aceptados.sh`

```bash
./test_conductores_aceptados.sh
```

**Salida Esperada**:
```
========================================================================
VERIFICACIÓN DE CONDUCTORES QUE ACEPTARON EL VIAJE
========================================================================

Cotización ID: 1 (cotización de prueba)

1. CONDUCTORES REGISTRADOS EN LLAMADAS_CONDUCTORES:
----------------------------------------------------------------
Total: 3 conductores (IDs 100, 101, 102)

2. CONDUCTORES QUE ACEPTARON (con driver_call_responses):
----------------------------------------------------------------
1 conductor aceptó: SEBASTIAN DELORIAN (id 100)

3. ESTADÍSTICAS:
----------------------------------------------------------------
Total conductores registrados: 3
Conductores que ACEPTARON: 1
Conductores que RECHAZARON: 1
Conductores SIN RESPUESTA: 1
```

### Prueba con cURL

```bash
curl -X GET "http://localhost:8000/api/calls/1" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest"
```

**Respuesta Esperada**:
```json
{
  "prompt": "Este flete consiste en transportar...",
  "vehicle_type": "CONTENEDOR 20\"",
  "ciudad_origen": "cartagena",
  "ciudad_destino": "funza",
  "total_to_call": 3,
  "accepted": [
    {
      "id": 100,
      "name": "SEBASTIAN DELORIAN",
      "phone": "3105672307",
      "placa": "ABC123",
      "tipo_vehiculo": "TRACTOMULA3",
      "ciudad_origen": "cartagena",
      "ciudad_destino": "funza",
      "decision_date": "2025-12-06 14:52:17"
    }
  ],
  "percentage": 33.33,
  "selected_driver_id": null
}
```

---

## 📊 Datos de Prueba

### Cotización ID: 1
```sql
-- Conductores registrados
INSERT INTO llamadas_conductores (id, cotizacion_id, nombre_conductor, telefono, placa, ...) VALUES
(100, 1, 'SEBASTIAN DELORIAN', '3105672307', 'ABC123', ...),
(101, 1, 'NICOLAS GARCIA', '3158142020', 'DEF987', ...),
(102, 1, 'DEIBY SALAS', '3022372269', 'GHI000', ...);

-- Respuestas de conductores
INSERT INTO driver_call_responses (id, driver_id, cotizacion_id, response_status, ...) VALUES
(3, 100, 1, 'accepted', ...),  -- SEBASTIAN DELORIAN aceptó
(4, 101, 1, 'rejected', ...);  -- NICOLAS GARCIA rechazó

-- Vincular respuestas a conductores
UPDATE llamadas_conductores SET driver_call_response_id = 3 WHERE id = 100;
UPDATE llamadas_conductores SET driver_call_response_id = 4 WHERE id = 101;
```

---

## 🔧 Correcciones Aplicadas

### 1. Error de Columna (CRÍTICO)
**Problema**: El código inicial usaba `response` en lugar de `response_status`

**Archivos Afectados**:
- `CallStatusController::show()` - línea ~110
- `CallStatusController::selectDriver()` - línea ~155
- `test_conductores_aceptados.sh` - múltiples líneas SQL

**Solución**: Cambiar todas las referencias de `'response'` a `'response_status'`

```php
// ❌ Antes
->where('response', 'accepted')

// ✅ Después
->where('response_status', 'accepted')
```

### 2. Campo Faltante en SELECT (CRÍTICO)
**Problema**: El `select()` en CallStatusController no incluía `driver_call_response_id`, lo que impedía cargar la relación `with('driverCallResponse')`

**Solución**:
```php
// ❌ Antes
->select([
    'id',
    'nombre_conductor',
    'telefono',
    'placa',
    'tipo_vehiculo',
    'ciudad_origen',
    'ciudad_destino',
    'cotizacion_id'
])

// ✅ Después
->select([
    'id',
    'nombre_conductor',
    'telefono',
    'placa',
    'tipo_vehiculo',
    'ciudad_origen',
    'ciudad_destino',
    'cotizacion_id',
    'driver_call_response_id'  // IMPORTANTE: necesario para cargar la relación
])
```

### 3. Campo Incorrecto en DriverCallResponse
**Problema**: El servicio usaba `notas` en lugar de `notes`

**Archivo**: `app/Services/ConversationalAgentService.php`

**Solución**:
```php
// ❌ Antes
'notas' => $agentResponse['response_message'] ?? null,

// ✅ Después
'notes' => $agentResponse['response_message'] ?? null,
```

### 4. Formato de Fecha de Decisión
**Problema**: El `decision_date` no se mostraba correctamente

**Solución**: Usar `response_time` como prioridad y `created_at` como fallback:
```php
$decisionDate = null;
if ($d->driverCallResponse) {
    if ($d->driverCallResponse->response_time) {
        $decisionDate = \Carbon\Carbon::parse($d->driverCallResponse->response_time)->format('Y-m-d H:i:s');
    } elseif ($d->driverCallResponse->created_at) {
        $decisionDate = $d->driverCallResponse->created_at->format('Y-m-d H:i:s');
    }
}
```

### 5. Sistema No Creaba DriverCallResponse (CRÍTICO)
**Problema**: El método `saveDriverDecision` en `ConversationalAgentService` solo guardaba en `CallDriverDecision` (tabla legacy), no creaba registros en `driver_call_responses`

**Solución**: Actualizado `saveDriverDecision()` para:
1. Crear/actualizar registro en `DriverCallResponse`
2. Vincular `driver_call_response_id` en `llamadas_conductores`
3. Actualizar `respuesta_llamada` y `estado_llamada`

```php
// Crear o actualizar en DriverCallResponse (nuevo sistema)
$responseStatus = $agentResponse['decision'] === 'ACCEPT' ? 'accepted' : 'rejected';

$driverCallResponse = \App\Models\DriverCallResponse::updateOrCreate(
    [
        'cotizacion_id' => $cotizacionId,
        'driver_id' => $driverId,
    ],
    [
        'driver_name' => $llamadaConductor->nombre_conductor,
        'driver_phone' => $llamadaConductor->telefono,
        'vehicle_plate' => $llamadaConductor->placa,
        'vehicle_type' => $llamadaConductor->tipo_vehiculo,
        'response_status' => $responseStatus,
        'response_time' => now(),
        'elevenlabs_conversation_id' => $llamadaConductor->elevenlabs_conversation_id,
        'notes' => $agentResponse['response_message'] ?? null,
        'updated_at' => now()
    ]
);

// Vincular driver_call_response_id en llamadas_conductores
$llamadaConductor->update([
    'driver_call_response_id' => $driverCallResponse->id,
    'respuesta_llamada' => $agentResponse['intent'] === 'ACCEPT' ? 'Acepta el viaje' : 'Rechaza el viaje',
    'estado_llamada' => 'completada'
]);
```

---

## 📱 Frontend (CallPanel.jsx)

**Ubicación**: `resources/js/components/Calls/CallPanel.jsx`

**Estado Actual**: El componente React **no requiere cambios**.

- Ya consume el endpoint `/api/calls/{id}` correctamente
- Hace polling cada 5 segundos con `fetchCallStatus()`
- Renderiza automáticamente el array `data.accepted`
- Muestra botón "Elegir este conductor" para cada conductor aceptado

**Service**: `resources/js/services/callService.js`

```javascript
export const fetchCallStatus = async (cotizacionId) => {
  const response = await axios.get(`/api/calls/${cotizacionId}`);
  return response.data;
};
```

---

## ✅ Checklist de Implementación

- [x] Refactorizar `CallStatusController::show()` para usar `llamadas_conductores`
- [x] Actualizar `CallStatusController::selectDriver()` con nueva validación
- [x] Agregar rutas API en `routes/api.php`
- [x] Corregir nombre de columna `response` → `response_status`
- [x] Crear script de verificación `test_conductores_aceptados.sh`
- [x] Crear datos de prueba (cotización 1 con 3 conductores)
- [x] Probar endpoint con cURL
- [x] Verificar estructura JSON de respuesta
- [x] Limpiar caches (`route:clear`, `config:clear`)
- [x] Documentar cambios en este archivo

---

## 🎯 Resultado Final

### Comportamiento Esperado:
1. ✅ El endpoint `/api/calls/{cotizacionId}` devuelve solo conductores con `response_status = 'accepted'`
2. ✅ Se filtran automáticamente los conductores rechazados o sin respuesta
3. ✅ El porcentaje se calcula correctamente: `(aceptados / total_registrados) * 100`
4. ✅ Frontend (CallPanel) muestra solo conductores aceptados en tiempo real

### Datos de Ejemplo (Cotización 1):
- **Total conductores**: 3
- **Aceptados**: 1 (SEBASTIAN DELORIAN)
- **Rechazados**: 1 (NICOLAS GARCIA)
- **Sin respuesta**: 1 (DEIBY SALAS)
- **Porcentaje**: 33.33%

---

## 📚 Referencias

- **Flujo de llamadas**: `AGENTE_IA_FLUJO_COMPLETO.md`
- **Arquitectura del sistema**: `SYSTEM_ANALYSIS_FINAL.md`
- **Almacenamiento de audio**: `ALMACENAMIENTO_LOCAL_AUDIO.md`
- **Modelo LlamadaConductor**: `app/Models/LlamadaConductor.php`
- **Controlador API**: `app/Http/Controllers/Api/CallStatusController.php`

---

**Fecha de implementación**: 6 de diciembre, 2025  
**Estado**: ✅ COMPLETADO Y PROBADO
