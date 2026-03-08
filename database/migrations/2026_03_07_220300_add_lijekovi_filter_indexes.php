<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lijekovi', function (Blueprint $table) {
            $table->index(['atc_sifra', 'aktuelna_lista_id'], 'lijekovi_atc_lista_idx');
            $table->index('aktuelna_cijena', 'lijekovi_aktuelna_cijena_idx');
            $table->index('aktuelni_iznos_participacije', 'lijekovi_akt_participacija_idx');
            $table->index('aktuelni_broj_indikacija', 'lijekovi_akt_indikacije_idx');
        });

        Schema::table('lijek_fond_zapisi', function (Blueprint $table) {
            $table->index(['lijek_id', 'indikacija_oznaka'], 'lijek_fond_lijek_indikacija_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lijek_fond_zapisi', function (Blueprint $table) {
            $table->dropIndex('lijek_fond_lijek_indikacija_idx');
        });

        Schema::table('lijekovi', function (Blueprint $table) {
            $table->dropIndex('lijekovi_atc_lista_idx');
            $table->dropIndex('lijekovi_aktuelna_cijena_idx');
            $table->dropIndex('lijekovi_akt_participacija_idx');
            $table->dropIndex('lijekovi_akt_indikacije_idx');
        });
    }
};
