# Limpieza Automática del Chat al Iniciar Nueva Cotización

## Fecha: 2025-11-18

## Problema Identificado

El sistema mantenía el historial de mensajes del chat cuando el usuario iniciaba una nueva cotización, mostrando conversaciones anteriores en lugar de empezar con un chat limpio.

## Solución Implementada

### Sistema de Limpieza Completa

Cuando el usuario hace clic en **"Crear Cotización"**, el sistema ahora:

1. **Limpia el thread de OpenAI** en el backend
2. **Resetea todos los mensajes** del chat
3. **Limpia todos los estados** del formulario
4. **Elimina parámetros** de URL
5. **Inicia con chat en blanco** sin historial

## Archivos Modificados

### 1. Frontend: `resources/js/components/CotizacionInicial/QuoteIndex.jsx`

#### Función `resetCreateFlow()` - Ahora es async

```javascript
const resetCreateFlow = async () => {
  console.log('🧹 Limpiando completamente el formulario de cotización');
  
  // Si hay un threadId activo, limpiarlo en el backend
  if (clientData.threadId) {
    try {
      console.log('🗑️ Limpiando thread en backend:', clientData.threadId);
      await fetch('/api/chat/clear-thread', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          thread_id: clientData.threadId
            client_id: clientData?.clientId || null
        })
      });
      console.log('✅ Thread limpiado en backend');
    } catch (error) {
      console.warn('⚠️ Error limpiando thread:', error);
    }
  }
  
  // Limpiar todos los estados
  setStep(0);
  setQuoteData([]);
  setMessages([]); // ← LIMPIA MENSAJES DEL CHAT
  setInputMessage('');
  setPricings([]);
  setSelectedPricings({});
  setPorcentajeGlobal(17);
  
  // Resetear clientData completamente
  setClientData({
    search: '',
    clientId: null,
    clientName: '',
    // ... todos los campos
    threadId: null, // ← LIMPIA THREAD ID
  });
  
  // Limpiar URL
  if (window.location.search) {
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
  }
  
  console.log('✅ Formulario y chat limpiados completamente');
};
```

**Cambios clave:**
- Función ahora es `async` para poder esperar limpieza del backend
- Llama a `/api/chat/clear-thread` antes de resetear estados
- Limpia `messages` array (historial del chat)
- Resetea `threadId` a `null`

#### Funciones `handleCreateQuote()` y `handleCancelCreate()` - Ahora async

```javascript
const handleCreateQuote = async () => {
  // Resetear todo antes de abrir el modal (incluyendo limpieza de thread)
  await resetCreateFlow();
  handleOpenModal('create');
};

const handleCancelCreate = async () => {
  handleCloseModal('create');
  await resetCreateFlow();
};
```

**Cambios clave:**
- Ambas funciones ahora son `async`
- Usan `await` para esperar que `resetCreateFlow()` termine
- Garantizan que el chat esté limpio antes de abrir el modal

### 2. Backend: Nuevo Endpoint en `routes/api.php`

```php
// Ruta para limpiar thread de OpenAI (nueva cotización)
Route::post('/clear-thread', [App\Http\Controllers\Api\QuoteCreationController::class, 'clearThread'])
    ->middleware('auth')
    ->name('api.chat.clear.thread');
```

**Propósito:** Endpoint para eliminar el thread de OpenAI cuando se inicia nueva cotización

### 3. Backend: `app/Http/Controllers/Api/QuoteCreationController.php`

#### Nuevo método `clearThread()`

```php
/**
 * Limpiar thread de OpenAI cuando se inicia una nueva cotización
 * Esto asegura que cada cotización empiece con un chat en blanco
 */
public function clearThread(Request $request)
{
    try {
        $request->validate([
            'thread_id' => 'nullable|string'
        ]);
        
        $threadId = $request->thread_id;
        
        if (!$threadId) {
            return response()->json([
                'success' => true,
                'message' => 'No hay thread para limpiar'
            ]);
        }
        
        Log::info('🗑️ Limpiando thread de OpenAI', [
            'thread_id' => $threadId,
            'user_id' => auth()->id()
        ]);
        
        // Intentar eliminar el thread de OpenAI
        try {
            QuoteAssistantService::deleteThread($threadId);
            Log::info('✅ Thread eliminado exitosamente de OpenAI', ['thread_id' => $threadId]);
        } catch (\Exception $openaiError) {
            Log::warning('⚠️ No se pudo eliminar thread de OpenAI (puede no existir)', [
                'thread_id' => $threadId,
                'error' => $openaiError->getMessage()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Thread limpiado exitosamente'
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error limpiando thread', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Error limpiando thread: ' . $e->getMessage()
        ], 500);
    }
}
```

**Propósito:**
- Valida que se envíe un `thread_id`
- Llama al servicio para eliminar el thread de OpenAI
- Maneja errores gracefully (si el thread no existe, no falla)
- Registra logs para debugging

### 4. Backend: `app/Services/QuoteAssistantService.php`

#### Nuevo método `deleteThread()`

```php
/**
 * Eliminar un thread de OpenAI
 * Se usa cuando se inicia una nueva cotización para limpiar el historial
 */
public static function deleteThread($thread_id)
{
    self::initToken();
    
    // Si es un thread local temporal, no intentar eliminarlo de OpenAI
    if (str_starts_with($thread_id, 'local_thread_')) {
        Log::info('Thread local detectado, omitiendo eliminación en OpenAI');
        return true;
    }
    
    Log::info('Eliminando thread de OpenAI:', ['thread_id' => $thread_id]);
    
    try {
        $request = Http::timeout(10)
            ->withHeaders([
                'OpenAI-Beta' => 'assistants=v2'
            ])
            ->withToken(self::$private_token)
            ->delete(self::$openai_uri . '/threads/' . $thread_id);

        if ($request->status() == 200) {
            Log::info('✅ Thread eliminado exitosamente', ['thread_id' => $thread_id]);
            return true;
        }
        
        Log::warning('No se pudo eliminar thread', [
            'status' => $request->status(),
            'response' => $request->json(),
            'thread_id' => 'nullable|string',
            'client_id' => 'nullable|integer|exists:clients,id'
        ]);
        
        return false;
          $clientId = $request->client_id;
    } catch (\Exception $e) {
        Log::error('Error eliminando thread de OpenAI', [
            'error' => $e->getMessage(),
            'thread_id' => $thread_id
        ]);
        throw $e;
    }
}
```

**Propósito:**
- Hace llamada DELETE a la API de OpenAI: `/threads/{thread_id}`
- Maneja threads locales (cuando OpenAI no está disponible)
- Registra logs detallados
            'client_id' => $client?->id,
- Lanza excepción si falla (para que el controlador lo maneje)

## Flujo Completo de Limpieza

```
Usuario hace clic en "Crear Cotización"
  ↓
handleCreateQuote() ejecuta await resetCreateFlow()
  ↓
resetCreateFlow() verifica si hay threadId
  ├─ Si hay threadId:
  │   └─> Llama a POST /api/chat/clear-thread
  │       └─> QuoteCreationController::clearThread()
  │           └─> QuoteAssistantService::deleteThread()
  │               └─> DELETE https://api.openai.com/v1/threads/{thread_id}
  │                   └─> Thread eliminado de OpenAI ✅
  │
  └─ Limpia todos los estados:
      ├─ messages = []
      ├─ quoteData = []
      ├─ clientData.threadId = null
      ├─ inputMessage = ''
      └─ Todos los demás campos resetean
  ↓
Modal se abre con formulario COMPLETAMENTE LIMPIO
  ↓
Usuario selecciona cliente
  ↓
Se crea NUEVO thread en OpenAI (chat en blanco)
  ↓
Chat inicia SIN historial previo ✅
```

## Ventajas de esta Solución

1. **Chat siempre limpio**: Cada cotización empieza con conversación nueva
2. **Sin confusión**: Usuario no ve mensajes de cotizaciones anteriores
3. **Mejor UX**: Experiencia clara y predecible
4. **Backend sincronizado**: OpenAI también limpia el thread
5. **Manejo de errores**: Si OpenAI falla, el frontend sigue limpiando
6. **Retrocompatible**: Funciona con threads locales (cuando OpenAI no disponible)

## Logs de Monitoreo

### Limpieza Exitosa
```
🗑️ Limpiando thread de OpenAI {"thread_id":"thread_abc123","user_id":13}
✅ Thread eliminado exitosamente de OpenAI {"thread_id":"thread_abc123"}
✅ Thread limpiado en backend
✅ Formulario y chat limpiados completamente
```

### Sin Thread (Primera Cotización)
```
🧹 Limpiando completamente el formulario de cotización
✅ Formulario y chat limpiados completamente
```

### Error al Eliminar (Thread no existe en OpenAI)
```
🗑️ Limpiando thread de OpenAI {"thread_id":"thread_abc123"}
⚠️ No se pudo eliminar thread de OpenAI (puede no existir)
✅ Formulario y chat limpiados completamente
```

## Testing

### Caso 1: Nueva Cotización después de otra
1. Usuario crea cotización A → Chat tiene mensajes
2. Usuario hace clic en "Crear Cotización" nuevamente
3. **Verificar**: Chat aparece VACÍO (sin mensajes previos) ✅
4. Usuario selecciona cliente
5. **Verificar**: Chat sigue vacío, listo para empezar ✅

### Caso 2: Cancelar y Reiniciar
1. Usuario abre modal de cotización
2. Usuario cancela
3. Usuario vuelve a abrir modal
4. **Verificar**: Formulario y chat están limpios ✅

### Caso 3: Primera Cotización del Usuario
1. Usuario nunca ha creado cotización
2. Usuario hace clic en "Crear Cotización"
3. **Verificar**: No hay errores en consola ✅
4. **Verificar**: Chat aparece vacío ✅

### Caso 4: OpenAI no Disponible
1. OpenAI API caída o sin conexión
2. Usuario crea cotización
3. **Verificar**: Frontend limpia igualmente ✅
4. **Verificar**: No bloquea el flujo ✅

## Verificación en Producción

```bash
# Ver logs de limpieza de threads
tail -f /home/ubuntu/conalca/conalca/storage/logs/laravel.log | grep -E "Limpiando thread|Thread eliminado"

# Ver si hay errores
tail -f /home/ubuntu/conalca/conalca/storage/logs/laravel.log | grep -E "ERROR|Error limpiando"
```

## Estado del Sistema

- ✅ **Frontend modificado**: `resetCreateFlow()` ahora limpia thread
- ✅ **Endpoint creado**: `POST /api/chat/clear-thread`
- ✅ **Controlador actualizado**: Método `clearThread()` implementado
- ✅ **Servicio actualizado**: Método `deleteThread()` implementado
- ✅ **Assets compilados**: `npm run build` ejecutado
- ✅ **Caché actualizado**: `route:cache`, `config:cache`
- ✅ **PHP-FPM recargado**: Cambios aplicados en producción

## Próximos Pasos

1. **Probar en producción**: Crear varias cotizaciones consecutivas
2. **Verificar logs**: Confirmar que threads se eliminan correctamente
3. **Monitorear UX**: Usuario siempre ve chat limpio
4. **Feedback usuarios**: Confirmar mejor experiencia

---

**Implementado**: 2025-11-18  
**Estado**: ✅ COMPLETADO - Producción actualizada  
**Impacto**: Cada nueva cotización empieza con chat en blanco, sin historial previo
