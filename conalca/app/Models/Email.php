<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user',
        'to_user',
        'subject',
        'files',
        'status',
        'description',
        'parent_id'
    ];

    /**
     * Get the from_user that owns the Email
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function from_user_relation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user');
    }

    /**
     * Get the to_user that owns the Email
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function to_user_relation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Email::class, 'parent_id');
    }
}
