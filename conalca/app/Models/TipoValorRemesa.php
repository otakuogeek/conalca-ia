<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoValorRemesa extends Model
{
    protected $table = 'tipo_valor_remesa';
    protected $primaryKey = 'tipvalrem_codigo';
    public $incrementing = false;

    protected $fillable = [
        'tipvalrem_codigo',
        'tipvalrem_nombre',
    ];
}
