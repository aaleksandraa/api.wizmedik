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
        Schema::table('doktori', function (Blueprint $table) {
            $table->boolean('telemedicine_enabled')->default(false)->after('prihvata_ostalo');
            $table->string('telemedicine_phone', 50)->nullable()->after('telemedicine_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn(['telemedicine_enabled', 'telemedicine_phone']);
        });
    }
};
