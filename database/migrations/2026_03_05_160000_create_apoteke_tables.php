<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apoteke_firme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('naziv_brenda');
            $table->string('pravni_naziv')->nullable();
            $table->string('jib', 32)->nullable();
            $table->string('broj_licence', 64)->nullable();
            $table->string('telefon', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('opis')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_active');
        });

        Schema::create('apoteke_poslovnice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('apoteke_firme')->onDelete('cascade');
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->foreignId('grad_id')->nullable()->constrained('gradovi')->nullOnDelete();
            $table->string('grad_naziv')->nullable();
            $table->string('adresa');
            $table->string('postanski_broj', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('telefon', 64)->nullable();
            $table->string('email')->nullable();
            $table->text('kratki_opis')->nullable();
            $table->string('profilna_slika_url', 500)->nullable();
            $table->json('galerija_slike')->nullable();
            $table->string('google_maps_link', 500)->nullable();
            $table->boolean('ima_dostavu')->default(false);
            $table->boolean('ima_parking')->default(false);
            $table->boolean('pristup_invalidima')->default(false);
            $table->boolean('is_24h')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('ocjena', 3, 2)->nullable();
            $table->unsignedInteger('broj_ocjena')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('firma_id');
            $table->index('grad_id');
            $table->index(['is_active', 'is_verified']);
            $table->index('is_24h');
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('apoteke_radno_vrijeme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poslovnica_id')->constrained('apoteke_poslovnice')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon, 7=Sun
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['poslovnica_id', 'day_of_week'], 'apoteke_rv_unique_day');
        });

        Schema::create('apoteke_radno_vrijeme_izuzeci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poslovnica_id')->constrained('apoteke_poslovnice')->onDelete('cascade');
            $table->date('date');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('closed')->default(false);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['poslovnica_id', 'date'], 'apoteke_rv_izuzeci_unique_date');
        });

        Schema::create('apoteke_dezurstva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poslovnica_id')->constrained('apoteke_poslovnice')->onDelete('cascade');
            $table->foreignId('grad_id')->constrained('gradovi')->onDelete('cascade');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('tip', ['night', 'holiday', 'weekend', 'continuous'])->default('night');
            $table->boolean('is_nonstop')->default(false);
            $table->enum('source', ['manual', 'import'])->default('manual');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['grad_id', 'starts_at', 'ends_at', 'status'], 'apoteke_dezurstva_grad_time_idx');
            $table->index(['poslovnica_id', 'starts_at', 'ends_at'], 'apoteke_dezurstva_poslovnica_time_idx');
        });

        Schema::create('apoteke_popusti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->nullable()->constrained('apoteke_firme')->nullOnDelete();
            $table->foreignId('poslovnica_id')->nullable()->constrained('apoteke_poslovnice')->nullOnDelete();
            $table->enum('tip', ['penzioneri', 'studenti', 'porodicni', 'svi'])->default('svi');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('min_purchase', 10, 2)->nullable();
            $table->json('days_of_week')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->text('uslovi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['firma_id', 'is_active']);
            $table->index(['poslovnica_id', 'is_active']);
        });

        Schema::create('apoteke_akcije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->nullable()->constrained('apoteke_firme')->nullOnDelete();
            $table->foreignId('poslovnica_id')->nullable()->constrained('apoteke_poslovnice')->nullOnDelete();
            $table->string('naslov');
            $table->text('opis')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('promo_code', 64)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['firma_id', 'is_active']);
            $table->index(['poslovnica_id', 'is_active']);
        });

        Schema::create('apoteke_posebne_ponude', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->nullable()->constrained('apoteke_firme')->nullOnDelete();
            $table->foreignId('poslovnica_id')->nullable()->constrained('apoteke_poslovnice')->nullOnDelete();
            $table->enum('offer_type', [
                'percent_discount',
                'fixed_discount',
                'full_assortment_discount',
                'category_discount',
                'product_discount',
                'free_service',
                'free_item',
                'bundle_offer',
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('target_group', ['svi', 'penzioneri', 'studenti', 'djeca', 'hronicni_bolesnici'])->default('svi');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->string('service_name')->nullable();
            $table->json('product_scope')->nullable();
            $table->json('conditions_json')->nullable();
            $table->json('days_of_week')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['firma_id', 'is_active']);
            $table->index(['poslovnica_id', 'is_active']);
            $table->index(['target_group', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apoteke_posebne_ponude');
        Schema::dropIfExists('apoteke_akcije');
        Schema::dropIfExists('apoteke_popusti');
        Schema::dropIfExists('apoteke_dezurstva');
        Schema::dropIfExists('apoteke_radno_vrijeme_izuzeci');
        Schema::dropIfExists('apoteke_radno_vrijeme');
        Schema::dropIfExists('apoteke_poslovnice');
        Schema::dropIfExists('apoteke_firme');
    }
};

