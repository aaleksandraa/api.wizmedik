<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('registration_requests', 'pharmacy_firm_id')) {
                $table->foreignId('pharmacy_firm_id')
                    ->nullable()
                    ->after('care_home_id')
                    ->constrained('apoteke_firme')
                    ->nullOnDelete();
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE registration_requests DROP CONSTRAINT IF EXISTS registration_requests_type_check');
            DB::statement('ALTER TABLE registration_requests ALTER COLUMN type TYPE VARCHAR(30)');
            DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic', 'laboratory', 'spa', 'care_home', 'pharmacy'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE registration_requests MODIFY COLUMN type ENUM('doctor', 'clinic', 'laboratory', 'spa', 'care_home', 'pharmacy') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            if (Schema::hasColumn('registration_requests', 'pharmacy_firm_id')) {
                $table->dropForeign(['pharmacy_firm_id']);
                $table->dropColumn('pharmacy_firm_id');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE registration_requests DROP CONSTRAINT IF EXISTS registration_requests_type_check');
            DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic', 'laboratory', 'spa', 'care_home'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE registration_requests MODIFY COLUMN type ENUM('doctor', 'clinic', 'laboratory', 'spa', 'care_home') NOT NULL");
        }
    }
};

