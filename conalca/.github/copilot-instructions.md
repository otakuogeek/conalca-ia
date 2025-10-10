# Guía rápida para agentes Copilot en CONALCA AI

## Panorama del sistema
- Plataforma Laravel 10 con Livewire 3 para el panel administrativo y servicios API para asistentes de IA.
- **IMPORTANTE: Twilio ha sido REMOVIDO del sistema** - Las funcionalidades de llamadas están deshabilitadas.
- Las conversaciones de cotizaciones en el dashboard se manejan con el componente Livewire `app/Livewire/QuoteIndex.php` y el servicio `QuoteAssistantService` (OpenAI Assistants v2).

## Datos y modelos clave
- `ConversationSession` y `ConversationMessage` (tablas `conversation_sessions`/`conversation_messages`) conservan cada turno de llamada (LEGACY - sin Twilio).
- `DriverCallResponse` y `CallDriverDecision` registran la aceptación o rechazo; cualquier cambio debe acompañarse de `Log::info` para auditar IDs y `driver_id`.
- `CotizacionModel` almacena el trabajo ofrecido; sus campos (`ciudad_origen`, `tipo_mercancia`, `vehiculo_requerido`, etc.) alimentan el asistente de cotizaciones.
- `LocalAudioStorage` persiste MP3 temporales en `storage/app/public/audios/temp`; usa su API en lugar de escribir directamente al filesystem.

## Sistema de llamadas (DESHABILITADO)
- **Twilio removido**: No hay más integración telefónica en el sistema.
- `ConversationalAgentService` mantiene el guion formal (LEGACY).
- Decisiones de conductores se guardan en `DriverCallResponse` y `CallDriverDecision`; cualquier actualización debe disparar logs (`storage/logs/laravel.log`) para facilitar auditoría.

## Cotizaciones asistidas por IA
- El flujo del bot de cotizaciones (`QuoteIndex`) usa `QuoteAssistantService::createMessage`, `runAssistant` y `checkRunStatus` para leer tool calls. Los datos esperados incluyen claves como `ciudad_origen`, `ciudad_destino`, `peso_mercancia`, etc.
- Cuando `checkRunStatus` devuelve rutas, `QuoteIndex::syncChat` llena `$this->quote_data` y genera sugerencias de vehículo con `getVehicleSuggestion()`. Mantén ese array listo para `saveCotizacion()` que aplica códigos DANE y crea registros.
- Usa los logs detallados (`Log::info('Datos de cotización recibidos')`) al depurar. Si agregas nuevos campos en tool calls, sincroniza tanto el servicio como la vista `resources/views/livewire/quote-index.blade.php`.
- `QuoteAssistantService::checkRunStatus` solo acepta `status` = `requires_action` con `tool_calls`; evita reintentos infinitos validando `run['status']` antes de repetir el polling.

## Sistema de audio local
- `LocalAudioStorage` reemplaza por completo a S3: guarda en `storage/app/public/audios/{temp|calls}` y expone URLs públicas bajo `/storage/audios`. Ver configuraciones en `config/services.php` y `.env` (`LOCAL_AUDIO_*`).
- Mantén el comando `php artisan audio:cleanup` alineado con nuevos escenarios; está agendado en `app/Console/Kernel.php` (cada hora) y ofrece flags como `--max-age` (`ALMACENAMIENTO_LOCAL_AUDIO.md`).

## Dependencias externas y configuración
- Variables críticas en `.env`: `OPENAI_API_KEY`, `ELEVENLABS_API_KEY`, `ELEVENLABS_DEFAULT_VOICE_ID` (Andrea `qHkrJuifPpn95wK3rm2A`), `TWILIO_*`, y `LOCAL_AUDIO_STORAGE=true`.
- `config/elevenlabs.php` fuerza la voz Andrea sin fallback. Cualquier nueva integración debe respetar este mandato.
- OpenAI se usa por partida doble: Assistants v2 (`QuoteAssistantService`) y Chat completions (`ConversationalAgentService::getVehicleSuggestion`). Ajusta modelos cuidando límites de tokens y formato JSON esperado.

## Debug y observabilidad
- Usa el canal principal `storage/logs/laravel.log` con filtros como `grep -E "(Call SID|Tool calls|LocalAudioStorage|ConversationalAgent)"` para seguir cada turno.
- Revisa `storage/app/public/audios/temp` cuando dudes de la generación de audio; el comando `php artisan audio:cleanup --max-age=0` limpia residuos al instante.
- Las rutas sanitarias (`php artisan system:verify`, `php artisan test:call-flow`, `php artisan test:twilio-webhooks`) confirman Twilio + ElevenLabs + almacenamiento; ejecútalas después de cualquier cambio en servicios o configuración.

## Comandos operativos y pruebas
- Setup local: `composer install`, `npm install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`, `npm run dev`/`npm run build`.
- Sanidad de servicios (ver `SYSTEM_ANALYSIS_FINAL.md`):
  - `php artisan system:verify`
  - `php artisan test:call-flow`
  - `php artisan test:twilio-webhooks`
  - `php artisan audio:cleanup`
- Logs en vivo: `tail -f storage/logs/laravel.log | grep -E "(Call SID|Tool calls|LocalAudioStorage)"`.

## Convenciones y buenas prácticas del repo
- Toda respuesta telefónica debe estar en español colombiano y seguir el guion formal documentado en `ConversationalAgentService`.
- Antes de guardar nuevas grabaciones o transcripciones, actualiza el estado en `TwilioCall` y dispara eventos de seguimiento desde `TwilioController` para mantener dashboards consistentes.
- Al tocar Livewire, mantén polling específico (`wire:poll.3s="syncChat"`) y estados como `$this->loading_prompt` para evitar renders innecesarios.
- Añade rutas API siempre en `routes/api.php` (con prefix según subsistema) y documenta endpoints Curl en el README o archivos *_CONFIGURACION.md correspondientes.

## Referencias imprescindibles
- `SYSTEM_ANALYSIS_FINAL.md` – estado de la migración y lista de comandos críticos.
- `AGENTE_IA_FLUJO_COMPLETO.md` – flujo detallado de cotizaciones y expected tool calls.
- `AI_INTERACTION_FIXES.md` – ajustes recientes en Livewire/QuoteAssistant.
- `ALMACENAMIENTO_LOCAL_AUDIO.md` y `ANDREA_VOICE_SYSTEM.md` – lineamientos obligatorios para audio.

Revisa estas notas antes de implementar cambios grandes y documenta cualquier flujo adicional directamente en esta guía para mantener a futuros agentes productivos desde el minuto uno.