<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleClass extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'vehicle_class';
    
    // Especificar la clave primaria
    protected $primaryKey = 'Codigo';
    
    // Si la clave primaria no es un entero autoincremental
    public $incrementing = false;
    
    // Tipo de la clave primaria
    protected $keyType = 'int';
    
    // Deshabilitar timestamps si la tabla no los tiene
    public $timestamps = false;

    // Campos asignables masivamente
    protected $fillable = [
        'Codigo',
        'Nombre',
        'Capacidad 1',
        'Peso',
        'Configuracion',
        'Pais',
        'Usuario',
        'Fecha Creacion',
        'Fecha Modificacion',
        'nomcoti',
    ];

    // Si hay campos sensibles, se pueden proteger aquí
    protected $guarded = [];

    // Casts defensivos (si normalizas fechas)
    protected $casts = [
        'Fecha Creacion' => 'string',
        'Fecha Modificacion' => 'string',
    ];
}
