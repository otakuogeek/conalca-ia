<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'cities';
    
    // Especificar la clave primaria
    protected $primaryKey = 'ciudad_codigo';
    
    // Deshabilitar timestamps si la tabla no los tiene
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'ciudad_codigo',
        'ciudad_nombre',
        'ciudad_codigodane',
        'municipio_nombre',
        'departamento_nombre',
        'pais_nombre',
        'zonciu_nombre',
        'estado_nombre',
    ];

}
