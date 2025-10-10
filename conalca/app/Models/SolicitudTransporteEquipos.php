<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteEquipos extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_transporte_id',
        'articulo_1',
        'cantidad_1',
        'articulo_2',
        'cantidad_2',
        'articulo_3',
        'cantidad_3',
        'articulo_4',
        'cantidad_4',
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
