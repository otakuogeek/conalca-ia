# FIX: Soporte para formato "Origen: X, Destino: Y"

## Problema Identificado

**Grupo #508** - El usuario usó el formato estructurado:
```
Origen: bogotá, Destino: Buenaventura, Peso: 2,500 kilogramos...
```

Pero el sistema generó **8 rutas falsas** en lugar de 1:
1. `PRODUCTO: ELECTRODOMESTICOS` → `SU TAMANO`
2. `VALOR DECLARADO: $50` → `SU TAMANO`  
3. `000` → `SU TAMANO`
4. `000 COP` → `SU TAMANO`
5. `EMBA: CAJAS DE MADERA REFORZADA` → `SU TAMANO`
6. `TIPO DE VEHICULO: CONTENEDORRR DE 40 PIES` → `SU TAMANO`
7. `PARA EXPORTACION MARITIMA...` → `SU TAMANO`
8. `MANEJO ESPECIALIZADO DEBIDO` → `SU TAMANO`

## Causa Raíz

El patrón de detección de rutas solo soportaba:
- ✅ "cotización de X a Y"
- ✅ "de X a Y" / "desde X hacia Y"
- ❌ **"Origen: X, Destino: Y"** (NO SOPORTADO)

Como no encontraba el formato esperado, el sistema fallback aplicaba patrones más generales que capturaban texto basura.

## Solución Implementada

**Archivo**: `app/Services/MCPAssistantService.php`

**Líneas modificadas**: ~4473-4510

### Cambio 1: Agregar patrón para "Origen: X, Destino: Y"

```php
// 🆕 FIX 10: TAMBIÉN detectar formato "Origen: X, Destino: Y"
$patronOrigenDestino = '/\bOrigen:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})\s*,\s*Destino:\s*([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+){0,2})/ui';

if (preg_match_all($patronOrigenDestino, $text, $matchesOD, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    // Usar formato "Origen: X, Destino: Y"
    $matchesCiudades = $matchesOD;
} elseif (preg_match_all($patronCiudadACiudad, $text, $matchesCA, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
    // Usar formato "de X a Y"
    $matchesCiudades = $matchesCA;
}
```

### Cambio 2: Retornar incluso con 1 sola ruta

Antes:
```php
if (count($rutasDetectadas) >= 2) {
    return $rutasDetectadas;
}
```

Ahora:
```php
if (count($rutasDetectadas) >= 1) {
    return $rutasDetectadas;
}
```

## Validación del Fix

### Test del patrón:

```bash
php test_origen_destino_pattern.php
```

**Resultado**:
- ✅ Test #1 (Grupo 508): Detecta **1 ruta** correctamente (Bogotá → Buenaventura)
- ✅ Test #2 (formato "de X a Y"): Detecta **1 ruta** correctamente
- ✅ Test #3 (Multi-ruta): Detecta **2 rutas** correctamente

## Formatos Ahora Soportados

1. **Estructurado**: `Origen: bogotá, Destino: Buenaventura, Peso: 2,500 kg`
2. **Natural**: `de bogotá a buenaventura`
3. **Formal**: `desde Bogotá hacia Buenaventura`
4. **Multi**: `cotización de Cali a Barranquilla, 5 toneladas. cotización de Bogotá a Medellín, 3 toneladas`

## Impacto

- ✅ Grupos con formato "Origen: X, Destino: Y" ahora se procesan correctamente
- ✅ Evita creación de rutas falsas por mal parseo
- ✅ Mantiene compatibilidad con formatos anteriores

## Próximos Pasos

1. Limpiar grupo #508 y volver a procesar
2. Validar con nuevos prompts usando formato estructurado
3. Actualizar documentación de API para usuarios

## Comandos Ejecutados

```bash
# Limpiar caché
php artisan config:clear
php artisan route:clear

# Test del patrón
php test_origen_destino_pattern.php
```

## Fecha

2026-01-22 16:45
