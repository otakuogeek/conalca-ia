# SISTEMA DE GESTIÓN DE COLA DE LLAMADAS
## Sistema de Llamadas con Límite de Concurrencia 3x3 y Retraso de 90 Segundos

---

## 📋 DESCRIPCIÓN GENERAL

El sistema de gestión de cola de llamadas está diseñado para manejar llamadas telefónicas con las siguientes características principales:

- **Límite de Concurrencia**: Máximo 3 llamadas simultáneas
- **Retraso entre Lotes**: 90 segundos entre cada grupo de 3 llamadas
- **Gestión Automática de Prioridades**: Sistema de priorización inteligente
- **Reintentos Automáticos**: Hasta 3 intentos para llamadas fallidas
- **Timeout Automático**: Limpieza de llamadas atascadas (120 segundos)

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Nuevos Campos en la Tabla `llamadas`

```sql
-- Estado de la cola
queue_status ENUM('pending', 'queued', 'processing', 'completed', 'failed', 'cancelled')
    DEFAULT 'pending'
    COMMENT 'Estado en la cola de llamadas'

-- Lote de llamadas (grupo de 3 llamadas)
batch_number INT NULL
    COMMENT 'Número de lote (grupo de 3 llamadas)'

-- Posición dentro del lote (1, 2 o 3)
batch_position TINYINT NULL
    COMMENT 'Posición dentro del lote (1-3)'

-- Prioridad en la cola (menor número = mayor prioridad)
queue_priority INT DEFAULT 10
    COMMENT 'Prioridad en la cola (1-10, menor = mayor prioridad)'

-- Timestamps del ciclo de vida en la cola
queued_at TIMESTAMP NULL
    COMMENT 'Momento en que se agregó a la cola'

processing_started_at TIMESTAMP NULL
    COMMENT 'Momento en que comenzó el procesamiento'

processing_completed_at TIMESTAMP NULL
    COMMENT 'Momento en que terminó el procesamiento'

-- Tiempo estimado de espera en segundos
estimated_wait_seconds INT NULL
    COMMENT 'Tiempo estimado de espera antes de iniciar'

-- Intentos de procesamiento
processing_attempts INT DEFAULT 0
    COMMENT 'Número de intentos de procesamiento'

-- Timestamp del último intento
last_attempt_at TIMESTAMP NULL
    COMMENT 'Momento del último intento de procesamiento'
```

### Índices Agregados

```sql
-- Índice para procesamiento eficiente de la cola
INDEX idx_queue_processing (queue_status, queue_priority, queued_at)

-- Índice para gestión de lotes
INDEX idx_batch_management (batch_number, batch_position)

-- Índice para tiempos de procesamiento
INDEX idx_processing_time (processing_started_at)
```

---

## 🔧 COMPONENTES DEL SISTEMA

### 1. CallQueueService

**Ubicación**: `app/Services/CallQueueService.php`

**Responsabilidades**:
- Encolar llamadas con gestión de lotes
- Procesar lotes respetando límites de concurrencia
- Gestionar reintentos automáticos
- Limpiar llamadas atascadas
- Proporcionar estadísticas de la cola

**Configuración**:
```php
const MAX_CONCURRENT_CALLS = 3;         // Máximo 3 llamadas simultáneas
const BATCH_DELAY_SECONDS = 90;         // 90 segundos entre lotes
const MAX_PROCESSING_ATTEMPTS = 3;      // 3 intentos máximos
const CALL_TIMEOUT_SECONDS = 120;       // 2 minutos de timeout
```

### 2. ProcessCallQueueCommand

**Ubicación**: `app/Console/Commands/ProcessCallQueueCommand.php`

**Comando**: `php artisan calls:process-queue`

**Opciones**:
```bash
# Procesar un solo lote
php artisan calls:process-queue

# Procesar continuamente (modo daemon)
php artisan calls:process-queue --continuous

# Ver solo estadísticas
php artisan calls:process-queue --stats

# Limpiar llamadas atascadas
php artisan calls:process-queue --clean
```

### 3. EnqueuePendingCallsCommand

**Ubicación**: `app/Console/Commands/EnqueuePendingCallsCommand.php`

**Comando**: `php artisan calls:enqueue-pending`

**Opciones**:
```bash
# Encolar con prioridad por defecto (5)
php artisan calls:enqueue-pending

# Encolar con prioridad específica
php artisan calls:enqueue-pending --priority=1
```

---

## 🚀 FLUJO DE TRABAJO

### 1. Creación de Llamadas

Cuando se llama al endpoint `/api/call-drivers`:

```php
POST /api/call-drivers
Content-Type: application/x-www-form-urlencoded

cotizacion_model_id=31
```

**Proceso**:
1. Se buscan los conductores disponibles
2. Para cada conductor se crea un registro en `llamadas` con `queue_status='pending'`
3. Se encola cada llamada con prioridad y lote asignados
4. Se calcula el tiempo estimado de espera
5. Se retorna respuesta con información de la cola

**Respuesta**:
```json
{
    "message": "Llamadas agregadas al sistema de cola",
    "total_drivers": 5,
    "calls_created": 5,
    "calls_enqueued": 5,
    "cotizacion_id": 31,
    "vehicle_type": "tracto mula s3",
    "queue_info": {
        "mode": "batch_processing",
        "max_concurrent": 3,
        "batch_delay_seconds": 90,
        "total_in_queue": 5,
        "total_processing": 0,
        "estimated_wait_time": "0 segundos",
        "can_process_now": true
    },
    "next_steps": "Las llamadas se procesarán automáticamente..."
}
```

### 2. Procesamiento de la Cola

#### Modo Manual (Un Lote)
```bash
php artisan calls:process-queue
```

Procesa un solo lote de hasta 3 llamadas.

#### Modo Continuo (Recomendado)
```bash
php artisan calls:process-queue --continuous
```

Procesa llamadas continuamente:
- Cada 30 segundos verifica si hay llamadas pendientes
- Respeta el límite de 3 llamadas concurrentes
- Espera 90 segundos después de cada lote
- Se ejecuta indefinidamente hasta interrumpir (Ctrl+C)

### 3. Gestión Automática

El sistema incluye:

- **Reintentos Automáticos**: Si una llamada falla, se reencola automáticamente con mayor prioridad
- **Limpieza de Timeout**: Llamadas que toman más de 120 segundos se marcan como fallidas
- **Priorización**: Llamadas fallidas obtienen mayor prioridad para próximo intento

---

## 📊 ESTADOS DE LA COLA

### queue_status

| Estado | Descripción |
|--------|-------------|
| `pending` | Llamada creada pero no en cola |
| `queued` | En cola esperando procesamiento |
| `processing` | Siendo procesada actualmente |
| `completed` | Completada exitosamente |
| `failed` | Fallida (sin más reintentos) |
| `cancelled` | Cancelada manualmente |

---

## 🔍 MONITOREO Y ESTADÍSTICAS

### Ver Estadísticas
```bash
php artisan calls:process-queue --stats
```

**Salida**:
```
📊 ESTADÍSTICAS DE LA COLA DE LLAMADAS
=============================================================
+----------------------+----------+
| Estado               | Cantidad |
+----------------------+----------+
| 🕐 Pendientes        | 0        |
| 📋 En cola           | 15       |
| 🔄 Procesando        | 3        |
| ✅ Completadas (24h) | 50       |
| ❌ Fallidas (24h)    | 2        |
| 📞 Llamadas activas  | 3        |
+----------------------+----------+

¿Puede procesar nuevo lote? ❌ No
Tiempo de espera: 45 segundos
Próximo lote: #6
```

### Limpiar Llamadas Atascadas
```bash
php artisan calls:process-queue --clean
```

Limpia llamadas que llevan más de 120 segundos en estado `processing`.

---

## ⚙️ CONFIGURACIÓN DEL SISTEMA

### Variables de Configuración

En `CallQueueService.php`:

```php
// Número máximo de llamadas concurrentes
const MAX_CONCURRENT_CALLS = 3;

// Tiempo de espera entre lotes (segundos)
const BATCH_DELAY_SECONDS = 90;

// Número máximo de intentos por llamada
const MAX_PROCESSING_ATTEMPTS = 3;

// Timeout para llamadas (segundos)
const CALL_TIMEOUT_SECONDS = 120;
```

### Ajustes Recomendados por Escenario

#### Alta Demanda (Más Llamadas)
```php
const MAX_CONCURRENT_CALLS = 3;      // Mantener límite SIP
const BATCH_DELAY_SECONDS = 60;      // Reducir a 60 segundos
const MAX_PROCESSING_ATTEMPTS = 2;   // Reducir intentos
```

#### Baja Demanda (Pocas Llamadas)
```php
const MAX_CONCURRENT_CALLS = 3;      // Mantener límite SIP
const BATCH_DELAY_SECONDS = 120;     // Aumentar a 120 segundos
const MAX_PROCESSING_ATTEMPTS = 5;   // Más intentos
```

---

## 🛠️ MANTENIMIENTO Y TROUBLESHOOTING

### Problema: Llamadas Atascadas

**Síntoma**: Llamadas en estado `processing` por mucho tiempo

**Solución**:
```bash
php artisan calls:process-queue --clean
```

### Problema: Cola Llena

**Síntoma**: Muchas llamadas en `queued` sin procesar

**Solución**:
```bash
# Iniciar procesador continuo
php artisan calls:process-queue --continuous
```

### Problema: Llamadas Pendientes sin Encolar

**Síntoma**: Llamadas en `pending` que no avanzan

**Solución**:
```bash
php artisan calls:enqueue-pending
```

### Logs

Los logs del sistema se encuentran en:
```
storage/logs/laravel.log
```

Búsqueda de errores:
```bash
tail -f storage/logs/laravel.log | grep "ERROR"
tail -f storage/logs/laravel.log | grep "CallQueue"
```

---

## 📝 LOGS Y TRAZABILIDAD

El sistema registra:

- ✅ Creación de llamadas
- ✅ Encolamiento con lote y posición
- ✅ Inicio de procesamiento
- ✅ Resultados de llamadas
- ✅ Reintentos
- ✅ Timeouts
- ✅ Limpiezas automáticas

Ejemplo de log:
```
[2025-10-06 08:30:15] Llamada #45 agregada a la cola
    batch_number: 3
    batch_position: 2
    estimated_wait: 270 segundos
    priority: 5

[2025-10-06 08:34:30] Lote #3 listo para procesar
    calls_count: 3
    calls: [45, 46, 47]

[2025-10-06 08:34:35] Llamada #45 marcada como completada
```

---

## 🔐 SEGURIDAD Y PERFORMANCE

### Límites del Sistema SIP

- **Máximo 3 llamadas concurrentes** (límite de la central)
- **90 segundos entre lotes** (tiempo promedio de llamada)
- **Timeout de 120 segundos** (previene bloqueos)

### Optimizaciones

1. **Índices de Base de Datos**: Optimizan consultas de cola
2. **Procesamiento en Lote**: Reduce overhead
3. **Reintentos Inteligentes**: Mayor prioridad para fallos
4. **Limpieza Automática**: Previene acumulación de recursos

---

## 📞 INTEGRACIÓN CON ELEVENLABS

El sistema está integrado con ElevenLabs para:

- Generación de voz en tiempo real
- Agentes conversacionales IA
- Tracking de llamadas
- Webhooks de estado

---

## 🎯 CASOS DE USO

### Caso 1: Llamar a 50 Conductores

```php
// 1. Crear llamadas (automático desde /api/call-drivers)
POST /api/call-drivers
cotizacion_model_id=31

// 2. Iniciar procesador continuo
php artisan calls:process-queue --continuous

// Resultado:
// - Lote 1: 3 llamadas (0-90 segundos)
// - Lote 2: 3 llamadas (90-180 segundos)
// - Lote 3: 3 llamadas (180-270 segundos)
// ... continúa hasta completar las 50
```

### Caso 2: Llamadas Urgentes (Alta Prioridad)

```php
// Encolar con prioridad 1 (máxima)
php artisan calls:enqueue-pending --priority=1
```

### Caso 3: Monitoreo en Tiempo Real

```bash
# Terminal 1: Procesador
php artisan calls:process-queue --continuous

# Terminal 2: Estadísticas cada 30 segundos
watch -n 30 'php artisan calls:process-queue --stats'

# Terminal 3: Logs en vivo
tail -f storage/logs/laravel.log | grep CallQueue
```

---

## 🚦 COMANDOS RÁPIDOS

```bash
# Ver estadísticas
php artisan calls:process-queue --stats

# Procesar un lote
php artisan calls:process-queue

# Procesar continuamente (recomendado para producción)
php artisan calls:process-queue --continuous

# Encolar llamadas pendientes
php artisan calls:enqueue-pending

# Encolar con prioridad alta
php artisan calls:enqueue-pending --priority=1

# Limpiar llamadas atascadas
php artisan calls:process-queue --clean

# Revisar logs
tail -f storage/logs/laravel.log | grep -E "(CallQueue|Llamada)"
```

---

## 📈 MÉTRICAS Y KPIs

El sistema permite rastrear:

- **Tasa de Éxito**: Llamadas completadas vs fallidas
- **Tiempo Promedio**: Duración media de llamadas
- **Tiempo de Espera**: Tiempo promedio en cola
- **Reintentos**: Número promedio de intentos
- **Throughput**: Llamadas procesadas por hora

---

## 🔮 FUTURAS MEJORAS

Posibles mejoras al sistema:

1. **Dashboard Web**: Interfaz visual para monitoreo
2. **Alertas Automáticas**: Notificaciones por email/SMS
3. **Priorización Dinámica**: Basada en reglas de negocio
4. **Escalado Horizontal**: Múltiples procesadores
5. **Analytics Avanzado**: Reportes y gráficos

---

## 📚 REFERENCIAS

- Migración: `database/migrations/2025_10_06_081927_add_queue_management_fields_to_llamadas_table.php`
- Servicio: `app/Services/CallQueueService.php`
- Comando Procesador: `app/Console/Commands/ProcessCallQueueCommand.php`
- Comando Encolador: `app/Console/Commands/EnqueuePendingCallsCommand.php`
- Modelo: `app/Models/Llamada.php`
- Controlador: `app/Http/Controllers/CallController.php`

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Migración de base de datos
- [x] Servicio de gestión de cola
- [x] Comando de procesamiento
- [x] Comando de encolamiento
- [x] Actualización del modelo
- [x] Integración con CallController
- [x] Sistema de reintentos
- [x] Limpieza automática
- [x] Logging completo
- [x] Documentación

---

## 💡 CONTACTO Y SOPORTE

Para más información o soporte:
- Revisar logs en `storage/logs/laravel.log`
- Ejecutar `php artisan calls:process-queue --stats`
- Verificar estado de la cola en base de datos

---

**Versión**: 1.0  
**Fecha**: Octubre 6, 2025  
**Sistema**: Conalca IA Transport
