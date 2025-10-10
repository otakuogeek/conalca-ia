#!/bin/bash

# Script de llamada específica para +573105672307
# Usa la lógica del sistema CONALCA para realizar una llamada de prueba

echo "🚀 CONALCA - Script de Llamada Específica"
echo "========================================"
echo ""

# Configuración
TARGET_NUMBER="+573105672307"
SCRIPT_DIR="/root/conalca/scripts"
LARAVEL_DIR="/root/conalca"

echo "📞 Número objetivo: $TARGET_NUMBER"
echo "📁 Directorio Laravel: $LARAVEL_DIR"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "$LARAVEL_DIR/artisan" ]; then
    echo "❌ Error: No se encontró el archivo artisan en $LARAVEL_DIR"
    echo "   Asegúrate de que el directorio sea correcto"
    exit 1
fi

echo "✅ Directorio Laravel verificado"

# Cambiar al directorio de Laravel
cd "$LARAVEL_DIR"

echo "🔄 Ejecutando script de llamada..."
echo ""

# Ejecutar el script PHP
php "$SCRIPT_DIR/call_specific_number.php"

echo ""
echo "📊 Verificando logs recientes..."

# Mostrar los últimos logs relacionados con llamadas
tail -n 20 storage/logs/laravel.log | grep -i "call\|twilio\|script" | tail -n 10

echo ""
echo "✅ Script de llamada completado"
echo "📋 Revisa los logs para más detalles: storage/logs/laravel.log"