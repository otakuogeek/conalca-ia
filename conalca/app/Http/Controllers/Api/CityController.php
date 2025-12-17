<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{
    public function index(Request $request)
    {
        // You can add filters or pagination if needed
        $cities = City::orderBy('ciudad_nombre')->get([
            'ciudad_codigo',
            'ciudad_nombre',
            'ciudad_codigodane',
            'departamento_nombre',
            'pais_nombre',
        ]);

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
}
