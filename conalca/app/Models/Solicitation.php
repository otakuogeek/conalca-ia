<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by','pricing_id','importance','status','origin',
        'destination','description','price',
        'pricing_note','superadmin_note','support_requested_at'
    ];

    /* ─── relations ─────────────────── */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
