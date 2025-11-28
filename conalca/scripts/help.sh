#!/bin/bash

# Script de ayuda rápida para desarrollo

cat << 'EOF'

╔══════════════════════════════════════════════════════════════╗
║                  CONALCA IA - DESARROLLO                     ║
╚══════════════════════════════════════════════════════════════╝

🚀 COMANDOS PRINCIPALES:

   ./scripts/start-dev.sh       Iniciar entorno de desarrollo
   ./scripts/stop-dev.sh        Detener entorno de desarrollo
   npm run build                Compilar para producción
   npm run deploy               Compilar y mostrar confirmación

📊 MONITOREO:

   pm2 list                     Ver todos los procesos
   pm2 logs                     Ver logs en tiempo real
   pm2 logs conalca-dev-backend     Logs del backend
   pm2 logs conalca-dev-frontend    Logs del frontend

🔧 GESTIÓN DE PROCESOS:

   pm2 restart conalca-dev-backend  Reiniciar backend
   pm2 restart conalca-dev-frontend Reiniciar frontend
   pm2 delete conalca-dev-backend   Eliminar backend
   pm2 delete conalca-dev-frontend  Eliminar frontend

🌐 URLS:

   Desarrollo:  http://localhost:5173
   Backend Dev: http://localhost:8000

📖 DOCUMENTACIÓN:

   cat README_DEV.md            Guía rápida
   cat GUIA_DESARROLLO.md       Documentación completa

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💡 PRIMERA VEZ: Edita .env.development con tus credenciales
   nano .env.development

EOF
