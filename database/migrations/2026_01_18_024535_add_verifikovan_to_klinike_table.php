<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('klinike', function (Blueprint $table) {
            // Add verification columns
            $table->boolean('verifikovan')->default(false)->after('aktivan');
            $table->timestamp('verifikovan_at')->nullable()->after('verifikovan');
            $table->foreignId('verifikovan_by')->nullable()->after('verifikovan_at')
                ->constrained('users')->onDelete('set null');
        });

        // Add indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_verifikovan ON klinike(verifikovan)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_aktivan_verifikovan ON klinike(aktivan, verifikovan)');

        // Set all existing clinics as verified
        DB::table('klinike')
            ->whereNull('deleted_at')
            ->update([
                'verifikovan' => true,
                'verifikovan_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS idx_klinike_verifikovan');
        DB::statement('DROP INDEX IF EXISTS idx_klinike_aktivan_verifikovan');

        Schema::table('klinike', function (Blueprint $table) {
            $table->dropForeign(['verifikovan_by']);
            $table->dropColumn(['verifikovan', 'verifikovan_at', 'verifikovan_by']);
        });
    }
};
