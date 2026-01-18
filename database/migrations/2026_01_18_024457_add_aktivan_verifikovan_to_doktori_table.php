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
        Schema::table('doktori', function (Blueprint $table) {
            // Add status columns
            $table->boolean('aktivan')->default(true)->after('slug');
            $table->boolean('verifikovan')->default(false)->after('aktivan');
            $table->timestamp('verifikovan_at')->nullable()->after('verifikovan');
            $table->foreignId('verifikovan_by')->nullable()->after('verifikovan_at')
                ->constrained('users')->onDelete('set null');
        });

        // Add indexes for performance
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_aktivan ON doktori(aktivan)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_verifikovan ON doktori(verifikovan)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_aktivan_verifikovan ON doktori(aktivan, verifikovan)');

        // Set all existing doctors as active and verified
        DB::table('doktori')
            ->whereNull('deleted_at')
            ->update([
                'aktivan' => true,
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
        DB::statement('DROP INDEX IF EXISTS idx_doktori_aktivan');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_verifikovan');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_aktivan_verifikovan');

        Schema::table('doktori', function (Blueprint $table) {
            $table->dropForeign(['verifikovan_by']);
            $table->dropColumn(['aktivan', 'verifikovan', 'verifikovan_at', 'verifikovan_by']);
        });
    }
};
