<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySchemaPriceRange extends Model
{
    protected $table = 'security_schema_price_ranges';

    protected $fillable = ['user_id', 'category', 'price_from', 'price_to'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function measures()
    {
        return $this->hasMany(SecuritySchemaMeasure::class, 'price_range_id');
    }

    public function nacionalMeasure()
    {
        return $this->hasOne(SecuritySchemaMeasure::class, 'price_range_id')->where('scope', 'nacional');
    }

    public function urbanoMeasure()
    {
        return $this->hasOne(SecuritySchemaMeasure::class, 'price_range_id')->where('scope', 'urbano');
    }
}
