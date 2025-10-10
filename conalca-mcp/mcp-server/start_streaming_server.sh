#!/bin/bash

# Servidor MCP Streameable - Script de inicio
# Este script inicia el servidor con capacidades completas de streaming

set -e

echo "🚀 Iniciando Conalca MCP Server - Streameable v2.0"
echo "=================================================="

# Configuración
SERVER_DIR="/root/conalca-mcp/mcp-server"
VENV_PATH="$SERVER_DIR/venv"
PORT=18840
ROOT_PATH="/mcp"

# Verificar que el directorio existe
if [ ! -d "$SERVER_DIR" ]; then
    echo "❌ Error: Directorio del servidor no encontrado: $SERVER_DIR"
    exit 1
fi

# Cambiar al directorio del servidor
cd "$SERVER_DIR"

# Activar el entorno virtual
if [ -d "$VENV_PATH" ]; then
    echo "🔧 Activando entorno virtual..."
    source "$VENV_PATH/bin/activate"
else
    echo "❌ Error: Entorno virtual no encontrado en $VENV_PATH"
    exit 1
fi

# Verificar dependencias críticas
echo "🔍 Verificando dependencias..."
python -c "import fastapi, uvicorn, sse_starlette, uvloop" || {
    echo "❌ Error: Faltan dependencias críticas"
    echo "Instalando dependencias..."
    pip install fastapi uvicorn sse-starlette uvloop websockets
}

# Mostrar información del servidor
echo ""
echo "📊 Configuración del servidor:"
echo "   - Puerto: $PORT"
echo "   - Root Path: $ROOT_PATH"
echo "   - Directorio: $SERVER_DIR"
echo "   - Modo: HTTP Streameable"
echo ""
echo "🌐 Endpoints disponibles:"
echo "   - https://my-kontrol.online/mcp/ (raíz)"
echo "   - https://my-kontrol.online/mcp/health (health check)"
echo "   - https://my-kontrol.online/mcp/webhook/elevenlabs (webhook ElevenLabs)"
echo "   - https://my-kontrol.online/mcp/ws (WebSocket)"
echo "   - https://my-kontrol.online/mcp/stream (Server-Sent Events)"
echo "   - https://my-kontrol.online/mcp/data/stream (HTTP Streaming)"
echo ""

# Verificar puerto
if netstat -tuln | grep ":$PORT " > /dev/null; then
    echo "⚠️  Puerto $PORT ya está en uso. Intentando detener procesos existentes..."
    pkill -f "run_mcp_server.py" || true
    pkill -f "server_streaming.py" || true
    sleep 2
fi

# Función para cleanup al salir
cleanup() {
    echo ""
    echo "🛑 Deteniendo servidor..."
    jobs -p | xargs -r kill
    exit 0
}

trap cleanup INT TERM

echo "🚀 Iniciando servidor streameable..."
echo "   Usa Ctrl+C para detener"
echo ""

# Iniciar el servidor usando el nuevo módulo streaming
python -m conalca_mcp_server.server_streaming http $PORT "$ROOT_PATH" &
SERVER_PID=$!

# Esperar un momento para que el servidor inicie
sleep 3

# Verificar que el servidor esté corriendo
if ps -p $SERVER_PID > /dev/null; then
    echo "✅ Servidor iniciado correctamente (PID: $SERVER_PID)"
    echo "🔗 Accesible en: https://my-kontrol.online/mcp/"
    echo ""
    echo "📡 Capacidades de streaming activas:"
    echo "   ✅ WebSocket (tiempo real)"
    echo "   ✅ Server-Sent Events (SSE)"
    echo "   ✅ HTTP Streaming"
    echo "   ✅ CORS habilitado"
    echo "   ✅ ElevenLabs webhook ready"
    echo ""
    echo "📊 Para monitorear el servidor:"
    echo "   curl https://my-kontrol.online/mcp/health"
    echo ""
    echo "🔄 Esperando conexiones..."
else
    echo "❌ Error: El servidor no pudo iniciarse"
    exit 1
fi

# Mantener el script corriendo
wait $SERVER_PID