<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'driver_id',
        'phone_number',
        // 'twilio_call_sid', // REMOVED: Twilio service disabled
        'status',
        'attempted_at',
        'responded_at',
        'response'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function call() {
        return $this->belongsTo(Call::class);
    }

    public function driver() {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
