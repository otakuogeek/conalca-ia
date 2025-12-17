<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LlamadaConductor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'llamadas_conductores';

    protected $fillable = [
        'identificador_unico',
        'cotizacion_id',
        'group_cotization_id',
        'nombre_conductor',
        'telefono',
        'placa',
        'tipo_vehiculo',
        'vehiculo_silogtran',
        'peso_maximo',
        'ciudad_actual',
        'ciudad_origen',
        'ciudad_destino',
        'disponible',
        'score',
        'estado_llamada',
        'call_id',
        'elevenlabs_conversation_id',
        'elevenlabs_sip_call_id',
        'driver_call_response_id',
        'fecha_llamada',
        'respuesta_llamada',
        'notas',
        'mercancia',
        'peso_carga',
        'empaque',
        'clase_vehiculo',
        'carroceria',
        'capacidad',
        'fuente',
        'chofer_id_local',
        'arcangel_id',
        'ciudad',
        'ultima_actualizacion',
        'datos_adicionales',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'score' => 'decimal:1',
        'peso_maximo' => 'decimal:2',
        'peso_carga' => 'decimal:2',
        'capacidad' => 'integer',
        'fecha_llamada' => 'datetime',
        'ultima_actualizacion' => 'datetime',
        'datos_adicionales' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con cotización (cotizacion_models)
     */
    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'cotizacion_id');
    }

    /**
     * Relación con grupo de cotización
     */
    public function groupCotization()
    {
        return $this->belongsTo(GroupCotization::class, 'group_cotization_id');
    }

    /**
     * Relación con llamadas (tabla llamadas)
     */
    public function llamadas()
    {
        return $this->hasMany(Llamada::class, 'conductor_id');
    }

    /**
     * Relación con la respuesta de llamada de ElevenLabs
     */
    public function driverCallResponse()
    {
        return $this->belongsTo(DriverCallResponse::class, 'driver_call_response_id');
    }

    /**
     * Relación con el chofer/driver de la tabla vieja (si existe)
     */
    public function chofer()
    {
        return $this->belongsTo(VehicleOwnerHolderDriver::class, 'chofer_id_local');
    }

    /**
     * Obtener cliente de la cotización
     */
    public function cliente()
    {
        return $this->hasOneThrough(
            \App\Models\Client::class,
            CotizacionModel::class,
            'id', // Foreign key en cotizacion_models
            'id', // Foreign key en clients
            'cotizacion_id', // Local key en llamadas_conductores
            'client_id' // Local key en cotizacion_models
        );
    }

    /**
     * Generar identificador único
     */
    public static function generarIdentificador(int $cotizacionId, string $telefono): string
    {
        return sprintf(
            'LC-%d-%s-%d',
            $cotizacionId,
            substr(md5($telefono), 0, 8),
            time()
        );
    }

    /**
     * Scope para conductores pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_llamada', 'pendiente');
    }

    /**
     * Scope para conductores disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true);
    }

    /**
     * Scope por cotización
     */
    public function scopePorCotizacion($query, int $cotizacionId)
    {
        return $query->where('cotizacion_id', $cotizacionId);
    }

    /**
     * Scope por grupo
     */
    public function scopePorGrupo($query, int $grupoId)
    {
        return $query->where('group_cotization_id', $grupoId);
    }

    /**
     * Crear o actualizar conductor desde datos de Arcángel
     */
    public static function createFromArcangel(array $vehiculo, string $ciudad, $cotizacionId = null, $groupId = null, array $cotizacion = []): self
    {
        $identificador = self::generarIdentificador($cotizacionId ?? 0, $vehiculo['telefono'] ?? '');
        
        // Procesar peso_carga
        $pesoCarga = null;
        if (!empty($cotizacion['peso_mercancia'])) {
            $pesoCarga = floatval(str_replace([',', ' kg', ' KG'], '', $cotizacion['peso_mercancia']));
        }
        
        // Procesar empaque (puede ser ID o texto)
        $empaqueTexto = $cotizacion['empaque'] ?? null;
        if (empty($empaqueTexto) && !empty($cotizacion['tipo_embajale'])) {
            // Si tipo_embajale es un número, es un ID - dejarlo como está por ahora
            $empaqueTexto = is_numeric($cotizacion['tipo_embajale']) 
                ? "Empaque ID: {$cotizacion['tipo_embajale']}"
                : $cotizacion['tipo_embajale'];
        }
        
        // Preparar datos adicionales completos
        $datosAdicionales = array_merge(
            $vehiculo,
            [
                'cotizacion' => [
                    'id' => $cotizacionId,
                    'group_id' => $groupId,
                    'vehiculo_requerido' => $cotizacion['vehiculo_requerido'] ?? 'No especificado',
                    'ciudad_origen' => $cotizacion['ciudad_origen'] ?? $ciudad,
                    'ciudad_destino' => $cotizacion['ciudad_destino'] ?? 'No especificado',
                    'tipo_mercancia' => $cotizacion['tipo_mercancia'] ?? 'Carga general',
                    'peso_mercancia' => $cotizacion['peso_mercancia'] ?? '0',
                    'peso_carga_numerico' => $pesoCarga,
                    'empaque' => $empaqueTexto ?? 'No especificado',
                    'tipo_embajale_original' => $cotizacion['tipo_embajale'] ?? null,
                ]
            ]
        );
        
        return self::updateOrCreate(
            [
                'identificador_unico' => $identificador,
            ],
            [
                'cotizacion_id' => $cotizacionId,
                'group_cotization_id' => $groupId,
                'nombre_conductor' => $vehiculo['conductor'] ?? 'N/A',
                'telefono' => $vehiculo['telefono'] ?? null,
                'placa' => $vehiculo['placa'] ?? null,
                'tipo_vehiculo' => $vehiculo['tipo_vehiculo'] ?? $vehiculo['clase'] ?? null,
                'vehiculo_silogtran' => $cotizacion['vehiculo_requerido'] ?? null,
                'peso_maximo' => $vehiculo['peso_maximo'] ?? null,
                'clase_vehiculo' => $vehiculo['clase'] ?? null,
                'score' => $vehiculo['score'] ?? 0,
                'carroceria' => $vehiculo['carroceria'] ?? null,
                'capacidad' => $vehiculo['capacidad'] ?? null,
                'fuente' => 'arcangel',
                'disponible' => $vehiculo['disponible'] ?? true,
                'ciudad_actual' => $ciudad,
                'ciudad_origen' => $cotizacion['ciudad_origen'] ?? $ciudad,
                'ciudad_destino' => $cotizacion['ciudad_destino'] ?? null,
                'mercancia' => $cotizacion['tipo_mercancia'] ?? 'Carga general',
                'peso_carga' => $pesoCarga,
                'empaque' => $empaqueTexto,
                'estado_llamada' => 'pendiente',
                'ultima_actualizacion' => now(),
                'datos_adicionales' => $datosAdicionales,
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
