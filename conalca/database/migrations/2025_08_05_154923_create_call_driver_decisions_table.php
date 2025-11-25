<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('call_driver_decisions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cotizacion_model_id');
            $table->unsignedBigInteger('driver_id'); // Referencia a vehicle_owner_holder_driver.id

            $table->boolean('decision')->default(0); // 0 = REJECT, 1 = ACCEPT
            $table->timestamps();

            // Relaciones
            $table->foreign('cotizacion_model_id')
                  ->references('id')
                  ->on('cotizacion_models')
                  ->cascadeOnDelete();

            $table->foreign('driver_id')
                  ->references('id')
                  ->on('vehicle_owner_holder_driver')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_driver_decisions');
    }
};
