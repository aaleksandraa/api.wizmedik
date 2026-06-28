<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds pg_trgm GIN indexes to accelerate the drug search.
 *
 * Lijek::scopeSearch() runs leading-wildcard, case-insensitive matches:
 *   LOWER(COALESCE(<col>, '')) LIKE '%term%'
 * across naziv, naziv_lijeka, brend, atc_sifra and inn. A standard B-tree
 * index cannot serve a leading-wildcard LIKE, so these scans are sequential
 * and get slower as the drug catalogue grows. A trigram (pg_trgm) GIN index
 * on the exact lowered expression lets Postgres use the index for those LIKEs.
 *
 * The indexes are created CONCURRENTLY so existing data and live traffic are
 * not blocked while they build. Nothing here modifies or deletes data.
 */
return new class extends Migration
{
    /**
     * CONCURRENTLY index creation cannot run inside a transaction.
     */
    public $withinTransaction = false;

    /**
     * The lowered expressions must match Lijek::scopeSearch() exactly so the
     * planner can use the index.
     */
    private array $expressions = [
        'lijekovi_naziv_trgm_idx' => "lower(COALESCE(naziv, ''))",
        'lijekovi_naziv_lijeka_trgm_idx' => "lower(COALESCE(naziv_lijeka, ''))",
        'lijekovi_brend_trgm_idx' => "lower(COALESCE(brend, ''))",
        'lijekovi_atc_sifra_trgm_idx' => "lower(COALESCE(atc_sifra, ''))",
        'lijekovi_inn_trgm_idx' => "lower(COALESCE(inn, ''))",
    ];

    public function up(): void
    {
        // Trigram indexes are PostgreSQL-specific.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->expressions as $indexName => $expression) {
            DB::statement(sprintf(
                'CREATE INDEX CONCURRENTLY IF NOT EXISTS %s ON lijekovi USING gin ((%s) gin_trgm_ops)',
                $indexName,
                $expression
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys($this->expressions) as $indexName) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ' . $indexName);
        }
    }
};
