# ✅ Configuración de Entorno de Desarrollo - COMPLETADA

## 📦 Archivos Creados

### Configuración
- **`.env.development`** - Variables de entorno para desarrollo
  - Puertos: 8000 (backend), 5173 (frontend)
  - Configuración separada de producción

### Scripts de Gestión
- **`scripts/start-dev.sh`** - Inicia el entorno de desarrollo
- **`scripts/stop-dev.sh`** - Detiene el entorno de desarrollo
- **`scripts/help.sh`** - Muestra ayuda rápida de comandos

### Documentación
- **`README_DEV.md`** - Guía rápida de inicio
- **`GUIA_DESARROLLO.md`** - Documentación completa

### Archivos Modificados
- **`vite.config.js`** - Soporte para puerto personalizable
- **`package.json`** - Añadido script `deploy`

---

## 🎯 Próximos Pasos

### 1. Configurar Credenciales (IMPORTANTE)
Edita `.env.development` y copia las credenciales de tu `.env` principal:

```bash
nano .env.development
```

Busca en tu `.env` principal y copia:
- `ARCANGEL_API_URL`
- `ARCANGEL_API_KEY`
- `ELEVENLABS_API_KEY`
- Cualquier otra credencial de API que uses

### 2. Iniciar Desarrollo
```bash
./scripts/start-dev.sh
```

### 3. Acceder a la Aplicación
Abre en tu navegador: **http://localhost:5173**

---

## 🔄 Flujo de Trabajo

### Desarrollo Diario
1. `./scripts/start-dev.sh` - Iniciar
2. Trabajar en tu código
3. Los cambios se ven automáticamente en http://localhost:5173
4. `./scripts/stop-dev.sh` - Detener cuando termines

### Aplicar a Producción
```bash
npm run build
```
Los cambios se aplicarán automáticamente a producción.

---

## 📊 Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│                    TU SERVIDOR                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  PRODUCCIÓN (No se modifica)                           │
│  ├── Backend: Puerto configurado en .env               │
│  └── Frontend: public/build (assets compilados)        │
│                                                         │
│  ─────────────────────────────────────────────────     │
│                                                         │
│  DESARROLLO (Nuevo)                                    │
│  ├── Backend: http://localhost:8000                    │
│  └── Frontend: http://localhost:5173 (HMR activo)      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**✅ Ambos entornos pueden correr simultáneamente sin conflictos**

---

## 💡 Comandos Útiles

| Comando | Descripción |
|---------|-------------|
| `./scripts/start-dev.sh` | Iniciar desarrollo |
| `./scripts/stop-dev.sh` | Detener desarrollo |
| `./scripts/help.sh` | Ver ayuda rápida |
| `npm run build` | Compilar para producción |
| `npm run deploy` | Build + confirmación |
| `pm2 list` | Ver procesos activos |
| `pm2 logs` | Ver logs en tiempo real |

---

## 🐛 Solución de Problemas

### Puerto ocupado
Si el puerto 8000 o 5173 está ocupado, edita `.env.development`:
```bash
DEV_PORT=8001
VITE_PORT=5174
```

### Reiniciar servicios
```bash
pm2 restart conalca-dev-backend
pm2 restart conalca-dev-frontend
```

### Limpiar todo y empezar de nuevo
```bash
./scripts/stop-dev.sh
pm2 kill
./scripts/start-dev.sh
```

---

## ✨ Características

✅ **Entornos Separados**: Desarrollo y producción independientes
✅ **Hot Module Replacement**: Cambios instantáneos sin recargar
✅ **Gestión con PM2**: Procesos estables y monitoreables
✅ **Puertos Configurables**: Evita conflictos
✅ **Logs en Tiempo Real**: Debugging fácil
✅ **Deploy Sencillo**: Un solo comando para producción

---

## 📞 Ayuda Rápida

Para ver la ayuda en cualquier momento:
```bash
./scripts/help.sh
```

Para ver la documentación completa:
```bash
cat GUIA_DESARROLLO.md
```

---

**¡Listo para desarrollar! 🚀**
