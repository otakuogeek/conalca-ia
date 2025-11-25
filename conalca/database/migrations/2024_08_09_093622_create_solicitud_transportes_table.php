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
        Schema::create('solicitud_transportes', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_codigo', 10)->nullable();
            $table->string('centro_costo_despacho')->nullable();
            $table->string('tipo_viaje')->nullable();
            $table->string('moneda')->nullable();
            $table->string('fuente_solicitud')->nullable();
            $table->string('observacion')->nullable();
            $table->string('condicion_despacho')->nullable();
            $table->string('condicion_facturacion')->nullable();
            $table->string('observacion_remesa')->nullable();
            $table->string('tipo_imagen')->nullable();
            $table->string('recomendacion_trafico')->nullable();
            $table->string('ciudad_facturacion')->nullable();
            $table->string('vendedor')->nullable();
            $table->string('cliente_final')->nullable();
            $table->string('tipo_operacion')->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->string('instrucciones_servicio')->nullable();
            $table->string('maersk_numero_viaje')->nullable();
            $table->string('solicitud_servicio')->nullable();
            $table->string('mostrar_digitalizados_vehiculos')->nullable();
            $table->string('mostrar_digitalizados_conductor')->nullable();
            $table->string('usuario_autorizado')->nullable();
            $table->string('empresa')->nullable();
            $table->string('usuario')->nullable();
            $table->json('steps_completed')->nullable();
            $table->string('estado')->default('incompleta');
            $table->unsignedBigInteger('cotizacion_model_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transportes');
    }
};
