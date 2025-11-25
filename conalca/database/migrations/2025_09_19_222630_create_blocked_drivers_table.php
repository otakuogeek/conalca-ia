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
        Schema::create('blocked_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('driver_name')->nullable();
            $table->text('block_reason')->nullable();
            $table->boolean('is_blocked')->default(true);
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamps();
            
            $table->index('phone_number');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_drivers');
    }
};
