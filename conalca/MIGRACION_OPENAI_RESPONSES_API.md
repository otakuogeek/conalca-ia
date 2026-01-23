# 🔄 PLAN DE MIGRACIÓN: OpenAI Assistants API → Responses API

## 📋 RESUMEN

La **API de Assistants de OpenAI está obsoleta** y se eliminará en **agosto de 2026**.  
Necesitamos migrar a la nueva **API de Responses** (también conocida como Chat Completions API moderna).

**Documentación oficial:** https://platform.openai.com/docs/guides/migrate-to-responses

---

## ✅ ESTADO ACTUAL

### Backend (PHP) - ✅ YA ESTÁ CORRECTO
El servicio `MCPAssistantService.php` **ya usa Chat Completions API**, que es el reemplazo correcto:

```php
// ✅ CORRECTO - Ya implementado
Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json'
])->post('https://api.openai.com/v1/chat/completions', [
    'model' => $model,
    'messages' => $messages,
    'tools' => $tools
]);
```

### Frontend (JavaScript) - ❌ USA API OBSOLETA
Los siguientes archivos usan la **API de Assistants obsoleta**:

1. `resources/views/livewire/drog-zone.blade.php` (7 ocurrencias)
2. `resources/views/livewire/pruebadosformulario.blade.php` (7 ocurrencias)

**Código problemático:**
```javascript
// ❌ OBSOLETO - Se eliminará en agosto 2026
fetch('https://api.openai.com/v1/threads', {
    headers: {
        'OpenAI-Beta': 'assistants=v2'
    }
});

fetch(`https://api.openai.com/v1/threads/${threadID}/messages`, {
    headers: {
        'OpenAI-Beta': 'assistants=v2'
    }
});
```

---

## 🎯 PLAN DE MIGRACIÓN

### OPCIÓN 1: Usar el backend existente (RECOMENDADO)
En lugar de llamar directamente a OpenAI desde el frontend, usar los endpoints Laravel ya implementados:

**Ventajas:**
- ✅ Ya está implementado y funciona
- ✅ No expone la API key en el frontend
- ✅ Mayor seguridad
- ✅ Manejo centralizado de errores
- ✅ No requiere migración de API

**Endpoints disponibles:**
```javascript
// Chat con Arcángel (asistente IA)
POST /api/chat/message
Body: {
    group_id: number,
    message: string,
    client_id: number
}

// Extraer datos de cotización
POST /api/chat/extract-quote-data
Body: {
    group_id: number,
    messages: array
}

// Obtener rutas
GET /api/chat/quote/routes/{groupId}
```

### OPCIÓN 2: Migrar a Chat Completions API directa
Si es necesario mantener llamadas directas desde el frontend:

**DE (API Assistants - OBSOLETA):**
```javascript
// Crear thread
const thread = await fetch('https://api.openai.com/v1/threads', {
    method: 'POST',
    headers: {
        'OpenAI-Beta': 'assistants=v2'
    }
});

// Enviar mensaje
await fetch(`https://api.openai.com/v1/threads/${threadID}/messages`, {
    method: 'POST',
    headers: {
        'OpenAI-Beta': 'assistants=v2'
    },
    body: JSON.stringify({
        role: 'user',
        content: message
    })
});

// Ejecutar asistente
await fetch(`https://api.openai.com/v1/threads/${threadID}/runs`, {
    method: 'POST',
    headers: {
        'OpenAI-Beta': 'assistants=v2'
    },
    body: JSON.stringify({
        assistant_id: assistantID
    })
});
```

**A (Chat Completions API - MODERNA):**
```javascript
// NO SE NECESITA crear thread - se maneja estado en el frontend
const messages = [
    { role: 'system', content: 'Eres un asistente de cotizaciones...' },
    ...previousMessages, // Mantener historial localmente
    { role: 'user', content: userMessage }
];

// Enviar mensaje (una sola llamada)
const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        model: 'gpt-4o',
        messages: messages,
        tools: [ /* definiciones de herramientas */ ]
    })
});

const data = await response.json();
const assistantMessage = data.choices[0].message.content;

// Agregar al historial local
messages.push({ role: 'assistant', content: assistantMessage });
```

---

## 📝 CAMBIOS CLAVE EN LA MIGRACIÓN

### 1. Gestión de Threads
- **ANTES:** OpenAI gestiona threads en el servidor
- **AHORA:** El cliente gestiona el historial de mensajes

### 2. Envío de Mensajes
- **ANTES:** 2 llamadas (agregar mensaje + ejecutar asistente)
- **AHORA:** 1 llamada (chat completions con historial)

### 3. Herramientas (Tools/Functions)
- **ANTES:** Se definen en el asistente en OpenAI dashboard
- **AHORA:** Se envían en cada request como parámetro `tools`

### 4. Streaming
- **ANTES:** Polling de runs status
- **AHORA:** Server-Sent Events (SSE) nativo

---

## 🔧 IMPLEMENTACIÓN RECOMENDADA

### Paso 1: Refactorizar frontend para usar backend API

**Archivo:** `resources/views/livewire/drog-zone.blade.php`

```javascript
// ✅ NUEVO - Usar endpoint Laravel
async function sendMessage(userMessage, groupId, clientId) {
    try {
        const response = await fetch('/api/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                group_id: groupId,
                message: userMessage,
                client_id: clientId
            })
        });

        const data = await response.json();
        return data.response; // Respuesta del asistente
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        throw error;
    }
}

// ✅ NUEVO - Mantener historial localmente (si se necesita)
let conversationHistory = [];

function addToHistory(role, content) {
    conversationHistory.push({ role, content });
    localStorage.setItem('chat_history', JSON.stringify(conversationHistory));
}

function loadHistory() {
    const saved = localStorage.getItem('chat_history');
    if (saved) {
        conversationHistory = JSON.parse(saved);
    }
}
```

### Paso 2: Eliminar código obsoleto

Remover todas las referencias a:
- `threads` endpoint
- `OpenAI-Beta: assistants=v2` header
- `threadID` localStorage
- Polling de runs status

### Paso 3: Actualizar UI

Mantener la misma experiencia de usuario pero usando los nuevos endpoints.

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### Seguridad
- ❌ **NO exponer** `OPENAI_API_KEY` en el frontend
- ✅ **SÍ usar** endpoints Laravel con autenticación

### Performance
- La API de Chat Completions es **más rápida** (1 request vs 2-3)
- No hay polling de status - respuesta inmediata

### Costos
- **Mismo costo** por token
- Potencialmente **menor costo** por menos overhead de API

### Migración Gradual
1. Mantener código obsoleto con feature flag
2. Migrar componente por componente
3. Eliminar código antiguo después de pruebas

---

## 📅 TIMELINE

- **Enero 2026:** Crear plan de migración ✅
- **Febrero 2026:** Implementar refactorización frontend
- **Marzo 2026:** Testing exhaustivo
- **Abril 2026:** Deploy a producción
- **Mayo-Julio 2026:** Monitoreo y ajustes
- **Agosto 2026:** API de Assistants será eliminada

---

## 🧪 TESTING

### Tests necesarios:
1. ✅ Envío de mensajes básicos
2. ✅ Historial de conversación
3. ✅ Tool calling (crear cotización)
4. ✅ Manejo de errores
5. ✅ Reconexión automática
6. ✅ Persistencia de estado

---

## 📚 RECURSOS

- [Documentación oficial de migración](https://platform.openai.com/docs/guides/migrate-to-responses)
- [Chat Completions API Reference](https://platform.openai.com/docs/api-reference/chat)
- [Function Calling Guide](https://platform.openai.com/docs/guides/function-calling)
- [MCPAssistantService.php](app/Services/MCPAssistantService.php) - Implementación de referencia

---

## ✅ PRÓXIMOS PASOS

1. **INMEDIATO:** Identificar todos los usos de API de Assistants
2. **ESTA SEMANA:** Refactorizar `drog-zone.blade.php`
3. **PRÓXIMA SEMANA:** Refactorizar `pruebadosformulario.blade.php`
4. **MES ACTUAL:** Testing completo
5. **SIGUIENTE MES:** Deploy a producción
