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
        Schema::create('pricings', function (Blueprint $table) {
            $table->id();
            // corto urbanos ipiales
            $table->string('documents')->nullable();
            $table->string('vehicle_type');
            $table->string('extra')->nullable();
            $table->string('price_extra')->nullable();
            $table->string('price_extra2')->nullable();
            $table->string('download_target')->nullable();
            $table->string('load_target')->nullable();
            $table->string('price_person')->nullable();
            // costo san miguel
            $table->string('event')->nullable();
            $table->string('type_send')->nullable();
            $table->string('save_box')->nullable();
            $table->string('time_day')->nullable();
            $table->string('return')->nullable();
            $table->string('container')->nullable();
            $table->string('complements')->nullable();
            $table->string('download_price')->nullable();
            $table->string('iva')->nullable();
            $table->string('person_download')->nullable();
            // costo peru
            $table->string('rent')->nullable();
            $table->string('download')->nullable();
            $table->string('load')->nullable();
            $table->string('store')->nullable();
            $table->string('scales')->nullable();
            $table->string('time')->nullable();
            $table->string('weight');
            // costo trueca tulcan
            $table->string('vehicle_extra')->nullable();
            $table->string('download_destiny')->nullable();
            $table->string('download_destiny_iva')->nullable();
            $table->string('price_complements')->nullable();
            $table->string('price_documents')->nullable();
            $table->string('load_tulan')->nullable();
            // costo bogota dedicados
            $table->string('volume')->nullable();
            $table->double('price_month')->nullable();
            $table->double('price_aux_month')->nullable();
            $table->double('price_week')->nullable();
            $table->double('price_aux_week')->nullable();
            $table->double('price_day')->nullable();
            $table->double('price_aux_day')->nullable();
            $table->string('condition')->nullable();

            $table->string('weight_from')->nullable();
            $table->string('weight_to')->nullable();

            $table->string('type_pricing')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->string('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricings');
    }
};
