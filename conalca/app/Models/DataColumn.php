<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_target',
        'column',
        'value',
    ];
}
