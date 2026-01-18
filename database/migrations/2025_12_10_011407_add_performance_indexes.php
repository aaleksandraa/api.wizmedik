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
        // Termini table indexes for booking performance
        Schema::table('termini', function (Blueprint $table) {
            $table->index(['doktor_id', 'datum_vrijeme'], 'idx_termini_booking');
            $table->index(['status', 'datum_vrijeme'], 'idx_termini_status_date');
            $table->index('created_at', 'idx_termini_created');
        });

        // Doktori table indexes
        Schema::table('doktori', function (Blueprint $table) {
            $table->index('specijalnost_id', 'idx_doktori_specialty');
            $table->index('slug', 'idx_doktori_slug');
        });

        // Klinike table indexes
        Schema::table('klinike', function (Blueprint $table) {
            $table->index('aktivan', 'idx_klinike_active');
            $table->index('slug', 'idx_klinike_slug');
        });

        // Pitanja table indexes
        Schema::table('pitanja', function (Blueprint $table) {
            $table->index(['specijalnost_id', 'je_odgovoreno'], 'idx_pitanja_specialty_answered');
            $table->index('slug', 'idx_pitanja_slug');
            $table->index('created_at', 'idx_pitanja_created');
        });

        // Odgovori table indexes
        Schema::table('odgovori_na_pitanja', function (Blueprint $table) {
            $table->index(['pitanje_id', 'created_at'], 'idx_odgovori_pitanje');
            $table->index('doktor_id', 'idx_odgovori_doktor');
        });

        // Recenzije table indexes
        Schema::table('recenzije', function (Blueprint $table) {
            $table->index(['recenziran_type', 'recenziran_id'], 'idx_recenzije_recenziran');
            $table->index('created_at', 'idx_recenzije_created');
        });

        // Gostovanja table indexes
        Schema::table('klinika_doktor_gostovanja', function (Blueprint $table) {
            $table->index(['doktor_id', 'datum'], 'idx_gostovanja_doktor_date');
            $table->index(['klinika_id', 'datum'], 'idx_gostovanja_klinika_date');
            $table->index('status', 'idx_gostovanja_status');
        });

        // Notifikacije table indexes
        Schema::table('notifikacije', function (Blueprint $table) {
            $table->index(['user_id', 'procitano', 'created_at'], 'idx_notifikacije_user');
            $table->index('created_at', 'idx_notifikacije_created');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
        });

        // Add full-text search indexes for PostgreSQL only (skip for SQLite testing)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_fulltext ON doktori USING gin(to_tsvector(\'simple\', coalesce(ime, \'\') || \' \' || coalesce(prezime, \'\')))');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_fulltext ON klinike USING gin(to_tsvector(\'simple\', coalesce(naziv, \'\') || \' \' || coalesce(adresa, \'\')))');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_pitanja_fulltext ON pitanja USING gin(to_tsvector(\'simple\', coalesce(naslov, \'\') || \' \' || coalesce(sadrzaj, \'\')))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_doktori_fulltext');
            DB::statement('DROP INDEX IF EXISTS idx_klinike_fulltext');
            DB::statement('DROP INDEX IF EXISTS idx_pitanja_fulltext');
        }

        // Drop regular indexes
        Schema::table('termini', function (Blueprint $table) {
            $table->dropIndex('idx_termini_booking');
            $table->dropIndex('idx_termini_status_date');
            $table->dropIndex('idx_termini_created');
        });

        Schema::table('doktori', function (Blueprint $table) {
            $table->dropIndex('idx_doktori_specialty');
            $table->dropIndex('idx_doktori_slug');
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->dropIndex('idx_klinike_active');
            $table->dropIndex('idx_klinike_slug');
        });

        Schema::table('pitanja', function (Blueprint $table) {
            $table->dropIndex('idx_pitanja_specialty_answered');
            $table->dropIndex('idx_pitanja_slug');
            $table->dropIndex('idx_pitanja_created');
        });

        Schema::table('odgovori_na_pitanja', function (Blueprint $table) {
            $table->dropIndex('idx_odgovori_pitanje');
            $table->dropIndex('idx_odgovori_doktor');
        });

        Schema::table('recenzije', function (Blueprint $table) {
            $table->dropIndex('idx_recenzije_recenziran');
            $table->dropIndex('idx_recenzije_created');
        });

        Schema::table('klinika_doktor_gostovanja', function (Blueprint $table) {
            $table->dropIndex('idx_gostovanja_doktor_date');
            $table->dropIndex('idx_gostovanja_klinika_date');
            $table->dropIndex('idx_gostovanja_status');
        });

        Schema::table('notifikacije', function (Blueprint $table) {
            $table->dropIndex('idx_notifikacije_user');
            $table->dropIndex('idx_notifikacije_created');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
        });
    }
};
