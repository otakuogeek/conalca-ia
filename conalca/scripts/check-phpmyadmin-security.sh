#!/bin/bash

# Script de verificación de seguridad para phpMyAdmin
# Autor: Sistema de Seguridad Conalca
# Fecha: Octubre 2025

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║     🔒 VERIFICACIÓN DE SEGURIDAD PHPMYADMIN 🔒              ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para verificar servicios
check_service() {
    if systemctl is-active --quiet "$1"; then
        echo -e "${GREEN}✅${NC} $2: ACTIVO"
        return 0
    else
        echo -e "${RED}❌${NC} $2: INACTIVO"
        return 1
    fi
}

# Función para verificar archivos
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅${NC} $2: EXISTE"
        return 0
    else
        echo -e "${RED}❌${NC} $2: NO ENCONTRADO"
        return 1
    fi
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  VERIFICACIÓN DE SERVICIOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check_service nginx "Nginx"
check_service php8.3-fpm "PHP-FPM 8.3"
check_service fail2ban "Fail2Ban"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  VERIFICACIÓN DE ARCHIVOS DE CONFIGURACIÓN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check_file "/etc/nginx/.htpasswd_phpmyadmin" "Archivo de contraseñas HTTP"
check_file "/etc/nginx/sites-available/conalcaia.conalca.com.co.conf" "Configuración Nginx"
check_file "/etc/fail2ban/filter.d/nginx-phpmyadmin-auth.conf" "Filtro Fail2Ban"
check_file "/etc/fail2ban/jail.d/nginx-phpmyadmin.conf" "Jail Fail2Ban"
check_file "/usr/share/phpmyadmin/index.php" "phpMyAdmin instalado"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  VERIFICACIÓN DE CONFIGURACIÓN NGINX"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if nginx -t 2>&1 | grep -q "syntax is ok"; then
    echo -e "${GREEN}✅${NC} Sintaxis de Nginx: VÁLIDA"
else
    echo -e "${RED}❌${NC} Sintaxis de Nginx: ERROR"
    nginx -t 2>&1 | tail -5
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  ESTADO DE FAIL2BAN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if fail2ban-client status nginx-phpmyadmin-auth > /dev/null 2>&1; then
    echo -e "${GREEN}✅${NC} Jail nginx-phpmyadmin-auth: ACTIVO"
    echo ""
    fail2ban-client status nginx-phpmyadmin-auth | grep -E "(Currently failed|Total failed|Currently banned|Total banned)"
else
    echo -e "${RED}❌${NC} Jail nginx-phpmyadmin-auth: NO ACTIVO"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  ÚLTIMOS ACCESOS A PHPMYADMIN (10 más recientes)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /var/log/nginx/conalca_access.log ]; then
    echo "IP              Fecha/Hora              Código"
    echo "───────────────────────────────────────────────────────────"
    grep "admin-db-secure-2025" /var/log/nginx/conalca_access.log | tail -10 | awk '{print $1 "  " $4 " " $5 "  " $9}' | sed 's/\[//g' | sed 's/\]//g' || echo "Sin accesos registrados"
else
    echo -e "${YELLOW}⚠️${NC}  Log no encontrado"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6️⃣  INTENTOS FALLIDOS (ÚLTIMAS 24 HORAS)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /var/log/nginx/conalca_access.log ]; then
    FAILED_COUNT=$(grep "admin-db-secure-2025.*401" /var/log/nginx/conalca_access.log | wc -l)
    if [ "$FAILED_COUNT" -eq 0 ]; then
        echo -e "${GREEN}✅${NC} Sin intentos fallidos registrados"
    else
        echo -e "${YELLOW}⚠️${NC}  Total de intentos fallidos (401): $FAILED_COUNT"
        echo ""
        echo "IPs con intentos fallidos (Top 10):"
        grep "admin-db-secure-2025.*401" /var/log/nginx/conalca_access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -10 | awk '{print "  " $2 ": " $1 " intentos"}'
    fi
else
    echo -e "${YELLOW}⚠️${NC}  Log no encontrado"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "7️⃣  INTENTOS DE ACCESO A RUTA ANTIGUA BLOQUEADA"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /var/log/nginx/conalca_access.log ]; then
    OLD_PATH_COUNT=$(grep "phpmyadmin.*404" /var/log/nginx/conalca_access.log | wc -l)
    if [ "$OLD_PATH_COUNT" -eq 0 ]; then
        echo -e "${GREEN}✅${NC} Sin intentos de acceso a ruta antigua"
    else
        echo -e "${YELLOW}⚠️${NC}  Total de intentos bloqueados: $OLD_PATH_COUNT"
        echo ""
        echo "IPs que intentaron acceder a ruta antigua (Top 5):"
        grep "phpmyadmin.*404" /var/log/nginx/conalca_access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -5 | awk '{print "  " $2 ": " $1 " intentos"}'
    fi
else
    echo -e "${YELLOW}⚠️${NC}  Log no encontrado"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "8️⃣  PERMISOS DE ARCHIVOS CRÍTICOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /etc/nginx/.htpasswd_phpmyadmin ]; then
    PERMS=$(stat -c "%a" /etc/nginx/.htpasswd_phpmyadmin)
    if [ "$PERMS" == "640" ]; then
        echo -e "${GREEN}✅${NC} Permisos de .htpasswd: 640 (CORRECTO)"
    else
        echo -e "${YELLOW}⚠️${NC}  Permisos de .htpasswd: $PERMS (se recomienda 640)"
    fi
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "9️⃣  INFORMACIÓN DE RATE LIMITING"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Configuración actual:"
echo "  • Límite: 10 peticiones por minuto por IP"
echo "  • Burst: 5 peticiones adicionales permitidas"
echo "  • Ban time (Fail2Ban): 1 hora"
echo "  • Max retry (Fail2Ban): 3 intentos"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔟  USUARIOS HTTP CONFIGURADOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f /etc/nginx/.htpasswd_phpmyadmin ]; then
    echo "Usuarios autorizados:"
    awk -F: '{print "  • " $1}' /etc/nginx/.htpasswd_phpmyadmin
else
    echo -e "${RED}❌${NC} Archivo de usuarios no encontrado"
fi
echo ""

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║           ✅ VERIFICACIÓN DE SEGURIDAD COMPLETADA            ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📍 URL de acceso seguro:"
echo "   https://conalcaia.conalca.com.co/admin-db-secure-2025/"
echo ""
echo "📚 Documentación completa:"
echo "   /home/ubuntu/mcp/conalca/SEGURIDAD_PHPMYADMIN.md"
echo ""
echo "🔄 Para ejecutar este script nuevamente:"
echo "   sudo /home/ubuntu/mcp/conalca/scripts/check-phpmyadmin-security.sh"
echo ""
