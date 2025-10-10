# 🔒 SEGURIDAD PHPMYADMIN - CONFIGURACIÓN COMPLETA

## 📋 RESUMEN DE IMPLEMENTACIÓN

Se han implementado **5 capas de seguridad** para proteger el acceso a phpMyAdmin contra ataques, fuerza bruta y accesos no autorizados.

---

## 🔐 CAPAS DE SEGURIDAD IMPLEMENTADAS

### 1️⃣ **URL PERSONALIZADA OCULTA**
- ❌ **URL Antigua (BLOQUEADA):** `https://conalcaia.conalca.com.co/phpmyadmin/`
- ✅ **URL Nueva (SEGURA):** `https://conalcaia.conalca.com.co/admin-db-secure-2025/`

**Beneficio:** Los atacantes no conocen la ruta real de phpMyAdmin.

---

### 2️⃣ **AUTENTICACIÓN HTTP BÁSICA**

Antes de acceder a phpMyAdmin, se requiere autenticación a nivel de servidor web (Nginx).

#### 📝 Credenciales de Acceso HTTP:
```
Usuario: admin_pma
Contraseña: SecureDB@2025!
```

**Ubicación del archivo de contraseñas:**
```bash
/etc/nginx/.htpasswd_phpmyadmin
```

#### 🔧 Comandos para gestionar usuarios:

**Agregar nuevo usuario:**
```bash
sudo htpasswd -b /etc/nginx/.htpasswd_phpmyadmin nuevo_usuario "password_seguro"
```

**Cambiar contraseña de usuario existente:**
```bash
sudo htpasswd -b /etc/nginx/.htpasswd_phpmyadmin admin_pma "NuevaPassword123!"
```

**Eliminar usuario:**
```bash
sudo htpasswd -D /etc/nginx/.htpasswd_phpmyadmin usuario_a_eliminar
```

**Ver usuarios existentes:**
```bash
sudo cat /etc/nginx/.htpasswd_phpmyadmin
```

---

### 3️⃣ **RATE LIMITING (ANTI FUERZA BRUTA)**

Limita las peticiones por IP para prevenir ataques de fuerza bruta:

- **Límite:** 10 peticiones por minuto por IP
- **Burst:** Permite 5 peticiones adicionales en ráfagas
- **Acción:** Rechaza peticiones adicionales con error 503

**Configuración en Nginx:**
```nginx
limit_req_zone $binary_remote_addr zone=phpmyadmin_limit:10m rate=10r/m;
limit_req zone=phpmyadmin_limit burst=5 nodelay;
```

---

### 4️⃣ **FAIL2BAN - BANEO AUTOMÁTICO DE IPS**

Sistema que detecta y bloquea automáticamente IPs con comportamiento sospechoso.

#### 📊 Configuración de Fail2Ban:

**Parámetros:**
- **maxretry:** 3 intentos fallidos
- **findtime:** Ventana de 10 minutos
- **bantime:** Baneo por 1 hora (3600 segundos)

**Archivos de configuración:**
- Filtro: `/etc/fail2ban/filter.d/nginx-phpmyadmin-auth.conf`
- Jail: `/etc/fail2ban/jail.d/nginx-phpmyadmin.conf`

#### 🛠️ Comandos útiles de Fail2Ban:

**Ver estado general:**
```bash
sudo fail2ban-client status
```

**Ver estado del jail de phpMyAdmin:**
```bash
sudo fail2ban-client status nginx-phpmyadmin-auth
```

**Ver IPs baneadas actualmente:**
```bash
sudo fail2ban-client status nginx-phpmyadmin-auth | grep "Banned IP"
```

**Desbanear una IP manualmente:**
```bash
sudo fail2ban-client set nginx-phpmyadmin-auth unbanip 192.168.1.100
```

**Banear una IP manualmente:**
```bash
sudo fail2ban-client set nginx-phpmyadmin-auth banip 203.0.113.50
```

**Ver logs de Fail2Ban:**
```bash
sudo tail -f /var/log/fail2ban.log
```

**Reiniciar Fail2Ban:**
```bash
sudo systemctl restart fail2ban
```

---

### 5️⃣ **RESTRICCIÓN POR IP (OPCIONAL - ACTUALMENTE DESACTIVADA)**

Puedes limitar el acceso solo a IPs o rangos de IPs confiables.

#### 📝 Para activar restricción por IP:

1. Editar configuración de Nginx:
```bash
sudo nano /etc/nginx/sites-available/conalcaia.conalca.com.co.conf
```

2. Buscar la sección de `/admin-db-secure-2025` y descomentar las líneas:
```nginx
location ^~ /admin-db-secure-2025 {
    # Descomenta estas líneas:
    allow 192.168.1.100;    # Tu IP de oficina
    allow 203.0.113.0/24;   # Rango de IPs de tu empresa
    allow 198.51.100.50;    # Otra IP confiable
    deny all;               # Bloquear todo lo demás
    
    # ... resto de configuración
}
```

3. Recargar Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### 🌐 Para encontrar tu IP actual:
```bash
curl ifconfig.me
```

---

## 🔐 CABECERAS DE SEGURIDAD HTTP

Se aplican las siguientes cabeceras de seguridad para phpMyAdmin:

| Cabecera | Valor | Protección |
|----------|-------|------------|
| `X-Frame-Options` | DENY | Previene clickjacking |
| `X-Content-Type-Options` | nosniff | Previene MIME sniffing |
| `X-XSS-Protection` | 1; mode=block | Protección contra XSS |
| `Referrer-Policy` | strict-origin-when-cross-origin | Control de referrer |
| `Content-Security-Policy` | Restrictivo | Previene inyección de código |

---

## 📍 ACCESO A PHPMYADMIN

### ✅ **Forma CORRECTA de acceder:**

1. Abrir navegador en modo incógnito (recomendado)
2. Ir a: `https://conalcaia.conalca.com.co/admin-db-secure-2025/`
3. Aparecerá popup de autenticación HTTP:
   - Usuario: `admin_pma`
   - Contraseña: `SecureDB@2025!`
4. Click "Aceptar" o "Sign In"
5. Se mostrará la pantalla de login de phpMyAdmin:
   - Usuario BD: `admin`
   - Contraseña BD: `Admin@2025`

### ❌ **Rutas BLOQUEADAS (retornan 404):**
- `https://conalcaia.conalca.com.co/phpmyadmin/`
- Cualquier variante de la ruta antigua

---

## 📊 MONITOREO Y LOGS

### 🔍 Ver intentos de acceso a phpMyAdmin:

**Ver logs de acceso de Nginx:**
```bash
sudo tail -f /var/log/nginx/conalca_access.log | grep "admin-db-secure-2025"
```

**Ver intentos fallidos (401):**
```bash
sudo grep "admin-db-secure-2025.*401" /var/log/nginx/conalca_access.log | tail -20
```

**Ver intentos de acceso a ruta antigua bloqueada:**
```bash
sudo grep "phpmyadmin.*404" /var/log/nginx/conalca_access.log | tail -20
```

**Ver errores de Nginx:**
```bash
sudo tail -f /var/log/nginx/conalca_error.log
```

### 📈 Estadísticas de seguridad:

**Contar intentos fallidos por IP:**
```bash
sudo grep "admin-db-secure-2025.*401" /var/log/nginx/conalca_access.log | awk '{print $1}' | sort | uniq -c | sort -rn
```

**Contar intentos de acceso a ruta antigua:**
```bash
sudo grep "phpmyadmin.*404" /var/log/nginx/conalca_access.log | wc -l
```

---

## 🛡️ PROTECCIÓN ADICIONAL DE DIRECTORIOS

Se bloquea el acceso a los siguientes directorios sensibles de phpMyAdmin:

- `/libraries/` - Librerías internas
- `/setup/` - Asistente de configuración
- `/config/` - Archivos de configuración
- `/tmp/` - Archivos temporales
- Todos los archivos y directorios ocultos (`.`)

**Intento de acceso retorna:** 404 Not Found

---

## 🚨 RESPUESTA A INCIDENTES

### Si detectas acceso no autorizado:

1. **Revisar IPs baneadas por Fail2Ban:**
```bash
sudo fail2ban-client status nginx-phpmyadmin-auth
```

2. **Revisar últimos accesos exitosos:**
```bash
sudo grep "admin-db-secure-2025.*200" /var/log/nginx/conalca_access.log | tail -50
```

3. **Cambiar contraseña HTTP inmediatamente:**
```bash
sudo htpasswd -b /etc/nginx/.htpasswd_phpmyadmin admin_pma "NuevaPasswordSegura2025!"
sudo systemctl reload nginx
```

4. **Activar restricción por IP (ver sección 5️⃣ arriba)**

5. **Cambiar URL personalizada:**
   - Editar `/etc/nginx/sites-available/conalcaia.conalca.com.co.conf`
   - Cambiar `/admin-db-secure-2025` por `/nueva-ruta-secreta-2025`
   - `sudo nginx -t && sudo systemctl reload nginx`

6. **Aumentar severidad de Fail2Ban:**
```bash
sudo nano /etc/fail2ban/jail.d/nginx-phpmyadmin.conf
```
Cambiar:
```ini
maxretry = 2          # De 3 a 2 intentos
bantime = 86400       # De 1 hora a 24 horas
```
Luego:
```bash
sudo systemctl restart fail2ban
```

---

## 🔄 BACKUP DE CONFIGURACIONES

### Archivos críticos de seguridad:

```bash
/etc/nginx/sites-available/conalcaia.conalca.com.co.conf    # Config principal
/etc/nginx/.htpasswd_phpmyadmin                              # Contraseñas HTTP
/etc/fail2ban/filter.d/nginx-phpmyadmin-auth.conf           # Filtro Fail2Ban
/etc/fail2ban/jail.d/nginx-phpmyadmin.conf                  # Jail Fail2Ban
```

### Crear backup de configuraciones:

```bash
sudo mkdir -p /root/backups/security-phpmyadmin
cd /root/backups/security-phpmyadmin
sudo cp /etc/nginx/sites-available/conalcaia.conalca.com.co.conf nginx-config-$(date +%Y%m%d).conf
sudo cp /etc/nginx/.htpasswd_phpmyadmin htpasswd-$(date +%Y%m%d).txt
sudo cp /etc/fail2ban/filter.d/nginx-phpmyadmin-auth.conf fail2ban-filter-$(date +%Y%m%d).conf
sudo cp /etc/fail2ban/jail.d/nginx-phpmyadmin.conf fail2ban-jail-$(date +%Y%m%d).conf
```

---

## ✅ VERIFICACIÓN DE SEGURIDAD

### Script de verificación rápida:

```bash
#!/bin/bash
echo "🔒 VERIFICACIÓN DE SEGURIDAD PHPMYADMIN"
echo "=========================================="
echo ""
echo "1️⃣ Autenticación HTTP:"
[ -f /etc/nginx/.htpasswd_phpmyadmin ] && echo "✅ Archivo de contraseñas existe" || echo "❌ FALTA archivo de contraseñas"
echo ""
echo "2️⃣ Fail2Ban:"
sudo systemctl is-active --quiet fail2ban && echo "✅ Fail2Ban activo" || echo "❌ Fail2Ban inactivo"
sudo fail2ban-client status nginx-phpmyadmin-auth > /dev/null 2>&1 && echo "✅ Jail phpMyAdmin activo" || echo "❌ Jail phpMyAdmin inactivo"
echo ""
echo "3️⃣ Nginx:"
sudo systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
sudo nginx -t > /dev/null 2>&1 && echo "✅ Configuración Nginx válida" || echo "❌ Error en configuración Nginx"
echo ""
echo "4️⃣ Accesos recientes (últimos 10):"
sudo grep "admin-db-secure-2025" /var/log/nginx/conalca_access.log | tail -10 | awk '{print $1, $4, $9}' || echo "Sin accesos registrados"
echo ""
echo "5️⃣ IPs baneadas actualmente:"
sudo fail2ban-client status nginx-phpmyadmin-auth | grep "Currently banned" || echo "Ninguna IP baneada"
```

### Guardar y ejecutar:
```bash
sudo nano /root/check-phpmyadmin-security.sh
sudo chmod +x /root/check-phpmyadmin-security.sh
sudo /root/check-phpmyadmin-security.sh
```

---

## 📞 INFORMACIÓN ADICIONAL

### Servicios y puertos:
- **Nginx:** Puerto 443 (HTTPS)
- **PHP-FPM:** Socket Unix `/run/php/php8.3-fpm.sock`
- **Fail2Ban:** Puerto N/A (gestiona iptables)

### Tiempos y límites:
- **Rate Limit:** 10 peticiones/minuto por IP
- **Burst:** 5 peticiones adicionales
- **Ban Time:** 1 hora (3600 segundos)
- **Find Time:** 10 minutos (600 segundos)
- **Max Retry:** 3 intentos fallidos

### Recursos útiles:
- Documentación Nginx: https://nginx.org/en/docs/
- Documentación Fail2Ban: https://www.fail2ban.org/
- Documentación phpMyAdmin: https://www.phpmyadmin.net/docs/

---

## 🎯 RESUMEN DE COMANDOS IMPORTANTES

```bash
# Ver estado de seguridad
sudo fail2ban-client status nginx-phpmyadmin-auth
sudo tail -f /var/log/nginx/conalca_access.log | grep "admin-db-secure-2025"

# Cambiar contraseña HTTP
sudo htpasswd -b /etc/nginx/.htpasswd_phpmyadmin admin_pma "NuevaPassword"
sudo systemctl reload nginx

# Desbanear IP
sudo fail2ban-client set nginx-phpmyadmin-auth unbanip 192.168.1.100

# Reiniciar servicios
sudo systemctl restart fail2ban
sudo systemctl reload nginx

# Verificar configuración
sudo nginx -t
sudo fail2ban-client status
```

---

## 📅 MANTENIMIENTO

### Tareas periódicas recomendadas:

**Semanalmente:**
- Revisar logs de acceso fallidos
- Verificar IPs baneadas (investigar patrones)
- Comprobar que Fail2Ban esté activo

**Mensualmente:**
- Cambiar contraseña HTTP de acceso
- Rotar logs de Nginx
- Actualizar filtros de Fail2Ban si es necesario

**Trimestralmente:**
- Considerar cambiar la URL personalizada
- Revisar y actualizar lista de IPs permitidas
- Auditoría de logs históricos

---

**Documento creado:** Octubre 2025  
**Última actualización:** Octubre 4, 2025  
**Versión:** 1.0  
**Estado:** ✅ TODAS LAS CAPAS DE SEGURIDAD ACTIVAS
