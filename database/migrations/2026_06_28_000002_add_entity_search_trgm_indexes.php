<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds pg_trgm GIN indexes for the doctor and clinic search.
 *
 * Doktor::scopeSearch() and ClinicController::index() use leading-wildcard
 * ILIKE '%term%' on these columns, which a B-tree index cannot serve. A
 * trigram GIN index on the column lets Postgres index those ILIKE scans.
 *
 * Indexes are created CONCURRENTLY (no write lock) and IF NOT EXISTS. Nothing
 * here modifies or deletes data; it is purely additive.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * indexName => [table, column]
     */
    private array $indexes = [
        'doktori_ime_trgm_idx' => ['doktori', 'ime'],
        'doktori_prezime_trgm_idx' => ['doktori', 'prezime'],
        'doktori_specijalnost_trgm_idx' => ['doktori', 'specijalnost'],
        'doktori_grad_trgm_idx' => ['doktori', 'grad'],
        'klinike_naziv_trgm_idx' => ['klinike', 'naziv'],
        'klinike_opis_trgm_idx' => ['klinike', 'opis'],
        'klinike_adresa_trgm_idx' => ['klinike', 'adresa'],
        'klinike_grad_trgm_idx' => ['klinike', 'grad'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->indexes as $indexName => [$table, $column]) {
            DB::statement(sprintf(
                'CREATE INDEX CONCURRENTLY IF NOT EXISTS %s ON %s USING gin (%s gin_trgm_ops)',
                $indexName,
                $table,
                $column
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys($this->indexes) as $indexName) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ' . $indexName);
        }
    }
};
