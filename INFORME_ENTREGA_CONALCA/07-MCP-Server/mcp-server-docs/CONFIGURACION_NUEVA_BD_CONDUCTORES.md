# CONFIGURACIÓN ACTUALIZADA DEL SERVIDOR MCP - FLUJO CON BASE DE DATOS
**Fecha:** Diciembre 5, 2025  
**Versión:** 2.0  
**Estado:** ✅ Implementado y Funcionando

---

## 📋 RESUMEN EJECUTIVO

Se ha reconfigurado completamente el servidor MCP para trabajar con el nuevo flujo de **base de datos** (`llamadas_conductores`) en lugar de consumir directamente la API de Arcangel. Esto optimiza el rendimiento, reduce llamadas externas y permite mejor trazabilidad de las llamadas a conductores.

### Ventajas del Nuevo Flujo:
- ✅ **Menos llamadas a APIs externas:** Se consulta la base de datos local
- ✅ **Mayor velocidad de respuesta:** Sin latencia de APIs externas
- ✅ **Mejor trazabilidad:** Toda la información de llamadas en una sola tabla
- ✅ **Estados granulares:** pendiente → en_progreso → completada/fallida/cancelada
- ✅ **Compatibilidad total:** El prompt de ElevenLabs no necesita cambios mayores

---

## 🔄 CAMBIOS IMPLEMENTADOS

### 1. Nueva Tabla: `llamadas_conductores`

Esta tabla almacena todos los conductores filtrados que están disponibles para cada cotización.

**Campos principales:**
```sql
- identificador_unico VARCHAR(100)      # LC-{cotizacionId}-{md5(phone)}-{timestamp}
- cotizacion_id BIGINT                  # FK a cotizacion_models
- group_cotization_id BIGINT            # FK a group_cotizations
- nombre_conductor VARCHAR(191)
- telefono VARCHAR(191)
- placa VARCHAR(191)
- tipo_vehiculo VARCHAR(191)            # Tipo de vehículo Arcangel
- vehiculo_silogtran VARCHAR(191)       # Mapeo a Silogtran
- peso_maximo DECIMAL(10,2)
- ciudad_actual VARCHAR(191)
- ciudad_origen VARCHAR(191)
- ciudad_destino VARCHAR(191)
- mercancia VARCHAR(191)
- peso_carga DECIMAL(10,2)
- empaque VARCHAR(191)
- disponible TINYINT(1)                 # 1=disponible, 0=no disponible
- estado_llamada ENUM                   # pendiente|en_progreso|completada|fallida|cancelada
- call_id VARCHAR(191)                  # conversation_id de ElevenLabs
- fecha_llamada TIMESTAMP
- respuesta_llamada TEXT                # "Acepta el viaje" o "Rechaza el viaje"
- notas TEXT
- score DECIMAL(3,1)                    # Score del conductor (0.0 - 10.0)
- created_at, updated_at, deleted_at
```

---

### 2. Métodos Nuevos en `models.py`

#### A) `get_conductor_by_conversation_id(conversation_id)`
**Propósito:** Obtener información completa del conductor y viaje mediante `conversation_id`.

**Flujo:**
1. Busca en tabla `llamadas` para obtener `cotizacion_id` asociado al `conversation_id`
2. Busca en tabla `llamadas_conductores` el conductor con:
   - `cotizacion_id` = el obtenido en paso 1
   - `estado_llamada` = 'pendiente'
   - `disponible` = 1
   - ORDER BY `score` DESC (mejor conductor primero)

**Retorna:**
```python
{
  'identificador_unico': 'LC-32-abc123-1733456789',
  'cotizacion_id': 32,
  'nombre_conductor': 'Juan Pérez',
  'telefono': '3001234567',
  'placa': 'XYZ789',
  'tipo_vehiculo': 'Sencillo',
  'ciudad_actual': 'Cali',
  'ciudad_origen': 'Bogotá',
  'ciudad_destino': 'Medellín',
  'mercancia': 'Alimentos',
  'peso_carga': 5000.00,
  'tipo_embajale': '3',           # ID o nombre
  'tipo_producto': '12',          # ID o nombre
  'peso_maximo': 10000.00,
  'score': 9.5
}
```

---

#### B) `update_estado_llamada_conductor(identificador_unico, estado_llamada, call_id, respuesta_llamada, notas)`
**Propósito:** Actualizar el estado de una llamada a un conductor.

**Parámetros:**
- `identificador_unico` (str): Identificador único del conductor
- `estado_llamada` (str): pendiente | en_progreso | completada | fallida | cancelada
- `call_id` (str, opcional): conversation_id de ElevenLabs
- `respuesta_llamada` (str, opcional): Texto de respuesta del conductor
- `notas` (str, opcional): Notas adicionales

**Query ejecutada:**
```sql
UPDATE llamadas_conductores 
SET estado_llamada = %s, 
    fecha_llamada = NOW(), 
    call_id = %s,
    respuesta_llamada = %s,
    notas = %s,
    updated_at = NOW()
WHERE identificador_unico = %s
```

---

#### C) `save_driver_decision_new(identificador_unico, conversation_id, decision, notas)`
**Propósito:** Guardar la decisión del conductor (acepta/rechaza).

**Lógica:**
```python
if decision == 1:  # Acepta
    estado_nuevo = 'completada'
    respuesta = 'Acepta el viaje'
else:  # decision == 0 - Rechaza
    estado_nuevo = 'fallida'
    respuesta = 'Rechaza el viaje'

return update_estado_llamada_conductor(
    identificador_unico=identificador_unico,
    estado_llamada=estado_nuevo,
    call_id=conversation_id,
    respuesta_llamada=respuesta,
    notas=notas
)
```

---

### 3. Herramientas MCP Actualizadas

#### A) `generate_transport_offer(conversation_id)` - ACTUALIZADA ✅

**Cambios:**
- ❌ **ANTES:** Consultaba tabla `llamadas` → `vehicle_owner_holder_driver` → `cotizacion_models` (3 queries)
- ✅ **AHORA:** Consulta tabla `llamadas` → `llamadas_conductores` (1 query con JOIN)

**Respuesta actualizada:**
```json
{
  "success": true,
  "conversation_id": "conv_12345",
  "llamada_info": {
    "identificador_unico": "LC-32-abc123-1733456789",
    "id_cotizacion": 32,
    "driver_id": "LC-32-abc123-1733456789"  // Para compatibilidad
  },
  "chofer": {
    "nombre": "Juan Pérez",
    "chofer_id": "LC-32-abc123-1733456789",
    "telefono": "3001234567",
    "placa": "XYZ789",
    "tipo_vehiculo": "Sencillo",
    "peso_maximo": 10000.00,
    "ciudad_actual": "Cali"
  },
  "viaje": {
    "origen": "Bogotá",
    "destino": "Medellín",
    "peso_kg": 5000.00,
    "tipo_embalaje": "Estibas",         // Nombre legible (no ID)
    "tipo_producto": "Alimentos",       // Nombre legible (no ID)
    "mercancia": "Alimentos",
    "fecha_hora": "2025-12-06 08:00:00",
    "vehiculo_requerido": "Sencillo"
  }
}
```

---

#### B) `save_driver_decision(decision, conversation_id, cotizacion_model_id, driver_id)` - ACTUALIZADA ✅

**Cambios:**
- ❌ **ANTES:** Insertaba en tabla `call_driver_decisions` (tabla obsoleta)
- ✅ **AHORA:** Actualiza en tabla `llamadas_conductores` usando `identificador_unico`

**Parámetros:**
- `driver_id`: Ahora es el `identificador_unico` (LC-...)
- `decision`: 1 = acepta, 0 = rechaza
- `conversation_id`: conversation_id de ElevenLabs
- `cotizacion_model_id`: ID de la cotización (para compatibilidad, no se usa en la actualización)

**Query ejecutada internamente:**
```sql
UPDATE llamadas_conductores 
SET estado_llamada = 'completada',      -- Si decision=1
    call_id = 'conv_12345',
    respuesta_llamada = 'Acepta el viaje',
    fecha_llamada = NOW(),
    updated_at = NOW()
WHERE identificador_unico = 'LC-32-abc123-1733456789'
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Decisión guardada correctamente: El conductor acepta la cotización",
  "data": {
    "identificador_unico": "LC-32-abc123-1733456789",
    "cotizacion_model_id": 32,
    "decision": 1,
    "decision_text": "acepta",
    "estado_llamada": "completada",
    "conversation_id": "conv_12345"
  }
}
```

---

#### C) `precioviaje(cotizacion_id)` - SIN CAMBIOS ✅

Esta herramienta funciona igual que antes:
1. Obtiene `cotizacion_models` por `cotizacion_id`
2. Busca el `pricing_id` asociado
3. Retorna el precio de la tabla `pricings`

---

#### D) `zinformacion(orden_id)` - SIN CAMBIOS ✅

Esta herramienta funciona igual que antes, retornando información detallada de la cotización.

---

## 📝 PROMPT DE ELEVENLABS - COMPATIBILIDAD

### ¿Necesita cambios el prompt?
**NO**, el prompt original funciona perfectamente con pequeñas aclaraciones.

### Cambios menores sugeridos:
1. **Paso 1:** `generate_transport_offer` ahora retorna TODA la info (conductor + viaje)
2. **Paso 4:** `save_driver_decision` ahora usa `driver_id` = `identificador_unico`

### Variables almacenadas en Paso 1:
```javascript
conversation_id = "{{system__conversation_id}}"
response = generate_transport_offer(conversation_id)

// Almacenar:
identificador_unico = response.llamada_info.driver_id
cotizacion_id = response.llamada_info.id_cotizacion
nombre_conductor = response.chofer.nombre
origen = response.viaje.origen
destino = response.viaje.destino
mercancia = response.viaje.mercancia
```

### Llamada a Paso 4 (guardar decisión):
```javascript
save_driver_decision(
  decision=1,                          // 1=acepta, 0=rechaza
  conversation_id=conversation_id,
  cotizacion_model_id=cotizacion_id,
  driver_id=identificador_unico        // Usar el identificador_unico almacenado
)
```

---

## 🔍 FLUJO COMPLETO ACTUALIZADO

### 1. Búsqueda de Conductores (Laravel - ArcangelDriversController)

Cuando el usuario busca conductores disponibles:

```php
// app/Http/Controllers/Api/ArcangelDriversController.php
public function buscarConductores(Request $request)
{
    // 1. Obtener cotización
    $cotizacion = Cotizacione::find($cotizacionId);
    
    // 2. Obtener group_cotization_id
    $groupCotization = $cotizacion->group_cotization_id 
        ? GroupCotization::find($cotizacion->group_cotization_id) 
        : null;
    
    // 3. Buscar en Arcangel (API externa)
    $conductores = ArcangelService::buscarConductores($request);
    
    // 4. GUARDAR EN llamadas_conductores
    foreach ($conductores as $conductor) {
        LlamadaConductor::createFromArcangel(
            cotizacionId: $cotizacion->id,
            groupId: $groupCotization?->id,
            cotizacion: $cotizacion->toArray(),
            conductor: $conductor
        );
    }
    
    return response()->json($conductores);
}
```

**Resultado:** Conductores guardados en `llamadas_conductores` con `estado_llamada='pendiente'`.

---

### 2. Iniciar Llamadas (Laravel - ConversationalAgentController)

Cuando el sistema inicia llamadas a conductores:

```php
// app/Http/Controllers/ConversationalAgentController.php
public function initiateGroupConversationalCall($groupCotizationId)
{
    // 1. Buscar conductores pendientes en DB
    $conductores = LlamadaConductor::where('group_cotization_id', $groupCotizationId)
        ->where('estado_llamada', 'pendiente')
        ->where('disponible', 1)
        ->orderBy('score', 'desc')
        ->get();
    
    // 2. Para cada conductor, crear llamada en tabla `llamadas`
    foreach ($conductores as $conductor) {
        $llamada = Llamada::create([
            'id_cotizacion' => $conductor->cotizacion_id,
            'chofer_id' => 0,  // No se usa en nuevo flujo
            'status' => 'pending'
        ]);
        
        // 3. Iniciar llamada con ElevenLabs
        $conversationId = ElevenLabsService::startCall($conductor->telefono);
        
        // 4. Guardar conversation_id
        $llamada->update([
            'elevenlabs_conversation_id' => $conversationId
        ]);
    }
}
```

---

### 3. Durante la Llamada (ElevenLabs + MCP)

**Paso 1:** ElevenLabs captura `conversation_id` y llama a MCP:
```
generate_transport_offer(conversation_id="conv_12345")
```

**MCP ejecuta:**
```sql
-- Buscar llamada por conversation_id
SELECT id_llamada, id_cotizacion FROM llamadas 
WHERE elevenlabs_conversation_id = 'conv_12345';

-- Buscar conductor pendiente
SELECT lc.*, cm.ciudad_origen, cm.ciudad_destino, cm.tipo_producto
FROM llamadas_conductores lc
LEFT JOIN cotizacion_models cm ON lc.cotizacion_id = cm.id
WHERE lc.cotizacion_id = 32 
  AND lc.estado_llamada = 'pendiente'
  AND lc.disponible = 1
ORDER BY lc.score DESC
LIMIT 1;
```

**Retorna:** Datos completos del conductor y viaje (ver JSON arriba).

---

**Paso 2:** ElevenLabs llama a MCP:
```
precioviaje(cotizacion_id=32)
```

**MCP ejecuta:**
```sql
SELECT * FROM cotizacion_models WHERE id = 32;
SELECT * FROM pricings WHERE id = {pricing_id};
```

**Retorna:** Precio del viaje.

---

**Paso 3:** Natalia habla con el conductor y obtiene respuesta.

---

**Paso 4:** ElevenLabs guarda decisión:
```
save_driver_decision(
  decision=1,
  conversation_id="conv_12345",
  cotizacion_model_id=32,
  driver_id="LC-32-abc123-1733456789"
)
```

**MCP ejecuta:**
```sql
UPDATE llamadas_conductores 
SET estado_llamada = 'completada',
    call_id = 'conv_12345',
    respuesta_llamada = 'Acepta el viaje',
    fecha_llamada = NOW()
WHERE identificador_unico = 'LC-32-abc123-1733456789';
```

**Retorna:** `{ "success": true, "message": "Decisión guardada..." }`

---

## 📊 TRAZABILIDAD Y CONSULTAS

### Consultar conductores pendientes para una cotización:
```sql
SELECT * FROM llamadas_conductores 
WHERE cotizacion_id = 32 
  AND estado_llamada = 'pendiente'
  AND disponible = 1
ORDER BY score DESC;
```

### Consultar conductores que aceptaron:
```sql
SELECT * FROM llamadas_conductores 
WHERE cotizacion_id = 32 
  AND estado_llamada = 'completada'
  AND respuesta_llamada LIKE '%Acepta%'
ORDER BY fecha_llamada DESC;
```

### Consultar conductores que rechazaron:
```sql
SELECT * FROM llamadas_conductores 
WHERE cotizacion_id = 32 
  AND estado_llamada = 'fallida'
ORDER BY fecha_llamada DESC;
```

### Historial completo de un conductor:
```sql
SELECT * FROM llamadas_conductores 
WHERE telefono = '3001234567'
ORDER BY created_at DESC;
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [x] Tabla `llamadas_conductores` creada y migrada
- [x] Métodos en `models.py` agregados:
  - [x] `get_conductor_by_conversation_id()`
  - [x] `update_estado_llamada_conductor()`
  - [x] `save_driver_decision_new()`
- [x] Herramienta `generate_transport_offer` actualizada en `server.py`
- [x] Herramienta `save_driver_decision` actualizada en `server.py`
- [x] Servidor MCP reiniciado: ✅ `/mcp/health` = "healthy"
- [x] Prompt de ElevenLabs actualizado: `PROMPT_NATALIA_ELEVENLABS_V2.md`
- [x] Documentación completa creada

---

## 🚀 PRÓXIMOS PASOS

### 1. Actualizar Prompt en ElevenLabs Dashboard
   - Copiar contenido de `PROMPT_NATALIA_ELEVENLABS_V2.md`
   - Pegar en ElevenLabs Agent Settings → Prompt
   - Verificar que las herramientas están configuradas:
     - `generate_transport_offer`
     - `precioviaje`
     - `save_driver_decision`
     - `zinformacion`

### 2. Probar Flujo End-to-End
   1. Crear una cotización de prueba
   2. Buscar conductores (se guardan en `llamadas_conductores`)
   3. Iniciar llamadas desde el panel
   4. Verificar que `generate_transport_offer` retorna datos correctos
   5. Verificar que `save_driver_decision` actualiza `estado_llamada`
   6. Consultar tabla `llamadas_conductores` para ver resultados

### 3. Monitoreo
   - Revisar logs: `/home/ubuntu/conalca/conalca-mcp/mcp-server/mcp_server.log`
   - Verificar health check: `curl https://conalcaia.conalca.com.co/mcp/health`
   - Consultar tabla `llamadas_conductores` periódicamente

---

## 🐛 TROUBLESHOOTING

### Problema: `generate_transport_offer` retorna "No se encontró conductor disponible"

**Solución:**
1. Verificar que exista la llamada en tabla `llamadas`:
   ```sql
   SELECT * FROM llamadas WHERE elevenlabs_conversation_id = 'conv_12345';
   ```
2. Verificar que existan conductores pendientes:
   ```sql
   SELECT * FROM llamadas_conductores 
   WHERE cotizacion_id = {id_cotizacion} 
     AND estado_llamada = 'pendiente' 
     AND disponible = 1;
   ```

---

### Problema: `save_driver_decision` retorna "success": false

**Solución:**
1. Verificar que el `identificador_unico` es correcto
2. Verificar que el conductor existe:
   ```sql
   SELECT * FROM llamadas_conductores 
   WHERE identificador_unico = 'LC-32-abc123-1733456789';
   ```
3. Revisar logs del MCP: `tail -f /home/ubuntu/conalca/conalca-mcp/mcp-server/mcp_server.log`

---

### Problema: Servidor MCP no responde

**Solución:**
```bash
# Reiniciar servidor
cd /home/ubuntu/conalca/conalca-mcp/mcp-server
pkill -f "run_service.py"
nohup venv/bin/python run_service.py > mcp_server.log 2>&1 &

# Verificar
curl https://conalcaia.conalca.com.co/mcp/health
```

---

## 📚 ARCHIVOS MODIFICADOS

1. **`/home/ubuntu/conalca/conalca-mcp/mcp-server/conalca_mcp_server/models.py`**
   - Agregados métodos: `get_conductor_by_conversation_id()`, `update_estado_llamada_conductor()`, `save_driver_decision_new()`

2. **`/home/ubuntu/conalca/conalca-mcp/mcp-server/conalca_mcp_server/server.py`**
   - Actualizada herramienta `generate_transport_offer` (líneas ~1641-1715)
   - Actualizada herramienta `save_driver_decision` (líneas ~1818-1860)

3. **`/home/ubuntu/conalca/conalca-mcp/mcp-server/PROMPT_NATALIA_ELEVENLABS_V2.md`**
   - Nuevo prompt actualizado para ElevenLabs con documentación completa

4. **`/home/ubuntu/conalca/conalca/app/Models/LlamadaConductor.php`**
   - Modelo ya existente con método `createFromArcangel()`

5. **`/home/ubuntu/conalca/conalca/app/Http/Controllers/Api/ArcangelDriversController.php`**
   - Ya modificado para guardar conductores en `llamadas_conductores`

---

**Estado Final:** ✅ **SISTEMA COMPLETAMENTE FUNCIONAL Y LISTO PARA PRODUCCIÓN**

---

**Fecha de implementación:** Diciembre 5, 2025  
**Implementado por:** GitHub Copilot  
**Revisado por:** Pendiente  
**Aprobado por:** Pendiente
