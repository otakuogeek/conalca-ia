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
        Schema::create('percentage_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_percentage', 5, 2)->default(17.00);
            $table->decimal('avg_percentage', 5, 2)->default(24.00);
            $table->decimal('max_percentage', 5, 2)->default(32.00);
            $table->boolean('use_custom_percentages')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percentage_settings');
    }
};
