#!/bin/bash

# Script de configuración automática de Nginx para Conalca MCP Server
# Configura el servidor para funcionar en https://my-kontrol.online/mcp

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

echo -e "${BLUE}🔧 Configuración automática de Nginx para MCP Server${NC}"
echo "===================================================="

# Verificar que se está ejecutando como root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Este script debe ejecutarse como root${NC}"
   echo "Usa: sudo $0"
   exit 1
fi

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Variables de configuración
DOMAIN="my-kontrol.online"
NGINX_SITE="/etc/nginx/sites-available/$DOMAIN"
NGINX_ENABLED="/etc/nginx/sites-enabled/$DOMAIN"
BACKUP_DIR="/etc/nginx/backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   Dominio: $DOMAIN"
echo "   Ruta MCP: /mcp"
echo "   Puerto MCP: 18840"
echo ""

# Crear directorio de backup
echo -e "${YELLOW}📦 Creando backup de configuración actual...${NC}"
mkdir -p "$BACKUP_DIR"

# Backup de la configuración actual
if [ -f "$NGINX_SITE" ]; then
    cp "$NGINX_SITE" "$BACKUP_DIR/$DOMAIN.backup-$TIMESTAMP"
    echo -e "${GREEN}✅ Backup creado: $BACKUP_DIR/$DOMAIN.backup-$TIMESTAMP${NC}"
else
    echo -e "${RED}❌ No se encontró configuración existente para $DOMAIN${NC}"
    exit 1
fi

# Verificar que nginx esté instalado
if ! command -v nginx &> /dev/null; then
    echo -e "${RED}❌ Nginx no está instalado${NC}"
    exit 1
fi

# Copiar la nueva configuración
echo -e "${YELLOW}🔄 Instalando nueva configuración de Nginx...${NC}"
if [ -f "$SCRIPT_DIR/my-kontrol.online.new" ]; then
    cp "$SCRIPT_DIR/my-kontrol.online.new" "$NGINX_SITE"
    echo -e "${GREEN}✅ Configuración copiada${NC}"
else
    echo -e "${RED}❌ No se encontró el archivo de configuración nueva${NC}"
    exit 1
fi

# Verificar sintaxis de nginx
echo -e "${YELLOW}🔍 Verificando sintaxis de Nginx...${NC}"
if nginx -t; then
    echo -e "${GREEN}✅ Sintaxis de Nginx correcta${NC}"
else
    echo -e "${RED}❌ Error en la sintaxis de Nginx${NC}"
    echo "Restaurando configuración anterior..."
    cp "$BACKUP_DIR/$DOMAIN.backup-$TIMESTAMP" "$NGINX_SITE"
    exit 1
fi

# Habilitar el sitio si no está habilitado
if [ ! -L "$NGINX_ENABLED" ]; then
    echo -e "${YELLOW}🔗 Habilitando sitio...${NC}"
    ln -sf "$NGINX_SITE" "$NGINX_ENABLED"
    echo -e "${GREEN}✅ Sitio habilitado${NC}"
fi

# Recargar nginx
echo -e "${YELLOW}🔄 Recargando Nginx...${NC}"
if systemctl reload nginx; then
    echo -e "${GREEN}✅ Nginx recargado exitosamente${NC}"
else
    echo -e "${RED}❌ Error al recargar Nginx${NC}"
    echo "Restaurando configuración anterior..."
    cp "$BACKUP_DIR/$DOMAIN.backup-$TIMESTAMP" "$NGINX_SITE"
    systemctl reload nginx
    exit 1
fi

# Verificar que nginx esté funcionando
echo -e "${YELLOW}🔍 Verificando estado de Nginx...${NC}"
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx está funcionando correctamente${NC}"
else
    echo -e "${RED}❌ Nginx no está funcionando${NC}"
    systemctl status nginx
    exit 1
fi

# Crear servicio systemd para el servidor MCP
echo -e "${YELLOW}🔧 Creando servicio systemd para MCP Server...${NC}"

cat > /etc/systemd/system/conalca-mcp.service << EOF
[Unit]
Description=Conalca MCP Server
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=exec
User=root
Group=root
WorkingDirectory=$SCRIPT_DIR
Environment=PATH=$SCRIPT_DIR/venv/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
ExecStart=$SCRIPT_DIR/venv/bin/python -m conalca_mcp_server.server http 8000
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=conalca-mcp

# Security settings
NoNewPrivileges=yes
PrivateTmp=yes
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=$SCRIPT_DIR

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd y habilitar el servicio
echo -e "${YELLOW}🔄 Configurando servicio systemd...${NC}"
systemctl daemon-reload
systemctl enable conalca-mcp.service

echo -e "${GREEN}✅ Servicio conalca-mcp.service creado y habilitado${NC}"

# Crear script de gestión del servicio
cat > "$SCRIPT_DIR/manage_mcp_service.sh" << 'EOF'
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
EOF

chmod +x "$SCRIPT_DIR/manage_mcp_service.sh"

echo -e "${GREEN}✅ Script de gestión creado: manage_mcp_service.sh${NC}"

# Configuración final
echo ""
echo -e "${GREEN}🎉 ¡Configuración completada exitosamente!${NC}"
echo "=============================================="
echo ""
echo -e "${BLUE}📋 Resumen de la configuración:${NC}"
echo "   • Nginx configurado para proxy reverso en /mcp"
echo "   • Servidor MCP configurado para puerto 18840"
echo "   • Servicio systemd creado: conalca-mcp.service"
echo "   • Script de gestión: manage_mcp_service.sh"
echo ""
echo -e "${BLUE}🌐 URLs disponibles:${NC}"
echo "   • Servidor principal: https://my-kontrol.online/"
echo "   • Servidor MCP: https://my-kontrol.online/mcp/"
echo "   • Health check: https://my-kontrol.online/mcp/health"
echo "   • Webhook ElevenLabs: https://my-kontrol.online/mcp/webhook/elevenlabs"
echo ""
echo -e "${YELLOW}🚀 Para iniciar el servidor MCP:${NC}"
echo "   sudo systemctl start conalca-mcp"
echo "   O usa: ./manage_mcp_service.sh start"
echo ""
echo -e "${YELLOW}📊 Para ver el estado:${NC}"
echo "   sudo systemctl status conalca-mcp"
echo "   O usa: ./manage_mcp_service.sh status"
echo ""
echo -e "${YELLOW}📋 Para ver los logs:${NC}"
echo "   sudo journalctl -u conalca-mcp -f"
echo "   O usa: ./manage_mcp_service.sh logs"
echo ""
echo -e "${GREEN}✅ ¡El servidor MCP está listo para funcionar en https://my-kontrol.online/mcp!${NC}"