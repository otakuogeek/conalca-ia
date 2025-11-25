<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LlamadaConductor extends Model
{
    use HasFactory;

    protected $table = 'llamadas_conductores';

    protected $fillable = [
        'nombre_conductor',
        'telefono',
        'placa',
        'clase_vehiculo',
        'score',
        'carroceria',
        'capacidad',
        'fuente',
        'chofer_id_local',
        'arcangel_id',
        'disponible',
        'ciudad',
        'ultima_actualizacion',
        'datos_adicionales',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'score' => 'float',
        'capacidad' => 'integer',
        'ultima_actualizacion' => 'datetime',
        'datos_adicionales' => 'array',
    ];

    /**
     * Relación con llamadas
     */
    public function llamadas()
    {
        return $this->hasMany(Llamada::class, 'conductor_id');
    }

    /**
     * Crear o actualizar conductor desde datos de Arcángel
     */
    public static function createFromArcangel(array $vehiculo, string $ciudad): self
    {
        return self::updateOrCreate(
            [
                'telefono' => $vehiculo['telefono'] ?? null,
                'placa' => $vehiculo['placa'] ?? null,
            ],
            [
                'nombre_conductor' => $vehiculo['conductor'] ?? 'N/A',
                'clase_vehiculo' => $vehiculo['clase'] ?? null,
                'score' => $vehiculo['score'] ?? 0,
                'carroceria' => $vehiculo['carroceria'] ?? null,
                'capacidad' => $vehiculo['capacidad'] ?? null,
                'fuente' => 'arcangel',
                'disponible' => $vehiculo['disponible'] ?? true,
                'ciudad' => $ciudad,
                'ultima_actualizacion' => now(),
                'datos_adicionales' => $vehiculo,
            ]
        );
    }

    /**
     * Crear conductor desde datos locales
     */
    public static function createFromLocal($driver, string $ciudad): self
    {
        return self::updateOrCreate(
            [
                'chofer_id_local' => $driver->id,
            ],
            [
                'nombre_conductor' => $driver->Conductor ?? 'N/A',
                'telefono' => $driver->Telefonoconductor ?? $driver->Telefonopropietario ?? $driver->Telefonoposeedor,
                'placa' => $driver->Placa ?? null,
                'clase_vehiculo' => $driver->Clasevehiculo ?? null,
                'score' => 0,
                'fuente' => 'local',
                'disponible' => true,
                'ciudad' => $ciudad,
                'ultima_actualizacion' => now(),
            ]
        );
    }
}
