<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE laboratorije AS l
            SET aktivan = TRUE,
                verifikovan = TRUE,
                verifikovan_at = COALESCE(l.verifikovan_at, rr.reviewed_at, NOW())
            FROM registration_requests AS rr
            WHERE rr.status = 'approved'
              AND rr.laboratory_id = l.id
              AND (
                    l.aktivan IS DISTINCT FROM TRUE
                 OR l.verifikovan IS DISTINCT FROM TRUE
                 OR l.verifikovan_at IS NULL
              )
        ");

        DB::statement("
            UPDATE banje AS b
            SET aktivan = TRUE,
                verifikovan = TRUE
            FROM registration_requests AS rr
            WHERE rr.status = 'approved'
              AND rr.spa_id = b.id
              AND (
                    b.aktivan IS DISTINCT FROM TRUE
                 OR b.verifikovan IS DISTINCT FROM TRUE
              )
        ");

        DB::statement("
            UPDATE domovi_njega AS d
            SET aktivan = TRUE,
                verifikovan = TRUE
            FROM registration_requests AS rr
            WHERE rr.status = 'approved'
              AND rr.care_home_id = d.id
              AND (
                    d.aktivan IS DISTINCT FROM TRUE
                 OR d.verifikovan IS DISTINCT FROM TRUE
              )
        ");
    }

    public function down(): void
    {
        // Data backfill only. No rollback.
    }
};
