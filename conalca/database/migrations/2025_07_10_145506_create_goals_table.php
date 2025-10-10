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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_id')  // Asesor Comercial
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('boss_id')       // Jefe Comercial que asigna
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->year('year');
            $table->tinyInteger('month');      // 1-12
            $table->decimal('target_amount', 15, 2);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->enum('status', ['pending','achieved','failed'])->default('pending');
            $table->timestamps();

            $table->unique(['commercial_id','year','month']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
