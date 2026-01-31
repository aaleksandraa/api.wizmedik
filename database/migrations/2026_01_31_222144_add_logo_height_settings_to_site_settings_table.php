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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->integer('logo_height_desktop')->default(70)->after('show_heart_icon_header');
            $table->integer('logo_height_mobile')->default(50)->after('logo_height_desktop');
            $table->integer('footer_logo_height_desktop')->default(70)->after('logo_height_mobile');
            $table->integer('footer_logo_height_mobile')->default(50)->after('footer_logo_height_desktop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_height_desktop', 'logo_height_mobile', 'footer_logo_height_desktop', 'footer_logo_height_mobile']);
        });
    }
};
