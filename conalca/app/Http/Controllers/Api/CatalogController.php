<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\City;
use App\Models\Seller;
use App\Models\User;
use App\Models\Product;
use App\Models\Packing;
use App\Models\VehicleClass;
use App\Models\Bodywork;


class CatalogController extends Controller
{

    public function clientes(Request $r) {
        $q = $r->input('q', '');

        return Client::where('cliente', 'like', "%$q%")
                    ->orWhere('codigo', 'like', "%$q%")
                    ->orWhere('documento', 'like', "%$q%")
                    ->limit(15)
                    ->get(['id', 'codigo', 'documento', 'cliente']);
    }

    /**
     * 🆕 Ciudades principales que deben tener prioridad cuando hay ambigüedad
     */
    private static $ciudadesPrincipales = [
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
        'CUCUTA' => 'NORTE DE SANTANDER',
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

    public function ciudades(Request $r) {
        $q = $r->input('q', '');
        $qUpper = mb_strtoupper(trim($q));

        $results = City::where('ciudad_nombre', 'like', "%$q%")
                ->orWhere('ciudad_codigodane', 'like', "%$q%")
                ->limit(30)
                ->get(['ciudad_codigo', 'ciudad_nombre', 'ciudad_codigodane']);
        
        // 🆕 Si hay múltiples resultados, priorizar la ciudad principal
        if ($results->count() > 1 && isset(self::$ciudadesPrincipales[$qUpper])) {
            $deptoPrincipal = self::$ciudadesPrincipales[$qUpper];
            
            // Ordenar: primero la ciudad principal, luego las demás
            $sorted = $results->sortBy(function($city) use ($deptoPrincipal) {
                // La ciudad que contiene el departamento principal va primero
                if (stripos($city->ciudad_nombre, $deptoPrincipal) !== false) {
                    return 0;
                }
                return 1;
            });
            
            return $sorted->values()->take(15);
        }
        
        return $results->take(15);
    }

    public function vendedores(Request $r) {
        $q = $r->input('q', '');

        // Buscar usuarios con rol 'ASISTENTE COMERCIAL' que tengan documento
        $usuarios_comerciales = User::role('ASISTENTE COMERCIAL')
                    ->whereNotNull('documento')
                    ->where('documento', '!=', '')
                    ->where(function($query) use ($q) {
                        $query->where('name', 'like', "%$q%")
                              ->orWhere('documento', 'like', "%$q%")
                              ->orWhere('email', 'like', "%$q%");
                    })
                    ->limit(15)
                    ->get(['id', 'documento', 'name', 'email'])
                    ->map(function($user) {
                        return [
                            'Codigo' => $user->id,
                            'Documento' => $user->documento, 
                            'Nombre' => $user->name
                        ];
                    });

        // Si no hay comerciales o la búsqueda está vacía, también incluir la tabla sellers como fallback
        if ($usuarios_comerciales->isEmpty() || empty($q)) {
            $sellers_fallback = Seller::where('Nombre', 'like', "%$q%")
                        ->orWhere('Codigo', 'like', "%$q%")
                        ->orWhere('Documento', 'like', "%$q%")
                        ->limit(5)
                        ->get(['Codigo', 'Documento', 'Nombre']);
            
            // Combinar resultados (comerciales primero)
            return $usuarios_comerciales->concat($sellers_fallback)->take(15);
        }

        return $usuarios_comerciales;
    }

    public function productos(Request $r) {
        $q = $r->input('q', '');

        return Product::where('producto_nombre', 'like', "%$q%")
                    ->orWhere('producto_codigo', 'like', "%$q%")
                    ->orWhere('producto_codigo_ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get([
                        'producto_codigo',
                        'producto_codigo_ministerio',
                        'producto_nombre',
                        'tippro_nombre',
                        'producto_fechacreacion',
                        'natcar_nombre',
                        'usuario_nombre'
                    ]);
    }

    public function empaques(Request $r) {
        $q = $r->input('q', '');

        // 🔧 FIX: Usar nombres de columnas reales de tb_empaque (portugués)
        $result = Packing::where('nome', 'like', "%$q%")
                    ->orWhere('codigo_ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get(['id', 'codigo_ministerio', 'nome'])
                    ->map(function($empaque) {
                        return [
                            'Codigo' => $empaque->id, // ID como código principal
                            'Codigo Ministerio' => $empaque->codigo_ministerio,
                            'Nombre' => $empaque->nome
                        ];
                    });

        \Log::info('[EMPAQUES] Búsqueda con q="'.$q.'", resultados: ' . $result->count());
        
        return $result;
    }

    public function clasesVehiculo(Request $r) {
        $q = $r->input('q', '');

        return VehicleClass::where('Nombre', 'like', "%$q%")
                        ->orWhere('Codigo', 'like', "%$q%")
                        ->limit(15)
                        ->get(['Codigo', 'Nombre']);
    }

    public function carrocerias(Request $r) {
        $q = $r->input('q', '');

        return Bodywork::where('Nombre', 'like', "%$q%")
                    ->orWhere('Codigo', 'like', "%$q%")
                    ->orWhere('Codigo Ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get(['Codigo', 'Codigo Ministerio', 'Nombre']);
    }

}
