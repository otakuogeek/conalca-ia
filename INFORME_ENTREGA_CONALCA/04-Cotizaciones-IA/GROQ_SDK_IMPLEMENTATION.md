# Implementación del Groq SDK en CONALCA AI

## 📋 Resumen
Se ha implementado un SDK personalizado (`GroqClient`) para mejorar la comunicación con Groq API y optimizar la extracción automática de datos de cotizaciones.

## 🎯 Cambios Implementados

### 1. Nuevo SDK: `GroqClient` (app/Services/GroqClient.php)

**Características:**
- ✅ Manejo robusto de errores con reintentos automáticos (3 intentos)
- ✅ Exponential backoff (1s, 2s, 4s entre reintentos)
- ✅ Logging detallado de todas las operaciones
- ✅ Métodos helper para extraer datos de respuestas
- ✅ Configuración centralizada desde .env
- ✅ Timeout configurable (default: 30s)

**Métodos Principales:**
```php
// Crear chat completion con tool calling
$response = $groqClient->createChatCompletion($messages, $tools, $options);

// Extraer mensaje del asistente
$message = $groqClient->extractAssistantMessage($response);

// Verificar tool calls
$hasTools = $groqClient->hasToolCalls($response);

// Extraer tool calls
$toolCalls = $groqClient->extractToolCalls($response);

// Extraer contenido de texto
$content = $groqClient->extractContent($response);

// Obtener uso de tokens
$usage = $groqClient->getUsage($response);
```

### 2. MCPAssistantService Refactorizado

**Cambios clave:**
- Reemplazado `Http::post()` directo por `GroqClient->createChatCompletion()`
- Eliminadas propiedades estáticas `$groq_api_key`, `$groq_model`, `$groq_base_url`
- Agregada propiedad estática `$groq_client` (instancia de GroqClient)
- Método `initConfig()` ahora crea instancia del SDK

**Antes:**
```php
$response = Http::timeout(30)
    ->withHeaders([
        'Authorization' => 'Bearer ' . self::$groq_api_key,
        'Content-Type' => 'application/json',
    ])
    ->post(self::$groq_base_url . '/chat/completions', [
        'model' => self::$groq_model,
        'messages' => $messages,
        'tools' => $tools,
        'temperature' => 0.7,
        'max_tokens' => 1000
    ]);
```

**Después:**
```php
$response = self::$groq_client->createChatCompletion(
    $messages,
    $tools,
    [
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'tool_choice' => 'auto'
    ]
);
```

### 3. Extracción Automática Mejorada

**Función:** `extractAllDataFromMessage()` - Versión MEJORADA

**Mejoras:**
- ✅ Logging detallado de cada campo extraído
- ✅ Múltiples patrones de búsqueda para cada dato
- ✅ Manejo de ciudades con espacios ("Funza", "Valle del Cauca")
- ✅ Detección de peso en toneladas Y kilogramos
- ✅ Detección de valor en millones, pesos, USD
- ✅ 7 tipos de empaques (cajas, bultos, paquetes, estibas, pallets, tambores, sacos)
- ✅ 11 productos comunes detectados
- ✅ 10 tipos de vehículos detectados

**Campos extraídos:**
1. **origen** - Ciudad de origen
2. **destino** - Ciudad de destino
3. **peso_kg** - Peso en kilogramos (convierte toneladas automáticamente)
4. **cantidad** - Número de unidades
5. **valor_declarado** - Valor en pesos COP
6. **empaque** - Tipo de embalaje (CAJAS, BULTOS, etc.)
7. **producto** - Producto/mercancía
8. **vehiculo** - Tipo de vehículo requerido

**Ejemplo de extracción:**
```php
// Mensaje: "Necesito una cotización de importación nacionalizada de cartagena a funza, 
// son 15 toneladas de neumaticos por un valor declarado de 35 millones, 60 unidades 
// empacadas en cajas, un único vehículo con capacidad de transportar contenedor"

// Datos extraídos:
[
    'origen' => 'Cartagena',
    'destino' => 'Funza',
    'peso_kg' => 15000,
    'cantidad' => 60,
    'valor_declarado' => 35000000,
    'empaque' => 'CAJAS',
    'producto' => 'neumaticos',
    'vehiculo' => 'contenedor'
]
```

### 4. Respuesta Enriquecida al Frontend

**Cambios en `runAssistant()` y `processToolCalls()`:**

**Antes:**
```php
return [
    'id' => $runId,
    'status' => 'completed'
];
```

**Después:**
```php
return [
    'id' => $runId,
    'status' => 'completed',
    'extracted_data' => $extractedData  // NUEVO: datos extraídos por regex
];

// En caso de tool calls:
return [
    'id' => $runId,
    'status' => 'completed_with_data',
    'quote_data' => $mergedData,        // Datos de tools
    'extracted_data' => $extractedData  // Datos extraídos por regex
];
```

**Metadata de sesión actualizada:**
```php
$metadata = [
    'last_run_id' => $runId,
    'last_run_status' => 'completed_with_data',
    'last_run_at' => '2026-01-07T16:51:59Z',
    'tool_results' => [...],
    'quote_data' => [...],      // Datos combinados
    'extracted_data' => [...]   // Datos de regex
];
```

## 🔧 Configuración

### Variables .env requeridas:
```env
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=qwen/qwen3-32b
MCP_BASE_URL=https://conalcaia.conalca.com.co/mcp/
```

### Modelo actual:
- **qwen/qwen3-32b**: 32B parámetros, multilingüe, optimizado para español
- Alternativas probadas: `openai/gpt-oss-120b`, `llama-4-maverick-17b-128e-instruct`

## 📝 Logging Mejorado

### Logs de GroqClient:
```log
[INFO] GroqClient inicializado {"model":"qwen/qwen3-32b","base_url":"..."}
[INFO] GroqClient: Creando chat completion {"model":"qwen/qwen3-32b","messages_count":3,"tools_count":3}
[INFO] GroqClient: Respuesta exitosa {"finish_reason":"tool_calls","has_tool_calls":true,"usage":{"prompt_tokens":902,"completion_tokens":99}}
[WARNING] GroqClient: Error HTTP {"status":503,"attempt":1}
[INFO] GroqClient: Esperando antes de reintentar {"wait_seconds":1}
```

### Logs de Extracción:
```log
[INFO] extractAllDataFromMessage: Procesando mensaje {"text":"...","length":253}
[INFO] extractAllDataFromMessage: Ciudades detectadas (patrón 1) {"origen":"Cartagena","destino":"Funza"}
[INFO] extractAllDataFromMessage: Peso detectado (toneladas) {"toneladas":15,"peso_kg":15000}
[INFO] extractAllDataFromMessage: Cantidad detectada {"cantidad":60}
[INFO] extractAllDataFromMessage: Valor detectado (millones) {"millones":35,"valor_declarado":35000000}
[INFO] extractAllDataFromMessage: Empaque detectado {"keyword":"cajas","empaque":"CAJAS"}
[INFO] extractAllDataFromMessage: Producto detectado {"keyword":"neumatic","producto":"neumaticos"}
[INFO] extractAllDataFromMessage: Vehículo detectado {"keyword":"contenedor","vehiculo":"contenedor"}
[INFO] extractAllDataFromMessage: Extracción completada {"data_count":8,"data":{...}}
```

### Logs de Tool Calls:
```log
[INFO] Tool calls procesados exitosamente {
    "run_id":"run_mcp_tools_1767821788",
    "has_quote_data":true,
    "tools_executed":3,
    "extracted_count":8,
    "merged_count":11
}
```

## 🧪 Pruebas

### 1. Mensaje de prueba completo:
```
Necesito una cotización de importación nacionalizada de cartagena a funza, son 15 toneladas de neumaticos por un valor declarado de 35 millones, 60 unidades empacadas en cajas, un único vehículo con capacidad de transportar contenedor sin necesidad de devolución, el peso no incluye tara. El cargue debe ser el 15/01 2pm y el descargue el 17/01 10AM
```

### 2. Verificar logs:
```bash
tail -f storage/logs/laravel.log | grep -E "(extractAllDataFromMessage|GroqClient|Tool calls procesados)"
```

### 3. Verificar respuesta en frontend:
- Abrir DevTools → Network → buscar request a `/api/chat/run`
- Verificar response contiene `extracted_data`
- Panel de progreso debe mostrar 8/8 campos completados

### 4. Limpiar sesión para prueba:
```bash
php artisan chat:clear-session 633
```

## 🎯 Ventajas del Nuevo Sistema

### 1. Mayor Confiabilidad
- Reintentos automáticos ante errores temporales
- Exponential backoff para evitar saturación
- Manejo robusto de timeouts

### 2. Mejor Observabilidad
- Logs detallados de cada operación
- Tracking de uso de tokens
- Debugging simplificado

### 3. Extracción Híbrida
- **Backend regex**: Extracción garantizada de datos básicos
- **AI tool calling**: Enriquecimiento con búsquedas en BD
- **Combinación**: Mejor de ambos mundos

### 4. Frontend Poblado Inmediatamente
- Datos extraídos llegan en primera respuesta
- No depende de que AI llame tools correctamente
- Panel de progreso se llena automáticamente

## 🔄 Flujo Completo

```
1. Usuario envía mensaje
   ↓
2. MCPAssistantService::runAssistant()
   ↓
3. extractAllDataFromMessage() → regex extraction
   ↓
4. GroqClient::createChatCompletion() → AI processing
   ↓
5. AI responde con tool_calls
   ↓
6. processToolCalls() → ejecuta MCP tools
   ↓
7. Merge: extracted_data + tool_data → quote_data
   ↓
8. Respuesta incluye:
   - id, status
   - extracted_data (regex)
   - quote_data (combinado)
   ↓
9. Frontend recibe y popula panel (8/8 campos)
```

## 📊 Modelos Comparados

| Modelo | Parámetros | Extracción (curl) | Extracción (prod) | Velocidad | Estado |
|--------|-----------|-------------------|-------------------|-----------|--------|
| groq/compound | ? | - | - | - | ❌ Bloqueado org |
| llama-4-maverick-17b-128e | 17B/128 exp | ✅ 8/8 | ❌ - | - | ❌ Bloqueado proyecto |
| llama-3.3-70b-versatile | 70B | - | - | - | ❌ Bloqueado proyecto |
| llama-4-scout-17b-16e | 17B/16 exp | ⚠️ 1/8 | ⚠️ 1/8 | Rápido | ✅ Disponible |
| openai/gpt-oss-120b | 120B | ✅ 8/8 | ⚠️ 1/8 | Medio | ✅ Disponible |
| **qwen/qwen3-32b** | **32B** | **✅ 8/8** | **🧪 Pendiente** | **Rápido** | **✅ ACTUAL** |

## ✅ Checklist de Implementación

- [x] Crear GroqClient SDK
- [x] Refactorizar MCPAssistantService
- [x] Mejorar extractAllDataFromMessage()
- [x] Agregar extracted_data a respuesta
- [x] Combinar datos regex + tools
- [x] Agregar logging detallado
- [x] Configurar qwen/qwen3-32b
- [x] Documentar cambios
- [ ] **PENDIENTE**: Probar con mensaje completo en producción
- [ ] **PENDIENTE**: Verificar 8/8 campos en panel de progreso
- [ ] **PENDIENTE**: Medir performance vs gpt-oss-120b
- [ ] **PENDIENTE**: Optimizar patrones regex según feedback

## 🚀 Próximos Pasos

1. **Prueba en Producción**
   - Enviar mensaje de prueba completo
   - Verificar logs de extracción
   - Confirmar panel 8/8 campos

2. **Monitoreo**
   - Revisar logs de GroqClient
   - Medir latencia de respuestas
   - Tracking de reintentos

3. **Optimización**
   - Ajustar patrones regex según casos reales
   - Agregar más productos/vehículos comunes
   - Optimizar temperatura del modelo

4. **Frontend Integration**
   - Verificar que ChatModal recibe extracted_data
   - Confirmar auto-población de quoteData
   - Validar progress bar (completed/8 * 100%)

## 📞 Soporte

Si encuentras problemas:
1. Revisar logs: `tail -f storage/logs/laravel.log`
2. Verificar .env tiene `GROQ_API_KEY` y `GROQ_MODEL`
3. Limpiar caché: `php artisan config:clear`
4. Limpiar sesión: `php artisan chat:clear-session {client_id}`

---

**Última actualización:** 2026-01-07  
**Implementado por:** GitHub Copilot  
**Modelo actual:** qwen/qwen3-32b (32B parámetros)
