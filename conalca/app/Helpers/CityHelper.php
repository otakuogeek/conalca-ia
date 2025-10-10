<?php

namespace App\Helpers;

use App\Models\City;

class CityHelper
{
    /**
     * Devuelve el campo ciudad_codigodane para un nombre de ciudad.
     * Si no existe retorna null.
     */
    public static function daneCode(string $cityName): ?string
    {
        return City::query()
            ->whereRaw('LOWER(ciudad_nombre) = ?', [ strtolower($cityName) ])
            ->value('ciudad_codigodane');
    }

    /**
     * Devuelve un listado (id => nombre) para poblar selects.
     */
    public static function list(): array
    {
        return City::orderBy('ciudad_nombre')
                   ->pluck('ciudad_nombre', 'ciudad_codigodane')
                   ->toArray();
    }
}