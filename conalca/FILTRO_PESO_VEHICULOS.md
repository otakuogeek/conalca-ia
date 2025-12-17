# Filtro de Peso Máximo en Búsqueda de Conductores

## 📋 Resumen

Se implementó un **filtro de peso máximo** en el sistema de búsqueda de conductores para que **solo se busquen vehículos que soporten el peso de la carga** especificado en la cotización.

## 🎯 Problema Resuelto

**Antes:**
- Sistema buscaba todos los vehículos del tipo solicitado sin considerar capacidad de carga
- Ejemplo: Para carga de 18,400 kg tipo "ARTICULADO" buscaba:
  - ❌ PATINETA2 (soporta solo 17,000 kg) - **NO puede transportar la carga**
  - ✅ TRACTOMULA 3 (soporta 34,000 kg) - **SÍ puede transportar la carga**

**Ahora:**
- Sistema filtra automáticamente por `peso_maximo >= peso_mercancia`
- Solo busca conductores con vehículos que **realmente pueden transportar la carga**

## 🔧 Cambios Implementados

### 1. ArcangelDriversController.php

#### a) Método `buscarConductores()`
```php
// Obtener el peso de la mercancía
$pesoCarga = 0;
if ($cotizacion->peso_mercancia) {
    // Limpiar el string y convertir a float (puede venir como "18400" o "18,400" o "18400 kg")
    $pesoCarga = floatval(str_replace([',', ' kg', ' KG'], '', $cotizacion->peso_mercancia));
}

// Pasar peso al método de conversión
$variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido, $pesoCarga);
```

#### b) Método `convertirTipoVehiculo()`
```php
/**
 * Convertir tipos de vehículos de nuestra nomenclatura a la de Arcángel
 * Solo incluye vehículos que soporten el peso de la carga
 * 
 * @param string $tipoLocal Tipo de vehículo en nomenclatura local
 * @param float $pesoCarga Peso de la carga en kg (0 si no se filtra por peso)
 * @return array Variantes de vehículos en Arcángel que cumplen con peso máximo
 */
private function convertirTipoVehiculo(string $tipoLocal, float $pesoCarga = 0): array
```

#### c) Filtrado en las 3 Estrategias SQL

**ESTRATEGIA 1 - Coincidencia exacta:**
```php
$query1 = DB::table('vehiculos_relaciones')
    ->join('vehiculos_pricing', ...)
    ->join('vehiculos_arcangel', ...)
    ->where(DB::raw('UPPER(vehiculos_pricing.vehiculo_silogtran)'), '=', $tipoNormalizado);

// Filtrar por peso máximo si se especifica
if ($pesoCarga > 0) {
    $query1->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
}
```

**ESTRATEGIA 2 - Coincidencia en tabla_pricing:**
```php
$query2 = DB::table('vehiculos_relaciones')
    ->join('vehiculos_pricing', ...)
    ->join('vehiculos_arcangel', ...)
    ->where(DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%' . $tipoNormalizado . '%');

// Filtrar por peso máximo si se especifica
if ($pesoCarga > 0) {
    $query2->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
}
```

**ESTRATEGIA 3 - Coincidencia parcial:**
```php
$query3 = DB::table('vehiculos_relaciones')
    ->join('vehiculos_pricing', ...)
    ->join('vehiculos_arcangel', ...)
    ->where(function($query) use ($tipoNormalizado) {
        $query->where(DB::raw('UPPER(vehiculos_pricing.vehiculo_silogtran)'), 'LIKE', '%' . $tipoNormalizado . '%')
              ->orWhere(DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%' . $tipoNormalizado . '%');
    });

// Filtrar por peso máximo si se especifica
if ($pesoCarga > 0) {
    $query3->where('vehiculos_pricing.peso_maximo', '>=', $pesoCarga);
}
```

## 📊 Ejemplo de Funcionamiento

### Datos de Prueba
- **Cotización:**
  - Vehículo requerido: `ARTICULADO`
  - Peso mercancía: `18,400 kg`

### Sin Filtro de Peso (antes)
```
Vehículos encontrados: 3
  - PATINETA2 (máx: 17,000 kg) ❌ NO SOPORTA
  - TRACTOMULA 3 (máx: 34,000 kg) ✅ SOPORTA
  - TRACTOMULA3 (máx: 34,000 kg) ✅ SOPORTA
```

### Con Filtro de Peso (ahora)
```
Vehículos encontrados: 2
  - TRACTOMULA 3 (máx: 34,000 kg) ✅
  - TRACTOMULA3 (máx: 34,000 kg) ✅
```

### Resultado
- ❌ PATINETA2 descartada automáticamente (no soporta el peso)
- ✅ Solo busca conductores con TRACTOMULA 3 y TRACTOMULA3
- ✅ Todos los vehículos retornados pueden transportar la carga

## 🗄️ Estructura de Datos

### Tabla: `cotizacion_models`
- `peso_mercancia` (string): Peso de la carga, puede venir en formatos:
  - `"18400"`
  - `"18,400"`
  - `"18400 kg"`

### Tabla: `vehiculos_pricing`
- `peso_maximo` (numeric): Peso máximo que soporta el vehículo en kg
- Ejemplos:
  - PATINETA2 - ARTICULADO: `17,000 kg`
  - TRACTOMULA 3 - ARTICULADO: `34,000 kg`

### Tabla: `vehiculos_relaciones`
- Junction table que relaciona:
  - `vehiculo_pricing_id` → `vehiculos_pricing`
  - `vehiculo_arcangel_id` → `vehiculos_arcangel`

## ✅ Ventajas

1. **Precisión:** Solo busca vehículos que realmente pueden transportar la carga
2. **Eficiencia:** Evita llamadas innecesarias a la API de Arcángel
3. **Seguridad:** Previene asignación de vehículos insuficientes
4. **Automático:** No requiere intervención manual
5. **Flexible:** Si peso = 0, no aplica filtro (búsqueda normal)

## 🔍 Logs

El sistema registra en `storage/logs/laravel.log`:

```
🔍 Convirtiendo tipo de vehículo con filtro de peso
  - vehiculo_local: ARTICULADO
  - peso_carga: 18400 kg

✅ Vehículos Arcangel encontrados (coincidencia tabla_pricing)
  - vehiculo_local: ARTICULADO
  - vehiculo_normalizado: ARTICULADO
  - vehiculos_arcangel: ["TRACTOMULA 3", "TRACTOMULA3"]
```

## 🧪 Pruebas Realizadas

### Test SQL en Tinker
```bash
php artisan tinker --execute="
\$vehiculos = DB::table('vehiculos_relaciones')
    ->join('vehiculos_pricing', 'vehiculos_relaciones.vehiculo_pricing_id', '=', 'vehiculos_pricing.id')
    ->join('vehiculos_arcangel', 'vehiculos_relaciones.vehiculo_arcangel_id', '=', 'vehiculos_arcangel.id')
    ->where(DB::raw('UPPER(vehiculos_pricing.tabla_pricing)'), 'LIKE', '%ARTICULADO%')
    ->where('vehiculos_pricing.peso_maximo', '>=', 18400)
    ->select('vehiculos_arcangel.nombre', 'vehiculos_pricing.peso_maximo')
    ->distinct()
    ->get();
"
```

**Resultado:**
```
✅ TRACTOMULA 3 (34,000 kg)
✅ TRACTOMULA3 (34,000 kg)
❌ PATINETA2 descartada (17,000 kg < 18,400 kg)
```

## 📝 Notas Importantes

1. **Parámetro opcional:** `$pesoCarga = 0` por defecto
   - Si peso = 0, no aplica filtro de peso
   - Mantiene compatibilidad con código existente

2. **Formato de peso:** El sistema limpia automáticamente:
   - Comas: `18,400` → `18400`
   - Sufijos: `18400 kg` → `18400`
   - Espacios extras

3. **Fallback mapping:** El mapeo de respaldo NO tiene filtro de peso
   - Solo se usa si falla la consulta a BD
   - Es recomendable tener todas las relaciones en BD

4. **Campo usado:** `cotizacion_models.peso_mercancia`
   - No hay tabla de "cargas" separada
   - Peso está directamente en la cotización

## 🚀 Próximos Pasos

Para probar en producción:
1. Crear cotización con vehículo `ARTICULADO` y peso `18400 kg`
2. Hacer clic en "Registrar Llamadas"
3. Verificar que el modal muestre solo conductores con TRACTOMULA 3/TRACTOMULA3
4. Confirmar que NO aparezca PATINETA2
5. Revisar logs en `storage/logs/laravel.log`

## 📅 Fecha de Implementación

4 de diciembre de 2025
