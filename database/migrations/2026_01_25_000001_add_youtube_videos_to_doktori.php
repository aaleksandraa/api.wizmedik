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
            $table->json('youtube_linkovi')->nullable()->after('opis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn('youtube_linkovi');
        });
    }
};
