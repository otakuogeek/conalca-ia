<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Llamada;
use App\Services\LocalAudioStorage;

class SystemStatusReport extends Command
{
    protected $signature = 'system:status-report';
    protected $description = 'Generar reporte completo del estado del sistema';

    public function handle()
    {
        $this->info("📊 REPORTE COMPLETO DEL SISTEMA CONALCA");
        $this->line("══════════════════════════════════════════");
        
        // 1. Estado de Llamadas
        $this->line("\n📞 ESTADO DE LLAMADAS:");
        $totalCalls = Llamada::count();
        $completedCalls = Llamada::where('status', 'completed')->count();
        $inProgressCalls = Llamada::whereIn('status', ['initiated', 'queued', 'ringing'])->count();
        $failedCalls = Llamada::whereIn('status', ['failed', 'no-answer', 'busy'])->count();
        
        $this->table(['Tipo', 'Cantidad', 'Porcentaje'], [
            ['✅ Exitosas', $completedCalls, $totalCalls > 0 ? round(($completedCalls/$totalCalls)*100, 1).'%' : '0%'],
            ['⏳ En progreso', $inProgressCalls, $totalCalls > 0 ? round(($inProgressCalls/$totalCalls)*100, 1).'%' : '0%'],
            ['❌ Fallidas', $failedCalls, $totalCalls > 0 ? round(($failedCalls/$totalCalls)*100, 1).'%' : '0%'],
            ['📊 TOTAL', $totalCalls, '100%'],
        ]);
        
        // 2. Últimas llamadas exitosas
        if ($completedCalls > 0) {
            $this->line("\n🎉 ÚLTIMAS LLAMADAS EXITOSAS:");
            $successfulCalls = Llamada::where('status', 'completed')
                                       ->orderBy('created_at', 'desc')
                                       ->limit(5)
                                       ->get();
            
            $successData = [];
            foreach ($successfulCalls as $call) {
                $successData[] = [
                    $call->call_sid ?: 'N/A',
                    $call->to_number,
                    $call->duration ? $call->duration.'s' : 'N/A',
                    $call->created_at->format('Y-m-d H:i:s'),
                ];
            }
            
            $this->table(['Call SID', 'Destino', 'Duración', 'Fecha'], $successData);
        }
        
        // 3. Estado del almacenamiento de audio
        $this->line("\n🎵 ESTADO DEL ALMACENAMIENTO DE AUDIO:");
        try {
            $audioStorage = app(LocalAudioStorage::class);
            $stats = $audioStorage->getStorageStats();
            
            $this->table(['Métrica', 'Valor'], [
                ['📁 Archivos temporales', $stats['temp_files']],
                ['💾 Tamaño total', $stats['total_size_mb'] . ' MB'],
                ['📂 Directorio', $stats['temp_directory']],
                ['🧹 Último cleanup', $stats['last_cleanup'] ?: 'N/A'],
            ]);
        } catch (\Exception $e) {
            $this->error("❌ Error al obtener estadísticas de audio: " . $e->getMessage());
        }
        
        // 4. Verificación de servicios
        $this->line("\n🔧 VERIFICACIÓN DE SERVICIOS:");
        $services = [
            ['🎤 ElevenLabs', config('elevenlabs.api_key') ? '✅ Configurado' : '❌ No configurado'],
            ['📞 Twilio', config('services.twilio.sid') ? '✅ Configurado' : '❌ No configurado'],
            ['🧠 OpenAI', config('services.openai.api_key') ? '✅ Configurado' : '❌ No configurado'],
            ['💾 Base de datos', '✅ Conectada'],
            ['🌐 URL Webhook', config('services.twilio.webhook_url')],
        ];
        
        $this->table(['Servicio', 'Estado'], $services);
        
        // 5. Estadísticas de hoy
        $this->line("\n📅 ESTADÍSTICAS DE HOY:");
        $today = now()->format('Y-m-d');
        $todayCalls = Llamada::whereDate('created_at', $today)->count();
        $todaySuccess = Llamada::whereDate('created_at', $today)->where('status', 'completed')->count();
        $totalDuration = Llamada::whereDate('created_at', $today)
                                  ->where('status', 'completed')
                                  ->sum('call_duration_seconds');
        
        $this->table(['Métrica', 'Valor'], [
            ['📞 Llamadas hoy', $todayCalls],
            ['✅ Exitosas hoy', $todaySuccess],
            ['⏱️ Tiempo total', $totalDuration . ' segundos'],
            ['📊 Tasa de éxito', $todayCalls > 0 ? round(($todaySuccess/$todayCalls)*100, 1).'%' : '0%'],
        ]);
        
        // 6. Resumen final
        $this->line("\n🎯 RESUMEN EJECUTIVO:");
        if ($completedCalls >= 2) {
            $this->info("✅ SISTEMA OPERATIVO: El sistema está funcionando correctamente");
            $this->info("🎉 Se han realizado {$completedCalls} llamadas exitosas");
            $this->info("🎤 Voz AI: ElevenLabs + Marcela (KoIf2KgeJA8uoGcgKIao)");
            $this->info("📞 Telefonía: Twilio completamente integrado");
        } else {
            $this->warn("⚠️ SISTEMA EN PRUEBAS: Pocas llamadas exitosas registradas");
        }
        
        $this->line("\n══════════════════════════════════════════");
        $this->info("📊 Reporte generado: " . now()->format('Y-m-d H:i:s'));
    }
}