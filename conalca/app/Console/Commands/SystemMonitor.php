<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\ElevenLabsService;
use App\Models\Llamada;

class SystemMonitor extends Command
{
    protected $signature = 'system:monitor 
                          {--detailed : Mostrar información detallada}
                          {--json : Salida en formato JSON}';
    
    protected $description = 'Monitorea el estado del sistema y muestra métricas importantes';

    public function handle()
    {
        $detailed = $this->option('detailed');
        $json = $this->option('json');
        
        $metrics = $this->gatherMetrics();
        
        if ($json) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }
        
        $this->displayMetrics($metrics, $detailed);
        return 0;
    }

    protected function gatherMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'elevenlabs' => $this->getElevenLabsMetrics(),
            'audio' => $this->getAudioMetrics(),
            'cache' => $this->getCacheMetrics(),
            'performance' => $this->getPerformanceMetrics()
        ];
    }

    protected function getSystemMetrics(): array
    {
        $memory = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'memory_usage' => $this->formatBytes($memory),
            'memory_peak' => $this->formatBytes($memoryPeak),
            'uptime' => $this->getUptime(),
        ];
    }

    protected function getDatabaseMetrics(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            $status = 'connected';
            
            // Count active connections and recent activity
            $recentCalls = Llamada::where('created_at', '>=', now()->subHour())->count();
            $totalCalls = Llamada::count();
            
        } catch (\Exception $e) {
            $status = 'error: ' . $e->getMessage();
            $recentCalls = 0;
            $totalCalls = 0;
        }

        return [
            'status' => $status,
            'recent_calls_1h' => $recentCalls,
            'total_calls' => $totalCalls,
            'connection_name' => config('database.default'),
        ];
    }

    protected function getElevenLabsMetrics(): array
    {
        $configured = !empty(config('elevenlabs.api_key'));
        
        if (!$configured) {
            return ['status' => 'not_configured'];
        }

        // Get call statistics from last 24 hours using Llamada model
        $stats = Llamada::where('created_at', '>=', now()->subDay())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "busy" THEN 1 ELSE 0 END) as busy,
                AVG(call_duration_seconds) as avg_duration
            ')
            ->first();

        return [
            'status' => 'configured',
            'calls_24h' => [
                'total' => $stats->total ?? 0,
                'completed' => $stats->completed ?? 0,
                'failed' => $stats->failed ?? 0,
                'busy' => $stats->busy ?? 0,
                'success_rate' => $stats->total > 0 ? round(($stats->completed / $stats->total) * 100, 2) : 0,
                'avg_duration' => round($stats->avg_duration ?? 0, 2),
            ]
        ];
    }

    protected function getAudioMetrics(): array
    {
        $audioPath = storage_path('app/public/audios');
        $tempPath = $audioPath . '/temp';
        
        $metrics = [
            'storage_path' => $audioPath,
            'storage_size' => 0,
            'temp_files' => 0,
            'old_files' => 0,
        ];

        if (is_dir($audioPath)) {
            $metrics['storage_size'] = $this->getDirectorySize($audioPath);
        }

        if (is_dir($tempPath)) {
            $tempFiles = glob($tempPath . '/*');
            $metrics['temp_files'] = count($tempFiles);
            
            // Count files older than 1 hour
            $oldCount = 0;
            foreach ($tempFiles as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 3600) {
                    $oldCount++;
                }
            }
            $metrics['old_files'] = $oldCount;
        }

        return $metrics;
    }

    protected function getCacheMetrics(): array
    {
        $driver = config('cache.default');
        
        $metrics = [
            'driver' => $driver,
            'status' => 'unknown'
        ];

        try {
            // Test cache functionality
            $testKey = 'system_monitor_test_' . time();
            Cache::put($testKey, 'test_value', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $metrics['status'] = ($retrieved === 'test_value') ? 'working' : 'error';
        } catch (\Exception $e) {
            $metrics['status'] = 'error: ' . $e->getMessage();
        }

        return $metrics;
    }

    protected function getPerformanceMetrics(): array
    {
        $startTime = microtime(true);
        
        // Simple performance test
        for ($i = 0; $i < 1000; $i++) {
            $hash = md5($i);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        return [
            'cpu_test_ms' => round($processingTime, 2),
            'load_average' => $this->getLoadAverage(),
            'disk_usage' => $this->getDiskUsage(),
        ];
    }

    protected function displayMetrics(array $metrics, bool $detailed): void
    {
        $this->info('🖥️  ESTADO DEL SISTEMA CONALCA');
        $this->info('════════════════════════════════════');

        // System Overview
        $this->newLine();
        $this->comment('📊 Sistema General:');
        $system = $metrics['system'];
        $this->line("   PHP: {$system['php_version']} | Laravel: {$system['laravel_version']}");
        $this->line("   Entorno: {$system['environment']} | Debug: " . ($system['debug_mode'] ? 'ON' : 'OFF'));
        $this->line("   Memoria: {$system['memory_usage']} (pico: {$system['memory_peak']})");

        // Database
        $this->newLine();
        $this->comment('🗄️  Base de Datos:');
        $db = $metrics['database'];
        $status = $db['status'] === 'connected' ? '✅ Conectada' : '❌ ' . $db['status'];
        $this->line("   Estado: {$status}");
        if ($db['status'] === 'connected') {
            $this->line("   Llamadas recientes (1h): {$db['recent_calls_1h']}");
            $this->line("   Total llamadas: {$db['total_calls']}");
        }

        // Twilio
        $this->newLine();
        $this->comment('📞 Twilio:');
        $twilio = $metrics['twilio'];
        if ($twilio['status'] === 'configured') {
            $calls = $twilio['calls_24h'];
            $this->line("   Estado: ✅ Configurado");
            $this->line("   Llamadas (24h): {$calls['total']} total, {$calls['completed']} completadas");
            $this->line("   Tasa de éxito: {$calls['success_rate']}%");
            $this->line("   Duración promedio: {$calls['avg_duration']}s");
        } else {
            $this->line("   Estado: ❌ No configurado");
        }

        // Audio Storage
        $this->newLine();
        $this->comment('🎵 Almacenamiento de Audio:');
        $audio = $metrics['audio'];
        $this->line("   Tamaño total: {$audio['storage_size']}");
        $this->line("   Archivos temporales: {$audio['temp_files']}");
        if ($audio['old_files'] > 0) {
            $this->line("   ⚠️  Archivos antiguos para limpiar: {$audio['old_files']}");
        }

        // Cache
        $this->newLine();
        $this->comment('💾 Cache:');
        $cache = $metrics['cache'];
        $status = $cache['status'] === 'working' ? '✅ Funcionando' : '❌ ' . $cache['status'];
        $this->line("   Driver: {$cache['driver']}");
        $this->line("   Estado: {$status}");

        // Performance
        if ($detailed) {
            $this->newLine();
            $this->comment('⚡ Rendimiento:');
            $perf = $metrics['performance'];
            $this->line("   Test CPU: {$perf['cpu_test_ms']} ms");
            $this->line("   Carga del sistema: {$perf['load_average']}");
            $this->line("   Uso de disco: {$perf['disk_usage']}");
        }

        $this->newLine();
        $this->info('Ejecuta "php artisan system:cleanup --all" para optimizar el sistema');
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    protected function getDirectorySize(string $directory): string
    {
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $this->formatBytes($size);
    }

    protected function getUptime(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'No disponible';
        }
        return 'No disponible';
    }

    protected function getLoadAverage(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return sprintf('%.2f %.2f %.2f', $load[0], $load[1], $load[2]);
        }
        return 'No disponible';
    }

    protected function getDiskUsage(): string
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        
        if ($free && $total) {
            $used = $total - $free;
            $percent = round(($used / $total) * 100, 1);
            return "{$percent}% usado";
        }
        
        return 'No disponible';
    }
}