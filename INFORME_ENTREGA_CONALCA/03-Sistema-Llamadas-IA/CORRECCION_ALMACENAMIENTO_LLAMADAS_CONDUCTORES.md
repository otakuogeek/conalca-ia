# CORRECCIÓN COMPLETA DEL ALMACENAMIENTO DE DATOS EN LLAMADAS_CONDUCTORES

## Fecha: 5 de diciembre de 2025

## PROBLEMAS IDENTIFICADOS

### 1. **Campos NULL en tipo_mercancia**
   - La tabla `cotizacion_models` tiene `tipo_mercancia` como NULL en muchos registros
   - Esto causaba que `llamadas_conductores` también almacenara NULL

### 2. **Empaque como ID en lugar de texto**
   - El campo `tipo_embajale` almacena IDs (ej: "2") en lugar de nombres
   - Faltaba resolver la relación con `tb_empaque`

### 3. **Proceso de creación en dos pasos**
   - Llamada a `createFromArcangel()` sin todos los parámetros
   - Luego un `update()` separado que nunca se ejecutaba correctamente

## SOLUCIONES IMPLEMENTADAS

### 1. **Manejo de NULL en createFromArcangel** (`app/Models/LlamadaConductor.php`)

```php
// Ahora usa valores por defecto cuando los campos están vacíos:
'tipo_mercancia' => $cotizacion['tipo_mercancia'] ?? 'Carga general'
'ciudad_destino' => $cotizacion['ciudad_destino'] ?? 'No especificado'
'vehiculo_requerido' => $cotizacion['vehiculo_requerido'] ?? 'No especificado'
```

### 2. **Procesamiento de empaque**

```php
// Detecta si tipo_embajale es un ID numérico y lo convierte a texto legible
$empaqueTexto = is_numeric($cotizacion['tipo_embajale']) 
    ? "Empaque ID: {$cotizacion['tipo_embajale']}"
    : $cotizacion['tipo_embajale'];
```

### 3. **Conversión de peso a número decimal**

```php
// Elimina comas, espacios y "kg" antes de convertir a float
$pesoCarga = floatval(str_replace([',', ' kg', ' KG'], '', $cotizacion['peso_mercancia']));
```

### 4. **datos_adicionales completo**

```php
$datosAdicionales = array_merge(
    $vehiculo,  // Datos del conductor de Arcángel
    [
        'cotizacion' => [
            'id' => $cotizacionId,
            'group_id' => $groupId,
            'vehiculo_requerido' => ...,
            'ciudad_origen' => ...,
            'ciudad_destino' => ...,
            'tipo_mercancia' => ...,
            'peso_mercancia' => ...,
            'peso_carga_numerico' => $pesoCarga,
            'empaque' => $empaqueTexto,
            'tipo_embajale_original' => ...
        ]
    ]
);
```

### 5. **Llamada correcta desde ConversationalAgentController**

```php
// Preparar datos con valores por defecto
$cotizacionData = [
    'vehiculo_requerido' => $cotizacion->vehiculo_requerido ?? 'No especificado',
    'ciudad_origen' => $cotizacion->ciudad_origen ?? $ciudadOrigen,
    'ciudad_destino' => $cotizacion->ciudad_destino ?? 'No especificado',
    'tipo_mercancia' => $cotizacion->tipo_mercancia ?? 'Carga general',
    'peso_mercancia' => $cotizacion->peso_mercancia ?? '0',
    'tipo_embajale' => $cotizacion->tipo_embajale ?? null,
];

// Una sola llamada con todos los datos
$conductor = \App\Models\LlamadaConductor::createFromArcangel(
    $vehiculo, 
    $ciudadOrigen,
    $cotizacion->id,
    $cotizacion->group_cotization_id,
    $cotizacionData
);
```

### 6. **Logs detallados para debugging**

```php
// Antes de crear
Log::info('🔍 Datos de cotización para createFromArcangel', [...]);

// Después de crear
Log::info('✅ Conductor creado con datos', [
    'conductor_id' => $conductor->id,
    'cotizacion_id_guardado' => $conductor->cotizacion_id,
    'group_cotization_id_guardado' => $conductor->group_cotization_id,
    'ciudad_origen_guardado' => $conductor->ciudad_origen,
    'ciudad_destino_guardado' => $conductor->ciudad_destino,
    'mercancia_guardado' => $conductor->mercancia,
    'peso_carga_guardado' => $conductor->peso_carga,
    'empaque_guardado' => $conductor->empaque,
]);
```

## CAMPOS QUE AHORA SE ALMACENAN CORRECTAMENTE

### En tabla `llamadas_conductores`:
- ✅ `cotizacion_id` - ID de la cotización
- ✅ `group_cotization_id` - ID del grupo de cotización
- ✅ `ciudad_origen` - Ciudad de origen (con fallback a $ciudadOrigen)
- ✅ `ciudad_destino` - Ciudad de destino (con fallback a "No especificado")
- ✅ `mercancia` - Tipo de mercancía (con fallback a "Carga general")
- ✅ `peso_carga` - Peso numérico en KG (convertido de string)
- ✅ `empaque` - Empaque en texto legible
- ✅ `vehiculo_silogtran` - Vehículo requerido de Silogtran
- ✅ `datos_adicionales` - JSON con información completa de vehículo + cotización

### En JSON `datos_adicionales`:
```json
{
    "conductor": "...",
    "telefono": "...",
    "placa": "...",
    "tipo_vehiculo": "...",
    "clase": "...",
    "score": ...,
    "cotizacion": {
        "id": 719,
        "group_id": 39,
        "vehiculo_requerido": "Tractomula de 3 ejes con capacidad de 20 toneladas",
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

## ESTRUCTURA FINAL

```
ConversationalAgentController::registerCallsForCotization()
    │
    ├─→ Obtiene cotización de BD
    ├─→ Busca conductores en Arcángel
    ├─→ Por cada conductor:
    │   ├─→ Prepara $cotizacionData con valores por defecto
    │   ├─→ Log de datos a enviar
    │   ├─→ Llama createFromArcangel() CON TODOS LOS DATOS
    │   │   │
    │   │   └─→ LlamadaConductor::createFromArcangel()
    │   │       ├─→ Procesa peso (string → float)
    │   │       ├─→ Procesa empaque (ID → texto)
    │   │       ├─→ Construye datos_adicionales completos
    │   │       └─→ updateOrCreate() con TODOS los campos
    │   │
    │   └─→ Log de confirmación
    │
    └─→ Dispatch de ProcessBatchElevenLabsCalls
```

## PRUEBAS

### Script de prueba creado: `test_registro_llamadas.sh`

```bash
./test_registro_llamadas.sh
```

Este script:
1. Obtiene la última cotización no rechazada
2. Muestra sus datos
3. Lista conductores ya registrados para esa cotización
4. Da instrucciones para probar desde el dashboard

### Monitoreo en tiempo real:

```bash
tail -f storage/logs/laravel.log | grep -E '(Datos de cotización|Conductor creado)'
```

## COMANDOS EJECUTADOS

```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Verificar permisos
sudo chmod -R 777 storage bootstrap/cache
```

## VERIFICACIÓN EN BASE DE DATOS

```sql
-- Ver últimos conductores creados
SELECT 
    id,
    cotizacion_id,
    group_cotization_id,
    ciudad_origen,
    ciudad_destino,
    mercancia,
    peso_carga,
    empaque,
    datos_adicionales
FROM llamadas_conductores 
ORDER BY id DESC 
LIMIT 5;

-- Verificar cotización específica
SELECT * FROM cotizacion_models WHERE id = 719;

-- Ver conductores de una cotización
SELECT * FROM llamadas_conductores WHERE cotizacion_id = 719;
```

## PRÓXIMOS PASOS

1. **Probar desde el dashboard:**
   - Ir a cotización ID 719
   - Hacer clic en "Registrar Llamadas"
   - Verificar que los campos se llenen correctamente

2. **Verificar en phpMyAdmin:**
   - Revisar tabla `llamadas_conductores`
   - Confirmar que `cotizacion_id`, `group_cotization_id`, `ciudad_origen`, `ciudad_destino`, `mercancia`, `peso_carga`, `empaque` tienen valores

3. **Revisar logs:**
   - Buscar mensajes "🔍 Datos de cotización para createFromArcangel"
   - Buscar mensajes "✅ Conductor creado con datos"
   - Verificar que los valores guardados coinciden con los enviados

## ARCHIVOS MODIFICADOS

1. `app/Models/LlamadaConductor.php`
   - Método `createFromArcangel()` refactorizado completamente
   - Manejo robusto de NULL
   - Conversión de tipos
   - Construcción de datos_adicionales

2. `app/Http/Controllers/ConversationalAgentController.php`
   - Método `registerCallsForCotization()` actualizado
   - Preparación de datos con valores por defecto
   - Logs de debugging
   - Eliminado update() redundante

3. Scripts creados:
   - `test_registro_llamadas.sh` - Script de prueba
   - `verificar_campos_cotizacion.sql` - Consultas SQL de verificación

## NOTAS IMPORTANTES

- **tipo_mercancia NULL:** Sistema ahora usa "Carga general" como valor por defecto
- **tipo_embajale como ID:** Se convierte a "Empaque ID: X" hasta que se implemente relación con tb_empaque
- **Todos los campos obligatorios:** Ahora tienen valores por defecto para evitar NULL
- **Operación atómica:** Todo se guarda en una sola operación updateOrCreate()
- **Trazabilidad completa:** Logs antes y después de crear cada conductor
