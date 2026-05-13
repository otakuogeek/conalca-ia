# 🧪 GUÍA DE PRUEBAS DEL SISTEMA DE COTIZACIONES

## 📋 Resumen

Este documento describe las herramientas de prueba disponibles para validar el sistema de extracción de cotizaciones.

---

## 🛠️ Herramientas Disponibles

### 1. **validate_system.sh** - Validación General
Script automático que verifica el estado general del sistema.

```bash
./validate_system.sh
```

**Qué valida:**
- ✅ Archivos críticos existen
- ✅ FIX 10 y FIX 11 están en el código
- ✅ Tests de regresión (10 casos)
- ✅ Calidad de últimos 10 grupos en BD
- ✅ PHP-FPM ejecutándose
- ✅ Timestamp de última modificación

**Cuándo usar:**
- Después de cada deploy
- Diariamente (recomendado en cronjob)
- Después de modificar MCPAssistantService.php

---

### 2. **RouteDetectionRegressionTest.php** - Tests de Regresión
Pruebas automatizadas de patrones de detección de rutas.

```bash
php tests/RouteDetectionRegressionTest.php
```

**Qué valida:**
- ✅ Formato estructurado: "Origen: X, Destino: Y"
- ✅ Formato natural: "de X a Y"
- ✅ Multi-ruta válida
- ✅ Bug #508 (caso problemático)
- ✅ Diferentes formatos de prompt

**Cuándo usar:**
- Antes de commit de cambios en MCPAssistantService.php
- Para verificar que el bug #508 no regresó

---

### 3. **MonitorCalidad.php** - Monitor de Producción
Analiza grupos existentes en la base de datos buscando problemas.

```bash
# Analizar últimos 50 grupos
php tests/MonitorCalidad.php 50

# Analizar desde fecha específica
php tests/MonitorCalidad.php 100 "2026-01-22 22:00:00"
```

**Qué detecta:**
- ❌ Ciudades inválidas
- ❌ Múltiples rutas sospechosas (>3)
- ❌ Patrones del bug #508: "SU TAMANO", "REFORZADA"
- ❌ Productos como empaques: "CARTON", "MADERA"
- ❌ Ciudades vacías (sin origen/destino)

**Cuándo usar:**
- Semanalmente para auditoría de calidad
- Después de reportes de usuarios
- Para identificar grupos problemáticos

---

### 4. **PruebasIntegralesChat.php** - Tests de Integración
Simula el proceso completo de extracción (sin crear grupos en BD).

```bash
php tests/PruebasIntegralesChat.php
```

**Qué prueba:**
- 11 escenarios diferentes
- Creación de rutas simples y múltiples
- Extracción de todos los campos (peso, producto, valor)
- Modificación de campos existentes
- Agregación de rutas a grupos

**Nota:** Este script **simula** la extracción pero **NO** crea grupos reales en la BD.

---

### 5. **test_live.sh** - Pruebas EN VIVO ⚠️
Script interactivo que **crea grupos REALES** en la base de datos.

```bash
./test_live.sh
```

**Qué hace:**
- ⚠️ Crea grupos de cotización REALES
- ✅ Valida extracción completa end-to-end
- ✅ Simula comportamiento real del usuario
- ✅ Verifica que el bug #508 no se presenta

**Casos de prueba:**
1. Ruta simple estructurada con todos los campos
2. Múltiples rutas en un solo prompt
3. Formato completo (el que causaba bug #508)

**⚠️ ADVERTENCIA:** Este script crea grupos reales. Los grupos tendrán timestamp único para identificación.

**Cuándo usar:**
- Para validación final antes de producción
- Cuando quieras ver el comportamiento real
- Para reproducir problemas reportados por usuarios

---

## 📊 Interpretación de Resultados

### Códigos de Salida

| Script | Código 0 | Código 1 | Código 2 |
|--------|----------|----------|----------|
| validate_system.sh | ✅ Todo OK | ⚠️ Advertencias | ❌ Problemas críticos |
| RouteDetectionRegressionTest.php | ✅ >90% | ⚠️ 75-90% | ❌ <75% |

### Umbrales de Calidad

**validate_system.sh:**
- 🎉 **100%**: Sistema perfecto
- ✅ **80-99%**: Funcional con advertencias menores
- ⚠️ **60-79%**: Problemas, requiere atención
- ❌ **<60%**: Crítico, requiere corrección inmediata

**MonitorCalidad.php:**
- ✅ **>80% OK**: Excelente
- ⚠️ **70-80% OK**: Aceptable, monitorear
- ❌ **<70% OK**: Problemas, investigar

---

## 🔄 Flujo de Trabajo Recomendado

### Antes de Hacer Cambios en el Código

```bash
# 1. Crear baseline
php tests/RouteDetectionRegressionTest.php > baseline_antes.txt

# 2. Hacer tus cambios en MCPAssistantService.php

# 3. Ejecutar tests nuevamente
php tests/RouteDetectionRegressionTest.php > baseline_despues.txt

# 4. Comparar
diff baseline_antes.txt baseline_despues.txt

# 5. Si todo OK, reiniciar PHP-FPM
sudo pkill -9 php-fpm && sudo /www/server/php/83/sbin/php-fpm

# 6. Validar sistema completo
./validate_system.sh
```

### Después de un Deploy

```bash
# 1. Validación rápida
./validate_system.sh

# 2. Si pasa, hacer prueba en vivo
./test_live.sh

# 3. Monitorear primeros grupos creados
php tests/MonitorCalidad.php 10
```

### Ante un Reporte de Usuario

```bash
# 1. Identificar el grupo problemático
GROUP_ID=520  # Ejemplo

# 2. Ver detalles en BD
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
      -u ai_transport_admin -p \
      -e "SELECT * FROM cotizacion_models WHERE group_cotization_id = $GROUP_ID"

# 3. Ejecutar monitor para buscar patrón
php tests/MonitorCalidad.php 50

# 4. Si es bug conocido, verificar que el fix esté activo
grep -A5 "FIX 11" app/Services/MCPAssistantService.php

# 5. Agregar caso a RouteDetectionRegressionTest.php
```

---

## 🎯 Escenarios de Prueba Cubiertos

### Formato de Prompts

| Formato | Ejemplo | Cubierto por |
|---------|---------|--------------|
| Estructurado | "Origen: Bogotá, Destino: Cali" | ✅ TEST 1 |
| Natural | "de Medellín a Barranquilla" | ✅ TEST 2 |
| Multi-ruta | "de X a Y y de A a B" | ✅ TEST 4 |
| Completo | Con peso, producto, valor, etc. | ✅ TEST 3 |
| Bug #508 | Formato que causaba 8 rutas falsas | ✅ TEST especial |

### Campos Extraídos

| Campo | Validado | Tests |
|-------|----------|-------|
| Origen | ✅ | TEST 1, 2, 3, 4 |
| Destino | ✅ | TEST 1, 2, 3, 4 |
| Peso | ✅ | TEST 3, 7 |
| Peso sin tara | ✅ | TEST 7 |
| Tara | ✅ | TEST 7 |
| Producto | ✅ | TEST 6 |
| Valor declarado | ✅ | TEST 5 |
| Empaque filtrado | ✅ | TEST 6 (no debe aparecer CARTON/MADERA) |

### Operaciones

| Operación | Validado | Tests |
|-----------|----------|-------|
| Crear ruta simple | ✅ | TEST 1, 2 |
| Crear múltiples rutas | ✅ | TEST 4 |
| Modificar origen | ✅ | TEST 8 |
| Modificar destino | ✅ | Similar a TEST 8 |
| Modificar peso | ✅ | TEST 9 |
| Modificar producto | ✅ | TEST 10 |
| Agregar ruta a grupo | ✅ | TEST 11 |

---

## 🚨 Problemas Comunes y Soluciones

### ❌ TEST FAIL: "FIX 11 NO ENCONTRADO"

**Causa:** El código fue sobreescrito o el comentario del FIX 11 fue eliminado.

**Solución:**
```bash
# Verificar manualmente
grep -n "detectMultipleRouteCities" app/Services/MCPAssistantService.php | grep -A10 "patronOrigenDestinoEstructurado"

# Si no aparece, restaurar desde Git o aplicar manualmente
```

### ❌ Tests de regresión <75%

**Causa:** Cambios en el código rompieron funcionalidad.

**Solución:**
1. Ver qué tests específicos fallaron
2. Si falla test del bug #508: código fue sobreescrito, restaurar FIX 11
3. Revisar último commit: `git log -1 --stat`

### ❌ MonitorCalidad.php muestra <70% OK

**Causa:** Grupos recientes tienen problemas de extracción.

**Solución:**
```bash
# Ver grupos problemáticos específicos
php tests/MonitorCalidad.php 50 | grep "❌ Grupo"

# Revisar patrón común
# Si muchos tienen "SU TAMANO": Bug #508 regresó
# Si son errores diversos: Problema con prompts de usuarios
```

### ⚠️ Advertencia: "Múltiples ciudades válidas"

**Causa:** Prompt ambiguo con varias ciudades mencionadas.

**Solución:** Este es un edge case conocido. No es crítico si los demás tests pasan.

---

## 📅 Calendario de Pruebas Recomendado

### Diario (Automatizado)
```bash
# Agregar a crontab
0 2 * * * /home/ubuntu/conalca/conalca/validate_system.sh >> /var/log/route_validation_$(date +\%Y\%m\%d).log 2>&1
```

### Semanal (Manual)
```bash
# Lunes por la mañana
cd /home/ubuntu/conalca/conalca
php tests/MonitorCalidad.php 100 > reports/calidad_semanal_$(date +%Y%m%d).txt
```

### Después de Cada Deploy
```bash
./validate_system.sh
# Si pasa, entonces:
./test_live.sh
```

### Antes de Release
```bash
# Ejecutar TODOS los tests
./validate_system.sh
php tests/RouteDetectionRegressionTest.php
php tests/PruebasIntegralesChat.php
./test_live.sh  # Solo si tienes BD de staging
```

---

## 📝 Registro de Cambios en Tests

| Fecha | Cambio | Razón |
|-------|--------|-------|
| 2026-01-22 | Agregado test específico para bug #508 | Prevenir regresión |
| 2026-01-22 | Creado MonitorCalidad.php | Auditoría de producción |
| 2026-01-22 | Creado validate_system.sh | Validación completa automatizada |

---

## 🔗 Referencias

- [SOLUCION_COMPLETA_BUG_RUTAS_FALSAS.md](SOLUCION_COMPLETA_BUG_RUTAS_FALSAS.md) - Documentación del bug #508
- [FIX_FORMATO_ORIGEN_DESTINO.md](FIX_FORMATO_ORIGEN_DESTINO.md) - FIX 10 documentado
- [PREVENCION_REGRESION.md](PREVENCION_REGRESION.md) - Guía de prevención

---

**Última actualización:** 2026-01-22  
**Versión:** 1.0  
**Mantenedor:** Sistema de Calidad Conalca
