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
        Schema::table('cotizacion_models', function (Blueprint $table) {
            // Parámetros automáticos por tipo de modalidad
            $table->decimal('candado_satelital', 10, 2)->default(0)->after('valor')->comment('Costo del candado satelital para DTA/OTM');
            
            // Parámetros automáticos por tipo de carga - Refrigerado
            $table->decimal('jen_set', 10, 2)->default(0)->after('candado_satelital')->comment('Costo del jen set para carga refrigerada');
            $table->decimal('combustible', 10, 2)->default(0)->after('jen_set')->comment('Costo del combustible para carga refrigerada');
            
            // Parámetros automáticos por tipo de carga - Mercancía peligrosa
            $table->decimal('kit_derrames', 10, 2)->default(0)->after('combustible')->comment('Costo del kit de derrames para mercancía peligrosa');
            $table->decimal('pictogramas', 10, 2)->default(0)->after('kit_derrames')->comment('Costo de pictogramas para mercancía peligrosa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizacion_models', function (Blueprint $table) {
            $table->dropColumn([
                'candado_satelital',
                'jen_set', 
                'combustible',
                'kit_derrames',
                'pictogramas'
            ]);
        });
    }
};
