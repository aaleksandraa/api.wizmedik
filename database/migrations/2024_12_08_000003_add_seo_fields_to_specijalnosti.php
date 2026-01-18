<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specijalnosti', function (Blueprint $table) {
            // SEO fields
            $table->string('meta_title')->nullable()->after('opis');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('detaljan_opis')->nullable();

            // YouTube videos section
            $table->boolean('prikazi_video_savjete')->default(false);
            $table->json('youtube_linkovi')->nullable();

            // FAQ section
            $table->boolean('prikazi_faq')->default(false);
            $table->json('faq')->nullable();

            // Services section
            $table->boolean('prikazi_usluge')->default(false);
            $table->json('usluge')->nullable();

            // Additional SEO content
            $table->text('uvodni_tekst')->nullable();
            $table->text('zakljucni_tekst')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('specijalnosti', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title', 'meta_description', 'meta_keywords', 'detaljan_opis',
                'prikazi_video_savjete', 'youtube_linkovi',
                'prikazi_faq', 'faq',
                'prikazi_usluge', 'usluge',
                'uvodni_tekst', 'zakljucni_tekst', 'canonical_url', 'og_image'
            ]);
        });
    }
};
