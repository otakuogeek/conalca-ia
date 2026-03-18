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

    /**
     * Consultar clientes en Silogtran (API externa).
     * GET /api/silog/clientes?documento=900894351&calificacion=A
     */
    public function consultarCliente(Request $req, SilogtranService $silog)
    {
        $documento    = $req->input('documento');
        $calificacion = $req->input('calificacion');

        if (!$documento && !$calificacion) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'msg'     => 'Debe ingresar al menos un filtro (documento o calificacion)',
            ], 422);
        }

        // Validar calificación si se envía
        if ($calificacion && !in_array(strtoupper($calificacion), ['A', 'B', 'C', 'D'])) {
            return response()->json([
                'success' => false,
                'data'    => [],
                'msg'     => 'La calificación debe ser A, B, C o D',
            ], 422);
        }

        try {
            $result = $silog->consultarCliente($documento, $calificacion);
            return response()->json($result);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[SilogController] consultarCliente error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data'    => [],
                'msg'     => 'Error al consultar clientes en Silogtran: ' . $e->getMessage(),
            ], 500);
        }
    }
}
