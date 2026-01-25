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
        Schema::create('doktor_gostovanje_usluge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gostovanje_id')->constrained('doktor_gostovanja')->onDelete('cascade');
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->decimal('cijena', 10, 2);
            $table->integer('trajanje_minuti');
            $table->integer('redoslijed')->default(0);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();

            $table->index(['gostovanje_id', 'redoslijed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doktor_gostovanje_usluge');
    }
};
