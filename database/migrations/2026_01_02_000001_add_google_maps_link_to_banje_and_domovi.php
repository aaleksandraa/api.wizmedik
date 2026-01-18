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
        // Add google_maps_link to banje table
        Schema::table('banje', function (Blueprint $table) {
            $table->string('google_maps_link', 500)->nullable()->after('longitude');
        });

        // Add google_maps_link to domovi_njega table
        Schema::table('domovi_njega', function (Blueprint $table) {
            $table->string('google_maps_link', 500)->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banje', function (Blueprint $table) {
            $table->dropColumn('google_maps_link');
        });

        Schema::table('domovi_njega', function (Blueprint $table) {
            $table->dropColumn('google_maps_link');
        });
    }
};
