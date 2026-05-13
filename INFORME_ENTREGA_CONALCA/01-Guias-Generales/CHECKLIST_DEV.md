# ✅ Checklist de Configuración - Entorno de Desarrollo

## Antes de Iniciar por Primera Vez

### ☐ 1. Editar `.env.development`
```bash
nano .env.development
```

**Copiar desde tu `.env` principal:**
- [ ] `ARCANGEL_API_URL`
- [ ] `ARCANGEL_API_KEY`
- [ ] `ELEVENLABS_API_KEY`
- [ ] Otras credenciales de APIs que uses
- [ ] `APP_KEY` (si usas encriptación)

**Opcional - Configurar base de datos separada:**
- [ ] `DB_DATABASE` (por defecto: `conalca_dev`)
- [ ] `DB_USERNAME`
- [ ] `DB_PASSWORD`

### ☐ 2. Verificar que las dependencias estén instaladas
```bash
npm install
composer install
```

### ☐ 3. Verificar que los scripts tengan permisos de ejecución
```bash
chmod +x scripts/*.sh
```

---

## Iniciar Desarrollo

### ☐ 1. Iniciar el entorno
```bash
./scripts/start-dev.sh
```

### ☐ 2. Verificar que los procesos estén corriendo
```bash
pm2 list
```

Deberías ver:
- `conalca-dev-backend` (online)
- `conalca-dev-frontend` (online)

### ☐ 3. Acceder a la aplicación
Abrir navegador en: **http://localhost:5173**

---

## Durante el Desarrollo

### ☐ Ver logs si hay problemas
```bash
pm2 logs
```

### ☐ Reiniciar un servicio si es necesario
```bash
pm2 restart conalca-dev-backend
# o
pm2 restart conalca-dev-frontend
```

---

## Al Terminar el Día

### ☐ Detener el entorno de desarrollo
```bash
./scripts/stop-dev.sh
```

---

## Aplicar Cambios a Producción

### ☐ 1. Compilar assets
```bash
npm run build
```

### ☐ 2. Verificar que el build se completó
Deberías ver archivos nuevos en `public/build/`

### ☐ 3. Los cambios se aplican automáticamente
Tu servidor de producción usará los nuevos assets automáticamente.

---

## Solución de Problemas

### ☐ Si el puerto está ocupado
Editar `.env.development`:
```bash
DEV_PORT=8001
VITE_PORT=5174
```

### ☐ Si los cambios no se reflejan
1. Verificar que estás en http://localhost:5173 (no en producción)
2. Revisar logs: `pm2 logs conalca-dev-frontend`
3. Reiniciar: `pm2 restart conalca-dev-frontend`

### ☐ Si PM2 da problemas
```bash
pm2 kill
./scripts/start-dev.sh
```

### ☐ Si Vite no inicia
```bash
rm -rf node_modules/.vite
npm run dev
```

---

## Verificación Final

### ☐ Checklist de funcionamiento:
- [ ] Backend responde en http://localhost:8000
- [ ] Frontend carga en http://localhost:5173
- [ ] Los cambios en el código se reflejan automáticamente
- [ ] Los logs se ven con `pm2 logs`
- [ ] Producción sigue funcionando normalmente

---

## Recursos de Ayuda

- **Ayuda rápida**: `./scripts/help.sh`
- **Guía de inicio**: `cat README_DEV.md`
- **Documentación completa**: `cat GUIA_DESARROLLO.md`
- **Este checklist**: `cat CHECKLIST_DEV.md`

---

**¡Listo para desarrollar! 🚀**
