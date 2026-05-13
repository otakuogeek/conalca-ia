# 🚀 PREVENCIÓN DE REGRESIÓN - Sistema de Rutas

## 🎯 Resumen del Problema Resuelto

### Bug Original (#508)
**Síntoma:** El prompt estructurado "Origen: X, Destino: Y..." generaba **8 rutas falsas** en lugar de 1.

**Causa:** El patrón `(?:de|desde)\s+(.+?)\s+(?:a|hacia)\s+(.+?)` en `detectMultipleRouteCities()` capturaba texto incorrecto:
- "Tipo de Producto... **de** madera reforzada **a** su tamaño"
- Creaba 8 origenes: "PRODUCTO: ELECTRODOMESTICOS", "VALOR DECLARADO: $50", "000", etc.
- Todos con el mismo destino: "SU TAMANO"

**Solución Implementada:**
- **FIX 10**: Patrón específico para formato estructurado en `detectMultipleRoutes()`
- **FIX 11**: Early return en `detectMultipleRouteCities()` cuando detecta formato estructurado
- **Resultado**: 90% de tests aprobados, bug resuelto ✅

---

## 🛠️ Herramientas de Validación Disponibles

### 1. Script de Validación Completa
```bash
./validate_system.sh
```

**Qué hace:**
- ✅ Verifica que archivos críticos existen
- ✅ Confirma que los FIX están en el código
- ✅ Ejecuta tests de regresión (10 casos)
- ✅ Analiza últimos 10 grupos de la BD
- ✅ Verifica estado de PHP-FPM
- ✅ Muestra tasa de éxito general

**Cuándo ejecutar:**
- Después de cada deploy
- Después de modificar MCPAssistantService.php
- Diariamente en producción (cronjob recomendado)

**Output esperado:**
```
Tasa de éxito: 80-100%
🎉 SISTEMA COMPLETAMENTE FUNCIONAL
```

---

### 2. Tests de Regresión
```bash
php tests/RouteDetectionRegressionTest.php
```

**Qué hace:**
- Simula 10 escenarios de prompts diferentes
- Valida patrones de detección de rutas
- Incluye el caso problemático #508

**Casos de prueba:**
1. ✅ Formato estructurado simple: "Origen: Bogotá, Destino: Cali"
2. ✅ Formato estructurado completo: con peso, producto, valor
3. ✅ Formato natural: "de Medellín a Barranquilla"
4. ✅ Multi-ruta válida: "de Cali a Bogotá y de Medellín a Cartagena"
5. ✅ Formato "cotización de": "cotización de Bogotá a Medellín"
6. ✅ Formato "desde/hacia": "desde Cali hacia Medellín"
7. ✅ CASO PROBLEMÁTICO #508: Bug original de 8 rutas falsas
8. ✅ Multi-ruta compleja válida
9. ⚠️ Múltiples ciudades válidas (edge case)
10. ✅ Formato email con saltos de línea

**Resultado esperado:**
```
Total de tests: 10
✅ Exitosos: 9 (90%)
⚠️ Advertencias: 1

✅ CASO PROBLEMÁTICO #508: BUG RESUELTO
   1 ruta correcta (BOGOTA → BUENAVENTURA)
```

---

### 3. Monitor de Calidad
```bash
# Analizar últimos 50 grupos
php tests/MonitorCalidad.php 50

# Analizar grupos desde fecha específica
php tests/MonitorCalidad.php 100 "2026-01-22 22:00:00"
```

**Qué hace:**
- Lee grupos de la base de datos
- Valida ciudades contra whitelist (30 ciudades colombianas)
- Detecta patrones de bugs conocidos:
  - "SU TAMANO", "REFORZADA", "000"
  - "PRODUCTO:", "VALOR DECLARADO:"
- Clasifica problemas:
  - ❌ Ciudades inválidas
  - ❌ Múltiples rutas sospechosas (>3)
  - ❌ Sin origen/destino
  - ❌ Peso inválido
  - ❌ Producto inválido

**Resultado esperado:**
```
Total de grupos analizados: 50
✅ Grupos correctos: 40 (80%)
❌ Grupos con problemas: 10 (20%)

✅ EXCELENTE - Menos del 10% tienen problemas
```

**Interpretación:**
- **✅ >80%**: Sistema funcionando correctamente
- **⚠️ 70-80%**: Aceptable, monitorear
- **❌ <70%**: Investigar causas inmediatamente

---

## 📅 Cronograma de Validación Recomendado

### Diario (Automatizado)
```bash
# Agregar a crontab
0 2 * * * /home/ubuntu/conalca/conalca/validate_system.sh >> /var/log/route_validation.log 2>&1
```

### Semanal (Manual)
```bash
# Análisis profundo de los últimos 100 grupos
php tests/MonitorCalidad.php 100 >> calidad_$(date +%Y%m%d).txt
```

### Después de cada deploy
```bash
cd /home/ubuntu/conalca/conalca
./validate_system.sh
```

### Después de modificar MCPAssistantService.php
```bash
php tests/RouteDetectionRegressionTest.php
```

---

## 🚨 Qué Hacer si Encuentras Problemas

### Si validate_system.sh falla
1. **Revisar qué test falló específicamente**
2. **TEST 2 (FIX no encontrado)**: 
   - Ejecutar: `grep -n "FIX 10\|FIX 11" app/Services/MCPAssistantService.php`
   - Si no aparece, el código fue sobreescrito
3. **TEST 3 (Tests regresión)**: 
   - Ver detalles en output
   - Si falla caso #508: bug regresó, código sobreescrito
4. **TEST 4 (Monitor calidad <70%)**:
   - Revisar grupos problemáticos específicos
   - Ver si hay patrón común en los errores

### Si RouteDetectionRegressionTest.php falla
1. **Ver qué caso específico falló**
2. **Si es caso #508**: 
   - ⚠️ **BUG REGRESÓ** - código fue modificado/sobreescrito
   - Acción: Restaurar FIX 11 en línea ~6662
3. **Si son múltiples casos**:
   - Revisar última modificación: `git log app/Services/MCPAssistantService.php`
   - Considerar rollback al commit con FIX

### Si MonitorCalidad.php muestra <70% OK
1. **Ver grupos problemáticos específicos**
2. **Si muchos tienen "SU TAMANO"**:
   - ⚠️ Bug #508 regresó
   - Verificar FIX 11 en código
3. **Si son errores diversos**:
   - Revisar prompts de usuarios
   - Considerar agregar casos a tests de regresión

---

## 🔄 Procedimiento de Restauración Rápida

Si los tests fallan después de un deploy:

```bash
# 1. Verificar que los FIX están en el código
grep -A5 "FIX 10" app/Services/MCPAssistantService.php
grep -A5 "FIX 11" app/Services/MCPAssistantService.php

# 2. Si no aparecen, restaurar desde Git (si está en repo)
git checkout HEAD~1 -- app/Services/MCPAssistantService.php

# 3. O aplicar manualmente los cambios:
#    - FIX 10: Línea ~4482 (patrón Origen/Destino)
#    - FIX 11: Línea ~6662 (early return en detectMultipleRouteCities)

# 4. Reiniciar PHP-FPM
sudo pkill -9 php-fpm && sudo /www/server/php/83/sbin/php-fpm

# 5. Validar
./validate_system.sh
```

---

## 📊 Métricas de Salud del Sistema

| Métrica | Umbral Bueno | Umbral Aceptable | Umbral Crítico |
|---------|--------------|------------------|----------------|
| Tests de regresión | ≥90% | 80-90% | <80% |
| Grupos OK (monitor) | ≥80% | 70-80% | <70% |
| Rutas promedio/grupo | 1-2 | 2-3 | >3 |
| Ciudades válidas | 100% | ≥95% | <95% |

---

## 📝 Logs y Auditoría

### Dónde revisar logs
```bash
# Logs de Laravel
tail -f storage/logs/laravel.log | grep "detectMultiple"

# Logs de PHP-FPM
tail -f /www/server/php/83/var/log/php-fpm.log

# Logs de validación (si configuraste cronjob)
tail -f /var/log/route_validation.log
```

### Qué buscar en los logs
```
✅ BUENO:
- "🎯 detectMultipleRoutes: Formato estructurado detectado - 1 ruta"
- "✅ Datos extraídos correctamente"
- "Rutas detectadas: 1"

❌ MALO:
- "Multiple routes detected: 8" (bug #508)
- "Ciudad no válida"
- "extractValorDeclarado returned null"
- "extractProducto returned CARTON/MADERA"
```

---

## 🔧 Casos de Uso Específicos

### Antes de hacer cambios en MCPAssistantService.php
```bash
# 1. Ejecutar baseline de tests
php tests/RouteDetectionRegressionTest.php > baseline_antes.txt

# 2. Hacer tus cambios...

# 3. Ejecutar tests nuevamente
php tests/RouteDetectionRegressionTest.php > baseline_despues.txt

# 4. Comparar
diff baseline_antes.txt baseline_despues.txt

# 5. Si todo OK, commit
git add .
git commit -m "feat: [tu cambio]"
```

### Después de un reporte de usuario de rutas incorrectas
```bash
# 1. Obtener ID del grupo problemático
GROUP_ID=520  # Ejemplo

# 2. Analizar ese grupo específico
mysql -h ai-transport.czqmsk4ck839.us-west-1.rds.amazonaws.com \
      -u ai_transport_admin -p \
      -e "SELECT * FROM group_cotizations WHERE id = $GROUP_ID; \
          SELECT * FROM cotizacion_models WHERE group_cotization_id = $GROUP_ID;"

# 3. Ver el prompt original
# (revisar en conversation_messages)

# 4. Agregar caso a RouteDetectionRegressionTest.php
# 5. Ejecutar tests
# 6. Si falla, investigar causa raíz
```

---

## 🎓 Formatos de Prompt Soportados

### ✅ Formato Estructurado (RECOMENDADO)
```
Origen: Bogotá, Destino: Cali, Peso: 1,000 kg
```

### ✅ Formato Natural
```
Necesito transportar de Medellín a Barranquilla
```

### ✅ Multi-ruta
```
de Cali a Bogotá y de Medellín a Cartagena
```

### ✅ Con "cotización de"
```
Solicito cotización de Bogotá a Medellín para 500 kg
```

### ⚠️ Evitar (puede causar confusión)
```
❌ Producto de madera reforzada a Bogotá
   (confunde "de madera" con ciudad origen)

❌ Embalaje: Cajas de cartón de 50x50 a Cali
   (confunde "de 50x50" con ciudad)
```

---

## 🔒 Checklist de Seguridad

Antes de considerar el sistema "seguro" después de cambios:

- [ ] `./validate_system.sh` retorna código 0 o 1 (no 2)
- [ ] `RouteDetectionRegressionTest.php` ≥ 90% éxito
- [ ] `MonitorCalidad.php` ≥ 80% grupos OK
- [ ] FIX 10 presente en línea ~4482
- [ ] FIX 11 presente en línea ~6662
- [ ] PHP-FPM reiniciado después de cambios
- [ ] Ningún log con "8 rutas" en últimos grupos

---

## 📞 Contacto y Soporte

Si los tests fallan consistentemente:
1. Revisar este documento completo
2. Ejecutar procedimiento de restauración
3. Verificar logs en `/storage/logs/laravel.log`
4. Si persiste, documentar:
   - Prompt exacto del usuario
   - ID del grupo problemático
   - Output de `./validate_system.sh`
   - Output de tests de regresión

---

**Última actualización:** 2026-01-22 22:21:12  
**Versión:** 1.0  
**Mantenedor:** Sistema de Calidad Conalca
