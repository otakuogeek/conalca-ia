# Fix: Flujo de Auto-Extracción y Auto-Llenado de Cotizaciones

## 🐛 Problema Reportado

El usuario reportó que el sistema **solo le pidió seleccionar el producto**, pero **NO extrajo ni auto-llenó los demás datos** de la cotización (origen, destino, peso, cantidad, valor, empaque, vehículo).

**Evidencia:**
- La extracción funcionaba en consola (8/8 campos detectados)
- PERO los datos NO se enviaban al frontend
- PERO el AI NO confirmaba los datos extraídos
- PERO el AI pedía datos que ya había detectado

## 🔍 Causa Raíz

1. **Backend extraía correctamente** (✅)
2. **Backend NO enviaba `extracted_data` al frontend** (❌)
3. **System prompt NO instruía al AI a usar datos pre-extraídos** (❌)
4. **ChatController NO incluía `extracted_data` en respuestas** (❌)

## ✅ Solución Implementada

### 1. ChatController - Envío de Datos Extraídos

**Archivo:** `app/Http/Controllers/Api/ChatController.php`

**Cambio en `quoteChat()`:**
```php
// ANTES:
return response()->json([
    'success' => true,
    'data' => [
        'thread_id' => $threadId,
        'run_id' => $run['id'],
        'user_message' => $userMessage,
        'messages' => $messages
    ]
]);

// DESPUÉS:
return response()->json([
    'success' => true,
    'data' => [
        'thread_id' => $threadId,
        'run_id' => $run['id'],
        'user_message' => $userMessage,
        'messages' => $messages,
        'extracted_data' => $run['extracted_data'] ?? null  // ✅ NUEVO
    ]
]);
```

**Cambio en `checkRunStatus()`:**
```php
// ANTES:
return response()->json([
    'success' => true,
    'data' => [
        'thread_id' => $threadId,
        'run_id' => $runId,
        'quote_data' => $runData,
        'status' => 'completed_with_data'
    ]
]);

// DESPUÉS:
$extractedData = $runData['extracted_data'] ?? null;
$quoteData = $runData['quote_data'] ?? $runData;

return response()->json([
    'success' => true,
    'data' => [
        'thread_id' => $threadId,
        'run_id' => $runId,
        'quote_data' => $quoteData,
        'extracted_data' => $extractedData,  // ✅ NUEVO
        'status' => 'completed_with_data'
    ]
]);
```

### 2. MCPAssistantService - Persistencia de Datos

**Archivo:** `app/Services/MCPAssistantService.php`

**Cambio en `checkRunStatus()`:**
```php
// Incluir datos extraídos automáticamente si existen
if (isset($metadata['extracted_data'])) {
    $result['extracted_data'] = $metadata['extracted_data'];
    Log::info('checkRunStatus: Incluyendo extracted_data', [
        'run_id' => $runId,
        'extracted_count' => count($metadata['extracted_data'])
    ]);
}
```

### 3. System Prompt - Instrucciones Mejoradas

**Archivo:** `app/Services/MCPAssistantService.php` - Función `getSystemPrompt()`

**ANTES:**
```php
$dataInstruction = "\n\n🎯 DATOS PRE-EXTRAÍDOS:\n";
foreach ($extractedData as $key => $value) {
    $dataInstruction .= "✓ " . ucfirst($key) . ": {$value}\n";
}
$dataInstruction .= "\nUSA estos datos en tu respuesta.\n";
```

**DESPUÉS:**
```php
$dataInstruction = "\n\n🎯 DATOS PRE-EXTRAÍDOS DEL MENSAJE:\n";
foreach ($extractedData as $key => $value) {
    $dataInstruction .= "✓ " . ucfirst($key) . ": {$value}\n";
}
$dataInstruction .= "\n⚡ INSTRUCCIONES CRÍTICAS:\n";
$dataInstruction .= "1. CONFIRMA estos datos al usuario en tu respuesta\n";
$dataInstruction .= "2. NO vuelvas a preguntar por estos datos\n";
$dataInstruction .= "3. SOLO pide lo que FALTA (si falta algo)\n";
$dataInstruction .= "4. Procede INMEDIATAMENTE a buscar productos con search_products()\n";
$dataInstruction .= "5. Si todos los datos están completos, confirma y crea la cotización\n\n";
```

## 🎯 Flujo Esperado Ahora

### Mensaje del Usuario:
```
"Necesito una cotización de importación nacionalizada de cartagena a funza, 
son 15 toneladas de neumaticos por un valor declarado de 35 millones, 
60 unidades empacadas en cajas, un único vehículo con capacidad de 
transportar contenedor"
```

### Paso 1: Backend Extrae Automáticamente
```php
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

### Paso 2: AI Recibe Datos en System Prompt
```
🎯 DATOS PRE-EXTRAÍDOS DEL MENSAJE:
✓ Origen: Cartagena
✓ Destino: Funza
✓ Peso_kg: 15000
✓ Cantidad: 60
✓ Valor_declarado: 35000000
✓ Empaque: CAJAS
✓ Producto: neumaticos
✓ Vehiculo: contenedor

⚡ INSTRUCCIONES CRÍTICAS:
1. CONFIRMA estos datos al usuario en tu respuesta
2. NO vuelvas a preguntar por estos datos
3. SOLO pide lo que FALTA (si falta algo)
4. Procede INMEDIATAMENTE a buscar productos con search_products()
5. Si todos los datos están completos, confirma y crea la cotización
```

### Paso 3: AI Responde (Esperado)
```
"Perfecto, he registrado tu solicitud:
✓ Ruta: Cartagena → Funza
✓ Peso: 15,000 kg
✓ Cantidad: 60 unidades
✓ Valor: $35,000,000
✓ Embalaje: CAJAS
✓ Vehículo: Contenedor

Buscando neumáticos en nuestro catálogo..."

[Llama search_products("neumaticos")]
```

### Paso 4: AI Presenta Opciones de Producto
```
"Encontré estos tipos de neumáticos:
1. NEUMATICOS NUEVOS DE CAUCHO
2. NEUMATICOS RECAUCHUTADOS O USADOS

¿Cuál describe mejor tu mercancía? (Responde con el número)"
```

### Paso 5: Usuario Selecciona
```
"1"
```

### Paso 6: AI Crea Cotización INMEDIATAMENTE
```
[Llama create_cotizacion({
    pricing_id: 43214,
    ciudad_origen: "cartagena",
    ciudad_destino: "funza",
    peso_mercancia: 15000,
    cantidad: 60,
    tipo_embajale: "CAJAS",
    tipo_producto: "NEUMATICOS NUEVOS DE CAUCHO",
    vehiculo_requerido: "contenedor",
    valor_declarado: 35000000
})]

"¡Perfecto! He creado tu cotización con todos los detalles."
```

### Paso 7: Frontend Recibe y Auto-Llena
```javascript
// Respuesta de /api/chat/quote
{
    "success": true,
    "data": {
        "thread_id": "mcp_633_1767810034",
        "run_id": "run_mcp_1767821788",
        "messages": [...],
        "extracted_data": {  // ✅ NUEVO: Frontend recibe esto
            "origen": "Cartagena",
            "destino": "Funza",
            "peso_kg": 15000,
            "cantidad": 60,
            "valor_declarado": 35000000,
            "empaque": "CAJAS",
            "producto": "neumaticos",
            "vehiculo": "contenedor"
        }
    }
}
```

```javascript
// ChatModal.jsx debe usar extracted_data para poblar quoteData:
if (response.data.extracted_data) {
    setQuoteData({
        ciudadOrigen: response.data.extracted_data.origen,
        ciudadDestino: response.data.extracted_data.destino,
        pesoMercancia: response.data.extracted_data.peso_kg,
        cantidadMercancia: response.data.extracted_data.cantidad,
        valorMercancia: response.data.extracted_data.valor_declarado,
        claseVehiculo: response.data.extracted_data.vehiculo,
        // empaque y producto se manejan vía búsqueda
    });
}
```

## 📊 Panel de Progreso - Estado Esperado

**Después de primer mensaje:**

| Campo | Estado | Valor |
|-------|--------|-------|
| 1. Origen | ✅ | Cartagena |
| 2. Destino | ✅ | Funza |
| 3. Peso | ✅ | 15,000 kg |
| 4. Cantidad | ✅ | 60 unidades |
| 5. Embalaje | ⏳ | Buscando... |
| 6. Producto | ⏳ | Buscando... |
| 7. Vehículo | ✅ | Contenedor |
| 8. Valor | ✅ | $35,000,000 |

**Progreso:** 6/8 campos (75%)

**Después de seleccionar producto:**

| Campo | Estado | Valor |
|-------|--------|-------|
| 1. Origen | ✅ | Cartagena |
| 2. Destino | ✅ | Funza |
| 3. Peso | ✅ | 15,000 kg |
| 4. Cantidad | ✅ | 60 unidades |
| 5. Embalaje | ✅ | CAJAS |
| 6. Producto | ✅ | NEUMATICOS NUEVOS DE CAUCHO |
| 7. Vehículo | ✅ | Contenedor |
| 8. Valor | ✅ | $35,000,000 |

**Progreso:** 8/8 campos (100%) ✅

## 🧪 Pruebas Realizadas

### Test 1: Extracción Backend
```bash
$ php -r "..." (test de extractAllDataFromMessage)
✅ Datos extraídos: 8 campos
   ✓ origen            : Cartagena
   ✓ destino           : Funza
   ✓ peso_kg           : 15000
   ✓ cantidad          : 60
   ✓ valor_declarado   : 35000000
   ✓ empaque           : CAJAS
   ✓ producto          : neumaticos
   ✓ vehiculo          : contenedor

📊 Resultado: 8/8 campos esperados
✅ PERFECTO: Todos los campos extraídos
```

### Test 2: GroqClient SDK
```bash
$ php -r "..." (test de GroqClient)
✅ GroqClient inicializado
   Modelo: qwen/qwen3-32b
📤 Enviando mensaje de prueba...
✅ Respuesta recibida
   Tokens: 100 (prompt: 50, completion: 50)
✅ SDK funcionando correctamente
```

## 🔧 Configuración Actual

```env
GROQ_API_KEY=your_groq_api_key_here
GROQ_MODEL=qwen/qwen3-32b
MCP_BASE_URL=https://conalcaia.conalca.com.co/mcp/
```

## 📝 Logs a Monitorear

```bash
# Ver extracción automática:
tail -f storage/logs/laravel.log | grep -E "extractAllDataFromMessage"

# Ver datos enviados al frontend:
tail -f storage/logs/laravel.log | grep -E "(extracted_data|checkRunStatus)"

# Ver procesamiento completo:
tail -f storage/logs/laravel.log | grep -E "(GroqClient|Tool calls|extracted)"
```

## ✅ Checklist de Verificación

- [x] Backend extrae 8/8 campos correctamente
- [x] `extracted_data` se guarda en metadata de sesión
- [x] `extracted_data` se incluye en respuesta de `quoteChat()`
- [x] `extracted_data` se incluye en respuesta de `checkRunStatus()`
- [x] System prompt instruye al AI a confirmar datos extraídos
- [x] System prompt instruye al AI a NO volver a preguntar por datos ya extraídos
- [x] GroqClient SDK implementado y funcionando
- [x] Logs configurados para debugging
- [ ] **PENDIENTE:** Frontend usa `extracted_data` para poblar `quoteData`
- [ ] **PENDIENTE:** Panel de progreso muestra 6/8 campos inmediatamente
- [ ] **PENDIENTE:** Prueba end-to-end en producción

## 🚀 Próximos Pasos

1. **Verificar Frontend** recibe y usa `extracted_data`
2. **Probar con mensaje real** en el chat
3. **Confirmar panel** muestra 6-8/8 campos inmediatamente
4. **Monitorear logs** para ajustar patrones si es necesario

## 📞 Debugging

Si el problema persiste:

1. **Verificar logs:**
   ```bash
   tail -30 storage/logs/laravel.log
   ```

2. **Verificar respuesta API:**
   - Abrir DevTools → Network
   - Buscar request a `/api/chat/quote` o `/api/chat/run/{threadId}/{runId}`
   - Verificar response incluye `extracted_data`

3. **Verificar metadata de sesión:**
   ```php
   php artisan tinker
   $session = ConversationSession::where('session_id', 'mcp_633_...')->first();
   $metadata = json_decode($session->metadata, true);
   print_r($metadata['extracted_data']);
   ```

---

**Última actualización:** 2026-01-07  
**Implementado por:** GitHub Copilot  
**Fix para:** Auto-extracción y auto-llenado de cotizaciones
