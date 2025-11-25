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
        Schema::table('group_cotizations', function (Blueprint $table) {
            // Parámetros automáticos según tipo de modalidad
            $table->boolean('candado_satelital')->default(false)->after('type');
            
            // Parámetros automáticos según tipo de carga
            $table->boolean('jen_set')->default(false)->after('candado_satelital');
            $table->boolean('combustible')->default(false)->after('jen_set');
            $table->boolean('kit_derrames')->default(false)->after('combustible');
            $table->boolean('pictogramas')->default(false)->after('kit_derrames');
            
            // Tipo de carga seleccionado
            $table->string('cargo_type')->nullable()->after('pictogramas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_cotizations', function (Blueprint $table) {
            $table->dropColumn([
                'candado_satelital',
                'jen_set', 
                'combustible',
                'kit_derrames',
                'pictogramas',
                'cargo_type'
            ]);
        });
    }
};
