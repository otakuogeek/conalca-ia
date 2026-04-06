<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudTransporteCosto extends Model
{
    protected $table = 'solicitud_transporte_costos';

    protected $fillable = [
        'solicitud_transporte_id',
        'tipvalrem_codigo',
        'tipvalrem_nombre',
        'valor_unitario',
        'valor_costo_unitario',
        'facturable',
        'observacion_costo',
        'aplica_flete',
        'proveedor_codigo',
        'proveedor_nombre',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudTransporte::class, 'solicitud_transporte_id');
    }

    public function tipoValorRemesa()
    {
        return $this->belongsTo(TipoValorRemesa::class, 'tipvalrem_codigo', 'tipvalrem_codigo');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_codigo', 'tercero_codigo');
    }
}
