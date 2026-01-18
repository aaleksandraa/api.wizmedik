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
        // Doktori - Composite indexes for common queries
        // Note: doktori doesn't have 'aktivan' column, uses soft deletes instead
        Schema::table('doktori', function (Blueprint $table) {
            $table->index(['grad', 'specijalnost_id'], 'idx_doktori_grad_spec');
            $table->index(['specijalnost_id', 'grad'], 'idx_doktori_spec_grad');
            $table->index(['grad', 'prihvata_online'], 'idx_doktori_grad_online');
            $table->index(['klinika_id', 'prihvata_online'], 'idx_doktori_klinika_online');
            $table->index('ocjena', 'idx_doktori_ocjena');
        });

        // Klinike - Composite indexes
        Schema::table('klinike', function (Blueprint $table) {
            $table->index(['grad', 'aktivan'], 'idx_klinike_grad_aktivan');
        });

        // Laboratorije - Composite indexes
        Schema::table('laboratorije', function (Blueprint $table) {
            $table->index(['grad', 'aktivan'], 'idx_laboratorije_grad_aktivan');
            $table->index(['verifikovan', 'aktivan'], 'idx_laboratorije_verif_aktivan');
        });

        // Banje - Composite indexes
        Schema::table('banje', function (Blueprint $table) {
            $table->index(['grad', 'aktivan'], 'idx_banje_grad_aktivan');
            $table->index(['verifikovan', 'aktivan'], 'idx_banje_verif_aktivan');
            $table->index('prosjecna_ocjena', 'idx_banje_ocjena');
        });

        // Termini - Composite indexes for appointment queries
        Schema::table('termini', function (Blueprint $table) {
            $table->index(['doktor_id', 'datum_vrijeme', 'status'], 'idx_termini_doktor_datum_status');
            $table->index(['user_id', 'datum_vrijeme'], 'idx_termini_user_datum');
            $table->index(['datum_vrijeme', 'status'], 'idx_termini_datum_status');
        });

        // Pitanja - Composite indexes
        Schema::table('pitanja', function (Blueprint $table) {
            $table->index(['je_javno', 'created_at'], 'idx_pitanja_javno_created');
            $table->index(['je_odgovoreno', 'created_at'], 'idx_pitanja_odgovoreno_created');
            $table->index('broj_pregleda', 'idx_pitanja_pregleda');
        });

        // Blog posts - Composite indexes
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index(['autor_id', 'status'], 'idx_blog_autor_status');
            $table->index('views', 'idx_blog_views');
        });

        // Analize - Additional composite indexes
        Schema::table('analize', function (Blueprint $table) {
            $table->index(['laboratorija_id', 'kategorija_id', 'aktivan'], 'idx_analize_lab_kat_aktivan');
        });

        // Full-text search indexes for PostgreSQL
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_search ON doktori USING gin(to_tsvector(\'simple\', COALESCE(ime, \'\') || \' \' || COALESCE(prezime, \'\')))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_search ON klinike USING gin(to_tsvector(\'simple\', COALESCE(naziv, \'\')))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_laboratorije_search ON laboratorije USING gin(to_tsvector(\'simple\', COALESCE(naziv, \'\')))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_banje_search ON banje USING gin(to_tsvector(\'simple\', COALESCE(naziv, \'\')))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes
        DB::statement('DROP INDEX IF EXISTS idx_doktori_search');
        DB::statement('DROP INDEX IF EXISTS idx_klinike_search');
        DB::statement('DROP INDEX IF EXISTS idx_laboratorije_search');
        DB::statement('DROP INDEX IF EXISTS idx_banje_search');

        // Drop composite indexes
        Schema::table('analize', function (Blueprint $table) {
            $table->dropIndex('idx_analize_lab_kat_aktivan');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('idx_blog_autor_status');
            $table->dropIndex('idx_blog_views');
        });

        Schema::table('pitanja', function (Blueprint $table) {
            $table->dropIndex('idx_pitanja_javno_created');
            $table->dropIndex('idx_pitanja_odgovoreno_created');
            $table->dropIndex('idx_pitanja_pregleda');
        });

        Schema::table('termini', function (Blueprint $table) {
            $table->dropIndex('idx_termini_doktor_datum_status');
            $table->dropIndex('idx_termini_user_datum');
            $table->dropIndex('idx_termini_datum_status');
        });

        Schema::table('doktori', function (Blueprint $table) {
            $table->dropIndex('idx_doktori_grad_spec');
            $table->dropIndex('idx_doktori_spec_grad');
            $table->dropIndex('idx_doktori_grad_online');
            $table->dropIndex('idx_doktori_klinika_online');
            $table->dropIndex('idx_doktori_ocjena');
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->dropIndex('idx_klinike_grad_aktivan');
        });

        Schema::table('laboratorije', function (Blueprint $table) {
            $table->dropIndex('idx_laboratorije_grad_aktivan');
            $table->dropIndex('idx_laboratorije_verif_aktivan');
        });

        Schema::table('banje', function (Blueprint $table) {
            $table->dropIndex('idx_banje_grad_aktivan');
            $table->dropIndex('idx_banje_verif_aktivan');
            $table->dropIndex('idx_banje_ocjena');
        });
    }
};
