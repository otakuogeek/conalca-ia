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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('position')->nullable()->after('phone');
            $table->string('department')->nullable()->after('position');
            $table->text('bio')->nullable()->after('department');
            $table->string('profile_photo')->nullable()->after('bio');
            $table->string('language')->default('es')->after('profile_photo');
            $table->string('timezone')->default('America/Bogota')->after('language');
            $table->boolean('dark_mode')->default(false)->after('timezone');
            $table->boolean('email_notifications')->default(true)->after('dark_mode');
            $table->boolean('push_notifications')->default(false)->after('email_notifications');
            $table->boolean('system_updates')->default(true)->after('push_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'position',
                'department',
                'bio',
                'profile_photo',
                'language',
                'timezone',
                'dark_mode',
                'email_notifications',
                'push_notifications',
                'system_updates'
            ]);
        });
    }
};
