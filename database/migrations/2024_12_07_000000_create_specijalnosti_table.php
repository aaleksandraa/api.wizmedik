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
        Schema::create('specijalnosti', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('specijalnosti')->onDelete('cascade');
            $table->text('opis')->nullable();
            $table->boolean('aktivan')->default(true);
            $table->timestamps();
            
            $table->index('parent_id');
            $table->index('aktivan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specijalnosti');
    }
};
