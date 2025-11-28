#!/bin/bash

# Script para detener el entorno de DESARROLLO

echo "🛑 Deteniendo entorno de DESARROLLO..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Ir al directorio raíz del proyecto
cd "$(dirname "$0")/.." || exit

# Restaurar .env original si existe backup (por seguridad)
if [ -f .env.prod.backup ]; then
    mv .env.prod.backup .env
    echo -e "${GREEN}✓ .env original restaurado${NC}"
fi

# Detener procesos PM2 de desarrollo
pm2 delete conalca-dev-backend 2>/dev/null && echo -e "${GREEN}✓ Backend detenido${NC}" || echo -e "${RED}✗ Backend no estaba corriendo${NC}"
pm2 delete conalca-dev-frontend 2>/dev/null && echo -e "${GREEN}✓ Frontend detenido${NC}" || echo -e "${RED}✗ Frontend no estaba corriendo${NC}"

# Limpiar archivos temporales
rm -rf /tmp/conalca-dev
echo -e "${GREEN}✓ Archivos temporales limpiados${NC}"

echo ""
echo -e "${GREEN}✅ Entorno de desarrollo detenido${NC}"
echo -e "${GREEN}✅ El backend de producción sigue corriendo normalmente${NC}"
echo ""

# Mostrar estado de PM2
pm2 list
