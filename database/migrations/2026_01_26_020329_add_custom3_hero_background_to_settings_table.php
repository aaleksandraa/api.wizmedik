<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('custom3_hero_bg_enabled')->default(false)->after('doctors_split_view_enabled');
            $table->string('custom3_hero_bg_image')->nullable()->after('custom3_hero_bg_enabled');
            $table->integer('custom3_hero_bg_opacity')->default(20)->after('custom3_hero_bg_image'); // 0-100
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['custom3_hero_bg_enabled', 'custom3_hero_bg_image', 'custom3_hero_bg_opacity']);
        });
    }
};
