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
        Schema::create('solicitud_transporte_acom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('vehiculo_acom')->nullable();   // HAVE TO DELETE
            $table->string('tipo_vehiculo_acom')->nullable();   // HAVE TO DELETE
            $table->string('acompanamiento_cuenta_acom')->nullable();   // HAVE TO DELETE
            $table->string('valor_acompanante_acom')->nullable(); // HAVE TO DELETE
            $table->unsignedInteger('itesoltra_vehiculoacompanamiento')->nullable();
            $table->string('tipaco_codigo')->nullable();              // MOTORIZADO | VEHICULAR | CABINA
            $table->string('itesoltra_acompanamientocuentade')->nullable(); // CLIENTE | EMPRESA
            $table->bigInteger('itesoltra_acompanamientovalor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_acompanamientos');
    }
};
