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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id'); // Foreign key to quotations
            $table->integer('total_drivers')->default(0);
            $table->integer('calls_made')->default(0);
            $table->integer('calls_accepted')->default(0);
            $table->integer('calls_not_answered')->default(0);
            $table->string('status');
            $table->timestamps();
            $table->foreign('quotation_id')->references('id')->on('cotizacion_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
