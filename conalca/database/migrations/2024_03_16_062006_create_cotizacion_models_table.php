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
        Schema::create('cotizacion_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_id')->constrained();
            $table->string('porcentaje')->nullable();
            $table->string('ciudad_origen')->nullable();
            $table->string('ciudad_destino')->nullable();
            $table->string('ciudad_origen_dane')->nullable();
            $table->string('ciudad_destino_dane')->nullable();
            $table->string('peso_mercancia')->nullable();
            $table->string('cantidad')->nullable();
            $table->string('tipo_embajale')->nullable();
            $table->string('dimensiones_exactas')->nullable();
            $table->string('registro_fotografico')->nullable();
            $table->string('planos')->nullable();
            $table->string('tipo_producto')->nullable();
            $table->string('temperatura_mercancia')->nullable();
            $table->string('humedad')->nullable();
            $table->string('vehiculo_requerido')->nullable();
            $table->string('regimen_nacionalizado')->nullable();
            $table->string('agente_aduanas')->nullable();
            $table->string('descargue_cargue')->nullable();
            $table->string('consolidado_expreso')->nullable();
            $table->string('fcl_lcl')->nullable();
            $table->string('sitio_devolucion_contenedor')->nullable();
            $table->string('numero_documento_bl')->nullable();
            $table->string('fecha_hora_descargue_cargue')->nullable();
            $table->string('cantidad_vh')->nullable();
            $table->string('un')->nullable();
            $table->string('ruta')->nullable();
            $table->string('frecuencia')->nullable();
            $table->string('esquema_seguridad')->nullable();
            $table->string('tipo_carroceria')->nullable();
            $table->string('valor')->nullable();
            $table->string('valor_declarado')->nullable();
            $table->string('tipo_mercancia')->nullable();
            $table->string('ventanas_horarios_recibidos')->nullable();
            $table->string('seguro')->nullable();
            $table->string('silogtran_status')->nullable();
            $table->string('tipo')->nullable();
            $table->string('operation_type')->nullable();
            $table->string('selected_driver')->nullable();
            $table->boolean('active')->default(false);
            $table->enum('decision_cliente', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->unsignedBigInteger('group_cotization_id')->nullable();
            $table->unsignedInteger('selected_driver_id')->nullable();
            $table->integer('itesoltra_vehiculoacompanamiento')->nullable();
            $table->string('tipaco_codigo')->nullable();
            $table->string('itesoltra_acompanamientocuentade')->nullable();
            $table->decimal('itesoltra_acompanamientovalor', 15, 2)->nullable();

            
            $table->foreign('group_cotization_id')->references('id')->on('group_cotizations');

            $table->foreign('selected_driver_id')
                ->references('id')
                ->on('vehicle_owner_holder_driver')
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_models');
    }
};
