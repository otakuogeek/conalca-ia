# 📞 MÓDULO: ANÁLISIS › LLAMADAS ELEVENLABS

## 1. ¿Para qué es este módulo?
Es el **panel de analytics avanzado de las llamadas IA** realizadas con ElevenLabs Conversational Agent (Andrea/Natalia). Permite a la gerencia comercial y a SAC medir la **efectividad** del agente de voz: tasa de conexión, tasa de aceptación, duración promedio, conductores más exitosos, etc.

## 2. ¿Qué hace?
- Muestra **KPIs de llamadas:** total, conectadas, aceptadas, rechazadas, no respondidas, fallidas.
- Calcula **tasas:** % aceptación, % conexión, % rechazo.
- Lista **top conductores** por aceptación y volumen.
- Muestra **duración promedio** de llamadas por estado.
- Permite **ver transcripts** completos de cada conversación.
- Permite **exportar Excel** con 11 hojas de análisis profundo.
- Filtra por: rango de fechas, grupo de cotización, conductor, estado.

## 3. ¿Cómo funciona?

### Flujo de carga
```
1. Usuario entra a /analysis/calls
2. CallAnalyticsController@show renderiza vista
3. Vista hace fetch → /analysis/calls (datos por defecto últimos 30 días)
4. Backend consulta DriverCallResponse + Llamada con joins
5. Calcula agregaciones (% por estado, top conductores)
6. Renderiza tarjetas, gráficos y tabla
```

### Ver transcript
```
Click en una llamada → /analysis/calls/transcript/{llamadaId}
    ↓
CallAnalyticsController@getTranscript
    ↓
Lee transcript de ElevenLabs (almacenado en BD tras webhook)
    ↓
Renderiza línea por línea con timestamps (Andrea: ... / Conductor: ...)
```

### Exportar Excel
```
Click "Exportar" → /analysis/calls/export
    ↓
CallAnalyticsController@export
    ↓
CallAnalyticsExport genera libro con 11 hojas
    ↓
Descarga .xlsx
```

## 4. ¿Cómo actúa en el sistema?
Es **sólo lectura analítica** sobre los datos generados por el módulo de llamadas IA.

Lee de:
- `Llamada` (registro principal)
- `LlamadaConductor` (cada llamada a conductor)
- `DriverCallResponse` (respuesta y datos ElevenLabs)
- `VehicleOwnerHolderDriver` (datos del conductor)
- `GroupCotization` + `CotizacionModel` (contexto de la cotización)

## 5. Componentes del código

### Rutas
| Método | URL | Función |
|--------|-----|---------|
| GET | `/analysis/calls` | Panel principal |
| GET | `/analysis/calls/export` | Exportar Excel |
| GET | `/analysis/calls/transcript/{llamadaId}` | Ver transcript |
| GET | `/analysis/calls/group/{groupId}` | Llamadas de un grupo |

### Controlador
**`app/Http/Controllers/CallAnalyticsController.php`**

**Métodos:**
- `show()` — vista principal con KPIs y tabla
- `apiData()` — datos JSON para gráficos
- `export(Request)` — exportar a Excel
- `getTranscript(Request, $llamadaId)` — transcript individual
- `getGroupCalls($groupId)` — llamadas de un grupo específico

### Vista
**`resources/views/analysis/calls.blade.php`** — panel con cards de KPI, gráficos, tabla y filtros.

### Exports (Maatwebsite/Excel)

El libro principal **`CallAnalyticsExport`** combina 11 hojas especializadas:

| Hoja | Archivo | Contenido |
|------|---------|-----------|
| 1. Resumen | `CallAnalyticsResumenSheet` | KPIs ejecutivos: totales, % por estado |
| 2. Conversaciones | `CallAnalyticsConversationsSheet` | Detalle de cada conversación |
| 3. Diario | `CallAnalyticsDailySheet` | Agregado por día |
| 4. Conductores | `CallAnalyticsDriversSheet` | Stats por conductor |
| 5. Top Conductores | `CallAnalyticsTopDriversSheet` | Top 20 por aceptación |
| 6. Por Grupo | `CallAnalyticsGroupSheet` | Stats por grupo cotización |
| 7. Transcripts por Grupo | `CallAnalyticsGroupTranscriptsSheet` | Transcripts agrupados |
| 8. Datos Crudos | `CallAnalyticsRawDataSheet` | DriverCallResponse en bruto |
| 9. Llamadas Crudas | `CallAnalyticsRawLlamadasSheet` | Tabla `llamadas` en bruto |
| 10. Respuestas | `CallAnalyticsResponsesSheet` | Respuestas de conductor |
| 11. Top Drivers | (variantes adicionales) | Análisis cruzados |

**Ubicación:** `app/Exports/`

### Modelos consultados
- `Llamada` (status, duration, transcript)
- `DriverCallResponse` (call_status, response_status, elevenlabs_conversation_id, sip_call_id, is_selected)
- `LlamadaConductor` (datos del conductor en la llamada)
- `VehicleOwnerHolderDriver` (perfil del conductor)
- `GroupCotization`, `CotizacionModel`

## 6. KPIs medidos
- **Total de llamadas** por período
- **Tasa de conexión** = (llamadas conectadas / total) × 100
- **Tasa de aceptación** = (aceptadas / conectadas) × 100
- **Tasa de rechazo** = (rechazadas / conectadas) × 100
- **No respondidas** (`no_answer`, `busy`, `failed`)
- **Duración promedio**
- **Cotizaciones cerradas** vía llamada (con `is_selected=true`)
- **Conductor top** por # aceptaciones
- **Hora pico** del día con más llamadas conectadas

## 7. Reglas de negocio
- **Estados de llamada de ElevenLabs:**
  - `calling` — marcando
  - `ringing` — timbrando
  - `answered` / `in_progress` — conductor contestó
  - `completed` — llamada finalizada con éxito
  - `failed` — falló (técnico)
  - `busy` — ocupado
  - `no_answer` — no contestó
- **Estados de respuesta del conductor:**
  - `pending` — esperando decisión
  - `accepted` — aceptó la oferta
  - `rejected` — rechazó
- **`is_selected=true`:** el conductor fue elegido para realizar el viaje

## 8. Permisos
| Rol | Acceso |
|-----|:------:|
| SUPER ADMIN | ✅ |
| JEFE COMERCIAL | ✅ |
| GERENTE DE CUENTA | ✅ |
| ASISTENTE COMERCIAL | ❌ |
| SAC | ✅ |
| PRICING | ❌ |

## 9. Performance
Consultas pesadas si el período es largo. Recomendado:
- Limitar a 90 días por defecto
- Cache de KPIs principales en Redis
- Índices en `driver_call_responses.created_at`, `cotizacion_id`, `response_status`

## 10. Operación
### Comandos relacionados
```bash
# Ver llamadas recientes
php artisan calls:list-recent

# Estadísticas de ElevenLabs
php artisan elevenlabs:call-stats

# Sincronizar respuestas
php artisan calls:sync-driver-responses

# Limpiar audios viejos
php artisan elevenlabs:cleanup-audios
```

## 11. Archivos clave
- `app/Http/Controllers/CallAnalyticsController.php`
- `app/Exports/CallAnalyticsExport.php` y 10 hojas en `app/Exports/`
- `resources/views/analysis/calls.blade.php`
- `app/Models/Llamada.php`, `DriverCallResponse.php`, `LlamadaConductor.php`

## 12. Relacionado con
- [08-Analisis-Auditoria-Llamadas](../08-Analisis-Auditoria-Llamadas/) — vista operativa (audios, timeline)
- [12-Gestion-Conductores](../12-Gestion-Conductores/) — gestión de conductores
- Carpeta `03-Sistema-Llamadas-IA/` con docs de fixes

## 13. Documentación adicional
Ver `03-Sistema-Llamadas-IA/`:
- `SISTEMA_LLAMADAS_CONFIG.md`
- `RELACION_TABLAS_LLAMADAS.md`
- `GUIA_MONITOREO_LLAMADAS.md`
