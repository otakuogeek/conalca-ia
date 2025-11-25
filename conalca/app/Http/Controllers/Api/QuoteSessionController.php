<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class QuoteSessionController extends Controller
{
    /**
     * Guarda el group_id en la sesión para que Livewire pueda acceder a él
     * Esto resuelve el problema de duplicación entre React y Livewire
     */
    public function setGroupInSession(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer|exists:group_cotizations,id'
        ]);

        $groupId = $request->input('group_id');
        
        // Guardar en sesión para que Livewire lo lea
        session()->put('current_group_id', $groupId);
        
        Log::info('✅ Group ID guardado en sesión desde React', [
            'group_id' => $groupId,
            'session_id' => session()->getId(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group ID almacenado en sesión correctamente',
            'group_id' => $groupId
        ]);
    }
}
