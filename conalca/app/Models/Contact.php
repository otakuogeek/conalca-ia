<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'nit',
        'sector',
        'address',
        'email',
        'position',
        'contact',
        'phone',
        'city',
        'main_contact',
        'contact_title',
        'address_2',
        'email_2',
        'projected_value',
        'date',
        'city_2',
        'openai_thread_id',
        'openai_current_run',
        'document',
        'name',
        'client_id',
    ];

    /**
     * Get all of the files for the Contact
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(ContactFile::class);
    }

     public function client()
    {
        return $this->belongsTo(Client::class, 'document', 'document');
    }

}
