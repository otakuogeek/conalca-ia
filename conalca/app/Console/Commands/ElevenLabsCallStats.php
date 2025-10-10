<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsCallService;
use App\Models\DriverCallResponse;
use Carbon\Carbon;

class ElevenLabsCallStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elevenlabs:call-stats {--days=7} {--detailed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show ElevenLabs SIP trunk call statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $detailed = $this->option('detailed');
        
        $this->info("=== Estadísticas de llamadas ElevenLabs (últimos {$days} días) ===");
        $this->line('');

        try {
            // Conexión a ElevenLabs
            $elevenLabsCallService = app(ElevenLabsCallService::class);
            $connectionTest = $elevenLabsCallService->testSipTrunkConnection();
            
            $this->info("Estado de conexión ElevenLabs:");
            if ($connectionTest['success']) {
                $this->line("  ✅ API disponible: SÍ");
                $this->line("  ✅ Agent ID: " . ($connectionTest['agent_id'] ?? 'No configurado'));
                $this->line("  ✅ Phone Number ID: " . ($connectionTest['agent_phone_number_id'] ?? 'No configurado'));
            } else {
                $this->line("  ❌ Error: " . $connectionTest['error']);
            }
            $this->line('');

            // Estadísticas desde la base de datos
            $startDate = Carbon::now()->subDays($days);
            
            $totalCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->count();
                
            $successfulCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->where('call_status', 'completed')
                ->count();
                
            $pendingCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->where('call_status', 'calling')
                ->count();
                
            $failedCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->where('call_status', 'failed')
                ->count();

            $acceptedResponses = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->where('response_status', 'accepted')
                ->count();

            $rejectedResponses = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                ->where('created_at', '>=', $startDate)
                ->where('response_status', 'rejected')
                ->count();

            // Mostrar estadísticas
            $this->info("Resumen de llamadas:");
            $this->line("  Total de llamadas: {$totalCalls}");
            $this->line("  Completadas: {$successfulCalls}");
            $this->line("  En progreso: {$pendingCalls}");
            $this->line("  Fallidas: {$failedCalls}");
            $this->line('');
            
            $this->info("Respuestas de conductores:");
            $this->line("  Aceptadas: {$acceptedResponses}");
            $this->line("  Rechazadas: {$rejectedResponses}");
            $this->line('');

            // Estadísticas por día si es modo detallado
            if ($detailed && $totalCalls > 0) {
                $this->info("Detalle por día:");
                
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $dayStart = $date->copy()->startOfDay();
                    $dayEnd = $date->copy()->endOfDay();
                    
                    $dayCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count();
                    
                    if ($dayCalls > 0) {
                        $daySuccess = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                            ->whereBetween('created_at', [$dayStart, $dayEnd])
                            ->where('call_status', 'completed')
                            ->count();
                        
                        $successRate = $dayCalls > 0 ? round(($daySuccess / $dayCalls) * 100, 1) : 0;
                        
                        $this->line("  {$date->format('Y-m-d')}: {$dayCalls} llamadas, {$daySuccess} exitosas ({$successRate}%)");
                    }
                }
                $this->line('');
            }

            // Últimas llamadas
            if ($detailed) {
                $this->info("Últimas 5 llamadas:");
                $recentCalls = DriverCallResponse::whereNotNull('elevenlabs_conversation_id')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['driver_name', 'driver_phone', 'call_status', 'response_status', 'created_at', 'notes']);

                foreach ($recentCalls as $call) {
                    $this->line("  {$call->created_at->format('Y-m-d H:i')} - {$call->driver_name} ({$call->driver_phone})");
                    $this->line("    Estado: {$call->call_status} | Respuesta: {$call->response_status}");
                    if ($call->notes) {
                        $this->line("    Notas: " . substr($call->notes, 0, 60) . (strlen($call->notes) > 60 ? '...' : ''));
                    }
                    $this->line('');
                }
            }

            // Cálculo de tasa de éxito
            if ($totalCalls > 0) {
                $successRate = round(($successfulCalls / $totalCalls) * 100, 1);
                $responseRate = round((($acceptedResponses + $rejectedResponses) / $totalCalls) * 100, 1);
                
                $this->info("Métricas:");
                $this->line("  Tasa de éxito: {$successRate}%");
                $this->line("  Tasa de respuesta: {$responseRate}%");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo estadísticas: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
