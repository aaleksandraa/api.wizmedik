<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specijalnost;

class SpecialtyController extends Controller
{
    public function index()
    {
        // Cache specialty list for 30 days since subcategories rarely change
        $specialties = \Cache::remember(
            'specialties:all',
            now()->addDays(30),
            function () {
                return Specijalnost::active()
                    ->select('id', 'naziv', 'slug', 'icon_url', 'parent_id', 'opis')
                    ->with(['children' => function($q) {
                        $q->select('id', 'naziv', 'slug', 'icon_url', 'parent_id', 'opis')
                          ->orderBy('sort_order')
                          ->orderBy('naziv');
                    }])
                    ->topLevel()
                    ->orderBy('sort_order')
                    ->orderBy('naziv')
                    ->get();
            }
        );

        return response()->json($specialties);
    }

    public function show($slug)
    {
        // Cache specialty data for 30 days since it rarely changes
        $specialty = \Cache::remember(
            "specialty:{$slug}",
            now()->addDays(30),
            function () use ($slug) {
                return Specijalnost::where('slug', $slug)
                    ->with(['children', 'parent', 'doktori', 'klinike'])
                    ->firstOrFail();
            }
        );

        return response()->json($specialty);
    }

    /**
     * Smart search - returns matching specialty based on keywords
     */
    public function smartSearch($query)
    {
        $query = mb_strtolower(trim($query));

        // First try exact match on naziv or slug
        $specialty = Specijalnost::active()
            ->where(function($q) use ($query) {
                $q->whereRaw('LOWER(naziv) LIKE ?', ["%{$query}%"])
                  ->orWhere('slug', 'LIKE', "%{$query}%");
            })
            ->first();

        if ($specialty) {
            return response()->json([
                'found' => true,
                'type' => 'specialty',
                'specialty' => $specialty,
                'redirect' => '/doktori/specijalnost/' . $specialty->slug
            ]);
        }

        // Search in keywords
        $specialties = Specijalnost::active()->get();

        foreach ($specialties as $spec) {
            $keywords = $spec->kljucne_rijeci ?? [];
            foreach ($keywords as $keyword) {
                if (mb_strpos(mb_strtolower($keyword), $query) !== false ||
                    mb_strpos($query, mb_strtolower($keyword)) !== false) {
                    return response()->json([
                        'found' => true,
                        'type' => 'specialty',
                        'specialty' => $spec,
                        'redirect' => '/doktori/specijalnost/' . $spec->slug
                    ]);
                }
            }
        }

        // No specialty match - return general search
        return response()->json([
            'found' => false,
            'type' => 'general',
            'redirect' => '/doktori?pretraga=' . urlencode($query)
        ]);
    }

    /**
     * Get all specialties with keywords for frontend caching
     */
    public function searchData()
    {
        $specialties = Specijalnost::active()
            ->select('id', 'naziv', 'slug', 'kljucne_rijeci')
            ->get()
            ->map(function($spec) {
                return [
                    'id' => $spec->id,
                    'naziv' => $spec->naziv,
                    'slug' => $spec->slug,
                    'keywords' => $spec->kljucne_rijeci ?? []
                ];
            });

        return response()->json($specialties);
    }

    /**
     * Get specialties with doctor counts (optimized)
     */
    public function withCounts()
    {
        $specialties = Specijalnost::active()
            ->select('id', 'naziv', 'slug', 'icon_url', 'parent_id', 'opis')
            ->with(['children' => function($q) {
                $q->select('id', 'naziv', 'slug', 'icon_url', 'parent_id', 'opis');
            }])
            ->topLevel()
            ->orderBy('naziv')
            ->get();

        // Get doctor counts efficiently with a single query
        $doctorCounts = \DB::table('doktori')
            ->select('specijalnost', \DB::raw('count(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('specijalnost')
            ->pluck('count', 'specijalnost');

        // Add counts to specialties
        $specialties->each(function($specialty) use ($doctorCounts) {
            $specialty->doctor_count = $doctorCounts[$specialty->naziv] ?? 0;

            if ($specialty->children) {
                $specialty->children->each(function($child) use ($doctorCounts) {
                    $child->doctor_count = $doctorCounts[$child->naziv] ?? 0;
                });
            }
        });

        return response()->json($specialties);
    }
}
