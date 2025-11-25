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
        Schema::create('solicitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('pricing_id')->nullable()->constrained('pricings');
            $table->enum('importance', ['LOW', 'MEDIUM', 'HIGH'])->default('LOW');
            $table->enum('status', [
                'PENDING',      // created by commercial
                'IN_PROCESS',   // handled by pricing
                'ANSWERED',     // answered by super-admin
                'FINALIZED',    // closed by pricing
                'SENT',         // Comercial pushed to Quotations
                'REJECTED'     // explicitly rejected
            ])->default('PENDING');
            $table->timestamp('support_requested_at')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->text('description')->nullable();
            $table->double('price')->nullable();   
            $table->text('pricing_note')->nullable();
            $table->text('superadmin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitations');
    }
};
