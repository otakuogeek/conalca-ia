<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'group_cotization_id', // 🆕 Agregar para permitir asignación masiva
        'role',
        'content',
        'metadata',
        'timestamp'
    ];

    protected $casts = [
        'metadata' => 'array',
        'timestamp' => 'datetime'
    ];

    public function session()
    {
        return $this->belongsTo(ConversationSession::class, 'session_id');
    }

    public function isUserMessage()
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage()
    {
        return $this->role === 'assistant';
    }

    public function isSystemMessage()
    {
        return $this->role === 'system';
    }
}
