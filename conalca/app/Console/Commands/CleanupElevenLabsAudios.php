<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElevenLabsService;

class CleanupElevenLabsAudios extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elevenlabs:cleanup {--hours=1 : Horas de antigüedad para limpiar archivos}';

    /**
     * The console command description.
     */
    protected $description = 'Limpiar archivos de audio antiguos generados por ElevenLabs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Limpiando archivos de ElevenLabs de más de {$hours} horas...");
        
        $audioPath = storage_path('app/public/audios');
        $cleaned = 0;
        $maxAge = $hours * 3600; // Horas a segundos
        
        if (is_dir($audioPath)) {
            $files = scandir($audioPath);
            
            foreach ($files as $file) {
                if (strpos($file, 'elevenlabs_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'mp3') {
                    $filePath = $audioPath . '/' . $file;
                    $fileTime = filemtime($filePath);
                    
                    if (time() - $fileTime > $maxAge) {
                        if (unlink($filePath)) {
                            $cleaned++;
                            $this->line("Eliminado: {$file}");
                        }
                    }
                }
            }
        }
        
        $this->info("Limpieza completada. {$cleaned} archivos eliminados.");
        return 0;
    }
}