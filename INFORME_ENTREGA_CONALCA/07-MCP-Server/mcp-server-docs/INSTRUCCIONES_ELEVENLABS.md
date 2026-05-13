# 📋 INSTRUCCIONES RÁPIDAS - ACTUALIZAR PROMPT EN ELEVENLABS

## ⚡ PASO A PASO (5 minutos)

### 1. Copiar el nuevo prompt
```bash
# El prompt completo está en:
/home/ubuntu/conalca/conalca-mcp/mcp-server/PROMPT_NATALIA_ELEVENLABS_V2.md

# O usar este comando para copiarlo al portapapeles (si estás en SSH):
cat /home/ubuntu/conalca/conalca-mcp/mcp-server/PROMPT_NATALIA_ELEVENLABS_V2.md
```

### 2. Ir a ElevenLabs Dashboard
1. Abrir: https://elevenlabs.io/app/conversational-ai
2. Login con tu cuenta
3. Buscar el agente: **"Natalia Álvarez - CONALCA"** o el nombre que tenga
4. Click en "Edit Agent"

### 3. Actualizar el Prompt
1. Scroll hasta la sección **"Prompt"** o **"System Prompt"**
2. **BORRAR** todo el contenido anterior
3. **PEGAR** el nuevo contenido de `PROMPT_NATALIA_ELEVENLABS_V2.md`
4. Click en **"Save"** o **"Update"**

### 4. Verificar Herramientas (Tools)
Asegúrate de que estas 4 herramientas estén configuradas:

#### ✅ generate_transport_offer
```json
{
  "name": "generate_transport_offer",
  "description": "Obtiene información completa del conductor y del viaje usando el conversation_id",
  "parameters": {
    "type": "object",
    "properties": {
      "conversation_id": {
        "type": "string",
        "description": "ID de la conversación de ElevenLabs"
      }
    },
    "required": ["conversation_id"]
  }
}
```
**URL:** `https://conalcaia.conalca.com.co/mcp/tools/generate_transport_offer`

---

#### ✅ precioviaje
```json
{
  "name": "precioviaje",
  "description": "Obtiene el precio del viaje basado en el ID de cotización",
  "parameters": {
    "type": "object",
    "properties": {
      "cotizacion_id": {
        "type": "integer",
        "description": "ID de la cotización"
      }
    },
    "required": ["cotizacion_id"]
  }
}
```
**URL:** `https://conalcaia.conalca.com.co/mcp/tools/precioviaje`

---

#### ✅ save_driver_decision
```json
{
  "name": "save_driver_decision",
  "description": "Guarda la decisión del conductor (acepta o rechaza el viaje)",
  "parameters": {
    "type": "object",
    "properties": {
      "decision": {
        "type": "integer",
        "description": "1 para acepta, 0 para rechaza"
      },
      "conversation_id": {
        "type": "string",
        "description": "ID de la conversación de ElevenLabs"
      },
      "cotizacion_model_id": {
        "type": "integer",
        "description": "ID de la cotización"
      },
      "driver_id": {
        "type": "string",
        "description": "Identificador único del conductor (identificador_unico)"
      },
      "notas": {
        "type": "string",
        "description": "Notas adicionales (opcional)"
      }
    },
    "required": ["decision", "driver_id"]
  }
}
```
**URL:** `https://conalcaia.conalca.com.co/mcp/tools/save_driver_decision`

---

#### ✅ zinformacion
```json
{
  "name": "zinformacion",
  "description": "Obtiene información detallada de una cotización/orden",
  "parameters": {
    "type": "object",
    "properties": {
      "orden_id": {
        "type": "integer",
        "description": "ID de la cotización/orden"
      }
    },
    "required": ["orden_id"]
  }
}
```
**URL:** `https://conalcaia.conalca.com.co/mcp/tools/zinformacion`

---

### 5. Probar el Agente
1. Click en **"Test"** o **"Try it"** en ElevenLabs
2. Iniciar una llamada de prueba
3. Verificar que el agente:
   - ✅ Saluda correctamente
   - ✅ Menciona el nombre del conductor
   - ✅ Dice el origen y destino
   - ✅ Dice el precio del viaje
   - ✅ Responde a preguntas sobre el viaje

---

## 🔍 VERIFICACIÓN RÁPIDA

### Probar que las herramientas funcionan:

```bash
# 1. Test generate_transport_offer (necesitas un conversation_id real)
curl -X POST https://conalcaia.conalca.com.co/mcp/tools/generate_transport_offer \
  -H "Content-Type: application/json" \
  -d '{"conversation_id": "CONVERSATION_ID_AQUI"}'

# 2. Test precioviaje (necesitas un cotizacion_id real)
curl -X POST https://conalcaia.conalca.com.co/mcp/tools/precioviaje \
  -H "Content-Type: application/json" \
  -d '{"cotizacion_id": 32}'

# 3. Test save_driver_decision (usar identificador_unico real)
curl -X POST https://conalcaia.conalca.com.co/mcp/tools/save_driver_decision \
  -H "Content-Type: application/json" \
  -d '{
    "decision": 0,
    "conversation_id": "test_123",
    "cotizacion_model_id": 32,
    "driver_id": "LC-32-abc123-1733456789",
    "notas": "Prueba manual"
  }'

# 4. Test zinformacion
curl -X POST https://conalcaia.conalca.com.co/mcp/tools/zinformacion \
  -H "Content-Type: application/json" \
  -d '{"orden_id": 32}'
```

---

## 📝 PROMPT RESUMIDO (Si no quieres copiar todo el archivo)

Aquí está el prompt **MÍNIMO** que debe tener ElevenLabs:

```
IDENTIDAD
Nombre: Natalia Álvarez
Cargo: Coordinadora de Logística – CONALCA
Tono: Natural, carismático, cercano. Usa "Don [Nombre]", "mi estimado", "usted que es cumplido".

PROTOCOLO DE CONVERSACIÓN

1. ACCIÓN INICIAL OBLIGATORIA (Primer turno)
   Paso 0: Capturar conversation_id = {{system__conversation_id}}
   Paso 1: Llamar generate_transport_offer(conversation_id)
           Almacenar: identificador_unico, id_cotizacion, nombre, origen, destino, mercancia
   Paso 2: Llamar precioviaje(cotizacion_id)
           Almacenar: precio_viaje
   Paso 3: Saludar:
           "Aló, ¿con Don [Nombre]? ¡Don [Nombre], qué gusto! Habla Natalia de CONALCA. 
           Le tengo un viajecito bueno: es para llevar [Mercancía] de [Origen] a [Destino] 
           y están pagando [Precio]. ¿Le suena?"

2. RESPUESTAS
   A) ACEPTA → save_driver_decision(decision=1, conversation_id, cotizacion_model_id, driver_id)
      "¡Eso, Don [Nombre]! En un momentico mi supervisor lo estará llamando."
   
   B) RECHAZA → save_driver_decision(decision=0, conversation_id, cotizacion_model_id, driver_id)
      "Ah bueno, Don [Nombre], tranquilo. Igual le agradezco su tiempo."
   
   C) DUDA → Usar zinformacion(orden_id) para responder preguntas
      "¡Claro que sí, Don [Nombre]! Le cuento rapidito: ..."

REGLAS
- NUNCA menciones nombres de herramientas
- SIEMPRE ejecuta save_driver_decision al final
- NO puedes negociar precios
- Los IDs se capturan al inicio y se reutilizan
```

---

## ⚠️ IMPORTANTE

### Variables del Sistema:
- `{{system__conversation_id}}` → ID único de la conversación (ElevenLabs lo proporciona automáticamente)

### Variables a Almacenar (Paso 1):
```javascript
response = generate_transport_offer(conversation_id)

identificador_unico = response.llamada_info.driver_id
cotizacion_id = response.llamada_info.id_cotizacion
nombre = response.chofer.nombre
telefono = response.chofer.telefono
origen = response.viaje.origen
destino = response.viaje.destino
mercancia = response.viaje.mercancia
```

### Variables a Almacenar (Paso 2):
```javascript
response = precioviaje(cotizacion_id)

precio = response.precio_viaje
```

### Llamada Final (Paso 4):
```javascript
save_driver_decision(
  decision = 1 o 0,
  conversation_id = conversation_id,
  cotizacion_model_id = cotizacion_id,
  driver_id = identificador_unico
)
```

---

## ✅ CHECKLIST FINAL

Antes de activar en producción:

- [ ] Prompt actualizado en ElevenLabs
- [ ] Las 4 herramientas configuradas correctamente
- [ ] URLs de herramientas apuntan a: `https://conalcaia.conalca.com.co/mcp/tools/{nombre}`
- [ ] Prueba con llamada de test exitosa
- [ ] Agente saluda correctamente
- [ ] Agente menciona precio del viaje
- [ ] Agente guarda la decisión correctamente
- [ ] Verificado en base de datos que `estado_llamada` se actualiza

---

## 🆘 TROUBLESHOOTING

### Problema: "No se encontró conductor disponible"
**Solución:** 
1. Verificar que existan conductores en `llamadas_conductores` con `estado_llamada='pendiente'`
2. Ejecutar búsqueda de conductores desde la aplicación web primero

### Problema: "Error al guardar decisión"
**Solución:**
1. Verificar que el `driver_id` sea el `identificador_unico` correcto (formato: LC-32-abc123-1733456789)
2. Ver logs del MCP: `tail -f /home/ubuntu/conalca/conalca-mcp/mcp-server/mcp_server.log`

### Problema: Agente no llama a las herramientas
**Solución:**
1. Verificar que las URLs de las herramientas sean correctas
2. Verificar que el método HTTP sea POST
3. Verificar que el prompt tenga las instrucciones para usar las herramientas

---

## 📞 CONTACTO

Si tienes problemas:
1. Revisar logs: `/home/ubuntu/conalca/conalca-mcp/mcp-server/mcp_server.log`
2. Ejecutar script de pruebas: `venv/bin/python test_nuevo_flujo.py`
3. Verificar health check: `curl https://conalcaia.conalca.com.co/mcp/health`

---

**¡Listo!** Con estos pasos el agente de ElevenLabs estará configurado con el nuevo flujo de base de datos.
