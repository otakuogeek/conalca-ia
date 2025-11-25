<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFile extends Model
{
    use HasFactory;

    protected $table = 'client_files';

    protected $fillable = [
        'client_id',
        'name',
        'file_path',
        'category',
        'size'
    ];

    /**
     * Get the client that owns the file
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
