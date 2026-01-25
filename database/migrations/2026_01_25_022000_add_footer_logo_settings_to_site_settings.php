<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Footer logo settings
            $table->string('footer_logo_url')->nullable()->after('logo_type');
            $table->boolean('footer_logo_enabled')->default(true)->after('footer_logo_url');
            $table->enum('footer_logo_type', ['image', 'text'])->default('text')->after('footer_logo_enabled');

            // Heart icon before logo
            $table->boolean('show_heart_icon')->default(true)->after('footer_logo_type');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_logo_url',
                'footer_logo_enabled',
                'footer_logo_type',
                'show_heart_icon'
            ]);
        });
    }
};
