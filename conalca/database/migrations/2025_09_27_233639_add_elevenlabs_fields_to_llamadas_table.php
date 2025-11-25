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
        Schema::table('llamadas', function (Blueprint $table) {
            $table->string('elevenlabs_conversation_id')->nullable()->after('status');
            $table->string('elevenlabs_sip_call_id')->nullable()->after('elevenlabs_conversation_id');
            $table->timestamp('call_started_at')->nullable()->after('elevenlabs_sip_call_id');
            $table->timestamp('call_ended_at')->nullable()->after('call_started_at');
            $table->text('call_notes')->nullable()->after('call_ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llamadas', function (Blueprint $table) {
            $table->dropColumn([
                'elevenlabs_conversation_id',
                'elevenlabs_sip_call_id',
                'call_started_at',
                'call_ended_at',
                'call_notes'
            ]);
        });
    }
};
