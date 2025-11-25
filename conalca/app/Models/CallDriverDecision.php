<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDriverDecision extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'cotizacion_model_id',
        'driver_id', 
        'decision'
    ];

    protected $casts = [
        'decision' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function driver()
    {
        return $this->belongsTo(VehicleOwnerHolderDriver::class, 'driver_id');
    }

    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_model_id');
    }

    /**
     * Scope para obtener solo decisiones aceptadas
     */
    public function scopeAccepted($query)
    {
        return $query->where('decision', true);
    }

    /**
     * Scope para obtener solo decisiones rechazadas
     */
    public function scopeRejected($query)
    {
        return $query->where('decision', false);
    }
}
