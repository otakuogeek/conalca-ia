#!/bin/bash

# Script de instalación automática del Servidor MCP Conalca
# Para uso con ElevenLabs

set -e

echo "🚀 INSTALACIÓN DEL SERVIDOR MCP CONALCA"
echo "========================================"

# Verificar sistema operativo
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    echo "⚠️  Este script está optimizado para Linux"
fi

# Verificar Python 3.9+
echo "🐍 Verificando Python..."
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 no está instalado"
    echo "   Instala Python3 con: sudo apt update && sudo apt install python3 python3-venv python3-pip"
    exit 1
fi

PYTHON_VERSION=$(python3 -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')")
echo "✅ Python $PYTHON_VERSION encontrado"

# Verificar MySQL
echo "🗄️  Verificando MySQL..."
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL no está instalado. Asegúrate de tener acceso a una base de datos MySQL"
else
    echo "✅ MySQL encontrado"
fi

# Verificar archivo .env
echo "⚙️  Verificando configuración..."
if [ ! -f "../.env" ]; then
    echo "❌ Archivo .env no encontrado en el directorio padre"
    echo "   Asegúrate de tener configuradas las variables de entorno de la base de datos"
    exit 1
fi
echo "✅ Archivo .env encontrado"

# Crear entorno virtual
echo "📦 Configurando entorno virtual..."
if [ -d "venv" ]; then
    echo "   Entorno virtual existente encontrado"
    rm -rf venv
    echo "   Eliminando entorno virtual anterior"
fi

python3 -m venv venv
echo "✅ Entorno virtual creado"

# Activar entorno virtual
source venv/bin/activate
echo "✅ Entorno virtual activado"

# Actualizar pip
echo "📚 Actualizando pip..."
pip install --upgrade pip
echo "✅ Pip actualizado"

# Instalar dependencias
echo "📦 Instalando dependencias..."
pip install -r requirements.txt
echo "✅ Dependencias instaladas"

# Ejecutar pruebas
echo "🧪 Ejecutando pruebas del sistema..."
python test_server.py

if [ $? -eq 0 ]; then
    echo "✅ Todas las pruebas pasaron"
else
    echo "❌ Algunas pruebas fallaron. Revisa la configuración de la base de datos"
    exit 1
fi

# Ejecutar ejemplos
echo "💡 Ejecutando ejemplos de uso..."
python example_usage.py

# Crear servicio systemd (opcional)
echo "🔧 ¿Deseas crear un servicio systemd para auto-inicio? (y/n)"
read -r create_service

if [[ $create_service =~ ^[Yy]$ ]]; then
    SERVICE_FILE="/etc/systemd/system/conalca-mcp.service"
    
    sudo tee $SERVICE_FILE > /dev/null <<EOF
[Unit]
Description=Conalca MCP Server for ElevenLabs
After=network.target mysql.service

[Service]
Type=simple
User=$USER
WorkingDirectory=$(pwd)
Environment=PATH=$(pwd)/venv/bin
ExecStart=$(pwd)/venv/bin/python -m conalca_mcp_server.server
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable conalca-mcp.service
    
    echo "✅ Servicio systemd creado"
    echo "   Comandos útiles:"
    echo "   - Iniciar: sudo systemctl start conalca-mcp"
    echo "   - Parar: sudo systemctl stop conalca-mcp"
    echo "   - Estado: sudo systemctl status conalca-mcp"
    echo "   - Logs: sudo journalctl -u conalca-mcp -f"
fi

echo ""
echo "🎉 INSTALACIÓN COMPLETADA"
echo "========================"
echo ""
echo "📋 RESUMEN:"
echo "   ✅ Entorno virtual configurado: $(pwd)/venv"
echo "   ✅ Dependencias instaladas"
echo "   ✅ Pruebas ejecutadas exitosamente"
echo "   ✅ Ejemplos de uso disponibles"
echo ""
echo "🚀 CÓMO EJECUTAR:"
echo "   1. Manual: ./start_server.sh"
echo "   2. Pruebas: python test_server.py"
echo "   3. Ejemplos: python example_usage.py"
echo ""
echo "🔗 INTEGRACIÓN CON ELEVENLABS:"
echo "   1. Copia el archivo mcp_config.json a tu configuración de ElevenLabs"
echo "   2. Revisa ELEVENLABS_INTEGRATION.md para instrucciones detalladas"
echo "   3. Configura los webhooks usando elevenlabs_webhook_handler"
echo ""
echo "📖 DOCUMENTACIÓN:"
echo "   - README.md: Documentación general"
echo "   - ELEVENLABS_INTEGRATION.md: Guía de integración específica"
echo ""
echo "🆘 SOPORTE:"
echo "   - Logs del servidor: ./start_server.sh"
echo "   - Pruebas: python test_server.py"
echo "   - Estado de la BD: Revisa las credenciales en ../.env"
echo ""
echo "¡Servidor MCP de Conalca listo para usar con ElevenLabs! 🎯"