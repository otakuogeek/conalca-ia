<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Packing;

class PackingController extends Controller
{
    public function index(Request $request)
    {
        $packings = Packing::orderBy('Nombre')->get([
            'Codigo',
            'Nombre',
        ]);

        return response()->json([
            'success' => true,
            'data' => $packings,
        ]);
    }
}
