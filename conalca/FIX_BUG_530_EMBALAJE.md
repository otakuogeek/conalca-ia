# 🐛 FIX BUG #530 - Embalaje/Empaque Truncado

## 📋 Resumen
**Bug ID:** #530  
**Fecha:** 2026-01-23  
**Status:** ✅ CORREGIDO  

## 🔍 Problema Reportado

Usuario reportó que al escribir "**embalaje es tonel**" o "**empaque en cajas**", el sistema estaba cortando la palabra a "**emba**" o "**empa**", causando que:
1. No reconociera el cambio en modificaciones
2. Perdiera información del tipo de empaque
3. No actualizara correctamente el campo

### Ejemplo del Bug
```
Usuario escribe: "destino bucaramanga, embalaje es tonel y vehiculo turbo"
Sistema procesa:  "destino bucaramanga, emba es tonel y vehículo turbo"
                                        ^^^^
                                      TRUNCADO
```

## 🔬 Análisis de Causa Raíz

### Investigación
Se trazó el flujo del texto a través de `TextPreprocessorService.php`:

**Flujo Original (CON BUG):**
```
1. Input:    "destino bucaramanga, embalaje es tonel"
2. Paso 5:   separateKeywords() → "emb a la je es tonel"
             ^^^^^^^^^^^^^
             'ala' dentro de "emb-ALA-je" se separó
3. Paso 3:   fixCommonMistakes() → "emba es tonel"
             ^^^^
             'laje' se eliminó, dejando solo "emba"
```

### Causa Identificada

**Causa #1:** `separateKeywords()` en línea ~354
- Buscaba keyword `'ala' => ' a la '` dentro de "emb**ala**je"
- Regex: `/([a-záéíóúñ])(ala)([a-záéíóúñ])?/ui`
- Match: "emb" + "ala" + "je" → "emb a la je"

**Causa #2:** `fixCommonMistakes()` en línea ~341
- Array contenía: `'laje' => ''`
- Eliminaba "laje" de "emb a la je"
- Resultado: "emba"

## ✅ Solución Implementada

### Cambio #1: Proteger palabras completas en `separateKeywords()`

**Archivo:** `app/Services/TextPreprocessorService.php`  
**Líneas:** ~354-404

```php
private static function separateKeywords(string $text): string
{
    // 🔧 FIX BUG #530: Palabras protegidas que NO deben separarse
    $protectedWords = [
        'embalaje',
        'embalajes',
        'embalada',
        'embaladas',
        'embalado',
        'embalados',
    ];
    
    // Marcar palabras protegidas con placeholders temporales
    $placeholders = [];
    foreach ($protectedWords as $i => $word) {
        $placeholder = "___PROTECTED_{$i}___";
        $pattern = '/\b' . preg_quote($word, '/') . '\b/ui';
        if (preg_match($pattern, $text, $matches)) {
            $placeholders[$placeholder] = $matches[0];
            $text = preg_replace($pattern, $placeholder, $text);
        }
    }
    
    // ... proceso normal de separación de keywords ...
    
    // Restaurar palabras protegidas
    foreach ($placeholders as $placeholder => $original) {
        $text = str_replace($placeholder, $original, $text);
    }
    
    return $text;
}
```

**Lógica:**
1. Antes de separar keywords, marca palabras protegidas con placeholders
2. Los placeholders (ej: `___PROTECTED_0___`) no contienen substrings problemáticos
3. Después de procesar, restaura las palabras originales

### Cambio #2: Eliminar entrada peligrosa de `$commonMistakes`

**Archivo:** `app/Services/TextPreprocessorService.php`  
**Líneas:** ~167-176

```php
private static array $commonMistakes = [
    // 🔧 FIX: Embalaje debe corregirse ANTES de eliminar "la je"
    'embala je' => 'embalaje',
    'embalage' => 'embalaje',
    'enbalaje' => 'embalaje',
    'embalajes' => 'embalajes',
    // 🔧 FIX BUG #530: "laje" eliminado porque causaba problemas
    // ELIMINADO: 'laje' => '',
```

**Razón:** La entrada `'laje' => ''` causaba que "embalaje" se truncara a "emba"

## 🧪 Tests

### Test Creado
**Archivo:** `test_fix_bug_530_embalaje.php`

### Casos de Prueba
```php
✅ Test #1: "destino bucaramanga, embalaje es tonel y vehiculo turbo"
   → Resultado: "destino bucaramanga, embalaje es tonel y vehículo turbo"

✅ Test #2: "origen bogota, empaque en cajas"
   → Resultado: "origen bogotá, empaque en cajas"

✅ Test #3: "cambia el embalaje a bultos"
   → Resultado: "cambia el embalaje a bultos"

✅ Test #4: "el empaque son sacos"
   → Resultado: "el empaque son sacos"
```

**Resultado:** 4/4 tests PASSED ✅

## 📊 Impacto

### Antes del Fix
- ❌ "embalaje" → "emba"
- ❌ "empaque" → NO afectado (no contiene "ala")
- ❌ MCPAssistantService.extractEmpaque() fallaba
- ❌ DataExtractionService no reconocía el campo

### Después del Fix
- ✅ "embalaje" se mantiene completo
- ✅ "empaque" funciona correctamente
- ✅ extractEmpaque() detecta correctamente
- ✅ Sistema reconoce modificaciones de empaque/embalaje

## 🚀 Deployment

### Pasos Realizados
1. ✅ Modificado `TextPreprocessorService.php` (2 cambios)
2. ✅ Creado test de validación
3. ✅ Ejecutado tests: 4/4 PASSED
4. ✅ Recargado PHP-FPM (PID 842443)
5. ✅ Documentado en `FIX_BUG_530_EMBALAJE.md`

### Comando para Recargar PHP-FPM
```bash
sudo pkill -USR2 php-fpm
```

## 📝 Notas Técnicas

### Palabras Protegidas
La lista de palabras protegidas puede extenderse en el futuro:
```php
$protectedWords = [
    'embalaje', 'embalajes',
    'embalada', 'embaladas', 'embalado', 'embalados',
    // Agregar más si se detectan problemas similares
];
```

### Keywords Problemáticos
Los siguientes keywords pueden causar problemas similares:
- `'ala'` → Afecta "emb**ala**je"
- `'aje'` → Podría afectar otras palabras
- Cualquier substring corto usado en `$keywordsThatNeedSpace`

### Recomendación
Si se agregan nuevos keywords cortos (<4 caracteres) a `$keywordsThatNeedSpace`, verificar que no causen falsos positivos con palabras comunes.

## ✅ Validación Final

### Test en Producción
```bash
cd /home/ubuntu/conalca/conalca
php test_fix_bug_530_embalaje.php
```

**Resultado Esperado:**
```
✅ Passed: 4
❌ Failed: 0
🎉 ¡TODOS LOS TESTS PASARON! Bug #530 CORREGIDO.
```

### Verificación Manual
1. Usuario envía: "destino bucaramanga, embalaje es tonel"
2. Sistema debe procesar: "destino bucaramanga, embalaje es tonel"
3. Campo `empaque` debe contener: "TONEL"
4. ✅ **Bug corregido**

## 📌 Referencias

- **Archivo Principal:** `app/Services/TextPreprocessorService.php`
- **Función Afectada:** `separateKeywords()`, `fixCommonMistakes()`
- **Logs:** Buscar "TextPreprocessor: Cambios realizados" en `storage/logs/laravel.log`
- **Test:** `test_fix_bug_530_embalaje.php`

---

**Fix completado por:** GitHub Copilot Agent  
**Fecha:** 2026-01-23 02:52 UTC
