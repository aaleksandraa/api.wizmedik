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
        Schema::table('banja_paketi', function (Blueprint $table) {
            $table->integer('redoslijed')->default(0)->after('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banja_paketi', function (Blueprint $table) {
            $table->dropColumn('redoslijed');
        });
    }
};
