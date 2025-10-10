<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    /* The `protected ` property in the `Appointment` model is used to specify which
    attributes are mass assignable. Mass assignment allows you to set multiple attributes of a model
    at once by passing an array of values. */
    protected $fillable = [
        'title',
        'client',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'appointment_type',
        'notes',
        'notify',
        'item_type',
        'priority',
        'completed'
    ];

}
