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
        Schema::table('driver_call_responses', function (Blueprint $table) {
            $table->string('elevenlabs_conversation_id')->nullable()->after('twilio_call_sid');
            $table->string('elevenlabs_sip_call_id')->nullable()->after('elevenlabs_conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_call_responses', function (Blueprint $table) {
            $table->dropColumn(['elevenlabs_conversation_id', 'elevenlabs_sip_call_id']);
        });
    }
};
