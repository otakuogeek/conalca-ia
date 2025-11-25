<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteAcompanamiento extends Model
{
    use HasFactory;

    protected $table = 'solicitud_transporte_acom';

    protected $fillable = [
        'solicitud_transporte_id', // HAVE TO DELETE
        'vehiculo_acom', // HAVE TO DELETE
        'tipo_vehiculo_acom', // HAVE TO DELETE
        'acompanamiento_cuenta_acom', // HAVE TO DELETE
        'valor_acompanante_acom', // HAVE TO DELETE
        'itesoltra_vehiculoacompanamiento',
        'tipaco_codigo',
        'itesoltra_acompanamientocuentade',
        'itesoltra_acompanamientovalor',
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
