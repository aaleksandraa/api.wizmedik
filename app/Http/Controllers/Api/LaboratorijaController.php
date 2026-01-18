<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laboratorija;
use App\Models\Analiza;
use App\Models\KategorijaAnalize;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaboratorijaController extends Controller
{
    /**
     * Get all laboratories with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 12);
        $grad = $request->input('grad');
        $search = $request->input('search');
        $kategorija = $request->input('kategorija');
        $onlineRezultati = $request->input('online_rezultati');
        $sortBy = $request->input('sort_by', 'naziv');
        $sortOrder = $request->input('sort_order', 'asc');

        $cacheKey = "laboratorije_list_{$perPage}_{$grad}_{$search}_{$kategorija}_{$onlineRezultati}_{$sortBy}_{$sortOrder}";

        $laboratorije = Cache::remember($cacheKey, 120, function () use ($perPage, $grad, $search, $kategorija, $onlineRezultati, $sortBy, $sortOrder) {
            $query = Laboratorija::with(['radnoVrijeme'])
                ->withCount(['aktivneAnalize as analize_count'])
                ->aktivan()
                ->verifikovan();

            // Filters
            if ($grad) {
                $query->byGrad($grad);
            }

            if ($search) {
                $query->search($search);
            }

            if ($onlineRezultati) {
                $query->withOnlineRezultati();
            }

            // Filter by category - only show labs that have analyses in this category
            if ($kategorija) {
                $query->whereHas('aktivneAnalize', function ($q) use ($kategorija) {
                    $q->whereHas('kategorija', function ($kq) use ($kategorija) {
                        $kq->where('slug', $kategorija);
                    });
                });
            }

            // Sorting
            $allowedSorts = ['naziv', 'grad', 'prosjecna_ocjena', 'broj_recenzija', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            return $query->paginate($perPage);
        });

        return response()->json($laboratorije);
    }

    /**
     * Get laboratory by slug with all details
     */
    public function show(string $slug): JsonResponse
    {
        $cacheKey = "laboratorija_{$slug}";

        $laboratorija = Cache::remember($cacheKey, 300, function () use ($slug) {
            return Laboratorija::with([
                'radnoVrijeme',
                'galerija',
                'klinika',
                'doktor'
            ])
            ->where('slug', $slug)
            ->aktivan()
            ->verifikovan()
            ->firstOrFail();
        });

        // Convert radnoVrijeme relationship to radno_vrijeme format for frontend
        $radnoVrijemeFormatted = [];
        foreach ($laboratorija->radnoVrijeme as $rv) {
            $radnoVrijemeFormatted[$rv->dan] = [
                'open' => $rv->otvaranje ? substr($rv->otvaranje, 0, 5) : null,
                'close' => $rv->zatvaranje ? substr($rv->zatvaranje, 0, 5) : null,
                'closed' => $rv->zatvoreno,
            ];
        }

        $labData = $laboratorija->toArray();
        $labData['radno_vrijeme'] = $radnoVrijemeFormatted;

        // Increment views (async, don't wait)
        dispatch(function () use ($laboratorija) {
            $laboratorija->incrementViews();
        })->afterResponse();

        return response()->json($labData);
    }

    /**
     * Get all analyses for a laboratory
     */
    public function getAnalize(int $id, Request $request): JsonResponse
    {
        $kategorijaId = $request->input('kategorija_id');
        $search = $request->input('search');
        $naAkciji = $request->input('na_akciji');
        $minCijena = $request->input('min_cijena');
        $maxCijena = $request->input('max_cijena');
        $sortBy = $request->input('sort_by', 'redoslijed');

        $query = Analiza::with('kategorija')
            ->where('laboratorija_id', $id)
            ->aktivan();

        // Filters
        if ($kategorijaId) {
            $query->byKategorija($kategorijaId);
        }

        if ($search) {
            $query->search($search);
        }

        if ($naAkciji) {
            $query->naAkciji();
        }

        if ($minCijena && $maxCijena) {
            $query->priceRange($minCijena, $maxCijena);
        }

        // Sorting
        switch ($sortBy) {
            case 'cijena_asc':
                $query->orderByRaw('COALESCE(akcijska_cijena, cijena) ASC');
                break;
            case 'cijena_desc':
                $query->orderByRaw('COALESCE(akcijska_cijena, cijena) DESC');
                break;
            case 'naziv':
                $query->orderBy('naziv');
                break;
            default:
                $query->ordered();
        }

        $analize = $query->get();

        // Group by category
        $grouped = $analize->groupBy('kategorija.naziv');

        return response()->json([
            'analize' => $analize,
            'grouped' => $grouped,
            'total' => $analize->count(),
        ]);
    }

    /**
     * Search analyses across all laboratories
     */
    public function searchAnalize(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $grad = $request->input('grad');
        $kategorijaId = $request->input('kategorija_id');
        $perPage = $request->input('per_page', 20);

        if (!$search) {
            return response()->json([
                'message' => 'Parametar pretrage je obavezan',
            ], 400);
        }

        $query = Analiza::with(['laboratorija', 'kategorija'])
            ->whereHas('laboratorija', function ($q) use ($grad) {
                $q->aktivan()->verifikovan();
                if ($grad) {
                    $q->byGrad($grad);
                }
            })
            ->aktivan()
            ->search($search);

        if ($kategorijaId) {
            $query->byKategorija($kategorijaId);
        }

        $analize = $query->orderByRaw('COALESCE(akcijska_cijena, cijena) ASC')
            ->paginate($perPage);

        // Increment search count (async)
        dispatch(function () use ($analize) {
            foreach ($analize as $analiza) {
                $analiza->incrementSearches();
            }
        })->afterResponse();

        return response()->json($analize);
    }

    /**
     * Get laboratories by city
     */
    public function getByGrad(string $grad): JsonResponse
    {
        $laboratorije = Laboratorija::with('radnoVrijeme')
            ->aktivan()
            ->verifikovan()
            ->byGrad($grad)
            ->orderBy('naziv')
            ->get();

        return response()->json($laboratorije);
    }

    /**
     * Get all analysis categories
     */
    public function getKategorije(): JsonResponse
    {
        $kategorije = Cache::remember('kategorije_analiza_with_count', 3600, function () {
            return KategorijaAnalize::aktivan()
                ->ordered()
                ->withCount(['aktivneAnalize' => function ($query) {
                    $query->whereHas('laboratorija', function ($q) {
                        $q->aktivan()->verifikovan();
                    });
                }])
                ->get();
        });

        return response()->json($kategorije);
    }

    /**
     * Get overall statistics for all laboratories
     */
    public function getAllStatistics(): JsonResponse
    {
        $stats = Cache::remember('laboratorije_all_statistics', 300, function () {
            return [
                'ukupno_laboratorija' => Laboratorija::aktivan()->verifikovan()->count(),
                'ukupno_analiza' => Analiza::whereHas('laboratorija', function ($q) {
                    $q->aktivan()->verifikovan();
                })->aktivan()->count(),
                'ukupno_kategorija' => KategorijaAnalize::aktivan()->count(),
                'ukupno_gradova' => Laboratorija::aktivan()->verifikovan()->distinct('grad')->count('grad'),
                'prosjecna_ocjena' => round(Laboratorija::aktivan()->verifikovan()->avg('prosjecna_ocjena'), 2),
                'ukupno_recenzija' => Laboratorija::aktivan()->verifikovan()->sum('broj_recenzija'),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get laboratory packages
     */
    public function getPaketi(int $id): JsonResponse
    {
        $laboratorija = Laboratorija::findOrFail($id);

        $paketi = $laboratorija->paketi()
            ->aktivan()
            ->ordered()
            ->get();

        return response()->json([
            'paketi' => $paketi,
            'total' => $paketi->count(),
        ]);
    }

    /**
     * Get laboratory statistics (public)
     */
    public function getStatistics(int $id): JsonResponse
    {
        $laboratorija = Laboratorija::findOrFail($id);

        $stats = [
            'broj_analiza' => $laboratorija->aktivneAnalize()->count(),
            'broj_kategorija' => $laboratorija->aktivneAnalize()
                ->distinct('kategorija_id')
                ->count('kategorija_id'),
            'prosjecna_ocjena' => $laboratorija->prosjecna_ocjena,
            'broj_recenzija' => $laboratorija->broj_recenzija,
            'online_rezultati' => $laboratorija->online_rezultati,
            'prosjecno_vrijeme_rezultata' => $laboratorija->prosjecno_vrijeme_rezultata,
        ];

        return response()->json($stats);
    }

    /**
     * Get cities with laboratories
     */
    public function getGradovi(): JsonResponse
    {
        $gradovi = Cache::remember('laboratorije_gradovi', 3600, function () {
            return Laboratorija::aktivan()
                ->verifikovan()
                ->select('grad', DB::raw('count(*) as broj_laboratorija'))
                ->groupBy('grad')
                ->orderBy('grad')
                ->get();
        });

        return response()->json($gradovi);
    }

    /**
     * Get popular analyses
     */
    public function getPopularneAnalize(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $analize = Analiza::with(['laboratorija', 'kategorija'])
            ->whereHas('laboratorija', function ($q) {
                $q->aktivan()->verifikovan();
            })
            ->aktivan()
            ->orderBy('broj_pretraga', 'desc')
            ->orderBy('broj_pregleda', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($analize);
    }

    /**
     * Get analyses on sale
     */
    public function getAnalizenaAkciji(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $grad = $request->input('grad');

        $query = Analiza::with(['laboratorija', 'kategorija'])
            ->whereHas('laboratorija', function ($q) use ($grad) {
                $q->aktivan()->verifikovan();
                if ($grad) {
                    $q->byGrad($grad);
                }
            })
            ->aktivan()
            ->naAkciji();

        $analize = $query->orderByRaw('((cijena - akcijska_cijena) / cijena) DESC')
            ->paginate($perPage);

        return response()->json($analize);
    }
}
