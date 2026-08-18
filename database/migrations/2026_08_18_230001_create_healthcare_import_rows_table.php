<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('healthcare_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('healthcare_import_batches')->cascadeOnDelete();
            $table->string('sheet_name', 64)->index();
            $table->unsignedInteger('row_number');
            $table->string('entity_type', 64)->nullable()->index();
            $table->string('external_id', 120)->nullable()->index();
            $table->string('action', 32)->nullable()->index();
            $table->string('status', 32)->nullable()->index();
            $table->string('matched_model_type', 120)->nullable();
            $table->unsignedBigInteger('matched_model_id')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('warnings')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'sheet_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('healthcare_import_rows');
    }
};
