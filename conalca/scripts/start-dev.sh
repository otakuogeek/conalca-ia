#!/bin/bash

# Script para iniciar el FRONTEND en modo desarrollo
# El BACKEND sigue corriendo en producción (un solo backend para todo)

echo "🚀 Iniciando FRONTEND en modo DESARROLLO..."

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Ir al directorio raíz del proyecto
cd "$(dirname "$0")/.." || exit

# Cargar variables ignorando comentarios y líneas vacías
if [ -f .env.development ]; then
    # Exportar variables temporalmente para este script
    set -a
    source .env.development
    set +a
fi

# Asignar valores con fallback
DEV_PORT=${DEV_PORT:-8000}
VITE_PORT=${VITE_PORT:-5173}

# Limpiar posibles caracteres de retorno de carro (\r) si el archivo fue editado en Windows
DEV_PORT=$(echo "$DEV_PORT" | tr -d '\r')
VITE_PORT=$(echo "$VITE_PORT" | tr -d '\r')

echo -e "${BLUE}📦 Verificando dependencias...${NC}"
if [ ! -d "node_modules" ]; then
    echo "Instalando dependencias..."
    npm install
fi

echo ""
echo -e "${BLUE}🔧 Configuración:${NC}"
echo "  - Backend (PRODUCCIÓN): Tu servidor actual"
echo "  - Frontend (DESARROLLO): http://localhost:${VITE_PORT}"
echo ""
echo -e "${YELLOW}📌 El backend de producción se mantiene corriendo${NC}"
echo -e "${YELLOW}📌 Solo el frontend estará en modo desarrollo con HMR${NC}"
echo ""

# Crear directorio temporal para el entorno de desarrollo
mkdir -p /tmp/conalca-dev

# Copiar .env.development a un archivo temporal que Laravel pueda usar
cp .env.development /tmp/conalca-dev/.env

# Iniciar backend Laravel en modo desarrollo con PM2
# Laravel buscará el .env en el directorio actual, así que usamos un script wrapper
echo -e "${GREEN}🟢 Iniciando Backend Laravel (DEV) en puerto ${DEV_PORT}...${NC}"

# Crear script temporal que ejecuta artisan con el .env correcto
cat > /tmp/conalca-dev/start-laravel.sh << 'EOFSCRIPT'
#!/bin/bash
cd /home/ubuntu/conalca/conalca
# Hacer backup temporal del .env original
if [ -f .env ]; then
    cp .env .env.prod.backup
fi
# Usar .env.development
cp .env.development .env
# Ejecutar servidor
php artisan serve --port=$1
# Restaurar .env original
if [ -f .env.prod.backup ]; then
    mv .env.prod.backup .env
fi
EOFSCRIPT

chmod +x /tmp/conalca-dev/start-laravel.sh

pm2 start /tmp/conalca-dev/start-laravel.sh --name "conalca-dev-backend" -- ${DEV_PORT}

# Esperar un momento para que el backend inicie
sleep 3

# Iniciar frontend Vite en modo desarrollo con PM2
echo -e "${GREEN}🟢 Iniciando Frontend Vite (DEV) en puerto ${VITE_PORT}...${NC}"
VITE_PORT=${VITE_PORT} pm2 start npm --name "conalca-dev-frontend" -- run dev

# Guardar configuración de PM2
pm2 save

echo ""
echo -e "${GREEN}✅ Entorno de desarrollo iniciado correctamente${NC}"
echo ""
echo -e "${BLUE}🌐 Accede a tu aplicación en desarrollo:${NC}"
echo "  👉 Frontend (Vite): http://localhost:${VITE_PORT}"
echo "  👉 Backend (Dev):   http://localhost:${DEV_PORT}"
echo ""
echo -e "${BLUE}ℹ️  Información importante:${NC}"
echo "  - PRODUCCIÓN: https://conalcaia.conalca.com.co (Estable, no afectado)"
echo "  - DESARROLLO: http://localhost:${DEV_PORT} (Usa Vite con HMR)"
echo "  - Los cambios en archivos .jsx, .js, .css se verán en el entorno de DESARROLLO"
echo ""
echo -e "${BLUE}📊 Para ver los logs:${NC}"
echo "  pm2 logs conalca-dev-backend"
echo "  pm2 logs conalca-dev-frontend"
echo ""
echo -e "${BLUE}🔄 Para aplicar cambios a PRODUCCIÓN:${NC}"
echo "  1. npm run build"
echo "  2. Los cambios se aplicarán automáticamente"
echo ""
echo -e "${BLUE}🛑 Para detener el entorno de desarrollo:${NC}"
echo "  ./scripts/stop-dev.sh"
echo ""

# Mostrar estado de PM2
pm2 list
