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
        Schema::table('klinike', function (Blueprint $table) {
            $table->json('pauze')->nullable()->after('radno_vrijeme');
            $table->json('odmori')->nullable()->after('pauze');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('klinike', function (Blueprint $table) {
            $table->dropColumn(['pauze', 'odmori']);
        });
    }
};
