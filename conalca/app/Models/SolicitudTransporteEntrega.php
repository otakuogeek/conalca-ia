<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteEntrega extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_transporte_id',
        'nombre_cliente_ent',
        'direccion_entrega_ent',
        'numero_documento_ent',
        'cantidad_ent',
        'empaque_ent',
        'f_12_ent',
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
