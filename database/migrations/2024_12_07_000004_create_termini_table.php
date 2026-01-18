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
        Schema::create('termini', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('doktor_id')->constrained('doktori')->onDelete('cascade');
            $table->foreignId('usluga_id')->nullable()->constrained('usluge')->onDelete('set null');
            $table->dateTime('datum_vrijeme');
            $table->text('razlog')->nullable();
            $table->text('napomene')->nullable();
            $table->enum('status', ['zakazan', 'potvrden', 'otkazan', 'zavrshen'])->default('zakazan');
            $table->integer('trajanje_minuti')->default(30);
            $table->decimal('cijena', 8, 2)->nullable();
            // Guest booking fields
            $table->string('guest_ime')->nullable();
            $table->string('guest_prezime')->nullable();
            $table->string('guest_telefon')->nullable();
            $table->string('guest_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('datum_vrijeme');
            $table->index('doktor_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['doktor_id', 'datum_vrijeme']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('termini');
    }
};
