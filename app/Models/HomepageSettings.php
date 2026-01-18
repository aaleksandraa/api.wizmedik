<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSettings extends Model
{
    protected $table = 'homepage_settings';

    protected $fillable = [
        // Colors
        'primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color',
        // Hero
        'hero_enabled', 'hero_title', 'hero_subtitle', 'hero_background_type', 'hero_background_value',
        'hero_cta_text', 'hero_cta_link',
        // Search
        'search_enabled', 'search_title', 'search_show_specialty', 'search_show_city', 'search_show_name',
        // Doctors
        'doctors_enabled', 'doctors_title', 'doctors_subtitle', 'doctors_count', 'doctors_display',
        'doctors_layout', 'doctors_show_view_all',
        // Clinics
        'clinics_enabled', 'clinics_title', 'clinics_subtitle', 'clinics_count', 'clinics_display',
        'clinics_layout', 'clinics_show_view_all',
        // Blog
        'blog_enabled', 'blog_title', 'blog_subtitle', 'blog_count', 'blog_display',
        'blog_layout', 'blog_show_view_all',
        // Specialties
        'specialties_enabled', 'specialties_title', 'specialties_subtitle', 'specialties_count', 'specialties_layout',
        // Stats
        'stats_enabled', 'stats_title', 'stats_show_doctors', 'stats_show_clinics',
        'stats_show_patients', 'stats_show_appointments',
        // CTA
        'cta_enabled', 'cta_title', 'cta_subtitle', 'cta_button_text', 'cta_button_link',
        'cta_background_type', 'cta_background_value',
        // Order
        'sections_order'
    ];

    protected $casts = [
        'hero_enabled' => 'boolean',
        'search_enabled' => 'boolean',
        'search_show_specialty' => 'boolean',
        'search_show_city' => 'boolean',
        'search_show_name' => 'boolean',
        'doctors_enabled' => 'boolean',
        'doctors_count' => 'integer',
        'doctors_show_view_all' => 'boolean',
        'clinics_enabled' => 'boolean',
        'clinics_count' => 'integer',
        'clinics_show_view_all' => 'boolean',
        'blog_enabled' => 'boolean',
        'blog_count' => 'integer',
        'blog_show_view_all' => 'boolean',
        'specialties_enabled' => 'boolean',
        'specialties_count' => 'integer',
        'stats_enabled' => 'boolean',
        'stats_show_doctors' => 'boolean',
        'stats_show_clinics' => 'boolean',
        'stats_show_patients' => 'boolean',
        'stats_show_appointments' => 'boolean',
        'cta_enabled' => 'boolean',
        'sections_order' => 'array',
    ];

    public static function get(): self
    {
        return static::first() ?? static::create([
            'sections_order' => ['hero', 'search', 'doctors', 'clinics', 'specialties', 'blog', 'stats', 'cta']
        ]);
    }
}
