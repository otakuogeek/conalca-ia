<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'name',
        'file_path',
        'category',
        'size'
    ];

    /**
     * Get the contact that owns the ContactFile
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
