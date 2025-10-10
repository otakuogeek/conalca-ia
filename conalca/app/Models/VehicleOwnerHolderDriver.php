<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleOwnerHolderDriver extends Model
{
    use HasFactory;

    protected $table = 'vehicle_owner_holder_driver';
    
    // Habilitar timestamps ahora que los agregamos
    public $timestamps = true;

    protected $fillable = [
        'Telefonopropietario',
        'Telefonoposeedor', 
        'Telefonoconductor',
        'Codigo',
        'Placa',
        'Propietario',
        'Tipodocumentopropietario',
        'Documentopropietario',
        'Direccion propietario',
        'Ciudad propietario',
        'Ciudad codigodane propietario',
        'Municipio nombre',
        'Municipio dane',
        'Departamento nombre',
        'Departamento dane',
        'Poseedor',
        'Tipodocumentoposeedor',
        'Documentoposeedor',
        'Direccion poseedor',
        'Ciudad poseedor',
        'Ciudad codigodane poseedor',
        'Conductor',
        'Tipodocumentoconducotor',
        'Cedula',
        'Direccion conductor',
        'Ciudad conductor',
        'Ciudad codigodane conductor',
        'Vehiculo ejes',
        'Clasevehiculo',
        'Marca',
        'Clase linea',
        'Modelo',
        'Vehiculo chasis',
        'Pais',
        'Fecha',
        'Tipafi codigo',
        'Tipafi nombre',
        'Carroceria',
        'Capacidad',
        'Estado',
        'Proveedorgps',
        'Usuariogps',
        'Clavegps',
        'Trailer',
        'Propietariotrailer',
        'Tipodocumentopropietariotrailer',
        'Documentopropietariotrailer',
        'Refrigeracion',
        'MyUnknownColumn',
        // Nuevos campos para gestión de llamadas
        'call_status',
        'last_call_at',
        'total_calls_received',
        'total_calls_accepted',
        'is_active'
    ];

    protected $casts = [
        'Fecha' => 'date',
        'Codigo' => 'integer',
        'Documentopropietario' => 'string',
        'Documentoposeedor' => 'string', 
        'Cedula' => 'string',
        'Vehiculo ejes' => 'integer',
        'Modelo' => 'string',
        'Tipafi codigo' => 'integer',
        'Capacidad' => 'integer',
        'Ciudad codigodane propietario' => 'integer',
        'Ciudad codigodane poseedor' => 'integer',
        'Ciudad codigodane conductor' => 'integer',
        'Documentopropietariotrailer' => 'string',
        // Nuevos campos
        'last_call_at' => 'datetime',
        'total_calls_received' => 'integer',
        'total_calls_accepted' => 'integer',
        'is_active' => 'boolean'
    ];

    // Establecer estado por defecto
    protected $attributes = [
        'Estado' => 'ACTIVO',
    ];

    /**
     * Override del método update para prevenir creación accidental de registros
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            throw new \Exception('No se puede actualizar un modelo que no existe en la base de datos');
        }
        
        return parent::update($attributes, $options);
    }

    /**
     * Scope para buscar por cédula
     */
    public function scopeByCedula($query, $cedula)
    {
        return $query->where('Cedula', $cedula);
    }

    /**
     * Scope para buscar por estado
     */
    public function scopeByEstado($query, $estado)
    {
        return $query->where('Estado', $estado);
    }

    /**
     * Verificar si la cédula ya existe (excluyendo el registro actual si se está editando)
     */
    public static function cedulaExiste($cedula, $excludeId = null)
    {
        $query = self::where('Cedula', $cedula);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    // Relaciones existentes
    public function cotizacionesAceptadas()
    {
        return $this->belongsToMany(
            CotizacionModel::class,
            'call_driver_decisions',
            'driver_id',
            'cotizacion_model_id'
        )->wherePivot('decision', 1);
    }

    /**
     * Relación con respuestas de llamadas
     */
    public function callResponses()
    {
        return $this->hasMany(DriverCallResponse::class, 'driver_id');
    }

    /**
     * Obtener el teléfono principal del conductor
     */
    public function getMainPhoneAttribute()
    {
        // Priorizar teléfono del conductor, luego poseedor, luego propietario
        $phone = $this->Telefonoconductor ?: $this->Telefonoposeedor ?: $this->Telefonopropietario;
        
        if (!$phone) return null;
        
        // Limpiar formato y tomar el primer número si hay múltiples
        $phone = trim(explode(' - ', $phone)[0]);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Tomar últimos 10 dígitos si es muy largo
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }
        
        return strlen($phone) >= 10 ? $phone : null;
    }

    /**
     * Scopes para filtrado
     */
    public function scopeAvailable($query)
    {
        return $query->where('call_status', 'available')
                    ->where('is_active', true);
    }

    public function scopeByVehicleType($query, $vehicleType)
    {
        $normalizedType = self::normalizeVehicleTypeStatic($vehicleType);
        
        // Si es TRACTOMULA, buscar todas las variaciones
        if ($normalizedType === 'TRACTOMULA') {
            return $query->where(function ($q) {
                $q->where('Clasevehiculo', 'LIKE', '%TRACTOMULA%')
                  ->orWhere('Clasevehiculo', 'LIKE', '%TRACTO MULA%');
            });
        }
        
        return $query->where('Clasevehiculo', 'LIKE', '%' . $normalizedType . '%');
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('Ciudad conductor', $city);
    }

    /**
     * Marcar conductor como llamado
     */
    public function markAsCalled()
    {
        $this->update([
            'last_call_at' => now(),
            'total_calls_received' => $this->total_calls_received + 1
        ]);
    }

    /**
     * Marcar conductor como que aceptó
     */
    public function markAsAccepted()
    {
        $this->update([
            'total_calls_accepted' => $this->total_calls_accepted + 1
        ]);
    }

    /**
     * Normalizar tipo de vehículo para búsqueda
     */
    private function normalizeVehicleType($vehicleType)
    {
        return self::normalizeVehicleTypeStatic($vehicleType);
    }

    /**
     * Normalizar tipo de vehículo para búsqueda (método estático)
     */
    public static function normalizeVehicleTypeStatic($vehicleType)
    {
        $normalized = strtoupper(trim($vehicleType));
        
        // Convertir variaciones comunes
        $conversions = [
            'TRACTO MULA' => 'TRACTOMULA',
            'TRACTO-MULA' => 'TRACTOMULA',
            'TRACTO_MULA' => 'TRACTOMULA',
            'TRACTO MULA S3' => 'TRACTOMULA',
            'MULA' => 'TRACTOMULA',
        ];
        
        return $conversions[$normalized] ?? $normalized;
    }
    
    /**
     * Métodos de acceso intuitivos
     */
    public function getNameAttribute()
    {
        return $this->Conductor;
    }
    
    public function getPhoneNumberAttribute()
    {
        return $this->Telefonoconductor;
    }
    
    public function getVehicleTypeAttribute()
    {
        return $this->Clasevehiculo;
    }
    
    public function getVehiclePlateAttribute()
    {
        return $this->Placa;
    }
    
    public function getDocumentNumberAttribute()
    {
        return $this->Cedula;
    }
    
    /**
     * Obtener número de teléfono formateado para Twilio
     */
    public function getFormattedPhoneAttribute()
    {
        $phone = $this->Telefonoconductor;
        
        // Extraer solo el primer número si hay múltiples separados por " - "
        if (str_contains($phone, ' - ')) {
            $phone = explode(' - ', $phone)[0];
        }
        
        // Limpiar el número
        $cleaned = preg_replace('/[^\d]/', '', $phone);
        
        // Si es un número de 10 dígitos (típico de Colombia), agregar +57
        if (strlen($cleaned) == 10 && !str_starts_with($cleaned, '57')) {
            $cleaned = '57' . $cleaned;
        }
        
        return '+' . $cleaned;
    }
    
    /**
     * Scope para conductores con números válidos
     */
    public function scopeWithValidPhone($query)
    {
        return $query->whereNotNull('Telefonoconductor')
                    ->where('Telefonoconductor', '!=', '')
                    ->where('Telefonoconductor', 'not regexp', '^[0\s\-]+$');
    }

    /**
     * Relación con las llamadas registradas
     */
    public function llamadas()
    {
        return $this->hasMany(Llamada::class, 'chofer_id');
    }
}
