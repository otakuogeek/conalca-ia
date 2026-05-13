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
        Schema::table('conversation_sessions', function (Blueprint $table) {
            // Hacer nullable los campos relacionados con llamadas telefónicas
            // para permitir sesiones de chat sin contexto de llamada
            $table->string('call_sid', 191)->nullable()->default(null)->change();
            $table->unsignedBigInteger('cotizacion_id')->nullable()->default(null)->change();
            $table->unsignedBigInteger('driver_id')->nullable()->default(null)->change();
            $table->string('driver_phone', 191)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_sessions', function (Blueprint $table) {
            // Revertir los cambios (opcional, puede causar problemas si hay datos)
            $table->string('call_sid', 191)->nullable(false)->change();
            $table->unsignedBigInteger('cotizacion_id')->nullable(false)->change();
            $table->unsignedBigInteger('driver_id')->nullable(false)->change();
            $table->string('driver_phone', 191)->nullable(false)->change();
        });
    }
};
