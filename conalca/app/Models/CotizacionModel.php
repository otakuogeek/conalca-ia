<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CotizacionModel extends Model
{
    use HasFactory;

    /**
     * Atributos que se deben agregar a la serialización JSON
     */
    protected $appends = ['producto_label'];

    protected $fillable = [
        'client_id',
        'user_id',
        'pricing_id',
        'porcentaje',
        'ciudad_origen',
        'ciudad_destino',
        'ciudad_origen_dane',
        'ciudad_destino_dane',
        'peso_mercancia',
        'cantidad',
        'tipo_embajale',
        'dimensiones_exactas',
        'registro_fotografico',
        'planos',
        'tipo_producto',
        'temperatura_mercancia',
        'humedad',
        'vehiculo_requerido',
        'vehiculo_filtrado',
        'regimen_nacionalizado',
        'agente_aduanas',
        'descargue_cargue',
        'consolidado_expreso',
        'fcl_lcl',
        'sitio_devolucion_contenedor',
        'numero_documento_bl',
        'fecha_hora_descargue_cargue',
        'cantidad_vh',
        'un',
        'ruta',
        'frecuencia',
        'esquema_seguridad',
        'tipo_carroceria',
        'valor',
        'valor_declarado',
        'tipo_mercancia',
        'ventanas_horarios_recibidos',
        'seguro',
        'silogtran_status',
        'tipo',
        'operation_type',
        'active',
        'group_cotization_id',
        'selected_driver_id',
        'itesoltra_vehiculoacompanamiento',
        'tipaco_codigo',
        'itesoltra_acompanamientocuentade',
        'itesoltra_acompanamientovalor',
    ];

    /**
     * Get the pricing that owns the CotizacionModel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    // public function pricing(): BelongsTo
    // {
    //     return $this->belongsTo(Pricing::class);
    // }

    /**
     * Get the client that owns the CotizacionModel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function pricing()
    {
        return $this->belongsTo(Pricing::class, 'pricing_id');
    }

    public function solicitud(): HasOne
    {
        return $this->hasOne(SolicitudTransporte::class, 'cotizacion_model_id');
    }

    public function groupCotization() {
        return $this->belongsTo(GroupCotization::class, 'group_cotization_id');
    }

    public function notes()
    {
        return $this->hasMany(CotizationNote::class, 'cotization_model_id');
    }

    public function drivers()
    {
        return $this->belongsToMany(
            VehicleOwnerHolderDriver::class,
            'call_driver_decisions',
            'cotizacion_model_id',
            'driver_id'
        )->withPivot(['decision'])->withTimestamps();
    }

    public function selectedDriver()
    {
        return $this->belongsTo(VehicleOwnerHolderDriver::class,'selected_driver_id');
    }

    public function decisions()
    {
        return $this->hasMany(\App\Models\CallDriverDecision::class,
                            'cotizacion_model_id');
    }

    /**
     * Relación con las llamadas registradas (tabla legacy)
     */
    public function llamadas()
    {
        return $this->hasMany(Llamada::class, 'id_cotizacion');
    }

    /**
     * Relación con conductores llamados (nueva tabla)
     */
    public function llamadasConductores()
    {
        return $this->hasMany(LlamadaConductor::class, 'cotizacion_id');
    }

    /**
     * Relación con respuestas de llamadas de ElevenLabs
     */
    public function driverCallResponses()
    {
        return $this->hasMany(DriverCallResponse::class, 'cotizacion_id');
    }

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Product::class, 'tipo_producto', 'producto_codigo');
    }

    /**
     * Accessor para obtener el nombre del producto
     */
    public function getProductoLabelAttribute()
    {
        return $this->producto?->producto_nombre;
    }

}
