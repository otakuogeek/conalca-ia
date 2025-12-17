<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PercentageSetting extends Model
{
    use HasFactory;

    protected $table = 'percentage_settings';

    protected $fillable = [
        'min_percentage',
        'avg_percentage',
        'max_percentage',
        'use_custom_percentages',
    ];
}
