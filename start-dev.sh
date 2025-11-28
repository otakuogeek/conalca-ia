#!/bin/bash
# ============================================================
# SCRIPT DE DESARROLLO - CONALCA
# ============================================================
# Este script inicia un entorno de desarrollo SEPARADO
# que NO afecta la producción en https://conalcaia.conalca.com.co
#
# PRODUCCIÓN: https://conalcaia.conalca.com.co (intacto)
# DESARROLLO: http://localhost:8000 (este script)
# ============================================================

cd /home/ubuntu/conalca/conalca

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "╔══════════════════════════════════════════════════════════╗"
echo "║          CONALCA - MODO DESARROLLO                       ║"
echo "╠══════════════════════════════════════════════════════════╣"
echo "║  Producción: https://conalcaia.conalca.com.co  (INTACTO) ║"
echo "║  Desarrollo: http://localhost:8000             (ESTE)    ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Verificar que no hay archivo hot (proteger producción)
rm -f /home/ubuntu/conalca/conalca/public/hot

# Matar procesos anteriores de desarrollo
pkill -f "php artisan serve" 2>/dev/null
pkill -f "vite.*5173" 2>/dev/null
sleep 1

echo -e "${YELLOW}[1/3] Iniciando Vite (Hot Module Replacement)...${NC}"
npm run dev -- --host 0.0.0.0 &
VITE_PID=$!
sleep 3

echo -e "${YELLOW}[2/3] Creando archivo hot para desarrollo...${NC}"
echo "http://localhost:5173" > /home/ubuntu/conalca/conalca/public/hot

echo -e "${YELLOW}[3/3] Iniciando servidor Laravel...${NC}"
php artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗"
echo -e "║  ✅ DESARROLLO LISTO                                      ║"
echo -e "╠══════════════════════════════════════════════════════════╣"
echo -e "║                                                          ║"
echo -e "║  🌐 Acceso desarrollo: ${YELLOW}http://localhost:8000${GREEN}            ║"
echo -e "║  🔥 Vite HMR:          ${YELLOW}http://localhost:5173${GREEN}            ║"
echo -e "║                                                          ║"
echo -e "║  📁 Los cambios en JS/CSS se reflejan automáticamente    ║"
echo -e "║                                                          ║"
echo -e "║  🛑 Para detener: ${RED}Ctrl+C${GREEN}                                 ║"
echo -e "║                                                          ║"
echo -e "╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Función para limpiar al salir
cleanup() {
    echo ""
    echo -e "${YELLOW}Deteniendo desarrollo...${NC}"
    kill $VITE_PID 2>/dev/null
    kill $LARAVEL_PID 2>/dev/null
    rm -f /home/ubuntu/conalca/conalca/public/hot
    echo -e "${GREEN}✅ Desarrollo detenido. Producción sigue funcionando.${NC}"
    exit 0
}

trap cleanup SIGINT SIGTERM

# Mantener script corriendo
wait
