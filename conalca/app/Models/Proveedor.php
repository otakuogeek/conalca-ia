<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $primaryKey = 'tercero_codigo';
    public $incrementing = false;

    protected $fillable = [
        'tercero_codigo',
        'nombre',
        'tipdoc_nombre',
        'tercero_documento',
    ];
}
