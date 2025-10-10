#!/bin/bash

# Servidor MCP Compliant - Compatible con ElevenLabs
# Implementa protocolo JSON-RPC 2.0 estándar

set -e

echo "🚀 Iniciando Conalca MCP Server - ElevenLabs Compatible v2.1"
echo "============================================================"

# Configuración
SERVER_DIR="/root/conalca-mcp/mcp-server"
VENV_PATH="$SERVER_DIR/venv"
PORT=18840
ROOT_PATH="/mcp"

# Verificar directorio
if [ ! -d "$SERVER_DIR" ]; then
    echo "❌ Error: Directorio del servidor no encontrado: $SERVER_DIR"
    exit 1
fi

cd "$SERVER_DIR"

# Activar entorno virtual
if [ -d "$VENV_PATH" ]; then
    echo "🔧 Activando entorno virtual..."
    source "$VENV_PATH/bin/activate"
else
    echo "❌ Error: Entorno virtual no encontrado en $VENV_PATH"
    exit 1
fi

# Verificar dependencias
echo "🔍 Verificando dependencias..."
python -c "import fastapi, uvicorn, uvloop" || {
    echo "❌ Error: Faltan dependencias críticas"
    echo "Instalando dependencias..."
    pip install fastapi uvicorn uvloop
}

# Información del servidor
echo ""
echo "📊 Configuración del servidor MCP:"
echo "   - Puerto: $PORT"
echo "   - Root Path: $ROOT_PATH"
echo "   - Protocolo: JSON-RPC 2.0"
echo "   - Compatible con: ElevenLabs"
echo ""
echo "🌐 Endpoints MCP disponibles:"
echo "   - https://my-kontrol.online/mcp/ (JSON-RPC endpoint)"
echo "   - https://my-kontrol.online/mcp/health (health check)"
echo "   - https://my-kontrol.online/mcp/webhook/elevenlabs (webhook)"
echo ""
echo "🔧 Métodos JSON-RPC soportados:"
echo "   - initialize (inicialización MCP)"
echo "   - tools/list (listar herramientas)"
echo "   - tools/call (ejecutar herramientas)"
echo "   - resources/list (listar recursos)"
echo ""

# Verificar puerto
if netstat -tuln | grep ":$PORT " > /dev/null; then
    echo "⚠️  Puerto $PORT en uso. Deteniendo procesos existentes..."
    pkill -f "server_streaming" || true
    pkill -f "server_mcp_compliant" || true
    sleep 2
fi

# Función cleanup
cleanup() {
    echo ""
    echo "🛑 Deteniendo servidor MCP..."
    jobs -p | xargs -r kill
    exit 0
}

trap cleanup INT TERM

echo "🚀 Iniciando servidor MCP compatible con ElevenLabs..."
echo "   Protocolo: JSON-RPC 2.0"
echo "   Usa Ctrl+C para detener"
echo ""

# Iniciar servidor
python -m conalca_mcp_server.server_mcp_compliant http $PORT "$ROOT_PATH" &
SERVER_PID=$!

# Esperar inicio
sleep 3

# Verificar que esté corriendo
if ps -p $SERVER_PID > /dev/null; then
    echo "✅ Servidor MCP iniciado correctamente (PID: $SERVER_PID)"
    echo "🔗 Accesible en: https://my-kontrol.online/mcp/"
    echo ""
    echo "📋 Para ElevenLabs usar:"
    echo "   - Tipo: HTTP Reproducible"
    echo "   - URL: https://my-kontrol.online/mcp/"
    echo ""
    echo "🔧 Herramientas disponibles:"
    echo "   ✅ get_llamadas (obtener llamadas)"
    echo "   ✅ get_llamada_by_id (llamada por ID)"
    echo "   ✅ create_llamada (crear llamada)"
    echo "   ✅ update_llamada_status (actualizar estado)"
    echo "   ✅ get_cotizaciones (obtener cotizaciones)"
    echo "   ✅ get_vehicle_by_telefono_conductor (buscar vehículos)"
    echo "   ✅ process_elevenlabs_event (procesar eventos)"
    echo ""
    echo "📊 Para verificar servidor:"
    echo "   curl https://my-kontrol.online/mcp/health"
    echo ""
    echo "🔄 Servidor listo para conexiones MCP..."
else
    echo "❌ Error: El servidor no pudo iniciarse"
    exit 1
fi

# Mantener script corriendo
wait $SERVER_PID