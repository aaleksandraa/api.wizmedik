<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add initiated_by column if it doesn't exist
        if (Schema::hasTable('klinika_doktor_zahtjevi') && !Schema::hasColumn('klinika_doktor_zahtjevi', 'initiated_by')) {
            Schema::table('klinika_doktor_zahtjevi', function (Blueprint $table) {
                $table->enum('initiated_by', ['clinic', 'doctor'])->default('clinic')->after('doktor_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('klinika_doktor_zahtjevi', 'initiated_by')) {
            Schema::table('klinika_doktor_zahtjevi', function (Blueprint $table) {
                $table->dropColumn('initiated_by');
            });
        }
    }
};
