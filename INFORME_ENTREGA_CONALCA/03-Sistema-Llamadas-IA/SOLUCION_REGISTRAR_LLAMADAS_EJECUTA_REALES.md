# SOLUCIÓN: Botón "Registrar Llamadas" Ahora Ejecuta Llamadas Reales

## Problema Identificado

El botón "Registrar Llamadas" en el dashboard solo estaba guardando datos en la base de datos (`llamadas_conductores` y `llamadas`) pero **NO estaba ejecutando las llamadas reales** a través de ElevenLabs.

## Solución Implementada

### 1. Sistema de Lotes Agregado al Registro

**Archivo modificado:** `app/Http/Controllers/ConversationalAgentController.php`  
**Método:** `registerCallsForCotization()`

#### Cambios realizados:

```php
// ANTES: Solo registraba en base de datos
foreach ($vehiculos as $vehiculo) {
    // Crear LlamadaConductor
    // Crear Llamada con status = 'pendiente'
    // NO ejecutaba llamadas
}

// DESPUÉS: Registra Y programa ejecución
foreach ($vehiculos as $index => $vehiculo) {
    // Calcular lote y posición
    $batchNumber = floor($index / 3) + 1;
    $batchPosition = ($index % 3) + 1;
    
    // Crear Llamada con campos de cola
    Llamada::create([
        'queue_status' => 'pending',
        'batch_number' => $batchNumber,
        'batch_position' => $batchPosition,
        'queued_at' => now(),
        // ... otros campos
    ]);
}

// NUEVO: Despachar job para ejecutar las llamadas
if (!empty($registeredCalls)) {
    ProcessBatchElevenLabsCalls::dispatch(
        $cotizacion->id,
        1, // Comenzar con batch 1
        3, // Máximo 3 llamadas concurrentes
        150 // 2.5 minutos entre lotes
    )->onQueue('calls');
}
```

### 2. Flujo Completo de Ejecución

```
┌─────────────────────────────────────┐
│ Usuario: Clic en "Registrar         │
│          Llamadas"                  │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ CallsManager.php (Livewire)         │
│ → registrarLlamadasParaGrupo()      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ API: /api/call-drivers-group        │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ ConversationalAgentController       │
│ → initiateGroupConversationalCall() │
│   → registerCallsForCotization()    │
└──────────────┬──────────────────────┘
               │
               ├─► 1. Buscar conductores en Arcángel
               │
               ├─► 2. Crear LlamadaConductor por cada uno
               │
               ├─► 3. Crear Llamada con batch_number
               │
               └─► 4. NUEVO: Despachar ProcessBatchElevenLabsCalls
                              │
                              ▼
               ┌──────────────────────────────────────┐
               │ ProcessBatchElevenLabsCalls (Job)    │
               │ - Procesa lotes de 3 llamadas        │
               │ - Delay de 5s entre llamadas         │
               │ - Delay de 150s entre lotes          │
               └──────────────┬───────────────────────┘
                              │
                              ├─► ProcessElevenLabsCall (por cada llamada)
                              │   │
                              │   └─► ElevenLabsCallService
                              │       │
                              │       └─► API ElevenLabs (llamada real)
                              │
                              └─► Auto-encadenamiento para siguiente lote
```

### 3. Respuesta Mejorada del Endpoint

El endpoint ahora retorna información del sistema de lotes:

```json
{
  "success": true,
  "cotizacion_id": 697,
  "drivers_registered": [
    {
      "llamada_id": 46,
      "batch_number": 1,
      "batch_position": 1,
      "status": "registrado"
    },
    {
      "llamada_id": 47,
      "batch_number": 1,
      "batch_position": 2,
      "status": "registrado"
    }
  ],
  "total_calls": 5,
  "calls_dispatched": true,
  "batch_system": {
    "enabled": true,
    "total_batches": 2,
    "max_concurrent": 3
  }
}
```

## Verificación del Sistema

### Script Creado: `verificar_sistema_llamadas.sh`

Ejecutar con:
```bash
./verificar_sistema_llamadas.sh
```

Este script verifica:
- ✅ Estructura de tabla `llamadas` (campos de cola)
- ✅ Jobs `ProcessBatchElevenLabsCalls` y `ProcessElevenLabsCall`
- ⚠️ Workers de cola corriendo
- ✅ Configuración de ElevenLabs
- ✅ Estado de tablas relacionadas
- ✅ Últimas llamadas registradas

## Iniciar el Worker de Cola

**IMPORTANTE:** Para que las llamadas se ejecuten, debe haber un worker corriendo:

```bash
php artisan queue:work --queue=calls --tries=3 --timeout=300
```

### Configuración Permanente (Supervisor)

Para producción, configurar Supervisor:

```ini
[program:conalca-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/ubuntu/conalca/conalca/artisan queue:work --queue=calls --tries=3 --timeout=300
autostart=true
autorestart=true
user=ubuntu
numprocs=1
redirect_stderr=true
stdout_logfile=/home/ubuntu/conalca/conalca/storage/logs/queue-worker.log
```

## Monitoreo en Tiempo Real

### Ver logs de ejecución de llamadas:
```bash
tail -f storage/logs/laravel.log | grep -E '(ProcessBatch|ProcessEleven|Llamada)'
```

### Verificar cola de trabajos:
```bash
php artisan queue:monitor calls
```

### Ver estado de llamadas en base de datos:
```bash
php artisan tinker
>>> App\Models\Llamada::where('queue_status', 'pending')->count()
>>> App\Models\Llamada::where('queue_status', 'processing')->count()
```

## Estado Actual de Llamadas en BD

Según la verificación ejecutada:

- **Total llamadas registradas:** 5 llamadas en tabla `llamadas`
- **Estado:** Todas con `queue_status = 'pending'`
- **Lotes configurados:** 
  - Batch 1: 3 llamadas (IDs: 46, 48)
  - Batch 2: 2 llamadas (IDs: 49, 50, 51)
- **Cotización:** ID 697

## Prueba del Sistema

### Pasos para probar:

1. **Iniciar worker:**
   ```bash
   php artisan queue:work --queue=calls --tries=3 --timeout=300
   ```

2. **En otra terminal, monitorear logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -E '(ProcessBatch|ElevenLabs|Llamada)'
   ```

3. **Ir al dashboard** y hacer clic en "Registrar Llamadas" para una cotización

4. **Observar en logs:**
   - Registro de llamadas en BD
   - Dispatch del job ProcessBatchElevenLabsCalls
   - Procesamiento de cada lote
   - Ejecución de llamadas individuales
   - Respuestas de ElevenLabs

## Archivos Modificados

1. **app/Http/Controllers/ConversationalAgentController.php**
   - Método `registerCallsForCotization()`: Agregado sistema de lotes y dispatch del job

2. **verificar_sistema_llamadas.sh** (nuevo)
   - Script de verificación completa del sistema

## Sistema de Lotes Explicado

### Configuración actual:
- **Máximo llamadas concurrentes:** 3
- **Delay entre llamadas del mismo lote:** 5 segundos
- **Delay entre lotes:** 150 segundos (2.5 minutos)

### Ejemplo con 7 conductores:

```
Lote 1 (batch_number=1):
  - Llamada 1 (batch_position=1) → +0s
  - Llamada 2 (batch_position=2) → +5s
  - Llamada 3 (batch_position=3) → +10s
  
[ESPERA 150 segundos]

Lote 2 (batch_number=2):
  - Llamada 4 (batch_position=1) → +0s
  - Llamada 5 (batch_position=2) → +5s
  - Llamada 6 (batch_position=3) → +10s

[ESPERA 150 segundos]

Lote 3 (batch_number=3):
  - Llamada 7 (batch_position=1) → +0s
```

## Siguiente Paso: Iniciar Worker

Para que el sistema funcione completamente, ejecuta:

```bash
cd /home/ubuntu/conalca/conalca
php artisan queue:work --queue=calls --tries=3 --timeout=300
```

O si prefieres ejecutarlo en segundo plano:

```bash
cd /home/ubuntu/conalca/conalca
nohup php artisan queue:work --queue=calls --tries=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &
```

---

**Fecha:** 5 de diciembre de 2025  
**Sistema:** CONALCA AI - Sistema de Llamadas Automatizadas  
**Estado:** ✅ Implementado - Requiere iniciar worker de cola
