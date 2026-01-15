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
        Schema::table('solicitud_transporte_detalles', function (Blueprint $table) {
            $table->string('lugar_recogida_contenedor')->nullable()->after('ciudad_intermedia');
            $table->string('tipo_carga')->nullable()->after('lugar_recogida_contenedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_transporte_detalles', function (Blueprint $table) {
            $table->dropColumn(['lugar_recogida_contenedor', 'tipo_carga']);
        });
    }
};
