# FIX CRÍTICO: Pérdida de Rutas al Editar Multi-Ruta

## Fecha: 2026-01-26
## Problema Reportado

Al editar **cualquier campo** de una ruta en una cotización multi-ruta (2 o 3 rutas), el sistema **eliminaba las otras rutas** y solo dejaba 1 visible.

### Ejemplo del Bug:
1. Usuario crea 3 rutas: Madrid-Cartagena, Cali-Barranquilla, Tocancipá-Bogotá ✅
2. Las 3 rutas se muestran correctamente en el panel izquierdo ✅
3. Usuario edita el peso de la ruta 2: "Cambia el peso a 15000 kg" 
4. **Bug**: Solo queda 1 ruta visible (Madrid-Cartagena), las otras 2 desaparecen ❌

## Causa Raíz

### Problema en ChatModal.jsx (líneas 2436-2459)

El frontend **SOLO guardaba** `extracted_data` en la BD cuando se modificaba la **tara** (línea 2436: `if (taraWasModified)`).

Cuando editabas peso, origen, destino, cantidad, valor, etc., **SIN mencionar "tara"**:
- ✅ Frontend actualizaba el estado local correctamente
- ❌ NO guardaba en BD
- ❌ Al refrescar o cambiar de pestaña, `fetchCurrentDataFromDB()` traía datos antiguos

**Código Problemático**:
```jsx
// 🆕 Si se agregó/quitó tara, guardar extracted_data en BD
if (taraWasModified) {  // ❌ SOLO guardaba si taraWasModified = true
  const extractedDataToSave = updatedRoutes.map(r => ({...}));
  fetch('/api/chat/update-extracted-data', {...});
}
```

## Solución Implementada

### 1. Frontend: Guardar SIEMPRE después de editar (ChatModal.jsx)

**Cambio en línea 2433**:

```jsx
// 🆕 SIEMPRE guardar extracted_data en BD después de editar cualquier ruta
// (antes solo se guardaba si se modificaba la tara, ahora se guarda SIEMPRE)
const extractedDataToSave = updatedRoutes.map(r => ({
  origen: r.ciudadOrigen,
  destino: r.ciudadDestino,
  peso_kg: r.pesoMercancia,
  cantidad: r.cantidadMercancia,
  valor_declarado: r.valorMercancia || r.valor_declarado,
  vehiculo: r.claseVehiculo,
  empaque: r.empaque,
  empaque_id: r.empaque_id,
  producto: r.producto || r.tipo_producto,
  incluye_tara: r.incluye_tara
}));

// Guardar en BD de manera asíncrona
fetch('/api/chat/update-extracted-data', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  },
  body: JSON.stringify({
    group_id: clientData.groupId,
    extracted_data: extractedDataToSave
  })
}).then(resp => resp.json())
  .then(data => {
    if (data.success) {
      console.log('✅ extracted_data actualizado en BD con todas las rutas');
    }
  })
  .catch(err => console.error('❌ Error guardando extracted_data:', err));
```

**Cambios clave**:
- ❌ Eliminado `if (taraWasModified)` 
- ✅ Ahora guarda SIEMPRE después de editar cualquier campo
- ✅ Incluye todos los campos: peso, cantidad, valor, vehiculo, empaque, producto
- ✅ Maneja alias: `valorMercancia || valor_declarado`, `producto || tipo_producto`

### 2. Backend: Guardar en formato multi-ruta (ChatController.php)

**Actualizado método `updateExtractedData()` (línea 855)**:

```php
// 🚛 Si son múltiples rutas, guardar en formato multi-ruta
if (is_array($extractedData) && count($extractedData) > 1) {
    $multiRutaData = [
        'multi_ruta' => true,
        'total_rutas' => count($extractedData),
        'rutas' => $extractedData
    ];
    $group->extracted_data = json_encode($multiRutaData);
    Log::info('🚛 Guardando extracted_data en formato multi-ruta', [
        'group_id' => $groupId,
        'total_rutas' => count($extractedData)
    ]);
} else {
    // Ruta única
    $group->extracted_data = json_encode($extractedData);
    Log::info('📍 Guardando extracted_data en formato ruta única', [
        'group_id' => $groupId
    ]);
}
$group->save();
```

**Antes**: Guardaba como array plano `[ruta1, ruta2, ruta3]`  
**Ahora**: Guarda con estructura `{multi_ruta: true, total_rutas: 3, rutas: [...]}`

## Flujo Corregido

```
Usuario crea 3 rutas
    ↓
Backend detecta multi_ruta=true y guarda en extracted_data
    ↓
Frontend muestra 3 rutas
    ↓
Usuario edita ruta 2: "Cambia el peso a 15000 kg"
    ↓
Frontend detecta modo "edición de ruta individual"
    ↓
Actualiza SOLO la ruta 2 en el estado local
    ↓
🆕 LLAMA SIEMPRE a /api/chat/update-extracted-data
    ↓
Backend recibe las 3 rutas (ruta 2 editada, rutas 1 y 3 intactas)
    ↓
Backend detecta count > 1 → guarda formato multi-ruta
    ↓
Guarda en BD: {multi_ruta: true, total_rutas: 3, rutas: [...]}
    ↓
Usuario refresca página o cambia de pestaña
    ↓
Frontend hace fetchCurrentDataFromDB()
    ↓
QuoteRoutesController detecta multi_ruta=true
    ↓
✅ Retorna las 3 rutas desde extracted_data
    ↓
✅ Frontend muestra las 3 rutas correctamente
```

## Validación

### Test Automatizado: `test_fix_persistencia_rutas.php`

```bash
php test_fix_persistencia_rutas.php
```

**Resultado**:
```
✅ Grupo #601 actualizado con 3 rutas
✅ Formato multi-ruta detectado correctamente
✅ Total de rutas: 3
✅ ¡ÉXITO! Las 3 rutas se mantuvieron después de la edición

Ruta 1: MADRID → CARTAGENA | 12000 kg
Ruta 2: CALI → BARRANQUILLA | 15000 kg [EDITADO: 3800 → 15000]
Ruta 3: TOCANCIPA → BOGOTA | 900 kg

✅ PERFECTO: Solo la ruta 2 fue editada, las demás permanecen intactas
```

### Test Manual

1. Refresca el navegador (Ctrl+F5)
2. Crea una cotización con 3 rutas:
   ```
   Hola, necesito cotizar tres rutas: 
   la primera es Madrid – Cartagena, 12.000 kg de productos de consumo masivo en 480 cajas, valor $95.000.000, en tractocamión. 
   La segunda es Cali – Barranquilla, 3.800 kg de textiles en 140 cajas, valor $22.000.000, en turbo.
   Y la tercera es Tocancipá – Bogotá, 900 kg de repuestos industriales en 35 cajas, valor $6.500.000, en sencillo.
   ```
3. Verifica que se muestren las 3 rutas
4. Edita una ruta: "Cambia el peso de la segunda ruta a 15000 kg"
5. ✅ Verifica que las 3 rutas siguen visibles
6. Refresca la página (Ctrl+F5)
7. ✅ Verifica que las 3 rutas siguen presentes

## Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|--------|
| [ChatModal.jsx](resources/js/components/CotizacionInicial/ChatModal.jsx#L2433-L2464) | 2433-2464 | Removido `if (taraWasModified)`, ahora guarda SIEMPRE |
| [ChatController.php](app/Http/Controllers/Api/ChatController.php#L855-L875) | 855-875 | Detecta count > 1 y guarda formato multi-ruta |

## Impacto

- ✅ Las rutas múltiples ahora persisten correctamente al editar cualquier campo
- ✅ No se pierden rutas al cambiar peso, origen, destino, cantidad, valor, etc.
- ✅ El formato multi-ruta se mantiene consistente en BD
- ✅ Compatible con ediciones de tara (suma/resta 3400 kg)
- ✅ No afecta cotizaciones de ruta única

## Estado: ✅ CORREGIDO Y VALIDADO

Frontend compilado, backend actualizado, test 100% exitoso.
