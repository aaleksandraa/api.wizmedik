<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApotekaDezurstvo;
use App\Models\ApotekaPoslovnica;
use App\Models\Grad;
use App\Services\ApotekaAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApotekaController extends Controller
{
    public function __construct(
        private readonly ApotekaAvailabilityService $availabilityService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'grad' => 'nullable|string|max:120',
            'search' => 'nullable|string|max:120',
            'open_now' => 'nullable|boolean',
            'dezurna_now' => 'nullable|boolean',
            'is_24h' => 'nullable|boolean',
            'pensioner_discount' => 'nullable|boolean',
            'has_actions' => 'nullable|boolean',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:300',
            'sort' => 'nullable|in:distance,open_first,rating,name',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $items = $this->buildListingItems($request);
        $sort = $request->string('sort')->toString() ?: 'open_first';
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        $items = $this->applySort($items, $sort, $lat !== null && $lng !== null);

        $perPage = (int) ($request->input('per_page', 12));
        $page = (int) ($request->input('page', 1));
        $total = $items->count();
        $lastPage = max((int) ceil($total / max($perPage, 1)), 1);
        $page = min(max($page, 1), $lastPage);
        $pageItems = $items->forPage($page, $perPage)->values();

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'total' => $total,
            'data' => $pageItems,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $branch = ApotekaPoslovnica::query()
            ->publicVisible()
            ->with([
                'firma',
                'grad',
                'radnoVrijeme',
                'radnoVrijemeIzuzeci',
                'dezurstva' => function ($query) {
                    $query->where('status', 'confirmed')
                        ->orderBy('starts_at');
                },
                'popusti',
                'akcije',
                'posebnePonude',
                'firma.popusti',
                'firma.akcije',
                'firma.posebnePonude',
            ])
            ->where('slug', $slug)
            ->first();

        if (!$branch) {
            return response()->json([
                'message' => 'Apoteka nije pronadjena.',
            ], 404);
        }

        $now = CarbonImmutable::now('Europe/Sarajevo');
        $status = $this->availabilityService->resolveStatus($branch, $now);

        $activeDiscounts = $this->activeDiscounts($branch, $now);
        $activeActions = $this->activeActions($branch, $now);
        $activeOffers = $this->activeSpecialOffers($branch, $now);

        $payload = $branch->toArray();
        $payload['status'] = $status;
        $payload['active_discounts'] = $activeDiscounts->values()->all();
        $payload['active_actions'] = $activeActions->values()->all();
        $payload['active_offers'] = $activeOffers->values()->all();
        $payload['schema_ready'] = $this->buildSchemaReadyPayload($branch, $status, $activeOffers);

        return response()->json($payload);
    }

    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:300',
            'open_now' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $items = $this->buildListingItems($request);
        $items = $this->applySort($items, 'distance', true);

        $limit = (int) ($request->input('limit', 20));

        return response()->json([
            'data' => $items->take($limit)->values(),
        ]);
    }

    public function dezurne(Request $request): JsonResponse
    {
        $request->validate([
            'grad' => 'required|string|max:120',
            'at' => 'nullable|date',
            'nonstop_only' => 'nullable|boolean',
        ]);

        $city = $this->resolveCity($request->input('grad'));
        if (!$city) {
            return response()->json([
                'message' => 'Grad nije pronadjen.',
            ], 404);
        }

        $moment = $request->filled('at')
            ? CarbonImmutable::parse($request->input('at'), 'Europe/Sarajevo')
            : CarbonImmutable::now('Europe/Sarajevo');
        $momentUtc = $moment->setTimezone('UTC');

        $shifts = ApotekaDezurstvo::query()
            ->with(['poslovnica.firma'])
            ->where('grad_id', $city->id)
            ->where('status', 'confirmed')
            ->where('starts_at', '<=', $momentUtc)
            ->where('ends_at', '>', $momentUtc)
            ->when(
                $request->boolean('nonstop_only'),
                fn ($query) => $query->where('is_nonstop', true)
            )
            ->whereHas('poslovnica', fn ($query) => $query->publicVisible())
            ->orderBy('starts_at')
            ->get();

        $data = $shifts
            ->map(function (ApotekaDezurstvo $shift) use ($moment) {
                $branch = $shift->poslovnica;
                if (!$branch) {
                    return null;
                }

                $status = $this->availabilityService->resolveStatus($branch, $moment);

                return [
                    'duty_shift_id' => $shift->id,
                    'starts_at' => CarbonImmutable::parse($shift->starts_at)->setTimezone('Europe/Sarajevo')->toIso8601String(),
                    'ends_at' => CarbonImmutable::parse($shift->ends_at)->setTimezone('Europe/Sarajevo')->toIso8601String(),
                    'tip' => $shift->tip,
                    'is_nonstop' => (bool) $shift->is_nonstop,
                    'poslovnica' => [
                        'id' => $branch->id,
                        'naziv' => $branch->naziv,
                        'slug' => $branch->slug,
                        'adresa' => $branch->adresa,
                        'grad_naziv' => $branch->grad_naziv ?: $city->naziv,
                        'telefon' => $branch->telefon,
                        'profilna_slika_url' => $branch->profilna_slika_url,
                        'status' => $status,
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'city' => [
                'id' => $city->id,
                'naziv' => $city->naziv,
                'slug' => $city->slug,
            ],
            'at' => $moment->toIso8601String(),
            'data' => $data,
        ]);
    }

    private function buildListingItems(Request $request): Collection
    {
        $query = ApotekaPoslovnica::query()
            ->publicVisible()
            ->with([
                'firma',
                'grad',
                'radnoVrijeme',
                'radnoVrijemeIzuzeci',
                'dezurstva',
                'popusti',
                'akcije',
                'posebnePonude',
                'firma.popusti',
                'firma.akcije',
                'firma.posebnePonude',
            ]);

        $cityFilter = $request->input('grad');
        if ($cityFilter) {
            $city = $this->resolveCity($cityFilter);
            if ($city) {
                $query->where(function ($q) use ($city) {
                    $q->where('grad_id', $city->id)
                        ->orWhereRaw('LOWER(COALESCE(grad_naziv, \'\')) = ?', [mb_strtolower($city->naziv)]);
                });
            } else {
                $query->whereRaw('LOWER(COALESCE(grad_naziv, \'\')) = ?', [mb_strtolower($cityFilter)]);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('naziv', 'ilike', "%{$search}%")
                    ->orWhere('adresa', 'ilike', "%{$search}%")
                    ->orWhere('kratki_opis', 'ilike', "%{$search}%")
                    ->orWhereHas('firma', function ($firmaQuery) use ($search) {
                        $firmaQuery->where('naziv_brenda', 'ilike', "%{$search}%");
                    });
            });
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radiusKm = (float) ($request->input('radius_km', 10));

        if ($lat !== null && $lng !== null) {
            $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';
            $query
                ->select('apoteke_poslovnice.*')
                ->selectRaw($distanceSql . ' as distance_km', [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereRaw($distanceSql . ' <= ?', [$lat, $lng, $lat, $radiusKm]);
        }

        $now = CarbonImmutable::now('Europe/Sarajevo');

        $items = $query->get()->map(function (ApotekaPoslovnica $branch) use ($now) {
            $status = $this->availabilityService->resolveStatus($branch, $now);
            $activeDiscounts = $this->activeDiscounts($branch, $now);
            $activeActions = $this->activeActions($branch, $now);
            $activeOffers = $this->activeSpecialOffers($branch, $now);

            return [
                'id' => $branch->id,
                'naziv' => $branch->naziv,
                'slug' => $branch->slug,
                'adresa' => $branch->adresa,
                'grad_naziv' => $branch->grad_naziv ?: ($branch->grad?->naziv ?? null),
                'telefon' => $branch->telefon,
                'email' => $branch->email,
                'profilna_slika_url' => $branch->profilna_slika_url,
                'kratki_opis' => $branch->kratki_opis,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'distance_km' => $branch->distance_km !== null ? round((float) $branch->distance_km, 2) : null,
                'ocjena' => $branch->ocjena !== null ? (float) $branch->ocjena : null,
                'broj_ocjena' => (int) $branch->broj_ocjena,
                'is_24h' => (bool) $branch->is_24h,
                'status' => $status,
                'open_now' => (bool) $status['open_now'],
                'is_dezurna' => (bool) $status['is_dezurna'],
                'status_label' => $status['status_label'],
                'next_change_at' => $status['next_change_at'],
                'active_discounts' => $activeDiscounts->map(function ($discount) {
                    return [
                        'id' => $discount->id,
                        'tip' => $discount->tip,
                        'discount_percent' => $discount->discount_percent !== null ? (float) $discount->discount_percent : null,
                        'discount_amount' => $discount->discount_amount !== null ? (float) $discount->discount_amount : null,
                    ];
                })->values()->all(),
                'active_actions_count' => $activeActions->count(),
                'active_offers_count' => $activeOffers->count(),
                'has_pensioner_discount' => $this->hasPensionerDiscount($activeDiscounts, $activeOffers),
            ];
        });

        if ($request->boolean('open_now')) {
            $items = $items->where('open_now', true)->values();
        }

        if ($request->boolean('dezurna_now')) {
            $items = $items->where('is_dezurna', true)->values();
        }

        if ($request->boolean('is_24h')) {
            $items = $items->where('is_24h', true)->values();
        }

        if ($request->boolean('pensioner_discount')) {
            $items = $items->where('has_pensioner_discount', true)->values();
        }

        if ($request->boolean('has_actions')) {
            $items = $items->where('active_actions_count', '>', 0)->values();
        }

        return $items->values();
    }

    private function activeDiscounts(ApotekaPoslovnica $branch, CarbonImmutable $now): Collection
    {
        $firmDiscounts = $branch->firma?->popusti ?? collect();
        $branchDiscounts = $branch->popusti ?? collect();

        return $firmDiscounts
            ->concat($branchDiscounts)
            ->filter(function ($discount) use ($now) {
                if (!$discount->is_active) {
                    return false;
                }

                if ($discount->discount_percent === null && $discount->discount_amount === null) {
                    return false;
                }

                return $this->availabilityService->isWindowActive(
                    $discount->starts_at,
                    $discount->ends_at,
                    $this->normalizeDays($discount->days_of_week),
                    null,
                    null,
                    $now
                );
            })
            ->values();
    }

    private function activeActions(ApotekaPoslovnica $branch, CarbonImmutable $now): Collection
    {
        $firmActions = $branch->firma?->akcije ?? collect();
        $branchActions = $branch->akcije ?? collect();

        return $firmActions
            ->concat($branchActions)
            ->filter(function ($action) use ($now) {
                if (!$action->is_active) {
                    return false;
                }

                return $this->availabilityService->isWindowActive(
                    $action->starts_at,
                    $action->ends_at,
                    null,
                    null,
                    null,
                    $now
                );
            })
            ->values();
    }

    private function activeSpecialOffers(ApotekaPoslovnica $branch, CarbonImmutable $now): Collection
    {
        $firmOffers = $branch->firma?->posebnePonude ?? collect();
        $branchOffers = $branch->posebnePonude ?? collect();

        return $firmOffers
            ->concat($branchOffers)
            ->filter(function ($offer) use ($now) {
                if (!$offer->is_active) {
                    return false;
                }

                return $this->availabilityService->isWindowActive(
                    $offer->starts_at,
                    $offer->ends_at,
                    $this->normalizeDays($offer->days_of_week),
                    $offer->time_from,
                    $offer->time_to,
                    $now
                );
            })
            ->sortByDesc('priority')
            ->values();
    }

    private function normalizeDays($days): ?array
    {
        if (!is_array($days) || empty($days)) {
            return null;
        }

        return collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 1 && $day <= 7)
            ->values()
            ->all();
    }

    private function hasPensionerDiscount(Collection $discounts, Collection $offers): bool
    {
        $discountFlag = $discounts->contains(function ($discount) {
            return in_array($discount->tip, ['penzioneri'], true);
        });

        if ($discountFlag) {
            return true;
        }

        return $offers->contains(function ($offer) {
            return in_array($offer->target_group, ['penzioneri'], true);
        });
    }

    private function applySort(Collection $items, string $sort, bool $hasDistance): Collection
    {
        return match ($sort) {
            'distance' => $items->sortBy(function ($item) use ($hasDistance) {
                return $hasDistance ? ($item['distance_km'] ?? PHP_FLOAT_MAX) : ($item['naziv'] ?? '');
            })->values(),
            'rating' => $items->sortByDesc(fn ($item) => $item['ocjena'] ?? -1)->values(),
            'name' => $items->sortBy(fn ($item) => mb_strtolower((string) ($item['naziv'] ?? '')))->values(),
            default => $items->sort(function ($a, $b) use ($hasDistance) {
                $scoreA = [
                    $a['is_dezurna'] ? 0 : 1,
                    $a['open_now'] ? 0 : 1,
                    $hasDistance ? ($a['distance_km'] ?? PHP_FLOAT_MAX) : PHP_FLOAT_MAX,
                    -1 * ((float) ($a['ocjena'] ?? 0)),
                    mb_strtolower((string) ($a['naziv'] ?? '')),
                ];
                $scoreB = [
                    $b['is_dezurna'] ? 0 : 1,
                    $b['open_now'] ? 0 : 1,
                    $hasDistance ? ($b['distance_km'] ?? PHP_FLOAT_MAX) : PHP_FLOAT_MAX,
                    -1 * ((float) ($b['ocjena'] ?? 0)),
                    mb_strtolower((string) ($b['naziv'] ?? '')),
                ];

                return $scoreA <=> $scoreB;
            })->values(),
        };
    }

    private function resolveCity(string $city): ?Grad
    {
        $normalized = mb_strtolower(trim($city));

        return Grad::query()
            ->where(function ($query) use ($city, $normalized) {
                $query->where('slug', $city)
                    ->orWhereRaw('LOWER(naziv) = ?', [$normalized]);
            })
            ->first();
    }

    private function buildSchemaReadyPayload(ApotekaPoslovnica $branch, array $status, Collection $activeOffers): array
    {
        $image = $branch->profilna_slika_url;
        if (!$image && is_array($branch->galerija_slike) && !empty($branch->galerija_slike)) {
            $image = $branch->galerija_slike[0];
        }

        return [
            '@type' => 'Pharmacy',
            'name' => $branch->naziv,
            'url' => config('app.frontend_url') . '/apoteka/' . $branch->slug,
            'telephone' => $branch->telefon,
            'email' => $branch->email,
            'image' => $image,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $branch->adresa,
                'addressLocality' => $branch->grad_naziv ?: ($branch->grad?->naziv ?? ''),
                'postalCode' => $branch->postanski_broj,
                'addressCountry' => 'BA',
            ],
            'geo' => ($branch->latitude && $branch->longitude) ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
            ] : null,
            'additionalProperty' => [
                [
                    '@type' => 'PropertyValue',
                    'name' => 'status_label',
                    'value' => $status['status_label'],
                ],
                [
                    '@type' => 'PropertyValue',
                    'name' => 'active_offers_count',
                    'value' => $activeOffers->count(),
                ],
            ],
        ];
    }
}
