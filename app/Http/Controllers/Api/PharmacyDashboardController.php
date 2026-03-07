<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApotekaAkcija;
use App\Models\ApotekaDezurstvo;
use App\Models\ApotekaFirma;
use App\Models\ApotekaPopust;
use App\Models\ApotekaPosebnaPonuda;
use App\Models\ApotekaPoslovnica;
use App\Models\ApotekaRadnoVrijeme;
use App\Models\ApotekaRadnoVrijemeIzuzetak;
use App\Services\ApotekaAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyDashboardController extends Controller
{
    public function __construct(
        private readonly ApotekaAvailabilityService $availabilityService
    ) {
    }

    public function profile(): JsonResponse
    {
        $firm = $this->ownedFirm();
        $firm->load([
            'poslovnice.radnoVrijeme',
            'poslovnice.radnoVrijemeIzuzeci',
            'poslovnice.dezurstva',
            'poslovnice.popusti',
            'poslovnice.akcije',
            'poslovnice.posebnePonude',
            'popusti',
            'akcije',
            'posebnePonude',
        ]);

        $now = CarbonImmutable::now('Europe/Sarajevo');
        $branches = $firm->poslovnice->map(function (ApotekaPoslovnica $branch) use ($now) {
            $status = $this->availabilityService->resolveStatus($branch, $now);
            $data = $branch->toArray();
            $data['status'] = $status;
            return $data;
        })->values();

        return response()->json([
            'firma' => $firm,
            'branches' => $branches,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $data = $request->validate([
            'naziv_brenda' => 'sometimes|required|string|max:255',
            'pravni_naziv' => 'nullable|string|max:255',
            'jib' => 'nullable|string|max:32',
            'broj_licence' => 'nullable|string|max:64',
            'telefon' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'nullable|string|max:5000',
            'logo_url' => 'nullable|url|max:500',
        ]);

        $firm->update($data);

        return response()->json([
            'message' => 'Profil apoteke je azuriran.',
            'firma' => $firm->fresh(),
        ]);
    }

    public function branches(): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branches = $firm->poslovnice()->orderBy('created_at')->get();

        return response()->json($branches);
    }

    public function storeBranch(Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $data = $this->validateBranchPayload($request);

        $branch = $firm->poslovnice()->create($data);

        return response()->json([
            'message' => 'Poslovnica je kreirana.',
            'branch' => $branch,
        ], 201);
    }

    public function updateBranch(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);
        $data = $this->validateBranchPayload($request, true);

        if (isset($data['naziv']) && $data['naziv'] !== $branch->naziv) {
            $data['slug'] = ApotekaPoslovnica::generateUniqueSlug($data['naziv'], $branch->id);
        }

        $branch->update($data);

        return response()->json([
            'message' => 'Poslovnica je azurirana.',
            'branch' => $branch->fresh(),
        ]);
    }

    public function deleteBranch(int $id): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);
        $branch->delete();

        return response()->json([
            'message' => 'Poslovnica je obrisana.',
        ]);
    }

    public function uploadProfileImage(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('apoteke/profilne', 'public');
            $branch->profilna_slika_url = '/storage/' . $path;
        } else {
            return response()->json([
                'message' => 'Posaljite profilnu sliku.',
            ], 422);
        }

        $branch->save();

        return response()->json([
            'message' => 'Profilna slika je azurirana.',
            'profilna_slika_url' => $branch->profilna_slika_url,
        ]);
    }

    public function uploadGalleryImages(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $existing = collect($branch->galerija_slike ?? []);
        $newImages = collect();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('apoteke/galerija', 'public');
                $newImages->push('/storage/' . $path);
            }
        }

        $merged = $existing->concat($newImages)->values();
        if ($merged->count() > 5) {
            return response()->json([
                'message' => 'Galerija moze imati maksimalno 5 slika.',
            ], 422);
        }

        $branch->update([
            'galerija_slike' => $merged->all(),
        ]);

        return response()->json([
            'message' => 'Galerija je azurirana.',
            'galerija_slike' => $merged->all(),
        ]);
    }

    public function deleteGalleryImage(int $id, string $imageId): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);
        $gallery = collect($branch->galerija_slike ?? []);

        if (is_numeric($imageId)) {
            $index = (int) $imageId;
            $gallery = $gallery->reject(fn ($_, $key) => (int) $key === $index)->values();
        } else {
            $decoded = urldecode($imageId);
            $gallery = $gallery->reject(fn ($url) => (string) $url === $decoded)->values();
        }

        $branch->update(['galerija_slike' => $gallery->all()]);

        return response()->json([
            'message' => 'Slika je uklonjena iz galerije.',
            'galerija_slike' => $gallery->all(),
        ]);
    }

    public function updateHours(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $data = $request->validate([
            'hours' => 'required|array|min:1|max:7',
            'hours.*.day_of_week' => 'required|integer|between:1,7',
            'hours.*.open_time' => 'nullable|date_format:H:i',
            'hours.*.close_time' => 'nullable|date_format:H:i',
            'hours.*.closed' => 'required|boolean',
        ]);

        foreach ($data['hours'] as $entry) {
            ApotekaRadnoVrijeme::updateOrCreate(
                [
                    'poslovnica_id' => $branch->id,
                    'day_of_week' => $entry['day_of_week'],
                ],
                [
                    'open_time' => $entry['closed'] ? null : ($entry['open_time'] ?? null),
                    'close_time' => $entry['closed'] ? null : ($entry['close_time'] ?? null),
                    'closed' => (bool) $entry['closed'],
                ]
            );
        }

        return response()->json([
            'message' => 'Radno vrijeme je azurirano.',
            'hours' => $branch->radnoVrijeme()->orderBy('day_of_week')->get(),
        ]);
    }

    public function storeHourException(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $data = $request->validate([
            'date' => 'required|date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'closed' => 'required|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $exception = ApotekaRadnoVrijemeIzuzetak::updateOrCreate(
            [
                'poslovnica_id' => $branch->id,
                'date' => $data['date'],
            ],
            [
                'open_time' => $data['closed'] ? null : ($data['open_time'] ?? null),
                'close_time' => $data['closed'] ? null : ($data['close_time'] ?? null),
                'closed' => (bool) $data['closed'],
                'reason' => $data['reason'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Izuzetak radnog vremena je sacuvan.',
            'exception' => $exception,
        ]);
    }

    public function indexDutyShifts(int $id): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $shifts = $branch->dezurstva()->orderByDesc('starts_at')->get();

        return response()->json($shifts);
    }

    public function storeDutyShift(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $id);

        $data = $request->validate([
            'grad_id' => 'required|exists:gradovi,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'tip' => 'nullable|in:night,holiday,weekend,continuous',
            'is_nonstop' => 'nullable|boolean',
            'status' => 'nullable|in:draft,confirmed,cancelled',
            'note' => 'nullable|string|max:2000',
        ]);

        $shift = $branch->dezurstva()->create([
            'grad_id' => $data['grad_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'tip' => $data['tip'] ?? 'night',
            'is_nonstop' => (bool) ($data['is_nonstop'] ?? false),
            'source' => 'manual',
            'status' => $data['status'] ?? 'draft',
            'note' => $data['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dezurstvo je kreirano.',
            'shift' => $shift,
        ], 201);
    }

    public function updateDutyShift(int $branchId, int $dutyId, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $branchId);

        $shift = $branch->dezurstva()->findOrFail($dutyId);
        $data = $request->validate([
            'grad_id' => 'sometimes|exists:gradovi,id',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'tip' => 'sometimes|in:night,holiday,weekend,continuous',
            'is_nonstop' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,confirmed,cancelled',
            'note' => 'nullable|string|max:2000',
        ]);

        $shift->update($data);

        return response()->json([
            'message' => 'Dezurstvo je azurirano.',
            'shift' => $shift->fresh(),
        ]);
    }

    public function deleteDutyShift(int $branchId, int $dutyId): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branch = $this->ownedBranch($firm, $branchId);
        $shift = $branch->dezurstva()->findOrFail($dutyId);
        $shift->delete();

        return response()->json([
            'message' => 'Dezurstvo je obrisano.',
        ]);
    }

    public function indexDiscounts(): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branchIds = $firm->poslovnice()->pluck('id');

        $discounts = ApotekaPopust::query()
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json($discounts);
    }

    public function storeDiscount(Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $data = $this->validateDiscountPayload($request, $firm);

        $discount = ApotekaPopust::create($data);

        return response()->json([
            'message' => 'Popust je sacuvan.',
            'discount' => $discount,
        ], 201);
    }

    public function updateDiscount(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $discount = $this->ownedDiscount($firm, $id);
        $data = $this->validateDiscountPayload($request, $firm, true);
        $discount->update($data);

        return response()->json([
            'message' => 'Popust je azuriran.',
            'discount' => $discount->fresh(),
        ]);
    }

    public function deleteDiscount(int $id): JsonResponse
    {
        $firm = $this->ownedFirm();
        $discount = $this->ownedDiscount($firm, $id);
        $discount->delete();

        return response()->json([
            'message' => 'Popust je obrisan.',
        ]);
    }

    public function indexPromotions(): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branchIds = $firm->poslovnice()->pluck('id');

        $promotions = ApotekaAkcija::query()
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->json($promotions);
    }

    public function storePromotion(Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $data = $this->validatePromotionPayload($request, $firm);
        $promotion = ApotekaAkcija::create($data);

        return response()->json([
            'message' => 'Akcija je sacuvana.',
            'promotion' => $promotion,
        ], 201);
    }

    public function updatePromotion(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $promotion = $this->ownedPromotion($firm, $id);
        $data = $this->validatePromotionPayload($request, $firm, true);
        $promotion->update($data);

        return response()->json([
            'message' => 'Akcija je azurirana.',
            'promotion' => $promotion->fresh(),
        ]);
    }

    public function deletePromotion(int $id): JsonResponse
    {
        $firm = $this->ownedFirm();
        $promotion = $this->ownedPromotion($firm, $id);
        $promotion->delete();

        return response()->json([
            'message' => 'Akcija je obrisana.',
        ]);
    }

    public function indexSpecialOffers(): JsonResponse
    {
        $firm = $this->ownedFirm();
        $branchIds = $firm->poslovnice()->pluck('id');

        $offers = ApotekaPosebnaPonuda::query()
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($offers);
    }

    public function storeSpecialOffer(Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $data = $this->validateSpecialOfferPayload($request, $firm);
        $offer = ApotekaPosebnaPonuda::create($data);

        return response()->json([
            'message' => 'Posebna ponuda je sacuvana.',
            'offer' => $offer,
        ], 201);
    }

    public function updateSpecialOffer(int $id, Request $request): JsonResponse
    {
        $firm = $this->ownedFirm();
        $offer = $this->ownedOffer($firm, $id);
        $data = $this->validateSpecialOfferPayload($request, $firm, true);
        $offer->update($data);

        return response()->json([
            'message' => 'Posebna ponuda je azurirana.',
            'offer' => $offer->fresh(),
        ]);
    }

    public function deleteSpecialOffer(int $id): JsonResponse
    {
        $firm = $this->ownedFirm();
        $offer = $this->ownedOffer($firm, $id);
        $offer->delete();

        return response()->json([
            'message' => 'Posebna ponuda je obrisana.',
        ]);
    }

    private function ownedFirm(): ApotekaFirma
    {
        return ApotekaFirma::query()
            ->where('owner_user_id', auth()->id())
            ->firstOrFail();
    }

    private function ownedBranch(ApotekaFirma $firm, int $branchId): ApotekaPoslovnica
    {
        return $firm->poslovnice()->findOrFail($branchId);
    }

    private function validateBranchPayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'naziv' => ($partial ? 'sometimes|' : '') . 'required|string|max:255',
            'grad_id' => 'nullable|exists:gradovi,id',
            'grad_naziv' => 'nullable|string|max:255',
            'adresa' => ($partial ? 'sometimes|' : '') . 'required|string|max:255',
            'postanski_broj' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'telefon' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'kratki_opis' => 'nullable|string|max:2000',
            'profilna_slika_url' => 'nullable|url|max:500',
            'galerija_slike' => 'nullable|array|max:5',
            'galerija_slike.*' => 'string|max:500',
            'google_maps_link' => 'nullable|url|max:500',
            'ima_dostavu' => 'nullable|boolean',
            'ima_parking' => 'nullable|boolean',
            'pristup_invalidima' => 'nullable|boolean',
            'is_24h' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];

        return $request->validate($rules);
    }

    private function validateDiscountPayload(Request $request, ApotekaFirma $firm, bool $partial = false): array
    {
        $rules = [
            'poslovnica_id' => 'nullable|integer',
            'tip' => ($partial ? 'sometimes|' : '') . 'required|in:penzioneri,studenti,porodicni,svi',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:1,7',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'uslovi' => 'nullable|string|max:4000',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        $this->ensureValidBranchScope($firm, $data['poslovnica_id'] ?? null);

        $hasPercentKey = array_key_exists('discount_percent', $data);
        $hasAmountKey = array_key_exists('discount_amount', $data);
        $hasPercentValue = $hasPercentKey && $data['discount_percent'] !== null;
        $hasAmountValue = $hasAmountKey && $data['discount_amount'] !== null;

        if (!$partial || $hasPercentKey || $hasAmountKey) {
            if (($hasPercentValue && $hasAmountValue) || (!$hasPercentValue && !$hasAmountValue)) {
                abort(422, 'Potrebno je unijeti ili procenat ili fiksni iznos popusta (samo jedno polje).');
            }
        }

        if ($hasPercentValue) {
            $data['discount_amount'] = null;
        }

        if ($hasAmountValue) {
            $data['discount_percent'] = null;
        }

        if (!$partial || array_key_exists('poslovnica_id', $data)) {
            $data['firma_id'] = $firm->id;
        }

        return $data;
    }

    private function validatePromotionPayload(Request $request, ApotekaFirma $firm, bool $partial = false): array
    {
        $rules = [
            'poslovnica_id' => 'nullable|integer',
            'naslov' => ($partial ? 'sometimes|' : '') . 'required|string|max:255',
            'opis' => 'nullable|string|max:5000',
            'image_url' => 'nullable|url|max:500',
            'promo_code' => 'nullable|string|max:64',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        $this->ensureValidBranchScope($firm, $data['poslovnica_id'] ?? null);

        if (!$partial || array_key_exists('poslovnica_id', $data)) {
            $data['firma_id'] = $firm->id;
        }

        return $data;
    }

    private function validateSpecialOfferPayload(Request $request, ApotekaFirma $firm, bool $partial = false): array
    {
        $rules = [
            'poslovnica_id' => 'nullable|integer',
            'offer_type' => ($partial ? 'sometimes|' : '') . 'required|in:percent_discount,fixed_discount,full_assortment_discount,category_discount,product_discount,free_service,free_item,bundle_offer',
            'title' => ($partial ? 'sometimes|' : '') . 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'target_group' => 'nullable|in:svi,penzioneri,studenti,djeca,hronicni_bolesnici',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'service_name' => 'nullable|string|max:255',
            'product_scope' => 'nullable|array',
            'conditions_json' => 'nullable|array',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:1,7',
            'time_from' => 'nullable|date_format:H:i',
            'time_to' => 'nullable|date_format:H:i',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:100',
        ];

        $data = $request->validate($rules);
        $this->ensureValidBranchScope($firm, $data['poslovnica_id'] ?? null);

        if (!$partial || array_key_exists('poslovnica_id', $data)) {
            $data['firma_id'] = $firm->id;
        }

        return $data;
    }

    private function ensureValidBranchScope(ApotekaFirma $firm, ?int $branchId): void
    {
        if (!$branchId) {
            return;
        }

        $owned = $firm->poslovnice()->where('id', $branchId)->exists();
        if (!$owned) {
            abort(422, 'Odabrana poslovnica ne pripada vasoj firmi.');
        }
    }

    private function ownedDiscount(ApotekaFirma $firm, int $id): ApotekaPopust
    {
        $branchIds = $firm->poslovnice()->pluck('id');
        return ApotekaPopust::query()
            ->where('id', $id)
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->firstOrFail();
    }

    private function ownedPromotion(ApotekaFirma $firm, int $id): ApotekaAkcija
    {
        $branchIds = $firm->poslovnice()->pluck('id');
        return ApotekaAkcija::query()
            ->where('id', $id)
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->firstOrFail();
    }

    private function ownedOffer(ApotekaFirma $firm, int $id): ApotekaPosebnaPonuda
    {
        $branchIds = $firm->poslovnice()->pluck('id');
        return ApotekaPosebnaPonuda::query()
            ->where('id', $id)
            ->where(function ($query) use ($firm, $branchIds) {
                $query->where('firma_id', $firm->id)
                    ->orWhereIn('poslovnica_id', $branchIds);
            })
            ->firstOrFail();
    }
}
