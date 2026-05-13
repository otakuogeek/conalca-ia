<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolicitudTransporte extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_viaje',
        'moneda',
        'fuente_solicitud',
        'observacion',
        'condicion_despacho',
        'condicion_facturacion',
        'observacion_remesa',
        'tipo_imagen',
        'recomendacion_trafico',
        'ciudad_facturacion',
        'vendedor',
        'cliente_final',
        'tipo_operacion',
        'fecha_solicitud',
        'instrucciones_servicio',
        'maersk_numero_viaje',
        'solicitud_servicio',
        'mostrar_digitalizados_vehiculos',
        'mostrar_digitalizados_conductor',
        'usuario_autorizado',
        'empresa',
        'usuario',
        'silogtran_status',
        'client_id',
        'origen',
        'destino',
        'cotizacion_model_id',
        'steps_completed',
        'estado',
        'cliente_codigo',
        'centro_costo_despacho',
    ];

    /**
     * Get the client that owns the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the acompanamiento associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function acompanamiento(): HasOne
    {
        return $this->hasOne(SolicitudTransporteAcompanamiento::class);
    }

    /**
     * Get the cargue associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cargue(): HasOne
    {
        return $this->hasOne(SolicitudTransporteCargue::class);
    }

    /**
     * Get the condiciones_factura associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function condiciones_factura(): HasOne
    {
        return $this->hasOne(SolicitudTransporteCondicionesFactura::class);
    }

    /**
     * Get the contenedor associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contenedor(): HasOne
    {
        return $this->hasOne(SolicitudTransporteContenedor::class);
    }

    /**
     * Get the detalle associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function detalle(): HasOne
    {
        return $this->hasOne(SolicitudTransporteDetalle::class);
    }

    /**
     * Get the entrega associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entrega(): HasOne
    {
        return $this->hasOne(SolicitudTransporteEntrega::class);
    }

    /**
     * Get the equipos associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function equipos(): HasOne
    {
        return $this->hasOne(SolicitudTransporteEquipos::class);
    }

    /**
     * Get the internacional associated with the SolicitudTransporte
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function internacional(): HasOne
    {
        return $this->hasOne(SolicitudTransporteInternacional::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_model_id');
    }

    public function costos(): HasMany
    {
        return $this->hasMany(SolicitudTransporteCosto::class);
    }
}
