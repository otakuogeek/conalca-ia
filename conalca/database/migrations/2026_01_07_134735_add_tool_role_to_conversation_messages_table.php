<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para incluir 'tool'
        DB::statement("ALTER TABLE conversation_messages MODIFY role ENUM('user', 'assistant', 'system', 'tool') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM original
        DB::statement("ALTER TABLE conversation_messages MODIFY role ENUM('user', 'assistant', 'system') NOT NULL");
    }
};
