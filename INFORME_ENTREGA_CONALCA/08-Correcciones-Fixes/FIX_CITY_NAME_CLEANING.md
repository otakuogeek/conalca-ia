# Fix: Limpieza de Ciudades en Extracción de Datos

## Problema Identificado (Cotización 332)
El sistema estaba extrayendo nombres de ciudades con palabras contextuales agregadas:

**Input del usuario:**
```
"ruta de cali a cucuta para llevar 20 toneladas de purina"
```

**Extracción incorrecta (ANTES):**
- `ciudad_origen`: "RUTA DE CALI"
- `ciudad_destino`: "CUCUTA PARA LLEVAR"

**Extracción correcta (DESPUÉS):**
- `ciudad_origen`: "CALI"
- `ciudad_destino`: "CUCUTA"

## Causa Raíz
La función `extractCiudades()` en `MCPAssistantService.php` usaba un patrón regex que capturaba hasta 3 palabras consecutivas antes y después de "a" sin filtrar palabras conectoras/contextual es como:
- "ruta de", "viaje de", "origen de", "destino de"
- "para llevar", "para cargar", "para descargar"

## Solución Implementada

### 1. Limpieza de Prefijos y Sufijos
Se agregó limpieza automática de palabras conectoras usando regex con alternativas:

```php
$patronPrefijos = '/^(?:distribuci[oó]n\s+nacionalizada\s+(?:de\s+)?|importaci[oó]n\s+(?:de\s+)?|exportaci[oó]n\s+(?:de\s+)?|ruta\s+(?:de\s+)?|viaje\s+(?:de\s+)?|destino\s+(?:de\s+)?|origen\s+(?:de\s+)?|de\s+la\s+|desde\s+|hacia\s+|de\s+)/ui';
$patronSufijos = '/(?:\s+para\s+(?:llevar|cargar|descargar)|\s+a\s+las\s+|\s+por\s+|\s+con\s+).*/ui';

$origen = preg_replace($patronPrefijos, '', $origen);
$destino = preg_replace($patronPrefijos, '', $destino);
$origen = preg_replace($patronSufijos, '', $origen);
$destino = preg_replace($patronSufijos, '', $destino);
```

### 2. Fix de Validación de Palabras Prohibidas
Se cambió la validación de palabras prohibidas de búsqueda de subcadenas a comparación exacta:

**ANTES (causaba falsos positivos):**
```php
$origenIsForbidden = in_array($origenLower, $forbiddenWords) || 
                     array_filter($forbiddenWords, fn($w) => strpos($origenLower, $w) !== false);
```
Problema: "medellin" contiene "del" → rechazado incorrectamente

**DESPUÉS (comparación exacta):**
```php
$origenIsForbidden = in_array($origenLower, $forbiddenWords);
$destinoIsForbidden = in_array($destinoLower, $forbiddenWords);
```

## Archivos Modificados
1. `/home/ubuntu/conalca/conalca/app/Services/MCPAssistantService.php`
   - Líneas ~5238-5246: Limpieza de prefijos y sufijos
   - Líneas ~5275-5277: Fix de validación de palabras prohibidas

## Pruebas de Validación

### Casos de Prueba Exitosos
✅ "ruta de cali a cucuta para llevar" → CALI, CUCUTA
✅ "de bogota a medellin" → BOGOTA, MEDELLIN
✅ "desde barranquilla hasta cartagena" → BARRANQUILLA, CARTAGENA
✅ "viaje de pereira a manizales" → PEREIRA, MANIZALES
✅ "distribucion nacionalizada de ibague a neiva" → IBAGUE, NEIVA

## Casos Especiales
- Frases con múltiples conectoras se limpian secuencialmente
- Palabras prohibidas solo se aplican a palabras completas, no subcadenas
- Prefijos específicos tienen prioridad sobre prefijos genéricos (ej: "ruta de" antes que "de")

## Impacto
- ✅ Mejora la precisión de extracción de ciudades
- ✅ Evita falsos negativos por palabras contextuales
- ✅ Previene rechazos incorrectos por subcadenas (ej: "medellin" con "del")
- ✅ Compatible con diferentes formas de expresar rutas en español

## Fecha de Implementación
2026-01-19

## Relacionado
- Issue reportado en cotización #332
- Conversación con usuario sobre extracción de datos
