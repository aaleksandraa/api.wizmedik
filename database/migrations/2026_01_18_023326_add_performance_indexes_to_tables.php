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
        // Doktori table indexes (only new ones, some already exist from create migration)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_specijalnost_id ON doktori(specijalnost_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_user_id ON doktori(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_klinika_id ON doktori(klinika_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_email ON doktori(email)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_doktori_deleted_at ON doktori(deleted_at)');

        // Klinike table indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_grad ON klinike(grad)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_user_id ON klinike(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinike_deleted_at ON klinike(deleted_at)');

        // Termini table indexes (most already exist from create migration)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_termini_pacijent_id ON termini(user_id)'); // user_id is pacijent
        // datum_vrijeme, doktor_id, status, and composite indexes already exist

        // Recenzije table indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recenzije_recenziran_type ON recenzije(recenziran_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recenzije_recenziran_id ON recenzije(recenziran_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recenzije_user_id ON recenzije(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recenzije_termin_id ON recenzije(termin_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recenzije_type_id ON recenzije(recenziran_type, recenziran_id)');

        // Specijalnosti table indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_specijalnosti_parent_id ON specijalnosti(parent_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_specijalnosti_sort_order ON specijalnosti(sort_order)');

        // Laboratorije table indexes (if exists)
        if (Schema::hasTable('laboratorije')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_laboratorije_grad ON laboratorije(grad)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_laboratorije_verifikovan ON laboratorije(verifikovan)');
        }

        // Banje table indexes (if exists)
        if (Schema::hasTable('banje')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_banje_grad ON banje(grad)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_banje_aktivan ON banje(aktivan)');
        }

        // Domovi table indexes (if exists)
        if (Schema::hasTable('domovi')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_domovi_grad ON domovi(grad)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_domovi_aktivan ON domovi(aktivan)');
        }

        // Pitanja table indexes (if exists) - only specijalnost_id
        if (Schema::hasTable('pitanja')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_pitanja_specijalnost_id ON pitanja(specijalnost_id)');
        }

        // Blog posts table indexes (if exists)
        if (Schema::hasTable('blog_posts')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_blog_doktor_id ON blog_posts(doktor_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_blog_published_at ON blog_posts(published_at)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes (only the ones we added)
        DB::statement('DROP INDEX IF EXISTS idx_doktori_specijalnost_id');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_user_id');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_klinika_id');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_email');
        DB::statement('DROP INDEX IF EXISTS idx_doktori_deleted_at');

        DB::statement('DROP INDEX IF EXISTS idx_klinike_grad');
        DB::statement('DROP INDEX IF EXISTS idx_klinike_user_id');
        DB::statement('DROP INDEX IF EXISTS idx_klinike_deleted_at');

        DB::statement('DROP INDEX IF EXISTS idx_termini_pacijent_id');

        DB::statement('DROP INDEX IF EXISTS idx_recenzije_recenziran_type');
        DB::statement('DROP INDEX IF EXISTS idx_recenzije_recenziran_id');
        DB::statement('DROP INDEX IF EXISTS idx_recenzije_user_id');
        DB::statement('DROP INDEX IF EXISTS idx_recenzije_termin_id');
        DB::statement('DROP INDEX IF EXISTS idx_recenzije_type_id');

        DB::statement('DROP INDEX IF EXISTS idx_specijalnosti_parent_id');
        DB::statement('DROP INDEX IF EXISTS idx_specijalnosti_sort_order');

        if (Schema::hasTable('laboratorije')) {
            DB::statement('DROP INDEX IF EXISTS idx_laboratorije_grad');
            DB::statement('DROP INDEX IF EXISTS idx_laboratorije_verifikovan');
        }

        if (Schema::hasTable('banje')) {
            DB::statement('DROP INDEX IF EXISTS idx_banje_grad');
            DB::statement('DROP INDEX IF EXISTS idx_banje_aktivan');
        }

        if (Schema::hasTable('domovi')) {
            DB::statement('DROP INDEX IF EXISTS idx_domovi_grad');
            DB::statement('DROP INDEX IF EXISTS idx_domovi_aktivan');
        }

        if (Schema::hasTable('pitanja')) {
            DB::statement('DROP INDEX IF EXISTS idx_pitanja_specijalnost_id');
        }

        if (Schema::hasTable('blog_posts')) {
            DB::statement('DROP INDEX IF EXISTS idx_blog_doktor_id');
            DB::statement('DROP INDEX IF EXISTS idx_blog_published_at');
        }
    }
};
