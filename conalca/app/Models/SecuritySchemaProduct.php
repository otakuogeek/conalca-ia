<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySchemaProduct extends Model
{
    protected $table = 'security_schema_products';

    protected $fillable = ['user_id', 'name', 'product_code', 'category'];

    protected $casts = [
        'product_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
