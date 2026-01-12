<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Packing;

class PackingController extends Controller
{
    public function index(Request $request)
    {
        $packings = Packing::orderBy('nome')->get([
            'id',
            'codigo_ministerio',
            'nome',
        ])->map(function($packing) {
            return [
                'id' => $packing->id,
                'Codigo' => $packing->codigo_ministerio, // Compatibilidad con frontend
                'Nombre' => $packing->nome,              // Compatibilidad con frontend
                'nome' => $packing->nome,                // Original
                'codigo_ministerio' => $packing->codigo_ministerio
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $packings,
        ]);
    }
}
