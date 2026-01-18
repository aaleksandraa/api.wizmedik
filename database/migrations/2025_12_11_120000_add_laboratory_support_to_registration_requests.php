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
        // Add laboratory to type enum
        DB::statement("ALTER TABLE registration_requests DROP CONSTRAINT registration_requests_type_check");
        DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic', 'laboratory'))");

        // Add laboratory_id foreign key
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->foreignId('laboratory_id')->nullable()->after('clinic_id')->constrained('laboratorije')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->dropForeign(['laboratory_id']);
            $table->dropColumn('laboratory_id');
        });

        // Revert type enum
        DB::statement("ALTER TABLE registration_requests DROP CONSTRAINT registration_requests_type_check");
        DB::statement("ALTER TABLE registration_requests ADD CONSTRAINT registration_requests_type_check CHECK (type IN ('doctor', 'clinic'))");
    }
};
