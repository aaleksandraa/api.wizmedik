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
        Schema::create('gradovi', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->string('opis');
            $table->text('detaljni_opis');
            $table->string('populacija')->nullable();
            $table->integer('broj_bolnica')->default(0);
            $table->integer('broj_doktora')->default(0);
            $table->integer('broj_klinika')->default(0);
            $table->string('hitna_pomoc')->default('124');
            $table->json('kljucne_tacke')->nullable();
            $table->boolean('aktivan')->default(true);
            $table->timestamps();
            
            $table->index('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gradovi');
    }
};
