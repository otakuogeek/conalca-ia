# CORRECCIÓN FINAL - SISTEMA COMPLETO DE ALMACENAMIENTO DE COTIZACIONES

## Fecha: 5 de diciembre de 2025

## PROBLEMA RAÍZ IDENTIFICADO

Los registros 67-69 se crearon con `cotizacion_id` NULL porque había **TRES métodos diferentes** que llamaban a `createFromArcangel()` SIN pasar información de cotización:

### Métodos afectados:
1. **`initiateConversationalCall`** (línea ~1010) - Llamadas individuales directas
2. **`initiateGroupConversationalCall`** (línea ~1601) - Llamadas desde Dashboard "Registrar Llamadas"  
3. **`registerCallsForCotization`** (línea ~1802) - YA estaba corregido

## SOLUCIÓN IMPLEMENTADA

### 1. Actualizado `initiateConversationalCall` (app/Http/Controllers/ConversationalAgentController.php ~línea 1010)

**ANTES:**
```php
$conductor = \App\Models\LlamadaConductor::createFromArcangel($vehiculo, $ciudadOrigen);
```

**DESPUÉS:**
```php
// Preparar datos de cotización
$cotizacionData = [
    'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
    'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
    'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
    'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
    'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
    'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
];

$conductor = \App\Models\LlamadaConductor::createFromArcangel(
    $vehiculo,
    $ciudadOrigen,
    $cotizacion->id,
    $cotizacion->group_cotization_id,
    $cotizacionData
);
```

### 2. Actualizado `initiateGroupConversationalCall` (app/Http/Controllers/ConversationalAgentController.php ~línea 1601)

**ANTES:**
```php
$conductor = \App\Models\LlamadaConductor::createFromArcangel($vehiculo, $ciudadOrigen);
```

**DESPUÉS:**
```php
// Preparar datos de cotización
$cotizacionData = [
    'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
    'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
    'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
    'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
    'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
    'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
];

$conductor = \App\Models\LlamadaConductor::createFromArcangel(
    $vehiculo,
    $ciudadOrigen,
    $cotizacion->id,
    $cotizacion->group_cotization_id,
    $cotizacionData
);
```

### 3. `registerCallsForCotization` ya estaba corregido (línea ~1802)

## FLUJOS DEL SISTEMA

### Flujo A: Llamadas desde Dashboard "Registrar Llamadas"
```
Usuario hace clic en "Registrar Llamadas"
    ↓
app/Livewire/CallsManager.php::registrarLlamadasParaGrupo()
    ↓
POST /api/call-drivers-group
    ↓
ConversationalAgentController::initiateGroupConversationalCall()
    ↓
LlamadaConductor::createFromArcangel() ✅ CON cotización_id
```

### Flujo B: API directa para llamadas individuales
```
POST /api/conversational-agent/call-drivers
    ↓
ConversationalAgentController::initiateConversationalCall()
    ↓
LlamadaConductor::createFromArcangel() ✅ CON cotización_id
```

### Flujo C: Registro manual de llamadas
```
POST /api/conversational-agent/register-calls
    ↓
ConversationalAgentController::registerCallsForCotization()
    ↓
LlamadaConductor::createFromArcangel() ✅ CON cotización_id
```

## RUTAS API AFECTADAS

```php
// routes/api.php

Route::post('/call-drivers', [ConversationalAgentController::class, 'initiateConversationalCall'])
    ->name('conversational-agent.call-drivers');

Route::post('/call-drivers-group', [ConversationalAgentController::class, 'initiateGroupConversationalCall'])
    ->name('conversational-agent.call-drivers-group');
```

## CAMPOS QUE AHORA SE ALMACENAN

En **tabla `llamadas_conductores`** desde la creación:

| Campo | Tipo | Fuente | Fallback |
|-------|------|--------|----------|
| `cotizacion_id` | bigint | `$cotizacion->id` | - |
| `group_cotization_id` | bigint | `$cotizacion->group_cotization_id` | NULL |
| `ciudad_origen` | varchar | `$cotizacion->ciudad_origen` | $ciudadOrigen (parámetro) |
| `ciudad_destino` | varchar | `$cotizacion->ciudad_destino` | "No especificado" |
| `mercancia` | varchar | `$cotizacion->tipo_mercancia` | "Carga general" |
| `peso_carga` | decimal | `$cotizacion->peso_mercancia` | NULL (convertido a float) |
| `empaque` | varchar | `$cotizacion->tipo_embajale` | "Empaque ID: X" o NULL |
| `vehiculo_silogtran` | varchar | `$cotizacion->vehiculo_requerido` | NULL |

En **JSON `datos_adicionales`**:
```json
{
  "placa": "ABC123",
  "conductor": "NOMBRE",
  "telefono": "3001234567",
  "clase": "TRACTOMULA3",
  "cotizacion": {
    "id": 719,
    "group_id": 39,
    "vehiculo_requerido": "Tractomula de 3 ejes...",
    "ciudad_origen": "cartagena",
    "ciudad_destino": "funza",
    "tipo_mercancia": "Carga general",
    "peso_mercancia": "18400",
    "peso_carga_numerico": 18400.0,
    "empaque": "Empaque ID: 2",
    "tipo_embajale_original": "2"
  }
}
```

## ARCHIVOS MODIFICADOS

1. **app/Models/LlamadaConductor.php**
   - Método `createFromArcangel()` refactorizado
   - Manejo robusto de valores NULL
   - Conversión automática de tipos
   - Construcción completa de `datos_adicionales`

2. **app/Http/Controllers/ConversationalAgentController.php**
   - `initiateConversationalCall()` - línea ~1010
   - `initiateGroupConversationalCall()` - línea ~1601  
   - `registerCallsForCotization()` - línea ~1802 (ya estaba)

## SCRIPTS DE PRUEBA Y VERIFICACIÓN

### Script principal: `verificar_sistema_completo.sh`
```bash
./verificar_sistema_completo.sh
```

Muestra:
- Última cotización disponible
- Conductores registrados para esa cotización
- Últimos 5 conductores creados
- Estadísticas (con/sin cotizacion_id)
- Distribución por cotización
- Llamadas asociadas

### Monitoreo en tiempo real:
```bash
tail -f storage/logs/laravel.log | grep -E '(🔍|✅|Conductor creado)'
```

## PRUEBA COMPLETA

### Opción A - Dashboard (Recomendado):
1. Abrir dashboard de cotizaciones
2. Buscar cotización ID 719
3. Hacer clic en "Registrar Llamadas"
4. Ejecutar `./verificar_sistema_completo.sh`

### Opción B - API directa:
```bash
curl -X POST http://[servidor]/api/call-drivers-group \
  -H 'Content-Type: application/json' \
  -d '{"group_cotization_id": 39}'
```

### Opción C - Comandos artisan:
```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Verificar rutas
php artisan route:list | grep -i conversational
```

## VERIFICACIÓN EN BASE DE DATOS

```sql
-- Ver últimos conductores con datos completos
SELECT 
    id,
    cotizacion_id,
    group_cotization_id,
    nombre_conductor,
    ciudad_origen,
    ciudad_destino,
    mercancia,
    peso_carga,
    empaque,
    created_at
FROM llamadas_conductores 
ORDER BY id DESC 
LIMIT 10;

-- Verificar conductores de una cotización específica
SELECT * FROM llamadas_conductores WHERE cotizacion_id = 719;

-- Estadísticas
SELECT 
    cotizacion_id,
    COUNT(*) as total,
    GROUP_CONCAT(DISTINCT ciudad_origen) as ciudades
FROM llamadas_conductores 
WHERE cotizacion_id IS NOT NULL
GROUP BY cotizacion_id
ORDER BY cotizacion_id DESC;
```

## NOTAS IMPORTANTES

1. **Los registros 67-69 seguirán con NULL** porque se crearon ANTES de las correcciones
2. **Nuevos registros tendrán todos los campos** llenos desde el primer momento
3. **Operación atómica** - Una sola llamada a `updateOrCreate()` con todos los datos
4. **Valores por defecto** - Sistema robusto que maneja NULL en cotizaciones
5. **Trazabilidad completa** - Logs detallados en cada paso

## PRÓXIMA ACCIÓN

**PROBAR AHORA** haciendo clic en "Registrar Llamadas" desde el dashboard y verificar que los nuevos registros (ID >= 70) tengan `cotizacion_id` y todos los campos poblados.

```bash
# Antes de probar, limpiar cachés
php artisan config:clear && php artisan route:clear

# Después de probar, verificar
./verificar_sistema_completo.sh
```

## RESULTADO ESPERADO

Nuevos registros en `llamadas_conductores` con:
- ✅ `cotizacion_id` = 719
- ✅ `group_cotization_id` = 39
- ✅ `ciudad_origen` = "CARTAGENA" o "cartagena"
- ✅ `ciudad_destino` = "FUNZA" o "funza"
- ✅ `mercancia` = "Carga general" (porque tipo_mercancia es NULL)
- ✅ `peso_carga` = 18400.00
- ✅ `empaque` = "Empaque ID: 2"
- ✅ `vehiculo_silogtran` = "Tractomula de 3 ejes con capacidad de 20 toneladas"
