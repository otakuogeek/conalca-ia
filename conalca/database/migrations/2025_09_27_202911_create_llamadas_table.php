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
        Schema::create('llamadas', function (Blueprint $table) {
            $table->id('id_llamada');
            $table->unsignedBigInteger('id_cotizacion');
            $table->unsignedBigInteger('chofer_id');
            $table->string('status')->default('pendiente'); // pendiente, en_curso, finalizada, aceptada, rechazada
            $table->timestamp('fecha_llamada')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_cotizacion')->references('id')->on('cotizacion_models')->onDelete('cascade');
            $table->foreign('chofer_id')->references('id')->on('vehicle_owner_holder_driver')->onDelete('cascade');

            // Indexes
            $table->index(['id_cotizacion', 'status']);
            $table->index(['chofer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llamadas');
    }
};
