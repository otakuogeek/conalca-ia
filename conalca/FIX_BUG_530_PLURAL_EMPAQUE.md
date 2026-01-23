# 🐛 FIX BUG #530 - Parte 2: Soporte para Plural en Empaque/Embalaje

## 📋 Resumen
**Bug ID:** #530 (Parte 2)  
**Fecha:** 2026-01-23  
**Status:** ✅ CORREGIDO  
**Related:** FIX_BUG_530_EMBALAJE.md

## 🔍 Problema Reportado

Después de corregir el truncamiento de "embalaje" → "emba", el usuario reportó un nuevo problema:

**Usuario escribe:** "embalajes es contenedor" (plural)  
**Sistema detecta:** NO detectaba el patrón y fallaba  

El sistema no reconocía la forma plural de "empaque**s**" o "embalaje**s**" cuando el usuario intentaba modificar el campo.

### Ejemplos del Bug
```
❌ "embalajes es contenedor"     → No detectado
❌ "los empaques son cajas"       → No detectado  
❌ "cambia los embalajes a tonel" → No detectado
```

## 🔬 Análisis de Causa Raíz

### Patrones Originales (SIN soporte plural)

**Archivo:** `app/Services/MCPAssistantService.php` - Función `extractEmpaque()`

```php
// ❌ PATRÓN ORIGINAL - Solo singular
preg_match('/(?:cambia|cambiar)(?:\s+el)?\s+empaque\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', ...)
preg_match('/(?:el\s+)?(?:empaque|embalaje)\s*(?:es|son|será|sea|queda|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', ...)
```

**Problema:**
- Los patrones buscaban exactamente "empaque" o "embalaje" (singular)
- No incluían el cuantificador `s?` para hacer la "s" opcional
- No soportaban el artículo "los" (plural)

### Test que Falla
```php
Input: "embalajes es contenedor"
Pattern: /(?:empaque|embalaje)\s*(?:es|son)/
Result: NO MATCH ❌
```

## ✅ Solución Implementada

### Cambios en MCPAssistantService.php

**Archivo:** `app/Services/MCPAssistantService.php`  
**Función:** `extractEmpaque()`  
**Líneas:** ~7453-7462

```php
// ✅ PATRÓN ACTUALIZADO - Con soporte plural
// Patrón: "cambia el empaque a X" o "cambia los embalajes a X"
// 🔧 FIX BUG #530-2: Agregar soporte para plural (empaques/embalajes) y artículos (el/los)
elseif (preg_match('/(?:cambia|cambiar)(?:\s+(?:el|los))?\s+(?:empaque|embalaje)s?\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches) ||
    preg_match('/(?:empaque|embalaje)s?\s+cambialo\s+(?:a|por)\s+([a-záéíóúñ]+)/ui', $lowerText, $matches)) {
    $empaqueKeyword = strtolower(trim($matches[1]));
    Log::info('📦 Empaque detectado (patrón "cambia empaque a X")', ['keyword' => $empaqueKeyword]);
}

// Patrón: "empaque es/son X" o "embalajes: X"
// 🔧 FIX BUG #530-2: Agregar soporte para plural (empaques/embalajes) y artículos (el/los)
elseif (preg_match('/(?:(?:el|los)\s+)?(?:empaque|embalaje)s?\s*(?:es|son|será|sea|queda|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui', $lowerText, $matches)) {
    $empaqueKeyword = strtolower(trim($matches[1]));
    Log::info('📦 Empaque detectado (patrón CORRECCIÓN)', ['keyword' => $empaqueKeyword]);
}
```

### Cambios Clave

**1. Agregar `s?` para plural opcional:**
```php
// Antes: /(?:empaque|embalaje)\s+/
// Ahora:  /(?:empaque|embalaje)s?\s+/
//                             ^^
//                        (plural opcional)
```

**2. Agregar soporte para artículos plural "los":**
```php
// Antes: /(?:\s+el)?\s+/
// Ahora:  /(?:\s+(?:el|los))?\s+/
//                     ^^^
//               (el o los opcional)
```

**3. Actualizar ambos patrones:**
- Patrón "cambia empaque a X"
- Patrón "empaque es X"

## 🧪 Tests

### Casos de Prueba
```php
✅ Test #1: "embalaje es contenedor"
   → Valor detectado: "contenedor"

✅ Test #2: "embalajes es contenedor"  (PLURAL)
   → Valor detectado: "contenedor"

✅ Test #3: "el empaque son bultos"
   → Valor detectado: "bultos"

✅ Test #4: "los empaques son cajas"  (PLURAL + ARTÍCULO)
   → Valor detectado: "cajas"

✅ Test #5: "cambia el embalaje a tonel"
   → Valor detectado: "tonel"

✅ Test #6: "cambia los embalajes a sacos"  (PLURAL)
   → Valor detectado: "sacos"

✅ Test #7: "cambia embalaje a cajas"
   → Valor detectado: "cajas"
```

**Resultado:** 7/7 tests PASSED ✅

## 📊 Impacto

### Antes del Fix
```
❌ "embalajes es contenedor"     → No detectado
❌ "los empaques son cajas"       → No detectado
❌ "cambia los embalajes a tonel" → No detectado
✅ "embalaje es contenedor"       → OK (singular)
✅ "el empaque son bultos"        → OK (singular)
```

### Después del Fix
```
✅ "embalajes es contenedor"     → Detecta "contenedor"
✅ "los empaques son cajas"       → Detecta "cajas"
✅ "cambia los embalajes a tonel" → Detecta "tonel"
✅ "embalaje es contenedor"       → Detecta "contenedor"
✅ "el empaque son bultos"        → Detecta "bultos"
```

## 🚀 Deployment

### Archivos Modificados
1. ✅ `app/Services/MCPAssistantService.php` - 2 patrones regex actualizados
2. ✅ PHP-FPM recargado (PID 1155863)

### Comando para Recargar
```bash
sudo pkill -USR2 php-fpm
```

## 📝 Patrones Regex Actualizados

### Patrón 1: "Cambia empaque a X"
```regex
/(?:cambia|cambiar)(?:\s+(?:el|los))?\s+(?:empaque|embalaje)s?\s+(?:a|por)\s+([a-záéíóúñ]+)/ui
```

**Capturas:**
- "cambia empaque a cajas" → "cajas"
- "cambia el empaque a cajas" → "cajas"
- "cambia los empaques a cajas" → "cajas"
- "cambia embalaje a tonel" → "tonel"
- "cambia los embalajes a tonel" → "tonel"

### Patrón 2: "Empaque es/son X"
```regex
/(?:(?:el|los)\s+)?(?:empaque|embalaje)s?\s*(?:es|son|será|sea|queda|:)\s*([a-záéíóúñ\s]+?)(?:\s*[.,;]|\s+y\s+|$)/ui
```

**Capturas:**
- "empaque es cajas" → "cajas"
- "empaques es contenedor" → "contenedor"
- "el empaque son bultos" → "bultos"
- "los empaques son cajas" → "cajas"
- "embalaje: tonel" → "tonel"
- "embalajes son sacos" → "sacos"

## 🔗 Relación con Fix Anterior

Este fix complementa el **FIX_BUG_530_EMBALAJE.md** que corrigió el truncamiento de palabras.

**Timeline de Fixes:**
1. **Fix #530.1:** Palabras protegidas en TextPreprocessor → "embalaje" ya no se trunca a "emba"
2. **Fix #530.2:** Soporte plural en extractEmpaque() → "embalajes" ahora se detecta correctamente

## ✅ Validación Final

### Test Manual
```bash
Usuario: "embalajes es contenedor"
Sistema: Detecta empaque = "CONTENEDOR (1) 20 PIES"
✅ CORRECTO
```

### Verificación en Logs
```
Log: "📦 Empaque detectado (patrón CORRECCIÓN)" 
     {"keyword":"contenedor"}
✅ CORRECTO
```

---

**Fix completado por:** GitHub Copilot Agent  
**Fecha:** 2026-01-23 03:09 UTC  
**PHP-FPM PID:** 1155863
