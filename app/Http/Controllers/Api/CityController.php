<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Grad, Doktor, Klinika, Banja, Dom, Laboratorija};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    /**
     * Cache key for cities list
     */
    private const CITIES_CACHE_KEY = 'cities_with_counts_v2';

    /**
     * Cache TTL - 24 hours (data rarely changes)
     */
    private const CITIES_CACHE_TTL = 86400;

    /**
     * Cache TTL for individual city - 12 hours
     */
    private const CITY_CACHE_TTL = 43200;

    public function index()
    {
        // Use Redis tags if available for better cache invalidation
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            return Cache::tags(['cities', 'counts'])->remember(
                self::CITIES_CACHE_KEY,
                self::CITIES_CACHE_TTL,
                fn() => $this->getCitiesWithCounts()
            );
        }

        // Fallback to regular cache
        return Cache::remember(
            self::CITIES_CACHE_KEY,
            self::CITIES_CACHE_TTL,
            fn() => $this->getCitiesWithCounts()
        );
    }

    /**
     * Get all cities with counts - optimized single query approach
     */
    private function getCitiesWithCounts()
    {
        // Get all counts in parallel using single queries
        $doktorCounts = Doktor::select('grad', DB::raw('count(*) as count'))
            ->groupBy('grad')
            ->pluck('count', 'grad');

        $klinikaCounts = Klinika::select('grad', DB::raw('count(*) as count'))
            ->groupBy('grad')
            ->pluck('count', 'grad');

        $banjaCounts = Banja::select('grad', DB::raw('count(*) as count'))
            ->where('aktivan', true)
            ->groupBy('grad')
            ->pluck('count', 'grad');

        $domCounts = Dom::select('grad', DB::raw('count(*) as count'))
            ->groupBy('grad')
            ->pluck('count', 'grad');

        $laboratorijaCounts = Laboratorija::select('grad', DB::raw('count(*) as count'))
            ->where('aktivan', true)
            ->groupBy('grad')
            ->pluck('count', 'grad');

        // Get cities with only needed fields
        $cities = Grad::active()
            ->select(['id', 'naziv', 'slug', 'opis', 'populacija'])
            ->orderBy('naziv')
            ->get();

        // Add counts from pre-fetched data (no additional queries)
        $cities->each(function ($city) use ($doktorCounts, $klinikaCounts, $banjaCounts, $domCounts, $laboratorijaCounts) {
            $city->broj_doktora = $doktorCounts[$city->naziv] ?? 0;
            $city->broj_klinika = $klinikaCounts[$city->naziv] ?? 0;
            $city->broj_banja = $banjaCounts[$city->naziv] ?? 0;
            $city->broj_domova = $domCounts[$city->naziv] ?? 0;
            $city->broj_laboratorija = $laboratorijaCounts[$city->naziv] ?? 0;
        });

        return response()->json($cities);
    }

    public function show($slug)
    {
        $cacheKey = "city_{$slug}_v2";
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            return Cache::tags(['cities', 'city_detail'])->remember(
                $cacheKey,
                self::CITY_CACHE_TTL,
                fn() => $this->getCityDetail($slug)
            );
        }

        return Cache::remember(
            $cacheKey,
            self::CITY_CACHE_TTL,
            fn() => $this->getCityDetail($slug)
        );
    }

    /**
     * Get single city with counts
     */
    private function getCityDetail($slug)
    {
        $city = Grad::where('slug', $slug)->firstOrFail();

        // Add dynamic counts
        $city->broj_doktora = Doktor::where('grad', $city->naziv)->count();
        $city->broj_klinika = Klinika::where('grad', $city->naziv)->count();
        $city->broj_banja = Banja::where('grad', $city->naziv)->where('aktivan', true)->count();
        $city->broj_domova = Dom::where('grad', $city->naziv)->count();
        $city->broj_laboratorija = Laboratorija::where('grad', $city->naziv)->where('aktivan', true)->count();

        return response()->json($city);
    }

    /**
     * Admin index - returns all cities without caching for admin panel
     */
    public function adminIndex()
    {
        $cities = Grad::orderBy('naziv')->get();
        return response()->json($cities);
    }

    /**
     * Clear cities cache - call this when data changes
     */
    public static function clearCache()
    {
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            Cache::tags(['cities'])->flush();
        } else {
            Cache::forget(self::CITIES_CACHE_KEY);
            // Clear individual city caches
            $cities = Grad::pluck('slug');
            foreach ($cities as $slug) {
                Cache::forget("city_{$slug}_v2");
            }
        }
    }
}
