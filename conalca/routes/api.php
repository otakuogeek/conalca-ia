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
use App\Http\Controllers\Api\ElevenLabsController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PricingApiController;
use App\Http\Controllers\Api\ArcangelController;

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

// Endpoint para iniciar llamadas reales con ElevenLabs
Route::post('/start-elevenlabs-calls/{cotizacionId}', [App\Http\Controllers\ConversationalAgentController::class, 'startElevenLabsCalls'])
    ->name('api.start-elevenlabs-calls');

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

// Búsqueda en tiempo real de empresas
Route::get('companies/search', [App\Http\Controllers\Api\CompanySearchController::class, 'search'])->name('api.companies.search');

// Búsqueda avanzada de clientes (mejorada)
Route::get('clients/search', [App\Http\Controllers\Api\ClientSearchController::class, 'search'])->name('api.clients.search');
Route::get('clients/search-by-document', [App\Http\Controllers\Api\ClientSearchController::class, 'searchByDocument'])->name('api.clients.searchByDocument');

// Búsqueda de clientes para React (nueva implementación)
Route::get('clients/search-react', [App\Http\Controllers\Api\ClientController::class, 'search'])->name('api.clients.search-react');
Route::get('clients/by-document', [App\Http\Controllers\Api\ClientController::class, 'getByDocument'])->name('api.clients.by-document');

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
        
    Route::get('/messages/{threadId}', [App\Http\Controllers\Api\ChatController::class, 'getMessages'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.messages');
        
    Route::get('/run/{threadId}/{runId}', [App\Http\Controllers\Api\ChatController::class, 'checkRunStatus'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class])
        ->name('api.chat.run.status');
        
    // Ruta para crear grupos de cotización con parámetros automáticos
    Route::post('/quote/create-group', [App\Http\Controllers\Api\QuoteCreationController::class, 'createQuoteGroup'])
        ->middleware('auth')
        ->name('api.quote.create.group');
});

// ═══════════════════════════════════════════════════════════════
// Arcangel API Routes - Integración con sistema Arcangel
// ═══════════════════════════════════════════════════════════════
Route::prefix('arcangel')->group(function () {
    // Health check - sin autenticación
    Route::get('/health', [App\Http\Controllers\Api\ArcangelController::class, 'healthCheck'])
        ->name('api.arcangel.health');
    
    // Rutas protegidas con autenticación
    Route::middleware(['auth:sanctum'])->group(function () {
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
