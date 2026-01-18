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
        // Tipovi domova (taxonomy)
        Schema::create('tipovi_domova', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200)->unique();
            $table->string('slug', 200)->unique();
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Nivoi njege (taxonomy)
        Schema::create('nivoi_njege', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200)->unique();
            $table->string('slug', 200)->unique();
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Programi njege (taxonomy)
        Schema::create('programi_njege', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200);
            $table->string('slug', 200)->unique();
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Medicinske usluge (taxonomy)
        Schema::create('medicinske_usluge', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200);
            $table->string('slug', 200)->unique();
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Smještaj i uslovi (taxonomy)
        Schema::create('smjestaj_uslovi', function (Blueprint $table) {
            $table->id();
            $table->string('naziv', 200);
            $table->string('slug', 200)->unique();
            $table->string('kategorija', 100)->nullable(); // smjestaj, pristupacnost, ishrana
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('kategorija');
            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Glavna tabela: domovi_njega
        Schema::create('domovi_njega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
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

            // Tip doma i nivo njege
            $table->foreignId('tip_doma_id')->constrained('tipovi_domova')->onDelete('restrict');
            $table->foreignId('nivo_njege_id')->constrained('nivoi_njege')->onDelete('restrict');

            // Admission profile
            $table->json('accepts_tags')->nullable();
            $table->text('not_accepts_text')->nullable();

            // Medicinski podaci i osoblje
            $table->enum('nurses_availability', ['24_7', 'shifts', 'on_demand'])->default('shifts');
            $table->enum('doctor_availability', ['permanent', 'periodic', 'on_call'])->default('on_call');
            $table->boolean('has_physiotherapist')->default(false);
            $table->boolean('has_physiatrist')->default(false);

            // Sigurnost i organizacija
            $table->boolean('emergency_protocol')->default(false);
            $table->text('emergency_protocol_text')->nullable();
            $table->boolean('controlled_entry')->default(false);
            $table->boolean('video_surveillance')->default(false);
            $table->text('visiting_rules')->nullable();

            // Cijene
            $table->enum('pricing_mode', ['public', 'on_request'])->default('on_request');
            $table->decimal('price_from', 10, 2)->nullable();
            $table->text('price_includes')->nullable();
            $table->text('extra_charges')->nullable();

            // Online funkcionalnosti
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

            // FAQ
            $table->json('faqs')->nullable();

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
            $table->index(['tip_doma_id', 'nivo_njege_id']);
            $table->index(['grad', 'aktivan']);

            // Full-text search only for MySQL (skip for SQLite and PostgreSQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['naziv', 'opis', 'grad']);
            }
        });

        // Pivot: dom_programi_njege
        Schema::create('dom_programi_njege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('program_id')->constrained('programi_njege')->onDelete('cascade');
            $table->integer('prioritet')->default(0);
            $table->text('napomena')->nullable();
            $table->timestamps();

            $table->unique(['dom_id', 'program_id']);
            $table->index('dom_id');
            $table->index('program_id');
            $table->index('prioritet');
        });

        // Pivot: dom_medicinske_usluge
        Schema::create('dom_medicinske_usluge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('usluga_id')->constrained('medicinske_usluge')->onDelete('cascade');
            $table->text('napomena')->nullable();
            $table->timestamps();

            $table->unique(['dom_id', 'usluga_id']);
            $table->index('dom_id');
            $table->index('usluga_id');
        });

        // Pivot: dom_smjestaj_uslovi
        Schema::create('dom_smjestaj_uslovi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('uslov_id')->constrained('smjestaj_uslovi')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['dom_id', 'uslov_id']);
            $table->index('dom_id');
            $table->index('uslov_id');
        });

        // Recenzije
        Schema::create('dom_recenzije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ime', 100)->nullable();
            $table->integer('ocjena')->unsigned();
            $table->text('komentar')->nullable();
            $table->boolean('verifikovano')->default(false);
            $table->boolean('odobreno')->default(false);
            $table->string('ip_adresa', 45)->nullable();
            $table->timestamps();

            $table->index(['dom_id', 'odobreno']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // Upiti/Rezervacije
        Schema::create('dom_upiti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ime', 100);
            $table->string('email');
            $table->string('telefon', 50)->nullable();
            $table->text('poruka');
            $table->text('opis_potreba')->nullable();
            $table->boolean('zelja_posjeta')->default(false);
            $table->string('tip', 50)->default('upit')->comment('upit, rezervacija');
            $table->string('status', 50)->default('novi')->comment('novi, procitan, odgovoren, zatvoren');
            $table->string('ip_adresa', 45)->nullable();
            $table->timestamps();

            $table->index('dom_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['dom_id', 'status']);
        });

        // Audit log za domove
        Schema::create('dom_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dom_id')->constrained('domovi_njega')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('akcija', 50)->comment('create, update, delete, verify, activate');
            $table->json('stare_vrijednosti')->nullable();
            $table->json('nove_vrijednosti')->nullable();
            $table->string('ip_adresa', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['dom_id', 'akcija']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dom_audit_log');
        Schema::dropIfExists('dom_upiti');
        Schema::dropIfExists('dom_recenzije');
        Schema::dropIfExists('dom_smjestaj_uslovi');
        Schema::dropIfExists('dom_medicinske_usluge');
        Schema::dropIfExists('dom_programi_njege');
        Schema::dropIfExists('domovi_njega');
        Schema::dropIfExists('smjestaj_uslovi');
        Schema::dropIfExists('medicinske_usluge');
        Schema::dropIfExists('programi_njege');
        Schema::dropIfExists('nivoi_njege');
        Schema::dropIfExists('tipovi_domova');
    }
};
