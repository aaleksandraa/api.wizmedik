<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lijekovi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lijek_id')->unique();
            $table->string('slug')->unique();

            // Osnovna polja iz RFZO cjenovnika (XML)
            $table->string('atc_sifra', 64)->nullable();
            $table->string('naziv_atc5_nivo')->nullable();
            $table->string('naziv')->nullable();
            $table->string('brend')->nullable();
            $table->string('oblik')->nullable();
            $table->string('doza')->nullable();
            $table->string('pakovanje')->nullable();
            $table->string('jedinica_mjere', 32)->nullable();
            $table->string('sifra_projekta', 64)->nullable();
            $table->text('opis')->nullable();

            // Dodatna nullable polja za buduce izvore podataka
            $table->string('inn')->nullable();
            $table->string('jidl', 64)->nullable();
            $table->string('naziv_lijeka')->nullable();
            $table->string('proizvodjac')->nullable();
            $table->string('nosilac_dozvole')->nullable();
            $table->string('oblik_registar')->nullable();
            $table->string('jacina')->nullable();
            $table->string('pakovanje_registar')->nullable();
            $table->string('broj_dozvole', 128)->nullable();
            $table->string('tip_lijeka', 128)->nullable();
            $table->string('podtip_lijeka', 128)->nullable();
            $table->date('vazi_od')->nullable();
            $table->date('vazi_do')->nullable();
            $table->date('datum_rjesenja')->nullable();
            $table->string('rezim_izdavanja', 128)->nullable();
            $table->text('posebne_oznake')->nullable();
            $table->text('nalaz_prve_serije')->nullable();
            $table->text('nalaz_prve_serije_prethodno_rjesenje')->nullable();
            $table->string('farmaceutski_oblik')->nullable();
            $table->string('vrsta_lijeka', 128)->nullable();
            $table->text('lista_rfzo_pojasnjenje')->nullable();

            // Aktuelni fondovski snapshot
            $table->decimal('aktuelna_cijena', 10, 2)->nullable();
            $table->decimal('aktuelni_procenat_participacije', 5, 2)->nullable();
            $table->decimal('aktuelni_iznos_participacije', 10, 2)->nullable();
            $table->string('aktuelna_lista_id', 16)->nullable();
            $table->date('aktuelna_verzija_od')->nullable();
            $table->date('aktuelna_verzija_do')->nullable();
            $table->unsignedInteger('aktuelni_broj_indikacija')->nullable();
            $table->date('xml_datum_izvoza')->nullable();
            $table->timestamps();

            $table->index('atc_sifra');
            $table->index('naziv');
            $table->index('brend');
            $table->index('aktuelna_lista_id');
            $table->index(['aktuelna_verzija_od', 'aktuelna_verzija_do']);
        });

        Schema::create('lijek_fond_zapisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lijek_id')->constrained('lijekovi')->cascadeOnDelete();
            $table->decimal('cijena', 10, 2)->nullable();
            $table->decimal('procenat_participacije', 5, 2)->nullable();
            $table->decimal('iznos_participacije', 10, 2)->nullable();
            $table->string('lista_id', 16)->nullable();
            $table->string('indikacija_oznaka', 64)->nullable();
            $table->text('indikacija_naziv')->nullable();
            $table->decimal('cijena_ref_lijeka', 10, 2)->nullable();
            $table->date('verzija_od')->nullable();
            $table->date('verzija_do')->nullable();
            $table->timestamps();

            $table->index('lista_id');
            $table->index('indikacija_oznaka');
            $table->index(['verzija_od', 'verzija_do']);
            $table->index(['lijek_id', 'verzija_od', 'verzija_do'], 'lijek_fond_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lijek_fond_zapisi');
        Schema::dropIfExists('lijekovi');
    }
};
