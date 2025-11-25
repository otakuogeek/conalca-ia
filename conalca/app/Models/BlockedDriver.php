<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'driver_name',
        'driver_phone_number',
        'type_vehicle',
    ];
}
