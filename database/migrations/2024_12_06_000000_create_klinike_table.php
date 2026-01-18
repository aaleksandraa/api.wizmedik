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
        Schema::create('klinike', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->text('opis')->nullable();
            $table->string('adresa');
            $table->string('grad');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('telefon');
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('slike')->nullable();
            $table->json('radno_vrijeme')->nullable();
            $table->boolean('aktivan')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('grad');
            $table->index('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klinike');
    }
};
