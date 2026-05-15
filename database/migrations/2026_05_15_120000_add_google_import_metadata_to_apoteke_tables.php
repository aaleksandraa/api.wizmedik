<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apoteke_firme', function (Blueprint $table) {
            if (!Schema::hasColumn('apoteke_firme', 'source')) {
                $table->string('source', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('apoteke_firme', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->index();
            }
            if (!Schema::hasColumn('apoteke_firme', 'imported_by')) {
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('apoteke_firme', 'import_batch')) {
                $table->string('import_batch', 120)->nullable()->index();
            }
        });

        Schema::table('apoteke_poslovnice', function (Blueprint $table) {
            if (!Schema::hasColumn('apoteke_poslovnice', 'google_place_id')) {
                $table->string('google_place_id', 255)->nullable()->unique();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'international_phone')) {
                $table->string('international_phone', 64)->nullable();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'opening_hours_json')) {
                $table->json('opening_hours_json')->nullable();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'source')) {
                $table->string('source', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->index();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'imported_by')) {
                $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('apoteke_poslovnice', 'import_batch')) {
                $table->string('import_batch', 120)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('apoteke_poslovnice', function (Blueprint $table) {
            foreach (['imported_by'] as $foreignColumn) {
                if (Schema::hasColumn('apoteke_poslovnice', $foreignColumn)) {
                    $table->dropConstrainedForeignId($foreignColumn);
                }
            }

            foreach ([
                'google_place_id',
                'international_phone',
                'opening_hours_json',
                'source',
                'imported_at',
                'import_batch',
            ] as $column) {
                if (Schema::hasColumn('apoteke_poslovnice', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('apoteke_firme', function (Blueprint $table) {
            foreach (['imported_by'] as $foreignColumn) {
                if (Schema::hasColumn('apoteke_firme', $foreignColumn)) {
                    $table->dropConstrainedForeignId($foreignColumn);
                }
            }

            foreach (['source', 'imported_at', 'import_batch'] as $column) {
                if (Schema::hasColumn('apoteke_firme', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
