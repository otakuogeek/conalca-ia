# Sistema de Llamadas - Configuración Actualizada

## ✅ CAMBIOS IMPLEMENTADOS

### 1. Llamadas de 2 en 2 (en lugar de 3 en 3)
- **Archivo**: `ConversationalAgentController.php`
- **Línea**: ~1623
- **Cambio**: `$maxConcurrentCalls = 2` (era 3)

### 2. Delay de 1 minuto entre lotes
- **Archivo**: `ConversationalAgentController.php`  
- **Línea**: ~2377
- **Cambio**: `dispatch($cotizacionId, 1, $maxConcurrentCalls, 60)` (era 90)
- **Archivo**: `ProcessBatchElevenLabsCalls.php` línea ~23
- **Cambio**: Parámetro por defecto `$delayBetweenBatches = 60` (era 90)

### 3. Detener en 7 confirmados
- **Archivo**: `ProcessBatchElevenLabsCalls.php`
- **Línea**: ~51 (nuevo código)
- **Lógica**: Antes de procesar cada lote, verifica si ya hay 7 confirmados
- **Acción**: Si hay 7+ confirmados, cancela todas las llamadas pendientes restantes

## 📋 LÓGICA ACTUAL

### Flujo de Llamadas:
1. ✅ Usuario hace clic en "Registrar Llamadas"
2. ✅ Sistema busca conductores disponibles
3. ✅ Muestra modal de vista previa con N conductores
4. ✅ Usuario confirma
5. ✅ Sistema registra llamadas en BD
6. ✅ Usuario hace clic en "Iniciar Llamadas"
7. ✅ Sistema organiza llamadas en lotes de 2
8. ✅ Procesa lotes con 60 segundos de delay entre lotes
9. ✅ Dentro de cada lote: 60 segundos entre llamada 1 y llamada 2
10. ✅ Verifica antes de cada lote si ya hay 7 confirmados → DETIENE si cumple

### Timing Actual:
- **Lote 1**: Llamada 1 (t=0), Llamada 2 (t=60s)
- **Delay**: 60 segundos
- **Lote 2**: Llamada 3 (t=120s), Llamada 4 (t=180s)
- **Delay**: 60 segundos
- **Lote 3**: Llamada 5 (t=240s), Llamada 6 (t=300s)
- Y así sucesivamente...

## ⚠️ FUNCIONALIDADES PENDIENTES

### 1. Sistema de Reintentos para No Contestados
**Estado**: ❌ NO IMPLEMENTADO

**Requisito**: Si un conductor no contesta, saltar turno y volver a llamar después

**Implementación necesaria**:
```php
// En ProcessElevenLabsCall.php o en un Job de seguimiento
- Detectar estado "no contestada" (no_answer, busy, failed)
- Agregar campo "intentos" en tabla llamadas
- Si intentos < 3: reprogramar la llamada al final de la cola
- Reorganizar llamadas: mover no contestadas al final
```

**Tablas a modificar**:
```sql
ALTER TABLE llamadas ADD COLUMN intentos INT DEFAULT 0;
ALTER TABLE llamadas ADD COLUMN ultimo_intento_at TIMESTAMP NULL;
```

### 2. Detener si Lista Tiene Menos de 7
**Estado**: ⚠️ PARCIALMENTE IMPLEMENTADO

**Requisito**: Si la lista de conductores disponibles tiene menos de 7, llamar solo a los disponibles

**Implementación actual**: 
- ✅ Sistema llama a TODOS los conductores encontrados
- ✅ Se detiene cuando hay 7 confirmados
- ❌ No verifica si la lista inicial tiene < 7 para ajustar lógica

**Mejora necesaria**:
```php
// En initiateGroupConversationalCall
$conductoresDisponibles = count($vehiculos);
$targetConfirmados = min(7, $conductoresDisponibles);

// Pasar este target al Job para que se detenga en ese número
ProcessBatchElevenLabsCalls::dispatch(
    $cotizacionId, 
    1, 
    $maxConcurrentCalls, 
    60,
    $targetConfirmados // <-- Nuevo parámetro
)
```

## 📊 RESUMEN DE CONFIGURACIÓN ACTUAL

| Parámetro | Valor | Estado |
|-----------|-------|--------|
| Llamadas por lote | 2 | ✅ Implementado |
| Delay entre lotes | 60s | ✅ Implementado |
| Delay dentro de lote | 60s | ✅ Implementado |
| Detener en 7 confirmados | Sí | ✅ Implementado |
| Reintentos no contestados | No | ❌ Pendiente |
| Ajuste para < 7 disponibles | Parcial | ⚠️ Mejorar |

## 🔧 PRÓXIMOS PASOS RECOMENDADOS

1. **Migración BD**: Agregar campos para reintentos
2. **Job de Reintentos**: Crear `RetryFailedCallsJob`
3. **Webhook Handler**: Mejorar manejo de estados desde ElevenLabs
4. **Dashboard**: Agregar visualización en tiempo real de llamadas activas/confirmadas

## 📝 NOTAS

- El sistema actual funciona correctamente para el flujo básico
- Los reintentos requieren monitoreo de webhooks de ElevenLabs
- Considerar agregar límite de tiempo total para las llamadas (ej: 30 minutos máximo)
