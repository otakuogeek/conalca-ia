<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'packing';
    
    // Especificar la clave primaria
    protected $primaryKey = 'Codigo';
    
    // Si la clave primaria no es un entero autoincremental
    public $incrementing = false;
    
    // Tipo de la clave primaria
    protected $keyType = 'string';
    
    // Deshabilitar timestamps si la tabla no los tiene
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'Codigo',
        'Nombre',
        'Codigo Ministerio',
        'Usuario',
        'Fecha Creacion',
        'Fecha Modificacion'
    ];
}
