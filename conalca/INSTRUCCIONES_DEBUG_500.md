# 🔍 Debug Error 500 en buscar-conductores

## ✅ Verificaciones realizadas:

1. **Endpoint funciona correctamente** cuando se prueba directamente con PHP
2. **Configuración Sanctum** correcta en `.env`
3. **Logs detallados** agregados al controlador

## 📋 Pasos para identificar el error:

### 1. Limpieza realizada:
```bash
php artisan config:clear
php artisan cache:clear
> storage/logs/laravel.log  # Log limpiado
```

### 2. Ahora intenta buscar conductores desde el navegador

### 3. Inmediatamente después, ejecuta:
```bash
cd /home/ubuntu/conalca/conalca
cat storage/logs/laravel.log
```

## 🔍 Qué buscar en los logs:

- `🔍 ArcangelDriversController::buscarConductores - Inicio` - Verifica si llega la petición
- `user_authenticated: false` - Confirma problema de autenticación
- `❌ ArcangelDriversController: Error` - Muestra el error exacto con línea y archivo

## 🎯 Posibles causas del error 500:

### Causa 1: Usuario no autenticado (más probable)
**Síntoma:** No aparece log `🔍 Inicio` porque Sanctum bloquea antes
**Solución:** Verificar que el usuario esté logueado en Laravel antes de acceder a la página

### Causa 2: Error en el código del controlador
**Síntoma:** Aparece log `🔍 Inicio` pero luego `❌ Error`
**Solución:** El log mostrará línea exacta del error

### Causa 3: Error en ArcangelService
**Síntoma:** Log muestra error en `getVehiculosFiltrados`
**Solución:** Verificar conectividad con API de Arcángel

## 🔧 Solución rápida si es problema de autenticación:

### Opción A: Remover autenticación de este endpoint (temporal)
```php
// En routes/api.php, mover la ruta FUERA del grupo auth:sanctum
Route::post('/arcangel/buscar-conductores', [App\Http\Controllers\Api\ArcangelDriversController::class, 'buscarConductores'])
    ->name('api.arcangel.buscar.conductores');
```

### Opción B: Inicializar sesión CSRF antes de llamar
```javascript
// En CallPanel.jsx, antes de buscarConductores:
await axios.get('/sanctum/csrf-cookie');
const response = await buscarConductores(cotizacion.id, 7, 50);
```

## 📊 Estado actual:
- ✅ Backend funciona correctamente (probado con script PHP)
- ✅ Logs detallados activados
- ⏳ Esperando reproducir error desde frontend para ver logs exactos
