<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConversationSession;
use App\Models\ConversationMessage;

class ClearChatSession extends Command
{
    protected $signature = 'chat:clear-session {client_id}';
    protected $description = 'Limpiar mensajes de la sesión de chat de un cliente';

    public function handle()
    {
        $clientId = $this->argument('client_id');
        
        $session = ConversationSession::where('client_id', $clientId)->first();
        
        if (!$session) {
            $this->error("No se encontró sesión para el cliente {$clientId}");
            return 1;
        }
        
        $count = ConversationMessage::where('session_id', $session->id)->count();
        ConversationMessage::where('session_id', $session->id)->delete();
        
        $this->info("✅ Se eliminaron {$count} mensajes de la sesión");
        $this->info("   Thread ID: {$session->session_id}");
        
        return 0;
    }
}
