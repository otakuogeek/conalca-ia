<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;
    
    protected $table = 'sellers';
    
    // Especificar la clave primaria
    protected $primaryKey = 'Codigo';
    
    // Deshabilitar timestamps si la tabla no los tiene
    public $timestamps = false;
    
    protected $fillable = [
        'Codigo',
        'Nombre',
        'Ciudad',
        'email',
        'Documento',
        'Ciudad_Cobro',
        'Usuario',
        'Fecha_Creacion',
        'Fecha_Modificacion',
    ];

}
