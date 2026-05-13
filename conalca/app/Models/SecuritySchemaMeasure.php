<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySchemaMeasure extends Model
{
    protected $table = 'security_schema_measures';

    protected $fillable = [
        'price_range_id',
        'scope',
        'gps',
        'candado_satelital',
        'acompanamiento_vehicular',
        'acompanamiento_motorizado',
    ];

    protected $casts = [
        'gps' => 'boolean',
        'candado_satelital' => 'boolean',
        'acompanamiento_vehicular' => 'integer',
        'acompanamiento_motorizado' => 'integer',
    ];

    public function priceRange()
    {
        return $this->belongsTo(SecuritySchemaPriceRange::class, 'price_range_id');
    }
}
