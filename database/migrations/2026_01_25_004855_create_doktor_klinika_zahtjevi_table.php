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
        Schema::create('doktor_klinika_zahtjevi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->foreignId('klinika_id')->constrained('klinike')->onDelete('cascade');
            $table->text('poruka')->nullable();
            $table->text('odgovor')->nullable();
            $table->enum('initiated_by', ['doctor', 'clinic'])->default('doctor');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['doktor_id', 'status']);
            $table->index(['klinika_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doktor_klinika_zahtjevi');
    }
};
