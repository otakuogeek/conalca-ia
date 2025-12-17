<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupCotization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'type',
        'operation_type',
        'reference',
        'status',
        'candado_satelital',
        'jen_set',
        'combustible',
        'kit_derrames',
        'pictogramas',
        'cargo_type',
        'openai_thread_id',
        'created_from_chat',
    ];

    protected $casts = [
        'candado_satelital' => 'boolean',
        'jen_set' => 'boolean',
        'combustible' => 'boolean',
        'kit_derrames' => 'boolean',
        'pictogramas' => 'boolean',
        'created_from_chat' => 'boolean',
    ];

    public function cotizaciones() {
        return $this->hasMany(CotizacionModel::class, 'group_cotization_id')->with('producto');
    }

    /**
     * Relación con conductores llamados del grupo
     */
    public function llamadasConductores()
    {
        return $this->hasMany(LlamadaConductor::class, 'group_cotization_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tieneAceptada()
    {
        return $this->cotizaciones()->where('decision_cliente', 'aceptada')->exists();
    }
    public function todasRespondidas()
    {
        return $this->cotizaciones()->where('decision_cliente', 'pendiente')->count() === 0;
    }

}
