# FIX: Validación de Pesos, Tara y Eliminación de Duplicación

## Fecha
2026-01-23

## Problema Reportado

Usuario reportó tres issues críticos:
1. **Validar pesos relacionados a su ruta correspondiente** - Los pesos no estaban asociados correctamente a cada ruta
2. **Aplicar lógica de tara correctamente** - La tara no se estaba sumando/restando adecuadamente por ruta
3. **Evitar duplicación en modificaciones** - Cada vez que se editaba una ruta, se duplicaban todas

## Root Cause Analysis

### Issue 1: Campos Planos Mezclados con Rutas
```json
{
  "0": {"origen": "BOGOTA", "destino": "CALI", "peso_kg": 9400, ...},
  "1": {"origen": "MONTERIA", "destino": "CARTAGENA", "peso_kg": 15400, ...},
  "origen": "BOGOTA",      // ❌ Campo plano duplicado
  "destino": "CALI",        // ❌ Campo plano duplicado
  "peso_kg": 9400           // ❌ Campo plano duplicado
}
```

**Problema**: Al guardar múltiples rutas en `group_cotizations.extracted_data`, se estaban guardando también campos planos que correspondían a la primera ruta. Esto causaba confusión al frontend y problemas de validación.

### Issue 2: Lógica de Tara
- La lógica de tara ya existía y funcionaba correctamente en `extractRouteDataFromText()`
- Cada ruta tenía su flag `incluye_tara` y el peso se ajustaba automáticamente
- El problema era la mezcla de campos planos que hacía parecer que la tara no se aplicaba correctamente

### Issue 3: Merge Duplicaba Rutas
- El merge inteligente ya estaba implementado (líneas 3112-3145)
- Sin embargo, los campos planos confundían el sistema
- No se estaba validando que multi-ruta NO debe tener campos planos

## Solución Implementada

### Fix en MCPAssistantService.php (Líneas 3112-3160)

**ANTES:**
```php
// Merge: campos planos se combinan (nuevos sobrescriben existentes)
$mergedFlat = array_merge($existingFlat, $newFlat);

// Combinar rutas + campos planos
$mergedData = $mergedRoutes + $mergedFlat;
```

**DESPUÉS:**
```php
// 🔧 CRÍTICO: Si hay múltiples rutas (2+), NO guardar campos planos
// Los campos planos son solo para ruta única
$isMultiRoute = count($newRoutes) >= 2 || count($existingRoutes) >= 2;

// Merge: campos planos SOLO si es ruta única
$mergedFlat = [];
if (!$isMultiRoute) {
    $mergedFlat = array_merge($existingFlat, $newFlat);
} else {
    Log::info('🚫 Multi-ruta detectada: NO guardando campos planos', [
        'rutas_totales' => count($mergedRoutes),
        'campos_planos_rechazados' => array_keys($newFlat)
    ]);
}

// Combinar rutas + campos planos (si aplica)
$mergedData = $mergedRoutes + $mergedFlat;
```

### Cambios Clave

1. **Detección de Multi-Ruta**: `$isMultiRoute = count($newRoutes) >= 2 || count($existingRoutes) >= 2`
2. **Filtrado de Campos Planos**: Solo se guardan si es ruta única
3. **Logging Mejorado**: Se registra cuando se rechazan campos planos

## Validación de Tara por Ruta

### Lógica Existente (Ya Funcionaba)

En `extractRouteDataFromText()` líneas 6950-7015:

```php
// Detectar "no incluye tara", "sin tara", "peso neto"
if ($detectoNoIncluyeTara) {
    $route['incluye_tara'] = false;
    // SUMAR 3400 kg automáticamente
    if (isset($route['peso_kg']) && $route['peso_kg'] > 0) {
        $pesoOriginal = $route['peso_kg'];
        $route['peso_kg'] = $pesoOriginal + 3400;
        Log::info('📦 TARA SUMADA automáticamente');
    }
} elseif ($detectoYaIncluyeTara) {
    $route['incluye_tara'] = true;
    // NO sumar - el peso ya incluye la tara
} else {
    // Por defecto: SUMAR tara
    $route['incluye_tara'] = false;
    if (isset($route['peso_kg']) && $route['peso_kg'] > 0) {
        $pesoOriginal = $route['peso_kg'];
        $route['peso_kg'] = $pesoOriginal + 3400;
        Log::info('📦 TARA SUMADA automáticamente (default)');
    }
}
```

### Validación

Cada ruta tiene:
- `peso_kg`: Peso en kilogramos (CON tara si `incluye_tara = false`)
- `incluye_tara`: `false` = se sumó tara (peso_kg = peso_neto + 3400)
- `incluye_tara`: `true` = NO se sumó tara (peso_kg = peso_bruto)

**Ejemplo:**
```json
{
  "0": {
    "origen": "BOGOTA",
    "destino": "CALI",
    "peso_kg": 9400,        // 6000 (neto) + 3400 (tara)
    "incluye_tara": false
  },
  "1": {
    "origen": "MONTERIA",
    "destino": "CARTAGENA",
    "peso_kg": 15400,       // 12000 (neto) + 3400 (tara)
    "incluye_tara": false
  }
}
```

## Tests Ejecutados

### Test 1: Validación de Estructura
```bash
php test_peso_tara_validation.php
```

**Resultados:**
- ✅ Total de rutas: 2
- ✅ Total de campos planos: 0
- ✅ Rutas duplicadas: NO
- ✅ Validación de tara Ruta #0: CORRECTA
- ✅ Validación de tara Ruta #1: CORRECTA

### Test 2: No Duplicación al Editar
```bash
# Editar ruta #0: cambiar peso y producto
# Resultado: Rutas después de edición: 2
# ✅ NO se duplicaron las rutas
```

### Test 3: Limpieza de Grupo 600
```bash
php clean_group_600.php
```

**Antes:**
- Rutas: 2
- Campos planos: 5 (origen, destino, peso_kg, producto, valor_declarado)

**Después:**
- Rutas: 2
- Campos planos: 0
- ✅ Estructura limpia

## Impacto

### Frontend (ChatController)
- Ya tenía lógica para separar rutas de campos planos (líneas 509-535)
- Ahora recibe estructura 100% limpia desde el backend
- No más confusión con datos mezclados

### Backend (MCPAssistantService)
- Datos de múltiples rutas guardados SOLO como array de rutas
- Campos planos rechazados automáticamente para multi-ruta
- Logs detallados para debugging

### Base de Datos
- `group_cotizations.extracted_data` ahora tiene estructura consistente:
  - **Ruta única**: `{"origen": "...", "destino": "...", "peso_kg": ...}`
  - **Multi-ruta**: `{"0": {...}, "1": {...}, "2": {...}}`
  - ❌ **NO MÁS**: `{"0": {...}, "1": {...}, "origen": "...", "peso_kg": ...}`

## Beneficios

1. **Datos por Ruta**: Cada ruta tiene sus propios datos aislados
2. **No Duplicación**: Sistema merge inteligente funciona correctamente
3. **Tara Correcta**: Cada ruta calcula su tara independientemente
4. **Frontend Limpio**: Recibe estructura consistente sin campos planos
5. **Debugging Fácil**: Logs claros indican cuando se rechazan campos planos

## Comandos de Verificación

```bash
# Verificar estructura de un grupo
php check_last_group.php

# Test completo de validación
php test_peso_tara_validation.php

# Limpiar grupo específico
php clean_group_600.php
```

## Archivos Modificados

1. **MCPAssistantService.php** (líneas 3112-3160)
   - Agregado: Detección de multi-ruta
   - Agregado: Filtrado de campos planos
   - Mejorado: Logging para debugging

## Notas Técnicas

### Estructura Correcta Multi-Ruta
```json
{
  "0": {
    "ruta_numero": 1,
    "origen": "BOGOTA",
    "destino": "CALI",
    "producto": "CAFE",
    "peso_kg": 9400,
    "incluye_tara": false,
    "valor_declarado": 18000000,
    "empaque": "CONTENEDOR"
  },
  "1": {
    "ruta_numero": 2,
    "origen": "MONTERIA",
    "destino": "CARTAGENA",
    "producto": "MAIZ",
    "peso_kg": 15400,
    "incluye_tara": false,
    "valor_declarado": 25000000,
    "empaque": "CONTENEDOR"
  }
}
```

### Validación de Tara
- **Peso sin tara**: `peso_kg - 3400`
- **Validación**: `(peso_kg - 3400) % 1000 === 0` (debe ser múltiplo de 1000)
- **Flag**: `incluye_tara = false` indica que se sumó tara

## Conclusión

✅ **TODOS LOS PROBLEMAS RESUELTOS**

1. ✅ Pesos relacionados correctamente a cada ruta
2. ✅ Lógica de tara aplicada independientemente por ruta
3. ✅ NO se duplican rutas al modificar
4. ✅ Estructura de datos limpia y consistente
5. ✅ Frontend recibe datos correctos

El sistema ahora maneja múltiples rutas de forma robusta, con datos aislados por ruta, sin duplicación y con lógica de tara correcta.
