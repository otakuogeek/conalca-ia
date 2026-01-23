# FIX: Bug "contenedor" → "contenedorr" (doble R)

## 🐛 Problema Reportado

Cuando el usuario intenta modificar el campo de empaque a "contenedor", el sistema estaba guardando "contenedorr" (con doble R) o incluso "contenedorrr" (triple R).

**Evidencia:**
```
Input:  "el embalaje es contenedor"
Output: "el embalaje es contenedorr"  ❌

Input:  "ambas en contenedorr de 40" (ya tenía 2 r's)
Output: "ambas en contenedorrr de 40" (ahora tiene 3 r's)  ❌
```

## 🔍 Diagnóstico

### Paso 1: Identificar dónde ocurre
Agregué logging en cada paso del preprocesamiento:

```
✅ Después de normalizeWhitespace: "ambas en contenedor"
✅ Después de expandAbbreviations: "ambas en contenedor"
❌ Después de fixCommonMistakes: "ambas en contenedorr"  ← AQUÍ OCURRE
```

### Paso 2: Identificar la causa

El array `$commonMistakes` tenía:

```php
'contenedo' => 'contenedor',  // Para corregir typo "contenedo"
```

El patrón usado era:
```php
$pattern = '/' . preg_quote($wrong, '/') . '/ui';  // SIN límites de palabra
```

**Problema:** Este patrón hacía match DENTRO de "contenedor":
- "cont**enedo**r" contiene "contenedo"
- Lo reemplaza: "cont" + "contenedor" + "r"
- Resultado: "contenedorr"

### Paso 3: Verificación

```php
$text = "ambas en contenedor";
$pattern = "/" . preg_quote("contenedo", "/") . "/ui";
preg_match($pattern, $text, $matches);
// ✅ Match: "contenedo" (dentro de "contenedoR")
```

## ✅ Solución

Agregué límites de palabra (`\b`) al patrón en el método `fixCommonMistakes()`:

```php
private static function fixCommonMistakes(string $text): string
{
    foreach (self::$commonMistakes as $wrong => $correct) {
        // 🔧 FIX: Agregar límites de palabra para evitar matches parciales
        // Sin \b: "contenedo" hace match en "contenedoR" → "contenedorr" ❌
        // Con \b: "contenedo" NO hace match en "contenedor" ✅
        $pattern = '/\b' . preg_quote($wrong, '/') . '\b/ui';
        $text = preg_replace($pattern, $correct, $text);
    }
    
    // Limpiar espacios extra que puedan quedar
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}
```

**Ahora:**
- `\bcontenedo\b` NO hace match en "contenedor" (no hay límite de palabra después de "contenedo")
- `\bcontenedo\b` SÍ hace match en "el contenedo es..." (palabra completa)

## 🧪 Tests de Verificación

```bash
✅ "vehiculo es turbo y el embalaje es contenedor" → "vehículo es turbo y el embalaje es contenedor"
✅ "el embalaje es contenedor de 40 pies" → "el embalaje es contenedor de 40 pies"
✅ "ambas en contenedor" → "ambas en contenedor"
✅ "ambas en contenedorde 40" → "ambas en contenedor de 40"
✅ "necesito contenedor maritimo" → "necesito contenedor maritimo"
✅ "es un contenedor de 40 pies" → "es un contenedor de 40 pies"
✅ "contenedor refrigerado" → "contenedor refrigerado"
✅ "embalaje contenedor" → "embalaje contenedor"
✅ "contenedor" → "contenedor"
```

**Todos los tests pasan ✅ - No se agrega "r" extra**

## 📂 Archivo Modificado

- `conalca/app/Services/TextPreprocessorService.php` (línea 363-376)
  - Método: `fixCommonMistakes()`
  - Cambio: Agregado `\b` al inicio y final del patrón

## ⚠️ Notas Importantes

1. **No se corrigen "contenedorr" existentes:** Si ya hay datos con "contenedorr" en la base de datos, NO se corrigen automáticamente. Solo se previene que se agreguen más "r".

2. **Correcciones de typos aún funcionan:** El límite de palabra permite corregir:
   - "el contenedo es grande" → "el contenedor es grande" ✅
   - "contenedo de 40 pies" → "contenedor de 40 pies" ✅

3. **Protección contra otros typos similares:** Todos los reemplazos en `$commonMistakes` ahora usan límites de palabra, previniendo bugs similares.

## 🔄 Deployment

```bash
# 1. Regenerar autoloader
composer dump-autoload -o

# 2. Recargar PHP-FPM
sudo /etc/init.d/php-fpm-83 reload

# 3. Verificar en producción
# Intentar editar empaque a "contenedor" → Debe guardar "CONTENEDOR" no "CONTENEDORR"
```

## 📊 Impacto

- ✅ FIX aplicado
- ✅ Tests pasando
- ✅ PHP-FPM recargado
- ⏳ Pendiente: Corregir manualmente grupos existentes con "CONTENEDORR" en la base de datos

---

**Fecha:** 23 Enero 2026  
**Bug ID:** TextPreprocessor contenedor doble R  
**Status:** RESUELTO ✅
