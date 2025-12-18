<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $cities = City::where('estado_nombre', 'ACTIVO')
            ->orderBy('municipio_nombre')
            ->get([
                'ciudad_codigodane',
                'municipio_nombre',
                'departamento_nombre',
                'ciudad_nombre',
            ]);

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
}
