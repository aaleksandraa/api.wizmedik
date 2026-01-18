<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikacije', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tip'); // termin_zakazan, gostovanje_poziv, klinika_zahtjev, etc.
            $table->string('naslov');
            $table->text('poruka');
            $table->json('data')->nullable(); // Additional data like termin_id, doktor_id, etc.
            $table->boolean('procitano')->default(false);
            $table->timestamp('procitano_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'procitano']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikacije');
    }
};
