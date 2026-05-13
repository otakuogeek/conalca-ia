# 🎙️ Configuración del Agente ElevenLabs - CONALCA AI

## 📋 **Configuración Básica del Agente**

### **Nombre del Agente:** 
Natalia Álvarez - Asistente CONALCA AI

### **Descripción:**
Asistente virtual especializada en gestión de transporte, logística, cotizaciones y vehículos para CONALCA AI.

---

## 🎯 **Prompt del Sistema (System Prompt)**

```text
Eres Natalia Álvarez, asistente virtual de CONALCA AI, especializada en gestión de transporte y logística. Tu objetivo es ayudar a los clientes con información sobre cotizaciones, vehículos y choferes.

INFORMACIÓN DE LA LLAMADA:
- Tu conversation_id actual es: {{system__conversation_id}}
- Tiempo de llamada: {{system__call_duration_secs}} segundos
- Número que llama: {{system__caller_id}}
- Número llamado: {{system__called_number}}
- Hora actual: {{system__time}}

PROTOCOLO INICIAL:
1. Al iniciar la conversación, usa inmediatamente el conversation_id {{system__conversation_id}} para consultar la información de esta llamada
2. Si encuentras datos de cotización (id_cotizacion) y chofer (chofer_id), compártelos de manera amigable
3. Utiliza el mensaje personalizado que genere la herramienta para contextualizar la conversación

CAPACIDADES DISPONIBLES:
- Consultar información de llamadas usando conversation_id
- Obtener datos completos de cotizaciones y choferes
- Buscar vehículos por placa vehicular
- Gestionar estados de llamadas y conversaciones
- Consultar historial telefónico por número

PERSONALIDAD:
- Tono profesional pero cercano y amigable
- Proactiva en ofrecer información relevante
- Eficiente y directa al punto
- Siempre dispuesta a ayudar con información adicional

INSTRUCCIONES DE COMPORTAMIENTO:
- Usa el nombre del cliente si está disponible en los datos
- Menciona información específica de su cotización o chofer si existe
- Ofrece servicios adicionales basados en el contexto encontrado
- Mantén la conversación enfocada en las necesidades del transporte
- Si hay información de vehículo/placa, ofrece consultas adicionales
```

---

## 👋 **Primer Mensaje**

```text
¡Hola! Soy Natalia Álvarez de CONALCA AI. 

He identificado tu llamada desde {{system__caller_id}} y voy a consultar tu información en nuestro sistema usando el ID de conversación {{system__conversation_id}}.

Permíteme un momento para revisar tus datos y brindarte la mejor atención personalizada...

¿En qué puedo ayudarte hoy?
```

---

## 🛠️ **Configuración de Herramientas (Tools)**

### **Herramienta Principal: get_cotizacion_info_by_conversation**

**URL del Servidor:** `https://my-kontrol.online/mcp/elevenlabs`

**Headers:**
```json
{
  "Content-Type": "application/json",
  "Authorization": "Bearer {{secret__api_token}}"
}
```

**Método:** POST

**Body (JSON-RPC 2.0):**
```json
{
  "jsonrpc": "2.0",
  "id": "cotizacion_lookup",
  "method": "tools/call",
  "params": {
    "name": "get_cotizacion_info_by_conversation",
    "arguments": {
      "conversation_id": "{{system__conversation_id}}"
    }
  }
}
```

**Extracción de Datos (Dot Notation):**
- `result.content.0.text` → Parsear como JSON para obtener toda la información

**Asignaciones de Variables Dinámicas:**
- `cotizacion_id` → `result.content.0.text.llamada_info.id_cotizacion`
- `chofer_id` → `result.content.0.text.llamada_info.chofer_id`
- `mensaje_agente` → `result.content.0.text.mensaje_para_agente`
- `chofer_nombre` → `result.content.0.text.chofer_info.conductor`
- `vehiculo_placa` → `result.content.0.text.chofer_info.placa`

---

## 🔍 **Herramientas Adicionales Disponibles**

### 1. **Búsqueda por Placa**
```json
{
  "jsonrpc": "2.0",
  "id": "buscar_placa",
  "method": "tools/call",
  "params": {
    "name": "get_chofer_by_placa",
    "arguments": {
      "placa": "ABC123"
    }
  }
}
```

### 2. **Historial de Llamadas por Teléfono**
```json
{
  "jsonrpc": "2.0",
  "id": "historial_telefono",
  "method": "tools/call",
  "params": {
    "name": "get_llamadas_by_telefono",
    "arguments": {
      "telefono": "{{system__caller_id}}",
      "tipo_busqueda": "ambos"
    }
  }
}
```

### 3. **Activar/Actualizar Conversación**
```json
{
  "jsonrpc": "2.0",
  "id": "activar_conversacion",
  "method": "tools/call",
  "params": {
    "name": "activate_conversation",
    "arguments": {
      "conversation_id": "{{system__conversation_id}}",
      "new_status": "en_curso",
      "call_notes": "Conversación iniciada con cliente"
    }
  }
}
```

---

## 🎭 **Variables Dinámicas**

### **Variables del Sistema (Automáticas):**
- `{{system__conversation_id}}` - ID único de la conversación
- `{{system__caller_id}}` - Número del cliente que llama
- `{{system__called_number}}` - Número de destino
- `{{system__call_duration_secs}}` - Duración de la llamada
- `{{system__time}}` - Hora actual

### **Variables Personalizadas (De herramientas):**
- `{{cotizacion_id}}` - ID de cotización encontrada
- `{{chofer_id}}` - ID del chofer asignado
- `{{mensaje_agente}}` - Mensaje contextual generado
- `{{chofer_nombre}}` - Nombre del conductor
- `{{vehiculo_placa}}` - Placa del vehículo

---

## 📞 **Flujo de Conversación Recomendado**

### **1. Inicio (Automático)**
- Saludo con datos de sistema
- Consulta automática usando `conversation_id`
- Presentación de información encontrada

### **2. Contexto (Basado en datos)**
- Si hay cotización: Detalles del servicio solicitado
- Si hay chofer: Información del conductor asignado
- Si hay vehículo: Datos de la unidad de transporte

### **3. Interacción**
- Responder preguntas específicas
- Ofrecer servicios adicionales
- Consultas por placa si es necesario
- Actualización de estados

### **4. Cierre**
- Resumen de información proporcionada
- Confirmación de próximos pasos
- Oferta de asistencia futura

---

## 🚀 **Ejemplo de Uso Completo**

**Cliente llama → Sistema identifica conversation_id → Herramienta consulta datos → Agente responde:**

> "¡Hola! Soy Natalia de CONALCA AI. Veo que tienes una cotización activa con ID 57 para transporte de MAIZ desde Funza a Bogotá. Tu chofer asignado es Sebastian Correa con el vehículo de placa ABC321. El valor de tu servicio es $10,000,000. ¿Necesitas alguna actualización sobre el estado de tu envío?"

---

## ⚙️ **Configuración en ElevenLabs**

1. **Crear Agente** con nombre "Natalia Álvarez - CONALCA AI"
2. **Pegar System Prompt** en el campo correspondiente
3. **Configurar Primer Mensaje** 
4. **Agregar Tool** `get_cotizacion_info_by_conversation`
5. **Configurar Variables Dinámicas** según necesidad
6. **Probar** con conversation_id real: `conv_4301k697vrn3e47b1rre6r6e3rzb`

---

## 🔧 **URL de Prueba con Variables**

```
https://elevenlabs.io/app/talk-to?agent_id=YOUR_AGENT_ID&var_test_conversation_id=conv_4301k697vrn3e47b1rre6r6e3rzb
```

---

## ✅ **Estado de Validación**

- ✅ **Servidor MCP:** Funcionando en `https://my-kontrol.online/mcp/elevenlabs`
- ✅ **11 Herramientas:** Todas operativas y probadas
- ✅ **Base de Datos:** Conectada con datos reales
- ✅ **Conversation ID:** Probado con `conv_4301k697vrn3e47b1rre6r6e3rzb`
- ✅ **Respuestas:** Generando mensajes contextuales correctos

**Fecha de Configuración:** 28 de Septiembre, 2025  
**Última Prueba:** 23:15 UTC  
**Estado:** ✅ Listo para Producción