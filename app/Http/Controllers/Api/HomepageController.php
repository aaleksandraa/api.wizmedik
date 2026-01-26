<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    /**
     * Get all homepage data in a single request
     * Cached for 5 minutes for optimal performance (0 in development)
     */
    public function getData()
    {
        // In development, allow cache bypass with ?nocache=1
        $useCache = config('app.env') === 'production' || !request()->has('nocache');
        $cacheTTL = config('app.env') === 'production' ? 300 : 60; // 5 min in prod, 1 min in dev

        if (!$useCache) {
            return $this->fetchHomepageData();
        }

        return Cache::remember('homepage_data', $cacheTTL, function () {
            return $this->fetchHomepageData();
        });
    }

    /**
     * Fetch homepage data (extracted for reusability)
     */
    private function fetchHomepageData()
    {
            // Get template settings from site_settings table using SiteSetting model
            $settings = [
                'doctor_profile_template' => \App\Models\SiteSetting::get('doctor_profile_template', 'classic'),
                'clinic_profile_template' => \App\Models\SiteSetting::get('clinic_profile_template', 'classic'),
                'homepage_template' => \App\Models\SiteSetting::get('homepage_template', 'classic'),
                'modern_cover_type' => \App\Models\SiteSetting::get('modern_cover_type', 'gradient'),
                'modern_cover_value' => \App\Models\SiteSetting::get('modern_cover_value', 'from-primary via-primary/90 to-primary/80'),
            ];

            // Get top 8 main specialties (no parent)
            $specialties = DB::table('specijalnosti')
                ->whereNull('parent_id')
                ->select('id', 'naziv', 'slug')
                ->limit(8)
                ->get();

            // Get top 12 featured doctors (highest rated)
            $doctors = DB::table('doktori')
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'slug',
                    'ime',
                    'prezime',
                    'specijalnost',
                    'ocjena',
                    'broj_ocjena',
                    'grad',
                    'telefon',
                    'slika_profila',
                    'prihvata_online',
                    'latitude',
                    'longitude'
                )
                ->orderBy('ocjena', 'desc')
                ->orderBy('broj_ocjena', 'desc')
                ->limit(12)
                ->get();

            // Get doctor counts by specialty
            $doctorCounts = DB::table('doktori')
                ->whereNull('deleted_at')
                ->select('specijalnost', DB::raw('count(*) as count'))
                ->groupBy('specijalnost')
                ->pluck('count', 'specijalnost');

            // Get top 6 featured clinics
            $clinics = DB::table('klinike')
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'slug',
                    'naziv',
                    'opis',
                    'adresa',
                    'grad',
                    'telefon',
                    'email',
                    'website',
                    'slike',
                    'radno_vrijeme'
                )
                ->limit(6)
                ->get()
                ->map(function ($clinic) {
                    $clinic->slike = json_decode($clinic->slike, true) ?? [];
                    $clinic->radno_vrijeme = json_decode($clinic->radno_vrijeme, true) ?? [];
                    return $clinic;
                });

            // Get top 4 featured banje (spas)
            $banje = DB::table('banje')
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'slug',
                    'naziv',
                    'opis',
                    'adresa',
                    'grad',
                    'telefon',
                    'email',
                    'website',
                    'galerija',
                    'radno_vrijeme'
                )
                ->limit(4)
                ->get()
                ->map(function ($banja) {
                    // Map galerija to slike for consistency with clinics
                    $banja->slike = json_decode($banja->galerija, true) ?? [];
                    $banja->radno_vrijeme = json_decode($banja->radno_vrijeme, true) ?? [];
                    unset($banja->galerija); // Remove galerija after mapping
                    return $banja;
                });

            // Get top 4 featured domovi (care homes)
            $domovi = DB::table('domovi_njega')
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'slug',
                    'naziv',
                    'opis',
                    'adresa',
                    'grad',
                    'telefon',
                    'email',
                    'website',
                    'galerija',
                    'radno_vrijeme'
                )
                ->limit(4)
                ->get()
                ->map(function ($dom) {
                    // Map galerija to slike for consistency with clinics
                    $dom->slike = json_decode($dom->galerija, true) ?? [];
                    $dom->radno_vrijeme = json_decode($dom->radno_vrijeme, true) ?? [];
                    unset($dom->galerija); // Remove galerija after mapping
                    return $dom;
                });

            // Get unique specialties and cities for filters
            $allSpecialties = DB::table('doktori')
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('specijalnost')
                ->filter()
                ->values();

            $allCities = DB::table('doktori')
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('grad')
                ->filter()
                ->values();

            // Get cities with doctor counts for homepage
            $citiesWithCounts = DB::table('gradovi')
                ->leftJoin('doktori', function($join) {
                    $join->on('gradovi.naziv', '=', 'doktori.grad')
                         ->whereNull('doktori.deleted_at');
                })
                ->select('gradovi.id', 'gradovi.naziv', 'gradovi.slug', DB::raw('count(doktori.id) as broj_doktora'))
                ->groupBy('gradovi.id', 'gradovi.naziv', 'gradovi.slug')
                ->orderBy('broj_doktora', 'desc')
                ->limit(20)
                ->get();

            // Get recent questions
            $pitanja = DB::table('pitanja')
                ->select('id', 'naslov', 'slug', 'sadrzaj', 'broj_pregleda', 'je_odgovoreno', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($pitanje) {
                    // Get answer count
                    $broj_odgovora = DB::table('odgovori_na_pitanja')
                        ->where('pitanje_id', $pitanje->id)
                        ->count();

                    $pitanje->broj_odgovora = $broj_odgovora;
                    $pitanje->ima_prihvacen_odgovor = $pitanje->je_odgovoreno;

                    return $pitanje;
                });

            // Get recent blog posts (if table exists)
            $blogPosts = [];
            try {
                $blogPosts = DB::table('blog_postovi')
                    ->where('status', 'published')
                    ->select('id', 'naslov', 'slug', 'kratak_opis', 'sadrzaj', 'slika_url', 'kategorija_id', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(3)
                    ->get()
                    ->map(function ($post) {
                        // Get category
                        if ($post->kategorija_id) {
                            $kategorija = DB::table('blog_kategorije')
                                ->where('id', $post->kategorija_id)
                                ->select('id', 'naziv', 'slug')
                                ->first();
                            $post->kategorija = $kategorija;
                        }
                        return $post;
                    });
            } catch (\Exception $e) {
                // Table doesn't exist yet, return empty array
                $blogPosts = [];
            }

            // Get homepage settings
            $homepageCustomSettings = DB::table('homepage_settings')
                ->select(
                    'primary_color', 'secondary_color', 'accent_color',
                    'hero_enabled', 'hero_title', 'hero_subtitle',
                    'search_enabled', 'doctors_enabled', 'doctors_title', 'doctors_subtitle', 'doctors_count',
                    'clinics_enabled', 'clinics_title', 'clinics_subtitle', 'clinics_count',
                    'specialties_enabled', 'specialties_title', 'specialties_subtitle', 'specialties_count',
                    'blog_enabled', 'blog_title', 'blog_subtitle', 'blog_count',
                    'cta_enabled', 'cta_title', 'cta_subtitle', 'cta_button_text', 'cta_button_link'
                )
                ->first();

            return response()->json([
                'settings' => $settings,
                'specialties' => $specialties->map(function ($spec) use ($doctorCounts) {
                    return [
                        'id' => $spec->id,
                        'naziv' => $spec->naziv,
                        'slug' => $spec->slug,
                        'doctor_count' => $doctorCounts[$spec->naziv] ?? 0,
                    ];
                }),
                'doctors' => $doctors,
                'clinics' => $clinics,
                'banje' => $banje,
                'domovi' => $domovi,
                'cities' => $citiesWithCounts,
                'pitanja' => $pitanja,
                'blog_posts' => $blogPosts,
                'homepage_custom_settings' => $homepageCustomSettings,
                'filters' => [
                    'specialties' => $allSpecialties,
                    'cities' => $allCities,
                ],
            ]);
    }

    /**
     * Clear homepage cache (call this when data changes)
     */
    public function clearCache()
    {
        Cache::forget('homepage_data');
        return response()->json(['message' => 'Homepage cache cleared']);
    }
}
