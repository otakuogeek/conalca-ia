# Corrección: Mapeo de Tipos de Vehículos con Arcángel API

## 📅 Fecha
**Noviembre 16, 2025**

## 🐛 Problema Identificado

### Error 500 en endpoint `/api/arcangel/buscar-conductores`

**Causa raíz**: Discrepancia entre la nomenclatura de tipos de vehículos en nuestra base de datos y la API de Arcángel.

### Evidencia del Problema

#### Nuestra Base de Datos
```
Vehículo: "Tracto Mula S3"
Ciudad: "funza" (minúsculas)
```

#### API de Arcángel
```
Tipos disponibles en Funza:
- PATINETA2
- TURBO
- CAMIONETA
- SENCILLO
- TRACTOMULA3      ← Correcto
- PATINETA3
- TRACTOMULA 3     ← Variante
```

**Mismatch**: Buscábamos "TRACTO MULA S3" pero Arcángel usa "TRACTOMULA3" o "TRACTOMULA 3"

## ✅ Solución Implementada

### 1. Nuevo Método de Mapeo

Creado en `ArcangelDriversController.php`:

```php
private function convertirTipoVehiculo(string $tipoLocal): array
{
    $tipoNormalizado = $this->normalizarTexto($tipoLocal);
    
    $mapeo = [
        // Tractomulas
        'TRACTO MULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
        'TRACTO MULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
        'TRACTOMULA S3' => ['TRACTOMULA3', 'TRACTOMULA 3', 'TRACTOMULA S3'],
        'TRACTOMULA' => ['TRACTOMULA', 'TRACTOMULA3', 'TRACTOMULA 3'],
        
        // Sencillos
        'SENCILLO' => ['SENCILLO'],
        'CAMION SENCILLO' => ['SENCILLO'],
        
        // Doble troque
        'DOBLE TROQUE' => ['DOBLE TROQUE', 'DOBLETROQUE'],
        
        // Camionetas
        'CAMIONETA' => ['CAMIONETA', 'TURBO'],
        'TURBO' => ['TURBO', 'CAMIONETA'],
        
        // Patinetas
        'PATINETA' => ['PATINETA', 'PATINETA2', 'PATINETA3'],
        'PATINETA 2' => ['PATINETA2', 'PATINETA 2'],
        'PATINETA 3' => ['PATINETA3', 'PATINETA 3'],
    ];
    
    // Buscar coincidencia y retornar variantes
    foreach ($mapeo as $clave => $variantes) {
        if (str_contains($tipoNormalizado, $clave) || $tipoNormalizado === $clave) {
            return $variantes;
        }
    }
    
    return [$tipoNormalizado];
}
```

### 2. Actualización del Método `buscarConductores`

**Antes**:
```php
$vehiculoRequerido = $this->normalizarTexto($cotizacion->vehiculo_requerido);

$resultado = $this->arcangelService->getVehiculosFiltrados(
    ciudad: $ciudadOrigen,
    clases: $vehiculoRequerido,  // String único
    minScore: $minScore,
    limit: $limit
);
```

**Después**:
```php
$variantesVehiculo = $this->convertirTipoVehiculo($cotizacion->vehiculo_requerido);

$resultado = $this->arcangelService->getVehiculosFiltrados(
    ciudad: $ciudadOrigen,
    clases: $variantesVehiculo,  // Array de variantes
    minScore: $minScore,
    limit: $limit
);
```

### 3. Respuesta Mejorada

**Nueva estructura de respuesta**:
```json
{
  "success": true,
  "data": {
    "cotizacion": {
      "id": 31,
      "ciudad_origen": "funza",
      "vehiculo_requerido": "Tracto Mula S3"
    },
    "conductores": [...],
    "total": 2,
    "filtros": {
      "ciudad": "FUNZA",
      "vehiculo_original": "Tracto Mula S3",
      "variantes_buscadas": [
        "TRACTOMULA3",
        "TRACTOMULA 3", 
        "TRACTOMULA S3"
      ],
      "min_score": 7
    }
  },
  "message": "Se encontraron 2 conductores disponibles"
}
```

## 🧪 Pruebas Realizadas

### Test 1: Cotización con Tracto Mula S3

```bash
php artisan tinker --execute="
$controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
$request = new \Illuminate\Http\Request();
$request->merge(['cotizacion_id' => 31]);
$response = $controller->buscarConductores($request);
"
```

**Resultado**:
```
✅ Success: SI
✅ Total conductores: 2
✅ Ciudad: FUNZA
✅ Vehículo original: Tracto Mula S3
✅ Variantes buscadas: ["TRACTOMULA3","TRACTOMULA 3","TRACTOMULA S3"]
```

### Test 2: Tipos disponibles en Arcángel (Funza)

```php
$service->getVehiculosCercanos('FUNZA');
```

**Resultado**:
```
- PATINETA2
- TURBO
- CAMIONETA
- SENCILLO
- TRACTOMULA3      ✓
- PATINETA3
- TRACTOMULA 3     ✓
```

## 📊 Beneficios de la Solución

### 1. **Búsqueda Exhaustiva**
- Busca múltiples variantes en una sola llamada
- Ejemplo: "Tracto Mula S3" busca 3 variantes simultáneamente

### 2. **Flexibilidad**
- Soporta diferentes nomenclaturas
- Fácil agregar nuevos mapeos

### 3. **Transparencia**
- La respuesta muestra qué variantes se buscaron
- Facilita debugging y auditoría

### 4. **Normalización Automática**
- Maneja minúsculas/mayúsculas
- Elimina acentos automáticamente
- Normaliza espacios

## 🔧 Mantenimiento Futuro

### Agregar Nuevos Tipos de Vehículos

1. **Identificar el tipo en Arcángel**:
```bash
php artisan arcangel:buscar-conductores <cotizacion_id> --arcangel
```

2. **Agregar al mapeo** en `convertirTipoVehiculo()`:
```php
'TIPO_LOCAL' => ['TIPO_ARCANGEL_1', 'TIPO_ARCANGEL_2'],
```

3. **Probar**:
```bash
php artisan tinker --execute="
$controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
// test aquí
"
```

### Tipos de Vehículos Conocidos en Arcángel

Basado en análisis de múltiples ciudades:

```
✅ TRACTOMULA3
✅ TRACTOMULA 3
✅ SENCILLO
✅ CAMIONETA
✅ TURBO
✅ PATINETA2
✅ PATINETA3
✅ PATINETA 2
✅ PATINETA 3
⚠️  DOBLE TROQUE (verificar existencia)
⚠️  DOBLETROQUE (verificar existencia)
```

## 🎯 Casos de Uso

### Caso 1: Cotización con Tracto Mula
```
Input: "Tracto Mula S3"
Variantes: ["TRACTOMULA3", "TRACTOMULA 3", "TRACTOMULA S3"]
Resultado: Encuentra vehículos con cualquiera de esas clasificaciones
```

### Caso 2: Cotización con Camioneta
```
Input: "Camioneta"
Variantes: ["CAMIONETA", "TURBO"]
Resultado: Encuentra tanto camionetas como turbos
```

### Caso 3: Cotización con Patineta
```
Input: "Patineta"
Variantes: ["PATINETA", "PATINETA2", "PATINETA3"]
Resultado: Encuentra todos los tipos de patinetas
```

## 📝 Archivos Modificados

```
✅ /app/Http/Controllers/Api/ArcangelDriversController.php
   - Agregado método convertirTipoVehiculo()
   - Actualizado buscarConductores()
   - Mejorada respuesta con variantes buscadas
```

## 🚀 Integración con Frontend

El componente `CallPanel.jsx` ahora recibe:

```javascript
{
  success: true,
  data: {
    total: 2,  // Número actualizado de conductores
    conductores: [...],
    filtros: {
      vehiculo_original: "Tracto Mula S3",
      variantes_buscadas: ["TRACTOMULA3", "TRACTOMULA 3", "TRACTOMULA S3"]
    }
  }
}
```

**Actualización automática del contador**:
```jsx
<p className="text-sm">
  <strong>Total conductores:</strong> {driversSearchData?.total || data.total_to_call}
  {driversSearchData && (
    <span className="ml-2 text-xs text-green-600">
      (Actualizado desde Arcángel)
    </span>
  )}
</p>
```

## ⚠️ Consideraciones

### 1. **Caché**
- Las búsquedas se cachean por 15 minutos
- Si Arcángel agrega nuevos tipos, limpiar caché:
```bash
php artisan cache:clear
```

### 2. **Ciudades**
- Las ciudades deben escribirse exactamente como en Arcángel
- Usar: `php artisan arcangel:listar-ciudades` para ver listado completo
- Total: 219 ciudades disponibles

### 3. **Score Mínimo**
- Por defecto: 7
- Ajustable vía parámetro `min_score` (0-10)

### 4. **Límite de Resultados**
- Por defecto: 50
- Máximo: 100
- Ajustable vía parámetro `limit`

## 🔍 Debugging

### Ver logs de búsqueda:
```bash
tail -f storage/logs/laravel.log | grep "ArcangelDriversController"
```

### Probar mapeo de vehículo específico:
```php
php artisan tinker
> $controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
> $method = new ReflectionMethod($controller, 'convertirTipoVehiculo');
> $method->setAccessible(true);
> $result = $method->invoke($controller, 'Tracto Mula S3');
> print_r($result);
```

### Verificar vehículos en ciudad:
```bash
php artisan arcangel:consultar-localidad "CIUDAD"
```

## 📚 Referencias

### Comandos Relacionados
- `php artisan arcangel:listar-ciudades` - Ver 219 ciudades disponibles
- `php artisan arcangel:consultar-localidad <ciudad>` - Ver vehículos en ciudad
- `php artisan arcangel:buscar-conductores <id> --arcangel` - Buscar para cotización

### Servicios Relacionados
- `ArcangelService::getVehiculosCercanos()` - Obtiene vehículos de ciudad
- `ArcangelService::getVehiculosFiltrados()` - Filtra por clase y score
- `ArcangelService::getCiudades()` - Lista todas las ciudades

### Documentación Previa
- `SISTEMA_CONDUCTORES_ARCANGEL.md` - Sistema completo
- `ANALISIS_BACKEND_ARCANGEL.md` - Análisis del backend
- `ACTUALIZACION_CALL_PANEL_VER_LISTADO.md` - Integración frontend

## ✅ Estado Actual

**Error Original**: 500 Internal Server Error  
**Estado Actual**: ✅ Funcionando correctamente  
**Conductores Encontrados**: 2 (Tracto Mula S3 en Funza)  
**Compilación Frontend**: ✅ Exitosa  

---

**Autor**: GitHub Copilot  
**Fecha**: Noviembre 16, 2025  
**Versión**: 2.0  
**Estado**: ✅ Corregido y Probado
