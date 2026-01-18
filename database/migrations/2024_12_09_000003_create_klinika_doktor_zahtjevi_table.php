<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klinika_doktor_zahtjevi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinika_id')->constrained('klinike')->onDelete('cascade');
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->enum('initiated_by', ['clinic', 'doctor'])->default('clinic');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('poruka')->nullable();
            $table->text('odgovor')->nullable();
            $table->timestamp('odgovoreno_at')->nullable();
            $table->timestamps();

            $table->unique(['klinika_id', 'doktor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klinika_doktor_zahtjevi');
    }
};
