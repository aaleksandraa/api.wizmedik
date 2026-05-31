<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apoteka_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poslovnica_id')->constrained('apoteke_poslovnice')->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->timestamps();

            $table->index('poslovnica_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apoteka_slug_redirects');
    }
};
