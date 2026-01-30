<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $cities = City::orderBy('ciudad_nombre')->get([
            'ciudad_codigo',
            'ciudad_nombre',
            'ciudad_codigodane',
            'departamento_nombre',
            'pais_nombre',
        ]);

        // 🆕 Ordenar para que las ciudades principales aparezcan primero
        $sorted = $cities->sortBy(function($city) {
            $nombreCiudad = mb_strtoupper(explode(' - ', $city->ciudad_nombre)[0] ?? '');
            $nombreDepto = mb_strtoupper($city->departamento_nombre ?? '');
            
            // Si esta ciudad tiene un departamento principal definido
            if (isset(self::$departamentosPrincipales[$nombreCiudad])) {
                $deptoPrincipal = self::$departamentosPrincipales[$nombreCiudad];
                // Si es la ciudad principal, ponerla primero (prioridad 0)
                if (stripos($nombreDepto, $deptoPrincipal) !== false || stripos($city->ciudad_nombre, $deptoPrincipal) !== false) {
                    return '0_' . $city->ciudad_nombre;
                }
                // Si no es la principal, ponerla después (prioridad 1)
                return '1_' . $city->ciudad_nombre;
            }
            
            // Ciudades sin duplicados, orden normal
            return '0_' . $city->ciudad_nombre;
        });

        return response()->json([
            'success' => true,
            'data' => $sorted->values()
        ]);
    }
}
