<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizationNote extends Model
{
    use HasFactory;
    protected $fillable = ['cotization_model_id','user_id','type','body'];

    public function cotization()  { return $this->belongsTo(CotizationModel::class); }
    public function author()      { return $this->belongsTo(User::class,'user_id');  }
}
