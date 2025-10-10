<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            // Estados detallados de la llamada
            $table->string('call_status')->nullable()->after('status'); // 'initiated', 'ringing', 'answered', 'busy', 'failed', 'completed'
            $table->string('sip_status_code')->nullable()->after('call_status'); // Código SIP (486, 200, 404, etc.)
            $table->string('sip_status_message')->nullable()->after('sip_status_code'); // Mensaje SIP detallado
            $table->string('failure_reason')->nullable()->after('sip_status_message'); // Razón específica del fallo
            
            // Tiempos específicos de la llamada
            $table->timestamp('call_initiated_at')->nullable()->after('failure_reason'); // Cuando se inició la llamada
            $table->timestamp('call_ringing_at')->nullable()->after('call_initiated_at'); // Cuando empezó a timbrar
            $table->timestamp('call_answered_at')->nullable()->after('call_ringing_at'); // Cuando se contestó
            $table->timestamp('call_completed_at')->nullable()->after('call_answered_at'); // Cuando se completó/terminó
            
            // Duración de la llamada
            $table->integer('call_duration_seconds')->nullable()->after('call_completed_at'); // Duración total en segundos
            $table->integer('ring_duration_seconds')->nullable()->after('call_duration_seconds'); // Tiempo que timbró
            $table->integer('talk_duration_seconds')->nullable()->after('ring_duration_seconds'); // Tiempo de conversación
            
            // Información adicional de ElevenLabs
            $table->json('elevenlabs_response')->nullable()->after('talk_duration_seconds'); // Respuesta completa de ElevenLabs
            $table->string('call_direction')->default('outbound')->after('elevenlabs_response'); // 'outbound', 'inbound'
            $table->string('call_type')->default('agent')->after('call_direction'); // 'agent', 'manual', 'webhook'
            
            // Información del conductor y contexto
            $table->string('driver_phone_type')->nullable()->after('call_type'); // 'mobile', 'landline', 'voip'
            $table->string('call_retry_count')->default(0)->after('driver_phone_type'); // Número de reintentos
            $table->timestamp('next_retry_at')->nullable()->after('call_retry_count'); // Próximo reintento programado
            
            // Metadatos adicionales
            $table->json('call_metadata')->nullable()->after('next_retry_at'); // Información adicional en JSON
            $table->text('internal_notes')->nullable()->after('call_metadata'); // Notas internas del sistema
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            $table->dropColumn([
                'call_status',
                'sip_status_code',
                'sip_status_message',
                'failure_reason',
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
                'internal_notes'
            ]);
        });
    }
};
