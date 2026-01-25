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
        Schema::table('usluge', function (Blueprint $table) {
            if (!Schema::hasColumn('usluge', 'kategorija_id')) {
                $table->foreignId('kategorija_id')->nullable()->after('doktor_id')->constrained('doktor_kategorije_usluga')->onDelete('set null');
                $table->index('kategorija_id');
            }

            if (!Schema::hasColumn('usluge', 'redoslijed')) {
                $table->integer('redoslijed')->default(0)->after('aktivan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usluge', function (Blueprint $table) {
            if (Schema::hasColumn('usluge', 'kategorija_id')) {
                $table->dropForeign(['kategorija_id']);
                $table->dropColumn('kategorija_id');
            }

            if (Schema::hasColumn('usluge', 'redoslijed')) {
                $table->dropColumn('redoslijed');
            }
        });
    }
};
