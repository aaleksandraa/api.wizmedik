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
        // Create service categories table
        Schema::create('doktor_kategorije_usluga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index(['doktor_id', 'redoslijed']);
        });

        // Add category_id and redoslijed to usluge table
        Schema::table('usluge', function (Blueprint $table) {
            $table->foreignId('kategorija_id')->nullable()->after('doktor_id')->constrained('doktor_kategorije_usluga')->onDelete('set null');
            $table->integer('redoslijed')->default(0)->after('popust');

            $table->index(['doktor_id', 'kategorija_id', 'redoslijed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usluge', function (Blueprint $table) {
            $table->dropForeign(['kategorija_id']);
            $table->dropColumn(['kategorija_id', 'redoslijed']);
        });

        Schema::dropIfExists('doktor_kategorije_usluga');
    }
};
