<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_sid',
        'cotizacion_id',
        'driver_id',
        'status',
        'turn_count',
        'final_decision',
        'metadata',
        'started_at',
        'ended_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime'
    ];

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class, 'session_id');
    }

    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\VehicleOwnerHolderDriver::class, 'driver_id');
    }

    public function getDurationAttribute()
    {
        if (!$this->ended_at) {
            return null;
        }
        
        return $this->ended_at->diffInSeconds($this->started_at);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
