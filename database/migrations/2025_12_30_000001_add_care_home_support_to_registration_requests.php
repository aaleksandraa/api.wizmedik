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
        // For PostgreSQL, we need to alter the type differently
        // First, check if the type column is an enum or varchar
        // In PostgreSQL, Laravel typically uses varchar for enum-like columns

        // Add care_home_id column
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->foreignId('care_home_id')->nullable()->after('spa_id')->constrained('domovi_njega')->onDelete('cascade');
        });

        // For PostgreSQL, the type column is likely a varchar, so we just need to ensure
        // the application accepts 'care_home' as a valid value (which it will since it's a string)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->dropForeign(['care_home_id']);
            $table->dropColumn('care_home_id');
        });
    }
};
