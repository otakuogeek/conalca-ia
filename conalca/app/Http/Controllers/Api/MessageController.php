<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Solicitation;
use App\Models\Message;
use App\Models\User;          // ← IMPORTACIÓN CORRECTA

class MessageController extends Controller
{
    /* ────── LISTAR MENSAJES ────── */
    public function index(int $id, string $channel)
    {
        $solicitation = Solicitation::findOrFail($id);
        $user         = request()->user();

        abort_if(!$this->allowed($user, $solicitation, $channel), 403);

        $messages = Message::where('solicitation_id', $id)
                           ->where('channel', $channel)
                           ->with('user:id,name')
                           ->orderBy('created_at')
                           ->get();

        return response()->json($messages);
    }

    /* ────── CREAR MENSAJE ────── */
    public function store(Request $request, int $id, string $channel)
    {
        $request->validate([
            'content' => 'required|string|max:400'
        ]);

        $solicitation = Solicitation::findOrFail($id);
        $user         = $request->user();

        abort_if(!$this->allowed($user, $solicitation, $channel), 403);

        $message = Message::create([
            'solicitation_id' => $id,
            'user_id'         => $user->id,
            'content'         => $request->content,
            'channel'         => $channel,
        ]);

        return response()->json($message->load('user'), 201);
    }

    /* ────── REGLA DE VISIBILIDAD ────── */
    private function allowed(User $user, Solicitation $sol, string $channel): bool
    {
        return match ($channel) {
            'PRICING_SA'  => $user->hasAnyRole(['PRICING', 'SUPER ADMIN']),
            'PRICING_COM' => ($user->id === $sol->created_by) || $user->hasRole('PRICING'),
            default       => false,
        };
    }
}