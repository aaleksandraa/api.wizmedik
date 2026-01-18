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
        Schema::table('paketi_analiza', function (Blueprint $table) {
            $table->boolean('prikazi_ustedite')->default(true)->after('ustedite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paketi_analiza', function (Blueprint $table) {
            $table->dropColumn('prikazi_ustedite');
        });
    }
};
