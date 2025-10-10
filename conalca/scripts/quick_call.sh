#!/bin/bash

# Script rápido para llamar a +573105672307
# Ejecuta el comando Artisan directamente

echo "📞 CONALCA - Llamada Rápida a +573105672307"
echo "=========================================="

cd /root/conalca

echo "🔄 Realizando llamada..."
php artisan call:specific +573105672307

echo ""
echo "📋 Últimos logs de llamadas:"
tail -n 10 storage/logs/laravel.log | grep -i "call\|twilio"