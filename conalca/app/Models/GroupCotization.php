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
        'reference',
        'status',
    ];

    public function cotizaciones() {
        return $this->hasMany(CotizacionModel::class, 'group_cotization_id')->with('producto');
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
