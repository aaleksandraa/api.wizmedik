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
        Schema::create('specialty_service_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained('specijalnosti')->onDelete('cascade');
            $table->string('naziv');
            $table->string('slug');
            $table->text('kratki_opis')->nullable();
            $table->longText('sadrzaj')->nullable();
            $table->string('status')->default('draft'); // draft|published
            $table->boolean('is_indexable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();

            // SEO fields
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('og_image', 2048)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['specialty_id', 'slug']);
            $table->index(['status', 'is_indexable']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialty_service_pages');
    }
};

