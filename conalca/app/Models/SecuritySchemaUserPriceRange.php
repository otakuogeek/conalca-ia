<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecuritySchemaUserPriceRange extends Model
{
    use HasFactory;

    protected $table = 'security_schema_user_price_ranges';

    protected $fillable = [
        'user_id',
        'base_range_id',
        'price_from',
        'price_to',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function baseRange()
    {
        return $this->belongsTo(SecuritySchemaPriceRange::class, 'base_range_id');
    }

    public function measures()
    {
        return $this->hasMany(SecuritySchemaUserMeasure::class, 'user_price_range_id');
    }
}
