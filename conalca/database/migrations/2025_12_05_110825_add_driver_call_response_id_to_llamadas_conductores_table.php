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
            // Agregar columna para relacionar con driver_call_responses
            $table->unsignedBigInteger('driver_call_response_id')->nullable()->after('call_id');
            
            // Agregar índice para mejorar performance
            $table->index('driver_call_response_id');
            
            // Agregar foreign key (opcional, comentada por si hay problemas de integridad)
            // $table->foreign('driver_call_response_id')
            //       ->references('id')
            //       ->on('driver_call_responses')
            //       ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llamadas_conductores', function (Blueprint $table) {
            // Eliminar foreign key si existe
            // $table->dropForeign(['driver_call_response_id']);
            
            // Eliminar índice y columna
            $table->dropIndex(['driver_call_response_id']);
            $table->dropColumn('driver_call_response_id');
        });
    }
};
