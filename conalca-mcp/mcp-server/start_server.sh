#!/bin/bash

# Script de inicio para el servidor MCP de Conalca
# Uso: ./start_server.sh [development|production]

set -e

MODE=${1:-development}

echo "🚀 Iniciando servidor MCP de Conalca en modo: $MODE"

# Verificar Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 no está instalado"
    exit 1
fi

# Verificar que el archivo .env existe
if [ ! -f "../.env" ]; then
    echo "❌ Archivo .env no encontrado en el directorio padre"
    echo "   Por favor configure las variables de entorno"
    exit 1
fi

# Crear entorno virtual si no existe
if [ ! -d "venv" ]; then
    echo "📦 Creando entorno virtual..."
    python3 -m venv venv
    
    # Activar entorno virtual
    source venv/bin/activate
    
    # Instalar dependencias
    echo "📚 Instalando dependencias..."
    pip install --upgrade pip
    pip install -r requirements.txt
else
    # Activar entorno virtual existente
    source venv/bin/activate
fi

# Configurar logging según el modo
if [ "$MODE" = "production" ]; then
    export LOG_LEVEL=WARNING
    export UVLOOP_ENABLED=true
else
    export LOG_LEVEL=INFO
    export UVLOOP_ENABLED=true
fi

echo "✅ Configuración completada"
echo "🔌 Conectando a base de datos MySQL..."

# Ejecutar el servidor
echo "🎯 Ejecutando servidor MCP..."
python -m conalca_mcp_server.server