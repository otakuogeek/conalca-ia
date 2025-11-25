<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('goals:evaluate')
            ->monthlyOn(1, '00:10'); // cada 1º de mes a las 00:10
            
        // Limpieza automática de archivos de audio temporales cada hora
        $schedule->command('audio:cleanup')
            ->hourly()
            ->withoutOverlapping();
            
        // Limpieza completa del sistema diariamente a las 2 AM
        $schedule->command('system:cleanup --all')
            ->dailyAt('02:00')
            ->withoutOverlapping();
            
        // Monitoreo del sistema cada 5 minutos en formato JSON para logs
        $schedule->command('system:monitor --json')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/system-monitor.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
