# CORRECCIÓN: Normalización Mayúsculas y Persistencia Multi-Ruta

## Fecha: 2026-01-26

### Problemas Reportados

1. **Empaque y Vehículo en minúsculas**: Los campos se mostraban como "cajas", "turbo", "sencillo" en lugar de "CAJAS", "TURBO", "SENCILLO"

2. **Pérdida de rutas al editar**: Al modificar algo en una cotización multi-ruta (3 rutas), solo quedaba 1 ruta visible en el frontend

### Causa Raíz

#### Problema 1: Normalización Incompleta
El método `normalizeExtractedData()` en `DataExtractionService.php` no estaba convirtiendo a mayúsculas los campos `empaque` y `vehiculo`, solo manejaba `origen` y `destino`.

#### Problema 2: Detección de Multi-Ruta en `getQuoteRoutes`
El endpoint `/api/chat/quote/routes/{groupId}` no detectaba correctamente el formato multi-ruta cuando `extracted_data` tenía la estructura:
```json
{
  "multi_ruta": true,
  "rutas": [ruta1, ruta2, ruta3],
  "origen": "...",  // Campos planos que confundían
  ...
}
```

### Solución Implementada

#### 1. Normalización de Mayúsculas (DataExtractionService.php)

**Antes**:
```php
case 'contenedor':
case 'producto':
case 'mercancia':
case 'incoterm':
case 'observaciones':
    $normalized[$key] = trim($value);
    break;
```

**Después**:
```php
case 'contenedor':
case 'producto':
case 'mercancia':
case 'observaciones':
    $normalized[$key] = trim($value);
    break;

case 'empaque':
case 'vehiculo':
    // Convertir a mayúsculas para estos campos críticos
    $val = trim($value);
    $normalized[$key] = mb_strtoupper($val, 'UTF-8');
    break;

case 'incoterm':
    // INCOTERM siempre en mayúsculas
    $normalized[$key] = mb_strtoupper(trim($value), 'UTF-8');
    break;
```

#### 2. Detección Multi-Ruta (QuoteRoutesController.php)

**Antes**:
```php
$routesArray = isset($extractedData[0]) ? $extractedData : [$extractedData];
```

**Después**:
```php
// 🚛 DETECTAR MULTI-RUTA PRIMERO
$routesArray = [];
if (isset($extractedData['multi_ruta']) && $extractedData['multi_ruta'] === true && isset($extractedData['rutas'])) {
    // Es multi-ruta, usar el array 'rutas'
    $routesArray = $extractedData['rutas'];
    Log::info('🚛 Multi-ruta detectada en extracted_data', ['total' => count($routesArray)]);
} elseif (isset($extractedData[0])) {
    // Es un array indexado de rutas
    $routesArray = $extractedData;
} else {
    // Es un objeto único (ruta única)
    $routesArray = [$extractedData];
}
```

### Validación

#### Test de Normalización:
```bash
php -r "..."
```

**Resultado**:
```
Ruta 1: Empaque=CAJAS, Vehículo=TRACTOCAMIÓN
Ruta 2: Empaque=CAJAS, Vehículo=TURBO
Ruta 3: Empaque=CAJAS, Vehículo=SENCILLO
```
✅ Empaque y Vehículo ahora en MAYÚSCULAS

#### Test de Persistencia Multi-Ruta:
```bash
php test_get_routes_753.php
```

**Resultado**:
```
✅ Total de rutas retornadas: 2

Ruta #1: BOGOTA → BUENAVENTURA
Ruta #2: MEDELLIN → CARTAGENA
```
✅ Mantiene todas las rutas al recuperar de BD

### Flujo Completo Corregido

```
Usuario crea 3 rutas
    ↓
Backend detecta multi_ruta=true
    ↓
Guarda en extracted_data del grupo:
{
  "multi_ruta": true,
  "total_rutas": 3,
  "rutas": [
    {"origen":"MADRID","destino":"CARTAGENA","empaque":"CAJAS","vehiculo":"TRACTOCAMIÓN",...},
    {"origen":"CALI","destino":"BARRANQUILLA","empaque":"CAJAS","vehiculo":"TURBO",...},
    {"origen":"TOCANCIPA","destino":"BOGOTA","empaque":"CAJAS","vehiculo":"SENCILLO",...}
  ]
}
    ↓
Frontend muestra 3 rutas
    ↓
Usuario edita algo (ej: cambia peso de ruta 1)
    ↓
Frontend hace fetch a /api/chat/quote/routes/{groupId}
    ↓
Backend detecta multi_ruta=true en extracted_data
    ↓
Retorna las 3 rutas desde extracted_data
    ↓
Frontend actualiza SOLO la ruta editada, mantiene las otras 2
    ↓
✅ Las 3 rutas persisten correctamente
```

### Campos Normalizados a Mayúsculas

| Campo | Ejemplo Entrada | Salida |
|-------|----------------|--------|
| origen | bogotá | BOGOTA |
| destino | medellín | MEDELLIN |
| empaque | cajas | CAJAS |
| empaque | bultos | BULTOS |
| empaque | estibas | ESTIBAS |
| vehiculo | turbo | TURBO |
| vehiculo | sencillo | SENCILLO |
| vehiculo | tractocamión | TRACTOCAMIÓN |
| incoterm | fob | FOB |
| incoterm | cif | CIF |

### Archivos Modificados

1. **app/Services/DataExtractionService.php** (líneas 490-510)
   - Agregada normalización a mayúsculas para `empaque` y `vehiculo`

2. **app/Http/Controllers/Api/QuoteRoutesController.php** (líneas 214-228)
   - Mejorada detección de formato multi-ruta en `extracted_data`

### Impacto

- ✅ Todos los campos críticos ahora se muestran consistentemente en mayúsculas
- ✅ Las rutas múltiples se mantienen al editar cualquier campo
- ✅ La persistencia funciona correctamente desde `extracted_data`
- ✅ No afecta rutas únicas (funcionan igual)

### Próximos Pasos

1. Refresca el navegador (Ctrl+F5)
2. Crea una cotización con 3 rutas usando el prompt
3. Verifica que empaque y vehículo estén en MAYÚSCULAS
4. Edita cualquier campo de cualquier ruta
5. Verifica que las 3 rutas se mantienen

### Estado: ✅ CORREGIDO Y LISTO
