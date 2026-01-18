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
        // Glavna tabela: banje
        Schema::create('banje', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->string('grad', 100);
            $table->string('regija', 100)->nullable();
            $table->text('adresa');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('telefon', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('opis');
            $table->text('detaljni_opis')->nullable();

            // Medicinski podaci
            $table->boolean('medicinski_nadzor')->default(false);
            $table->boolean('fizijatar_prisutan')->default(false);

            // Smještaj i kapacitet
            $table->boolean('ima_smjestaj')->default(false);
            $table->integer('broj_kreveta')->nullable();

            // Online funkcionalnosti
            $table->boolean('online_rezervacija')->default(false);
            $table->boolean('online_upit')->default(true);

            // Status i verifikacija
            $table->boolean('verifikovan')->default(false);
            $table->boolean('aktivan')->default(true);

            // Ratings
            $table->decimal('prosjecna_ocjena', 3, 2)->default(0);
            $table->integer('broj_recenzija')->default(0);
            $table->integer('broj_pregleda')->default(0);

            // Galerija
            $table->string('featured_slika', 500)->nullable();
            $table->json('galerija')->nullable();

            // Radno vrijeme
            $table->json('radno_vrijeme')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('grad');
            $table->index('slug');
            $table->index('aktivan');
            $table->index('verifikovan');
            $table->index(['grad', 'aktivan']);

            // Full-text search only for MySQL (skip for SQLite and PostgreSQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['naziv', 'opis', 'grad']);
            }
        });

        // Vrste banja (taxonomy)
        Schema::create('vrste_banja', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('opis')->nullable();
            $table->string('ikona', 50)->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Pivot: banja_vrste
        Schema::create('banja_vrste', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('vrsta_id')->constrained('vrste_banja')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['banja_id', 'vrsta_id']);
            $table->index('banja_id');
            $table->index('vrsta_id');
        });

        // Indikacije (taxonomy)
        Schema::create('indikacije', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200);
            $table->string('slug', 200)->unique();
            $table->string('kategorija', 100)->nullable();
            $table->text('opis')->nullable();
            $table->text('medicinski_opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('kategorija');
            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Pivot: banja_indikacije
        Schema::create('banja_indikacije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('indikacija_id')->constrained('indikacije')->onDelete('cascade');
            $table->integer('prioritet')->default(0)->comment('1=glavna, 2=sekundarna, 3=dodatna');
            $table->text('napomena')->nullable();
            $table->timestamps();

            $table->unique(['banja_id', 'indikacija_id']);
            $table->index('banja_id');
            $table->index('indikacija_id');
            $table->index('prioritet');
        });

        // Terapije/Usluge (taxonomy)
        Schema::create('terapije', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200);
            $table->string('slug', 200)->unique();
            $table->string('kategorija', 100)->nullable();
            $table->text('opis')->nullable();
            $table->text('medicinski_opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('kategorija');
            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Pivot: banja_terapije
        Schema::create('banja_terapije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('terapija_id')->constrained('terapije')->onDelete('cascade');
            $table->decimal('cijena', 10, 2)->nullable();
            $table->integer('trajanje_minuta')->nullable();
            $table->text('napomena')->nullable();
            $table->timestamps();

            $table->unique(['banja_id', 'terapija_id']);
            $table->index('banja_id');
            $table->index('terapija_id');
        });

        // Paketi (opciono - za kasnije)
        Schema::create('banja_paketi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->integer('trajanje_dana')->nullable();
            $table->decimal('cijena', 10, 2)->nullable();
            $table->json('ukljuceno')->nullable()->comment('lista usluga');
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('banja_id');
            $table->index('aktivan');
        });

        // Recenzije
        Schema::create('banja_recenzije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ime', 100)->nullable();
            $table->integer('ocjena')->unsigned();
            $table->text('komentar')->nullable();
            $table->boolean('verifikovano')->default(false);
            $table->boolean('odobreno')->default(false);
            $table->string('ip_adresa', 45)->nullable();
            $table->timestamps();

            $table->index(['banja_id', 'odobreno']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // Upiti/Rezervacije
        Schema::create('banja_upiti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ime', 100);
            $table->string('email');
            $table->string('telefon', 50)->nullable();
            $table->text('poruka');
            $table->date('datum_dolaska')->nullable();
            $table->integer('broj_osoba')->nullable();
            $table->string('tip', 50)->default('upit')->comment('upit, rezervacija');
            $table->string('status', 50)->default('novi')->comment('novi, procitan, odgovoren, zatvoren');
            $table->string('ip_adresa', 45)->nullable();
            $table->timestamps();

            $table->index('banja_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['banja_id', 'status']);
        });

        // Custom terapije za banje
        Schema::create('banja_custom_terapije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->string('naziv', 200);
            $table->text('opis')->nullable();
            $table->decimal('cijena', 10, 2)->nullable();
            $table->integer('trajanje_minuta')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('banja_id');
            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Audit log za banje
        Schema::create('banja_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banja_id')->constrained('banje')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('akcija', 50)->comment('create, update, delete, verify, activate');
            $table->json('stare_vrijednosti')->nullable();
            $table->json('nove_vrijednosti')->nullable();
            $table->string('ip_adresa', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['banja_id', 'akcija']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banja_audit_log');
        Schema::dropIfExists('banja_custom_terapije');
        Schema::dropIfExists('banja_upiti');
        Schema::dropIfExists('banja_recenzije');
        Schema::dropIfExists('banja_paketi');
        Schema::dropIfExists('banja_terapije');
        Schema::dropIfExists('terapije');
        Schema::dropIfExists('banja_indikacije');
        Schema::dropIfExists('indikacije');
        Schema::dropIfExists('banja_vrste');
        Schema::dropIfExists('vrste_banja');
        Schema::dropIfExists('banje');
    }
};
