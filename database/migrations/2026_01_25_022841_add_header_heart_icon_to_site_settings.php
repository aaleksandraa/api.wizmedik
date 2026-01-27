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
        // Add show_heart_icon_header setting (use updateOrInsert to avoid duplicate key error)
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'show_heart_icon_header'],
            [
                'value' => '1', // Default enabled
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('site_settings')->where('key', 'show_heart_icon_header')->delete();
    }
};
