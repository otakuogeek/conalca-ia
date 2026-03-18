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
        Schema::table('solicitud_transporte_cargues', function (Blueprint $table) {
            $table->string('tiempo_cargue_pactado')->nullable()->after('hora_cargue');
            $table->date('fecha_cita_descargue')->nullable()->after('tiempo_cargue_pactado');
            $table->string('hora_cita_descargue')->nullable()->after('fecha_cita_descargue');
            $table->string('tiempo_descargue_pactado')->nullable()->after('hora_cita_descargue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_transporte_cargues', function (Blueprint $table) {
            $table->dropColumn([
                'tiempo_cargue_pactado',
                'fecha_cita_descargue',
                'hora_cita_descargue',
                'tiempo_descargue_pactado',
            ]);
        });
    }
};
