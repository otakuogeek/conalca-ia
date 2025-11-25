<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteCondicionesFactura extends Model
{
    use HasFactory;

    protected $table = 'solicitud_transporte_con';


    protected $fillable = [
        'solicitud_transporte_id',
        'condicion_factura',
        'factura_remesa_hija',
        'opcion_factura_remesa',
        'opcion_condicion_cumplida',
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
