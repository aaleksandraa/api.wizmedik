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
        Schema::create('usluge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->string('naziv');
            $table->text('opis')->nullable();
            $table->decimal('cijena', 8, 2)->nullable();
            $table->integer('trajanje_minuti')->default(30);
            $table->boolean('aktivan')->default(true);
            $table->timestamps();
            
            $table->index('doktor_id');
            $table->index('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usluge');
    }
};
