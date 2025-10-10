<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteContenedor extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_transporte_id',
        'tipo_carga_cont',
        'tamano_contenedor',
        'sitio_entrega_cont',
        'cantidad_cont',
        'peso_contenedor',
        'tipo_contenedor',
        'fecha_entrega_cont',
        'contenedor',
        'numero_cont',
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
