#!/bin/bash

# Script para gestionar el servicio Conalca MCP

case "$1" in
    start)
        echo "🚀 Iniciando Conalca MCP Server..."
        sudo systemctl start conalca-mcp
        echo "✅ Servicio iniciado"
        ;;
    stop)
        echo "🛑 Deteniendo Conalca MCP Server..."
        sudo systemctl stop conalca-mcp
        echo "✅ Servicio detenido"
        ;;
    restart)
        echo "🔄 Reiniciando Conalca MCP Server..."
        sudo systemctl restart conalca-mcp
        echo "✅ Servicio reiniciado"
        ;;
    status)
        echo "📊 Estado del Conalca MCP Server:"
        sudo systemctl status conalca-mcp
        ;;
    logs)
        echo "📋 Logs del Conalca MCP Server:"
        sudo journalctl -u conalca-mcp -f
        ;;
    enable)
        echo "🔧 Habilitando auto-inicio..."
        sudo systemctl enable conalca-mcp
        echo "✅ Auto-inicio habilitado"
        ;;
    disable)
        echo "🔧 Deshabilitando auto-inicio..."
        sudo systemctl disable conalca-mcp
        echo "✅ Auto-inicio deshabilitado"
        ;;
    *)
        echo "Uso: $0 {start|stop|restart|status|logs|enable|disable}"
        echo ""
        echo "Comandos disponibles:"
        echo "  start    - Iniciar el servidor MCP"
        echo "  stop     - Detener el servidor MCP"
        echo "  restart  - Reiniciar el servidor MCP"
        echo "  status   - Mostrar estado del servicio"
        echo "  logs     - Mostrar logs en tiempo real"
        echo "  enable   - Habilitar auto-inicio"
        echo "  disable  - Deshabilitar auto-inicio"
        exit 1
        ;;
esac
