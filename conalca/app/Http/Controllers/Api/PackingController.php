<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Packing;

class PackingController extends Controller
{
    public function index(Request $request)
    {
        // Cachear embalajes por 24h — catálogo estático
        $packings = Cache::remember('catalog_packings', 60 * 60 * 24, function () {
            return Packing::orderBy('nome')->get([
                'id',
                'codigo_ministerio',
                'nome',
            ])->map(function($packing) {
                return [
                    'id' => $packing->id,
                    'Codigo' => $packing->codigo_ministerio,
                    'Nombre' => $packing->nome,
                    'nome' => $packing->nome,
                    'codigo_ministerio' => $packing->codigo_ministerio
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $packings,
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
