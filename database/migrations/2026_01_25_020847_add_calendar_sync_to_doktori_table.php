<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->string('calendar_sync_token', 64)->nullable()->unique()->after('prihvata_online');
            $table->boolean('calendar_sync_enabled')->default(false)->after('calendar_sync_token');
            $table->string('google_calendar_url')->nullable()->after('calendar_sync_enabled');
            $table->string('outlook_calendar_url')->nullable()->after('google_calendar_url');
            $table->timestamp('calendar_last_synced')->nullable()->after('outlook_calendar_url');
        });
    }

    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn([
                'calendar_sync_token',
                'calendar_sync_enabled',
                'google_calendar_url',
                'outlook_calendar_url',
                'calendar_last_synced'
            ]);
        });
    }
};
