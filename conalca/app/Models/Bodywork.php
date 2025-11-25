<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodywork extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'bodywork';

    // Configuración de clave primaria personalizada
    protected $primaryKey = 'Codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    // Campos asignables masivamente
    protected $fillable = [
        'Codigo',
        'Nombre',
        'Codigo Ministerio',
        'Usuario',
        'Fecha Creacion',
        'Fecha Modificacion',
    ];

    // Campos protegidos (si aplica)
    protected $guarded = [];

    // Casts defensivos para trazabilidad temporal
    protected $casts = [
        'Fecha Creacion' => 'datetime',
        'Fecha Modificacion' => 'datetime',
    ];
}
