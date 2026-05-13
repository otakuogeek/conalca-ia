# INFORME TÉCNICO DE ENTREGA – PROYECTO CONALCA

**Fecha del informe:** 2026-05-13
**Versión:** 1.0 (entrega final / handover)
**Dominio de producción:** https://conalcaia.conalca.com.co
**Servidor:** AWS EC2 (Ubuntu) — `13.56.4.123`

> Este documento es el handover técnico y funcional COMPLETO del proyecto. Cubre la arquitectura, módulos, modelos, controladores, servicios, integraciones externas, infraestructura, MCP y flujos de negocio.

---

## ÍNDICE

1. Resumen ejecutivo del negocio
2. Stack tecnológico
3. Arquitectura general
4. Modelos y base de datos (Eloquent / MySQL)
5. Capa HTTP — Rutas, Controladores, Middleware
6. Capa de Servicios (Services)
7. Jobs (Colas asíncronas)
8. Componentes Livewire (UI dinámico)
9. Comandos Artisan (Console)
10. Frontend (Blade + React + Vite)
11. Integraciones externas (APIs)
12. Servidor MCP (Model Context Protocol)
13. Roles y permisos
14. Configuración y variables de entorno
15. Flujos de negocio detallados
16. Mejoras, correcciones y mantenimiento histórico
17. Operación: despliegue, colas, monitoreo
18. Checklist de entrega y puntos de atención

---

## 1. RESUMEN EJECUTIVO DEL NEGOCIO

**CONALCA** es una empresa colombiana de transporte y logística. El sistema "CONALCA IA" es una plataforma comercial-operativa que integra:

- **Cotizaciones de transporte** asistidas por Inteligencia Artificial (chat con OpenAI/Groq).
- **Búsqueda automática de conductores** disponibles a través de la API externa **Arcángel**.
- **Llamadas telefónicas automáticas** a los conductores mediante un agente de voz IA (**ElevenLabs Conversational AI** con la voz "Andrea/Natalia").
- **Creación de Solicitudes de Transporte (ST)** sincronizadas con el sistema externo **Silogtran** (sistema operativo de transporte).
- **CRM comercial**: clientes, contactos, vendedores, metas, calendario y emails.
- **Panel administrativo** con roles (SUPER ADMIN, JEFE COMERCIAL, GERENTE DE CUENTA, ASISTENTE COMERCIAL, SAC, PRICING, API_PRICING).

### Flujo macro:

```
CLIENTE  →  ASISTENTE COMERCIAL hace cotización (chat IA)
        →  Sistema sugiere PRICING por ruta/vehículo
        →  Cliente recibe grupo de cotizaciones (link público)
        →  Cliente ACEPTA → se busca conductor en ARCÁNGEL
        →  IA llama por teléfono a los conductores
        →  Conductor ACEPTA → se crea SOLICITUD DE TRANSPORTE
        →  ST se envía a SILOGTRAN → genera orden real
```

---

## 2. STACK TECNOLÓGICO

| Capa | Tecnología | Versión |
|------|------------|---------|
| Backend | **Laravel** | ^10.10 |
| Lenguaje | **PHP** | ^8.1 |
| Base de datos | **MySQL** (MariaDB compatible) | — |
| ORM | Eloquent | 10.x |
| Frontend dinámico | **Livewire** | ^3.4 |
| Frontend SPA | **React** | ^18.3.1 |
| Router React | react-router-dom | ^7.6.2 |
| Bundler / Asset pipeline | **Vite** | ^4.5.14 |
| CSS | **Tailwind CSS** + DaisyUI | 3.3.3 / 3.6.5 |
| Auth API | **Laravel Sanctum** | ^3.3 |
| Permisos | **Spatie Laravel Permission** | ^6.23 |
| Excel | maatwebsite/excel | ^3.1 |
| HTTP Client | Guzzle | ^7.9 |
| WebSockets | beyondcode/laravel-websockets | ^1.14 |
| Cache / Queue | **Redis** (predis) | ^2.3 |
| SDK IA | openai-php/client + groq custom | 0.10.x |
| Mapas | Leaflet + leaflet-routing-machine | 1.9.4 |
| Gráficas | Chart.js + chartjs-chart-geo | 4.5 |
| Reconocimiento voz | react-speech-recognition | 4.0 |
| QA | Playwright + PHPUnit | 10.1 |

### Servicios externos:
- **OpenAI** (gpt-4o-mini, Threads/Assistants v2 + Responses API)
- **Groq** (qwen/qwen3-32b) — alternativo
- **ElevenLabs** (TTS + Conversational AI agent)
- **Zadarma** (SIP Trunk para llamadas)
- **Twilio** (proveedor alternativo de llamadas y SMS)
- **Arcángel API** (https://arcangel.conalca.com.co/api) — conductores y vehículos
- **Silogtran** (sistema operativo de transporte)
- **BulkGate** (OTP / SMS)
- **Pusher** (broadcasting)

---

## 3. ARQUITECTURA GENERAL

```
┌──────────────────────────────────────────────────────────┐
│                  NAVEGADOR DEL USUARIO                    │
│  Blade Views │ Livewire │ React SPA (Vite)               │
└──────────────────────────┬───────────────────────────────┘
                           │ HTTPS
┌──────────────────────────▼───────────────────────────────┐
│                  LARAVEL 10 (PHP 8.1)                     │
│  Routes (web/api) → Middleware → Controllers              │
│  Services → Jobs (queue) → Models (Eloquent)              │
│  Livewire components │ Notifications │ Mail │ Exports     │
└─────┬─────────────────┬───────────────┬──────────────────┘
      │                 │               │
      ▼                 ▼               ▼
┌───────────┐    ┌─────────────┐  ┌──────────────────┐
│  MySQL    │    │  Redis      │  │ SERVIDOR MCP     │
│ ai_transp │    │ Cache/Queue │  │ Python FastAPI   │
└───────────┘    └─────────────┘  │ + aiomysql       │
                                  └────────┬─────────┘
                                           │ JSON-RPC 2.0
                                           ▼
                                  ┌──────────────────┐
                                  │   ELEVENLABS     │
                                  │ ConvAI Agent +   │
                                  │ SIP (Zadarma/Tw) │
                                  └──────────────────┘
       APIs externas: Arcángel, Silogtran, OpenAI, Groq, BulkGate
```

### Puntos clave:
- **Modo Producción / Desarrollo en paralelo**: archivo `public/hot` activa Vite HMR sin afectar producción.
- **2 base de datos lógicas** (configuradas en `config/database.php`): la principal y una de tipos de remesa.
- **Cola por Redis** (`QUEUE_CONNECTION=redis`), con worker systemd `conalca-queue-worker.service`.
- **Cache de tokens** (Arcángel, OpenAI threads) en Redis.
- **WebSockets** vía Pusher para eventos en tiempo real (estado de llamadas, conductores aceptados).

---

## 4. MODELOS Y BASE DE DATOS

La base de datos contiene **108 migraciones** acumuladas (carpeta `database/migrations/`) y **60+ modelos Eloquent**.

### 4.1 Resumen de modelos por dominio

#### 🔐 Usuarios, roles y seguridad
| Modelo | Tabla | Función |
|--------|-------|---------|
| `User` | users | Usuarios del sistema, con creator() para jerarquía |
| `UserColumn` | user_columns | Columnas personalizables por usuario en grids |
| `DataColumn` | data_columns | Columnas globales del sistema |
| `Role` (Spatie) | roles | Roles asignables |
| `SecuritySchemaProduct` | security_schema_products | Catálogo de medidas de seguridad por producto |
| `SecuritySchemaMeasure` | security_schema_measures | Medidas de seguridad (candado satelital, jen-set, etc.) |
| `SecuritySchemaPriceRange` | security_schema_price_ranges | Rangos de precio para reglas de seguridad |
| `SecuritySchemaClientAssignment` | security_schema_client_assignments | Asignaciones por cliente |
| `SecuritySchemaUserMeasure` | security_schema_user_measures | Medidas por usuario |
| `SecuritySchemaUserPriceRange` | security_schema_user_price_ranges | Rangos por usuario |

#### 💼 Clientes y comercial
| Modelo | Tabla | Función |
|--------|-------|---------|
| `Client` | clients | Empresas/clientes con NIT, ciudad, vendedor asignado |
| `Contact` | contacts | Contactos de clientes |
| `ContactFile` | contact_files | Archivos asociados a contactos |
| `ClientFile` | client_files | Documentos del cliente |
| `ClientUserAssignment` | client_user_assignments | Asignación cliente ↔ ejecutivo comercial |
| `Seller` | sellers | Vendedores |
| `Goal` | goals | Metas mensuales por ejecutivo |

#### 💰 Cotizaciones y pricing
| Modelo | Tabla | Función |
|--------|-------|---------|
| `GroupCotization` | group_cotizations | Grupo de cotizaciones (puede tener múltiples rutas) |
| `CotizacionModel` | cotizacion_models | Cotización individual (1 ruta) |
| `CotizationNote` | cotization_notes | Notas en cotización |
| `Pricing` | pricings | Precio base por ruta/vehículo |
| `PercentageSetting` | percentage_settings | Porcentajes de comisión |
| `TaraSetting` | tara_settings | Tara de contenedores (20'=2300kg, 40'=3400kg) |
| `TipoValorRemesa` | tipo_valor_remesa | Tipos de remesa |
| `Proveedor` | proveedores | Proveedores |
| `Product` | products | Catálogo de productos transportables |
| `Packing` | packings | Tipos de empaque (paletizado, sacos, granel, etc.) |
| `City` | cities | Ciudades colombianas (con códigos DANE) |

#### 🚛 Conductores y vehículos
| Modelo | Tabla | Función |
|--------|-------|---------|
| `VehicleOwnerHolderDriver` | vehicle_owner_holder_driver | Conductores+vehículos (sync con Arcángel) |
| `VehicleClass` | vehicle_class | Clases de vehículo (CAMION, TURBO, TRACTOMULA, etc.) |
| `Bodywork` | bodyworks | Tipos de carrocería |
| `BlockedDriver` | blocked_drivers | Conductores bloqueados |
| `ChoferFiltrado` | choferes_filtrados | Cache de conductores filtrados por cotización |
| `Transport` | (legacy) | Transportes legacy |

#### 📞 Llamadas e IA conversacional
| Modelo | Tabla | Función |
|--------|-------|---------|
| `Llamada` | llamadas | Registro principal de llamada IA |
| `LlamadaConductor` | llamadas_conductores | Llamada a conductor específico (FK a driver_call_response) |
| `DriverCallResponse` | driver_call_responses | Respuesta del conductor (datos ElevenLabs) |
| `CallDriverDecision` | call_driver_decisions | Decisión (acepta/rechaza) |
| `Call` | calls | Tabla legacy de llamadas |
| `CallDriver` | call_drivers | Tabla legacy join |
| `Conversation` | conversations | Conversaciones (chat genérico) |
| `ConversationSession` | conversation_sessions | Sesión de chat IA (un cliente, un thread OpenAI) |
| `ConversationMessage` | conversation_messages | Mensajes del chat (user/assistant/tool/system) |
| `Message` | messages | Mensajes genéricos |

#### 📋 Solicitudes de Transporte (formulario multi-paso)
| Modelo | Tabla | Función |
|--------|-------|---------|
| `SolicitudTransporte` | solicitud_transportes | Solicitud cabecera (paso 1: encabezado) |
| `SolicitudTransporteDetalle` | solicitud_transporte_detalles | Detalle de carga |
| `SolicitudTransporteCargue` | solicitud_transporte_cargues | Datos de cargue (lugares, fechas) |
| `SolicitudTransporteContenedor` | solicitud_transporte_contenedors | Datos del contenedor |
| `SolicitudTransporteInternacional` | solicitud_transporte_internacionals | Datos para operaciones internacionales |
| `SolicitudTransporteEquipos` | solicitud_transporte_equipos | Equipos requeridos |
| `SolicitudTransporteCondicionesFactura` | solicitud_transporte_condiciones_facturas | Condiciones de facturación |
| `SolicitudTransporteAcompanamiento` | solicitud_transporte_acompanamientos | Acompañamiento (escolta) |
| `SolicitudTransporteEntrega` | solicitud_transporte_entregas | Datos de entrega |
| `SolicitudTransporteCosto` | solicitud_transporte_costos | Costos asociados |
| `Solicitation` | solicitations | Solicitudes genéricas (paso intermedio) |

#### 📅 Tareas y eventos
| Modelo | Tabla | Función |
|--------|-------|---------|
| `Appointment` | appointments | Eventos del calendario, tareas |
| `CalendarEvent` | calendar_events | Eventos calendario |
| `Pending` | pendings | Tareas pendientes |
| `TransitEvent` | transit_events | Eventos del tránsito |
| `Request` | requests | Solicitudes generales |

#### 📧 Comunicaciones
| Modelo | Tabla | Función |
|--------|-------|---------|
| `Email` | emails | Emails enviados/recibidos |
| `Document` | documents | Documentos subidos |

### 4.2 Relaciones críticas

**`CotizacionModel`** es el nodo central:
- `belongsTo`: Client, Pricing, GroupCotization, selectedDriver (VehicleOwnerHolderDriver), Product
- `hasMany`: llamadas, llamadasConductores, driverCallResponses, decisions, notes
- `hasOne`: SolicitudTransporte
- `belongsToMany`: VehicleOwnerHolderDriver (via call_driver_decisions)

**`GroupCotization`**:
- `hasMany` cotizaciones (CotizacionModel) — un grupo puede tener N rutas
- `belongsTo`: user, client
- Métodos de negocio: `tieneAceptada()`, `todasRespondidas()`
- Guarda parámetros automáticos: operation_type, candado_satelital, jen_set, combustible, kit_derrames, pictogramas, cargo_type
- Tiene chat fields: thread_id, current_run, decisión cliente

**`LlamadaConductor`** ↔ **`DriverCallResponse`**: relación bidireccional para acceder a datos ElevenLabs (conversation_id, sip_call_id) desde el registro de conductor.

### 4.3 Migraciones destacadas (orden cronológico)
- 2014–2024: tablas base (users, clients, contacts, pricings, group_cotizations, cotizacion_models, products, cities, solicitud_transportes y sus 9 tablas hijas).
- 2025-09 a 2025-11: tablas de **llamadas IA** (calls, call_drivers, llamadas, llamadas_conductores, driver_call_responses, conversation_sessions, conversation_messages) + campos ElevenLabs.
- 2025-12 a 2026-04: ajustes finos (cola de llamadas, multi-ruta, normalización, taras, índices de performance, sistema de **security schema**).

### 4.4 Seeders
- `DatabaseSeeder.php` — orquestador
- `DashboardDataSeeder.php` — datos demo para el dashboard
- `DevContPricingSeeder.php` — pricings de desarrollo (contenedores)
- `PercentageSettingsSeeder.php` — porcentajes iniciales
- `ProveedorSeeder.php` — proveedores
- `TipoValorRemesaSeeder.php` — tipos de remesa

---

## 5. CAPA HTTP — RUTAS, CONTROLADORES, MIDDLEWARE

### 5.1 Archivos de rutas
- `routes/web.php` — rutas web (sesión, Livewire, Blade)
- `routes/api.php` — API REST con Sanctum
- `routes/console.php` — definiciones de comandos
- `routes/channels.php` — canales broadcast (Pusher)
- `routes/emergency.php` — endpoints de emergencia (debug)
- `routes/test-arcangel.php` — endpoints de pruebas Arcángel
- `routes/test.php` — endpoints de pruebas

### 5.2 Grupos de rutas (resumen)

#### 🔐 Autenticación
| Método | URL | Controlador |
|--------|-----|-------------|
| GET | `/login` | Auth\LoginController@index |
| POST | `/login` | Auth\LoginController@store |
| POST | `/simple-login` | closure JSON |
| GET/POST | `/logout` | Auth\LoginController@logout |
| GET | `/password-request` | Auth\PasswordRequestController@index |
| POST | `/password-request` | Auth\PasswordRequestController@store |

#### 💰 Cotizaciones
- `GET /quotes` — Vista Livewire legacy
- `GET /cotizacion`, `/cotizacion-react`, `/quotes-react` — Vistas React
- `GET /api/groups-quotes` — GroupQuotationController@groupsQuotes
- `PATCH /api/groups-quotes/{id}/status` — cambiar estado
- `GET/POST /api/groups-quotes/accept|cancel/{id}` — acción del cliente
- `DELETE /api/groups-quotes/{id}`
- `GET /api/grupo-cotizacion/{id}` — datos públicos para responder
- `GET /cotizacion/grupo/{id}/responder` — vista pública para cliente
- `POST /cotizacion/grupo/{id}/responder` — guardar respuesta
- `POST /api/chat/quote` — ChatController@quoteChat
- `POST /api/chat/functions` — ChatController@chatWithFunctions
- `POST /api/quote/create-group` — QuoteCreationController@createQuoteGroup
- `POST /api/chat/clear-thread` — limpiar thread OpenAI
- `POST /api/quote/save-routes` — QuoteRoutesController@saveQuoteRoutes
- `GET /api/quote/routes/{groupId}` — obtener rutas guardadas

#### 📞 Llamadas IA
- `POST /elevenlabs-webhook` — ElevenLabsWebhookController (middleware `elevenlabs.webhook.auth`)
- `GET /elevenlabs-queue-status` — estado de la cola
- `POST /api/call-drivers` — ConversationalAgentController@initiateConversationalCall
- `POST /api/call-drivers-group` — llamadas por grupo
- `POST /api/call-drivers-group-async` — AsyncCallController@initiateAsyncGroupCalls
- `GET /api/call-drivers-group-status` — estado de grupo
- `GET /api/llamadas` — listar por cotización
- `POST /api/llamadas/update-status`
- `POST /api/llamadas/{llamada}/hangup` — colgar llamada
- `POST /api/start-elevenlabs-calls/{cotizacionId}` — iniciar
- `POST /elevenlabs/call` — llamada directa
- `GET /elevenlabs/stats` — estadísticas

##### Tools del agente de voz (webhooks que ElevenLabs invoca durante la llamada):
- `POST /api/elevenlabs-tools/get-contexto-inicial-conductor`
- `POST /api/elevenlabs-tools/get-conductor-by-telefono`
- `POST /api/elevenlabs-tools/get-cotizaciones`
- `POST /api/elevenlabs-tools/precioviaje`
- `POST /api/elevenlabs-tools/save-driver-decision`

#### 📋 Solicitudes de Transporte
- `POST /api/solicitud-transporte/guardar-parcial` — guardado paso a paso (10 pasos)
- `GET /api/solicitud/{cotizacion}/data`, `prefill`
- `GET /api/solicitud/group/{group_id}/prefill`
- `POST /solicitud/store` — guardar completa
- `POST /silog/crear-st` — enviar a Silogtran

#### 🚛 Conductores y vehículos
- `/conductores`, `/api/conductores/*` (CRUD)
- `/vehiculos`, `/vehiculos/sincronizar`, `/vehiculos/relaciones`, etc.

#### 🌐 Arcángel
- `GET /api/arcangel/health`
- `GET /api/arcangel/ciudades`
- `GET /api/arcangel/vehiculos/cercanos|filtrar|clases`
- `POST /api/arcangel/buscar-conductores`
- `POST /api/arcangel/buscar-conductores-filtros`
- `POST /api/arcangel/llamar-conductor`
- `POST /api/arcangel/clear-cache`

#### 👥 Usuarios
- `/users` (CRUD), `/users/columns` (CRUD personalizado), `/users/me`, `/users/assignable-roles`, `/users/potential-parents`

#### 💵 Pricing
- `/pricing` (vista)
- `/api/pricings-solutions` (CRUD bulk)
- `/api/pricing-suggestions`, `/api/pricing-rentability-stats`, `/api/pricing/vehicle-guide`
- `/percentage-settings`, `/tara-settings` (admin only)

#### 📊 Análisis y auditoría
- `/analysis`, `/analysis/data`, `/analysis/calls`, `/analysis/calls/export`, `/analysis/calls/transcript/{id}`
- `/auditoria-llamadas`, `/auditoria-llamadas/api`, `/batch/{id}`, `/transcript/{convId}`, `/audio/{convId}`, `/timeline/{id}`, `/export-csv`

#### 📇 Catálogos
- `/cities`, `/packings`, `/products`, `/mcp/search-products`
- `/catalog/clientes|ciudades|vendedores|productos|empaques`
- `/clients/search-react`, `/clients/by-document`, `POST /clients`

#### 🎯 Metas
- `/goals`, `/my-goal`, `/boss/notifications`

#### 🛡️ Utilitarios
- `/dashboard`, `/dashboard/chart-data`
- `/server/metrics`, `/fingerprint`, `/debug-system-info`
- `/emergency/stop-run/{threadId}`

### 5.3 Controladores principales (78 archivos totales)

Carpeta `app/Http/Controllers/` (raíz):
- AccountController, AnalysisController, AuditoriaLlamadasController
- CalendarController, CallAnalyticsController, CallController
- ConductorController, ContactController, ConversationalAgentController
- DashboardController, DataColumnController, DocumentController
- EmailController, HomeController, PercentageSettingController
- PricingExportController, SecuritySchemaController, SolicitudTransporteController
- SystemMetricsController, TaraSettingController, TransportController
- VehiculoController, VoiceAgentController

Carpeta `app/Http/Controllers/Api/` (38 controladores):
- ArcangelController, ArcangelDriversController, AsyncCallController, AuthController
- CallHangupController, CallStatusController, CatalogController, ChatController
- CityController, ClientController, ClientSearchController, CompanySearchController
- CotizacionClienteController, CotizationNoteController, DataExtractionController
- **ElevenLabsAgentToolsController** (tools que el agente IA invoca durante la llamada)
- ElevenLabsController, **ElevenLabsWebhookController** (recibe eventos de fin de llamada)
- GoalController, GroupQuotationController, GroupRecoveryController
- MessageController, OrphanMessageController, PackingController, PendingController
- PricingApiController, PricingController, ProductController
- QuoteCreationController, QuoteEmailController, QuoteRoutesController
- QuoteSaveController, QuoteSessionController, SacCotizationController
- SilogController, SolicitationController, TestSeedController
- UserColumnController, UserController

Carpeta `app/Http/Controllers/Auth/`:
- LoginController, PasswordRequestController, ConfirmPasswordController
- ForgotPasswordController, RegisterController, ResetPasswordController
- VerificationController

### 5.4 Middleware

#### `Authenticate.php`
Sobrecargado para devolver 401 JSON cuando la ruta es `/api/*` o se espera JSON. Si es web, redirige a `/login`.

#### `EnsureUserHasRole.php`
Valida que el usuario tenga al menos uno de los roles separados por `|`.
**Uso:** `Route::middleware('role:SUPER ADMIN|JEFE COMERCIAL')`

#### `ElevenLabsWebhookAuth.php`
Validación robusta de webhooks de ElevenLabs:
1. Content-Type debe ser `application/json`
2. Método HTTP POST
3. Firma HMAC-SHA256 con `ELEVENLABS_WEBHOOK_SECRET` (usa `hash_equals` para evitar timing attacks)
4. JSON válido con `event_type` presente
5. Loguea todo rechazo con IP y detalles

#### `ValidateElevenLabsConfig.php`
Verifica que `ELEVENLABS_API_KEY` esté configurada antes de procesar.

#### `DatabaseConnectionMiddleware.php`
Verifica conexión a la BD. Si falla:
- En `/login` → redirige con `database_error=true`
- En API → 503 JSON
- En web → vista `errors.database` 503

#### `NormalizeProductCodes.php`
Limpia y estandariza códigos de producto antes de guardar.

#### `MockUserForLocal.php`
En entorno `local`, auto-autentica un usuario fake para evitar login manual.

#### `ContentSecurityPolicy.php`
Headers CSP de seguridad.

#### `ApiRateLimitMiddleware.php`
Rate limiting de APIs.

#### Estándar Laravel
- `TrimStrings`, `VerifyCsrfToken`, `EncryptCookies`, `ConvertEmptyStringsToNull`, `PreventRequestsDuringMaintenance`, `TrustProxies`.

---

## 6. CAPA DE SERVICIOS (`app/Services/`)

22 servicios, agrupados por responsabilidad:

### 6.1 Asistentes IA

#### `QuoteAssistantService.php`
Servicio histórico usando **OpenAI Assistants API v2 (Threads + Runs)**.
- `getThread(Client $client)` — obtiene/crea thread por cliente, lo persiste en `clients.openai_thread_id`.
- `getMessages($threadId)` — historial completo.
- `sendMessage($threadId, $content)` — añade mensaje al thread.
- `runAssistant($threadId)` — lanza un run.
- `waitForRunAndExtractData($threadId, $runId)` — polling hasta completarse y devuelve datos extraídos.
- `processMessages($messages)` — alternativa directa sin threads (function calling).
- `extractCotizacionData($texto)` — parsea la respuesta para extraer estructura.
- `clearStuckRuns($threadId)` — cancela runs activos antes de eliminar un thread.
- `executeMCPTool($tool, $args)` — invoca herramientas en el servidor MCP.

#### `MCPAssistantService.php`
**Servicio principal actual.** Usa OpenAI ChatGPT Completions API + tool calling, comunicándose con el servidor MCP en `https://conalcaia.conalca.com.co/mcp/`.
- Tool calling para: `create_cotizacion`, `search_products`, `get_empaques`, `precioviaje`, etc.
- Manejo inteligente de **tara de contenedores**: detecta menciones (20'=2300kg, 40'=3400kg).
- `findFirstRouteWithoutValidatedProduct()` — para flujos multi-ruta, encuentra la siguiente ruta a validar.
- `generateFinalSummary()` — genera resumen cuando todas las rutas están completas.
- Gestión de sesiones via `ConversationSession` y `ConversationMessage`.

#### `DataExtractionService.php`
Extracción inteligente de datos de cotización desde mensajes libres usando **OpenAI gpt-4o-mini**.
- `extractDataFromMessage($userMessage, $currentData, $selectedRouteIndex)` — método principal.
- Prompts especiales para modo **EDICIÓN** (solo extrae campos mencionados, no inventa).
- Normalización: tipos de vehículo (`camión sencillo` → `SENCILLO`), pesos, ciudades.
- Validación de **ciudades colombianas reales** — descarta direcciones, aeropuertos.
- Extracción de ciudad desde direcciones complejas: `"Lutransa Bodega 11 – Autopista Medellín Km. 2.5 – Cota"` → `"COTA"`.
- `generateContextualResponse()` — respuesta al usuario con campos faltantes.

#### `OpenAIService.php`
Wrapper genérico de OpenAI:
- `generateResponse($message, $context)`
- `analyzeIntent($text)` — detecta intención del usuario.
- `processConversation($userInput, $conversationContext)` — conversación telefónica con historial.
- `summarizeConversation($messages)` — resumen para contexto.

#### `GroqClient.php`
Cliente SDK custom para **Groq API** (modelo `qwen/qwen3-32b`) con tool calling. Alternativa a OpenAI.
- `createChatCompletion($messages, $tools, $options)`
- `extractAssistantMessage()`, `hasToolCalls()`, `extractToolCalls()`
- Reintentos automáticos, logging, configuración de timeout.

#### `TextPreprocessorService.php`
Preprocesa texto del usuario antes de extraer datos:
- Separa palabras pegadas (`bogotaa` → `bogota`).
- Corrige errores ortográficos comunes y de speech-to-text.
- Expande abreviaciones.
- Normaliza acentos.

### 6.2 Llamadas IA y voz

#### `ElevenLabsService.php`
TTS de ElevenLabs:
- `generateSpeech($text, $voiceId, $modelId)` — genera audio y guarda archivo, retorna URL pública.
- `generatePhoneOptimizedSpeech($text, $voiceId, $context)` — config optimizada para telefonía.
- `generateAudioContent()` — bytes binarios.
- `getAvailableVoices()`, `voiceExists()`, `validateApiKey()`.
- Voz por defecto: **Andrea** (ID `qHkrJuifPpn95wK3rm2A`), oficial CONALCA.

#### `ElevenLabsCallService.php`
Llamadas SIP a través de ElevenLabs Conversational AI:
- `startCallsForCotization($cotizacionId)` — itera todas las llamadas pendientes.
- `makeDirectSipCall($toNumber, $clientData, $provider)` — llamada saliente.
- **Soporta dos proveedores SIP**: `zadarma` (2 líneas concurrentes) y `twilio` (10 líneas).
- `getConversationDetails($conversationId)` — obtiene detalles post-llamada de ElevenLabs.
- `testSipTrunkConnection()` — health check.
- `formatPhoneForColombia()` — añade +57 si no tiene prefijo.

#### `ConversationalAgentService.php`
Lógica conversacional para el agente de voz (legacy Twilio + nuevo ElevenLabs):
- `generateInitialMessage(CotizacionModel $cotizacion, $driverName)` — primer saludo personalizado.
- `processDriverResponse($cotizacionId, $driverId, $userInput, $history)` — procesa respuesta natural del conductor.
- `quickResponseAnalysis()` — análisis rápido de respuestas comunes (sí/no/cuanto).
- `cacheInitialAssets()` / `getCachedInitialAssets()` — precarga datos de cotización en cache para acelerar la llamada.
- `saveDriverDecision()` — persiste decisión en BD.

#### `CallQueueManager.php` (servicio crítico)
**Gestor central de la cola de llamadas concurrentes.**
- Máximo según proveedor: 2 (Zadarma) / 10 (Twilio).
- Lock global Redis para evitar despacho doble.
- `getActiveCallsCount()` — cuenta llamadas REALES activas (queue_status='processing').
- `getAvailableSlots()` — slots libres.
- `dispatchNextCalls($cotizacionId)` — despacha llamadas pendientes.
- `onCallCompleted($llamadaId)` — libera slot y dispara la siguiente.
- `onCallFailed($llamadaId)` — manejo de error.
- `cleanStuckCalls()` — limpia llamadas atascadas (timeout > X minutos).
- `parseTranscript($turns)` — convierte transcript ElevenLabs a texto legible.

#### `CallQueueService.php` (versión más antigua/alternativa)
- Cola con prioridad y reintentos.
- `enqueueCall($llamada, $priority)`, `processNextBatch()`, `completeCall()`, `requeueCall()`.
- Lotes de 2-3 llamadas con delay de 60-90s.

#### `CotizacionCallWindowService.php`
Valida si la cotización está dentro de su ventana de tiempo para llamar.
- `getCallRestriction(CotizacionModel $cotizacion)` — retorna razón de bloqueo si aplica.
- `parseLoadingDateTime($value)` — parser flexible de `fecha_hora_descargue_cargue`.

#### `LocalAudioStorage.php`
Almacenamiento local de archivos de audio:
- `saveAudio($audioData, $filename, $subdir)`, `getPublicUrl($path)`, `deleteAudio()`.
- `cleanupTempAudios($maxAge)` — limpia archivos > 1 hora.
- `getStorageStats()` — espacio usado.

#### `VoiceCacheService.php`
Cache de audios pre-generados con Redis para no regenerar TTS repetidos.
- `getCachedAudio()`, `cacheAudio()`, `cleanupOldCache()`, `getCacheStatistics()`, `flush()`.

#### `StreamingAudioService.php`
Streaming inteligente:
- Selección de audio basada en contexto.
- Precarga predictiva (preload de audios probables).
- Sistema de fallback en 4 niveles para garantizar respuesta.
- Métricas de rendimiento.

#### `MockTtsService.php`
Mock para desarrollo/testing cuando ElevenLabs no está disponible.

### 6.3 Integraciones externas

#### `ArcangelService.php` (591 líneas)
Cliente HTTP para la API de Arcángel:
- Métodos genéricos: `get()`, `post()`, `put()`, `delete()` con caché opcional.
- Autenticación con token (1 hora, auto-renovado a los 55 min).
- **Dual mode**: producción / desarrollo con URLs y API Keys diferentes.
- Reintentos automáticos (3 intentos, 100ms delay).
- Endpoints específicos: `getCiudades()`, `getVehiculosCercanos($ciudad)`, `filtrarVehiculos()`, `getClasesDisponibles()`, `buscarConductores()`.
- Logging detallado y manejo de excepciones.
- `clearCache($endpoint)`, `clearAllCache()`.

#### `SilogtranService.php`
Cliente para el sistema externo Silogtran:
- `token()` — autenticación.
- `call($api, $payload, $method)` — método genérico.
- `consultarCliente($documento, $calificacion)` — busca cliente por NIT.
- `crearSolicitudTransporte($payload)` — envía la ST a Silogtran.

#### `SilogtranTransform.php`
Transformador de datos `SolicitudTransporte` (modelo local) → payload Silogtran.
- Mapeo de centros de costo (TRANSLIDHER → CONALCA BOGOTA).
- Normalización de valores (acompañamiento mínimo 1, no 0).
- Helper `SilogtranHelper::normalizeCostCenter()`.

#### `ClientService.php`
Resolución/creación de clientes:
- `resolveOrCreateClient($nit, $name, $additionalData)` — busca por NIT, retorna prompt si no existe.
- `createClient($data)` — crea cliente nuevo.

### 6.4 Performance

#### `PerformanceService.php`
Cálculo de métricas comerciales:
- `calculateAcceptedAmount(User $commercial, int $year, int $month)` — monto aceptado por comercial en un mes.

---

## 7. JOBS (`app/Jobs/`) — Procesamiento asíncrono

| Job | Descripción |
|-----|-------------|
| `CallDriverJob` | Llamada individual a conductor con prompt personalizado. Timeout 600s. |
| `CallDriversJob` | Llamada en lote sin prompt (usa configuración por defecto). |
| `ProcessElevenLabsCall` | **Job principal actual.** Procesa una llamada ElevenLabs. ShouldBeUnique. Tries=1, timeout=120s, uniqueFor=300s. |
| `ProcessBatchElevenLabsCalls` | Procesa un **lote** de llamadas con concurrencia controlada. Tries=3, timeout=300s, backoff=[10,30]. Cancela si ya hay 7 confirmados. |

**Cola:** Redis (`QUEUE_CONNECTION=redis`).
**Worker systemd:** `conalca-queue-worker.service` (archivo en raíz del proyecto).

---

## 8. COMPONENTES LIVEWIRE (`app/Livewire/`)

13 componentes interactivos para UI dinámica:

| Componente | Función |
|------------|---------|
| `QuoteIndex` | Vista principal de cotizaciones (legacy con chat OpenAI integrado). Maneja modal de creación, chat, pricing, pasos. |
| `QuoteIndexSimple` | Versión simplificada para testing. |
| `CreateQuote` | Wizard de creación de cotización paso a paso. |
| `DrogZone` | Modal de Solicitud de Transporte (6 pasos), con búsqueda async de cliente/ciudad/vendedor. |
| `PricingIndex` | Grid de pricings con filtros (origen, destino, tipo, vehículo, IVA, contenedor). |
| `ModalPricing` | Modal CRUD individual de pricing. |
| `ClientsIndex` | Lista de clientes con asignación a usuarios comerciales. |
| `CallsManager` | Gestión en tiempo real de llamadas de un grupo: estadísticas, actualización de estado, registro de llamadas. |
| `StreamingMonitor` | Monitor en tiempo real del sistema de streaming de audio (refresh interval 3s). |
| `CalendarIndex` | Vista calendario. |
| `EmailIndex` | Bandeja de entrada con filtros y reenvío. |
| `EmailStore` | Modal para componer email. |
| `UserSearch` | Buscador de usuarios para reenvío de emails. |

---

## 9. COMANDOS ARTISAN (`app/Console/Commands/`)

54 comandos personalizados. Agrupados:

### Sistema y monitoreo
- `SystemMonitor`, `SystemStatusReport`, `SystemCleanup`
- `VerifySystemConfiguration`, `DiagnoseDatabase`
- `MonitorCallQueue` — monitorea y despacha llamadas pendientes (ejecutar como cron).
- `ProcessCallQueue`, `ProcessCallQueueCommand`, `ResetQueueCommand`
- `EnqueuePendingCallsCommand`

### ElevenLabs
- `TestElevenLabs`, `TestElevenLabsAudio`, `TestElevenLabsCall`, `TestElevenLabsConfig`, `TestElevenLabsConnection`, `TestElevenLabsSimple`
- `DebugElevenLabsConfig`, `ElevenLabsCallStats`
- `CheckSpecificVoice`, `ListElevenLabsVoices`, `TestAndreaVoice`
- `CleanupElevenLabsAudios`, `OptimizeVoiceSystem`
- `GenerateAudioTest`, `GenerateStaticAudios`, `TestOptimizedAudio`
- `AudioStorageDetails`, `CleanupAudioFiles`

### Arcángel
- `ArcangelModeSwitch` — cambia entre modo producción y desarrollo
- `BuscarConductoresParaCotizacion`
- `ConsultarLocalidadArcangel`, `ListarCiudadesArcangel`

### Llamadas
- `BackfillCallData` — completa datos faltantes de llamadas históricas
- `ListRecentCalls`, `ListarLlamadasPendientes`
- `RegistrarLlamadaConductor`
- `SyncDriverCallResponses` — sincroniza respuestas con tabla de conductores
- `TestCallFlow`, `TestCallSystemCommand`, `TestCompleteFlow`
- `TestConversationalAgent`, `TestInitialCallEndpoint`, `TestVoiceAgent`

### Datos y mantenimiento
- `ImportProducts`, `ImportProductsFromCsv`
- `SyncClientStatus`
- `FixClientThreads` — repara threads OpenAI corruptos
- `ClearChatSession`
- `EvaluateMonthlyGoals` — evalúa cumplimiento de metas
- `RemoveHotFile` — para volver de modo dev a producción
- `DebugApiKeys` — verifica API keys
- `TestOpenAI`, `TestEmail`, `TestPlaywrightMCP`
- `DiagnoseTwilioRoutes`
- `ZinformacionCommand` — herramienta del agente IA

---

## 10. FRONTEND (Vistas Blade + React + Vite)

### 10.1 Estructura de vistas Blade (`resources/views/`)
**99 archivos Blade** organizados en módulos:

| Módulo | Vistas principales |
|--------|--------------------|
| `auth/` | login, passwordRequest, app |
| `layout/` | app.blade.php (layout maestro) |
| `account/` | show.blade.php |
| `dashboardChart/` | gráficos del dashboard |
| `cotizations/` | index (Livewire QuoteIndex) |
| `quotes/` | vista React de cotizaciones (componentes) |
| `contacts/` | índice, detalles, modal de creación, asignación |
| `pricing/` | show.blade.php |
| `vehiculos/` | index (sincronización Arcángel) |
| `conductores/` | index + modals |
| `requests/` | index (solicitudes de transporte) |
| `llamadas/` | vistas de gestión |
| `analysis/` | analytics, auditoria-llamadas, show |
| `interactiveCallsAI/` | panel de llamadas IA |
| `calendar/` | calendario |
| `goals/` | metas |
| `users/` | panel |
| `mailbox/` | bandeja de emails |
| `email/` | template email |
| `documents/` | documentos |
| `security-schema/` | esquema de seguridad |
| `tara-settings/` | tara contenedores |
| `percentage-settings/` | porcentajes |
| `livewire/` | partials de componentes Livewire |
| `components/` | componentes blade reusables (input, select, submit, etc.) |
| `errors/` | database.blade.php |

### 10.2 React SPA (`resources/js/`)

**Carpetas principales:**
- `pages/` — páginas raíz (driver-details.jsx)
- `components/` — componentes por módulo:
  - `Calls/` — UI de llamadas
  - `CotizacionInicial/`, `Cotizations/`, `Quotes/` — formularios de cotización
  - `Goals/` — gestión de metas
  - `SecuritySchema/` — esquemas de seguridad
  - `Solicitation/`, `SolicitudWizard/` — wizard de solicitud de transporte
  - `UserControlPanel/` — panel de usuario
  - `ui/` — componentes UI reusables
- `services/` — clientes HTTP por dominio:
  - `ArcangelService.js`, `callService.js`, `channelService.js`
  - `citiesService.js`, `columnsService.js`, `cotizationsService.js`
  - `goals.js`, `packingService.js`, `pricingService.js`, `pricings.js`
  - `productService.js`, `roles.js`, `securitySchemaService.js`
  - `solicitations.js`, `usePendings.js`, `userService.js`
- `hooks/` — `useCurrentUser.js`, `useInterval.js`
- `api/` — clientes axios base
- `utils/` — utilidades
- `CotizacionInicial-demo/` — demo standalone

**Entry points:**
- `app.jsx` — bootstrap React principal
- `quotes-react.jsx` — entry de cotizaciones React
- `app.js`, `bootstrap.js` — Laravel JS
- `dashboard.js`, `dashboard-chart.js`, `analysis.js` — vistas legacy con jQuery
- `sidebar-server-metrics.js` — métricas del servidor

### 10.3 Build (`vite.config.js`)
- Vite 4.5 con plugin React
- HMR vía `public/hot` (sólo en modo desarrollo)
- Build para producción → `public/build/`

### 10.4 Estilos
- **Tailwind CSS 3.3** + **DaisyUI 3.6**
- `tailwind-scrollbar-hide` para scrollbars limpios
- FontAwesome 6.7 (íconos)
- Sass para componentes legacy

### 10.5 Otros assets clave
- `chart.js` + `chartjs-chart-geo` + `datamaps` (gráficos)
- `leaflet` + `leaflet-routing-machine` (mapas y rutas)
- `flatpickr` (datepickers)
- `select2` (selects avanzados)
- `dropzone` (uploads)
- `sortablejs` + `react-beautiful-dnd` (drag & drop)
- `react-hot-toast` (notificaciones)
- `react-speech-recognition` (transcripción local)
- `dayjs` (fechas)
- `react-data-table-component` (tablas)

---

## 11. INTEGRACIONES EXTERNAS (APIs)

### 11.1 OpenAI
- **API Key:** `OPENAI_API_KEY` (config: `config/openai.php` + `config/services.php`)
- **Modelo:** `gpt-4o-mini` (configurable con `OPENAI_MODEL`)
- **Organización:** `OPENAI_ORGANIZATION`
- **Timeout:** 30s
- **Endpoints usados:**
  - `POST /v1/threads` — crear thread
  - `POST /v1/threads/{id}/messages` — añadir mensaje
  - `POST /v1/threads/{id}/runs` — ejecutar asistente
  - `GET /v1/threads/{id}/runs/{run_id}` — polling de status
  - `POST /v1/chat/completions` — completions directas (MCPAssistantService)
  - Responses API (migración reciente — ver `MIGRACION_OPENAI_RESPONSES_API.md`)
- **Usos:** extracción de datos de cotización, chat con cliente, generación de respuestas, function/tool calling al servidor MCP.

### 11.2 Groq (alternativa)
- **API Key:** `GROQ_API_KEY`
- **Modelo:** `qwen/qwen3-32b`
- **Wrapper:** `GroqClient.php`
- **Uso:** alternativa más rápida/barata para tool calling.

### 11.3 ElevenLabs
- **API Key:** `ELEVENLABS_API_KEY`
- **Base URL:** `https://api.elevenlabs.io/v1`
- **Configuración (`config/elevenlabs.php`):**
  - Voz oficial: **Andrea** (`qHkrJuifPpn95wK3rm2A`) — obligatoria, sin fallback.
  - Modelo: `eleven_multilingual_v2`
  - Output: `mp3_44100_192`
  - Settings de voz español: stability 0.90, similarity_boost 0.80, style 0.10
  - 5 voces secundarias disponibles: George, Adam, Bella, Antoni, Arnold
- **Conversational Agent:**
  - `ELEVENLABS_AGENT_ID` (Andrea - CONALCA)
  - `ELEVENLABS_AGENT_PHONE_NUMBER_ID`
  - Max duración llamada: 300s (5 min)
  - Language: `es`, country code: `CO`
- **Webhook:** `https://conalcaia.conalca.com.co/api/elevenlabs-webhook`
  - Eventos: call_initiated, call_ringing, call_answered, call_completed, call_failed, call_busy, call_no_answer
  - Validación HMAC-SHA256 con `ELEVENLABS_WEBHOOK_SECRET`
- **Proveedor SIP:** configurable con `ELEVENLABS_CALL_PROVIDER`:
  - `zadarma` (default): 2 líneas concurrentes — número `+576017564145`
  - `twilio`: hasta 10 líneas — número `+573105672307`
- **Mensajes predefinidos:** welcome, listening, repeat, error, goodbye.
- **Cache TTL:** 12 horas de audios pre-generados.

### 11.4 Arcángel API
- **Base URL prod:** `https://arcangel.conalca.com.co/api/`
- **Base URL dev:** `https://dev.arcangel.conalca.com.co/api/`
- **API Keys:** `ARCANGEL_API_KEY` / `ARCANGEL_API_KEY_DEV`
- **Modo:** `ARCANGEL_MODE` (`production` o `development`) — conmutable en runtime.
- **Endpoints clave:**
  - `POST GenerateToken/` — autenticación (token 1h, cache 55min)
  - `GET getCiudadesNombres/` — listado de ciudades
  - `GET getVehiculosCercanos?ciudad=X` — vehículos cercanos
  - `GET filtrarVehiculos` — búsqueda con filtros
  - `GET getClasesDisponibles` — clases de vehículo
  - `POST buscarConductores` — conductores por cotización
- **Usos:** búsqueda inteligente de conductores disponibles por ciudad, tipo de vehículo y capacidad de carga; score de disponibilidad.

### 11.5 Silogtran
- **Base URL:** `SILOG_BASE` (prod) / `SILOG_BASE_TEST` (test)
- **Credenciales:** `SILOG_USER`, `SILOG_PASS`
- **Endpoints usados:**
  - `token` — autenticación
  - `consultarCliente` — verificar cliente por NIT
  - `crearSolicitudTransporte` — crear ST en Silogtran (ej: ST 0312599238)
- **Documentación:** `Manual de uso API conexión ARCANGEL - CONALCA IA v1.0.pdf` y `INTEGRACION_SILOGTRAN_EXITOSA.md`

### 11.6 Twilio (legacy + alternativo SIP)
- `TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_PHONE_NUMBER`
- Webhook: `{APP_URL}/api/twilio/webhook/status`
- Inicialmente fue el agente legacy (handleInitialCall, handleDriverResponse, waitForResponse, secondChance).

### 11.7 BulkGate (SMS / OTP)
- `BULKGATE_APP_ID`, `BULKGATE_APP_TOKEN`
- Sender ID: `gSystem`
- OTP TTL: 10 minutos

### 11.8 Pusher (broadcasting)
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`
- Para eventos en tiempo real (estado de llamadas, conductores aceptados).

### 11.9 SMTP (Mail)
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

### 11.10 Redis
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- Usado para: cache (`CACHE_DRIVER`), queue (`QUEUE_CONNECTION`), session (`SESSION_DRIVER`), broadcasting.

### 11.11 SFTP / SSH
- `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD`, `SFTP_PORT`
- `SSH_HOST`, `SSH_PASSWORD` — para deploys.

---

## 12. SERVIDOR MCP (Model Context Protocol)

Ubicación: `/home/ubuntu/conalca/conalca-mcp/mcp-server/`

### 12.1 Tecnología
- **Python 3.9+**
- **FastAPI** + **uvicorn** (asíncrono con `uvloop`)
- **MCP SDK** (`mcp.server`)
- **aiomysql** (pool de conexiones MySQL asíncrono)
- **SSE Starlette** (Server-Sent Events para streaming)
- **WebSockets** para streaming bidireccional

### 12.2 Propósito
Servidor que expone la base de datos `ai_transport` como **herramientas MCP** (Model Context Protocol). Es el "puente" entre el **agente de voz ElevenLabs** y la **base de datos CONALCA**, permitiendo al agente:
- Consultar cotizaciones disponibles para un conductor.
- Buscar conductores por placa/teléfono/identificador.
- Calcular precio de viaje en tiempo real.
- Guardar la decisión del conductor durante la llamada.
- Crear/actualizar llamadas, etc.

### 12.3 Estructura
```
conalca-mcp/mcp-server/
├── conalca_mcp_server/
│   ├── __init__.py
│   ├── server.py                   # FastAPI app principal
│   ├── server_mcp_compliant.py    # Versión MCP-compliant JSON-RPC 2.0
│   ├── server_streaming.py        # Versión con streaming SSE/WS
│   ├── server_backup.py
│   ├── tools.py                    # 23 herramientas MCP
│   ├── models.py                   # Repositorio de acceso a BD
│   └── database.py                 # Conexión asíncrona MySQL
├── run_mcp_server.py
├── run_service.py
├── start_server_http_v2.sh
├── start_streaming_server.sh
├── mcp_config.json
├── nginx_mcp_config.conf
├── conalca-mcp.service             # Systemd service
├── manage_mcp_service.sh
├── requirements.txt
├── pyproject.toml
└── venv/                            # Python virtualenv
```

### 12.4 Herramientas MCP expuestas (23)
| Tool | Descripción |
|------|-------------|
| `get_llamadas` | Lista llamadas con paginación |
| `get_llamada_by_id` | Obtiene llamada por ID |
| `get_llamadas_by_telefono` | Llamadas filtradas por número de teléfono |
| `create_llamada` | Crea registro de llamada |
| `update_llamada_status` | Actualiza estado + notas |
| `process_elevenlabs_event` | Procesa evento webhook de ElevenLabs |
| `activate_conversation` | Activa una conversación |
| `get_cotizaciones` | Cotizaciones disponibles |
| `search_cotizaciones_by_ruta` | Buscar por ruta origen-destino |
| `get_cotizacion_with_group_info` | Con info del grupo |
| `get_cotizacion_with_pricing_info` | Con info del pricing |
| `get_cotizations_by_group` | Cotizaciones de un grupo |
| `get_group_cotizations` | Listar grupos |
| `get_group_cotizations_by_user` | Grupos por usuario |
| `search_pricings_by_route` | Pricing por ruta |
| `get_pricings` | Lista pricings |
| `get_pricing_by_vehicle_type` | Pricing por tipo de vehículo |
| `get_vehicle_by_telefono_conductor` | Vehículo por teléfono del conductor |
| `get_chofer_by_placa` | Conductor por placa |
| `precioviaje` | **Calcula el precio del viaje en vivo** durante la llamada |
| `generate_transport_offer` | Genera oferta de transporte |
| `save_driver_decision` | **Guarda decisión del conductor** (acepta/rechaza/contraoferta) |
| `llenar_formulario` | Llena formulario completo de cotización |
| `zinformacion` | Información ampliada del sistema (catch-all) |

### 12.5 Configuración del servidor
- Variables: `MCP_BASE_URL`, `MCP_WEBSOCKET_URL`, `MCP_TOOLS_ENABLED`, `MCP_SERVER_PORT` (18840), `MCP_SERVER_HOST`.
- BD: `ai_transport` (DB_HOST=127.0.0.1, DB_USERNAME=biosanar_user).
- Servicio systemd: `conalca-mcp.service` (auto-start).
- Nginx config: `nginx_mcp_config.conf` (proxy reverse).
- Expuesto en: `https://conalcaia.conalca.com.co/mcp/`.

### 12.6 Integración con ElevenLabs
El **agente conversacional Andrea/Natalia** está configurado en el dashboard de ElevenLabs con las URLs de las herramientas MCP como webhooks. Durante la llamada, el LLM del agente invoca dinámicamente estas tools para consultar datos en tiempo real y registrar la decisión.

Prompt principal de la agente: `/home/ubuntu/conalca/conalca/PROMPT_NATALIA_V2.md` (24KB) e `INSTRUCCIONES_ASISTENTE_ARCANGEL.md`.

---

## 13. ROLES Y PERMISOS

Implementado con **Spatie Laravel Permission**.

| Rol | Permisos clave |
|-----|---------------|
| **SUPER ADMIN** | Acceso total: usuarios, configuraciones, conductores, todos los grupos |
| **JEFE COMERCIAL** | Configuraciones de % y tara, ve todos los grupos, evalúa metas |
| **GERENTE DE CUENTA** | Ve todos los grupos comerciales, asigna clientes |
| **ASISTENTE COMERCIAL** | Ve sus propios grupos, crea cotizaciones |
| **SAC** (Servicio al Cliente) | Gestiona conductores, tara contenedores |
| **PRICING** | Gestiona pricings y precios |
| **API_PRICING** | Acceso API para sistemas externos a pricing |

**Filtrado por rol:** Por ejemplo, `GroupQuotationController@groupsQuotes` filtra:
```php
// SUPER ADMIN, GERENTE DE CUENTA → ven todos los grupos
// Otros roles → sólo grupos donde user_id = auth()->id()
```

---

## 14. CONFIGURACIÓN Y VARIABLES DE ENTORNO

Variables en `.env` (clasificadas):

### App
- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `LOG_CHANNEL`, `LOG_LEVEL`, `LOG_DEPRECATIONS_CHANNEL`

### Base de datos
- `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### Cache/Queue/Session
- `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`
- `BROADCAST_DRIVER`
- `MEMCACHED_HOST`
- `FILESYSTEM_DISK`

### Redis
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`

### OpenAI / Groq / IA
- `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_REASONING_EFFORT`
- `GROQ_API_KEY`, `GROQ_MODEL`
- `CHAT_PROVIDER`, `ASSISTANT_SERVICE`

### ElevenLabs
- `ELEVENLABS_API_KEY`, `ELEVENLABS_DEFAULT_VOICE_ID`, `ELEVENLABS_MODEL_ID`
- `ELEVENLABS_OUTPUT_FORMAT`, `ELEVENLABS_OPTIMIZE_STREAMING_LATENCY`
- `ELEVENLABS_AGENT_ID`, `ELEVENLABS_AGENT_NAME`
- `ELEVENLABS_AGENT_PHONE_NUMBER`, `ELEVENLABS_AGENT_PHONE_NUMBER_ID`
- `ELEVENLABS_TWILIO_PHONE_NUMBER`, `ELEVENLABS_TWILIO_PHONE_NUMBER_ID`
- `ELEVENLABS_CALL_PROVIDER` (`zadarma` | `twilio`)
- `ELEVENLABS_WEBHOOK_URL`, `ELEVENLABS_WEBHOOK_ENABLED`, `ELEVENLABS_WEBHOOK_SECRET`
- `ELEVENLABS_STREAM_ENABLED`

### Arcángel
- `ARCANGEL_MODE`, `ARCANGEL_BASE_URL`, `ARCANGEL_BASE_URL_DEV`
- `ARCANGEL_API_KEY`, `ARCANGEL_API_KEY_DEV`
- `ARCANGEL_TIMEOUT`, `ARCANGEL_RETRY_TIMES`, `ARCANGEL_RETRY_DELAY`

### Silogtran
- `SILOG_BASE`, `SILOG_BASE_TEST`, `SILOG_USER`, `SILOG_PASS`

### BulkGate
- `BULKGATE_APP_ID`, `BULKGATE_APP_TOKEN`, `BULKGATE_SENDER_ID`, `BULKGATE_OTP_TTL`

### Pusher
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`
- `PUSHER_HOST`, `PUSHER_PORT`, `PUSHER_SCHEME`

### MCP
- `MCP_BASE_URL`, `MCP_WEBSOCKET_URL`, `MCP_TOOLS_ENABLED`, `MCP_SERVER_PORT`, `MCP_SERVER_HOST`

### Mail
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`, `MAIL_SCHEME`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

### Audio local
- `LOCAL_AUDIO_STORAGE`, `LOCAL_AUDIO_PATH`, `LOCAL_RECORDINGS_PATH`
- `LOCAL_AUDIO_CLEANUP_HOURS`, `LOCAL_FILES_BASE_URL`
- `AUDIO_TEMP_PATH`, `SOX_BINARY_PATH`

### SFTP/SSH (deploys)
- `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD`, `SFTP_PORT`
- `SSH_HOST`, `SSH_PASSWORD`

### Asterisk (legacy de telefonía)
- `AGI_DEBUG`, `AGI_TIMEOUT`, `ASTERISK_SOUNDS_PATH`

### Sanctum
- `SANCTUM_STATEFUL_DOMAINS`

### Twilio
- `TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_PHONE_NUMBER`, `TWILIO_WEBHOOK_URL`

---

## 15. FLUJOS DE NEGOCIO DETALLADOS

### 15.1 Flujo de creación de cotización (con IA)
```
1. Asistente comercial entra a /cotizacion-react
2. Busca cliente por NIT en CreateQuoteModal (search async)
3. Selecciona cliente → ClientData se vincula globalmente
4. Frontend → POST /api/quote/create-group
   ↓ Guarda en sesión: operation_type, candado_satelital, jen_set,
     combustible, kit_derrames, pictogramas, cargo_type
   ↓ Devuelve thread_id (NO crea grupo todavía)
5. Se abre ChatModal con la voz/contexto del cliente
6. Usuario escribe texto natural ("necesito flete Bogotá-Medellín, 30T, paletizado")
7. Frontend → POST /api/chat/quote o /api/chat/functions
8. ChatController → MCPAssistantService → OpenAI ChatGPT
9. OpenAI invoca tool: search_pricings_by_route → BD local
10. OpenAI invoca tool: search_products → BD local
11. IA pregunta datos faltantes hasta completar:
    - origen, destino, peso, producto, empaque, vehículo, tara (si contenedor)
12. Una vez completos los datos, abre PricingModal con propuesta
13. Usuario ajusta % y guarda → Livewire QuoteIndex@storeST
14. Se crea 1 GroupCotization con TODOS los datos (operation_type, etc.)
15. Se crean N CotizacionModel (una por ruta)
16. Se limpia sesión
17. (Opcional) Se envía email al cliente con link público:
    /cotizacion/grupo/{id}/responder
```

### 15.2 Flujo del cliente respondiendo
```
1. Cliente abre /cotizacion/grupo/{id}/responder (sin auth, link público)
2. CotizacionClienteController@responderVista carga datos del grupo
3. Cliente ve las opciones y elige por ruta: aceptar / rechazar / pendiente
4. POST /cotizacion/grupo/{id}/responder
5. CotizacionClienteController@guardarRespuestas guarda decision_cliente
6. Si todas aceptadas → grupo pasa a "aceptada"
   Si alguna aceptada → estado "parcial"
   Si todas rechazadas → "rechazada"
7. Sistema notifica al comercial vía email + dashboard
```

### 15.3 Flujo de búsqueda de conductores + llamadas IA
```
1. Comercial entra al grupo aceptado, hace click "Llamar conductores"
2. POST /api/arcangel/buscar-conductores con cotizacion_id
3. ArcangelDriversController:
   - Valida fecha_hora_descargue_cargue (ventana de tiempo)
   - Normaliza ciudad de origen
   - Consulta peso de carga
   - Llama a Arcángel: GET buscarConductores
4. Arcángel responde con N conductores ordenados por score
5. Frontend muestra modal de preview con los conductores
6. Comercial confirma → POST /api/call-drivers-group-async
7. AsyncCallController crea registros en `llamadas` (status=pendiente)
   y `llamadas_conductores` con score, datos de conductor
8. Dispatcher (CallQueueManager.dispatchNextCalls):
   - Verifica slots libres (2 Zadarma / 10 Twilio)
   - Despacha hasta llenar los slots → Job ProcessElevenLabsCall
9. ProcessElevenLabsCall:
   - Pre-carga datos en cache (ConversationalAgentService.cacheInitialAssets)
   - Llama ElevenLabsCallService.makeDirectSipCall(número, clientData)
   - ElevenLabs marca el SIP, suena en el celular del conductor
10. Durante la llamada, el agente Andrea/Natalia:
    - Saluda al conductor por nombre
    - Le ofrece la cotización (origen, destino, carga)
    - Invoca tools del MCP: get_cotizaciones, precioviaje
    - Negocia: si conductor pide más, IA puede contraoferta limitada
    - Conductor acepta → tool: save_driver_decision
11. ElevenLabs envía webhook a /elevenlabs-webhook al colgar
12. ElevenLabsWebhookController:
    - Valida HMAC
    - Actualiza Llamada y DriverCallResponse con conversation_id, sip_call_id,
      transcript, audio URL, duración
    - Llama CallQueueManager.onCallCompleted → libera slot
    - Despacha siguiente llamada
13. Si ya hay 7 conductores confirmados → ProcessBatchElevenLabsCalls
    cancela el resto de pendientes (configurable)
14. Comercial ve en /auditoria-llamadas los resultados, transcripts y audios
```

### 15.4 Flujo de Solicitud de Transporte (paso a paso)
```
1. Cotización aceptada → comercial entra a /solicitud
2. Sistema prefill datos desde la cotización (SolicitudTransporteController.prefill)
3. Wizard de 10 pasos en React:
   - step_1: Encabezado (tipo viaje, moneda, operación)
   - step_2: Detalle de carga (peso, valor, producto)
   - step_3: Contenedor / embalaje (si aplica)
   - step_4: Internacional (DTA, INCOTERMS, etc.)
   - step_5: Equipos requeridos
   - step_6: Acompañamiento (escolta, GPS, etc.)
   - step_7: Condiciones de factura
   - step_8: Entrega
   - step_9: Cargues / descargues múltiples
   - step_10: Costos
4. En cada paso: POST /api/solicitud-transporte/guardar-parcial
   → Guarda en la tabla correspondiente, marca steps_completed[step]=true
5. Al finalizar, comercial pulsa "Crear ST"
6. POST /silog/crear-st → SilogController@crearST
7. SilogtranTransform convierte modelo local → payload Silogtran
   (normaliza centros de costo, valores, fechas)
8. SilogtranService.crearSolicitudTransporte($payload)
9. Silogtran responde con número de ST (ej: ST 0312599238)
10. Sistema guarda número de ST en SolicitudTransporte
11. Comercial puede consultar/imprimir la ST desde el sistema
```

### 15.5 Reglas de negocio importantes
- **Tara de contenedores:** 20 pies = 2300 kg, 40 pies = 3400 kg (TaraSetting)
- **Llamadas concurrentes:** máximo 2 (Zadarma) / 10 (Twilio)
- **Delay entre lotes:** 60 segundos
- **Stop condition:** 7 confirmados → cancela el resto
- **Ventana de llamadas:** no permite llamar si `fecha_hora_descargue_cargue` ya pasó (CotizacionCallWindowService)
- **Normalización de teléfono:** si no tiene `+`, agrega `+57` (Colombia)
- **Normalización de peso:** `12.400` (formato miles ES) → 12400; `7,5` (decimal coma) → 8
- **Validación de ciudad:** solo acepta ciudades colombianas reales (lista cerrada en DataExtractionService)
- **Productos personalizados:** si el producto no está en BD, IA pregunta y se guarda como "personalizado"
- **Ediciones:** prompt especial para no "inventar" datos no mencionados explícitamente

---

## 16. MEJORAS, CORRECCIONES Y MANTENIMIENTO HISTÓRICO

Existen **más de 120 archivos .md** documentando fixes y mejoras. Agrupados por tema:

### Extracción IA (cotizaciones)
- `MEJORAS_EXTRACCION_IA.md`, `MEJORAS_EXTRACCION_IA_v2.md`
- `SOLUCION_COMPLETA_EXTRACCION_IA.md`
- `FIX_AUTO_EXTRACTION_FLOW.md`
- `FIX_EXTRACTED_DATA_DISPLAY.md`
- `DEBUG_EXTRACTION_DATA.md`
- `MIGRACION_OPENAI_RESPONSES_API.md`
- `GROQ_SDK_IMPLEMENTATION.md`

### Multi-ruta y rutas
- `FIX_MULTIPLE_ROUTES_SEPARATOR.md`
- `FIX_ROUTE_DUPLICATION_ON_EDIT.md`
- `FIX_PERDIDA_RUTAS_AL_EDITAR.md`
- `FIX_NORMALIZACION_CAMPOS_RUTAS.md`
- `VALIDACION_MULTI_RUTA_COMPLETA.md`

### Tara y contenedores
- `FIX_BUG_TARA_DATAEXTRACTION.md`, `FIX_BUG_TARA_EDICION.md`
- `FIX_PESO_TARA_VALIDACION.md`
- `FIX_CONTENEDOR_DOBLE_R.md`

### Llamadas y ElevenLabs
- `ELEVENLABS_INTEGRATION.md`
- `SISTEMA_LLAMADAS_CONFIG.md`
- `SOLUCION_REGISTRAR_LLAMADAS_EJECUTA_REALES.md`
- `CORRECCION_ALMACENAMIENTO_LLAMADAS_CONDUCTORES.md`
- `MODAL_CONDUCTORES_TIEMPO_REAL.md`
- `RELACION_TABLAS_LLAMADAS.md`
- `MEJORAS_FECHA_CARGUE_ELEVENLABS_V5.md`
- `RESUMEN_CAMBIO_PROCESSEVENLASBSCALL.md`

### Conductores
- `CORRECCION_AUTENTICACION_BUSCAR_CONDUCTORES.md`
- `CORRECCION_MAPEO_VEHICULOS_ARCANGEL.md`
- `FIX_CONDUCTORES_ACEPTADOS_NO_MOSTRABAN.md`
- `GUIA_PRUEBAS_CONDUCTORES.md`

### Grupos de cotizaciones
- `CORRECCION_DUPLICACION_GROUP_COTIZATIONS.md`
- `FLUJO_COMPLETO_GRUPOS.md`
- `ANALISIS_GRUPO_527.md`, `REPORTE_ANALISIS_GRUPOS_465_505.md`
- `SOLUCION_GRUPO_350.md`

### Integraciones
- `INTEGRACION_ARCANGEL_COMPLETADA.md`
- `INTEGRACION_SILOGTRAN_EXITOSA.md`
- `ANALISIS_BACKEND_ARCANGEL.md`
- `ANALISIS_FLUJO_ORDEN_FILTRADO_CONDUCTORES.md`
- `ENDPOINT_FILTRADO_VEHICULOS.md`

### MCP
- `MIGRACION_MCP_COMPLETADA.md`
- `ANALISIS_SERVIDOR_MCP.md`
- `FIX_MCP_RETURN_FORMAT.md`
- En conalca-mcp/: `LISTADO_COMPLETO_HERRAMIENTAS_MCP.md`, `ANALISIS_TECNICO_20_HERRAMIENTAS_MCP.md`

### Fecha de cargue
- `RESUMEN_FINAL_INTEGRACION_FECHA.md`
- `VERIFICACION_INTEGRACION_FECHA_CARGUE_COMPLETO.md`
- `GUIA_FECHA_CARGUE_API.md`
- En conalca-mcp/: `IMPLEMENTACION_VALIDACION_FECHA_CODIGO.md`, `VALIDACION_FECHA_CARGUE_MCP.md`

### Pricing
- `DOCUMENTACION_BULK_UPDATE_PRICINGS.md`
- `FILTRO_PESO_VEHICULOS.md`

### Mensajes y persistencia
- `FIXES_FINALES_MENSAJES.md`, `FIXES_SINCRONIZACION_Y_VALIDACIONES.md`
- `FIX_MAYUSCULAS_Y_PERSISTENCIA.md`
- `FIX_CONVERSATION_SESSIONS_NULLABLE.md`

### Otros
- `LIMPIEZA_CHAT_NUEVA_COTIZACION.md`, `LIMPIEZA_FORMULARIO_COTIZACIONES.md`
- `HOTFIX_ERROR_409_CRITICO.md`
- `PREVENCION_REGRESION.md`
- `OPTIMIZACION_MOBILE_COTIZACIONES.md`, `OPTIMIZACION_CHAT_AUTOLLENADO.md`

**Recomendación:** Conservar todos los `.md` en el repositorio porque documentan decisiones de diseño y bugs históricos.

---

## 17. OPERACIÓN: DESPLIEGUE, COLAS, MONITOREO

### 17.1 Modo Desarrollo vs Producción
**`/home/ubuntu/conalca/conalca/public/hot`** controla el modo:
- Si existe → Vite HMR activo (URL: `http://localhost:5173`)
- Si NO existe → Producción usa `/build/assets/`

**Script de arranque desarrollo:** `/home/ubuntu/conalca/start-dev.sh` o `dev.sh` en el proyecto.

**Para volver a producción:**
1. Detener Vite (Ctrl+C)
2. `rm -f /home/ubuntu/conalca/conalca/public/hot`
3. Si hubo cambios: `npm run build`

### 17.2 Servicios systemd
- **`conalca-queue-worker.service`** — worker de cola Laravel (en raíz del proyecto)
- **`conalca-mcp.service`** — servidor MCP Python (en `conalca-mcp/mcp-server/`)

### 17.3 Scripts de mantenimiento (en `conalca/`)
- `start-dev.sh`, `dev.sh` — arrancar dev server
- `monitor_llamadas_real_time.sh` — monitorear llamadas en tiempo real
- `monitor_logs.sh` — tail de logs
- `ver_logs_llamadas.sh` — ver logs de llamadas
- `procesar_llamadas_pendientes.sh` — disparar despacho manual
- `sincronizar_conversation_ids.sh` — sync entre BD y ElevenLabs
- `verificar_sistema_completo.sh`, `verificar_sistema_llamadas.sh`
- `actualizar_conductores_existentes.sh`
- `switch_arcangel_mode.sh` — cambiar entre prod/dev de Arcángel
- `check_arcangel_status.sh`, `check_elevenlabs_status.php`
- `reload-php-fpm.sh` — recargar PHP-FPM

### 17.4 Comandos Artisan operativos
```bash
# Procesar cola manualmente
php artisan queue:work --queue=default --tries=3

# Monitor de la cola
php artisan monitor:call-queue

# Despachar llamadas pendientes
php artisan calls:enqueue-pending

# Resetear cola
php artisan queue:reset

# Sincronizar respuestas
php artisan calls:sync-driver-responses

# Limpiar audios viejos
php artisan elevenlabs:cleanup-audios

# Backfill datos faltantes
php artisan calls:backfill-data

# Cambiar modo Arcángel
php artisan arcangel:mode-switch development
```

### 17.5 Cron recomendado
- `* * * * * php artisan monitor:call-queue` — cada minuto despacha pendientes
- `0 * * * * php artisan elevenlabs:cleanup-audios` — cada hora limpia audios
- `0 0 1 * * php artisan goals:evaluate-monthly` — primer día de mes evalúa metas
- `0 2 * * * php artisan system:cleanup` — limpieza diaria 2am

### 17.6 Monitoreo
- `/server/metrics` — endpoint con métricas del sistema (memoria, CPU, BD, queue)
- `/debug-system-info` — info de sesión/CSRF
- `/elevenlabs-queue-status` — estado de la cola de llamadas
- `/api/elevenlabs/test-connection` — health check ElevenLabs
- `/api/arcangel/health` — health check Arcángel
- `/fingerprint` — hash del commit (para verificar versión deployada)

### 17.7 Logs
- Laravel: `storage/logs/laravel.log`
- MCP: `conalca-mcp/mcp-server/logs/`
- Audios: `storage/app/public/audios/` (limpieza automática)

### 17.8 Backups
- BD: `backup_conalca_20251118_002012.sql.gz` (referencia en raíz)
- Proyecto: `conalca-backup-20260126-210141.zip`, `conalca_backup_20260107_164331.zip`
- **Recomendación:** programar backups diarios incrementales de BD + semanales de assets.

---

## 18. CHECKLIST DE ENTREGA Y PUNTOS DE ATENCIÓN

### 18.1 Items a verificar al recibir el proyecto
- [ ] Acceso SSH al servidor (`ssh ubuntu@13.56.4.123`)
- [ ] Credenciales `.env` válidas (no usar `.env.development` en producción)
- [ ] API Keys vigentes:
  - [ ] OpenAI (`OPENAI_API_KEY`) — verificar saldo
  - [ ] Groq (`GROQ_API_KEY`)
  - [ ] ElevenLabs (`ELEVENLABS_API_KEY`) — verificar saldo de caracteres TTS
  - [ ] Arcángel (`ARCANGEL_API_KEY`) — verificar permisos
  - [ ] Silogtran (`SILOG_USER`, `SILOG_PASS`)
  - [ ] BulkGate (`BULKGATE_APP_TOKEN`)
  - [ ] Pusher (`PUSHER_APP_*`)
  - [ ] Twilio (`TWILIO_SID`, `TWILIO_AUTH_TOKEN`)
- [ ] Servicios corriendo: nginx, PHP-FPM, Redis, MySQL, queue-worker, MCP server
- [ ] Build de assets actualizado: `npm run build`
- [ ] Migraciones al día: `php artisan migrate --pretend`
- [ ] Cache limpios: `php artisan optimize:clear`
- [ ] Certificado SSL vigente (Let's Encrypt o similar)
- [ ] DNS apuntando correctamente a `conalcaia.conalca.com.co`
- [ ] Webhook de ElevenLabs configurado en su dashboard

### 18.2 Dependencias externas críticas
| Servicio | Plan/saldo | Riesgo |
|----------|------------|--------|
| OpenAI | API pay-as-you-go | Costo variable según volumen de cotizaciones |
| ElevenLabs | Plan de TTS + ConvAI | **CRÍTICO** — sin saldo no hay llamadas |
| Arcángel | API interna del cliente | **CRÍTICO** — sin conductores no hay flujo |
| Silogtran | API del cliente | **CRÍTICO** — sin esto no se generan ST reales |
| Zadarma SIP | Plan de líneas | Limita concurrencia a 2 |
| Twilio | Pay-as-you-go | Alternativa SIP (10 líneas) |

### 18.3 Puntos sensibles del código
- **Modelo `CotizacionModel`** es el nodo central — cualquier cambio afecta toda la cadena.
- **Service `MCPAssistantService`** es el cerebro del chat IA — toca con cuidado.
- **`CallQueueManager`** gestiona concurrencia — un bug aquí puede saturar SIP.
- **`ElevenLabsWebhookController`** valida HMAC — cualquier cambio en validación rompe ingreso de eventos.
- **`SilogtranTransform`** — los campos del payload son sensibles, Silogtran rechaza valores inválidos (ej: 0 en acompañamiento debe ser 1).
- **Migraciones legacy** (`create_legacy_tables_for_compatibility`, `create_legacy_views_fixed`) — son vistas/tablas para compatibilidad, no eliminar.

### 18.4 Tareas pendientes documentadas (según `SISTEMA_LLAMADAS_CONFIG.md`)
- ❌ Sistema de **reintentos para conductores que no contestan** (saltar turno y reagendar al final).
- ❌ Campo `intentos` en tabla `llamadas` para llevar contador.
- ❌ Reordenamiento de la cola tras "no_answer".

### 18.5 Mejoras sugeridas
1. **Tests automatizados**: hay 60+ archivos `test_*.php` y `test_*.sh` standalone, conviene migrarlos a PHPUnit estructurado en `tests/Feature` y `tests/Unit`.
2. **CI/CD**: hay `.github/workflows/` pero verificar pipeline activo.
3. **Limpieza de carpeta raíz**: existen 70+ archivos `.php`, `.sh`, `.md` de scripts ad-hoc en la raíz de `conalca/` que conviene mover a `scripts/` o `docs/`.
4. **Eliminar Twilio legacy** si ya no se usa (ahorra mantenimiento).
5. **Documentar API pública** en OpenAPI/Swagger (ya existe colección Postman: `Conalca_API_Collection.postman_collection.json`).
6. **Monitorear costos de OpenAI** — el flujo de extracción consume tokens y puede dispararse con muchos clientes.

### 18.6 Documentación adicional incluida
- `Manual de uso API conexión ARCANGEL - CONALCA IA v1.0.pdf` (197 KB)
- `Conalca_API_Collection.postman_collection.json` (31 KB)
- `POSTMAN_SEED_COTIZACIONES.json`, `POSTMAN_INSTRUCCIONES.md`
- `GUIA_INTEGRACION_RAPIDA.md`
- `GUIA_DESARROLLO.md`, `CHECKLIST_DEV.md`
- `GUIA_FECHA_CARGUE_API.md`
- `GUIA_MONITOREO_LLAMADAS.md`
- `GUIA_PRUEBAS_SISTEMA.md`, `GUIA_PRUEBAS_CONDUCTORES.md`
- `PROMPT_NATALIA_V2.md` — prompt completo del agente de voz
- `INSTRUCCIONES_ASISTENTE_ARCANGEL.md`
- `INSTRUCCIONES_ASISTENTE_OPTIMIZADAS.md`
- `INSTRUCCIONES_ASISTENTE_IA_CIUDADES.md`

---

## RESUMEN FINAL

**CONALCA IA** es un sistema Laravel 10 + React + Livewire que automatiza el ciclo comercial completo de una empresa de transporte:

1. **Chat IA con OpenAI** asiste al comercial a crear cotizaciones extrayendo datos automáticamente del lenguaje natural.
2. **Pricing motor** sugiere precios por ruta/vehículo.
3. **Cliente** acepta vía link público.
4. **Arcángel** filtra conductores disponibles.
5. **ElevenLabs Conversational AI** (voz Andrea/Natalia) realiza llamadas reales, negocia y registra decisiones vía servidor MCP.
6. **Solicitud de Transporte** se arma en 10 pasos y se envía a **Silogtran** para generar la orden real.

Stack: **PHP 8.1 / Laravel 10 / MySQL / Redis / React 18 / Vite / Tailwind / Livewire 3 / Python MCP / FastAPI**.

Integraciones: **OpenAI, Groq, ElevenLabs (Zadarma+Twilio SIP), Arcángel, Silogtran, BulkGate, Pusher, Twilio, SMTP**.

Servidor: **AWS EC2 Ubuntu** (13.56.4.123) en `https://conalcaia.conalca.com.co`.

El sistema está en producción y tiene **108 migraciones**, **60+ modelos**, **78 controladores**, **22 servicios**, **4 jobs**, **13 componentes Livewire**, **54 comandos Artisan**, **99 vistas Blade** y un **servidor MCP Python con 23 herramientas**.

---

**Documento generado el 2026-05-13 como entrega técnica del proyecto CONALCA.**
