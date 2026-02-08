<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();

            // Global colors
            $table->string('primary_color')->default('#0891b2');
            $table->string('secondary_color')->default('#8b5cf6');
            $table->string('accent_color')->default('#10b981');
            $table->string('background_color')->default('#ffffff');
            $table->string('text_color')->default('#1f2937');

            // Hero section
            $table->boolean('hero_enabled')->default(true);
            $table->string('hero_title')->default('Pronađite najboljeg doktora');
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_background_type')->default('gradient'); // gradient, image, color
            $table->string('hero_background_value')->nullable();
            $table->string('hero_cta_text')->default('Pretraži doktore');
            $table->string('hero_cta_link')->default('/doktori');

            // Search section
            $table->boolean('search_enabled')->default(true);
            $table->string('search_title')->default('Pretraži');
            $table->boolean('search_show_specialty')->default(true);
            $table->boolean('search_show_city')->default(true);
            $table->boolean('search_show_name')->default(true);

            // Doctors section
            $table->boolean('doctors_enabled')->default(true);
            $table->string('doctors_title')->default('Naši doktori');
            $table->text('doctors_subtitle')->nullable();
            $table->integer('doctors_count')->default(6);
            $table->string('doctors_display')->default('featured'); // featured, latest, random
            $table->string('doctors_layout')->default('grid'); // grid, carousel
            $table->boolean('doctors_show_view_all')->default(true);

            // Clinics section
            $table->boolean('clinics_enabled')->default(true);
            $table->string('clinics_title')->default('Klinike');
            $table->text('clinics_subtitle')->nullable();
            $table->integer('clinics_count')->default(4);
            $table->string('clinics_display')->default('featured'); // featured, latest, random
            $table->string('clinics_layout')->default('grid'); // grid, carousel
            $table->boolean('clinics_show_view_all')->default(true);

            // Blog section
            $table->boolean('blog_enabled')->default(true);
            $table->string('blog_title')->default('Blog');
            $table->text('blog_subtitle')->nullable();
            $table->integer('blog_count')->default(3);
            $table->string('blog_display')->default('latest'); // featured, latest
            $table->string('blog_layout')->default('grid'); // grid, list
            $table->boolean('blog_show_view_all')->default(true);

            // Specialties section
            $table->boolean('specialties_enabled')->default(true);
            $table->string('specialties_title')->default('Specijalnosti');
            $table->text('specialties_subtitle')->nullable();
            $table->integer('specialties_count')->default(8);
            $table->string('specialties_layout')->default('grid'); // grid, list

            // Stats section
            $table->boolean('stats_enabled')->default(true);
            $table->string('stats_title')->nullable();
            $table->boolean('stats_show_doctors')->default(true);
            $table->boolean('stats_show_clinics')->default(true);
            $table->boolean('stats_show_patients')->default(true);
            $table->boolean('stats_show_appointments')->default(true);

            // CTA section
            $table->boolean('cta_enabled')->default(true);
            $table->string('cta_title')->default('Pridružite se');
            $table->text('cta_subtitle')->nullable();
            $table->string('cta_button_text')->default('Registrujte se');
            $table->string('cta_button_link')->default('/registracija');
            $table->string('cta_background_type')->default('gradient');
            $table->string('cta_background_value')->nullable();

            // Section order (JSON array of section names)
            $table->json('sections_order')->nullable();

            $table->timestamps();
        });

        // Insert default settings
        \DB::table('homepage_settings')->insert([
            'sections_order' => json_encode(['hero', 'search', 'doctors', 'clinics', 'specialties', 'blog', 'stats', 'cta']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};
