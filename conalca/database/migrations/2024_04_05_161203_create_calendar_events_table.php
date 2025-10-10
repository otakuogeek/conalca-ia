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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('client')->nullable();
            $table->date('date_start')->nullable();
            $table->time('hour_start')->nullable();
            $table->date('date_end')->nullable();
            $table->time('hour_end')->nullable();
            $table->string('description')->nullable();
            $table->string('notify')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
