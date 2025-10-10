# 🐛 DIAGNÓSTICO - CHAT NO MUESTRA MENSAJES

## 📋 PROBLEMA REPORTADO

**Usuario:** DonDavidNavarro@conalca.com  
**Síntoma:** Al escribir en el chat y presionar enviar, no se muestra ningún mensaje ni respuesta de la IA.

## 🔍 ANÁLISIS TÉCNICO

### 1. Estado del HTML
El HTML del chat está renderizado correctamente:
```html
<textarea 
    id="chat-textarea"
    wire:model="input_message" 
    wire:keydown.enter.prevent="sendMessage"
    placeholder="Describe tu envío: origen, destino, peso, tipo de carga..."
    class="w-full px-4 py-3 pr-20 text-gray-800 placeholder-gray-400 bg-gray-50 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all duration-200 product-sans"
    rows="3">
</textarea>

<button type="button" 
    wire:click="sendMessage"
    class="w-8 h-8 flex items-center justify-center rounded-full bg-orange-400 hover:bg-orange-500 transition-all duration-200 shadow-md"
    title="Enviar mensaje">
```

### 2. Wire Bindings Correctos
- ✅ `wire:model="input_message"` - Vincula el textarea con la propiedad de Livewire
- ✅ `wire:keydown.enter.prevent="sendMessage"` - Envía con Enter
- ✅ `wire:click="sendMessage"` - Envía con clic en botón

### 3. Componente Livewire
- ✅ Archivo: `app/Livewire/QuoteIndex.php`
- ✅ Método: `public function sendMessage()` existe
- ✅ Propiedad: `$input_message` debe existir

## 🚨 POSIBLES CAUSAS

### Causa #1: Livewire No Está Cargando Correctamente
**Síntomas:**
- El textarea acepta texto pero no lo envía
- No hay actualización visual al hacer clic
- No hay errores 419 en consola (si hubiera, sería problema de CSRF)

**Verificación:**
```javascript
// En consola del navegador (F12):
typeof Livewire
// Debe retornar: "object"

Livewire.components.componentsById
// Debe mostrar objetos con IDs de componentes
```

### Causa #2: Wire:model No Está Funcionando
**Síntomas:**
- El texto escrito no se refleja en la propiedad de Livewire
- Livewire no detecta cambios en el input

**Verificación:**
```javascript
// En consola del navegador (F12):
// Encontrar el componente Livewire del chat
Livewire.find('component-id')
```

### Causa #3: El Área de Conversación No Se Actualiza
**Síntomas:**
- El mensaje se envía pero no aparece en el área de conversación
- El wire:poll no está funcionando

**Código relevante:**
```html
<div wire:poll.5s.visible="" id="conversation" 
     class="flex-1 p-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 bg-gray-50" 
     style="max-height: 500px;">
```

El `wire:poll.5s.visible=""` significa que debería actualizarse cada 5 segundos cuando esté visible.

## ✅ SOLUCIÓN PASO A PASO

### Paso 1: Verificar Error 404 de CSS (YA RESUELTO)
✅ Ya corregido en iteración anterior
- Layout corregido
- CSS cargando correctamente desde `/build/assets/app-8ef293d1.css`

### Paso 2: Verificar Livewire en Consola

Abre la consola del navegador (F12) y ejecuta:

```javascript
// 1. Verificar que Livewire esté cargado
console.log('Livewire loaded:', typeof Livewire !== 'undefined');

// 2. Verificar componentes activos
if (typeof Livewire !== 'undefined') {
    console.log('Components:', Object.keys(Livewire.components.componentsById));
}

// 3. Encontrar el componente del chat
const chatTextarea = document.getElementById('chat-textarea');
if (chatTextarea) {
    const component = chatTextarea.closest('[wire\\:id]');
    if (component) {
        const componentId = component.getAttribute('wire:id');
        console.log('Chat Component ID:', componentId);
        console.log('Component Data:', Livewire.find(componentId)?.data);
    }
}
```

### Paso 3: Verificar Errores de Red

En la consola del navegador (F12), pestaña Network:

1. Filtrar por "livewire"
2. Escribir un mensaje y enviar
3. Verificar si aparece una petición POST a `/livewire/update`
4. Ver el código de respuesta:
   - **200**: OK, pero puede que no actualice la vista
   - **419**: Error de CSRF Token
   - **422**: Error de validación
   - **500**: Error del servidor

### Paso 4: Verificar en Logs del Servidor

```bash
tail -f /home/ubuntu/mcp/conalca/storage/logs/laravel.log | grep -E "(sendMessage|QuoteIndex|Livewire)"
```

Luego intenta enviar un mensaje y observa si aparecen errores.

## 🔧 CORRECCIONES A APLICAR

### Corrección #1: Verificar Propiedad $input_message

Archivo: `app/Livewire/QuoteIndex.php`

Buscar:
```php
public $input_message;
```

Si NO existe, agregar después de la línea `class QuoteIndex extends Component`:

```php
class QuoteIndex extends Component
{
    public $input_message = '';
    public $messages = [];
    
    // ... resto del código
}
```

### Corrección #2: Verificar Método sendMessage()

El método debe:
1. Recibir el mensaje de `$this->input_message`
2. Guardarlo en la base de datos o array
3. Enviarlo a OpenAI
4. Limpiar el input: `$this->input_message = '';`
5. Disparar un evento de actualización

### Corrección #3: Agregar Debugging Temporal

Agregar al inicio del método `sendMessage()`:

```php
public function sendMessage()
{
    \Log::info('🔵 sendMessage llamado', [
        'input_message' => $this->input_message,
        'messages_count' => count($this->messages ?? [])
    ]);
    
    if (empty($this->input_message)) {
        \Log::warning('⚠️ input_message está vacío');
        return;
    }
    
    // ... resto del método
}
```

## 📊 VERIFICACIÓN DE ÉXITO

Después de aplicar correcciones:

1. ✅ Escribir mensaje en el textarea
2. ✅ Presionar Enter o clic en botón de enviar
3. ✅ Ver el mensaje aparecer en el área de conversación
4. ✅ Ver la respuesta de la IA aparecer después
5. ✅ El textarea se limpia automáticamente

## 🐛 SI AÚN NO FUNCIONA

### Debug Avanzado

Agregar wire:offline para debug:

```html
<div wire:offline class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded">
    Sin conexión a internet
</div>

<div wire:loading class="fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded">
    Enviando mensaje...
</div>
```

### Revisar JavaScript

Buscar errores de JavaScript que puedan estar interfiriendo:

```javascript
// En consola del navegador:
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.message, e.filename, e.lineno);
});
```

## 📝 COMANDOS ÚTILES

```bash
# Ver logs en tiempo real
tail -f /home/ubuntu/mcp/conalca/storage/logs/laravel.log

# Limpiar cachés
cd /home/ubuntu/mcp/conalca
php artisan optimize:clear

# Verificar que Livewire esté instalado
php artisan livewire:list | grep QuoteIndex
```

## 🎯 PRÓXIMOS PASOS

1. **Hacer hard refresh del navegador** (Ctrl+Shift+R)
2. **Abrir consola del navegador** (F12)
3. **Ir a /quotes**
4. **Hacer clic en "Responder" de una cotización**
5. **Escribir un mensaje de prueba**
6. **Presionar Enter o clic en enviar**
7. **Observar consola y Network**
8. **Compartir capturas de pantalla si persiste**

---

**Fecha:** Octubre 6, 2025  
**Estado:** Diagnóstico completo  
**Próxima acción:** Verificación en navegador del usuario
