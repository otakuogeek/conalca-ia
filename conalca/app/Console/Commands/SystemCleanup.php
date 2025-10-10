<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemCleanup extends Command
{
    protected $signature = 'system:cleanup 
                          {--cache : Limpiar solo cache}
                          {--audio : Limpiar solo archivos de audio}
                          {--logs : Limpiar logs antiguos}
                          {--all : Limpiar todo}';
    
    protected $description = 'Limpia cachés, archivos temporales y optimiza el sistema';

    public function handle()
    {
        $this->info('🧹 Iniciando limpieza del sistema...');
        $this->newLine();

        $cache = $this->option('cache') || $this->option('all');
        $audio = $this->option('audio') || $this->option('all');
        $logs = $this->option('logs') || $this->option('all');

        if (!$cache && !$audio && !$logs) {
            $this->error('Especifica al menos una opción: --cache, --audio, --logs o --all');
            return 1;
        }

        $cleaned = [];

        if ($cache) {
            $cleaned['cache'] = $this->cleanCache();
        }

        if ($audio) {
            $cleaned['audio'] = $this->cleanAudioFiles();
        }

        if ($logs) {
            $cleaned['logs'] = $this->cleanLogs();
        }

        $this->displayResults($cleaned);
        
        // Optimization commands
        if ($cache) {
            $this->optimizeSystem();
        }

        $this->info('✅ Limpieza completada exitosamente');
        return 0;
    }

    protected function cleanCache(): array
    {
        $this->line('📦 Limpiando caché...');
        
        $result = [
            'config' => false,
            'route' => false,
            'view' => false,
            'application' => false
        ];

        try {
            // Clear all caches
            $this->call('config:clear');
            $result['config'] = true;
            
            $this->call('route:clear');
            $result['route'] = true;
            
            $this->call('view:clear');
            $result['view'] = true;
            
            Cache::flush();
            $result['application'] = true;

            $this->info('   ✅ Caché limpiado correctamente');
        } catch (\Exception $e) {
            $this->error('   ❌ Error limpiando caché: ' . $e->getMessage());
        }

        return $result;
    }

    protected function cleanAudioFiles(): array
    {
        $this->line('🎵 Limpiando archivos de audio temporales...');
        
        $result = [
            'temp_files' => 0,
            'old_files' => 0,
            'size_freed' => 0
        ];

        try {
            // Clean temporary audio files older than 1 hour
            $tempPath = storage_path('app/public/audios/temp');
            $hoursOld = config('app.audio_cleanup_hours', 1);
            
            if (is_dir($tempPath)) {
                $files = glob($tempPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && (time() - filemtime($file)) > ($hoursOld * 3600)) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $result['temp_files']++;
                            $result['size_freed'] += $size;
                        }
                    }
                }
            }

            // Clean old audio recordings from TwilioCall
            $oldCalls = DB::table('twilio_calls')
                ->where('created_at', '<', Carbon::now()->subDays(7))
                ->whereNotNull('audio_url')
                ->get();

            foreach ($oldCalls as $call) {
                if ($call->audio_url && Storage::disk('public')->exists($call->audio_url)) {
                    $size = Storage::disk('public')->size($call->audio_url);
                    if (Storage::disk('public')->delete($call->audio_url)) {
                        $result['old_files']++;
                        $result['size_freed'] += $size;
                    }
                }
            }

            $sizeFreedMB = round($result['size_freed'] / 1024 / 1024, 2);
            $this->info("   ✅ Archivos limpiados: {$result['temp_files']} temporales, {$result['old_files']} antiguos");
            $this->info("   💾 Espacio liberado: {$sizeFreedMB} MB");
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error limpiando archivos de audio: ' . $e->getMessage());
        }

        return $result;
    }

    protected function cleanLogs(): array
    {
        $this->line('📝 Limpiando logs antiguos...');
        
        $result = [
            'files_cleaned' => 0,
            'size_freed' => 0
        ];

        try {
            $logPath = storage_path('logs');
            $daysOld = 30; // Keep logs for 30 days
            
            $logFiles = glob($logPath . '/*.log');
            foreach ($logFiles as $file) {
                if ((time() - filemtime($file)) > ($daysOld * 24 * 3600)) {
                    $size = filesize($file);
                    if (unlink($file)) {
                        $result['files_cleaned']++;
                        $result['size_freed'] += $size;
                    }
                }
            }

            $sizeFreedMB = round($result['size_freed'] / 1024 / 1024, 2);
            $this->info("   ✅ Logs limpiados: {$result['files_cleaned']} archivos, {$sizeFreedMB} MB liberados");
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error limpiando logs: ' . $e->getMessage());
        }

        return $result;
    }

    protected function optimizeSystem(): void
    {
        $this->line('⚡ Optimizando sistema...');
        
        try {
            // Cache configurations for better performance
            $this->call('config:cache');
            $this->call('route:cache');
            
            $this->info('   ✅ Configuraciones y rutas cacheadas');
        } catch (\Exception $e) {
            $this->error('   ❌ Error optimizando: ' . $e->getMessage());
        }
    }

    protected function displayResults(array $cleaned): void
    {
        $this->newLine();
        $this->info('📊 Resumen de limpieza:');
        
        foreach ($cleaned as $type => $result) {
            if (is_array($result)) {
                $this->line("   {$type}: " . json_encode($result));
            } else {
                $this->line("   {$type}: " . ($result ? '✅' : '❌'));
            }
        }
    }
}