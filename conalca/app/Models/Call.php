<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id', 
        'total_drivers', 
        'calls_made', 
        'calls_accepted', 
        'calls_not_answered', 
        'status'
    ];

    public function drivers() {
        return $this->hasMany(CallDriver::class);
    }
}
