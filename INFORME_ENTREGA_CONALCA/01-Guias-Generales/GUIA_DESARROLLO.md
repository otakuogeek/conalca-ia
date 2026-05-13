# 🚀 Guía de Desarrollo - Conalca IA

## 📋 Configuración de Entornos

Este proyecto está configurado para ejecutar **dos entornos simultáneamente**:

### 🟢 Entorno de DESARROLLO
- **Backend Laravel**: `http://localhost:8000`
- **Frontend Vite**: `http://localhost:5173`
- Usa archivo `.env.development`
- Hot Module Replacement (HMR) activo
- Logs en tiempo real

### 🔵 Entorno de PRODUCCIÓN
- **Backend Laravel**: Puerto configurado en `.env` (generalmente 80)
- **Frontend**: Assets compilados en `public/build`
- Usa archivo `.env`
- Optimizado y minificado

---

## 🛠️ Comandos Principales

### Iniciar Desarrollo
```bash
./scripts/start-dev.sh
```
Esto iniciará:
- Backend Laravel en puerto 8000
- Frontend Vite con HMR en puerto 5173
- Ambos procesos gestionados por PM2

### Detener Desarrollo
```bash
./scripts/stop-dev.sh
```

### Ver Logs de Desarrollo
```bash
# Ver todos los logs
pm2 logs

# Ver solo backend
pm2 logs conalca-dev-backend

# Ver solo frontend
pm2 logs conalca-dev-frontend
```

### Aplicar Cambios a Producción
```bash
npm run build
# o
npm run deploy
```
Esto compilará los assets y los colocará en `public/build`. Tu servidor de producción los usará automáticamente.

---

## ⚙️ Configuración Inicial

### 1. Configurar archivo .env.development

La primera vez que ejecutes `./scripts/start-dev.sh`, se creará automáticamente `.env.development` desde tu `.env`.

**Debes editar** `.env.development` y configurar:

```bash
# Puertos de desarrollo
DEV_PORT=8000
VITE_PORT=5173

# Nombre de la app
APP_NAME="Conalca IA (DEV)"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de datos (puedes usar la misma o una diferente)
DB_DATABASE=conalca_dev  # O usa la misma DB de producción
```

### 2. Instalar dependencias (si no lo has hecho)
```bash
npm install
composer install
```

---

## 🔄 Flujo de Trabajo Típico

### Desarrollo Diario

1. **Iniciar entorno de desarrollo**
   ```bash
   ./scripts/start-dev.sh
   ```

2. **Trabajar en tu código**
   - Edita archivos en `resources/js/`, `resources/css/`, etc.
   - Los cambios se reflejan automáticamente en `http://localhost:5173`
   - El HMR actualiza el navegador sin recargar

3. **Ver logs si hay errores**
   ```bash
   pm2 logs
   ```

4. **Al terminar, detener desarrollo**
   ```bash
   ./scripts/stop-dev.sh
   ```

### Desplegar a Producción

Cuando tus cambios estén listos:

```bash
npm run deploy
```

Esto compilará todo y tu servidor de producción usará los nuevos assets automáticamente.

---

## 📊 Gestión de Procesos PM2

### Ver todos los procesos
```bash
pm2 list
```

### Reiniciar un proceso
```bash
pm2 restart conalca-dev-backend
pm2 restart conalca-dev-frontend
```

### Detener procesos específicos
```bash
pm2 delete conalca-dev-backend
pm2 delete conalca-dev-frontend
```

---

## 🐛 Solución de Problemas

### El puerto 8000 o 5173 ya está en uso
Edita `.env.development` y cambia los puertos:
```bash
DEV_PORT=8001
VITE_PORT=5174
```

### Los cambios no se reflejan en desarrollo
1. Verifica que estés accediendo a `http://localhost:5173` (no al puerto de producción)
2. Revisa los logs: `pm2 logs conalca-dev-frontend`
3. Reinicia el frontend: `pm2 restart conalca-dev-frontend`

### Error al iniciar PM2
```bash
pm2 kill
./scripts/start-dev.sh
```

### Limpiar cache de Vite
```bash
rm -rf node_modules/.vite
npm run dev
```

---

## 📝 Notas Importantes

- ✅ **Producción NO se ve afectada** cuando trabajas en desarrollo
- ✅ Puedes tener ambos entornos corriendo simultáneamente
- ✅ Los puertos son diferentes para evitar conflictos
- ✅ Cada entorno usa su propio archivo `.env`
- ✅ PM2 gestiona todos los procesos de forma eficiente

---

## 🎯 Resumen Rápido

| Acción | Comando |
|--------|---------|
| Iniciar desarrollo | `./scripts/start-dev.sh` |
| Detener desarrollo | `./scripts/stop-dev.sh` |
| Ver logs | `pm2 logs` |
| Compilar para producción | `npm run build` |
| Desplegar a producción | `npm run deploy` |
| Ver procesos | `pm2 list` |
