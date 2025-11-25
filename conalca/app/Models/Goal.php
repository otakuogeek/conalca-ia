<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_id',
        'boss_id',
        'year',
        'month',
        'target_amount',
        'achieved_amount',
        'status',
    ];

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function boss()
    {
        return $this->belongsTo(User::class, 'boss_id');
    }
}
