<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_entity_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 120)->index();
            $table->string('entity_type', 64);
            $table->string('external_id', 120);
            $table->string('model_type', 120);
            $table->unsignedBigInteger('model_id');
            $table->string('source_hash', 64)->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['source', 'entity_type', 'external_id'], 'import_entity_mappings_source_type_ext_unique');
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_entity_mappings');
    }
};
