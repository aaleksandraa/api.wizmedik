<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\HomepageSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getTemplates()
    {
        return response()->json([
            'doctor_profile_template' => SiteSetting::get('doctor_profile_template', 'classic'),
            'clinic_profile_template' => SiteSetting::get('clinic_profile_template', 'classic'),
            'homepage_template' => SiteSetting::get('homepage_template', 'classic'),
            'navbar_style' => SiteSetting::get('navbar_style', 'auto'),
            'doctors_split_view_enabled' => SiteSetting::get('doctors_split_view_enabled', true),
            'modern_cover_type' => SiteSetting::get('modern_cover_type', 'gradient'),
            'modern_cover_value' => SiteSetting::get('modern_cover_value', 'from-primary via-primary/90 to-primary/80'),
            'custom3_hero_bg_enabled' => SiteSetting::get('custom3_hero_bg_enabled', false),
            'custom3_hero_bg_image' => SiteSetting::get('custom3_hero_bg_image', null),
            'custom3_hero_bg_opacity' => SiteSetting::get('custom3_hero_bg_opacity', 20),
            'available_templates' => [
                'doctor' => [
                    ['id' => 'classic', 'name' => 'Klasični', 'description' => 'Tradicionalni prikaz sa svim informacijama'],
                    ['id' => 'modern', 'name' => 'Moderni', 'description' => 'Čist dizajn sa fokusom na booking'],
                    ['id' => 'card', 'name' => 'Card', 'description' => 'Kompaktni card-based layout'],
                    ['id' => 'minimal', 'name' => 'Minimalistički', 'description' => 'Jednostavan i elegantan'],
                ],
                'clinic' => [
                    ['id' => 'classic', 'name' => 'Klasični', 'description' => 'Tradicionalni prikaz klinike'],
                    ['id' => 'modern', 'name' => 'Moderni', 'description' => 'Savremeni dizajn sa galerijom'],
                    ['id' => 'corporate', 'name' => 'Korporativni', 'description' => 'Profesionalni poslovni izgled'],
                ],
                'homepage' => [
                    ['id' => 'soft', 'name' => 'Soft', 'description' => 'Blage boje, tab pretraga i istaknute blog teme sa ilustracijama'],
                    ['id' => 'clean', 'name' => 'Clean', 'description' => 'Čist i moderan dizajn sa teal bojama, tab pretraga i blog sekcijom'],
                    ['id' => 'custom', 'name' => 'Custom', 'description' => 'Prilagođeni dizajn - podesi u "Početna" tabu'],
                    ['id' => 'custom2-cyan', 'name' => 'Custom 2 Cyan', 'description' => 'ZocDoc stil sa svijetlo plavom/cyan bojom'],
                    ['id' => 'custom2-yellow', 'name' => 'Custom 2 Yellow', 'description' => 'ZocDoc stil sa žutom bojom'],
                    ['id' => 'custom3-cyan', 'name' => 'Custom 3 Cyan', 'description' => 'Minimalistički centrisani dizajn sa cyan bojom'],
                    ['id' => 'pro', 'name' => 'Pro', 'description' => 'Profesionalni dizajn sa ljepšim klinikama i mobile responsive'],
                    ['id' => 'medical', 'name' => 'Medical', 'description' => 'Kompletan medicinski portal sa svim sekcijama'],
                    ['id' => 'modern', 'name' => 'Modern', 'description' => 'Moderan dizajn sa teal bojama i video sekcijom'],
                    ['id' => 'classic', 'name' => 'Klasični', 'description' => 'Trenutni dizajn početne stranice'],
                    ['id' => 'zocdoc', 'name' => 'ZocDoc', 'description' => 'Moderan dizajn sa zelenim tonovima'],
                    ['id' => 'zocdoc-bih', 'name' => 'ZocDoc BiH', 'description' => 'ZocDoc stil prilagođen za BiH tržište'],
                    ['id' => 'warm', 'name' => 'Topli', 'description' => 'Elegantan dizajn sa žuto-bež nijansama'],
                    ['id' => 'ocean', 'name' => 'Ocean', 'description' => 'Svjež dizajn sa plavim nijansama'],
                    ['id' => 'lime', 'name' => 'Lime', 'description' => 'Prirodan dizajn zeleno-žute nijanse'],
                    ['id' => 'teal', 'name' => 'Teal', 'description' => 'Plavo-zeleni moderni dizajn'],
                    ['id' => 'rose', 'name' => 'Rose', 'description' => 'Nježan dizajn sa ružičastim tonovima'],
                    ['id' => 'sunset', 'name' => 'Sunset', 'description' => 'Topao dizajn sa narandžastim nijansama'],
                    ['id' => 'minimal', 'name' => 'Minimal', 'description' => 'Ultra jednostavan, čist dizajn'],
                    ['id' => 'bold', 'name' => 'Bold', 'description' => 'Tamni, moderan dizajn sa gradijentima'],
                    ['id' => 'cards', 'name' => 'Cards', 'description' => 'Dashboard stil sa karticama'],
                ],
            ]
        ]);
    }

    public function updateTemplates(Request $request)
    {
        $request->validate([
            'doctor_profile_template' => 'sometimes|string|in:classic,modern,card,minimal',
            'clinic_profile_template' => 'sometimes|string|in:classic,modern,corporate',
            'homepage_template' => 'sometimes|string|in:soft,clean,custom,custom2-cyan,custom2-yellow,custom3-cyan,pro,medical,modern,classic,zocdoc,zocdoc-bih,warm,ocean,lime,teal,rose,sunset,minimal,bold,cards',
            'navbar_style' => 'sometimes|string|in:auto,default,colored',
            'doctors_split_view_enabled' => 'sometimes|boolean',
            'modern_cover_type' => 'sometimes|string|in:gradient,image',
            'modern_cover_value' => 'sometimes|nullable|string',
            'custom3_hero_bg_enabled' => 'sometimes|boolean',
            'custom3_hero_bg_image' => 'sometimes|nullable|string',
            'custom3_hero_bg_opacity' => 'sometimes|integer|min:0|max:100',
        ]);

        if ($request->has('doctor_profile_template')) {
            SiteSetting::set('doctor_profile_template', $request->doctor_profile_template);
        }

        if ($request->has('clinic_profile_template')) {
            SiteSetting::set('clinic_profile_template', $request->clinic_profile_template);
        }

        if ($request->has('homepage_template')) {
            SiteSetting::set('homepage_template', $request->homepage_template);
        }

        if ($request->has('navbar_style')) {
            SiteSetting::set('navbar_style', $request->navbar_style);
        }

        if ($request->has('doctors_split_view_enabled')) {
            SiteSetting::set('doctors_split_view_enabled', $request->doctors_split_view_enabled);
        }

        if ($request->has('modern_cover_type')) {
            SiteSetting::set('modern_cover_type', $request->modern_cover_type);
        }

        if ($request->has('modern_cover_value')) {
            SiteSetting::set('modern_cover_value', $request->modern_cover_value);
        }

        if ($request->has('custom3_hero_bg_enabled')) {
            SiteSetting::set('custom3_hero_bg_enabled', $request->custom3_hero_bg_enabled);
        }

        if ($request->has('custom3_hero_bg_image')) {
            SiteSetting::set('custom3_hero_bg_image', $request->custom3_hero_bg_image);
        }

        if ($request->has('custom3_hero_bg_opacity')) {
            SiteSetting::set('custom3_hero_bg_opacity', $request->custom3_hero_bg_opacity);
        }

        // Clear homepage cache so changes are immediately visible
        \Cache::forget('homepage_data');

        return response()->json(['message' => 'Template postavke ažurirane']);
    }

    public function getDoctorCardSettings()
    {
        return response()->json([
            'variant' => SiteSetting::get('doctor_card_variant', 'classic'),
            'showRating' => SiteSetting::get('doctor_card_show_rating', 'true') === 'true',
            'showLocation' => SiteSetting::get('doctor_card_show_location', 'true') === 'true',
            'showPhone' => SiteSetting::get('doctor_card_show_phone', 'true') === 'true',
            'showSpecialty' => SiteSetting::get('doctor_card_show_specialty', 'true') === 'true',
            'showOnlineStatus' => SiteSetting::get('doctor_card_show_online_status', 'true') === 'true',
            'showBookButton' => SiteSetting::get('doctor_card_show_book_button', 'true') === 'true',
            'primaryColor' => SiteSetting::get('doctor_card_primary_color', '#0891b2'),
            'accentColor' => SiteSetting::get('doctor_card_accent_color', '#10b981'),
        ]);
    }

    public function updateDoctorCardSettings(Request $request)
    {
        $request->validate([
            'variant' => 'sometimes|string',
            'showRating' => 'sometimes|boolean',
            'showLocation' => 'sometimes|boolean',
            'showPhone' => 'sometimes|boolean',
            'showSpecialty' => 'sometimes|boolean',
            'showOnlineStatus' => 'sometimes|boolean',
            'showBookButton' => 'sometimes|boolean',
            'primaryColor' => 'sometimes|string',
            'accentColor' => 'sometimes|string',
        ]);

        if ($request->has('variant')) {
            SiteSetting::set('doctor_card_variant', $request->variant);
        }
        if ($request->has('showRating')) {
            SiteSetting::set('doctor_card_show_rating', $request->showRating ? 'true' : 'false');
        }
        if ($request->has('showLocation')) {
            SiteSetting::set('doctor_card_show_location', $request->showLocation ? 'true' : 'false');
        }
        if ($request->has('showPhone')) {
            SiteSetting::set('doctor_card_show_phone', $request->showPhone ? 'true' : 'false');
        }
        if ($request->has('showSpecialty')) {
            SiteSetting::set('doctor_card_show_specialty', $request->showSpecialty ? 'true' : 'false');
        }
        if ($request->has('showOnlineStatus')) {
            SiteSetting::set('doctor_card_show_online_status', $request->showOnlineStatus ? 'true' : 'false');
        }
        if ($request->has('showBookButton')) {
            SiteSetting::set('doctor_card_show_book_button', $request->showBookButton ? 'true' : 'false');
        }
        if ($request->has('primaryColor')) {
            SiteSetting::set('doctor_card_primary_color', $request->primaryColor);
        }
        if ($request->has('accentColor')) {
            SiteSetting::set('doctor_card_accent_color', $request->accentColor);
        }

        return response()->json(['message' => 'Doctor card postavke ažurirane']);
    }

    public function getClinicCardSettings()
    {
        return response()->json([
            'variant' => SiteSetting::get('clinic_card_variant', 'classic'),
            'showImage' => SiteSetting::get('clinic_card_show_image', 'true') === 'true',
            'showDescription' => SiteSetting::get('clinic_card_show_description', 'true') === 'true',
            'showAddress' => SiteSetting::get('clinic_card_show_address', 'true') === 'true',
            'showPhone' => SiteSetting::get('clinic_card_show_phone', 'true') === 'true',
            'showEmail' => SiteSetting::get('clinic_card_show_email', 'false') === 'true',
            'showWebsite' => SiteSetting::get('clinic_card_show_website', 'false') === 'true',
            'showWorkingHours' => SiteSetting::get('clinic_card_show_working_hours', 'true') === 'true',
            'showDoctorsCount' => SiteSetting::get('clinic_card_show_doctors_count', 'true') === 'true',
            'showDistance' => SiteSetting::get('clinic_card_show_distance', 'true') === 'true',
            'primaryColor' => SiteSetting::get('clinic_card_primary_color', '#0891b2'),
            'accentColor' => SiteSetting::get('clinic_card_accent_color', '#8b5cf6'),
        ]);
    }

    public function updateClinicCardSettings(Request $request)
    {
        $fields = [
            'variant' => 'clinic_card_variant',
            'showImage' => 'clinic_card_show_image',
            'showDescription' => 'clinic_card_show_description',
            'showAddress' => 'clinic_card_show_address',
            'showPhone' => 'clinic_card_show_phone',
            'showEmail' => 'clinic_card_show_email',
            'showWebsite' => 'clinic_card_show_website',
            'showWorkingHours' => 'clinic_card_show_working_hours',
            'showDoctorsCount' => 'clinic_card_show_doctors_count',
            'showDistance' => 'clinic_card_show_distance',
            'primaryColor' => 'clinic_card_primary_color',
            'accentColor' => 'clinic_card_accent_color',
        ];

        foreach ($fields as $requestKey => $settingKey) {
            if ($request->has($requestKey)) {
                $value = $request->$requestKey;
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                SiteSetting::set($settingKey, $value);
            }
        }

        return response()->json(['message' => 'Clinic card postavke ažurirane']);
    }

    public function getHomepageSettings()
    {
        return response()->json(HomepageSettings::get());
    }

    public function updateHomepageSettings(Request $request)
    {
        $settings = HomepageSettings::get();

        $validated = $request->validate([
            // Colors
            'primary_color' => 'sometimes|string|max:20',
            'secondary_color' => 'sometimes|string|max:20',
            'accent_color' => 'sometimes|string|max:20',
            'background_color' => 'sometimes|string|max:20',
            'text_color' => 'sometimes|string|max:20',
            // Hero
            'hero_enabled' => 'sometimes|boolean',
            'hero_title' => 'sometimes|string|max:255',
            'hero_subtitle' => 'sometimes|nullable|string',
            'hero_background_type' => 'sometimes|string|in:gradient,image,color',
            'hero_background_value' => 'sometimes|nullable|string',
            'hero_cta_text' => 'sometimes|string|max:100',
            'hero_cta_link' => 'sometimes|string|max:255',
            // Search
            'search_enabled' => 'sometimes|boolean',
            'search_title' => 'sometimes|string|max:255',
            'search_show_specialty' => 'sometimes|boolean',
            'search_show_city' => 'sometimes|boolean',
            'search_show_name' => 'sometimes|boolean',
            // Doctors
            'doctors_enabled' => 'sometimes|boolean',
            'doctors_title' => 'sometimes|string|max:255',
            'doctors_subtitle' => 'sometimes|nullable|string',
            'doctors_count' => 'sometimes|integer|min:1|max:20',
            'doctors_display' => 'sometimes|string|in:featured,latest,random',
            'doctors_layout' => 'sometimes|string|in:grid,carousel',
            'doctors_show_view_all' => 'sometimes|boolean',
            // Clinics
            'clinics_enabled' => 'sometimes|boolean',
            'clinics_title' => 'sometimes|string|max:255',
            'clinics_subtitle' => 'sometimes|nullable|string',
            'clinics_count' => 'sometimes|integer|min:1|max:20',
            'clinics_display' => 'sometimes|string|in:featured,latest,random',
            'clinics_layout' => 'sometimes|string|in:grid,carousel',
            'clinics_show_view_all' => 'sometimes|boolean',
            // Blog
            'blog_enabled' => 'sometimes|boolean',
            'blog_title' => 'sometimes|string|max:255',
            'blog_subtitle' => 'sometimes|nullable|string',
            'blog_count' => 'sometimes|integer|min:1|max:12',
            'blog_display' => 'sometimes|string|in:featured,latest',
            'blog_layout' => 'sometimes|string|in:grid,list',
            'blog_show_view_all' => 'sometimes|boolean',
            // Specialties
            'specialties_enabled' => 'sometimes|boolean',
            'specialties_title' => 'sometimes|string|max:255',
            'specialties_subtitle' => 'sometimes|nullable|string',
            'specialties_count' => 'sometimes|integer|min:1|max:20',
            'specialties_layout' => 'sometimes|string|in:grid,list',
            // Stats
            'stats_enabled' => 'sometimes|boolean',
            'stats_title' => 'sometimes|nullable|string|max:255',
            'stats_show_doctors' => 'sometimes|boolean',
            'stats_show_clinics' => 'sometimes|boolean',
            'stats_show_patients' => 'sometimes|boolean',
            'stats_show_appointments' => 'sometimes|boolean',
            // CTA
            'cta_enabled' => 'sometimes|boolean',
            'cta_title' => 'sometimes|string|max:255',
            'cta_subtitle' => 'sometimes|nullable|string',
            'cta_button_text' => 'sometimes|string|max:100',
            'cta_button_link' => 'sometimes|string|max:255',
            'cta_background_type' => 'sometimes|string|in:gradient,image,color',
            'cta_background_value' => 'sometimes|nullable|string',
            // Order
            'sections_order' => 'sometimes|array',
        ]);

        $settings->update($validated);

        return response()->json($settings);
    }

    public function getGlobalColors()
    {
        $settings = HomepageSettings::get();
        return response()->json([
            'primary_color' => $settings->primary_color,
            'secondary_color' => $settings->secondary_color,
            'accent_color' => $settings->accent_color,
            'background_color' => $settings->background_color,
            'text_color' => $settings->text_color,
        ]);
    }

    /**
     * Get cookie consent settings (public)
     */
    public function getCookieSettings()
    {
        return response()->json([
            'enabled' => SiteSetting::get('cookie_consent_enabled', 'true') === 'true',
            'text' => SiteSetting::get('cookie_consent_text', 'Koristimo kolačiće i slične tehnologije kako bismo poboljšali vaše korisničko iskustvo.'),
            'accept_button' => SiteSetting::get('cookie_accept_button', 'Prihvati'),
            'reject_button' => SiteSetting::get('cookie_reject_button', 'Odbij'),
        ]);
    }

    /**
     * Update cookie consent settings (admin only)
     */
    public function updateCookieSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'sometimes|boolean',
            'text' => 'sometimes|string|max:1000',
            'accept_button' => 'sometimes|string|max:50',
            'reject_button' => 'sometimes|string|max:50',
        ]);

        if ($request->has('enabled')) {
            SiteSetting::set('cookie_consent_enabled', $request->enabled ? 'true' : 'false');
        }
        if ($request->has('text')) {
            SiteSetting::set('cookie_consent_text', $request->text);
        }
        if ($request->has('accept_button')) {
            SiteSetting::set('cookie_accept_button', $request->accept_button);
        }
        if ($request->has('reject_button')) {
            SiteSetting::set('cookie_reject_button', $request->reject_button);
        }

        return response()->json(['message' => 'Cookie postavke ažurirane']);
    }

    /**
     * Get privacy policy (public)
     */
    public function getPrivacyPolicy()
    {
        return response()->json([
            'title' => SiteSetting::get('privacy_policy_title', 'Politika privatnosti'),
            'content' => SiteSetting::get('privacy_policy_content', ''),
        ]);
    }

    /**
     * Update privacy policy (admin only)
     */
    public function updatePrivacyPolicy(Request $request)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        if ($request->has('title')) {
            SiteSetting::set('privacy_policy_title', $request->title);
        }
        if ($request->has('content')) {
            SiteSetting::set('privacy_policy_content', $request->content);
        }

        return response()->json(['message' => 'Politika privatnosti ažurirana']);
    }

    /**
     * Get terms of service (public)
     */
    public function getTermsOfService()
    {
        return response()->json([
            'title' => SiteSetting::get('terms_of_service_title', 'Uslovi korištenja'),
            'content' => SiteSetting::get('terms_of_service_content', ''),
        ]);
    }

    /**
     * Update terms of service (admin only)
     */
    public function updateTermsOfService(Request $request)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        if ($request->has('title')) {
            SiteSetting::set('terms_of_service_title', $request->title);
        }
        if ($request->has('content')) {
            SiteSetting::set('terms_of_service_content', $request->content);
        }

        return response()->json(['message' => 'Uslovi korištenja ažurirani']);
    }

    /**
     * Get all legal settings for admin
     */
    public function getLegalSettings()
    {
        return response()->json([
            'cookie' => [
                'enabled' => SiteSetting::get('cookie_consent_enabled', 'true') === 'true',
                'text' => SiteSetting::get('cookie_consent_text', ''),
                'accept_button' => SiteSetting::get('cookie_accept_button', 'Prihvati'),
                'reject_button' => SiteSetting::get('cookie_reject_button', 'Odbij'),
            ],
            'privacy_policy' => [
                'title' => SiteSetting::get('privacy_policy_title', 'Politika privatnosti'),
                'content' => SiteSetting::get('privacy_policy_content', ''),
            ],
            'terms_of_service' => [
                'title' => SiteSetting::get('terms_of_service_title', 'Uslovi korištenja'),
                'content' => SiteSetting::get('terms_of_service_content', ''),
            ],
        ]);
    }

    /**
     * Get specialty page template (public)
     */
    public function getSpecialtyTemplate()
    {
        return response()->json([
            'template' => SiteSetting::get('specialty_template', 'classic'),
            'show_stats' => SiteSetting::get('specialty_show_stats', 'true') === 'true'
        ]);
    }

    /**
     * Update specialty page template (admin only)
     */
    public function updateSpecialtyTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:classic,grid,list,cards,modern',
            'show_stats' => 'sometimes|boolean'
        ]);

        SiteSetting::set('specialty_template', $request->template);

        if ($request->has('show_stats')) {
            SiteSetting::set('specialty_show_stats', $request->show_stats ? 'true' : 'false');
        }

        return response()->json(['message' => 'Postavke uspješno ažurirane']);
    }

    /**
     * Get blog typography settings (public)
     */
    public function getBlogTypography()
    {
        return response()->json([
            'h1_size' => SiteSetting::get('blog_h1_size', '28'),
            'h2_size' => SiteSetting::get('blog_h2_size', '24'),
            'h3_size' => SiteSetting::get('blog_h3_size', '20'),
            'p_size' => SiteSetting::get('blog_p_size', '19'),
            'p_line_height' => SiteSetting::get('blog_p_line_height', '34'),
            'p_color' => SiteSetting::get('blog_p_color', '#555'),
        ]);
    }

    /**
     * Update blog typography settings (admin only)
     */
    public function updateBlogTypography(Request $request)
    {
        $request->validate([
            'h1_size' => 'sometimes|numeric|min:12|max:72',
            'h2_size' => 'sometimes|numeric|min:12|max:60',
            'h3_size' => 'sometimes|numeric|min:12|max:48',
            'p_size' => 'sometimes|numeric|min:12|max:32',
            'p_line_height' => 'sometimes|numeric|min:16|max:60',
            'p_color' => 'sometimes|string|max:20',
        ]);

        if ($request->has('h1_size')) {
            SiteSetting::set('blog_h1_size', $request->h1_size);
        }
        if ($request->has('h2_size')) {
            SiteSetting::set('blog_h2_size', $request->h2_size);
        }
        if ($request->has('h3_size')) {
            SiteSetting::set('blog_h3_size', $request->h3_size);
        }
        if ($request->has('p_size')) {
            SiteSetting::set('blog_p_size', $request->p_size);
        }
        if ($request->has('p_line_height')) {
            SiteSetting::set('blog_p_line_height', $request->p_line_height);
        }
        if ($request->has('p_color')) {
            SiteSetting::set('blog_p_color', $request->p_color);
        }

        return response()->json(['message' => 'Blog tipografija ažurirana']);
    }

    /**
     * Get listing template (public)
     */
    public function getListingTemplate(Request $request)
    {
        $type = $request->query('type'); // doctors, clinics, cities, laboratories
        $template = SiteSetting::get("{$type}_listing_template", 'default');

        return response()->json(['template' => $template]);
    }

    /**
     * Update listing template (admin only)
     */
    public function updateListingTemplate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:doctors,clinics,cities,laboratories',
            'template' => 'required|in:default,soft'
        ]);

        SiteSetting::set("{$request->type}_listing_template", $request->template);

        return response()->json(['message' => 'Template ažuriran']);
    }
}
