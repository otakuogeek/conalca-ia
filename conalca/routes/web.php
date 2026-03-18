<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordRequestController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\SolicitudTransporteController;
use App\Models\Appointment;
use App\Models\CotizacionModel;
use App\Models\Email;
use App\Models\Pricing;
use App\Models\User;
use App\Models\TransitEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DataColumnController;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Api\GroupQuotationController;
use App\Http\Controllers\Api\UserColumnController;
use App\Http\Controllers\Api\PendingController;
use App\Http\Controllers\Api\SolicitationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\PercentageSettingController;
use App\Http\Controllers\TaraSettingController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\CotizationNoteController;
use App\Http\Controllers\Api\SacCotizationController;
use App\Http\Controllers\Api\CallStatusController;
use App\Http\Controllers\Api\CotizacionClienteController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PackingController;

use App\Http\Controllers\Api\SilogController;

use Illuminate\Support\Facades\Log;

// Endpoint para debugging del sistema
Route::get('/debug-system-info', function (Request $request) {
    return response()->json([
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_started' => session()->isStarted(),
        'cookies' => $request->cookies->all(),
        'headers' => $request->headers->all(),
        'server_time' => now()->toISOString(),
        'app_env' => config('app.env'),
        'session_config' => [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'secure' => config('session.secure'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
        ]
    ]);
});

// Endpoint para simular login completo
Route::post('/debug-login-simulation', function (Request $request) {
    $result = [
        'timestamp' => now()->toISOString(),
        'request_data' => $request->all(),
        'session_before' => [
            'id' => session()->getId(),
            'token' => session()->token(),
            'started' => session()->isStarted()
        ],
        'validation' => null,
        'auth_attempt' => null,
        'errors' => []
    ];
    
    try {
        // Validar datos
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if ($validator->fails()) {
            $result['validation'] = ['success' => false, 'errors' => $validator->errors()];
            return response()->json($result, 422);
        }
        
        $result['validation'] = ['success' => true];
        
        // Intentar autenticación
        $credentials = $request->only('email', 'password');
        $authAttempt = auth()->attempt($credentials);
        
        $result['auth_attempt'] = [
            'success' => $authAttempt,
            'user' => $authAttempt ? auth()->user()->only(['id', 'name', 'email']) : null
        ];
        
        $result['session_after'] = [
            'id' => session()->getId(),
            'token' => session()->token(),
            'started' => session()->isStarted()
        ];
        
        if ($authAttempt) {
            $result['redirect_url'] = route('dashboard.show');
            // NO hacer logout para mantener la sesión
        }
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        $result['errors'][] = [
            'type' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        return response()->json($result, 500);
    }
});

// Endpoint simplificado de login que siempre devuelve JSON
Route::post('/simple-login', function (Request $request) {
    try {
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $credentials = $request->only('email', 'password');
        
        if (auth()->attempt($credentials)) {
            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => auth()->user()->only(['id', 'name', 'email']),
                'redirect' => route('dashboard.show'),
                'session_id' => session()->getId()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
});



Route::get('/testEmail', function () {
    try {
        Mail::to("oyhincapie@gmail.com")->send(new \App\Mail\StepCompleted([
            'origin' => "pereira",
            'destination' => "dosquebradas",
            'price' => "100",
            'vehicle_type' => "tipo",
            'text' => "texto",
            'title' => "titulo",
            'name' => "nombre",
            'phone' => "3232233223",
            'pbx' => "32322332",
            'ubicacion' => "ubicacion",
            'email' => "email",
            'id_last_created' => "las aiddsa",
        ]));

        return "Correo enviado exitosamente.";
    } catch (\Exception $e) {
        Log::error("Error enviando correo: " . $e->getMessage());
        return "Error enviando correo: " . $e->getMessage();
    }
});

Route::get('/test-login', function () {
    // Get the first user
    $user = User::first();

    // Log the user in
    Auth::login($user);

    // Redirect to the desired route, e.g., dashboard
    return redirect()->route('dashboard.show');
});




// Route::get('/test', function () {
//     return view('email.template');
// });

// Route::get('/test-roles', function () {
//     $roles = [
//         'SUPER ADMIN',
//         'JEFE COMERCIAL',
//         'GERENTE DE CUENTA',
//         'ASISTENTE COMERCIAL',
//         'SAC',
//         'PRICING',
//     ];

//     foreach ($roles as $roleName) {
//         Role::firstOrCreate(['name' => $roleName]);
//     }

//     return 'Roles creados correctamente.';
// });

// Route::get('/asignar-superadmin', function () {
//     $user = User::find(1);

//     if ($user) {
//         $user->assignRole('SUPER ADMIN');
//         return 'Rol SUPER ADMIN asignado correctamente al usuario 1.';
//     }

//     return 'Usuario no encontrado.';
// });



Route::post('/get-multiple-data', [DataColumnController::class, 'getMultipleData']);

// Ruta de bienvenida pública para verificar funcionamiento
Route::get('/welcome', function () {
    return '<h1>🚀 Conalca está funcionando correctamente!</h1><p>Servidor Laravel activo en ' . now() . '</p><p><a href="/login">Ir al Login</a></p>';
});

Route::get('/quotes/accept/{cotizacion}', function (CotizacionModel $cotizacion) {
    $cotizacion->update(['silogtran_status' => 'accept']);
    Email::create([
        'from_user' => 1,
        'to_user' => 1,
        'subject' => 'La presolicitud con el id ' . $cotizacion->id . ' A sido Aprovada',
        'description' => 'La solicitud a sido aceptada con exito',
        'files' => json_encode([]),
        'status' => 'no-read',
    ]);
    return "Se a aceptado la cotizacion";
});

Route::get('/quotes/cancel/{cotizacion}', function (CotizacionModel $cotizacion) {
    $cotizacion->update(['silogtran_status' => 'cancel']);
    Email::create([
        'from_user' => 1,
        'to_user' => 1,
        'subject' => 'La presolicitud con el id ' . $cotizacion->id . ' A sido cancelada',
        'description' => 'La solicitud a sido cancelada',
        'files' => json_encode([]),
        'status' => 'no-read',
    ]);
    return "Se ha cancelado la cotizacion";
});

// Public ElevenLabs Testing Routes (no auth required)
Route::prefix('api/elevenlabs')->group(function () {
    Route::get('/test-connection', function () {
        try {
            $elevenLabsCallService = app(\App\Services\ElevenLabsCallService::class);
            $result = $elevenLabsCallService->testSipTrunkConnection();
            return response()->json([
                'service' => 'ElevenLabs SIP Trunk',
                'timestamp' => now()->toISOString(),
                'connection_test' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'service' => 'ElevenLabs SIP Trunk',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/public-stats', function () {
        try {
            $elevenLabsCallService = app(\App\Services\ElevenLabsCallService::class);
            $connectionTest = $elevenLabsCallService->testSipTrunkConnection();
            
            $totalCalls = \App\Models\DriverCallResponse::whereNotNull('elevenlabs_conversation_id')->count();
            $recentCalls = \App\Models\DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            
            return response()->json([
                'service' => 'ElevenLabs SIP Trunk',
                'timestamp' => now()->toISOString(),
                'connection_status' => $connectionTest,
                'stats' => [
                    'total_elevenlabs_calls' => $totalCalls,
                    'calls_last_24h' => $recentCalls
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'service' => 'ElevenLabs SIP Trunk',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

Route::group(['middleware' => 'auth'], function () {

    Route::get('/panel-control-usuarios', function () {
        return view('users.panel');
    })->name('users.panel');

    Route::get('/canales-cotizaciones', function () {
        return view('quotes.channels');
    })->name('quotes.channels');

    // Ruta raíz que redirige según autenticación
    Route::get('/', function () {
        if (auth()->check()) {
            return redirect()->route('dashboard.show');
        }
        return redirect()->route('auth.login');
    });

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'show'])->name('dashboard.show');
    Route::get('/dashboard/chart-data', [App\Http\Controllers\DashboardController::class, 'getChartData'])->name('dashboard.chart-data');

    // Start AI Calls

    // Route::get('/ai-calls', function () {
    //     return view('interactiveCallsAI.index');
    // });

    Route::get('/ai-calls', function (Request $request) {
        return view('interactiveCallsAI.index', ['cotizacion_id' => $request->query('cotizacion_id')]);
    });

    // Página de prueba para llamadas por grupo
    Route::get('/test-group-calls', function () {
        return view('test-group-calls');
    });

    Route::get('/test-modal-integration', function () {
        return view('test-modal-integration');
    });

    Route::get('/last-transport-request', [CallController::class, 'fetchLastTransportRequest']);

    Route::post('/marking-call-drivers', [TransportController::class, 'callDrivers']);

    Route::post('/interactive-call-drivers', [CallController::class, 'callDrivers']);

    Route::get('/get-call-status', function () {
        return response()->json(Cache::get('call_status', [
            'total_usuarios' => 0,
            'estado' => 'No iniciado',
            'llamadas_realizadas' => 0,
            'aceptado_por' => null
        ]));
    });

    Route::post('/cancel-call', [TransportController::class, 'cancelCall'])->name('calls.cancel');

    Route::post('/reset-call-status', function (Request $request) {
        Cache::put('call_status', [
            'total_usuarios' => 0, // O el valor que necesites
            'estado' => 'en ejecucion',
            'llamadas_realizadas' => 0,
            'aceptado_por' => null,
        ], now()->addMinutes(10));

        return response()->json(['message' => 'Call status reiniciado']);
    });

    Route::post('/calls', [CallController::class, 'createCall']);
    Route::post('/call-drivers/add', [CallController::class, 'addCallDriver']);
    Route::post('/blocked-drivers', [CallController::class, 'blockDriver']);

    // ElevenLabs SIP Trunk Routes
    Route::post('/elevenlabs/call', [CallController::class, 'makeElevenLabsCall']);
    Route::get('/elevenlabs/stats', [CallController::class, 'getElevenLabsCallStats']);

    Route::get('/all-calls', [TransportController::class, 'getCallsWithAcceptedDrivers']);
    Route::get('/last-call', [TransportController::class, 'getLastCallWithAcceptedDrivers']);
    Route::get('/all-blocked-drivers', [TransportController::class, 'getBlockedDrivers']);
    Route::delete('/unblock-driver', [TransportController::class, 'unblockDriver'])->name('unblock.driver');

    // Pricing Export Route
    Route::get('/pricing/export/template', [App\Http\Controllers\PricingExportController::class, 'exportTemplate'])->name('pricing.export.template');

    Route::get('/vehicle-type/{solicitudId}', [CallController::class, 'getVehicleType']);

    // End AI Calls

    Route::post('/solicitud/store', [SolicitudTransporteController::class, 'store'])->name('solicitud.store');

    // SOLICITATIONS !!!!!!!!!!!!!!!!!!!!!!!!!!!
    // Route::get('/requests', function () {
    //     return view('requests.show');
    // })->name('requests.show');
    // Route::get('/new-requests', function () {
    //     return view('requests.components.newRequests');
    // })->name('requests.newRequests');


    Route::get('analysis', function () {
        $controller = new App\Http\Controllers\AnalysisController();
        return $controller->show();
    })->name('analysis.show');
    
    Route::get('analysis/data', function () {
        $controller = new App\Http\Controllers\AnalysisController();
        return $controller->getAnalysisDataApi();
    })->name('analysis.data');

    Route::get('pricing', function () {
        $pricings = Pricing::all();
        return view('pricing.show', compact('pricings'));
    })->name('pricing.show');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.show');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::get('/calendar/search', [CalendarController::class, 'search'])->name('calendar.search');

    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.show');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/update', [ContactController::class, 'update'])->name('contacts.update');
    
    // Client assignment routes
    Route::post('/clients/assign', [ContactController::class, 'assignUserToClient'])->name('clients.assign');
    Route::get('/clients/{id}/assigned-users', [ContactController::class, 'getClientAssignedUsers'])->name('clients.assigned-users');
    Route::get('/clients/{id}/details', [ContactController::class, 'getClientDetails'])->name('clients.details');
    Route::delete('/clients/remove-assignment', [ContactController::class, 'removeUserFromClient'])->name('clients.remove-assignment');

    Route::post('/documents', [DocumentController::class, 'store'])->name('document.store');
    Route::get('/documents', [DocumentController::class, 'index'])->name('document.show');
    Route::get('/api/client/{clientId}/files', [DocumentController::class, 'getClientFiles'])->name('client.files');
    Route::get('/documents/clients/{id}/details', [DocumentController::class, 'getClientDetails'])->name('document.client.details');
    Route::get('/documents/clients/{id}/files/{category}', [DocumentController::class, 'getClientFilesByCategory']);
    
    // Debug route for client data
    Route::get('/debug/client/{id}', function($id) {
        $client = \App\Models\Client::findOrFail($id);
        return response()->json([
            'client_data' => $client->toArray(),
            'fillable_fields' => $client->getFillable(),
            'all_attributes' => $client->getAttributes()
        ]);
    });    // Debug route to test client data
    Route::get('/debug/client/{id}', function($id) {
        $client = \App\Models\Client::find($id);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return response()->json([
            'raw_client' => $client->toArray(),
            'field_mapping' => [
                'cliente' => $client->cliente,
                'documento' => $client->documento,
                'direccion' => $client->direccion,
                'telefono' => $client->telefono,
                'email' => $client->email,
                'actividad' => $client->actividad,
                'ciudad' => $client->ciudad,
                'contacto' => $client->contacto,
                'cargo' => $client->cargo,
            ]
        ]);
    });

    Route::get('/mail', function () {
        return view('mailbox.show');
    })->name('mailbox.show');

    Route::post('/emails/store', [EmailController::class, 'store'])->name('emails.store');
    Route::post('/emails/reply', [EmailController::class, 'reply'])->name('emails.reply');

    Route::get('/quotes', function () {
        $quotes = CotizacionModel::get();
        return view('quotes.show', compact('quotes'));
    })->name('quotes.show');

    // Nueva ruta específica para React con nombre más claro
    Route::get('/cotizacion', function () {
        return view('quotes.react-show');
    })->name('quotes.react-test');

    // Ruta original con Livewire (disponible como backup)
    Route::get('/quotes-livewire', function () {
        $quotes = CotizacionModel::get();
        return view('quotes.show', compact('quotes'));
    })->name('quotes.livewire-show');

    // Nueva ruta específica para React con nombre más claro
    Route::get('/cotizacion-react', function () {
        $quotes = CotizacionModel::get();
        return view('quotes.react-show', compact('quotes'));
    })->name('quotes.react-specific');

    // Nueva ruta para la versión React de cotizaciones
    Route::get('/quotes-react', function () {
        $quotes = CotizacionModel::get();
        return view('quotes.react-show', compact('quotes'));
    })->name('quotes.react-show');

    Route::get('/llamadas', function (Request $request) {
        $groupCotizationId = $request->get('group_id');
        $cotizacionId = $request->get('cotizacion_id');
        
        return view('llamadas.index', compact('groupCotizationId', 'cotizacionId'));
    })->name('llamadas.index');

    Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('auth.logout');

    // Account management routes
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'show'])->name('account.show');
    Route::post('/account/profile', [App\Http\Controllers\AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/notifications', [App\Http\Controllers\AccountController::class, 'updateNotifications'])->name('account.notifications.update');
    Route::post('/account/preferences', [App\Http\Controllers\AccountController::class, 'updatePreferences'])->name('account.preferences.update');
    Route::delete('/account/profile-photo', [App\Http\Controllers\AccountController::class, 'removeProfilePhoto'])->name('account.profile-photo.remove');
});

// Ruta de diagnóstico sin middleware para aislar fallo en /quotes
Route::get('/quotes-debug', function () {
    \Log::info('Accediendo a /quotes-debug (ruta de diagnóstico)');
    try {
        $quotes = \App\Models\CotizacionModel::get();
        // Indicador para la vista (si se quiere mostrar banner debug)
        return view('quotes.show', [
            'quotes' => $quotes,
            'debug_mode' => true,
        ]);
    } catch (\Throwable $e) {
        \Log::error('Excepción en /quotes-debug: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        return response('Debug error: '.$e->getMessage(), 500);
    }
});

Route::group(['middleware' => 'guest'], function () {

    Route::get('/login', [LoginController::class, 'index'])->name('auth.login');
    Route::post('/login', [LoginController::class, 'store'])->name('auth.store');


    Route::get('/password-request', [PasswordRequestController::class, 'index'])->name('auth.passwordRequest');
    Route::post('/password-request', [PasswordRequestController::class, 'store'])->name('auth.passwordRequestStore');
    Route::get('/createUser', function () {
        $user = new User([
            'name' => 'prueba',
            'email' => 'prueba1@gmail.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $user->save();

        $user = new User([
            'name' => 'prueba2',
            'email' => 'prueba2@gmail.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $user->save();

        $user = new User([
            'name' => 'prueba3',
            'email' => 'prueba3@gmail.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $user->save();
    });
    });

    // Ruta de prueba mínima
    Route::get('/test-minimal', function () {
        return view('test-minimal');
    });

    // Ruta de prueba para quotes sin Livewire
    Route::get('/test-quotes', function () {
        return view('test-quotes');
    })->middleware('auth');

    // Ruta de prueba para Livewire simple
    Route::get('/test-livewire', function () {
        return view('test-livewire');
    })->middleware('auth');

Route::resource('transports', TransportController::class);

// User Panel Control

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::get('/assignable-roles', [UserController::class, 'assignableRoles']);
    Route::get('/me', [UserController::class, 'current']);
    Route::get('/potential-parents', [UserController::class, 'potentialParents']);

    // CHANNELS

    Route::get('/columns', [UserColumnController::class, 'index']);
    Route::post('/columns', [UserColumnController::class, 'store']);
    Route::put('/columns/{column}', [UserColumnController::class, 'update']);
    Route::delete('/columns/{column}', [UserColumnController::class, 'destroy']);
    Route::post('/columns/reorder', [UserColumnController::class, 'reorder']);
});

// Fingerprint simple para confirmar despliegue y versión de commit (no protegido)
Route::get('/fingerprint', function () {
    $hash = trim((@file_exists(base_path('.git/HEAD')) ? @shell_exec('git rev-parse --short HEAD') : 'no-git'));
    return response()->json([
        'app' => 'conalca',
        'timestamp' => now()->toDateTimeString(),
        'commit' => $hash,
        'php_version' => PHP_VERSION,
    ]);
});

Route::get('/groups-quotes', [GroupQuotationController::class, 'groupsQuotes']);
Route::patch('/groups-quotes/{id}/status', [GroupQuotationController::class, 'changeStatus']);
// Para aceptar cotización
Route::get('groups-quotes/accept/{id}', [GroupQuotationController::class, 'accept'])->name('quotes.accept');

// Para rechazar cotización
Route::get('groups-quotes/cancel/{id}', [GroupQuotationController::class, 'cancel'])->name('quotes.cancel');

// Para eliminar grupo de cotización
Route::delete('/groups-quotes/{id}', [GroupQuotationController::class, 'destroy'])
    ->middleware('auth')
    ->name('groups-quotes.destroy');

Route::get('cotizacion/grupo/{id}/responder', [CotizacionClienteController::class, 'responderVista'])
    ->name('cotizacion.publica.vista');
Route::post('cotizacion/grupo/{id}/responder', [CotizacionClienteController::class, 'guardarRespuestas'])
    ->name('cotizacion.publica.responder');




// Route::post('/solicitud/progreso', [SolicitudTransporteController::class, 'guardarParcial']);

// Pendings group quote
Route::get('groups/{group}/pendings', [PendingController::class, 'index']);
Route::post('pendings', [PendingController::class, 'store']);
Route::put('pendings/{id}', [PendingController::class, 'update']);
Route::delete('pendings/{id}', [PendingController::class, 'destroy']);


// Request view
    Route::middleware('auth')->group(function () {
        Route::get('/requests', function () {
            return view('requests.index');
        })->name('requests.show');
    });


    Route::middleware('auth')->group(function () {
        Route::prefix('solicitations')->group(function () {
            /* ────── CRUD de Solicitudes ────── */
            Route::get ('/'      , [SolicitationController::class, 'index']);
            Route::post('/'      , [SolicitationController::class, 'store']);
            Route::post('/pricing-route-request', [SolicitationController::class, 'requestPricingRoute']);
            Route::get ('/{id}'  , [SolicitationController::class, 'show']);
            Route::put ('/{id}'  , [SolicitationController::class, 'update']);

            /* ────── Acciones complementarias ────── */
            Route::post('/{id}/send'    , [SolicitationController::class, 'send']);
            Route::put ('/{id}/price'   , [SolicitationController::class, 'assignPrice']);
            Route::post('/{id}/note'    , [SolicitationController::class, 'addNote']);
            Route::post('/{id}/escalate', [SolicitationController::class, 'escalateToSuperAdmin']);

            /* ────── CHAT interno ────── */
            Route::prefix('{id}/messages')->group(function () {

                Route::get ('/{channel}', [MessageController::class, 'index'])
                    ->whereNumber('id')
                    ->where('channel', 'PRICING_COM|PRICING_SA');

                Route::post('/{channel}', [MessageController::class, 'store'])
                    ->whereNumber('id')
                    ->where('channel', 'PRICING_COM|PRICING_SA');
            });
        });
    });    

    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/packings', [PackingController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/mcp/search-products', [ProductController::class, 'search']);

    Route::post('/pricings-solutions',  [PricingController::class,'store']);
    Route::put ('/pricings-solutions/{id}', [PricingController::class,'update']);
    Route::get('/pricings-solutions/latest-by-route', [PricingController::class, 'latestByRoute']);
    Route::post('/pricing-suggestions', [PricingController::class, 'suggestVehicles']);
    Route::get('/pricing-rentability-stats', [PricingController::class, 'rentabilityStats']);
    Route::get('/pricing/vehicle-guide', [PricingController::class, 'vehicleCapacityGuide']);
    
    Route::get('/pricing-percentage-settings', [PricingController::class, 'percentageSettings']);
    // Panel Percentage
    // Route::middleware(['auth', 'role:SUPER ADMIN|JEFE COMERCIAL'])->group(function () {
    //     Route::get('/percentage-settings', [PercentageSettingController::class, 'index'])->name('percentage-settings.index');
    //     Route::put('/percentage-settings/{percentageSetting}', [PercentageSettingController::class, 'update'])->name('percentage-settings.update');
    // });
    Route::middleware(['auth', 'role:SUPER ADMIN|JEFE COMERCIAL'])
    ->group(function () {
        Route::get('/percentage-settings', [PercentageSettingController::class, 'index'])
            ->name('percentage-settings.index');
        Route::put('/percentage-settings/{percentageSetting}', [PercentageSettingController::class, 'update'])
            ->name('percentage-settings.update');
    });

    // Tara Settings
    Route::middleware(['auth', 'role:SUPER ADMIN|JEFE COMERCIAL|SAC'])
    ->group(function () {
        Route::get('/tara-settings', [TaraSettingController::class, 'index'])
            ->name('tara-settings.index');
        Route::put('/tara-settings/{taraSetting}', [TaraSettingController::class, 'update'])
            ->name('tara-settings.update');
    });

    // GOALS

    Route::get ('/goals'               , [GoalController::class, 'index']);
    Route::put('/goals/{goal}'         , [GoalController::class, 'update']);
    Route::get('/my-goal', [GoalController::class, 'myGoal']);
    Route::get ('/boss/notifications'  , [GoalController::class, 'notifications']);
    Route::post('/boss/notifications/{id}/read', [GoalController::class, 'markNotificationRead']);

    Route::middleware('auth')->group(function () {
        Route::get('/goal-management', function () {
            return view('goals.index');
        })->name('goals.index');
    });

    
    // Route::middleware('auth')->group(function () {
    // });
    Route::get('/quotes-news-alerts', function () {
        return view('cotizations.index');
    })->name('alertsnews.index');

    Route::prefix('cotizations')->group(function () {
        Route::get('/sac-groups', [SacCotizationController::class,'groups']);   // <—
        Route::patch('/group/{id}',[SacCotizationController::class,'updateGroupStatus']);

        Route::get   ('/{id}/notes',[CotizationNoteController::class,'index']);
        Route::post  ('/{id}/notes',[CotizationNoteController::class,'store']);

        Route::get('/transit-events',fn() => TransitEvent::all());
    });


    Route::post('/silog/crear-st', [SilogController::class, 'crearST']);


    Route::get ('/calls/{cotizacion}',  [CallStatusController::class,'show']);
    Route::post('/call-drivers',        [CallController::class,'callDrivers']); // botón React

    Route::get('calls/{cotizacionId}', [CallStatusController::class, 'show']);
    Route::post('calls/{cotizacionId}/select-driver', [CallStatusController::class, 'selectDriver']);
    
    Route::prefix('/solicitud')->group(function () {
        // ①  Guardado parcial (ya existe)
        Route::post('/progreso', [SolicitudTransporteController::class,'guardarParcial'])
            ->name('solicitud.progreso');

        // ②  Cargar data (para editar una solicitud existente)
        Route::get('/{cotizacion}/data', function ($cotizacion) {
            return \App\Models\SolicitudTransporte::with([
                'detalle','cargue','contenedor','internacional',
                'acompanamiento','condiciones_factura','entrega'
            ])->where('cotizacion_model_id',$cotizacion)->first();
        })->name('solicitud.data');

         /* ③  NUEVA RUTA – Prefill desde la Cotización                  */
        Route::get('/{cotizacion}/prefill',
            [SolicitudTransporteController::class,'prefill'])
            ->name('solicitud.prefill');
            
        /* ④  NUEVA RUTA – Prefill desde Grupo de Cotización             */
        Route::get('/group/{group_id}/prefill',
            [SolicitudTransporteController::class,'prefillFromGroup'])
            ->name('solicitud.group.prefill');
    });

    Route::prefix('catalog')->controller(CatalogController::class)->group(function () {
        Route::get('/clientes',          'clientes');
        Route::get('/ciudades',          'ciudades');
        Route::get('/vendedores',        'vendedores');
        Route::get('/productos',         'productos');
        Route::get('/empaques',          'empaques');
        Route::get('/vehiculos/clases',  'clasesVehiculo');
        Route::get('/vehiculos/carrocerias','carrocerias');
        Route::get('/terceros',          'terceros');
    });

    // Módulo de Conductores (Solo SUPER ADMIN y SAC)
    Route::middleware(['auth'])->group(function () {
        Route::get('/conductores', [App\Http\Controllers\ConductorController::class, 'index'])->name('conductores.index');
        
        // Rutas API para conductores
        Route::prefix('api/conductores')->group(function () {
            Route::get('/', [App\Http\Controllers\ConductorController::class, 'getConductores']);
            Route::post('/', [App\Http\Controllers\ConductorController::class, 'store']);
            Route::get('/stats', [App\Http\Controllers\ConductorController::class, 'getStats']);
            Route::get('/vehicle-classes', [App\Http\Controllers\ConductorController::class, 'getVehicleClasses']);
            Route::get('/{id}', [App\Http\Controllers\ConductorController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\ConductorController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\ConductorController::class, 'destroy']);
            Route::patch('/{id}/status', [App\Http\Controllers\ConductorController::class, 'changeStatus']);
        });
    });

    // Módulo de Vehículos (Solo SUPER ADMIN y SAC)
    Route::middleware(['auth'])->group(function () {
        Route::get('/vehiculos', [App\Http\Controllers\VehiculoController::class, 'index'])->name('vehiculos.index');
        Route::get('/vehiculos/sincronizar', [App\Http\Controllers\VehiculoController::class, 'sincronizar'])->name('vehiculos.sincronizar');
        
        // Rutas para relaciones de vehículos
        Route::post('/vehiculos/relaciones', [App\Http\Controllers\VehiculoController::class, 'agregarRelacion'])->name('vehiculos.agregar-relacion');
        Route::delete('/vehiculos/relaciones/{id}', [App\Http\Controllers\VehiculoController::class, 'eliminarRelacion'])->name('vehiculos.eliminar-relacion');
        Route::get('/vehiculos/{id}/relaciones', [App\Http\Controllers\VehiculoController::class, 'obtenerRelaciones'])->name('vehiculos.obtener-relaciones');
        
        // Búsqueda de vehículos por ciudad (tiempo real)
        Route::get('/vehiculos/ciudades', [App\Http\Controllers\VehiculoController::class, 'getCiudades'])->name('vehiculos.ciudades');
        Route::post('/vehiculos/buscar-ciudad', [App\Http\Controllers\VehiculoController::class, 'buscarVehiculosCiudad'])->name('vehiculos.buscar-ciudad');
        
        // Cambiar modo Arcángel (producción/desarrollo)
        Route::post('/vehiculos/cambiar-modo-arcangel', [App\Http\Controllers\VehiculoController::class, 'cambiarModoArcangel'])->name('vehiculos.cambiar-modo-arcangel');
        Route::get('/vehiculos/modo-arcangel-actual', [App\Http\Controllers\VehiculoController::class, 'obtenerModoActual'])->name('vehiculos.modo-arcangel-actual');
    });

// ═══════════════════════════════════════════════════════════════
// Chat AI Routes - Rutas específicas sin middleware CSRF
// ═══════════════════════════════════════════════════════════════
Route::post('/chat/assistant', [App\Http\Controllers\Api\ChatController::class, 'chatWithFunctions'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('chat.assistant');
Route::get('/test-drivers-modal', function () { return view('test-drivers-modal'); });
