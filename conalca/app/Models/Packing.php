<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    // Nombre explícito de la tabla
    protected $table = 'tb_empaque';
    
    // Especificar la clave primaria
    protected $primaryKey = 'id';
    
    // La clave primaria es autoincremental
    public $incrementing = true;
    
    // Tipo de la clave primaria
    protected $keyType = 'int';
    
    // Deshabilitar timestamps (usa data_criacao/data_modificacao)
    public $timestamps = false;
    
    // Mapear nombres de timestamps personalizados
    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = 'data_modificacao';

    // Campos asignables masivamente
    protected $fillable = [
        'codigo_ministerio',
        'nome',
        'usuario',
        'data_criacao',
        'data_modificacao'
    ];
    
    // Accessor para compatibilidad con código existente que usa 'Codigo'
    public function getCodigoAttribute()
    {
        return $this->codigo_ministerio;
    }
    
    // Accessor para compatibilidad con código existente que usa 'Nombre'
    public function getNombreAttribute()
    {
        return $this->nome;
    }
}
