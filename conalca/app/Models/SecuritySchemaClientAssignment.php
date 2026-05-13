<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySchemaClientAssignment extends Model
{
    protected $table = 'security_schema_client_assignments';

    protected $fillable = ['client_id', 'assigned_by'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
