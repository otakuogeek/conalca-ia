<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ArcangelModeSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arcangel:mode {mode : production or development}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch Arcangel API between production and development mode';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->argument('mode');
        
        if (!in_array($mode, ['production', 'development'])) {
            $this->error('Invalid mode. Use "production" or "development"');
            return 1;
        }
        
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            $this->error('.env file not found');
            return 1;
        }
        
        $content = file_get_contents($envFile);
        
        // Cambiar el modo
        if ($mode === 'production') {
            $content = preg_replace(
                '/ARCANGEL_MODE=development/',
                'ARCANGEL_MODE=production',
                $content
            );
            $content = preg_replace(
                '/^ARCANGEL_MODE=production$/m',
                'ARCANGEL_MODE=production',
                $content
            );
            // Comentar development, descomentar production
            $content = preg_replace(
                '/^ARCANGEL_MODE=production$/m',
                'ARCANGEL_MODE=production',
                $content
            );
        } else {
            $content = preg_replace(
                '/ARCANGEL_MODE=production/',
                'ARCANGEL_MODE=development',
                $content
            );
        }
        
        file_put_contents($envFile, $content);
        
        // Limpiar caché
        $this->info('Clearing configuration cache...');
        Artisan::call('config:clear');
        
        // Recargar PHP-FPM
        $this->info('Reloading PHP-FPM...');
        exec('sudo /etc/init.d/php-fpm-83 reload 2>&1', $output, $return);
        
        $this->newLine();
        $this->info("✓ Arcangel mode switched to: {$mode}");
        $this->newLine();
        
        // Mostrar configuración actual
        config()->forget('arcangel'); // Forzar recarga
        
        $currentMode = config('arcangel.mode');
        $baseUrl = $currentMode === 'development' 
            ? config('arcangel.base_url_dev') 
            : config('arcangel.base_url');
        $apiKey = $currentMode === 'development'
            ? config('arcangel.api_key_dev')
            : config('arcangel.api_key');
            
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mode', $currentMode],
                ['Base URL', $baseUrl],
                ['API Key', substr($apiKey, 0, 20) . '...'],
            ]
        );
        
        return 0;
    }
}
