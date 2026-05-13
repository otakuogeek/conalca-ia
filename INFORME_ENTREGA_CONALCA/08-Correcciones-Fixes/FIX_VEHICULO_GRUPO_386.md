# FIX GRUPO 386 - CAMPO VEHÍCULO NO SE MOSTRABA

## 📋 Problema Reportado

**Grupo de Cotización ID:** 386

**Síntoma:** El campo "vehículo" no se mostraba en el preview del sistema a pesar de que el usuario había especificado "vehículo turbo" y el asistente confirmó el cambio.

## 🔍 Diagnóstico

### 1. Análisis de Datos
Al consultar el `extracted_data` del grupo 386, encontramos:
```json
{
  "vehiculo": "TURBO",              // ✅ Correcto
  "vehiculo_requerido": "MINIMULA", // ❌ Valor antiguo
  "claseVehiculo": "MINIMULA"       // ❌ Valor antiguo (duplicado)
}
```

### 2. Análisis del Chat
El historial de chat muestra:
- Mensaje inicial: "el vehículo es Minimula" → Sistema actualiza a "MINIMULA"
- Mensaje posterior: "vehículo turbo" → Sistema confirma: "He actualizado el vehículo a 'TURBO'"

**Conclusión:** El sistema confirmó la actualización pero NO guardó el cambio en todos los campos duplicados.

### 3. Identificación del Campo Usado por Frontend
El `CallController.php` (línea 610) lee directamente:
```php
return $cotizacion->vehiculo_requerido;
```

**Problema:** El frontend/backend usa `vehiculo_requerido` pero la actualización solo modificó `vehiculo`.

### 4. Causa Raíz
El mensaje del usuario fue: **"vehículo turbo"** (sin conector como "es", "será", etc.)

Los patrones existentes en MCPAssistantService.php NO coincidían con este formato:
- ✅ Patrón 1: `vehículo ES turbo` (con conector)
- ✅ Patrón 2: `cambia vehículo a turbo` (con verbo de cambio)
- ❌ **FALTABA:** `vehículo turbo` (sin conector)

Para comparación, ya existían patrones sin conector para origen y destino:
- `/^(?:el\s+)?origen\s+([a-záéíóúñ\s]+)$/ui` → "origen cali"
- `/^(?:el\s+)?destino\s+([a-záéíóúñ\s]+)$/ui` → "destino bogotá"

## ✅ Soluciones Implementadas

### 1. Corrección Manual del Grupo 386
**Archivo:** Base de datos - tabla `group_cotizations`
**Acción:** Sincronización de los 3 campos de vehículo

```php
$extracted['vehiculo'] = 'TURBO';
$extracted['vehiculo_requerido'] = 'TURBO';
$extracted['claseVehiculo'] = 'TURBO';
```

**Resultado:**
```
Estado ANTES:
  vehiculo: TURBO
  vehiculo_requerido: MINIMULA ❌
  claseVehiculo: MINIMULA ❌

Estado DESPUÉS:
  vehiculo: TURBO ✅
  vehiculo_requerido: TURBO ✅
  claseVehiculo: TURBO ✅
```

### 2. Nuevo Patrón de Detección
**Archivo:** `app/Services/MCPAssistantService.php`
**Línea:** ~833 (después del patrón "cambia vehículo")

**Código Agregado:**
```php
} elseif ($esEdicionSimpleHabilitada && preg_match('/^(?:el\s+)?veh[ií]culo\s+([a-záéíóúñ\s]+)$/ui', $lastUserMessageForEdit, $matchVehiculoSimple)) {
    // 🆕 NUEVO: "vehículo turbo" o "el vehículo tractomula" (sin conector)
    $esEdicionSimpleTemprana = true;
    $campoEditadoTemprano = 'vehiculo';
    $vehiculoRaw = trim($matchVehiculoSimple[1]);
    $valorEditadoTemprano = strtoupper(preg_replace('/[^a-záéíóúñ\s]/ui', '', $vehiculoRaw));
```

**Cobertura del Nuevo Patrón:**
- ✅ "vehículo turbo"
- ✅ "vehiculo turbo" (sin tilde)
- ✅ "el vehículo turbo"
- ✅ "vehículo tractomula"
- ❌ "hola vehículo turbo hola" (no coincide - correcto, debe ser mensaje completo)

## 🧪 Validación

### Test Suite: `test_fix_vehiculo_386.php`

**TEST 1:** Verificar sincronización de campos de vehículo
- ✅ Campo 'vehiculo': TURBO
- ✅ Campo 'vehiculo_requerido': TURBO
- ✅ Campo 'claseVehiculo': TURBO

**TEST 2:** Verificar nuevo patrón de detección
- ✅ 'vehículo turbo' → MATCH
- ✅ 'vehiculo turbo' → MATCH
- ✅ 'el vehículo turbo' → MATCH
- ✅ 'vehículo es turbo' → MATCH
- ✅ 'cambia vehículo a turbo' → MATCH
- ✅ 'hola vehículo turbo hola' → NO MATCH (correcto)

**TEST 3:** Verificar que otros campos NO se dañaron
- ✅ empaque: TONEL
- ✅ peso_kg: 18400
- ✅ cantidad: 55
- ✅ producto: ANIMALES VALORADOS

**RESULTADO FINAL:** ✅ **3/3 TESTS PASARON**

## 📊 Impacto

### Problema Solucionado
- ✅ Grupo 386 ahora muestra correctamente el vehículo "TURBO"
- ✅ Frontend/backend pueden leer cualquiera de los 3 campos (están sincronizados)
- ✅ Sistema filtrará conductores por tipo de vehículo "TURBO" correctamente

### Prevención de Futuros Errores
- ✅ Nuevos mensajes tipo "vehículo X" (sin conector) se detectarán correctamente
- ✅ Lógica de mapeo de campos duplicados ya existía y funcionará:
  ```php
  $camposAActualizar = ['vehiculo', 'claseVehiculo', 'vehiculo_requerido'];
  ```

## ⚠️ Observaciones Técnicas

### Campos Duplicados
El sistema tiene 3 nombres de campo para el mismo dato (vehículo):
1. `vehiculo`
2. `vehiculo_requerido` ← **Frontend usa este**
3. `claseVehiculo`

**Lógica de Sincronización:**
Ya existe en MCPAssistantService.php (líneas 1154, 1194, 2123) que actualiza los 3 campos simultáneamente:
```php
'vehiculo' => ['vehiculo', 'claseVehiculo', 'vehiculo_requerido']
```

**Recomendación:** Considerar unificar a un solo nombre de campo en el futuro para evitar inconsistencias.

## 📁 Archivos Modificados

1. **app/Services/MCPAssistantService.php**
   - Línea ~833: Agregado patrón sin conector para vehículo
   - Función: `processChat()` - Detección de edición simple

2. **Base de Datos: group_cotizations (ID 386)**
   - Campo: `extracted_data`
   - Acción: Sincronización manual de vehiculo/vehiculo_requerido/claseVehiculo

## ✅ Checklist de Validación

- [x] Grupo 386 tiene todos los campos de vehículo sincronizados
- [x] Nuevo patrón agregado detecta "vehículo turbo"
- [x] Tests pasando 100% (3/3)
- [x] Otros campos del grupo 386 intactos
- [x] Lógica de mapeo de campos duplicados verificada
- [x] Frontend usa `vehiculo_requerido` (ahora tiene valor correcto)

## 🎯 Conclusión

**Problema:** Patrón de detección incompleto + campos duplicados desincronizados

**Solución:** 
1. Patrón nuevo para "vehículo X" (sin conector)
2. Sincronización manual de grupo 386
3. Lógica de mapeo existente funcionará para futuros casos

**Estado:** ✅ **RESUELTO Y VALIDADO**
