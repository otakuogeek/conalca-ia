<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\City;
use App\Models\Seller;
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

    public function ciudades(Request $r) {
        $q = $r->input('q', '');

        return City::where('ciudad_nombre', 'like', "%$q%")
                // ->orWhere('ciudad_codigo', 'like', "%$q%")
                ->orWhere('ciudad_codigodane', 'like', "%$q%")
                ->limit(15)
                ->get(['ciudad_codigo', 'ciudad_nombre', 'ciudad_codigodane']);
    }

    public function vendedores(Request $r) {
        $q = $r->input('q', '');

        return Seller::where('Nombre', 'like', "%$q%")
                    ->orWhere('Codigo', 'like', "%$q%")
                    ->orWhere('Documento', 'like', "%$q%")
                    ->limit(15)
                    ->get(['Codigo', 'Documento', 'Nombre']);
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

        return Packing::where('Nombre', 'like', "%$q%")
                    ->orWhere('Codigo', 'like', "%$q%")
                    ->orWhere('Codigo Ministerio', 'like', "%$q%")
                    ->limit(15)
                    ->get(['Codigo', 'Codigo Ministerio', 'Nombre']);
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
