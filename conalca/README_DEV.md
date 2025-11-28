# 🚀 Guía Rápida - Modo Desarrollo (Aislado)

## ✅ Arquitectura del Sistema

Para permitir que Producción y Desarrollo funcionen simultáneamente sin conflictos, hemos implementado una arquitectura aislada:

### 1. Producción (Estable)
- **URL**: `https://conalcaia.conalca.com.co`
- **Backend**: Nginx + PHP-FPM (Puerto 443)
- **Frontend**: Assets compilados en `public/build`
- **Configuración**: Usa `.env` principal
- **Estado**: Siempre activo, NO se ve afectado por desarrollo.

### 2. Desarrollo (Dinámico)
- **URL Backend**: `http://localhost:8000`
- **URL Frontend**: `http://localhost:5173`
- **Backend**: `php artisan serve` (Puerto 8000)
- **Frontend**: Vite Dev Server (Puerto 5173)
- **Configuración**: Usa `.env.development` y `public/hot-dev`
- **Estado**: Se inicia/detiene bajo demanda.

---

## 🎯 Uso Diario

### Iniciar Desarrollo
```bash
./scripts/start-dev.sh
```
Esto iniciará tanto el backend de desarrollo como el servidor Vite.

### Acceder a Desarrollo
Debes usar el puerto **8000** para ver la aplicación en modo desarrollo:
👉 **http://localhost:8000**

(Vite en el puerto 5173 sirve los assets, pero la aplicación corre en el 8000).

### Detener Desarrollo
```bash
./scripts/stop-dev.sh
```

### Ver Logs
```bash
pm2 logs conalca-dev-backend
pm2 logs conalca-dev-frontend
```

### Aplicar Cambios a Producción
```bash
npm run build
```
¡Listo! Los cambios están en producción.

---

## 💡 Cómo Funciona el Aislamiento

1.  **Vite** está configurado para escribir un archivo especial `public/hot-dev` en lugar del estándar `public/hot`.
2.  **Laravel** está configurado (`AppServiceProvider`) para buscar `hot-dev` **SOLO** cuando está en entorno `local` o `development`.
3.  **Producción** (entorno `production`) ignora `hot-dev` y sigue usando los assets compilados.
4.  **Desarrollo** (entorno `local`) encuentra `hot-dev` y carga los assets desde Vite (puerto 5173).

Esto garantiza que **NUNCA** se rompa producción al iniciar desarrollo.
