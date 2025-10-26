# 📘 Documentación - Integración API Arcangel

## 🎯 Descripción General

Integración completa de la API de Arcangel en el backend Laravel de Conalca IA, con soporte para:
- ✅ Modo producción y desarrollo (configurable desde `.env`)
- ✅ API Keys separadas por entorno
- ✅ Sistema de caché inteligente
- ✅ Manejo robusto de errores
- ✅ Reintentos automáticos
- ✅ Logging detallado

---

## ⚙️ Configuración

### 1. Variables de entorno (.env)

Añade estas variables a tu archivo `.env`:

```env
# ============================================================================
# ARCANGEL - INTEGRACIÓN API
# ============================================================================
ARCANGEL_MODE=production                                    # o "development"
ARCANGEL_BASE_URL=https://arcangel.conalca.com.co/api/
ARCANGEL_BASE_URL_DEV=https://dev.arcangel.conalca.com.co/api/
ARCANGEL_API_KEY=344c9ff2734cc79a7f2ededce7f9dc255fc18f0b
ARCANGEL_API_KEY_DEV=13b068d6527b4b3d7f16627198d5fcdce330d20e
```

### 2. Cambiar entre producción y desarrollo

```bash
# En .env, cambia:
ARCANGEL_MODE=development  # Para desarrollo
ARCANGEL_MODE=production   # Para producción

# Luego limpia la caché:
php artisan config:clear
```

---

## 📦 Archivos Creados

```
conalca/
├── config/
│   └── arcangel.php                    # Configuración de Arcangel
├── app/
│   ├── Services/
│   │   └── ArcangelService.php         # Servicio principal
│   ├── Http/Controllers/Api/
│   │   └── ArcangelController.php      # Controlador API
│   └── Examples/
│       └── ArcangelExamples.php        # Ejemplos de uso
├── routes/
│   └── api.php                         # Rutas API (actualizado)
└── test_arcangel_api.php               # Script de prueba
```

---

## 🚀 Uso del Servicio

### Opción 1: Inyección de dependencias (Recomendado)

```php
use App\Services\ArcangelService;

class MiController extends Controller
{
    protected ArcangelService $arcangel;

    public function __construct(ArcangelService $arcangel)
    {
        $this->arcangel = $arcangel;
    }

    public function consultar()
    {
        $data = $this->arcangel->get('endpoint');
        return response()->json($data);
    }
}
```

### Opción 2: Usando app()

```php
$arcangel = app(ArcangelService::class);
$data = $arcangel->get('clientes');
```

---

## 📋 Métodos Disponibles

### 🔹 GET - Consultar datos

```php
// Consulta simple
$clientes = $arcangel->get('clientes');

// Con parámetros
$clientes = $arcangel->get('clientes', [
    'ciudad' => 'Bogotá',
    'estado' => 'activo'
]);

// Con caché (60 minutos)
$clientes = $arcangel->get('clientes', [], true, 60);
```

### 🔹 POST - Crear/Enviar datos

```php
$nuevoCliente = $arcangel->post('clientes', [
    'nombre' => 'Cliente Ejemplo',
    'documento' => '123456789',
    'telefono' => '3001234567'
]);
```

### 🔹 PUT - Actualizar datos

```php
$actualizado = $arcangel->put('clientes/123', [
    'telefono' => '3009876543'
]);
```

### 🔹 DELETE - Eliminar datos

```php
$resultado = $arcangel->delete('clientes/123');
```

### 🔹 Health Check

```php
$isHealthy = $arcangel->healthCheck();
if ($isHealthy) {
    echo "API disponible";
}
```

### 🔹 Obtener información

```php
$info = $arcangel->getInfo();
// Retorna: ['mode', 'base_url', 'timeout', 'retry_times']
```

### 🔹 Limpiar caché

```php
// Caché de un endpoint específico
$arcangel->clearCache('clientes', ['ciudad' => 'Bogotá']);

// Toda la caché
$arcangel->clearAllCache();
```

---

## 🌐 Rutas API REST

### Health Check (sin autenticación)
```http
GET /api/arcangel/health
```

**Respuesta:**
```json
{
  "success": true,
  "message": "API Arcangel disponible",
  "info": {
    "mode": "production",
    "base_url": "https://arcangel.conalca.com.co/api/",
    "timeout": 30,
    "retry_times": 3
  }
}
```

### Consultar (con autenticación)
```http
GET /api/arcangel/consultar
```

**Body:**
```json
{
  "endpoint": "clientes",
  "params": {
    "ciudad": "Bogotá"
  },
  "use_cache": true
}
```

### Crear (con autenticación)
```http
POST /api/arcangel/crear
```

**Body:**
```json
{
  "endpoint": "clientes",
  "data": {
    "nombre": "Cliente Nuevo",
    "documento": "123456789"
  }
}
```

### Actualizar (con autenticación)
```http
PUT /api/arcangel/actualizar
```

**Body:**
```json
{
  "endpoint": "clientes/123",
  "data": {
    "telefono": "3001234567"
  }
}
```

### Eliminar (con autenticación)
```http
DELETE /api/arcangel/eliminar
```

**Body:**
```json
{
  "endpoint": "clientes/123"
}
```

### Limpiar caché (con autenticación)
```http
POST /api/arcangel/clear-cache
```

**Body (específico):**
```json
{
  "endpoint": "clientes",
  "params": {"ciudad": "Bogotá"}
}
```

**Body (todo):**
```json
{}
```

---

## 🧪 Pruebas

### Ejecutar script de prueba

```bash
cd /home/ubuntu/mcp/conalca
php test_arcangel_api.php
```

### Probar Health Check con curl

```bash
# Producción
curl https://conalcaia.conalca.com.co/api/arcangel/health

# Desarrollo
curl https://conalcaia.conalca.com.co/api/arcangel/health
```

### Probar desde frontend (JavaScript)

```javascript
// Health Check
fetch('/api/arcangel/health')
  .then(res => res.json())
  .then(data => console.log(data));

// Consultar (requiere autenticación)
fetch('/api/arcangel/consultar', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: JSON.stringify({
    endpoint: 'clientes',
    use_cache: true
  })
})
  .then(res => res.json())
  .then(data => console.log(data));
```

---

## 🔒 Seguridad

### Headers enviados automáticamente

```php
[
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer {API_KEY}'
]
```

### Protección de rutas

- ✅ `/api/arcangel/health` - Público (health check)
- 🔐 Todas las demás rutas requieren autenticación con `auth:sanctum`

---

## 📊 Logging

Todos los eventos se registran automáticamente en `storage/logs/laravel.log`:

```
[2025-10-23 10:30:00] local.INFO: ArcangelService initialized {"mode":"production","base_url":"https://arcangel.conalca.com.co/api/"}
[2025-10-23 10:30:05] local.INFO: ArcangelService: Request successful {"endpoint":"clientes","status":200}
[2025-10-23 10:30:10] local.ERROR: ArcangelService GET error {"endpoint":"productos","error":"Endpoint not found"}
```

---

## ⚡ Características Avanzadas

### 1. Caché inteligente

```php
// Cache por 30 minutos
$data = $arcangel->get('productos', [], true, 30);

// Cache por 2 horas
$data = $arcangel->get('categorias', [], true, 120);
```

### 2. Reintentos automáticos

El servicio reintenta automáticamente 3 veces con 100ms de delay entre intentos en caso de fallos temporales.

### 3. Timeout configurable

```env
ARCANGEL_TIMEOUT=30  # 30 segundos (por defecto)
```

### 4. Manejo de errores

```php
try {
    $data = $arcangel->get('endpoint-invalido');
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'No autorizado')) {
        // Error de autenticación
    } elseif (str_contains($e->getMessage(), 'no encontrado')) {
        // Endpoint no existe
    } else {
        // Otro error
    }
}
```

---

## 🔧 Configuración Avanzada

### Personalizar reintentos y delay

```env
ARCANGEL_RETRY_TIMES=5      # Número de reintentos
ARCANGEL_RETRY_DELAY=200    # Delay en milisegundos
```

### Múltiples entornos

```env
# Desarrollo
ARCANGEL_MODE=development

# Staging
ARCANGEL_MODE=staging
ARCANGEL_BASE_URL_STAGING=https://staging.arcangel.conalca.com.co/api/
ARCANGEL_API_KEY_STAGING=tu_api_key_staging
```

---

## 📝 Ejemplo Completo

```php
<?php

namespace App\Http\Controllers;

use App\Services\ArcangelService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    protected ArcangelService $arcangel;

    public function __construct(ArcangelService $arcangel)
    {
        $this->arcangel = $arcangel;
    }

    public function index()
    {
        try {
            // Consultar clientes con caché de 1 hora
            $clientes = $this->arcangel->get('clientes', [], true, 60);
            
            return view('clientes.index', compact('clientes'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string',
                'documento' => 'required|string',
            ]);

            $cliente = $this->arcangel->post('clientes', $validated);
            
            // Limpiar caché después de crear
            $this->arcangel->clearCache('clientes');
            
            return redirect()->route('clientes.index')
                ->with('success', 'Cliente creado');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
```

---

## 🐛 Troubleshooting

### Error: "No autorizado"
- Verifica que `ARCANGEL_API_KEY` esté configurada correctamente
- Asegúrate de estar en el modo correcto (production/development)

### Error: "Endpoint no encontrado"
- Verifica que el endpoint existe en la API de Arcangel
- Revisa la documentación de Arcangel para endpoints válidos

### Error: "Timeout"
- Aumenta `ARCANGEL_TIMEOUT` en `.env`
- Verifica la conectividad de red

### Caché no se limpia
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 📞 Soporte

Para más información sobre los endpoints disponibles, consulta el manual de la API de Arcangel:
- 📄 `Manual de uso API conexión ARCANGEL - CONALCA IA v1.0.pdf`

---

**✨ Integración completada exitosamente ✨**
