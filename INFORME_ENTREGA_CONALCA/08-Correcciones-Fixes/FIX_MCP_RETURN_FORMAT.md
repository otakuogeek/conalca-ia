# Fix: MCPAssistantService Return Format

## Problema Detectado

**Síntoma**: Chat mostrando "⚠️ El servicio de IA no está disponible"

**Log**: 
```
[2026-01-07 13:22:27] local.WARNING: MCP assistant falló, implementando fallback
```

## Causa Raíz

El método `MCPAssistantService::runAssistant()` estaba retornando un array con clave `run_id`:

```php
return [
    'run_id' => $runId,  // ❌ INCORRECTO
    'status' => 'completed'
];
```

Pero el `ChatController` esperaba la clave `id`:

```php
$run = MCPAssistantService::runAssistant($threadId, $request->type_business);
if (!$run || !isset($run['id'])) {  // Busca 'id'
    // Fallback - muestra "IA no disponible"
}
```

## Solución Aplicada

### 1. Corregido en `runAssistant()` (línea ~202)

**Antes**:
```php
return [
    'run_id' => $runId,
    'status' => 'completed'
];
```

**Después**:
```php
return [
    'id' => $runId,  // ✅ CORRECTO
    'status' => 'completed'
];
```

### 2. Corregido en `processToolCalls()` (línea ~311)

**Antes**:
```php
return [
    'run_id' => $runId,
    'status' => !empty($extractedData) ? 'completed_with_data' : 'completed',
    'quote_data' => $extractedData
];
```

**Después**:
```php
return [
    'id' => $runId,  // ✅ CORRECTO
    'status' => !empty($extractedData) ? 'completed_with_data' : 'completed',
    'quote_data' => $extractedData
];
```

### 3. Logs Adicionales Agregados

Para facilitar debugging futuro:

```php
// Al enviar a OpenAI
Log::info('Enviando request a OpenAI', [
    'model' => self::$openai_model,
    'messages_count' => count($messages),
    'tools_count' => count($tools)
]);

// Al recibir respuesta
Log::info('Respuesta de OpenAI recibida', [
    'status' => $response->status(),
    'successful' => $response->successful()
]);

// Al procesar mensaje
Log::info('Mensaje del asistente recibido', [
    'has_tool_calls' => isset($assistantMessage['tool_calls']),
    'has_content' => isset($assistantMessage['content'])
]);

// Al crear run_id
Log::info('Creando run_id para respuesta', [
    'run_id' => $runId,
    'thread_id' => $threadId
]);

// Al completar tool calls
Log::info('Tool calls procesados exitosamente', [
    'run_id' => $runId,
    'has_quote_data' => !empty($extractedData),
    'tools_executed' => count($toolResults)
]);
```

## Flujo Correcto Ahora

### Chat Normal (sin tool calls)
```
1. Usuario envía mensaje
2. MCPAssistantService::runAssistant()
3. OpenAI responde con contenido texto
4. Se guarda mensaje del asistente
5. Se retorna ['id' => 'run_mcp_1234567890', 'status' => 'completed']
6. ChatController recibe run['id'] ✅
7. Frontend muestra respuesta
```

### Chat con Tool Calls (cotizaciones)
```
1. Usuario envía mensaje con datos de cotización
2. MCPAssistantService::runAssistant()
3. OpenAI responde con tool_calls
4. Se ejecutan herramientas MCP (create_cotizacion, search_products, etc.)
5. Se guardan resultados
6. Se extrae quote_data
7. Se retorna ['id' => 'run_mcp_tools_123', 'status' => 'completed_with_data', 'quote_data' => [...]]
8. ChatController recibe run['id'] ✅
9. Frontend procesa quote_data
```

## Validación

### Test de Logs
Después del fix, deberías ver:

```
[timestamp] local.INFO: MCPAssistantService initialized
[timestamp] local.INFO: Thread obtenido/creado
[timestamp] local.INFO: Mensaje creado
[timestamp] local.INFO: Ejecutando asistente MCP
[timestamp] local.INFO: Enviando request a OpenAI
[timestamp] local.INFO: Respuesta de OpenAI recibida {"status":200,"successful":true}
[timestamp] local.INFO: Mensaje del asistente recibido
[timestamp] local.INFO: Creando run_id para respuesta {"run_id":"run_mcp_..."}
```

### Test de Respuesta del Controller

**API Response esperado**:
```json
{
  "success": true,
  "data": {
    "thread_id": "mcp_633_1767810034",
    "run_id": "run_mcp_1767810234",
    "user_message": {...},
    "messages": [...]
  }
}
```

## Archivos Modificados

1. `/home/ubuntu/conalca/conalca/app/Services/MCPAssistantService.php`
   - Línea ~202: `'id' => $runId` en runAssistant()
   - Línea ~311: `'id' => $runId` en processToolCalls()
   - Logs adicionales en múltiples puntos

## Compatibilidad

✅ **OpenAI Assistants API** (QuoteAssistantService):
```php
$run = QuoteAssistantService::runAssistant(...);
// Retorna: ['id' => 'run_xyz123', ...]
```

✅ **MCP Assistant** (MCPAssistantService):
```php
$run = MCPAssistantService::runAssistant(...);
// Retorna: ['id' => 'run_mcp_123', ...]
```

Ambos ahora usan la misma estructura de respuesta.

## Pruebas

### Test 1: Mensaje Simple
```
Usuario: "hola"
Esperado: Respuesta del asistente sin errores
```

### Test 2: Cotización
```
Usuario: "Necesito cotización de Bogotá a Medellín, 1000kg"
Esperado: Tool calls ejecutados, quote_data extraído
```

### Test 3: Verificar Logs
```bash
tail -f storage/logs/laravel.log | grep -E "(MCPAssistant|OpenAI|Tool calls)"
```

Deberías ver logs detallados sin mensajes de "fallback".

## Próximos Pasos

1. ✅ Fix aplicado
2. ✅ Cachés limpiadas
3. ⏳ **Prueba el chat ahora** - Recarga la página y envía un mensaje
4. ⏳ Monitorear logs en tiempo real
5. ⏳ Validar creación de cotizaciones

---

**Fix Aplicado**: 7 de enero de 2026, 13:28 UTC
**Estado**: ✅ Listo para probar
**Impacto**: CRÍTICO - Resuelve "IA no disponible"
