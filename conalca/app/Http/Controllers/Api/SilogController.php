<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SilogtranService;

class SilogController extends Controller
{
    
    // te devuelve string
    // $token = app(Silogtran::class)->token();   

    // GET THE CLIENT
    // $clientes = $silog->call('servicio.DatosTranslidher.cliente',
                        //  ['cliente'=>1884],'get')->json(); 
    
    // CREATE SENDER
    // $resp = $silog->call('servicio.Remitente.crear',
    //       ['remitentes'=>$arrayRemitentes]);

    //  SET PRE CLIENT
    // $crear = $silog->call('servicio.Precliente.setPrecliente', $request->all());

    // uploadImagen
    //     $response = Http::withToken($api->token())
    //    ->attach('file', fopen($request->file('file')->path(),'r'), $request->file('file')->getClientOriginalName())
    //    ->post($api->base.'/index.php?api=servicio.GestionImagenes.cargarImagen',[
    //         'modulo'=>'solicitudtransporte',
    //         'codigo'=>68,
    //         'documento'=>2
    //    ]);

    public function crearST(Request $req, SilogtranService $silog)
    {
        $respuesta = $silog->call(
            'servicio.Solicitudtransporte.crearST',
            $req->all(),          // o construyes tú el array
            'post'
        );

        return $respuesta->json();
    }

    // TEST SECOND OPTION IF THE FIRST DON'T WORK
    public function nuevaST(Request $r, SilogtranService $api) {
        $res = $api->call('servicio.Solicitudtransporte.crearST',
                            $r->all());
        return $res->json();
    }
}
