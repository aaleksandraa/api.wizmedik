<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesSorting;
use App\Http\Controllers\Concerns\SortsByDistance;
use App\Http\Controllers\Controller;
use App\Models\Klinika;
use App\Models\ProfileSlugRedirect;
use App\Models\Specijalnost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClinicController extends Controller
{
    use ResolvesSorting;
    use SortsByDistance;

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 30), 100);

        $query = Klinika::active()
            ->verifikovan()
            ->select(
                'klinike.id',
                'klinike.naziv',
                'klinike.slug',
                'klinike.opis',
                'klinike.grad',
                'klinike.adresa',
                'klinike.telefon',
                'klinike.email',
                'klinike.website',
                'klinike.ocjena',
                'klinike.broj_ocjena',
                'klinike.slike',
                'klinike.radno_vrijeme',
                'klinike.latitude',
                'klinike.longitude'
            )
            ->with([
                'specijalnosti' => function ($specialtyQuery) {
                    $specialtyQuery->select(
                        'specijalnosti.id',
                        'specijalnosti.naziv',
                        'specijalnosti.slug',
                        'specijalnosti.parent_id'
                    );
                },
            ])
            ->withCount([
                'doktori as broj_doktora' => function ($doctorQuery) {
                    $doctorQuery->aktivan()->verifikovan();
                },
            ]);

        if ($request->filled('grad')) {
            $cityValue = trim((string) $request->grad);
            $normalizedCity = mb_strtolower(str_replace('-', ' ', $cityValue));
            $query->whereRaw('LOWER(klinike.grad) = ?', [$normalizedCity]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('klinike.naziv', 'ilike', '%' . $search . '%')
                    ->orWhere('klinike.opis', 'ilike', '%' . $search . '%')
                    ->orWhere('klinike.adresa', 'ilike', '%' . $search . '%');
            });
        }

        $this->applySpecialtyFilter($query, $request);

        $usingLimit = $request->has('limit');
        $sortBy = (string) $request->get('sort_by', 'naziv');
        if ($sortBy === 'name') {
            $sortBy = 'naziv';
        }
        if ($sortBy === 'rating') {
            $sortBy = 'ocjena';
        }

        if (! $usingLimit) {
            $request->merge(['sort_by' => $sortBy]);

            if (! $this->applyDistanceSort($query, $request, 'klinike')) {
                $this->applySafeSort(
                    $query,
                    $request,
                    ['ocjena', 'broj_ocjena', 'naziv', 'grad', 'created_at'],
                    'naziv',
                    'asc'
                );
                $query->orderBy('klinike.id');
            }
        }

        $cacheable = ! $request->filled('grad')
            && ! $request->filled('search')
            && ! $request->filled('specijalnost')
            && $sortBy !== 'distance'
            && ! $request->filled('lat');

        if ($request->has('limit')) {
            $limit = min((int) $request->get('limit'), 1000);

            $result = $cacheable
                ? Cache::remember("clinics:list:limit:{$limit}", now()->addMinutes(5), fn () => $query->limit($limit)->get())
                : $query->limit($limit)->get();

            return response()->json($result);
        }

        $page = max(1, (int) $request->get('page', 1));
        $sortOrder = strtolower((string) $request->get('sort_order', 'asc'));
        $cacheKey = "clinics:list:pp:{$perPage}:p:{$page}:s:{$sortBy}:o:{$sortOrder}";

        $result = $cacheable
            ? Cache::remember($cacheKey, now()->addMinutes(5), fn () => $query->paginate($perPage))
            : $query->paginate($perPage);

        return response()->json($result);
    }

    public function show($slug)
    {
        $clinic = Klinika::where('slug', $slug)
            ->with([
                'doktori' => function ($doctorQuery) {
                    $doctorQuery->aktivan()->verifikovan();
                },
                'specijalnosti' => function ($specialtyQuery) {
                    $specialtyQuery->select('specijalnosti.id', 'specijalnosti.naziv', 'specijalnosti.slug');
                },
            ])
            ->first();

        if (!$clinic) {
            if ($currentSlug = ProfileSlugRedirect::resolveCurrentSlug('klinika', $slug, 'klinike')) {
                return response()->json([
                    'redirect_to' => "/klinika/{$currentSlug}",
                    'slug' => $currentSlug,
                ]);
            }

            return response()->json([
                'message' => 'Klinika nije pronaÄ‘ena',
                'slug' => $slug,
            ], 404);
        }

        if (!$clinic->aktivan || !$clinic->verifikovan) {
            return response()->json([
                'message' => 'Klinika trenutno nije dostupna',
                'slug' => $slug,
                'aktivan' => $clinic->aktivan,
                'verifikovan' => $clinic->verifikovan,
            ], 404);
        }

        return response()->json($clinic);
    }

    private function applySpecialtyFilter($query, Request $request): void
    {
        if (! $request->filled('specijalnost')) {
            return;
        }

        $values = preg_split('/\s*,\s*/', (string) $request->specijalnost, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($values === []) {
            return;
        }

        $expandChildren = count($values) === 1;
        $specialtyIds = [];
        foreach ($values as $value) {
            $specialtyIds = array_merge($specialtyIds, $this->resolveSpecialtyIds($value, $expandChildren));
        }
        $specialtyIds = array_values(array_unique(array_map('intval', $specialtyIds)));

        $specialtyNames = Specijalnost::query()
            ->whereIn('id', $specialtyIds)
            ->pluck('naziv')
            ->map(fn ($name) => mb_strtolower((string) $name))
            ->values()
            ->all();

        if ($specialtyIds === [] && $specialtyNames === []) {
            return;
        }

        $query->where(function ($builder) use ($specialtyIds, $specialtyNames) {
            if ($specialtyIds !== []) {
                $builder->whereHas('specijalnosti', function ($specialtyQuery) use ($specialtyIds) {
                    $specialtyQuery->whereIn('specijalnosti.id', $specialtyIds);
                });
            }

            $builder->orWhereHas('doktori', function ($doctorQuery) use ($specialtyIds, $specialtyNames) {
                $doctorQuery->aktivan()
                    ->verifikovan()
                    ->where(function ($matchQuery) use ($specialtyIds, $specialtyNames) {
                        if ($specialtyIds !== []) {
                            $matchQuery->whereIn('specijalnost_id', $specialtyIds);
                        }

                        if ($specialtyNames !== []) {
                            $method = $specialtyIds !== [] ? 'orWhereIn' : 'whereIn';
                            $matchQuery->{$method}(DB::raw('LOWER(specijalnost)'), $specialtyNames);
                        }
                    });
            });
        });
    }

    /**
     * @return array<int, int>
     */
    private function resolveSpecialtyIds(string $value, bool $includeChildren = true): array
    {
        $normalizedValue = trim($value);
        if ($normalizedValue === '') {
            return [];
        }

        $decodedName = str_replace('-', ' ', urldecode($normalizedValue));

        $baseSpecialty = Specijalnost::query()
            ->where('aktivan', true)
            ->where(function ($query) use ($normalizedValue, $decodedName) {
                if (is_numeric($normalizedValue)) {
                    $query->orWhere('id', (int) $normalizedValue);
                }

                $query->orWhere('slug', $normalizedValue)
                    ->orWhereRaw('LOWER(naziv) = ?', [mb_strtolower($decodedName)]);
            })
            ->first();

        if (! $baseSpecialty) {
            return [];
        }

        $ids = [(int) $baseSpecialty->id];

        if ($includeChildren) {
            $childIds = Specijalnost::query()
                ->where('parent_id', $baseSpecialty->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_merge($ids, $childIds);
        }

        return array_values(array_unique($ids));
    }
}
