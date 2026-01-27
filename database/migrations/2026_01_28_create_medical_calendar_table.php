<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_calendar', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['day', 'week', 'month', 'campaign'])->default('day');
            $table->date('end_date')->nullable(); // Za sedmice, mjesece i kampanje
            $table->string('category')->nullable(); // npr. 'cancer', 'mental-health', 'vaccination'
            $table->string('color')->default('#3b82f6'); // Hex boja za kalendar
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('date');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_calendar');
    }
};
