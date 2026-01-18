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
        // Main laboratories table
        Schema::create('laboratorije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Basic information
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->text('opis')->nullable();
            $table->text('kratak_opis')->nullable();

            // Contact information
            $table->string('email');
            $table->string('telefon');
            $table->string('telefon_2')->nullable();
            $table->string('website')->nullable();

            // Location
            $table->string('adresa');
            $table->string('grad');
            $table->string('postanski_broj')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('google_maps_link')->nullable();

            // Images
            $table->string('featured_slika')->nullable();
            $table->string('profilna_slika')->nullable();
            $table->json('galerija')->nullable(); // Array of image URLs

            // Working hours
            $table->json('radno_vrijeme')->nullable();

            // Relations (optional - if part of clinic/doctor)
            $table->foreignId('klinika_id')->nullable()->constrained('klinike')->onDelete('set null');
            $table->foreignId('doktor_id')->nullable()->constrained('doktori')->onDelete('set null');

            // Features
            $table->boolean('online_rezultati')->default(false);
            $table->string('prosjecno_vrijeme_rezultata')->nullable(); // e.g., "24-48 sati"
            $table->text('napomena')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            // Status and visibility
            $table->boolean('aktivan')->default(true);
            $table->boolean('verifikovan')->default(false);
            $table->timestamp('verifikovan_at')->nullable();

            // Statistics (for performance)
            $table->integer('broj_pregleda')->default(0);
            $table->decimal('prosjecna_ocjena', 3, 2)->default(0);
            $table->integer('broj_recenzija')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('slug');
            $table->index('grad');
            $table->index('aktivan');
            $table->index('verifikovan');
            $table->index(['latitude', 'longitude']);

            // Full-text search only for MySQL (skip for SQLite and PostgreSQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['naziv', 'opis', 'kratak_opis', 'adresa', 'grad']);
            }
        });

        // Analysis categories table
        Schema::create('kategorije_analiza', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->text('opis')->nullable();
            $table->string('ikona')->nullable(); // Icon name or path
            $table->string('boja')->nullable(); // Hex color for UI
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('aktivan');
            $table->index('redoslijed');
        });

        // Analyses table
        Schema::create('analize', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorija_id')->constrained('laboratorije')->onDelete('cascade');
            $table->foreignId('kategorija_id')->constrained('kategorije_analiza')->onDelete('cascade');

            // Basic information
            $table->string('naziv');
            $table->string('slug');
            $table->string('kod')->nullable(); // Lab code (e.g., "KKS-001")
            $table->text('opis')->nullable();
            $table->string('kratak_opis')->nullable();

            // Pricing
            $table->decimal('cijena', 10, 2);
            $table->decimal('akcijska_cijena', 10, 2)->nullable();
            $table->date('akcija_od')->nullable();
            $table->date('akcija_do')->nullable();

            // Details
            $table->string('prosjecno_vrijeme_rezultata')->nullable(); // e.g., "2-4 sata"
            $table->text('priprema')->nullable(); // Preparation instructions
            $table->text('napomena')->nullable();

            // SEO and search
            $table->json('kljucne_rijeci')->nullable(); // Keywords for search
            $table->json('sinonimi')->nullable(); // Alternative names

            // Features
            $table->boolean('hitno_dostupno')->default(false);
            $table->boolean('kucna_posjeta')->default(false);
            $table->boolean('online_rezultati')->default(true);

            // Status
            $table->boolean('aktivan')->default(true);
            $table->integer('redoslijed')->default(0);

            // Statistics
            $table->integer('broj_pretraga')->default(0);
            $table->integer('broj_pregleda')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('slug');
            $table->index('laboratorija_id');
            $table->index('kategorija_id');
            $table->index('aktivan');
            $table->index(['cijena', 'akcijska_cijena']);

            // Full-text search only for MySQL (skip for SQLite and PostgreSQL)
            if (DB::getDriverName() === 'mysql') {
                $table->fullText(['naziv', 'opis', 'kratak_opis', 'kod']);
            }

            // Unique constraint
            $table->unique(['laboratorija_id', 'slug']);
        });

        // Laboratory gallery table
        Schema::create('laboratorija_galerija', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorija_id')->constrained('laboratorije')->onDelete('cascade');
            $table->string('slika_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('naslov')->nullable();
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('laboratorija_id');
            $table->index('redoslijed');
        });

        // Laboratory working hours (separate table for flexibility)
        Schema::create('laboratorija_radno_vrijeme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorija_id')->constrained('laboratorije')->onDelete('cascade');
            $table->enum('dan', ['ponedeljak', 'utorak', 'srijeda', 'cetvrtak', 'petak', 'subota', 'nedjelja']);
            $table->time('otvaranje')->nullable();
            $table->time('zatvaranje')->nullable();
            $table->time('pauza_od')->nullable();
            $table->time('pauza_do')->nullable();
            $table->boolean('zatvoreno')->default(false);
            $table->text('napomena')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('laboratorija_id');
            $table->unique(['laboratorija_id', 'dan']);
        });

        // Analysis packages (combo deals)
        Schema::create('paketi_analiza', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratorija_id')->constrained('laboratorije')->onDelete('cascade');
            $table->string('naziv');
            $table->string('slug');
            $table->text('opis')->nullable();
            $table->decimal('cijena', 10, 2);
            $table->decimal('ustedite', 10, 2)->nullable(); // Savings amount
            $table->json('analize_ids'); // Array of analysis IDs
            $table->boolean('aktivan')->default(true);
            $table->integer('redoslijed')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('laboratorija_id');
            $table->index('slug');
            $table->index('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paketi_analiza');
        Schema::dropIfExists('laboratorija_radno_vrijeme');
        Schema::dropIfExists('laboratorija_galerija');
        Schema::dropIfExists('analize');
        Schema::dropIfExists('kategorije_analiza');
        Schema::dropIfExists('laboratorije');
    }
};
