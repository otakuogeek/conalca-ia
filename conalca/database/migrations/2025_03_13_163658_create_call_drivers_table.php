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
        Schema::create('call_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id'); // Foreign key to calls
            $table->unsignedBigInteger('driver_id'); // Foreign key to drivers
            $table->string('driver_name');
            $table->string('driver_phone_number');
            $table->string('type_vehicle');
            $table->string('response');
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_drivers');
    }
};
