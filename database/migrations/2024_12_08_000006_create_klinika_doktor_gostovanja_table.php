<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klinika_doktor_gostovanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinika_id')->constrained('klinike')->onDelete('cascade');
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->date('datum');
            $table->time('vrijeme_od')->default('08:00');
            $table->time('vrijeme_do')->default('16:00');
            $table->integer('slot_trajanje_minuti')->default(30);
            $table->json('pauze')->nullable();
            $table->json('usluge')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('napomena')->nullable();
            $table->timestamps();

            $table->unique(['klinika_id', 'doktor_id', 'datum'], 'unique_gostovanje');
        });

        Schema::table('termini', function (Blueprint $table) {
            $table->foreignId('gostovanje_id')->nullable()->after('doktor_id')->constrained('klinika_doktor_gostovanja')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('termini', function (Blueprint $table) {
            $table->dropForeign(['gostovanje_id']);
            $table->dropColumn('gostovanje_id');
        });
        Schema::dropIfExists('klinika_doktor_gostovanja');
    }
};
