<?php

use Illuminate\Support\Facades\Route;

// Ruta temporal para probar CSRF
Route::get('/test-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_started' => session()->isStarted(),
        'app_key' => config('app.key') ? 'Set' : 'Not set'
    ]);
});