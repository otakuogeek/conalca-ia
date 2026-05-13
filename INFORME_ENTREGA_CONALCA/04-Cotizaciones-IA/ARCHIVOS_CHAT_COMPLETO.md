# 📋 LISTA COMPLETA DE ARCHIVOS QUE AFECTAN EL CHAT

Este documento lista **TODOS** los archivos que intervienen en el funcionamiento del chat de cotizaciones.

---

## 🎯 ARCHIVOS PRINCIPALES (Críticos)

### 1. **Backend - Componente Livewire**
```
📁 app/Livewire/QuoteIndex.php
```
**Función**: Componente principal del chat
**Líneas clave modificadas**: 
- Líneas 188-215: `mount()` - Inicialización del thread
- Líneas 1273-1320: `sendMessage()` - Envío de mensajes
- Líneas 1162-1194: `submitClient()` - Búsqueda de cliente
- Líneas 1242-1270: `syncQuoteData()` - Sincronización de datos

**Propiedades importantes**:
- Línea 58: `public $messages = [];`
- Línea 62: `public $openai_thread = "";`
- Línea 64: `public $openai_current_run = null;`
- Línea 66: `public $input_message = "";`

**Métodos que usan el chat**:
- `mount()` - Inicializa thread al cargar
- `submitClient()` - Inicializa thread al buscar cliente
- `sendMessage()` - Envía mensaje del usuario
- `syncQuoteData()` - Obtiene mensajes del thread

---

### 2. **Backend - Servicio OpenAI**
```
📁 app/Services/QuoteAssistantService.php
```
**Función**: Gestiona toda la comunicación con OpenAI API
**Métodos principales**:
- `getThread($client)` - Obtiene o crea thread
- `createMessage($thread, $content)` - Crea mensaje en thread
- `getMessages($thread)` - Obtiene mensajes del thread
- `runAssistant($thread, $type_business)` - Ejecuta asistente IA
- `checkRunStatus($thread, $run_id)` - Verifica estado de ejecución

**Dependencias**:
- OpenAI PHP Client (`openai-php/client`)
- Variables de entorno: `OPENAI_API_KEY`, `OPENAI_ASSISTANT_*`

---

### 3. **Frontend - Vista Blade**
```
📁 resources/views/livewire/quote-index.blade.php
```
**Función**: Interfaz visual del chat
**Líneas clave**:
- Línea 890: `<div wire:poll.5s.visible id="conversation">` - Contenedor principal
- Línea 893: `@foreach ($messages as $message)` - Renderizado de mensajes
- Línea 913: `@if(empty($messages))` - Mensaje de bienvenida
- Línea 938: `<textarea wire:model="input_message">` - Campo de entrada
- Línea 939: `wire:keydown.enter.prevent="sendMessage"` - Envío con Enter
- Línea 967: `<button wire:click="sendMessage">` - Botón de envío

**Wire Directives usados**:
- `wire:poll.5s.visible` - Auto-refresco cada 5 segundos
- `wire:model="input_message"` - Binding bidireccional
- `wire:click="sendMessage"` - Ejecuta método en backend
- `wire:keydown.enter.prevent` - Captura tecla Enter

---

## 📦 ARCHIVOS DE SOPORTE

### 4. **Modelo de Cliente**
```
📁 app/Models/Client.php
```
**Función**: Modelo Eloquent del cliente
**Campos relacionados con chat**:
- `openai_thread_id` - ID del thread de OpenAI
- `openai_current_run` - ID del run actual (nullable)

---

### 5. **Modelo de Contacto** (Alternativo)
```
📁 app/Models/Contact.php
```
**Función**: Modelo Eloquent de contacto
**Campos relacionados**:
- `openai_thread_id` - ID del thread de OpenAI

---

### 6. **Migraciones de Base de Datos**
```
📁 database/migrations/2024_02_16_104652_create_clients_table.php
```
**Función**: Define estructura de tabla `clients`
**Campo**: `$table->string('openai_thread_id')->nullable();`

```
📁 database/migrations/2024_02_16_104660_create_contacts_table.php
```
**Función**: Define estructura de tabla `contacts`
**Campo**: `$table->string('openai_thread_id')->nullable();`

---

### 7. **Layout Principal**
```
📁 resources/views/layout/app.blade.php
```
**Función**: Layout base de la aplicación
**Elementos que afectan el chat**:
- Línea 9: `@vite('resources/css/app.css')` - Estilos CSS
- Línea 25: `@vite('resources/js/app.jsx')` - JavaScript React
- Scripts de Livewire y Alpine.js

---

### 8. **Vista Principal de Quotes**
```
📁 resources/views/quotes/show.blade.php
```
**Función**: Vista que carga el componente Livewire
**Contenido**:
```blade
@extends('layout.app')
@section('content')
    @livewire('quote-index', ['search' => request('search')])
@endsection
```

---

## 🔧 ARCHIVOS DE CONFIGURACIÓN

### 9. **Variables de Entorno**
```
📁 .env
```
**Variables críticas para el chat**:
```bash
OPENAI_API_KEY=sk-proj-...
OPENAI_ASSISTANT_DTA=asst_...
OPENAI_ASSISTANT_DTI=asst_...
OPENAI_ASSISTANT_ADUANAS=asst_...
```

---

### 10. **Configuración de Servicios**
```
📁 config/services.php
```
**Función**: Configuración de servicios externos
**Sección OpenAI**:
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'assistant' => [
        'dta' => env('OPENAI_ASSISTANT_DTA'),
        'dti' => env('OPENAI_ASSISTANT_DTI'),
        'aduanas' => env('OPENAI_ASSISTANT_ADUANAS'),
    ]
]
```

---

### 11. **Configuración de Livewire**
```
📁 config/livewire.php
```
**Función**: Configuración del framework Livewire
**Importante**:
- `'inject_assets' => true`
- `'manifest_path' => null`

---

## 🎨 ARCHIVOS DE ESTILOS

### 12. **CSS Principal**
```
📁 resources/css/app.css
```
**Función**: Estilos principales de la aplicación
**Clases usadas en el chat**:
- `.chat-message` - Estilo de mensajes
- `.scrollbar-thin` - Scrollbar personalizada
- `.animate-fade-in` - Animación de entrada

---

### 13. **CSS Compilado (Vite)**
```
📁 public/build/assets/app-8ef293d1.css
```
**Función**: CSS compilado por Vite
**Nota**: Este archivo cambia cada vez que ejecutas `npm run build`

---

## 📜 ARCHIVOS JAVASCRIPT

### 14. **JavaScript Principal**
```
📁 resources/js/app.jsx
```
**Función**: JavaScript/React principal
**Posible contenido**:
- Inicialización de Alpine.js
- Configuración de eventos Livewire
- Componentes React

---

### 15. **Livewire JavaScript (Vendor)**
```
📁 public/vendor/livewire/livewire.js
```
**Función**: Framework JavaScript de Livewire
**Generado por**: `php artisan livewire:publish --assets`

---

## 🗂️ ARCHIVOS DE CACHE (Generados automáticamente)

### 16. **Vista Compilada de Blade**
```
📁 storage/framework/views/0369e2bdeddb3983ffa85b4ced233f6e.php
```
**Función**: Cache PHP de quote-index.blade.php
**Nota**: Se regenera automáticamente cuando modificas la vista

---

### 17. **Rutas en Cache**
```
📁 bootstrap/cache/routes-v7.php
```
**Función**: Cache de rutas de Laravel
**Limpiar con**: `php artisan route:clear`

---

## 🛣️ ARCHIVOS DE RUTAS

### 18. **Rutas Web**
```
📁 routes/web.php
```
**Función**: Define las rutas de la aplicación
**Ruta del chat**:
```php
Route::get('/quotes', function () {
    $quotes = CotizacionModel::get();
    return view('quotes.show', compact('quotes'));
})->name('quotes.show');
```

---

## 📊 RESUMEN POR CATEGORÍA

### ✅ **Archivos que MODIFIQUÉ** (con esta corrección)

1. ✏️ `app/Livewire/QuoteIndex.php` - Agregada inicialización en `mount()`

### 🔴 **Archivos CRÍTICOS** (Si fallan, el chat no funciona)

1. 🔴 `app/Livewire/QuoteIndex.php`
2. 🔴 `app/Services/QuoteAssistantService.php`
3. 🔴 `resources/views/livewire/quote-index.blade.php`
4. 🔴 `.env` (Variables de OpenAI)

### 🟡 **Archivos IMPORTANTES** (Afectan funcionalidad)

1. 🟡 `app/Models/Client.php`
2. 🟡 `resources/views/layout/app.blade.php`
3. 🟡 `resources/views/quotes/show.blade.php`
4. 🟡 `public/vendor/livewire/livewire.js`

### 🟢 **Archivos SECUNDARIOS** (Estilos, configuración)

1. 🟢 `resources/css/app.css`
2. 🟢 `resources/js/app.jsx`
3. 🟢 `config/livewire.php`
4. 🟢 `config/services.php`

---

## 📤 INSTRUCCIONES PARA ENVIARTE ARCHIVOS

### Opción 1: Archivo por archivo

Para cada archivo que quieras revisar, ejecuta:

```bash
# Ver contenido completo
cat /ruta/al/archivo

# Ver con números de línea
cat -n /ruta/al/archivo

# Ver secciones específicas (líneas 100-200)
sed -n '100,200p' /ruta/al/archivo
```

### Opción 2: Crear backup de todos los archivos del chat

```bash
cd /home/ubuntu/mcp/conalca
mkdir -p backup_chat_antiguo

# Copiar archivos principales
cp app/Livewire/QuoteIndex.php backup_chat_antiguo/
cp app/Services/QuoteAssistantService.php backup_chat_antiguo/
cp resources/views/livewire/quote-index.blade.php backup_chat_antiguo/
cp app/Models/Client.php backup_chat_antiguo/
cp .env backup_chat_antiguo/env.txt

# Crear archivo comprimido
tar -czf backup_chat_$(date +%Y%m%d_%H%M%S).tar.gz backup_chat_antiguo/

echo "✅ Backup creado"
ls -lh backup_chat_*.tar.gz
```

### Opción 3: Ver diferencias con Git (si usas control de versiones)

```bash
# Ver archivos modificados
git status

# Ver diferencias del archivo principal
git diff app/Livewire/QuoteIndex.php

# Ver diferencias de todos los archivos
git diff
```

---

## 🔍 COMANDOS ÚTILES PARA DIAGNOSTICAR

### Ver si el archivo fue modificado recientemente
```bash
ls -lh app/Livewire/QuoteIndex.php
```

### Ver últimas líneas del log
```bash
tail -n 50 storage/logs/laravel.log
```

### Buscar en el código
```bash
# Buscar "sendMessage" en todos los archivos PHP
grep -r "sendMessage" app/ --include="*.php"

# Buscar "openai_thread" en todos los archivos
grep -r "openai_thread" . --include="*.php" --include="*.blade.php"
```

### Ver hash del archivo actual
```bash
md5sum app/Livewire/QuoteIndex.php
```

---

## 📝 NOTAS IMPORTANTES

1. **Archivos en `storage/framework/views/`**: Son CACHE, se regeneran automáticamente. NO los modifiques directamente.

2. **Archivos en `public/build/`**: Son generados por Vite. Se actualizan con `npm run build`.

3. **Archivo `.env`**: NO debe estar en Git por seguridad. Ten copia de respaldo.

4. **Variables de entorno**: Si cambias `.env`, reinicia PHP-FPM:
   ```bash
   sudo systemctl restart php8.3-fpm
   ```

5. **Después de modificar archivos Blade**: Limpia cache de vistas:
   ```bash
   php artisan view:clear
   ```

---

## ✅ CONCLUSIÓN

**Total de archivos que afectan el chat**: ~18 archivos

**Archivos críticos a revisar si hay problemas**:
1. `app/Livewire/QuoteIndex.php` ⭐
2. `app/Services/QuoteAssistantService.php` ⭐
3. `resources/views/livewire/quote-index.blade.php` ⭐
4. `.env` ⭐

**Si necesitas restaurar versión antigua**:
Dime cuál archivo específico quieres revisar y te ayudo a comparar versiones.

---

**Fecha de generación**: 2025-10-07  
**Sistema**: Conalca IA - Módulo de Cotizaciones  
**Estado**: ✅ Documentación completa
