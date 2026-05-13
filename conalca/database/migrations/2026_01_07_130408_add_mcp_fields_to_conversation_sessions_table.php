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
        Schema::table('conversation_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('id')->index();
            $table->string('session_id')->nullable()->after('call_sid')->unique();
            
            // Agregar foreign key si la tabla clients existe
            if (Schema::hasTable('clients')) {
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_sessions', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'session_id']);
        });
    }
};
