<?php

namespace App\Http\Controllers;

use App\Models\CotizacionModel;
use App\Models\Email;
use App\Models\GroupCotization;
use App\Models\SolicitudTransporte;
use App\Models\SolicitudTransporteAcompanamiento;
use App\Models\SolicitudTransporteCargue;
use App\Models\SolicitudTransporteCondicionesFactura;
use App\Models\SolicitudTransporteContenedor;
use App\Models\SolicitudTransporteDetalle;
use App\Models\SolicitudTransporteEntrega;
use App\Models\SolicitudTransporteEquipos;
use App\Models\SolicitudTransporteInternacional;
use App\Services\SilogtranService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SilogtranTransform as ST;

use App\Helpers\SilogtranHelper as SH;

class SolicitudTransporteController extends Controller
{
    public function guardarParcial(Request $request, SilogtranService $silog)
    {
        Log::info('[ST] → guardarParcial() INICIO', ['request_data' => $request->all()]);

        try {
            /* -----------------------------------------------------------------
            | 1. Datos básicos recibidos
            |-----------------------------------------------------------------*/
            $step         = $request->input('step');              // ej.  step_3
            $cotizacionId = $request->input('CotizacionModelId'); // id de la cotización
            $data         = $request->except(['step', 'CotizacionModelId']);

            Log::info('[ST] Datos procesados:', [
                'step' => $step,
                'cotizacionId' => $cotizacionId,
                'data_keys' => array_keys($data),
                'data_count' => count($data)
            ]);

            /* -----------------------------------------------------------------
            | 1.1. Validación de datos críticos
            |-----------------------------------------------------------------*/
            if (empty($step)) {
                Log::error('[ST] ERROR: Step vacío');
                return response()->json([
                    'status' => false,
                    'error' => 'Step requerido'
                ], 400);
            }

            if (empty($cotizacionId)) {
                Log::error('[ST] ERROR: CotizacionModelId vacío');
                return response()->json([
                    'status' => false,
                    'error' => 'CotizacionModelId requerido'
                ], 400);
            }

            /* -----------------------------------------------------------------
            | 2. Crear / obtener solicitud padre
            |-----------------------------------------------------------------*/
            Log::info('[ST] Intentando crear/obtener solicitud con cotizacionId:', ['cotizacion_id' => $cotizacionId]);
            
            $solicitud = SolicitudTransporte::firstOrCreate(
                ['cotizacion_model_id' => $cotizacionId],
                [
                    'estado'          => 'incompleta',
                    'steps_completed' => json_encode([]),
                    'client_id'       => CotizacionModel::find($cotizacionId)?->client_id,
                ]
            );
            Log::info('[ST] Solicitud padre creada/encontrada', ['id' => $solicitud->id]);

        /* -----------------------------------------------------------------
        | 3. Guardar la información correspondiente al paso
        |-----------------------------------------------------------------*/
        switch ($step) {

            /* ------------   STEP 1  (Encabezado)   ------------ */
            case 'step_1':
                $solicitud->fill([
                    'tipo_viaje'             => $data['tipo_viaje']            ?? null,
                    'moneda'                 => $data['moneda']                ?? null,
                    'fuente_solicitud'       => $data['fuente_solicitud']      ?? null,
                    'condicion_despacho'     => $data['condicion_despacho']    ?? null,
                    'condicion_facturacion'  => $data['condicion_facturacion'] ?? null,
                    'ciudad_facturacion'     => $data['ciudad_facturacion']    ?? null,
                    'vendedor'               => $data['vendedor']              ?? null,
                    'tipo_operacion'         => $data['tipo_operacion']        ?? null,

                    /* nuevos obligatorios */
                    'centro_costo_despacho'  => $data['centro_costo_despacho'] ?? null,
                    'cliente_codigo'         => $data['cliente_codigo']        ?? null,
                ]);
                $solicitud->save();
                break;

            /* ------------   STEP 2  (Detalle)   -------------- */
            case 'step_2':
                SolicitudTransporteDetalle::updateOrCreate(
                    ['solicitud_transporte_id' => $solicitud->id],
                    [...$data, 'cotizacion_model_id' => $cotizacionId]
                );
                break;

            /* ------------   STEP 3  (Cargue)   --------------- */
            case 'step_3':
                SolicitudTransporteCargue::updateOrCreate(
                    ['solicitud_transporte_id' => $solicitud->id],
                    $data
                );
                break;

            /* ------------   STEP 4  (Contenedor) ------------- */
            case 'step_4':
                SolicitudTransporteContenedor::updateOrCreate(
                    ['solicitud_transporte_id' => $solicitud->id],
                    $data
                );
                break;

            /* ------------   STEP 5  (Internacional) ---------- */
            case 'step_5':
                SolicitudTransporteInternacional::updateOrCreate(
                    ['solicitud_transporte_id' => $solicitud->id],
                    $data
                );
                break;

            /* ------------   STEP 6  (Acompañamiento) --------- */
            case 'step_6':
                SolicitudTransporteAcompanamiento::updateOrCreate(
                    ['solicitud_transporte_id' => $solicitud->id],
                    $data
                );
                break;

            default:
                Log::warning('[ST] Paso no reconocido', ['step' => $step]);
        }

        /* -----------------------------------------------------------------
        | 4.  Marcar el paso como completado
        |-----------------------------------------------------------------*/
        $steps_completed = $solicitud->steps_completed
                            ? json_decode($solicitud->steps_completed, true)
                            : [];

        $steps_completed[$step] = true;
        $solicitud->steps_completed = json_encode($steps_completed);

        $PASOS_OBLIGATORIOS = ['step_1','step_2','step_3','step_4','step_5','step_6'];

        /* -----------------------------------------------------------------
        | 5.  Si están los 6 pasos, enviar a Silogtran
        |-----------------------------------------------------------------*/
        $completados = array_intersect_key(
            array_flip($PASOS_OBLIGATORIOS),
            $steps_completed
        );

        if (count($completados) === count($PASOS_OBLIGATORIOS)) {

            try {
                Log::info('[ST] 6 pasos completos. Enviando a Silogtran …');
                $payload   = $this->armarPayloadSilog($solicitud);
                Log::info('[ST] Payload a Silogtran', ['payload' => $payload]);

                $respuesta = $silog->crearSolicitudTransporte($payload);
                Log::info('[ST] Respuesta Silogtran', ['respuesta' => $respuesta]);

                /* -------- Validar respuesta -------- */
                $ok = ($respuesta['success'] ?? false) &&
                    (($respuesta['data']['1']['result'] ?? false) === true);

                if ($ok) {
                    /* Éxito */
                    $solicitud->estado           = 'completada';
                    $solicitud->silogtran_status = $respuesta['data']['1']['validacion'] ?? 'OK';
                } else {
                    /* Error → marcar estado error y devolver a paso 6 */
                    unset($steps_completed['step_6']);
                    $solicitud->estado = 'error';

                    // NUEVO: obtenemos el primer mensaje de error real
                    $solicitud->silogtran_status =
                        $this->primerErrorValidacion($respuesta)
                        ?? ($respuesta['msg'] ?? 'Error Silogtran');
                }
            } catch (\Exception $silogException) {
                /* Error al conectar con Silogtran - guardamos localmente */
                Log::error('[ST] Error al conectar con Silogtran:', [
                    'message' => $silogException->getMessage(),
                    'trace' => $silogException->getTraceAsString()
                ]);
                
                $solicitud->estado = 'pendiente_sincronizacion';
                $solicitud->silogtran_status = 'Error de conexión con Silogtran. Guardado localmente. Intente sincronizar más tarde.';
            }

        } else {
            /* Aún faltan pasos */
            $solicitud->estado = count($steps_completed) ? 'en_proceso' : 'incompleta';
        }

        $solicitud->save();
        Log::info('[ST] Solicitud guardada/parcial',
                ['id' => $solicitud->id, 'estado' => $solicitud->estado]);

        /* -----------------------------------------------------------------
        | 6.  Respuesta al front
        |-----------------------------------------------------------------*/
        return response()->json([
            'status'          => true,
            'solicitud_id'    => $solicitud->id,
            'estado'          => $solicitud->estado,
            'steps_completed' => $steps_completed,
            'silogtran'       => $solicitud->silogtran_status ?? null,
            'advertencia'     => $solicitud->estado === 'error'
                                ? $solicitud->silogtran_status
                                : null,
        ]);

        } catch (\Exception $e) {
            Log::error('[ST] ERROR CRÍTICO en guardarParcial:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'error' => 'Error interno del servidor',
                'details' => config('app.debug') ? $e->getMessage() : 'Error procesando solicitud'
            ], 500);
        }
    }

    /* =========================================================
    *  Extrae el primer mensaje "validacion" del array devuelto
    *  por Silogtran.  Devuelve null si no existe.
    * =========================================================*/
    private function primerErrorValidacion(array $respuesta): ?string
    {
        if (!isset($respuesta['data']) || !is_array($respuesta['data'])) {
            return null;
        }

        foreach ($respuesta['data'] as $bloque) {        // '1', '2', …
            if (is_array($bloque) && isset($bloque[0]['validacion'])) {
                return $bloque[0]['validacion'];
            }
        }

        return null;
    }

    /* -----------------------------------------------------------------
    |  Construye el payload que se envía al WS de Silogtran
    |-----------------------------------------------------------------*/
    private function armarPayloadSilog(SolicitudTransporte $solicitud): array
    {
        /* ------------ Alias cortos de las relaciones ------------ */
        $d  = $solicitud->detalle;          // Paso 2
        $c  = $solicitud->cargue;           // Paso 3
        $co = $solicitud->contenedor;       // Paso 4
        $i  = $solicitud->internacional;    // Paso 5
        $ac = $solicitud->acompanamiento;   // Paso 6

        /* ----------- DETALLE  (toma lo que haya y normaliza los valores) ----------- */
        $detalle = [[
            'ciudad_codigo_origen'            => SH::normalizeCityCode($d->origen ?? 11001000),
            'ciudad_codigo_destino'           => SH::normalizeCityCode($d->destino ?? 11001000),
            'ciudad_codigo_intermedia'        => null,

            'itesoltra_cantidad'              => SH::normalizeNumeric($d->cantidad_mercancia ?? 1),
            'itesoltra_peso'                  => SH::normalizeNumeric($d->peso ?? 1),
            'producto_codigo'                 => SH::normalizeNumeric($d->producto ?? 1),
            'empaque_codigo'                  => SH::normalizeNumeric($d->empaque ?? 1),
            'itesoltra_vehiculos'             => SH::normalizeNumeric($d->cantidad_vehiculos ?? 1),
            'claveh_codigo'                   => SH::normalizeNumeric($d->clase_vehiculo ?? 1),
            'carroceria_codigo'               => SH::normalizeNumeric($d->carroceria ?? 1),
            'itesoltra_modelo'                => SH::normalizeNumeric($d->minimo_modelo ?? 2010),

            'tipfle_codigo'                   => SH::normalizeFreightType($d->tipo_flete ?? 'CARGA SUELTA'),
            'itesoltra_flete'                 => SH::normalizeNumeric($d->flete_conductor ?? 1),
            'itesoltra_fleteministerio'       => SH::normalizeNumeric($d->flete_ministerio ?? 1),
            'tiptar_codigo'                   => $d->tipo_tarifa ?? 'GENERAL',
            'itesoltra_valortarifa'           => SH::normalizeNumeric($d->tarifa_cliente ?? 1),
            'itesoltra_valormercancia'        => SH::normalizeNumeric($d->valor_mercancia ?? 1),

            'itesoltra_tipodocumento'         => $d->tipo_documento ?? 'DO',
            'itesoltra_numerodocumento'       => SH::normalizeNumeric($d->numero_documento ?? 1),
            'itesoltra_restricciones'         => SH::normalizeNumeric($d->restricciones_cliente ?? 1),

            'itesoltra_cargueporcuentade'     => $d->cargue_cuenta_de ?? 'EMPRESA',
            'itesoltra_descargueporcuentade'  => $d->descargue_cuenta_de ?? 'DESTINATARIO',
            'itesoltra_seguroporcuentade'     => $d->seguro_cuenta_de ?? 'CLIENTE',

            'itesoltra_descripcionmercancia'  => $d->descripcion_mercancia ?? 'Mercancía General',
            'itesoltra_escolta'               => SH::normalizeYesNo($d->servicio_escolta ?? 'NO'),
            'itesoltra_kitseguridad'          => SH::normalizeYesNo($d->kit_seguridad ?? 'NO'),
            'itesoltra_kitcinchas'            => SH::normalizeYesNo($d->kit_cinchas ?? 'NO'),
            'itesoltra_guiaacompanamiento'    => SH::normalizeYesNo($d->guia_acompanamiento ?? 'NO'),
            'itesoltra_observacion'           => SH::normalizeNumeric($d->observacion_detalle ?? 1),

            'subcliente_codigo'               => SH::normalizeNumeric($d->sub_cliente ?? 1),

            'itesoltra_guiadespacho'          => SH::normalizeNumeric($d->guia_despacho ?? 1),
            'itesoltra_remisionviaje'         => SH::normalizeNumeric($d->remision_viaje ?? 1),
            'itesoltra_numeropedido'          => SH::normalizeNumeric($d->numero_pedido ?? 1),
            'itesoltra_notaentrega'           => SH::normalizeNumeric($d->nota_entrega ?? 1),
            'itesoltra_planillatransporte'    => SH::normalizeNumeric($d->planilla_entrega ?? 1),
            'itesoltra_facturamercancia'      => SH::normalizeNumeric($d->factura_mercancia ?? 1),

            'tipo_remesa_rndc'                => $d->tipo_remesa_rndc ?? 3,

            /* ----------  sub-bloques ---------- */
            'detalle_cargue'                  => ST::cargue      ($c?->toArray()  ?? []),
            'detalle_contenedor'              => ST::contenedor  ($co?->toArray() ?? []),
            'detalle_internacional'           => ST::internacional($i?->toArray() ?? []),
            'detalle_condicion_factura'       => ST::condicionFactura([]),
            'detalle_condicion_cumplido'      => ST::cumplido([]),
            'detalle_acompanamiento'          => ST::acompanamiento($ac?->toArray() ?? []),
        ]];

        /* ---------------- ENCABEZADO (normalizado) ---------------- */
        $encabezado = [
            'empresa_codigo'                     => 11,
            'tipvia_codigo'                      => SH::normalizeTripType($solicitud->tipo_viaje ?? 'NACIONAL'),
            'cencos_codigo_despacho'             => SH::normalizeCostCenter($solicitud->centro_costo_despacho ?? 'CONALCA BOGOTA'),
            'cliente_codigo'                     => SH::normalizeClientCode($solicitud->cliente_codigo ?? 1846),
            'moneda_codigo'                      => SH::normalizeCurrency($solicitud->moneda ?? 'PESOS'),
            'soltra_medio'                       => SH::normalizeRequestSource($solicitud->fuente_solicitud ?? 'PAGINA WEB'),
            'soltra_condicionesdespacho'         => $solicitud->condicion_despacho ?? 'PRUEBA WS',
            'soltra_condicionesfacturacion'      => $solicitud->condicion_facturacion ?? 'PRUEBA WS',
            'ciudad_codigo_facturacion'          => SH::normalizeCityCode($solicitud->ciudad_facturacion ?? 11001000),
            'vendedor_codigo'                    => $solicitud->vendedor,
            'soltra_mandatario'                  => 1,
            'soltra_tipooperacion'               => SH::normalizeOperationType($solicitud->tipo_operacion ?? 'DISTRIBUCION'),

            /* campos no obligatorios que dejamos fijos */
            'soltra_observacion'                 => 'PRUEBAS WS',
            'soltra_observacion_remesa'          => '',
            'soltra_tipoimagen'                  => '',
            'soltra_fechasolicitud'              => now()->format('Y-m-d'),
            'soltra_instruccionesservicio'       => '',
            'solser_codigo'                      => '',
            'soltra_mostrarvehiculodigitalizado' => 'NO',
            'soltra_mostrarconductordigitalizado'=> 'NO',
            'usuario_codigoautorizado'           => '',
            'soltra_recomendado'                 => '',
        ];

        return [
            'solicitudtransporte' => [[
                'encabezado' => $encabezado,
                'detalle'    => $detalle,
            ]],
        ];
    }

    public function prefill($cotizacionId)
    {
        $cot = CotizacionModel::with('client')->findOrFail($cotizacionId);

        $fechaHora = Carbon::parse($cot->fecha_hora_descargue_cargue);

        /*  Mapeo  Cotización → Wizard  */
        $data = [
            /* Paso 1 */
            'cliente_codigo'           => $cot->client?->codigo,
            'cliente_nombre'           => $cot->client?->cliente,          
            // 'ciudad_facturacion'       => $cot->client?->ciudad_codigo_radicacion,
            'vendedor'         => $cot->groupCotization?->user?->documento,
            /* Paso 2 */
            'origen'                => $cot->ciudad_origen_dane,
            'destino'               => $cot->ciudad_destino_dane,
            'cantidad_mercancia'    => $cot->cantidad,
            'peso'                  => $cot->peso_mercancia,
            'producto'              => $cot->tipo_producto,
            'empaque'               => $cot->tipo_embajale,
            'cantidad_vehiculos'    => $cot->cantidad_vh,
            'tipo_operacion'        => $cot->operation_type,
            // 'clase_vehiculo'        => $cot->vehiculo_requerido,
            // 'carroceria'            => $cot->tipo_carroceria,
            'tipo_flete'            => strtoupper($cot->consolidado_expreso ?: $cot->fcl_lcl),
            'valor_mercancia'       => $cot->valor_declarado ?: $cot->valor,
            'descripcion_mercancia' => $cot->tipo_mercancia,
            'cargue_cuenta_de'      => strtoupper($cot->descargue_cargue) ?: 'CLIENTE',
            /* Paso 3 */
            'fecha_cargue'          => $fechaHora?->toDateString(),
            'hora_cargue'           => $fechaHora?->format('H:i'),
            /* Paso 4 */
            'contenedor'            => $cot->fcl_lcl === 'FCL' ? 'SI' : 'NO',
            /* Paso 5 */
            'modalidad_internacional'=> strtoupper($cot->tipo),
            /* Paso 6 */
            // 'vehiculo_acom'         => $cot->cantidad_vh,
            'itesoltra_vehiculoacompanamiento'  => $cot->itesoltra_vehiculoacompanamiento,
            'tipaco_codigo'                     => $cot->tipaco_codigo,
            'itesoltra_acompanamientocuentade'  => $cot->itesoltra_acompanamientocuentade,
            'itesoltra_acompanamientovalor'     => $cot->itesoltra_acompanamientovalor,
        ];

        return response()->json($data);
    }

    /**
     * Prefill wizard desde un grupo de cotización
     */
    public function prefillFromGroup($groupId)
    {
        try {
            Log::info('[PREFILL] Iniciando prefill para grupo: ' . $groupId);
            
            $grupo = GroupCotization::with(['cotizaciones', 'client', 'user'])->findOrFail($groupId);
            
            Log::info('[PREFILL] Grupo encontrado:', [
                'id' => $grupo->id,
                'client_id' => $grupo->client_id,
                'operation_type' => $grupo->operation_type,
                'cotizaciones_count' => $grupo->cotizaciones->count()
            ]);
            
            // Tomar datos de la primera cotización como base
            $primera_cotizacion = $grupo->cotizaciones->first();
            
            if (!$primera_cotizacion) {
                Log::error('[PREFILL] Grupo sin cotizaciones');
                return response()->json(['error' => 'Grupo sin cotizaciones'], 404);
            }

            Log::info('[PREFILL] Primera cotización:', [
                'id' => $primera_cotizacion->id,
                'origen' => $primera_cotizacion->ciudad_origen,
                'destino' => $primera_cotizacion->ciudad_destino
            ]);

            /*  Mapeo  Grupo de Cotización → Wizard  */
            $data = [
                /* Paso 1 - Datos básicos */
                'cliente_codigo'           => $grupo->client?->codigo,
                'cliente_nombre'           => $grupo->client?->cliente,
                'tipo_operacion'           => $grupo->operation_type ?: 'DISTRIBUCION',
                'vendedor'                 => $grupo->user?->documento,
                
                /* Paso 2 - Detalle del servicio */
                'origen'                   => $primera_cotizacion->ciudad_origen,
                'destino'                  => $primera_cotizacion->ciudad_destino,
                'peso'                     => $primera_cotizacion->peso,
                'valor_mercancia'          => $primera_cotizacion->valor_declarado,
                'descripcion_mercancia'    => $primera_cotizacion->tipo_mercancia,
                'vehiculo_requerido'       => $primera_cotizacion->vehiculo_requerido,
                
                /* Campos específicos para import/export */
                'tipo_carga'               => $primera_cotizacion->tipo_carga,
                'clasificacion_contenedor' => $primera_cotizacion->clasificacion_contenedor,
                'tipo_contenedor'          => $primera_cotizacion->tipo_contenedor,
                'toneladas'                => $primera_cotizacion->toneladas,
                'incluye_tara'             => $primera_cotizacion->incluye_tara,
                'acompanamiento_seguridad' => $primera_cotizacion->acompanamiento_seguridad,
                
                /* Paso 5 - Modalidad internacional */
                'modalidad_internacional'  => strtoupper($grupo->type ?: ''),
                
                /* Información del flujo */
                'operation_flow' => [
                    'type' => $grupo->operation_type,
                    'isImportExport' => in_array($grupo->operation_type, ['IMPORTACION', 'EXPORTACION'])
                ],
                
                /* Metadatos */
                'source_group_id'          => $groupId,
                'total_routes'             => $grupo->cotizaciones->count(),
            ];

            Log::info('[PREFILL] Datos mapeados exitosamente, total campos: ' . count($data));
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('[PREFILL] Error en prefillFromGroup:', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
