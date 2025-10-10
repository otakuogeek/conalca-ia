<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransporteInternacional extends Model
{
    use HasFactory;

    protected $table = 'solicitud_transporte_int';

    protected $fillable = [
        'solicitud_transporte_id',
        'modalidad_internacional',
        'tipo_viaje_int',
        'numero_documento_int',
        'fecha_llegada_int',
        'aduana_int',
        'nombre_cliente',
        'nombre_exportador',
        'datos_agente_aduana',
        'datos_bodega_ingresa',
        'ciudad_int',
        'tipo_operacion_int',
        'quien_paga_almacenamiento',
        'ciudad_otra',
        'tipo_otro',
        'nombre_importador',
        'descripcion_mercancia',
        'cantidad_peso_mercancia',
        'fecha_vencimiento_modalidad',
        'paso_frontera',
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
