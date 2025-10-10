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
        Schema::create('client_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            $table->string('file_path');
            $table->string('category')->default('general');
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index('client_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_files');
    }
};
