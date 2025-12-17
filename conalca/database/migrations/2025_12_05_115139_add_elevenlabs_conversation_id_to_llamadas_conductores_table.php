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
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            // Agregar campo para conversation_id de ElevenLabs
            $table->string('elevenlabs_conversation_id', 100)->nullable()->after('call_id');
            
            // Agregar campo para sip_call_id de ElevenLabs
            $table->string('elevenlabs_sip_call_id', 100)->nullable()->after('elevenlabs_conversation_id');
            
            // Índice para búsqueda rápida por conversation_id
            $table->index('elevenlabs_conversation_id', 'idx_elevenlabs_conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            $table->dropIndex('idx_elevenlabs_conversation_id');
            $table->dropColumn(['elevenlabs_conversation_id', 'elevenlabs_sip_call_id']);
        });
    }
};
