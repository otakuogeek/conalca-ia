<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['solicitation_id', 'user_id', 'content', 'channel'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
