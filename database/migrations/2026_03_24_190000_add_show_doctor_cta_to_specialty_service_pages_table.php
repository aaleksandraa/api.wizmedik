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
        if (!Schema::hasColumn('specialty_service_pages', 'show_doctor_cta')) {
            Schema::table('specialty_service_pages', function (Blueprint $table) {
                $table->boolean('show_doctor_cta')->default(true)->after('is_indexable');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('specialty_service_pages', 'show_doctor_cta')) {
            Schema::table('specialty_service_pages', function (Blueprint $table) {
                $table->dropColumn('show_doctor_cta');
            });
        }
    }
};
