<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pitanja', function (Blueprint $table) {
            $table->id();
            $table->string('naslov');
            $table->text('sadrzaj');
            $table->string('ime_korisnika');
            $table->string('email_korisnika')->nullable();
            $table->foreignId('specijalnost_id')->constrained('specijalnosti')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->json('tagovi')->nullable(); // ['dijabetes', 'hipertenzija']
            $table->integer('broj_pregleda')->default(0);
            $table->boolean('je_odgovoreno')->default(false);
            $table->boolean('je_javno')->default(true);
            $table->string('ip_adresa')->nullable();
            $table->timestamps();

            $table->index('specijalnost_id');
            $table->index('je_javno');
            $table->index('je_odgovoreno');

            // Full-text search only for MySQL/PostgreSQL (skip for SQLite testing)
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['naslov', 'sadrzaj']);
            }
        });

        Schema::create('odgovori_na_pitanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitanje_id')->constrained('pitanja')->onDelete('cascade');
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->text('sadrzaj');
            $table->boolean('je_prihvacen')->default(false); // Best answer
            $table->integer('broj_lajkova')->default(0);
            $table->timestamps();

            $table->index('pitanje_id');
            $table->index('doktor_id');
        });

        Schema::create('notifikacije_pitanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitanje_id')->constrained('pitanja')->onDelete('cascade');
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->boolean('je_procitano')->default(false);
            $table->timestamp('procitano_u')->nullable();
            $table->timestamps();

            $table->index(['doktor_id', 'je_procitano']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikacije_pitanja');
        Schema::dropIfExists('odgovori_na_pitanja');
        Schema::dropIfExists('pitanja');
    }
};
