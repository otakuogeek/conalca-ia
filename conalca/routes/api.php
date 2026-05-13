<?php

use App\Http\Controllers\ApiProductController;
use App\Http\Controllers\AnalysisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ContactController;

use Illuminate\Support\Facades\Cache;
use App\Imports\DataColumnImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\DataColumnController;
use App\Http\Controllers\Api\CallStatusController;
use App\Http\Controllers\Api\CallHangupController;
use App\Http\Controllers\Api\ElevenLabsController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PricingApiController;
use App\Http\Controllers\Api\ArcangelController;
use App\Models\ConversationSession;

// ═══════════════════════════════════════════════════════════════
// EMERGENCY ENDPOINT - Detener runs atascados
// ═══════════════════════════════════════════════════════════════
Route::get('/emergency/stop-run/{threadId}', function($threadId) {
    try {
        $session = ConversationSession::where('session_id', $threadId)->first();
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada'
            ], 404);
        }
        
        $metadata = json_decode($session->metadata ?? '{}', true);
        $oldRunId = $metadata['last_run_id'] ?? null;
        $oldStatus = $metadata['last_run_status'] ?? null;
        
        // Forzar status a completed sin datos para detener polling
        $metadata['last_run_status'] = 'completed';
        $session->metadata = json_encode($metadata);
        $session->save();
        
        \Log::info('🚨 EMERGENCY STOP ejecutado', [
            'thread_id' => $threadId,
            'old_run_id' => $oldRunId,
            'old_status' => $oldStatus
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Run detenido forzadamente',
            'old_run_id' => $oldRunId,
            'old_status' => $oldStatus,
            'new_status' => 'completed',
            'action' => 'Por favor recarga la página'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// ═══════════════════════════════════════════════════════════════
// TEST ENDPOINT - Arcangel sin autenticación (temporal)
// ═══════════════════════════════════════════════════════════════
Route::post('/test-arcangel-buscar', function(\Illuminate\Http\Request $request) {
    try {
        $arcangelService = app(\App\Services\ArcangelService::class);
        $controller = app(\App\Http\Controllers\Api\ArcangelDriversController::class);
        
        // Probar servicio directamente
        $vehiculos = $arcangelService->getVehiculosCercanos('FUNZA');
        
        return response()->json([
            'success' => true,
            'test' => 'Endpoint de prueba funcionando',
            'ciudad' => 'FUNZA',
            'total_vehiculos' => count($vehiculos['vehiculos'] ?? []),
            'primeros_3' => array_slice($vehiculos['vehiculos'] ?? [], 0, 3),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'guards' => array_keys(config('auth.guards')),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

use App\Http\Controllers\Api\ArcangelDriversController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users/me', function (Request $request) {
        return response()->json($request->user());
    });
});

Route::middleware(['auth:sanctum'])
        ->prefix('pricings')
        ->group(function () {
            /* ---------- LISTAR / CREAR UNO ---------- */
            Route::get ('',   [PricingApiController::class, 'index']);   // «» en vez de «/»
            Route::post('',   [PricingApiController::class, 'store']);

            /* ----------  BULK  ---------- */
            Route::post ('bulk', [PricingApiController::class, 'bulkStore'])
                    ->name('pricings.bulk.store');

            Route::put  ('bulk', [PricingApiController::class, 'bulkUpdate'])
                    ->name('pricings.bulk.update');

            Route::delete('bulk', [PricingApiController::class, 'bulkDestroy'])
                    ->name('pricings.bulk.delete');

            /* ---------- OPERACIONES POR ID ---------- */
            Route::get   ('{pricing}', [PricingApiController::class, 'show'])
                    ->whereNumber('pricing');
            Route::put   ('{pricing}', [PricingApiController::class, 'update'])
                    ->whereNumber('pricing');
            Route::delete('{pricing}', [PricingApiController::class, 'destroy'])
                    ->whereNumber('pricing');
        });



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get/product/{product_name}', [ApiProductController::class => 'getProduct']);

// ═══════════════════════════════════════════════════════════════
// MCP (Model Context Protocol) - Búsqueda inteligente de productos
// ═══════════════════════════════════════════════════════════════
Route::prefix('mcp')->group(function () {
    Route::post('/search-products', [App\Http\Controllers\Api\ProductController::class, 'search']);
});

Route::post('/get-multiple-data', [DataColumnController::class, 'getMultipleData']);

Route::post('/import-data-columns', function (Request $request) {
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv',
    ]);

    $fixedColumn = 'ciudad'; // Valor fijo para la columna 'column'

    Excel::import(new DataColumnImport($fixedColumn), $request->file('file'));

    return response()->json(['message' => 'Importación completada']);
});

Route::get('/lambda/{code}/{status}', [CallController::class, 'updateCallStatusInLambda']);
Route::get('/get-call-status/{call_code}', [CallController::class, 'getCallStatusFromLambda']);


// Rutas legacy del CallController (comentadas para usar el nuevo sistema conversacional)
// Route::post('/call-drivers',        [CallController::class,'callDrivers']); // botón React
Route::post('/debug-call-drivers',  [CallController::class,'debugCallDrivers']); // debug endpoint
Route::post('/test-simple',         [CallController::class,'testSimple']); // test simple
Route::post('/test-webhook-calls',  function() { return response()->json(['message' => 'webhook works']); }); // ElevenLabs webhook test
Route::post('/backfill-call-data',  [App\Http\Controllers\ConversationalAgentController::class,'backfillMissingCallData']); // Completar datos faltantes
Route::post('/retry-calls',         [CallController::class,'handleCallRetries']); // sistema de reintentos

// Rutas para el estado de llamadas
Route::get('/calls/{callId}/status', [CallController::class, 'getCallStatus'])->name('api.calls.status');

// Ruta para obtener el estado de conductores aceptados por cotización
Route::get('/calls/{cotizacionId}', [CallStatusController::class, 'show'])->name('api.calls.accepted');
Route::post('/calls/{cotizacionId}/select-driver', [CallStatusController::class, 'selectDriver'])->name('api.calls.select-driver');
Route::get('/conductor-details/{driverId}', [CallStatusController::class, 'getDriverDetails'])->name('api.conductor.details');

// Client assignment routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/clients/assign', [ContactController::class, 'assignUserToClient']);
    Route::get('/clients/{id}/assigned-users', [ContactController::class, 'getClientAssignedUsers']);
    Route::delete('/clients/remove-assignment', [ContactController::class, 'removeUserFromClient']);
    Route::get('/users/commercial-roles', [ContactController::class, 'getCommercialUsers']);
});

// Rutas para análisis de datos
Route::get('analysis/data', [AnalysisController::class, 'getAnalysisDataApi'])->name('api.analysis.data');
Route::get('analysis/map-data', [AnalysisController::class, 'getMapDataEndpoint'])->name('api.analysis.map-data');
Route::get('analysis/debug-cities', [AnalysisController::class, 'debugCities'])->name('api.analysis.debug-cities');


// Rutas para ElevenLabs TTS
Route::prefix('elevenlabs')->middleware('elevenlabs.config')->group(function () {
    Route::get('/voices', [ElevenLabsController::class, 'getVoices'])->name('api.elevenlabs.voices');
    Route::get('/voices/spanish', [ElevenLabsController::class, 'getSpanishVoices'])->name('api.elevenlabs.spanish-voices');
    Route::get('/models', [ElevenLabsController::class, 'getModels'])->name('api.elevenlabs.models');
    Route::post('/test-tts', [ElevenLabsController::class, 'testTts'])->name('api.elevenlabs.test-tts');
});

// Rutas del Agente Conversacional para Conductores
Route::prefix('agent')->group(function () {
    // Webhooks públicos para Twilio
    Route::match(['get', 'post'], '/initial-call', [App\Http\Controllers\ConversationalAgentController::class, 'handleInitialCall'])
        ->name('agent.initial-call');
    
    Route::match(['get', 'post'], '/handle-response', [App\Http\Controllers\ConversationalAgentController::class, 'handleDriverResponse'])
        ->name('agent.handle-response');
    
    Route::match(['get', 'post'], '/wait-for-response', [App\Http\Controllers\ConversationalAgentController::class, 'waitForResponse'])
        ->name('agent.wait-for-response');
    
    Route::match(['get', 'post'], '/second-chance', [App\Http\Controllers\ConversationalAgentController::class, 'secondChance'])
        ->name('agent.second-chance');
});

// Rutas protegidas para llamadas conversacionales
Route::middleware(['auth:sanctum,web'])->group(function () {
    // Endpoint principal para iniciar llamadas conversacionales (compatible con curl del usuario)
    Route::post('/call-drivers', [App\Http\Controllers\ConversationalAgentController::class, 'initiateConversationalCall'])
        ->name('call-drivers.conversational');

    // Endpoint para iniciar llamadas conversacionales por grupo de cotización
    Route::post('/call-drivers-group', [App\Http\Controllers\ConversationalAgentController::class, 'initiateGroupConversationalCall'])
        ->name('call-drivers.group');

    // Async call routes para evitar timeouts
    Route::post('/call-drivers-group-async', [App\Http\Controllers\Api\AsyncCallController::class, 'initiateAsyncGroupCalls'])
        ->name('call-drivers.group-async');
    Route::get('/call-drivers-group-status', [App\Http\Controllers\Api\AsyncCallController::class, 'getGroupCallStatus'])
        ->name('call-drivers.group-status');

    // Endpoints para gestión de llamadas registradas
    Route::get('/llamadas', [App\Http\Controllers\ConversationalAgentController::class, 'getLlamadasPorCotizacion'])
        ->name('api.llamadas.get');
    Route::post('/llamadas/update-status', [App\Http\Controllers\ConversationalAgentController::class, 'updateLlamadaStatus'])
        ->name('api.llamadas.update-status');
    Route::post('/llamadas/{llamada}/hangup', CallHangupController::class)
        ->name('api.llamadas.hangup');
});

// Endpoint para iniciar llamadas reales con ElevenLabs (fuera de middleware auth para compatibilidad con frontend)
Route::post('/start-elevenlabs-calls/{cotizacionId}', [App\Http\Controllers\ConversationalAgentController::class, 'startElevenLabsCalls'])
    ->name('api.start-elevenlabs-calls');

// ============================================================================
// ElevenLabs Post-Call Webhooks - Control de cola con máximo 2 llamadas simultáneas
// Estos webhooks son llamados por ElevenLabs cuando una llamada termina o falla
// Configurar la URL en ElevenLabs Dashboard > Seguridad > Webhook posterior a la llamada
// ============================================================================
Route::post('/elevenlabs-webhook', [App\Http\Controllers\Api\ElevenLabsWebhookController::class, 'handleWebhook'])
    ->name('api.elevenlabs-webhook');
Route::get('/elevenlabs-queue-status', [App\Http\Controllers\Api\ElevenLabsWebhookController::class, 'getQueueStatus'])
    ->name('api.elevenlabs-queue-status');

// ============================================================================
// ElevenLabs Agent Tools - Endpoints que el agente de voz usa como herramientas
// Estos endpoints son llamados vía webhook por el agente durante las conversaciones
// ============================================================================
Route::prefix('elevenlabs-tools')->group(function () {
    Route::post('/get-contexto-inicial-conductor', [App\Http\Controllers\Api\ElevenLabsAgentToolsController::class, 'getContextoInicialConductor'])
        ->name('api.elevenlabs-tools.get-contexto-inicial-conductor');
    Route::post('/get-conductor-by-telefono', [App\Http\Controllers\Api\ElevenLabsAgentToolsController::class, 'getConductorByTelefono'])
        ->name('api.elevenlabs-tools.get-conductor');
    Route::post('/get-cotizaciones', [App\Http\Controllers\Api\ElevenLabsAgentToolsController::class, 'getCotizaciones'])
        ->name('api.elevenlabs-tools.get-cotizaciones');
    Route::post('/precioviaje', [App\Http\Controllers\Api\ElevenLabsAgentToolsController::class, 'precioViaje'])
        ->name('api.elevenlabs-tools.precioviaje');
    Route::post('/save-driver-decision', [App\Http\Controllers\Api\ElevenLabsAgentToolsController::class, 'saveDriverDecision'])
        ->name('api.elevenlabs-tools.save-driver-decision');
});

// Endpoint para hacer llamadas directas a cualquier número
Route::post('/make-direct-call', [App\Http\Controllers\ConversationalAgentController::class, 'makeDirectCall'])
    ->name('call-drivers.direct');

// API endpoints para decisiones de conductores
Route::get('/driver-decisions', [App\Http\Controllers\ConversationalAgentController::class, 'getDriverDecisions'])
    ->name('api.driver-decisions');

// Rutas del Agente de Voz Conversacional (Voice Agent)
Route::prefix('voice')->group(function () {
    // TwiML endpoints - públicos para Twilio
    Route::match(['get', 'post'], '/welcome', [App\Http\Controllers\VoiceAgentController::class, 'welcome'])
        ->name('voice.welcome');
    
    // Nueva ruta para procesamiento conversacional
    Route::post('/conversation/{conversation_id}/process', [App\Http\Controllers\VoiceAgentController::class, 'processConversation'])
        ->name('voice.conversation.process');
    
    // Rutas legacy para compatibilidad (redirigen al nuevo sistema)
    Route::post('/menu/response', [App\Http\Controllers\VoiceAgentController::class, 'menuResponse'])
        ->name('voice.menu.response');
    
    Route::match(['get', 'post'], '/agent/start', [App\Http\Controllers\VoiceAgentController::class, 'startVoiceAgent'])
        ->name('voice.agent.start');
    
    Route::post('/agent/process', [App\Http\Controllers\VoiceAgentController::class, 'processVoiceInput'])
        ->name('voice.agent.process');
    
    Route::match(['get', 'post'], '/services/menu', [App\Http\Controllers\VoiceAgentController::class, 'servicesMenu'])
        ->name('voice.services.menu');
    
    Route::post('/services/response', [App\Http\Controllers\VoiceAgentController::class, 'servicesResponse'])
        ->name('voice.services.response');
    
    Route::match(['get', 'post'], '/transfer', [App\Http\Controllers\VoiceAgentController::class, 'transferToAgent'])
        ->name('voice.transfer');
});

// Rutas de Twilio (continuación)
Route::prefix('twilio')->group(function () {
    
    // NOTE: Twilio API routes removed - service disabled
    Route::middleware(['auth:sanctum'])->group(function () {
        // Twilio functionality disabled
        Route::any('/call/outbound', function() {
            return response()->json(['error' => 'Twilio service removed'], 503);
        })->name('twilio.call.outbound');
        
        Route::any('/call/{callSid}', function() {
            return response()->json(['error' => 'Twilio service removed'], 503);
        })->name('twilio.call.info');
        
        Route::any('/calls', function() {
            return response()->json(['error' => 'Twilio service removed'], 503);
        })->name('twilio.calls.list');
        
        Route::any('/stats', function() {
            return response()->json(['error' => 'Twilio service removed'], 503);
        })->name('twilio.calls.stats');
    });
});

// Rutas para gestión de audio local
Route::prefix('audio')->group(function () {
    Route::get('/stats', [CallController::class, 'getAudioStorageStats'])
        ->name('audio.stats');
    
    Route::get('/list', [CallController::class, 'listAudios'])
        ->name('audio.list');
    
    Route::post('/cleanup', [CallController::class, 'cleanupTempAudios'])
        ->name('audio.cleanup');
});

// Cotización Cliente API
Route::get('grupo-cotizacion/{id}', [App\Http\Controllers\Api\CotizacionClienteController::class, 'getGroupData'])->name('api.grupo-cotizacion.show');

// Recuperación de grupos de cotización para continuar progreso
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('groups/{id}/recover', [App\Http\Controllers\Api\GroupRecoveryController::class, 'recover'])->name('api.groups.recover');
});

// Búsqueda en tiempo real de empresas
Route::get('companies/search', [App\Http\Controllers\Api\CompanySearchController::class, 'search'])->name('api.companies.search');

// Búsqueda avanzada de clientes (mejorada)
Route::get('clients/search', [App\Http\Controllers\Api\ClientSearchController::class, 'search'])->name('api.clients.search');
Route::get('clients/search-by-document', [App\Http\Controllers\Api\ClientSearchController::class, 'searchByDocument'])->name('api.clients.searchByDocument');

// Búsqueda de clientes para React (nueva implementación)
Route::get('clients/search-react', [App\Http\Controllers\Api\ClientController::class, 'search'])->name('api.clients.search-react');
Route::get('clients/by-document', [App\Http\Controllers\Api\ClientController::class, 'getByDocument'])->name('api.clients.by-document');
Route::post('clients', [App\Http\Controllers\Api\ClientController::class, 'store'])->name('api.clients.store');

// Goals API - Rutas protegidas
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/goals', [App\Http\Controllers\Api\GoalController::class, 'index']);
    Route::put('/goals/{goal}', [App\Http\Controllers\Api\GoalController::class, 'update']);
    Route::get('/my-goal', [App\Http\Controllers\Api\GoalController::class, 'myGoal']);
    Route::get('/boss/notifications', [App\Http\Controllers\Api\GoalController::class, 'notifications']);
    Route::post('/boss/notifications/{id}/read', [App\Http\Controllers\Api\GoalController::class, 'markNotificationRead']);
});

Route::get('analysis/test', [AnalysisController::class, 'testData'])->name('api.analysis.test');

// Búsqueda de empresas en tiempo real (legacy - mantener compatibilidad)
Route::get('search/companies', function(Request $request) {
    $search = $request->get('q', '');
    
    if (strlen($search) < 2) {
        return response()->json([]);
    }
    
    $companies = \App\Models\Client::where('cliente', 'LIKE', '%' . $search . '%')
        ->select('id', 'cliente as name', 'documento', 'telefono', 'ciudad', 'estado')
        ->limit(10)
        ->get();
    
    return response()->json($companies);
});

// ═══════════════════════════════════════════════════════════════
// Chat AI Routes - Sistema de chat inteligente para formularios
// NOTA: Estas rutas NO requieren CSRF para facilitar las llamadas desde frontend
// ═══════════════════════════════════════════════════════════════
Route::prefix('chat')->group(function () {
    Route::post('/simple', [App\Http\Controllers\Api\ChatController::class, 'chat'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.simple');
    
    Route::post('/functions', [App\Http\Controllers\Api\ChatController::class, 'chatWithFunctions'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.functions');
        
    Route::post('/quote', [App\Http\Controllers\Api\ChatController::class, 'quoteChat'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.quote');
        
    // 🆕 Fallback para GET accidental en navegador
    Route::get('/quote', function() {
        return response()->json([
            'message' => 'Este endpoint es solo para uso interno del chat (POST).',
            'status' => 'active'
        ]);
    });
    
    // 🆕 Ruta para extracción inteligente de datos con IA
    Route::post('/extract-quote-data', [App\Http\Controllers\Api\DataExtractionController::class, 'chatExtractData'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.extract.quote.data');
        
    Route::post('/clear-stuck-runs', [App\Http\Controllers\Api\ChatController::class, 'clearStuckRuns'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.clear.stuck.runs');
        
    Route::get('/messages/{threadId}', [App\Http\Controllers\Api\ChatController::class, 'getMessages'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.messages');
        
    Route::get('/run/{threadId}/{runId}', [App\Http\Controllers\Api\ChatController::class, 'checkRunStatus'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.run.status');
        
    // Rutas para manejo de mensajes huérfanos
    Route::post('/orphan/process', [App\Http\Controllers\Api\OrphanMessageController::class, 'processOrphanMessages'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.orphan.process');
        
    Route::get('/orphan/candidates', [App\Http\Controllers\Api\OrphanMessageController::class, 'listOrphanCandidates'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.orphan.candidates');
        
    // Ruta para crear grupos de cotización con parámetros automáticos
    Route::post('/quote/create-group', [App\Http\Controllers\Api\QuoteCreationController::class, 'createQuoteGroup'])
        ->middleware('auth')
        ->name('api.quote.create.group');
    
    // Ruta para limpiar thread de OpenAI (nueva cotización)
    Route::post('/clear-thread', [App\Http\Controllers\Api\QuoteCreationController::class, 'clearThread'])
        ->middleware('auth')
        ->name('api.chat.clear.thread');
    
    // 🆕 Ruta para limpiar mensajes de un grupo específico
    Route::post('/chat/clear-group', [App\Http\Controllers\Api\ChatController::class, 'clearGroupMessages'])
        ->middleware('auth')
        ->name('api.chat.clear.group');
    
    // Ruta para guardar group_id en sesión (puente React → Livewire)
    Route::post('/quote/set-session-group', [App\Http\Controllers\Api\QuoteSessionController::class, 'setGroupInSession'])
        ->middleware('auth')
        ->name('api.quote.set.session.group');
        
    // Rutas para manejo de rutas individuales de cotización
    Route::post('/quote/save-routes', [App\Http\Controllers\Api\QuoteRoutesController::class, 'saveQuoteRoutes'])
        ->middleware('auth')
        ->name('api.quote.save.routes');
        
    Route::get('/quote/routes/{groupId}', [App\Http\Controllers\Api\QuoteRoutesController::class, 'getQuoteRoutes'])
        ->middleware('auth')
        ->name('api.quote.get.routes');
    
    // 🆕 Actualizar extracted_data (para cambios de tara en tiempo real)
    Route::post('/chat/update-extracted-data', [App\Http\Controllers\Api\ChatController::class, 'updateExtractedData'])
        ->middleware('auth')
        ->name('api.chat.update.extracted.data');
        
    // Debug endpoint para verificar rutas (temporal)
    Route::get('/quote/debug/{groupId}', function($groupId) {
        $group = \App\Models\GroupCotization::find($groupId);
        if (!$group) {
            return response()->json(['error' => 'Grupo no encontrado']);
        }
        
        $routes = $group->cotizaciones()->get();
        return response()->json([
            'group_id' => $groupId,
            'group_reference' => $group->reference,
            'routes_count' => $routes->count(),
            'routes' => $routes->map(function($r) {
                return [
                    'id' => $r->id,
                    'origen' => $r->ciudad_origen,
                    'destino' => $r->ciudad_destino,
                    'peso' => $r->peso_mercancia,
                    'estado' => $r->estado
                ];
            })
        ]);
    })->name('api.quote.debug');
    
    // Ruta para guardar cotizaciones desde el chat
    Route::post('/save-quote-from-chat', [App\Http\Controllers\Api\QuoteSaveController::class, 'saveQuoteFromChat'])
        ->middleware('auth')
        ->name('api.quote.save.from.chat');
});

// 🆕 Rutas para extracción de datos con IA
Route::post('/extract-quote-data', [App\Http\Controllers\Api\DataExtractionController::class, 'extractData'])
    ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
    ->name('api.extract.quote.data');

// Ruta para enviar emails de cotización
Route::post('/send-quote-email', [App\Http\Controllers\Api\QuoteEmailController::class, 'sendQuoteEmail'])
    ->middleware('auth')
    ->name('api.send.quote.email');

// ═══════════════════════════════════════════════════════════════
// Arcangel API Routes - Integración con sistema Arcangel
// ═══════════════════════════════════════════════════════════════
Route::prefix('arcangel')->group(function () {
    // Health check - sin autenticación
    Route::get('/health', [App\Http\Controllers\Api\ArcangelController::class, 'healthCheck'])
        ->name('api.arcangel.health');
    
    // Rutas protegidas con autenticación web o sanctum
    Route::middleware(['auth:sanctum,web'])->group(function () {
        // Endpoints específicos de Arcangel
        
        // Ciudades
        Route::get('/ciudades', [App\Http\Controllers\Api\ArcangelController::class, 'obtenerCiudades'])
            ->name('api.arcangel.ciudades');
        
        // Vehículos
        Route::get('/vehiculos/cercanos', [App\Http\Controllers\Api\ArcangelController::class, 'obtenerVehiculosCercanos'])
            ->name('api.arcangel.vehiculos.cercanos');
        
        Route::get('/vehiculos/filtrar', [App\Http\Controllers\Api\ArcangelController::class, 'filtrarVehiculos'])
            ->name('api.arcangel.vehiculos.filtrar');
        
        Route::get('/vehiculos/clases', [App\Http\Controllers\Api\ArcangelController::class, 'obtenerClasesDisponibles'])
            ->name('api.arcangel.vehiculos.clases');
        
        // Conductores disponibles
        Route::post('/buscar-conductores', [App\Http\Controllers\Api\ArcangelDriversController::class, 'buscarConductores'])
            ->name('api.arcangel.buscar.conductores');
        
        Route::post('/buscar-conductores-filtros', [App\Http\Controllers\Api\ArcangelDriversController::class, 'buscarPorFiltros'])
            ->name('api.arcangel.buscar.filtros');
        
        // Llamar a conductor específico
        Route::post('/llamar-conductor', [App\Http\Controllers\Api\ArcangelDriversController::class, 'llamarConductor'])
            ->name('api.arcangel.llamar.conductor');
        
        // Métodos genéricos para cualquier endpoint
        Route::get('/consultar', [App\Http\Controllers\Api\ArcangelController::class, 'consultar'])
            ->name('api.arcangel.consultar');
        Route::post('/crear', [App\Http\Controllers\Api\ArcangelController::class, 'crear'])
            ->name('api.arcangel.crear');
        Route::put('/actualizar', [App\Http\Controllers\Api\ArcangelController::class, 'actualizar'])
            ->name('api.arcangel.actualizar');
        Route::delete('/eliminar', [App\Http\Controllers\Api\ArcangelController::class, 'eliminar'])
            ->name('api.arcangel.eliminar');
        
        // Utilidades
        Route::post('/clear-cache', [App\Http\Controllers\Api\ArcangelController::class, 'clearCache'])
            ->name('api.arcangel.clear-cache');
    });
});

// Ruta para eliminar grupos de cotización
Route::delete('/cotizacion/grupos/{id}', [App\Http\Controllers\Api\GroupQuotationController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('api.cotizacion.grupos.destroy');

// ═══════════════════════════════════════════════════════════════
// Silogtran - Consultas externas
// ═══════════════════════════════════════════════════════════════
Route::prefix('silog')->group(function () {
    Route::get('/clientes', [App\Http\Controllers\Api\SilogController::class, 'consultarCliente'])
        ->name('api.silog.consultar-cliente');
});

// ═══════════════════════════════════════════════════════════════
// Solicitud Transporte Routes - Sistema de solicitudes de transporte paso a paso
// ═══════════════════════════════════════════════════════════════
Route::prefix('solicitud-transporte')->group(function () {
    // Guardar parcialmente (step by step)
    Route::post('/guardar-parcial', [App\Http\Controllers\SolicitudTransporteController::class, 'guardarParcial'])
        ->name('api.solicitud-transporte.guardar-parcial');
    
    // Obtener datos para prefill desde grupo de cotización
    Route::get('/prefill-from-group/{grupoId}', [App\Http\Controllers\SolicitudTransporteController::class, 'prefillFromGroup'])
        ->name('api.solicitud-transporte.prefill-from-group');
    
    // Obtener solicitud completa
    Route::get('/{id}', [App\Http\Controllers\SolicitudTransporteController::class, 'obtenerSolicitud'])
        ->name('api.solicitud-transporte.obtener');
    
    // Enviar a Silogtran
    Route::post('/{id}/enviar-silogtran', [App\Http\Controllers\SolicitudTransporteController::class, 'enviarASilogtran'])
        ->name('api.solicitud-transporte.enviar-silogtran');
});

// ═══════════════════════════════════════════════════════════════
// Catálogos para formulario de cotización
// ═══════════════════════════════════════════════════════════════
Route::prefix('catalog')->group(function () {
    // Empaques
    Route::get('/packing', function() {
        $packings = \DB::table('packing')
            ->select('Codigo as id', 'Nombre as name')
            ->orderBy('Nombre')
            ->get();
        return response()->json($packings);
    });
    
    // Clases de vehículo
    Route::get('/vehicle-class', function() {
        $vehicleClasses = \DB::table('vehicle_class')
            ->select('Codigo as id', 'Nombre as name', 'Configuracion as config')
            ->orderBy('Nombre')
            ->get();
        return response()->json($vehicleClasses);
    });
    
    // Carrocerías
    Route::get('/bodywork', function() {
        $bodyworks = \DB::table('bodywork')
            ->select('Codigo as id', 'Nombre as name')
            ->orderBy('Nombre')
            ->get();
        return response()->json($bodyworks);
    });

    // Tara de contenedores
    Route::get('/tara-settings', function() {
        $settings = \App\Models\TaraSetting::getInstance();
        return response()->json([
            'tara_contenedor_20' => $settings->tara_contenedor_20,
            'tara_contenedor_40' => $settings->tara_contenedor_40,
        ]);
    });
});

// ═══════════════════════════════════════════════════════════════
// TEST SEED Routes - Crear cotizaciones de prueba directamente
// Sin autenticación para facilitar pruebas con curl/Postman
// ═══════════════════════════════════════════════════════════════
Route::prefix('test')->group(function () {
    // Crear un grupo con cotizaciones en estado "En tránsito"
    Route::post('/seed-cotizacion', [App\Http\Controllers\Api\TestSeedController::class, 'seedCotizacion'])
        ->name('api.test.seed.cotizacion');

    // Crear múltiples grupos de una vez
    Route::post('/seed-multiple', [App\Http\Controllers\Api\TestSeedController::class, 'seedMultiple'])
        ->name('api.test.seed.multiple');

    // Listar grupos de prueba creados
    Route::get('/seed-groups', [App\Http\Controllers\Api\TestSeedController::class, 'listTestGroups'])
        ->name('api.test.seed.list');

    // Eliminar un grupo de prueba específico
    Route::delete('/seed-groups/{id}', [App\Http\Controllers\Api\TestSeedController::class, 'deleteTestGroup'])
        ->name('api.test.seed.delete');

    // Limpiar todos los grupos de prueba
    Route::delete('/seed-cleanup', [App\Http\Controllers\Api\TestSeedController::class, 'cleanupTestGroups'])
        ->name('api.test.seed.cleanup');
});
