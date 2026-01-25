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
        Schema::create('doktor_gostovanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->foreignId('klinika_id')->constrained('klinike')->onDelete('cascade');
            $table->dateTime('datum_od');
            $table->dateTime('datum_do');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('napomena')->nullable();
            $table->enum('initiated_by', ['doctor', 'clinic'])->default('clinic');
            $table->timestamps();

            $table->index(['doktor_id', 'datum_od']);
            $table->index(['klinika_id', 'datum_od']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doktor_gostovanja');
    }
};
