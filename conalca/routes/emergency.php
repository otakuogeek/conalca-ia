<?php

use Illuminate\Support\Facades\Route;
use App\Models\ConversationSession;

// Ruta de emergencia para detener runs atascados
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
        
        // Forzar status a completed
        $metadata['last_run_status'] = 'force_stopped';
        $session->metadata = json_encode($metadata);
        $session->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Run detenido forzadamente',
            'old_run_id' => $oldRunId,
            'old_status' => $oldStatus,
            'new_status' => 'force_stopped'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});
