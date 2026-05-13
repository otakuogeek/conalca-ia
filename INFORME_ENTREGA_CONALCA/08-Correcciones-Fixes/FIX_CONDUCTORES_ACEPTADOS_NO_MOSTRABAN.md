# Fix: Conductores Aceptados No Se Mostraban en Frontend

## 🐛 Problema Identificado

El sistema guardaba las respuestas de los conductores en `llamadas_conductores.respuesta_llamada` ("Acepta el viaje"), pero **NO creaba los registros en `driver_call_responses`** ni vinculaba el `driver_call_response_id`.

### Síntomas:
- ✅ Llamadas ElevenLabs se ejecutaban correctamente
- ✅ Campo `respuesta_llamada` se llenaba con "Acepta el viaje"
- ❌ Campo `driver_call_response_id` quedaba en NULL
- ❌ No se creaban registros en tabla `driver_call_responses`
- ❌ Endpoint `/api/calls/{cotizacionId}` devolvía array `accepted: []` vacío
- ❌ Frontend mostraba "Ninguno aún" aunque el conductor aceptó

---

## 🔍 Causa Raíz

### 1. Inconsistencia en Nombres de Campos
El método `saveDriverDecision()` en `ConversationalAgentService` esperaba:
- `$agentResponse['decision']` = 'ACCEPT' o 'REJECT'

Pero el código que lo llamaba enviaba:
- `$agentResponse['intent']` = 'ACCEPT' o 'REJECT'

**Resultado**: La condición `if (!isset($agentResponse['decision']))` siempre era `true`, por lo que nunca se ejecutaba el código de guardado.

### 2. Faltaba el Campo en SELECT
El `CallStatusController::show()` hacía `select()` sin incluir `driver_call_response_id`, por lo que la relación `with('driverCallResponse')` no podía cargarse.

---

## ✅ Solución Implementada

### 1. Normalización de Campos (ConversationalAgentService.php)

**Ubicación**: `app/Services/ConversationalAgentService.php` línea ~239

```php
// Guardar decisión del conductor si es clara
if (in_array($parsedResponse['intent'], ['ACCEPT', 'REJECT'])) {
    // Normalizar para saveDriverDecision (usa 'decision' no 'intent')
    $parsedResponse['decision'] = $parsedResponse['intent'];
    $this->saveDriverDecision($cotizacionId, $driverId, $parsedResponse);
}
```

**Cambio**: Agregamos `$parsedResponse['decision'] = $parsedResponse['intent']` para normalizar antes de llamar a `saveDriverDecision()`.

### 2. Creación de DriverCallResponse (ConversationalAgentService.php)

**Ubicación**: `app/Services/ConversationalAgentService.php` línea ~768

```php
// Obtener conductor desde llamadas_conductores
$llamadaConductor = \App\Models\LlamadaConductor::where('cotizacion_id', $cotizacionId)
    ->where('id', $driverId)
    ->first();

if ($llamadaConductor) {
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
        'respuesta_llamada' => $agentResponse['decision'] === 'ACCEPT' ? 'Acepta el viaje' : 'Rechaza el viaje',
        'estado_llamada' => 'completada'
    ]);

    Log::info("Decisión del conductor guardada en ambas tablas", [
        'cotizacion_id' => $cotizacionId,
        'driver_id' => $driverId,
        'decision' => $decision ? 'ACCEPT' : 'REJECT',
        'response_status' => $responseStatus,
        'driver_call_response_id' => $driverCallResponse->id,
        'confidence' => $agentResponse['confidence'] ?? 'N/A'
    ]);
}
```

**Cambios**:
- ✅ Crea registro en `driver_call_responses` con `updateOrCreate`
- ✅ Vincula `driver_call_response_id` en `llamadas_conductores`
- ✅ Actualiza `estado_llamada` a 'completada'
- ✅ Usa campo correcto `notes` (no `notas`)

### 3. Incluir driver_call_response_id en SELECT (CallStatusController.php)

**Ubicación**: `app/Http/Controllers/Api/CallStatusController.php` línea ~103

```php
$acceptedDrivers = \App\Models\LlamadaConductor::query()
    ->select(
        'id',
        'nombre_conductor',
        'telefono',
        'placa',
        'tipo_vehiculo',
        'ciudad_origen',
        'ciudad_destino',
        'driver_call_response_id'  // ← AGREGADO
    )
    ->where('cotizacion_id', $cotizacionId)
    ->whereHas('driverCallResponse', function ($query) {
        $query->where('response_status', 'accepted');
    })
    ->with('driverCallResponse')
    ->get();
```

**Cambio**: Agregamos `driver_call_response_id` al `select()` para que la relación pueda cargarse correctamente.

### 4. Comando de Sincronización para Datos Históricos

**Archivo**: `app/Console/Commands/SyncDriverCallResponses.php`

```bash
# Ver qué haría sin ejecutar cambios
php artisan sync:driver-responses --dry-run

# Ejecutar sincronización
php artisan sync:driver-responses

# Sincronizar solo una cotización
php artisan sync:driver-responses --cotizacion=1
```

**Función**: Crea registros en `driver_call_responses` para conductores que tienen `respuesta_llamada` pero no `driver_call_response_id`.

---

## 🧪 Pruebas de Verificación

### 1. Verificar Base de Datos

```sql
SELECT 
    lc.id,
    lc.nombre_conductor,
    lc.respuesta_llamada,
    lc.driver_call_response_id,
    dcr.response_status,
    dcr.response_time
FROM llamadas_conductores lc
LEFT JOIN driver_call_responses dcr ON lc.driver_call_response_id = dcr.id
WHERE lc.cotizacion_id = 1;
```

**Resultado Esperado**:
```
+-----+--------------------+-------------------+-------------------------+-----------------+---------------------+
| id  | nombre_conductor   | respuesta_llamada | driver_call_response_id | response_status | response_time       |
+-----+--------------------+-------------------+-------------------------+-----------------+---------------------+
| 115 | SEBASTIAN DELORIAN | Acepta el viaje   |                       7 | accepted        | 2025-12-06 15:57:28 |
| 116 | NICOLAS GARCIA     | NULL              |                    NULL | NULL            | NULL                |
| 117 | DEIBY SALAS        | NULL              |                    NULL | NULL            | NULL                |
+-----+--------------------+-------------------+-------------------------+-----------------+---------------------+
```

### 2. Probar Endpoint API

```bash
curl -X GET "http://localhost:8000/api/calls/1" -H "Accept: application/json"
```

**Respuesta Esperada**:
```json
{
  "prompt": "Este flete consiste en transportar 3725 desde cartagena hasta funza con peso 18400 kg.",
  "vehicle_type": "CONTENEDOR 20\"",
  "ciudad_origen": "cartagena",
  "ciudad_destino": "funza",
  "total_to_call": 3,
  "accepted": [
    {
      "id": 115,
      "name": "SEBASTIAN DELORIAN",
      "phone": "3105672307",
      "placa": "ABC123",
      "tipo_vehiculo": "TRACTOMULA3",
      "ciudad_origen": "cartagena",
      "ciudad_destino": "funza",
      "decision_date": "2025-12-06 15:57:28"
    }
  ],
  "percentage": 33.33,
  "selected_driver_id": null
}
```

### 3. Script de Verificación

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
Total: 3 conductores

2. CONDUCTORES QUE ACEPTARON (con driver_call_responses):
1 conductor: SEBASTIAN DELORIAN

3. ESTADÍSTICAS:
Total conductores registrados: 3
Conductores que ACEPTARON: 1
Conductores que RECHAZARON: 0
Conductores SIN RESPUESTA: 2
```

---

## 📊 Flujo Completo Correcto

### Cuando un conductor acepta (ElevenLabs Webhook):

```
1. ElevenLabs detecta respuesta del conductor
   ↓
2. ConversationalAgentController::handleDriverResponse()
   ↓
3. ConversationalAgentService::processDriverResponse()
   - OpenAI analiza respuesta
   - Devuelve: { "intent": "ACCEPT", ... }
   ↓
4. Normalización: parsedResponse['decision'] = parsedResponse['intent']
   ↓
5. saveDriverDecision($cotizacionId, $driverId, $parsedResponse)
   ↓
6. Crear DriverCallResponse:
   - cotizacion_id, driver_id
   - response_status = 'accepted'
   - response_time = now()
   - notes = "Acepta el viaje"
   ↓
7. Actualizar LlamadaConductor:
   - driver_call_response_id = [ID creado]
   - respuesta_llamada = "Acepta el viaje"
   - estado_llamada = 'completada'
   ↓
8. Frontend consulta /api/calls/{id}
   ↓
9. CallStatusController::show()
   - whereHas('driverCallResponse', 'response_status = accepted')
   ↓
10. Devuelve array 'accepted' con conductores
    ↓
11. Frontend muestra en sección "Aceptaron"
```

---

## 🎯 Archivos Modificados

1. **app/Services/ConversationalAgentService.php**
   - Línea ~239: Normalización `decision = intent`
   - Línea ~768-810: Creación de DriverCallResponse
   - Línea ~786: Cambio `notes` (no `notas`)

2. **app/Http/Controllers/Api/CallStatusController.php**
   - Línea ~103: Agregado `driver_call_response_id` a select()

3. **app/Console/Commands/SyncDriverCallResponses.php** (NUEVO)
   - Comando para sincronizar registros históricos

---

## 📝 Logs de Debug

Para verificar que el sistema funciona correctamente, revisar en `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep -E "(Decisión del conductor guardada|DriverCallResponse|saveDriverDecision)"
```

**Log Esperado**:
```
[2025-12-06 XX:XX:XX] local.INFO: Decisión del conductor guardada en ambas tablas
{
  "cotizacion_id": 1,
  "driver_id": 115,
  "decision": "ACCEPT",
  "response_status": "accepted",
  "driver_call_response_id": 7,
  "confidence": 0.9
}
```

---

## ✅ Checklist de Validación

- [x] `driver_call_response_id` se llena cuando conductor acepta/rechaza
- [x] Registro se crea en tabla `driver_call_responses`
- [x] Campo `response_status` tiene valores: 'accepted', 'rejected', 'pending'
- [x] Endpoint `/api/calls/{id}` devuelve conductores aceptados
- [x] Frontend muestra conductores en sección "Aceptaron"
- [x] Campo `decision_date` muestra fecha correcta
- [x] Logs registran creación de DriverCallResponse
- [x] Comando `sync:driver-responses` disponible para datos históricos
- [x] Documentación actualizada

---

**Fecha**: 6 de diciembre, 2025  
**Estado**: ✅ RESUELTO Y PROBADO  
**Próximos pasos**: Monitorear logs en producción para confirmar funcionamiento en llamadas reales
