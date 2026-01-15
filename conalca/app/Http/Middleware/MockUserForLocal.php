<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class MockUserForLocal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en entorno local
        if (app()->environment('local')) {
            // Si no hay usuario logueado, intentar loguear al primero
            if (!Auth::check()) {
                $user = User::first();
                if ($user) {
                    Auth::login($user);
                    // Opcional: Compartir variable global si fuera necesario, pero Auth::login basta
                }
            }
        }

        return $next($request);
    }
}
