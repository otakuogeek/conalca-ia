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
        Schema::create('solicitud_transporte_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_transporte_id')->constrained();
            $table->string('origen')->nullable();
            $table->string('destino')->nullable();
            $table->string('ciudad_intermedia')->nullable();
            $table->string('cantidad_mercancia')->nullable();
            $table->string('peso')->nullable();
            $table->string('peso_despachado')->nullable();
            $table->string('producto')->nullable();
            $table->string('empaque')->nullable();
            $table->string('cantidad_vehiculos')->nullable();
            $table->string('cantidad_vehiculos_despachados')->nullable();
            $table->string('clase_vehiculo')->nullable();
            $table->string('carroceria')->nullable();
            $table->string('minimo_modelo')->nullable();
            $table->string('tipo_flete')->nullable();
            $table->string('flete_conductor')->nullable();
            $table->string('flete_ministerio')->nullable();
            $table->string('tipo_tarifa')->nullable();
            $table->string('tarifa_cliente')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('valor_mercancia')->nullable();
            $table->string('restricciones_cliente')->nullable();
            $table->string('cargue_cuenta_de')->nullable();
            $table->string('descargue_cuenta_de')->nullable();
            $table->string('seguro_cuenta_de')->nullable();
            $table->string('descripcion_mercancia')->nullable();
            $table->string('servicio_escolta')->nullable();
            $table->string('kit_seguridad')->nullable();
            $table->string('kit_cinchas')->nullable();
            $table->string('guia_acompanamiento')->nullable();
            $table->string('observacion_detalle')->nullable();
            $table->string('sub_cliente')->nullable();
            $table->string('guia_despacho')->nullable();
            $table->string('numero_viaje')->nullable();
            $table->string('numero_pedido')->nullable();
            $table->string('nota_entrega')->nullable();
            $table->string('planilla_entrega')->nullable();
            $table->string('numero_factura')->nullable();
            $table->string('modelo')->nullable();
            $table->string('qty')->nullable();
            $table->string('t_cbm')->nullable();
            $table->string('order_no')->nullable();
            $table->string('o_c_cliente')->nullable();
            $table->string('bodega')->nullable();
            $table->date('fecha_expiracion')->nullable();
            $table->string('net_amt_txn')->nullable();
            $table->string('prec_unit')->nullable();
            $table->string('remark')->nullable();
            $table->string('addr')->nullable();
            $table->date('appoint_date')->nullable();
            $table->string('tipo_vehiculo')->nullable();
            $table->string('item')->nullable();
            $table->string('tipo_remesa_rndc')->nullable();
            $table->string('load')->nullable();
            $table->foreignId('cotizacion_model_id')->nullable()->constrained('cotizacion_models')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_transporte_detalles');
    }
};
