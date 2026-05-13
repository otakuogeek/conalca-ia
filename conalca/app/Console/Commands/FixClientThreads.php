<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\ConversationSession;
use Illuminate\Support\Facades\Log;

class FixClientThreads extends Command
{
    protected $signature = 'fix:client-threads 
                            {--client_id= : ID del cliente específico a arreglar}
                            {--all : Arreglar todos los clientes con sesiones activas}';

    protected $description = 'Sincroniza los thread_ids faltantes entre clients y conversation_sessions';

    public function handle()
    {
        $this->info("🔧 Reparando sincronización de threads...\n");
        
        $clientId = $this->option('client_id');
        $all = $this->option('all');
        
        if (!$clientId && !$all) {
            $this->error("❌ Debes especificar --client_id=X o --all");
            return 1;
        }
        
        $query = ConversationSession::where('status', 'active')
            ->whereNotNull('session_id');
            
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        
        $sessions = $query->get();
        
        $this->info("📊 Sesiones encontradas: " . $sessions->count());
        $this->newLine();
        
        $fixed = 0;
        $errors = 0;
        
        foreach ($sessions as $session) {
            $client = Client::find($session->client_id);
            
            if (!$client) {
                $this->warn("⚠️  Cliente no encontrado para sesión {$session->id}");
                $errors++;
                continue;
            }
            
            $needsUpdate = false;
            $changes = [];
            
            // Si el cliente no tiene thread_id pero la sesión sí
            if (!$client->openai_thread_id && $session->session_id) {
                $client->openai_thread_id = $session->session_id;
                $needsUpdate = true;
                $changes[] = "thread_id";
            }
            
            // Si hay run_id en metadata pero no en el cliente
            if ($session->metadata) {
                $metadata = json_decode($session->metadata, true);
                if (isset($metadata['current_run_id']) && !$client->openai_current_run) {
                    $client->openai_current_run = $metadata['current_run_id'];
                    $needsUpdate = true;
                    $changes[] = "run_id";
                }
            }
            
            if ($needsUpdate) {
                $client->save();
                $fixed++;
                
                $this->info("✅ Cliente {$client->id} ({$client->cliente})");
                $this->line("   📝 Actualizado: " . implode(', ', $changes));
                $this->line("   🆔 Thread: {$client->openai_thread_id}");
                if ($client->openai_current_run) {
                    $this->line("   🏃 Run: {$client->openai_current_run}");
                }
                $this->newLine();
                
                Log::info("Thread sincronizado para cliente", [
                    'client_id' => $client->id,
                    'thread_id' => $client->openai_thread_id,
                    'run_id' => $client->openai_current_run,
                    'changes' => $changes
                ]);
            } else {
                $this->line("ℹ️  Cliente {$client->id} ya está sincronizado");
            }
        }
        
        $this->newLine();
        $this->info("📈 Resumen:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Sesiones revisadas', $sessions->count()],
                ['Clientes actualizados', $fixed],
                ['Errores', $errors],
            ]
        );
        
        if ($fixed > 0) {
            $this->newLine();
            $this->comment("💡 Ahora los clientes deberían poder sincronizar datos del chat al panel lateral.");
            $this->comment("   Recarga la página /cotizacion para ver los cambios.");
        }
        
        return 0;
    }
}
