<?php
/* app/Services/SilogtranTransform.php */
namespace App\Services;
use Carbon\Carbon;

class SilogtranTransform
{
    // OBLIGATORY
    public static function cargue(array $c = []): array
    {
        /* — normalizador de hora   HH:MM — */
        $hora = function (?string $h): string {
            if (!$h)                         return '00:00';
            if (preg_match('/^\d{2}:\d{2}$/',       $h)) return $h;
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $h)) return substr($h,0,5);
            return '00:00';
        };

        return [
            /* fechas en yyyy-mm-dd */
            'itesoltra_fechacargue'         => self::fecha($c['fecha_cargue']       ?? null),
            'itesoltra_promesaservicio'     => self::fecha($c['promesa_servicio']   ?? null),

            /* horas en HH:MM - IMPORTANTE: sin guion bajo extra en horacargue */
            'itesoltra_horacargue'          => $hora($c['hora_cargue']              ?? null),
            'itesoltra_promesaserviciohora' => $hora($c['promesa_servicio_hora']    ?? null),

            /* datos mínimos o quemados */
            'remitente_codigo'              => 1,
            'destinatario_codigo'           => 1,
            'itesoltra_observacioncargue'   => $c['observacion_cargue'] ?? 1,
            'itesoltra_documentotransporte' => $c['documento_transporte'] ?? 1,

            /* ---------  LOS 5 CAMPOS QUE FALTABAN  --------- */
            'itesoltra_manifiestocliente'   => 1,
            'itesoltra_remesacliente'       => 1,
            'itesoltra_remesioncliente'     => 1,
            'itesoltra_codigoentrega'       => 1,
            'itesoltra_email'               => 1,

            /* contacto quemado (si no lo envías) */
            'itesoltra_contacto'            => 2345678,
        ];
    }

    private static function fecha(?string $valor): string
    {
        try {
            return $valor
                   ? Carbon::parse($valor)->format('Y-m-d')
                   : Carbon::now()->format('Y-m-d');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m-d');
        }
    }

    /* ----------------------------- CONTENEDOR ---------------------------- */
    public static function contenedor(array $c = []): array
    {
        return [
            'itesoltra_conttam'              => 10,
            'lugar_codigo'                   => 1,
            'cantidad_contenedor'            => 1,  // API requiere nombre completo, no abreviado
            'contenedor_fechaentrega'        => self::fecha($c['fecha_entrega_cont'] ?? null),
            'itesoltra_contenedornumero'     => 1,
            'itesoltra_devolucioncontenedor' => 'NO',
        ];
    }

    /* ---------------------------- INTERNACIONAL -------------------------- */
    public static function internacional(array $i = []): array
    {
        return [
            'itesoltra_modalidad'                 => 'OTM',
            'itesoltra_modalidadtipoviaje'        => 'BOGOTA',
            'itesoltra_modalidadnumero'           => 1,
            'itesoltra_fechallegadadta'           => Carbon::now()->format('Y-m-d'),
            'aduana_codigo'                       => 'TULCAN',
            'itesoltra_nombrecliente'             => 1,
            'itesoltra_nombreexportador'          => 1,
            'itesoltra_datosagenteduana'          => 1,
            'itesoltra_datosbodegaingresa'        => 1,
            'ingresa_ciudad_codigo'               => 11001000,
            'itesoltra_ingresatipo'               => 'IMPORTACION',
            'itesoltra_datospagoalamcenamientobodega'=> 1,
            'pago_ciudad_codigo'                  => 11001000,
            'itesoltra_pagotipo'                  => 'IMPORTACION',
            'itesoltra_nombreimportador'          => 1,
            'itesoltra_descripcionbrevemercancia' => 1,
            'itesoltra_cantidadpesomercancia'     => 1,
            'itesoltra_fechavencimientomodalidad' => Carbon::now()->format('Y-m-d'),
            'itesoltra_pasofrontera'              => 'NO',
        ];
    }

    /* ----------------------- CONDICIÓN FACTURA --------------------------- */
    public static function condicionFactura(array $c = []): array
    {
        // Mapeo de campos BD → API Silogtran
        // BD: condicion_factura → API: itesoltra_condicionfacturacion
        // BD: factura_remesa_hija → API: itesoltra_facturaremesahija
        // BD: opcion_factura_remesa → API: itesoltra_solounafactura
        return [
            'itesoltra_condicionfacturacion' => $c['condicion_factura'] ?? 'CON EMISION DE DESPACHO',
            'itesoltra_facturaremesahija'    => $c['factura_remesa_hija'] ?? 'NO',
            'itesoltra_solounafactura'       => $c['opcion_factura_remesa'] ?? 'NO',
        ];
    }

    /* ---------------------------- CUMPLIDO ------------------------------- */
    public static function cumplido(array $c = []): array
    {
        // Mapeo de campos BD → API Silogtran
        // BD: opcion_condicion_cumplida → API: itesoltra_condicioncumplido
        return [
            'itesoltra_condicioncumplido' => $c['opcion_condicion_cumplida'] ?? 'COMODATO, REMESA',
        ];
    }

    // /* ------------------------- ACOMPAÑAMIENTO ---------------------------- */
    // public static function acompanamiento(array $a = []): array
    // {
    //     return [
    //         'itesoltra_vehiculoacompanamiento' => 1,
    //         'tipaco_codigo'                    => 'MOTORIZADO',
    //         'itesoltra_acompanamientocuentade' => 'CLIENTE',
    //         'itesoltra_acompanamientovalor'    => 1,
    //     ];
    // }

    /* ------------------------------------------------------------------
    | ACOMPAÑAMIENTO
    |------------------------------------------------------------------*/
    public static function acompanamiento(array $a = []): array
    {
        return [
            /* Nº vehículos de acompañamiento - debe ser 1 como mínimo según Silogtran */
            'itesoltra_vehiculoacompanamiento'  => $a['itesoltra_vehiculoacompanamiento']  ?? 1,

            /* MOTORIZADO | VEHICULAR | CABINA */
            'tipaco_codigo'                     => $a['tipaco_codigo']                     ?? '',

            /* CLIENTE | EMPRESA  */
            'itesoltra_acompanamientocuentade'  => $a['itesoltra_acompanamientocuentade']  ?? '',

            /* Valor COP - debe ser 1 como mínimo según Silogtran */
            'itesoltra_acompanamientovalor'     => $a['itesoltra_acompanamientovalor']     ?? 1,
        ];
    }
    
}