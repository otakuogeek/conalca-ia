# FIX - Duplicación de Rutas al Editar

## Problema
Al editar una ruta en una cotización con múltiples rutas, el sistema duplicaba TODAS las rutas en lugar de actualizar solo la ruta editada.

**Ejemplo - Grupo 598:**
- **Inicial**: 2 rutas (BOGOTA→CALI, MONTERIA→CARTAGENA)
- **Después de editar Ruta 2**: 4 rutas (2 originales + 2 duplicadas)

### Datos Observados
```json
{
  "0": { "origen": "BOGOTA", "destino": "CALI", ... },     // Original
  "1": { "origen": "MONTERIA", "destino": "CARTAGENA", ... }, // Original editada
  "2": { "origen": "BOGOTA", "destino": "CALI", ... },     // DUPLICADO
  "3": { "origen": "MONTERIA", "destino": "CARTAGENA", ... } // DUPLICADO
}
```

## Causa Raíz

En `MCPAssistantService.php` línea 3118, el merge de `extracted_data` usaba `array_merge()`:

```php
$existingData = json_decode($group->extracted_data ?? '{}', true) ?? [];
$mergedData = array_merge($existingData, $extractedData);
```

**Problema de `array_merge()`:**
- Cuando se usa con arrays que tienen índices numéricos, `array_merge()` **re-indexa los valores** en lugar de preservar los índices.
- Resultado:
  ```php
  $existingData = [0 => [...], 1 => [...]];  // 2 rutas existentes
  $extractedData = [0 => [...], 1 => [...]]; // 2 rutas actualizadas
  $mergedData = [0 => [...], 1 => [...], 2 => [...], 3 => [...]]; // 4 rutas (DUPLICADO)
  ```

### Secuencia de Eventos (Grupo 598)
```
11:31:23 - Se guardaron 2 rutas inicialmente (routes_count: 2)
11:31:38 - Usuario edita ruta 1 (selected_route_index: 1)
11:31:44 - Merge duplica índices: [0,1] + [0,1] = [0,1,2,3]
11:31:45 - Se guardan 4 rutas (routes_count: 4) ← DUPLICACIÓN
```

## Solución

### Merge Inteligente
Implementamos un merge que:
1. **Separa rutas (índices numéricos) de campos planos (strings)**
2. **Rutas**: Las nuevas SOBRESCRIBEN las existentes con mismo índice
3. **Campos planos**: Se combinan normalmente con `array_merge()`

```php
// Separar datos por tipo
$existingRoutes = [];
$existingFlat = [];
foreach ($existingData as $key => $value) {
    if (is_numeric($key)) {
        $existingRoutes[$key] = $value;
    } else {
        $existingFlat[$key] = $value;
    }
}

$newRoutes = [];
$newFlat = [];
foreach ($extractedData as $key => $value) {
    if (is_numeric($key)) {
        $newRoutes[$key] = $value;
    } else {
        $newFlat[$key] = $value;
    }
}

// Merge: nuevas rutas SOBRESCRIBEN rutas existentes con mismo índice
$mergedRoutes = $existingRoutes;
foreach ($newRoutes as $idx => $routeData) {
    $mergedRoutes[$idx] = $routeData; // Sobrescribir/agregar
}

// Merge: campos planos se combinan
$mergedFlat = array_merge($existingFlat, $newFlat);

// Combinar rutas + campos planos
$mergedData = $mergedRoutes + $mergedFlat;
```

### Comportamiento Correcto

**Editar ruta existente:**
```php
// Existentes: [0 => ruta1, 1 => ruta2]
// Nuevos: [1 => ruta2_editada]
// Resultado: [0 => ruta1, 1 => ruta2_editada] ✅ Sin duplicar
```

**Agregar nueva ruta:**
```php
// Existentes: [0 => ruta1, 1 => ruta2]
// Nuevos: [2 => ruta3]
// Resultado: [0 => ruta1, 1 => ruta2, 2 => ruta3] ✅
```

**Editar múltiples rutas:**
```php
// Existentes: [0 => ruta1, 1 => ruta2, 2 => ruta3]
// Nuevos: [0 => ruta1_editada, 2 => ruta3_editada]
// Resultado: [0 => ruta1_editada, 1 => ruta2, 2 => ruta3_editada] ✅
```

## Logs Mejorados

Antes:
```
📦 Datos extraídos guardados en GRUPO (con merge)
  - campos_previos: [0,1,"origen","destino",...]
  - campos_nuevos: [0,1,"origen","destino",...]
  - campos_finales: [0,1,2,3,"origen","destino",...]  ← Confuso
```

Después:
```
📦 Datos extraídos guardados en GRUPO (con merge inteligente)
  - rutas_previas: 2
  - rutas_nuevas: 2
  - rutas_finales: 2  ← Claro, sin duplicación
  - campos_planos: ["origen","destino","peso_kg",...]
```

## Testing

### Caso 1: Editar Ruta Existente
```
1. Crear cotización con 2 rutas
2. Editar ruta 1 (cambiar destino)
3. Verificar que sigue habiendo 2 rutas (no 4)
4. Verificar que la ruta 1 tiene el nuevo destino
```

### Caso 2: Agregar Nueva Ruta
```
1. Crear cotización con 1 ruta
2. Solicitar agregar segunda ruta
3. Verificar que hay 2 rutas (no 3)
```

### Caso 3: Editar y Agregar Simultáneamente
```
1. Crear cotización con 2 rutas
2. Editar ruta 0 y agregar ruta 2
3. Verificar que hay 3 rutas totales
4. Verificar que ruta 0 está editada y ruta 1 intacta
```

## Archivos Modificados
- `app/Services/MCPAssistantService.php`
  - Líneas 3112-3145: Implementación de merge inteligente

## Impacto
- ✅ Evita duplicación de rutas al editar
- ✅ Preserva índices numéricos de rutas
- ✅ Mejora claridad de logs
- ✅ Compatible con edición múltiple y adición de rutas

## Problema Adicional Detectado

En el Grupo 598 también se detectó un **origen mal capturado**:
```
Origen: "CALI Y DESTINO BGA"  ← Incorrecto
```

Esto sugiere que el usuario escribió algo como "cali y destino bga" y el sistema lo tomó como nombre de ciudad. Este es un problema diferente relacionado con la detección de ciudades en mensajes de edición.

**Posible causa**: El patrón de detección de ciudades no está filtrando frases descriptivas que contienen "y destino" como parte del texto de edición del usuario.

**Solución futura**: Agregar validación adicional en `extractRouteDataFromText()` para rechazar nombres de ciudad que contengan "destino", "origen", "ruta" como palabras internas.
