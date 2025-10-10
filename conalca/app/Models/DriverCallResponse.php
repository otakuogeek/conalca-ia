<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverCallResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id',
        'driver_id',
        'driver_name',
        'driver_phone',
        'vehicle_type',
        'vehicle_plate',
        'call_status',
        'response_status',
        'response_time',
        // 'twilio_call_sid', // REMOVED: Twilio service disabled
        'elevenlabs_conversation_id',
        'elevenlabs_sip_call_id',
        'call_duration',
        'notes',
        'is_selected',
        'retry_count',
        'original_call_id'
    ];

    protected $casts = [
        'response_time' => 'datetime',
        'is_selected' => 'boolean',
    ];

    /**
     * Relación con la cotización
     */
    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_id');
    }

    /**
     * Relación con el conductor (vehículo)
     */
    public function driver()
    {
        return $this->belongsTo(VehicleOwnerHolderDriver::class, 'driver_id');
    }

    /**
     * Scope para conductores que aceptaron
     */
    public function scopeAccepted($query)
    {
        return $query->where('response_status', 'accepted');
    }

    /**
     * Scope para conductores que rechazaron
     */
    public function scopeRejected($query)
    {
        return $query->where('response_status', 'rejected');
    }

    /**
     * Scope para conductores pendientes
     */
    public function scopePending($query)
    {
        return $query->where('response_status', 'pending');
    }

    /**
     * Scope para conductor seleccionado
     */
    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }
}