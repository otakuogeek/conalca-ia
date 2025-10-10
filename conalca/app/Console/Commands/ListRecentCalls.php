<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;

class ListRecentCalls extends Command
{
    protected $signature = 'twilio:list-calls {--limit=10}';
    protected $description = 'Listar llamadas recientes del sistema';

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("📞 Últimas $limit llamadas del sistema:");
        
        $calls = Llamada::orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
        
        if ($calls->isEmpty()) {
            $this->warn("📵 No hay llamadas registradas");
            return;
        }
        
        $tableData = [];
        foreach ($calls as $call) {
            $tableData[] = [
                $call->id,
                $call->call_sid ?: 'N/A',
                $call->direction,
                $call->from_number,
                $call->to_number,
                $call->status,
                $call->duration ? $call->duration . 's' : 'N/A',
                $call->created_at->format('Y-m-d H:i:s'),
            ];
        }
        
        $this->table([
            'ID', 'Call SID', 'Dirección', 'Desde', 'Hacia', 'Estado', 'Duración', 'Fecha'
        ], $tableData);
        
        // Estadísticas rápidas
        $successful = $calls->where('status', 'completed')->count();
        $inProgress = $calls->whereIn('status', ['queued', 'ringing', 'initiated'])->count();
        $failed = $calls->whereIn('status', ['failed', 'no-answer', 'busy'])->count();
        
        $this->line("\n📊 Resumen:");
        $this->line("✅ Exitosas: $successful");
        $this->line("⏳ En progreso: $inProgress");
        $this->line("❌ Fallidas: $failed");
    }
}