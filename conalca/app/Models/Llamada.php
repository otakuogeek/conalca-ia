<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Llamada extends Model
{
    use HasFactory;

    protected $table = 'llamadas';
    protected $primaryKey = 'id_llamada';

    protected $fillable = [
        'id_cotizacion',
        'chofer_id',
        'numero_destino',
        'status',
        'call_status',
        'sip_status_code',
        'sip_status_message',
        'failure_reason',
        'fecha_llamada',
        'observaciones',
        'elevenlabs_conversation_id',
        'elevenlabs_sip_call_id',
        'call_started_at',
        'call_ended_at',
        'call_notes',
        'call_initiated_at',
        'call_ringing_at',
        'call_answered_at',
        'call_completed_at',
        'call_duration_seconds',
        'ring_duration_seconds',
        'talk_duration_seconds',
        'elevenlabs_response',
        'call_direction',
        'call_type',
        'driver_phone_type',
        'call_retry_count',
        'next_retry_at',
        'call_metadata',
        'internal_notes',
        // Campos de gestión de cola
        'queue_status',
        'batch_number',
        'batch_position',
        'queue_priority',
        'queued_at',
        'processing_started_at',
        'processing_completed_at',
        'estimated_wait_seconds',
        'processing_attempts',
        'last_attempt_at'
    ];

    protected $casts = [
        'fecha_llamada' => 'datetime',
        'call_started_at' => 'datetime',
        'call_ended_at' => 'datetime',
        'call_initiated_at' => 'datetime',
        'call_ringing_at' => 'datetime',
        'call_answered_at' => 'datetime',
        'call_completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'elevenlabs_response' => 'array',
        'call_metadata' => 'array',
        // Campos de cola
        'queued_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    // Estados posibles para las llamadas (status general)
    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_EN_CURSO = 'en_curso';
    public const STATUS_FINALIZADA = 'finalizada';
    public const STATUS_ACEPTADA = 'aceptada';
    public const STATUS_RECHAZADA = 'rechazada';

    // Estados detallados de llamada (call_status)
    public const CALL_STATUS_INITIATED = 'initiated';
    public const CALL_STATUS_RINGING = 'ringing';
    public const CALL_STATUS_ANSWERED = 'answered';
    public const CALL_STATUS_BUSY = 'busy';
    public const CALL_STATUS_NO_ANSWER = 'no_answer';
    public const CALL_STATUS_FAILED = 'failed';
    public const CALL_STATUS_COMPLETED = 'completed';
    public const CALL_STATUS_CANCELLED = 'cancelled';

    // Códigos SIP comunes
    public const SIP_CODE_OK = '200';
    public const SIP_CODE_RINGING = '180';
    public const SIP_CODE_BUSY = '486';
    public const SIP_CODE_NO_ANSWER = '408';
    public const SIP_CODE_NOT_FOUND = '404';
    public const SIP_CODE_UNAVAILABLE = '503';

    // Direcciones de llamada
    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    // Tipos de llamada
    public const TYPE_AGENT = 'agent';
    public const TYPE_MANUAL = 'manual';
    public const TYPE_WEBHOOK = 'webhook';

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDIENTE => 'Pendiente',
            self::STATUS_EN_CURSO => 'En Curso',
            self::STATUS_FINALIZADA => 'Finalizada',
            self::STATUS_ACEPTADA => 'Aceptada',
            self::STATUS_RECHAZADA => 'Rechazada',
        ];
    }

    public static function getCallStatusOptions()
    {
        return [
            self::CALL_STATUS_INITIATED => 'Iniciada',
            self::CALL_STATUS_RINGING => 'Timbrando',
            self::CALL_STATUS_ANSWERED => 'Contestada',
            self::CALL_STATUS_BUSY => 'Ocupado',
            self::CALL_STATUS_NO_ANSWER => 'Sin Respuesta',
            self::CALL_STATUS_FAILED => 'Fallida',
            self::CALL_STATUS_COMPLETED => 'Completada',
            self::CALL_STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public static function getSipStatusMessages()
    {
        return [
            self::SIP_CODE_OK => 'OK - Llamada exitosa',
            self::SIP_CODE_RINGING => 'Ringing - Timbrando',
            self::SIP_CODE_BUSY => 'Busy Here - Línea ocupada',
            self::SIP_CODE_NO_ANSWER => 'Request Timeout - Sin respuesta',
            self::SIP_CODE_NOT_FOUND => 'Not Found - Número no encontrado',
            self::SIP_CODE_UNAVAILABLE => 'Service Unavailable - Servicio no disponible',
        ];
    }

    /**
     * Relación con la cotización
     */
    public function cotizacion()
    {
        return $this->belongsTo(CotizacionModel::class, 'id_cotizacion');
    }

    /**
     * Relación con el conductor/chofer
     */
    public function chofer()
    {
        return $this->belongsTo(VehicleOwnerHolderDriver::class, 'chofer_id');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener llamadas de una cotización específica
     */
    public function scopeByCotizacion($query, $cotizacionId)
    {
        return $query->where('id_cotizacion', $cotizacionId);
    }

    /**
     * Scope para obtener llamadas de un chofer específico
     */
    public function scopeByChofer($query, $choferId)
    {
        return $query->where('chofer_id', $choferId);
    }
}
