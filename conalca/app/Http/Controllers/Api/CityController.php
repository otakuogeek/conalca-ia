<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\City;

class CityController extends Controller
{
    /**
     * 🆕 Departamentos principales para ciudades con nombres duplicados
     * Cuando hay múltiples ciudades con el mismo nombre, estas son las principales
     */
    private static $departamentosPrincipales = [
        'CARTAGENA' => 'BOLIVAR',
        'ARMENIA' => 'QUINDIO',
        'CALI' => 'VALLE',
        'MEDELLIN' => 'ANTIOQUIA',
        'BOGOTA' => 'CUNDINAMARCA',
        'BARRANQUILLA' => 'ATLANTICO',
        'BUCARAMANGA' => 'SANTANDER',
        'PEREIRA' => 'RISARALDA',
        'MANIZALES' => 'CALDAS',
        'IBAGUE' => 'TOLIMA',
        'CUCUTA' => 'NORTE',
        'SANTA MARTA' => 'MAGDALENA',
        'VILLAVICENCIO' => 'META',
        'PASTO' => 'NARINO',
        'NEIVA' => 'HUILA',
        'MONTERIA' => 'CORDOBA',
        'VALLEDUPAR' => 'CESAR',
        'TUNJA' => 'BOYACA',
        'POPAYAN' => 'CAUCA',
        'SINCELEJO' => 'SUCRE',
        'RIOHACHA' => 'GUAJIRA',
        'QUIBDO' => 'CHOCO',
        'FLORENCIA' => 'CAQUETA',
        'YOPAL' => 'CASANARE',
        'BUENAVENTURA' => 'VALLE',
    ];

    public function index(Request $request)
    {
        // Cachear ciudades por 24h — catálogo estático
        $sorted = Cache::remember('catalog_cities_sorted', 60 * 60 * 24, function () {
            $cities = City::orderBy('ciudad_nombre')->get([
                'ciudad_codigo',
                'ciudad_nombre',
                'ciudad_codigodane',
                'departamento_nombre',
                'pais_nombre',
            ]);

            return $cities->sortBy(function($city) {
                $nombreCiudad = mb_strtoupper(explode(' - ', $city->ciudad_nombre)[0] ?? '');
                $nombreDepto = mb_strtoupper($city->departamento_nombre ?? '');
                
                if (isset(self::$departamentosPrincipales[$nombreCiudad])) {
                    $deptoPrincipal = self::$departamentosPrincipales[$nombreCiudad];
                    if (stripos($nombreDepto, $deptoPrincipal) !== false || stripos($city->ciudad_nombre, $deptoPrincipal) !== false) {
                        return '0_' . $city->ciudad_nombre;
                    }
                    return '1_' . $city->ciudad_nombre;
                }
                
                return '0_' . $city->ciudad_nombre;
            })->values();
        });

        return response()->json([
            'success' => true,
            'data' => $sorted
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
