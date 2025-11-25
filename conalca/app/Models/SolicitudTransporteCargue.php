<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteCargue extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_transporte_id',
        'fecha_cargue',
        'hora_cargue',
        'remitente',
        'direccion_cargue',
        'observacion_cargue',
        'contacto',
        'destinario',
        'promesa_servicio',
        'documento_transporte',
        'manifiesto_cliente',
        'remesa_cliente',
        'remision_cliente',
        'codigo_entrega',
        'email',
        'remitente_codigo',
        'destinatario_codigo',
        'promesa_servicio_hora',
        'promesaservicio_hora',
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
}
