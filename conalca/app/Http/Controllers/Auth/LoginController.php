<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    //login the user
    public function store(Request $request)
    {
        // Logging detallado para debugging
        Log::info('=== LOGIN ATTEMPT STARTED ===', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'csrf_token_sent' => $request->input('_token'),
            'csrf_token_session' => $request->session()->token(),
            'cookies' => $request->cookies->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all()
        ]);

        //validate the request
        $validation = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        Log::info('Validation passed', ['validation_result' => $validation]);

        try {
            // Test database connection before attempting login
            Log::info('Testing database connection...');
            DB::connection()->getPdo();
            Log::info('Database connection successful');

            Log::info('Attempting authentication...');
            //if the credentials are invalid
            if (!auth()->attempt($request->only('email', 'password'), $request->remember)) {
                Log::warning('Authentication failed - invalid credentials', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return back()->with('message', 'Credenciales Incorrectas');
            }

            Log::info('Authentication successful', [
                'user_id' => auth()->user()->id,
                'email' => auth()->user()->email,
                'name' => auth()->user()->name,
                'ip' => $request->ip(),
                'session_after_auth' => $request->session()->getId()
            ]);

            Log::info('Redirecting to dashboard...');
            
            // Para debugging: verificar si es una petición AJAX
            if ($request->expectsJson() || $request->ajax()) {
                Log::info('Request expects JSON, returning JSON response');
                return response()->json([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'redirect' => route('dashboard.show'),
                    'user' => auth()->user()->only(['id', 'name', 'email'])
                ]);
            }
            
            //if the credentials are valid - redirect to dashboard without parameters
            return redirect()->route('dashboard.show');

        } catch (QueryException $e) {
            Log::error('Database query error during login: ' . $e->getMessage());

            // Check if it's a connection error
            if ($e->getCode() == 2006 || str_contains($e->getMessage(), 'MySQL server has gone away')) {
                return back()->with('database_error', true)->withInput($request->only('email'));
            }

            return back()->with('message', 'Error interno del servidor. Por favor intenta nuevamente.');
        } catch (\Exception $e) {
            Log::error('Unexpected error during login: ' . $e->getMessage());
            return back()->with('message', 'Error interno del servidor. Por favor intenta nuevamente.');
        }
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return redirect()->route('auth.login');
    }
}
