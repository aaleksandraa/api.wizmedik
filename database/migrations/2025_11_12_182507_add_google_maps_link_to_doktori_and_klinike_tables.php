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
            $table->text('google_maps_link')->nullable()->after('longitude');
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->text('google_maps_link')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn('google_maps_link');
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->dropColumn('google_maps_link');
        });
    }
};
