<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert default setting using key-value system
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'specialty_show_stats'],
            ['value' => 'true', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'specialty_show_stats')->delete();
    }
};
