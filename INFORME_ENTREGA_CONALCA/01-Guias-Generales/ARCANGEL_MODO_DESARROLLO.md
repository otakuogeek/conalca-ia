# ARCANGEL - GESTIÓN DE MODOS (PRODUCTION/DEVELOPMENT)

## 📋 Estado Actual

**Modo activo:** `development`
**URL:** `https://dev.arcangel.conalca.com.co/api/`
**API Key:** `13b068d6527b4b3d7f16...`

## 🔄 Scripts Disponibles

### 1. Cambiar de Modo
```bash
./switch_arcangel_mode.sh [production|development]
```

**Ejemplos:**
```bash
# Cambiar a producción
./switch_arcangel_mode.sh production

# Cambiar a desarrollo
./switch_arcangel_mode.sh development
```

**Lo que hace:**
- Actualiza `.env` con el modo seleccionado
- Limpia el caché de configuración de Laravel
- Recarga PHP-FPM
- Muestra la configuración activa

### 2. Verificar Estado
```bash
./check_arcangel_status.sh
```

**Muestra:**
- Configuración en `.env`
- Configuración activa en Laravel (cacheada)
- Test de conexión con la API
- Generación de token
- Lista de ciudades disponibles
- Vehículos cercanos (Bogotá)

### 3. Test Completo
```bash
sudo -u www-data php test_arcangel_dev.php
```

**Prueba:**
- Inicialización del servicio
- Generación de token de autenticación
- Obtención de ciudades disponibles
- Consulta de vehículos cercanos

## ⚙️ Configuración Manual

### Editar .env
```bash
nano .env
```

Buscar la sección:
```env
# ============================================================================
# ARCANGEL - INTEGRACIÓN API
# ============================================================================
#ARCANGEL_MODE=production
ARCANGEL_MODE=development
ARCANGEL_BASE_URL=https://arcangel.conalca.com.co/api/
ARCANGEL_BASE_URL_DEV=https://dev.arcangel.conalca.com.co/api/
ARCANGEL_API_KEY=344c9ff2734cc79a7f2ededce7f9dc255fc18f0b
ARCANGEL_API_KEY_DEV=13b068d6527b4b3d7f16627198d5fcdce330d20e
```

### Aplicar Cambios
```bash
# Limpiar caché
sudo -u www-data php artisan config:clear

# Recargar PHP-FPM
sudo /etc/init.d/php-fpm-83 reload

# Verificar
./check_arcangel_status.sh
```

## 🌐 URLs y Credenciales

### Producción
- **URL:** `https://arcangel.conalca.com.co/api/`
- **API Key:** `344c9ff2734cc79a7f2ededce7f9dc255fc18f0b`

### Development
- **URL:** `https://dev.arcangel.conalca.com.co/api/`
- **API Key:** `13b068d6527b4b3d7f16627198d5fcdce330d20e`

## 📝 Notas Importantes

### Sistema de Autenticación
1. El sistema usa **X-API-KEY** para autenticación inicial
2. Se genera un **token temporal** que expira en 60 minutos
3. El token se cachea automáticamente por 55 minutos
4. Laravel maneja la renovación automática del token

### Caché
- Los tokens se guardan en `storage/framework/cache/`
- La configuración se cachea en `bootstrap/cache/config.php`
- Usar `config:clear` después de cambiar `.env`

### Permisos
- El usuario `www-data` debe tener acceso al directorio `storage/`
- Los scripts deben ejecutarse con `sudo -u www-data` para coincidir con permisos de web

## 🚀 Flujo de Trabajo Recomendado

### Para Desarrollo
```bash
# 1. Activar modo desarrollo
./switch_arcangel_mode.sh development

# 2. Verificar estado
./check_arcangel_status.sh

# 3. Desarrollar y probar
# ... tu código aquí ...

# 4. Probar manualmente
sudo -u www-data php test_arcangel_dev.php
```

### Para Producción
```bash
# 1. Probar en desarrollo primero
./check_arcangel_status.sh

# 2. Cambiar a producción
./switch_arcangel_mode.sh production

# 3. Verificar que funciona
./check_arcangel_status.sh

# 4. Monitorear logs
tail -f storage/logs/laravel.log
```

## 🔍 Troubleshooting

### Error: "Invalid token"
```bash
# Limpiar token cacheado
sudo -u www-data php artisan cache:clear

# Verificar API Key
grep ARCANGEL_API_KEY .env
```

### Error: Permission denied en storage/
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### Modo no se aplica
```bash
# Limpiar completamente el caché
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo rm -f bootstrap/cache/config.php

# Recargar PHP-FPM
sudo /etc/init.d/php-fpm-83 reload
```

## 📊 Archivos de Configuración

- **`.env`** - Variables de entorno
- **`config/arcangel.php`** - Configuración de Laravel
- **`app/Services/ArcangelService.php`** - Servicio principal
- **`test_arcangel_dev.php`** - Script de pruebas
- **`switch_arcangel_mode.sh`** - Cambio de modo
- **`check_arcangel_status.sh`** - Diagnóstico

## ✅ Validación Final

Ejecutar este comando para verificar que todo funciona:
```bash
./check_arcangel_status.sh && echo "" && echo "✓ SISTEMA FUNCIONANDO CORRECTAMENTE"
```

Deberías ver:
- ✓ Service created successfully
- ✓ Token generated successfully
- ✓ Cities retrieved successfully
- ✓ Vehicles retrieved successfully
