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
        // Add prihvata_online_rezervacije to gostovanja
        Schema::table('klinika_doktor_gostovanja', function (Blueprint $table) {
            if (!Schema::hasColumn('klinika_doktor_gostovanja', 'prihvata_online_rezervacije')) {
                $table->boolean('prihvata_online_rezervacije')->default(true)->after('usluge');
            }
        });

        // Create table for guest visit services (usluge specifične za gostovanje)
        if (!Schema::hasTable('gostovanje_usluge')) {
            Schema::create('gostovanje_usluge', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gostovanje_id')->constrained('klinika_doktor_gostovanja')->onDelete('cascade');
                $table->string('naziv');
                $table->text('opis')->nullable();
                $table->decimal('cijena', 10, 2)->nullable();
                $table->integer('trajanje_minuti')->default(30);
                $table->enum('dodao', ['klinika', 'doktor'])->default('klinika');
                $table->boolean('aktivna')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gostovanje_usluge');

        Schema::table('klinika_doktor_gostovanja', function (Blueprint $table) {
            $table->dropColumn('prihvata_online_rezervacije');
        });
    }
};
