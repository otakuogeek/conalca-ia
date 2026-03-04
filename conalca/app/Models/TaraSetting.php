<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaraSetting extends Model
{
    protected $table = 'tara_settings';

    protected $fillable = [
        'tara_contenedor_20',
        'tara_contenedor_40',
    ];

    protected $casts = [
        'tara_contenedor_20' => 'float',
        'tara_contenedor_40' => 'float',
    ];

    /**
     * Obtener la instancia única de configuración de tara.
     * Se cachea por 1 hora para no consultar BD en cada cotización.
     */
    public static function getInstance(): self
    {
        return Cache::remember('tara_settings', 3600, function () {
            return self::first() ?? self::create([
                'tara_contenedor_20' => 2300,
                'tara_contenedor_40' => 3400,
            ]);
        });
    }

    /**
     * Obtener tara según tamaño de contenedor (20, 40, 45 pies).
     */
    public static function getTara(int $containerSize): float
    {
        $settings = self::getInstance();

        return match ($containerSize) {
            20      => $settings->tara_contenedor_20,
            40, 45  => $settings->tara_contenedor_40,
            default => 0,
        };
    }

    /**
     * Shortcuts estáticos para uso rápido en código.
     */
    public static function tara20(): float
    {
        return self::getInstance()->tara_contenedor_20;
    }

    public static function tara40(): float
    {
        return self::getInstance()->tara_contenedor_40;
    }

    /**
     * Limpiar caché al actualizar.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('tara_settings');
        });
    }
}
