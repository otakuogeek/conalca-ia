<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cache configuraciones importantes por 1 hora
        $this->cacheConfigurations();
        
        // Share cached data with views
        $this->shareViewData();
    }

    /**
     * Cache important configurations
     */
    protected function cacheConfigurations(): void
    {
        // Cache voice configurations for ElevenLabs
        Cache::remember('elevenlabs_voices', 3600, function () {
            return app(\App\Services\ElevenLabsService::class)->getVoices();
        });

        // Cache system configuration
        Cache::remember('system_config', 3600, function () {
            return [
                'twilio_configured' => !empty(config('services.twilio.sid')),
                'elevenlabs_configured' => !empty(config('services.elevenlabs.api_key')),
                'openai_configured' => !empty(env('OPENAI_API_KEY')),
            ];
        });
    }

    /**
     * Share cached data with views
     */
    protected function shareViewData(): void
    {
        View::composer('*', function ($view) {
            $view->with('systemConfig', Cache::get('system_config', []));
        });
    }
}