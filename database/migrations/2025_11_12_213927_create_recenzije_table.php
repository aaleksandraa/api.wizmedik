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
        Schema::create('recenzije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('termin_id')->constrained('termini')->onDelete('cascade');
            $table->morphs('recenziran');
            $table->tinyInteger('ocjena')->unsigned();
            $table->text('komentar')->nullable();
            $table->text('odgovor')->nullable();
            $table->timestamp('odgovor_datum')->nullable();
            $table->boolean('email_poslat')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'termin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recenzije');
    }
};
