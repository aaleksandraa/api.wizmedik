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
        Schema::create('doktori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('specijalnost_id')->nullable()->constrained('specijalnosti')->onDelete('set null');
            $table->foreignId('klinika_id')->nullable()->constrained('klinike')->onDelete('set null');
            $table->string('ime');
            $table->string('prezime');
            $table->string('specijalnost'); // Redundant for easier querying
            $table->string('grad');
            $table->string('lokacija');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('telefon');
            $table->string('email')->nullable()->unique();
            $table->text('opis')->nullable();
            $table->decimal('ocjena', 2, 1)->default(0);
            $table->integer('broj_ocjena')->default(0);
            $table->string('slika_profila')->nullable();
            $table->string('slug')->unique();
            $table->boolean('prihvata_online')->default(true);
            $table->integer('slot_trajanje_minuti')->default(30);
            $table->json('radno_vrijeme')->nullable();
            $table->json('pauze')->nullable();
            $table->json('odmori')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('grad');
            $table->index('specijalnost');
            $table->index(['grad', 'specijalnost']);
            $table->index('ocjena');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doktori');
    }
};
