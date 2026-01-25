<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('id');
            $table->boolean('logo_enabled')->default(true)->after('logo_url');
            $table->enum('logo_type', ['image', 'text'])->default('text')->after('logo_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'logo_enabled', 'logo_type']);
        });
    }
};
