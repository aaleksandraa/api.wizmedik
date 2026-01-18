<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Blog posts table
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('naslov');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('sadrzaj');
            $table->string('thumbnail')->nullable();
            $table->foreignId('autor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('doktor_id')->nullable()->constrained('doktori')->onDelete('set null');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->integer('views')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('featured');
        });

        // Blog categories
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->text('opis')->nullable();
            $table->timestamps();
        });

        // Pivot table for post categories
        Schema::create('blog_post_category', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('blog_category_id')->constrained()->onDelete('cascade');
            $table->primary(['blog_post_id', 'blog_category_id']);
        });

        // Blog settings
        Schema::create('blog_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('doctors_can_write')->default(false);
            $table->enum('homepage_display', ['featured', 'latest'])->default('latest');
            $table->integer('homepage_count')->default(3);
            $table->json('featured_post_ids')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        \DB::table('blog_settings')->insert([
            'doctors_can_write' => false,
            'homepage_display' => 'latest',
            'homepage_count' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_category');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog_settings');
        Schema::dropIfExists('blog_posts');
    }
};
