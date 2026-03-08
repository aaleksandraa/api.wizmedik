<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lijek_registar_zapisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lijek_id')->nullable()->constrained('lijekovi')->nullOnDelete();

            $table->unsignedBigInteger('source_lijek_id')->nullable();
            $table->string('atc_sifra', 64)->nullable();
            $table->string('inn')->nullable();
            $table->string('jidl', 64)->nullable();
            $table->string('naziv_lijeka')->nullable();
            $table->string('proizvodjac')->nullable();
            $table->string('nosilac_dozvole')->nullable();
            $table->string('oblik')->nullable();
            $table->string('jacina')->nullable();
            $table->string('pakovanje')->nullable();
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

            $table->string('import_batch_id', 64)->nullable();
            $table->unsignedInteger('import_row_number')->nullable();
            $table->string('match_status', 32)->default('unmatched');
            $table->text('match_note')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('source_lijek_id');
            $table->index('atc_sifra');
            $table->index('jidl');
            $table->index('match_status');
            $table->index(['import_batch_id', 'import_row_number'], 'lijek_registar_batch_row_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lijek_registar_zapisi');
    }
};
