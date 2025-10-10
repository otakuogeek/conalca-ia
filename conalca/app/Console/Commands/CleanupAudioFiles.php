<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocalAudioStorage;
use Illuminate\Support\Facades\Log;

class CleanupAudioFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:cleanup {--max-age=3600 : Maximum age of temp files in seconds (default: 1 hour)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia archivos de audio temporales antiguos del almacenamiento local';

    protected $audioStorage;

    public function __construct(LocalAudioStorage $audioStorage)
    {
        parent::__construct();
        $this->audioStorage = $audioStorage;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxAge = (int) $this->option('max-age');
        
        $this->info("🧹 Iniciando limpieza de archivos temporales...");
        $this->info("⏰ Edad máxima: {$maxAge} segundos");
        
        try {
            // Obtener estadísticas antes de la limpieza
            $statsBefore = $this->audioStorage->getStorageStats();
            $this->info("📊 Archivos temporales antes: {$statsBefore['temp_files']} ({$statsBefore['temp_size_mb']} MB)");
            
            // Realizar limpieza
            $cleaned = $this->audioStorage->cleanupTempAudios($maxAge);
            
            // Obtener estadísticas después de la limpieza
            $statsAfter = $this->audioStorage->getStorageStats();
            
            $this->info("✅ Limpieza completada:");
            $this->line("   🗑️  Archivos eliminados: {$cleaned}");
            $this->line("   📁 Archivos restantes: {$statsAfter['temp_files']} ({$statsAfter['temp_size_mb']} MB)");
            
            $spaceSaved = $statsBefore['temp_size_mb'] - $statsAfter['temp_size_mb'];
            if ($spaceSaved > 0) {
                $this->line("   💾 Espacio liberado: {$spaceSaved} MB");
            }
            
            Log::info('Limpieza de archivos de audio completada', [
                'files_cleaned' => $cleaned,
                'space_saved_mb' => $spaceSaved,
                'max_age_seconds' => $maxAge
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: {$e->getMessage()}");
            Log::error('Error en limpieza de archivos de audio', [
                'error' => $e->getMessage(),
                'max_age' => $maxAge
            ]);
            
            return Command::FAILURE;
        }
    }
}
