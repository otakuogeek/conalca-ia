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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('openai_thread_id')->nullable();
            $table->string('openai_current_run')->nullable();
            $table->string('nit');
            $table->string('sector');
            $table->text('address');
            $table->string('email');
            $table->string('position');
            $table->string('contact');
            $table->string('phone');
            $table->string('city');
            $table->string('main_contact');
            $table->string('contact_title');
            $table->text('address_2');
            $table->string('email_2');
            $table->string('document');
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->decimal('projected_value');
            $table->date('date');
            $table->string('city_2');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
