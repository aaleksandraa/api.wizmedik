<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE doktori AS d
            SET aktivan = TRUE,
                verifikovan = TRUE,
                verifikovan_at = COALESCE(d.verifikovan_at, rr.reviewed_at, NOW()),
                verifikovan_by = COALESCE(d.verifikovan_by, rr.reviewed_by)
            FROM registration_requests AS rr
            WHERE rr.status = 'approved'
              AND rr.doctor_id = d.id
              AND (
                    d.aktivan IS DISTINCT FROM TRUE
                 OR d.verifikovan IS DISTINCT FROM TRUE
                 OR d.verifikovan_at IS NULL
              )
        ");

        DB::statement("
            UPDATE klinike AS k
            SET aktivan = TRUE,
                verifikovan = TRUE,
                verifikovan_at = COALESCE(k.verifikovan_at, rr.reviewed_at, NOW()),
                verifikovan_by = COALESCE(k.verifikovan_by, rr.reviewed_by)
            FROM registration_requests AS rr
            WHERE rr.status = 'approved'
              AND rr.clinic_id = k.id
              AND (
                    k.aktivan IS DISTINCT FROM TRUE
                 OR k.verifikovan IS DISTINCT FROM TRUE
                 OR k.verifikovan_at IS NULL
              )
        ");
    }

    public function down(): void
    {
        // Data backfill only. No rollback.
    }
};
