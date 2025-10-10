#!/bin/bash

# Script para iniciar el servidor MCP de Conalca en modo HTTP
# Para funcionar bajo nginx en https://my-kontrol.online/mcp

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

echo -e "${BLUE}🚀 Iniciando Conalca MCP Server (Modo HTTP)${NC}"
echo "=============================================="

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Verificar archivo .env
if [ ! -f "../.env" ]; then
    echo -e "${RED}❌ Error: Archivo .env no encontrado en el directorio padre${NC}"
    echo "Por favor, crea el archivo .env con la configuración de la base de datos"
    exit 1
fi

# Verificar entorno virtual
if [ ! -d "venv" ]; then
    echo -e "${RED}❌ Error: Entorno virtual no encontrado${NC}"
    echo "Ejecuta ./install.sh primero"
    exit 1
fi

# Activar entorno virtual
echo -e "${YELLOW}📦 Activando entorno virtual...${NC}"
source venv/bin/activate

# Verificar dependencias
echo -e "${YELLOW}🔍 Verificando dependencias...${NC}"
if ! python -c "import fastapi, uvicorn, aiomysql, mcp" 2>/dev/null; then
    echo -e "${RED}❌ Error: Dependencias faltantes${NC}"
    echo "Ejecuta ./install.sh para instalar dependencias"
    exit 1
fi

# Configuración del servidor
HOST="127.0.0.1"
PORT="18840"
ROOT_PATH=""  # Se configurará como /mcp en nginx

# Procesar argumentos de línea de comandos
while [[ $# -gt 0 ]]; do
    case $1 in
        --host)
            HOST="$2"
            shift 2
            ;;
        --port)
            PORT="$2"
            shift 2
            ;;
        --root-path)
            ROOT_PATH="$2"
            shift 2
            ;;
        -h|--help)
            echo "Uso: $0 [opciones]"
            echo "Opciones:"
            echo "  --host HOST       Host para bind (default: 127.0.0.1)"
            echo "  --port PORT       Puerto para bind (default: 8000)"
            echo "  --root-path PATH  Root path para reverse proxy (default: vacío)"
            echo "  -h, --help        Mostrar esta ayuda"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Opción desconocida: $1${NC}"
            exit 1
            ;;
    esac
done

# Verificar que el puerto no esté en uso
if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: Puerto $PORT ya está en uso${NC}"
    echo "Procesos usando el puerto:"
    lsof -Pi :$PORT -sTCP:LISTEN
    exit 1
fi

# Mostrar configuración
echo -e "${GREEN}✅ Configuración del servidor:${NC}"
echo "   Host: $HOST"
echo "   Puerto: $PORT"
echo "   Root Path: ${ROOT_PATH:-'(ninguno)'}"
echo ""

# Función para limpiar al salir
cleanup() {
    echo -e "\n${YELLOW}🛑 Deteniendo servidor...${NC}"
    # Matar procesos hijos
    jobs -p | xargs -r kill
    exit 0
}

# Configurar señales de limpieza
trap cleanup SIGINT SIGTERM

# Crear directorio de logs si no existe
mkdir -p logs

# Iniciar servidor
echo -e "${GREEN}🌟 Iniciando servidor MCP HTTP...${NC}"
echo -e "${BLUE}URL local: http://$HOST:$PORT${NC}"
echo -e "${BLUE}URL pública: https://my-kontrol.online/mcp${NC}"
echo ""
echo -e "${YELLOW}Presiona Ctrl+C para detener el servidor${NC}"
echo ""

# Ejecutar servidor directamente con Python
if [ -n "$ROOT_PATH" ]; then
    exec python -m conalca_mcp_server.server http "$PORT" "$ROOT_PATH"
else
    exec python -m conalca_mcp_server.server http "$PORT"
fi