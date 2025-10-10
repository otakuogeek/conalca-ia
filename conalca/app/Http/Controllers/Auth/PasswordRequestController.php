<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordRequestController extends Controller
{
    public function index() {
        return view('auth.passwordRequest');
	}

    public function store(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);

        return redirect()->route('auth.login');
    }
}
