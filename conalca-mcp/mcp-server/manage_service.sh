#!/bin/bash

# Script de gestión para Conalca MCP Server - ElevenLabs Compatible
# Uso: ./manage_service.sh [start|stop|restart|status|logs|enable|disable]

SERVICE_NAME="conalca-mcp-elevenlabs"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

show_usage() {
    echo "🔧 Gestión del Servicio Conalca MCP - ElevenLabs Compatible"
    echo "=================================================="
    echo "Uso: $0 [comando]"
    echo ""
    echo "Comandos disponibles:"
    echo "  start     - Inicia el servicio"
    echo "  stop      - Detiene el servicio"
    echo "  restart   - Reinicia el servicio"
    echo "  status    - Muestra el estado del servicio"
    echo "  logs      - Muestra los logs del servicio"
    echo "  enable    - Habilita el servicio para inicio automático"
    echo "  disable   - Deshabilita el inicio automático"
    echo "  test      - Ejecuta pruebas de conectividad"
    echo "  info      - Muestra información del servidor"
    echo ""
}

start_service() {
    echo "🚀 Iniciando servicio $SERVICE_NAME..."
    sudo systemctl start $SERVICE_NAME.service
    sleep 2
    sudo systemctl status $SERVICE_NAME.service --no-pager -l
}

stop_service() {
    echo "🛑 Deteniendo servicio $SERVICE_NAME..."
    sudo systemctl stop $SERVICE_NAME.service
    echo "✅ Servicio detenido"
}

restart_service() {
    echo "🔄 Reiniciando servicio $SERVICE_NAME..."
    sudo systemctl restart $SERVICE_NAME.service
    sleep 2
    sudo systemctl status $SERVICE_NAME.service --no-pager -l
}

show_status() {
    echo "📊 Estado del servicio $SERVICE_NAME:"
    echo "=================================="
    sudo systemctl status $SERVICE_NAME.service --no-pager -l
}

show_logs() {
    echo "📋 Logs del servicio $SERVICE_NAME:"
    echo "==============================="
    sudo journalctl -u $SERVICE_NAME.service --no-pager -n 50 -f
}

enable_service() {
    echo "⚡ Habilitando inicio automático para $SERVICE_NAME..."
    sudo systemctl enable $SERVICE_NAME.service
    echo "✅ Servicio habilitado para inicio automático"
}

disable_service() {
    echo "❌ Deshabilitando inicio automático para $SERVICE_NAME..."
    sudo systemctl disable $SERVICE_NAME.service
    echo "✅ Inicio automático deshabilitado"
}

test_service() {
    echo "🧪 Probando conectividad del servicio..."
    echo "======================================="
    
    echo "1. Health Check:"
    curl -s https://my-kontrol.online/mcp/health | jq . || echo "❌ Health check falló"
    
    echo -e "\n2. Endpoint Principal:"
    curl -s https://my-kontrol.online/mcp/ | jq . || echo "❌ Endpoint principal falló"
    
    echo -e "\n3. Endpoint ElevenLabs - Initialize:"
    curl -s -X POST https://my-kontrol.online/mcp/elevenlabs \
      -H "Content-Type: application/json" \
      -d '{"jsonrpc":"2.0","id":"test","method":"initialize"}' | jq . || echo "❌ Initialize falló"
    
    echo -e "\n4. Endpoint ElevenLabs - Tools List:"
    curl -s -X POST https://my-kontrol.online/mcp/elevenlabs \
      -H "Content-Type: application/json" \
      -d '{"jsonrpc":"2.0","id":"test","method":"tools/list"}' | jq '.result.tools | length' || echo "❌ Tools list falló"
    
    echo -e "\n✅ Pruebas completadas"
}

show_info() {
    echo "ℹ️  Información del Conalca MCP Server - ElevenLabs Compatible"
    echo "============================================================"
    echo "📍 URL Principal: https://my-kontrol.online/mcp/"
    echo "🎯 URL ElevenLabs: https://my-kontrol.online/mcp/elevenlabs"
    echo "💚 Health Check: https://my-kontrol.online/mcp/health"
    echo "🔧 Puerto Local: 18840"
    echo "📦 Servicio: $SERVICE_NAME.service"
    echo "📄 Script: $SCRIPT_DIR/run_service.py"
    echo ""
    echo "🛠️  Configuración para ElevenLabs:"
    echo "   - Tipo: HTTP Streamable"
    echo "   - URL: https://my-kontrol.online/mcp/elevenlabs"
    echo "   - Headers: Ninguno requerido"
    echo ""
    echo "🔧 Herramientas disponibles:"
    echo "   ✅ get_llamadas (obtener llamadas)"
    echo "   ✅ get_llamada_by_id (llamada por ID)"
    echo "   ✅ create_llamada (crear llamada)"
    echo "   ✅ update_llamada_status (actualizar estado)"
    echo "   ✅ get_cotizaciones (obtener cotizaciones)"
    echo "   ✅ get_vehicle_by_telefono_conductor (buscar vehículos)"
    echo "   ✅ get_chofer_by_placa (buscar chofer por placa)"
    echo "   ✅ process_elevenlabs_event (procesar eventos)"
}

# Procesamiento de argumentos
case "$1" in
    start)
        start_service
        ;;
    stop)
        stop_service
        ;;
    restart)
        restart_service
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    enable)
        enable_service
        ;;
    disable)
        disable_service
        ;;
    test)
        test_service
        ;;
    info)
        show_info
        ;;
    *)
        show_usage
        exit 1
        ;;
esac

exit 0