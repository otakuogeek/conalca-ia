# 🔍 MÓDULO: ANÁLISIS › AUDITORÍA DE LLAMADAS

## 1. ¿Para qué es este módulo?
Es la **vista operativa de auditoría en tiempo real** de las llamadas IA. A diferencia del módulo de Analytics (que es comercial/ejecutivo), este es **operativo y forense**: permite escuchar el audio, leer transcript línea por línea con timestamps, ver el timeline completo de eventos webhook, y reconstruir exactamente qué pasó en cada llamada.

## 2. ¿Qué hace?
- Lista **todas las llamadas IA** con filtros avanzados.
- Muestra **estado en tiempo real** de la cola (cuántas en curso, cuántas pendientes, slots libres).
- Permite **descargar audio MP3** de cualquier llamada (almacenado por ElevenLabs).
- Permite **ver transcript** completo con timestamps de cada turno.
- Construye un **timeline cronológico** de eventos: marcando → timbrando → contestada → conversación → colgada.
- Permite **buscar por conductor** específico.
- Permite **ver detalle de un lote** (todas las llamadas de un grupo de cotización).
- Exporta a **CSV** para análisis externo.

## 3. ¿Cómo funciona?

### Flujo de uso típico (auditoría de incidente)
```
1. Comercial reporta: "el conductor dice que no le ofrecimos el precio correcto"
2. SAC entra a /auditoria-llamadas
3. Filtra por grupo de cotización o por conductor
4. Encuentra la llamada → click "Ver detalle"
5. Lee transcript línea por línea
6. Escucha audio para verificar inflexión/tono
7. Ve timeline de eventos (cuándo timbró, cuándo contestó, cuánto duró)
8. Si encuentra problema → reporta al equipo
```

### Flujo de tiempo real (durante una campaña de llamadas)
```
1. SAC entra a /auditoria-llamadas
2. Auto-refresh cada 5s (queueStatus endpoint)
3. Ve cuántas llamadas están "ringing" / "in_progress"
4. Si algo se atasca → puede colgar manualmente (/api/llamadas/{id}/hangup)
```

### Endpoint queueStatus
```
GET /auditoria-llamadas/queue-status
    ↓
Devuelve:
{
  active_calls: 2,
  available_slots: 0,
  pending: 15,
  provider: "zadarma",
  max_concurrent: 2
}
```

## 4. ¿Cómo actúa en el sistema?
Es **sólo lectura** sobre los datos generados por el sistema de llamadas IA, pero permite **acciones operativas críticas** (colgar llamada manualmente desde otro endpoint).

Consume datos de:
- `Llamada` (tabla principal de llamadas)
- `LlamadaConductor` (datos del conductor en cada llamada)
- `DriverCallResponse` (eventos ElevenLabs)
- Archivos de audio en disco / ElevenLabs API

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/auditoria-llamadas` | Vista principal |
| GET | `/auditoria-llamadas/api` | Datos paginados JSON |
| GET | `/auditoria-llamadas/batch/{groupId}` | Detalle de lote |
| GET | `/auditoria-llamadas/transcript/{conversationId}` | Descarga transcript |
| GET | `/auditoria-llamadas/audio/{conversationId}` | Descarga audio MP3 |
| GET | `/auditoria-llamadas/timeline/{llamadaId}` | Timeline de eventos |
| GET | `/auditoria-llamadas/export-csv` | Exportar CSV |

### Controlador
**`app/Http/Controllers/AuditoriaLlamadasController.php`**

**Métodos:**
- `index(Request)` — vista principal con tabla paginada
- `apiData(Request)` — JSON con filtros (fecha, conductor, estado, grupo)
- `batchDetail(Request, $groupId)` — detalle de un lote completo
- `getTranscript($conversationId)` — descarga/visualiza transcript de ElevenLabs
- `getAudio($conversationId)` — descarga MP3 de la conversación
- `queueStatus()` — estado actual de la cola (tiempo real)
- `callTimeline($llamadaId)` — timeline cronológico
- `searchConductor(Request)` — buscar llamadas por conductor
- `exportCsv(Request)` — exportar a CSV

### Vista
**`resources/views/analysis/auditoria-llamadas.blade.php`**

### Modelos
- `Llamada`
- `LlamadaConductor`
- `DriverCallResponse`
- `VehicleOwnerHolderDriver`

### Servicios usados
- **`CallQueueManager`** — para `queueStatus()`
- **`ElevenLabsCallService.getConversationDetails($conversationId)`** — para obtener detalle de ElevenLabs

## 6. Datos del timeline

Eventos típicos en el timeline de una llamada:

| Tiempo | Evento | Origen |
|--------|--------|--------|
| 00:00:00 | `call_initiated` | Sistema dispatch |
| 00:00:01 | `call_ringing` | ElevenLabs webhook |
| 00:00:08 | `call_answered` | ElevenLabs webhook |
| 00:00:09 | Agente saluda | Transcript |
| 00:00:15 | Conductor responde | Transcript |
| ... | Conversación | Transcript |
| 00:02:30 | `save_driver_decision` | Tool MCP |
| 00:02:35 | Agente despide | Transcript |
| 00:02:40 | `call_completed` | ElevenLabs webhook |

## 7. Diferencia con "Llamadas ElevenLabs" (módulo Analytics)

| Aspecto | Llamadas ElevenLabs (Analytics) | Auditoría Llamadas |
|---------|:-------------------------------:|:------------------:|
| Audiencia | Gerencia comercial | SAC / operaciones |
| Enfoque | Métricas, KPIs, tasas | Llamada individual, evidencia |
| Audio | ❌ | ✅ |
| Transcript | Resumido | Completo línea por línea |
| Timeline | ❌ | ✅ |
| Estado cola | ❌ | ✅ (tiempo real) |
| Export | Excel multi-hoja | CSV simple |

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ |
| JEFE COMERCIAL | ✅ |
| GERENTE DE CUENTA | ❌ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ✅ |
| PRICING | ❌ |

## 9. Almacenamiento de audios y transcripts
- **Audios:** ElevenLabs guarda automáticamente; el sistema los descarga a `storage/app/public/recordings/` cuando se solicita
- **Transcripts:** se guardan en `llamadas.transcript` (TEXT) cuando llega el webhook `call_completed`
- **Retención:** ElevenLabs guarda por X días (verificar plan)
- **Backup local:** comando `php artisan elevenlabs:cleanup-audios` limpia archivos > 24h por defecto

## 10. Comandos operativos
```bash
# Backfill datos faltantes
php artisan calls:backfill-data

# Ver detalles de audio storage
php artisan audio:storage-details

# Reset de cola (en emergencias)
php artisan queue:reset

# Limpiar llamadas atascadas
# (lo hace automáticamente CallQueueManager.cleanStuckCalls cada minuto vía cron)
```

## 11. Acción manual: colgar llamada
Si una llamada se queda colgada (en `processing` por más de 5 min):
- Endpoint: `POST /api/llamadas/{llamada}/hangup`
- Controlador: `CallHangupController@handle`
- Llama a ElevenLabs API para forzar cierre
- Libera el slot de la cola

## 12. Archivos clave
- `app/Http/Controllers/AuditoriaLlamadasController.php`
- `app/Http/Controllers/Api/CallHangupController.php`
- `app/Services/CallQueueManager.php`
- `app/Services/ElevenLabsCallService.php`
- `resources/views/analysis/auditoria-llamadas.blade.php`

## 13. Relacionado con
- [07-Analisis-Llamadas-ElevenLabs](../07-Analisis-Llamadas-ElevenLabs/) — versión analítica comercial
- [12-Gestion-Conductores](../12-Gestion-Conductores/) — gestión de conductores
- Carpeta `03-Sistema-Llamadas-IA/` para docs

## 14. Documentación adicional
Ver carpeta `03-Sistema-Llamadas-IA/`:
- `GUIA_MONITOREO_LLAMADAS.md`
- `SOLUCION_REGISTRAR_LLAMADAS_EJECUTA_REALES.md`
- `MEJORAS_FECHA_CARGUE_ELEVENLABS_V5.md`
