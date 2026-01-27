# FIX: Normalización de Campos en Rutas Múltiples

## Fecha: 2026-01-26
## Problema Reportado

Al editar una ruta, el backend retornaba datos con **nombres de campos inconsistentes**:

```json
{
  "rutas": [
    {
      "peso": 12000,           // ❌ Usa 'peso' en lugar de 'peso_kg'
      "valor": 95000000,       // ❌ Usa 'valor' en lugar de 'valor_declarado'
      "vehiculo": "TRACTOCAMIÓN"
    },
    {
      "peso_kg": 3800,         // ✅ Usa 'peso_kg'
      "valor_declarado": 22000000,  // ✅ Usa 'valor_declarado'
      "vehiculo": "TURBO"
    }
  ]
}
```

**Consecuencia**: El frontend no reconocía correctamente qué ruta estaba siendo editada porque los nombres de los campos no coincidían.

## Causa Raíz

### 1. Backend sin normalización
El endpoint `/api/chat/update-extracted-data` guardaba los datos **tal como llegaban** sin normalizar los nombres de los campos. Esto causaba que:
- Ruta 1 tuviera `peso`, ruta 2 tuviera `peso_kg`, ruta 3 tuviera `pesoMercancia`
- Lo mismo con `valor`, `valor_declarado`, `valorMercancia`
- Y `vehiculo`, `claseVehiculo`

### 2. Frontend sin normalización al recibir
El frontend esperaba nombres específicos (`peso_kg`, `valor_declarado`) pero recibía diferentes alias, causando que:
- No pudiera identificar correctamente la ruta editada
- Perdiera datos al mapear los campos
- Mostrara valores incorrectos o vacíos

## Solución Implementada

### 1. Backend: Normalización al Guardar (ChatController.php)

**Agregado en líneas 854-893**:

```php
// 🔧 NORMALIZAR nombres de campos antes de guardar
$normalizedData = [];
foreach ($extractedData as $ruta) {
    $normalized = [
        'origen' => $ruta['origen'] ?? null,
        'destino' => $ruta['destino'] ?? null,
        // Normalizar peso: peso_kg, peso, pesoMercancia
        'peso_kg' => $ruta['peso_kg'] ?? $ruta['peso'] ?? $ruta['pesoMercancia'] ?? null,
        'cantidad' => $ruta['cantidad'] ?? $ruta['cantidadMercancia'] ?? null,
        // Normalizar valor: valor_declarado, valor, valorMercancia
        'valor_declarado' => $ruta['valor_declarado'] ?? $ruta['valor'] ?? $ruta['valorMercancia'] ?? null,
        'vehiculo' => $ruta['vehiculo'] ?? $ruta['claseVehiculo'] ?? null,
        'empaque' => $ruta['empaque'] ?? null,
        'empaque_id' => $ruta['empaque_id'] ?? null,
        'producto' => $ruta['producto'] ?? $ruta['tipo_producto'] ?? null,
        'contenedor' => $ruta['contenedor'] ?? null,
        'incluye_tara' => $ruta['incluye_tara'] ?? false
    ];
    // Remover nulls
    $normalizedData[] = array_filter($normalized, fn($v) => $v !== null);
}

// Guardar con datos normalizados
if (count($normalizedData) > 1) {
    $multiRutaData = [
        'multi_ruta' => true,
        'total_rutas' => count($normalizedData),
        'rutas' => $normalizedData  // ← Ahora con campos normalizados
    ];
    $group->extracted_data = json_encode($multiRutaData);
}
```

**Mapeo de Alias**:
| Alias de entrada | Campo normalizado |
|-----------------|-------------------|
| `peso`, `peso_kg`, `pesoMercancia` | → `peso_kg` |
| `valor`, `valor_declarado`, `valorMercancia` | → `valor_declarado` |
| `vehiculo`, `claseVehiculo` | → `vehiculo` |
| `cantidad`, `cantidadMercancia` | → `cantidad` |
| `producto`, `tipo_producto` | → `producto` |

### 2. Frontend: Normalización al Recibir (ChatModal.jsx)

**Agregado en líneas 2318-2330**:

```jsx
// 🔧 NORMALIZAR campos de editedRoute (puede venir con diferentes nombres)
const normalizedEdited = {
  origen: editedRoute.origen ?? editedRoute.ciudadOrigen,
  destino: editedRoute.destino ?? editedRoute.ciudadDestino,
  peso_kg: editedRoute.peso_kg ?? editedRoute.peso ?? editedRoute.pesoMercancia,
  cantidad: editedRoute.cantidad ?? editedRoute.cantidadMercancia,
  valor_declarado: editedRoute.valor_declarado ?? editedRoute.valor ?? editedRoute.valorMercancia,
  vehiculo: editedRoute.vehiculo ?? editedRoute.claseVehiculo,
  empaque: editedRoute.empaque,
  empaque_id: editedRoute.empaque_id,
  producto: editedRoute.producto ?? editedRoute.tipo_producto,
  contenedor: editedRoute.contenedor,
  incluye_tara: editedRoute.incluye_tara
};

// Usar normalizedEdited en lugar de editedRoute directamente
const pesoBase = Number(normalizedEdited.peso_kg ?? existingRoute.pesoMercancia ?? 0) || 0;
```

**Actualizado merge de ruta editada (líneas 2363-2385)**:
```jsx
const mergedRoute = {
  ...existingRoute,
  // Usar datos normalizados
  ...(normalizedEdited.origen !== undefined && { ciudadOrigen: normalizedEdited.origen }),
  ...(normalizedEdited.destino !== undefined && { ciudadDestino: normalizedEdited.destino }),
  ...(pesoFinal && { pesoMercancia: pesoFinal }),
  ...(normalizedEdited.cantidad !== undefined && { cantidadMercancia: normalizedEdited.cantidad }),
  ...(normalizedEdited.valor_declarado !== undefined && { 
    valorMercancia: normalizedEdited.valor_declarado,
    valor_declarado: normalizedEdited.valor_declarado 
  }),
  ...(normalizedEdited.vehiculo !== undefined && { claseVehiculo: normalizedEdited.vehiculo }),
  ...(normalizedEdited.empaque !== undefined && { empaque: normalizedEdited.empaque }),
  ...(normalizedEdited.producto !== undefined && {
    producto: normalizedEdited.producto,
    tipo_producto: normalizedEdited.producto
  }),
  incluye_tara: incluyeTara,
};
```

## Flujo Corregido

```
Backend retorna rutas con alias mixtos:
{peso: 12000, valor: 95000000}
    ↓
Frontend normaliza al recibir:
{peso_kg: 12000, valor_declarado: 95000000}
    ↓
Usuario edita ruta 2: "Cambia el peso a 15000 kg"
    ↓
Frontend identifica correctamente la ruta 2 (normalización funciona)
    ↓
Frontend envía las 3 rutas al backend
    ↓
Backend normaliza antes de guardar:
[
  {peso_kg: 12000, valor_declarado: 95000000, vehiculo: "TRACTOCAMIÓN"},
  {peso_kg: 15000, valor_declarado: 22000000, vehiculo: "TURBO"},
  {peso_kg: 900, valor_declarado: 6500000, vehiculo: "SENCILLO"}
]
    ↓
✅ Guarda en BD con formato consistente multi-ruta
    ↓
✅ Al recuperar, todas las rutas tienen los mismos nombres de campos
```

## Validación

### Test: `test_normalizacion_campos.php`

Prueba con 3 rutas usando diferentes alias:
- Ruta 1: `peso`, `valor`, `vehiculo`
- Ruta 2: `peso_kg`, `valor_declarado`, `vehiculo`
- Ruta 3: `pesoMercancia`, `valorMercancia`, `claseVehiculo`

**Resultado**:
```
✅ Ruta 1: MADRID → CARTAGENA
   ✅ peso_kg: 12000 kg
   ✅ valor_declarado: $95.000.000
   ✅ vehiculo: TRACTOCAMIÓN

✅ Ruta 2: CALI → BARRANQUILLA
   ✅ peso_kg: 3800 kg
   ✅ valor_declarado: $22.000.000
   ✅ vehiculo: TURBO

✅ Ruta 3: TOCANCIPA → BOGOTA
   ✅ peso_kg: 900 kg
   ✅ valor_declarado: $6.500.000
   ✅ vehiculo: SENCILLO

✅ ¡PERFECTO! Todos los campos están normalizados correctamente
✅ Nombres consistentes: peso_kg, valor_declarado, vehiculo
✅ No se encontraron alias antiguos
```

## Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|--------|
| [ChatController.php](app/Http/Controllers/Api/ChatController.php#L854-L893) | 854-893 | Normalización de campos antes de guardar |
| [ChatModal.jsx](resources/js/components/CotizacionInicial/ChatModal.jsx#L2318-L2330) | 2318-2330 | Normalización de campos al recibir datos editados |
| [ChatModal.jsx](resources/js/components/CotizacionInicial/ChatModal.jsx#L2363-L2385) | 2363-2385 | Uso de datos normalizados en merge de ruta |

## Impacto

- ✅ Los datos se guardan siempre con nombres consistentes en BD
- ✅ El frontend reconoce correctamente la ruta que está siendo editada
- ✅ No se pierden datos por diferencias en nombres de campos
- ✅ Compatible con datos antiguos que usan alias
- ✅ Mayor robustez ante cambios futuros de nombres

## Estado: ✅ CORREGIDO Y VALIDADO

Backend normaliza al guardar, frontend normaliza al recibir. Test 100% exitoso.
