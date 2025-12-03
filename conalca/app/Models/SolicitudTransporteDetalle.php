<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_transporte_id',
        'cotizacion_model_id',
        'origen',
        'destino',
        'ciudad_intermedia',
        'cantidad_mercancia',
        'peso',
        'peso_despachado',
        'producto',
        'empaque',
        'cantidad_vehiculos',
        'cantidad_vehiculos_despachados',
        'clase_vehiculo',
        'carroceria',
        'minimo_modelo',
        'tipo_flete',
        'flete_conductor',
        'flete_ministerio',
        'tipo_tarifa',
        'tarifa_cliente',
        'tipo_documento',
        'numero_documento',
        'valor_mercancia',
        'restricciones_cliente',
        'cargue_cuenta_de',
        'descargue_cuenta_de',
        'seguro_cuenta_de',  
        'descripcion_mercancia',
        'servicio_escolta',
        'kit_seguridad',
        'kit_cinchas',
        'guia_acompanamiento',
        'observacion_detalle',
        'sub_cliente',
        'guia_despacho',
        'numero_viaje',
        'numero_pedido',
        'nota_entrega',
        'planilla_entrega',
        'numero_factura',
        'modelo',
        'qty',
        't_cbm',
        'order_no',
        'o_c_cliente',
        'bodega',
        'fecha_expiracion',
        'net_amt_txn',
        'prec_unit',
        'remark',
        'addr',
        'appoint_date',
        'tipo_vehiculo',
        'item',
        'load',
        'tipo_remesa_rndc',
    ];

    /**
     * Get the solicitud that owns the SolicitudTransporteAcompanamiento
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudTransporte::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_model_id');
    }

    public function cotizacionModel()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_model_id');
    }

}
