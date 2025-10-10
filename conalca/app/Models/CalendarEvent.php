<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'client',
        'date_start',
        'hour_start',
        'date_end',
        'hour_end',
        'description',
        'notify',
        'status',
    ];
}
