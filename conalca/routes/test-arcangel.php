<?php

use Illuminate\Support\Facades\Route;

Route::post('/test-arcangel-buscar', function(\Illuminate\Http\Request $request) {
    try {
        $arcangelService = app(\App\Services\ArcangelService::class);
        
        // Simular búsqueda
        $ciudad = 'FUNZA';
        $vehiculos = $arcangelService->getVehiculosCercanos($ciudad);
        
        return response()->json([
            'success' => true,
            'ciudad' => $ciudad,
            'total' => count($vehiculos['vehiculos'] ?? []),
            'vehiculos' => array_slice($vehiculos['vehiculos'] ?? [], 0, 5),
            'request_data' => $request->all(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
