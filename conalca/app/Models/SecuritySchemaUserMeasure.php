<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecuritySchemaUserMeasure extends Model
{
    use HasFactory;

    protected $table = 'security_schema_user_measures';

    protected $fillable = [
        'user_price_range_id',
        'scope',
        'gps',
        'candado_satelital',
        'acompanamiento_vehicular',
        'acompanamiento_motorizado',
    ];

    protected $casts = [
        'gps' => 'boolean',
        'candado_satelital' => 'boolean',
    ];

    public function userPriceRange()
    {
        return $this->belongsTo(SecuritySchemaUserPriceRange::class, 'user_price_range_id');
    }
}
