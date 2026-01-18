<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Glavne kategorije MKB-10 (A00-B99, C00-D48, itd.)
        Schema::create('mkb10_kategorije', function (Blueprint $table) {
            $table->id();
            $table->string('kod_od', 10);           // npr. A00
            $table->string('kod_do', 10);           // npr. B99
            $table->string('naziv', 500);           // Zarazne i parazitarne bolesti
            $table->text('opis')->nullable();
            $table->string('boja', 20)->nullable(); // za UI
            $table->string('ikona', 50)->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index(['kod_od', 'kod_do']);
        });

        // Podkategorije (npr. A00-A09 Crijevne zarazne bolesti)
        Schema::create('mkb10_podkategorije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategorija_id')->constrained('mkb10_kategorije')->onDelete('cascade');
            $table->string('kod_od', 10);
            $table->string('kod_do', 10);
            $table->string('naziv', 500);
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index(['kategorija_id', 'kod_od']);
        });

        // Pojedinačne dijagnoze/bolesti
        Schema::create('mkb10_dijagnoze', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategorija_id')->constrained('mkb10_kategorije')->onDelete('cascade');
            $table->foreignId('podkategorija_id')->nullable()->constrained('mkb10_podkategorije')->onDelete('set null');
            $table->string('kod', 10)->unique();    // npr. A00.0
            $table->string('naziv', 500);           // Kolera uzrokovana Vibrio cholerae 01, biotip cholerae
            $table->string('naziv_lat', 500)->nullable(); // Latinski naziv
            $table->text('opis')->nullable();
            $table->text('ukljucuje')->nullable();  // Šta uključuje
            $table->text('iskljucuje')->nullable(); // Šta isključuje
            $table->json('sinonimi')->nullable();   // Alternativni nazivi
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index('kod');
            $table->index('kategorija_id');
            $table->fullText(['naziv', 'kod']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mkb10_dijagnoze');
        Schema::dropIfExists('mkb10_podkategorije');
        Schema::dropIfExists('mkb10_kategorije');
    }
};
