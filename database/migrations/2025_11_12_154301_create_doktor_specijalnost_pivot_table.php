<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doktor_specijalnost', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->foreignId('specijalnost_id')->constrained('specijalnosti')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['doktor_id', 'specijalnost_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doktor_specijalnost');
    }
};
