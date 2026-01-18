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
        Schema::table('termini', function (Blueprint $table) {
            if (!Schema::hasColumn('termini', 'klinika_id')) {
                $table->foreignId('klinika_id')->nullable()->after('gostovanje_id')->constrained('klinike')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('termini', function (Blueprint $table) {
            $table->dropForeign(['klinika_id']);
            $table->dropColumn('klinika_id');
        });
    }
};
