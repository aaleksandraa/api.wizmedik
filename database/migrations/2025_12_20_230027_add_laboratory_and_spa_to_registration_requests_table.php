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
        // For PostgreSQL, we need to drop and recreate the type constraint
        DB::statement("ALTER TABLE registration_requests DROP CONSTRAINT IF EXISTS registration_requests_type_check");
        DB::statement("ALTER TABLE registration_requests ALTER COLUMN type TYPE VARCHAR(20)");

        Schema::table('registration_requests', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('registration_requests', 'laboratory_id')) {
                $table->foreignId('laboratory_id')->nullable()->constrained('laboratorije')->onDelete('cascade');
            }
            if (!Schema::hasColumn('registration_requests', 'spa_id')) {
                $table->foreignId('spa_id')->nullable()->constrained('banje')->onDelete('cascade');
            }
            if (!Schema::hasColumn('registration_requests', 'regija')) {
                $table->string('regija')->nullable();
            }
        });

        // Add check constraint for type
        DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic', 'laboratory', 'spa'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            // Drop foreign keys and columns if they exist
            if (Schema::hasColumn('registration_requests', 'laboratory_id')) {
                $table->dropForeign(['laboratory_id']);
                $table->dropColumn('laboratory_id');
            }
            if (Schema::hasColumn('registration_requests', 'spa_id')) {
                $table->dropForeign(['spa_id']);
                $table->dropColumn('spa_id');
            }
            if (Schema::hasColumn('registration_requests', 'regija')) {
                $table->dropColumn('regija');
            }
        });

        // Revert type constraint
        DB::statement("ALTER TABLE registration_requests DROP CONSTRAINT IF EXISTS registration_requests_type_check");
        DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic'))");
    }
};
