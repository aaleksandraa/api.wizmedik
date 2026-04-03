<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\ApotekaRadnoVrijeme;
use App\Models\Grad;
use App\Services\AdminProfileAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPharmacyController extends Controller
{
    public function __construct(private AdminProfileAccessService $profileAccessService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 1000);
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $active = $request->input('active');

        $query = ApotekaFirma::query()
            ->with([
                'owner:id,name,ime,prezime,email,role',
                'poslovnice' => fn ($query) => $query->orderBy('id'),
            ])
            ->withCount('poslovnice')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $searchLike = '%' . mb_strtolower($search) . '%';
            $query->where(function (Builder $builder) use ($searchLike) {
                $builder
                    ->whereRaw('LOWER(naziv_brenda) LIKE ?', [$searchLike])
                    ->orWhereRaw('LOWER(COALESCE(pravni_naziv, \'\')) LIKE ?', [$searchLike])
                    ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$searchLike])
                    ->orWhereRaw('LOWER(COALESCE(telefon, \'\')) LIKE ?', [$searchLike])
                    ->orWhereHas('owner', function (Builder $ownerQuery) use ($searchLike) {
                        $ownerQuery
                            ->whereRaw('LOWER(email) LIKE ?', [$searchLike])
                            ->orWhereRaw('LOWER(COALESCE(name, \'\')) LIKE ?', [$searchLike])
                            ->orWhereRaw('LOWER(COALESCE(ime, \'\')) LIKE ?', [$searchLike])
                            ->orWhereRaw('LOWER(COALESCE(prezime, \'\')) LIKE ?', [$searchLike]);
                    })
                    ->orWhereHas('poslovnice', function (Builder $branchQuery) use ($searchLike) {
                        $branchQuery
                            ->whereRaw('LOWER(COALESCE(naziv, \'\')) LIKE ?', [$searchLike])
                            ->orWhereRaw('LOWER(COALESCE(grad_naziv, \'\')) LIKE ?', [$searchLike])
                            ->orWhereRaw('LOWER(COALESCE(adresa, \'\')) LIKE ?', [$searchLike]);
                    });
            });
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if ($active !== null && $active !== '') {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        $firms = $query->paginate($perPage);

        $firms->getCollection()->transform(function (ApotekaFirma $firm) {
            return $this->transformFirm($firm);
        });

        return response()->json($firms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'naziv_brenda' => 'required|string|max:255',
            'pravni_naziv' => 'nullable|string|max:255',
            'broj_licence' => 'nullable|string|max:64',
            'telefon' => 'required|string|max:64',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'nullable|string|max:5000',
            'status' => 'nullable|in:pending,verified,rejected,suspended',
            'is_active' => 'nullable|boolean',

            'branch_naziv' => 'nullable|string|max:255',
            'grad' => 'required|string|max:100',
            'adresa' => 'required|string|max:255',
            'postanski_broj' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|url|max:500',
            'is_24h' => 'nullable|boolean',

            'account_email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ]);

        $status = $validated['status'] ?? 'verified';
        $isActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;
        $isVerified = $status === 'verified';

        $firm = DB::transaction(function () use ($validated, $status, $isActive, $isVerified) {
            $firm = ApotekaFirma::create([
                'naziv_brenda' => $validated['naziv_brenda'],
                'pravni_naziv' => $validated['pravni_naziv'] ?? null,
                'broj_licence' => $validated['broj_licence'] ?? null,
                'telefon' => $validated['telefon'],
                'email' => isset($validated['email']) ? $this->normalizeEmail($validated['email']) : null,
                'website' => $validated['website'] ?? null,
                'opis' => $validated['opis'] ?? null,
                'status' => $status,
                'is_active' => $isActive,
                'verified_at' => $isVerified ? now() : null,
                'verified_by' => $isVerified ? auth()->id() : null,
            ]);

            $city = $this->resolveCity($validated['grad']);
            $branchName = trim((string) ($validated['branch_naziv'] ?? ''));
            if ($branchName === '') {
                $branchName = $validated['naziv_brenda'] . ' - Glavna poslovnica';
            }

            $branch = ApotekaPoslovnica::create([
                'firma_id' => $firm->id,
                'naziv' => $branchName,
                'slug' => ApotekaPoslovnica::generateUniqueSlug($branchName),
                'grad_id' => $city?->id,
                'grad_naziv' => $city?->naziv ?? $validated['grad'],
                'adresa' => $validated['adresa'],
                'postanski_broj' => $validated['postanski_broj'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'telefon' => $validated['telefon'],
                'email' => isset($validated['email']) ? $this->normalizeEmail($validated['email']) : null,
                'google_maps_link' => $validated['google_maps_link'] ?? null,
                'is_24h' => (bool) ($validated['is_24h'] ?? false),
                'is_active' => $isActive,
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
                'verified_by' => $isVerified ? auth()->id() : null,
            ]);

            $this->createDefaultWorkingHours($branch->id);

            $this->profileAccessService->sync($firm, $validated, [
                'relation_column' => 'owner_user_id',
                'role' => 'pharmacy_owner',
                'model_class' => ApotekaFirma::class,
                'entity_label' => 'apoteka',
                'invitation_label' => 'profil apoteke',
                'name' => fn (ApotekaFirma $entity) => $entity->naziv_brenda,
            ]);

            return $firm;
        });

        return response()->json([
            'message' => 'Apoteka je uspjesno kreirana.',
            'data' => $this->transformFirm(
                ApotekaFirma::with([
                    'owner:id,name,ime,prezime,email,role',
                    'poslovnice' => fn ($query) => $query->orderBy('id'),
                ])->findOrFail($firm->id)
            ),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $firm = ApotekaFirma::with([
            'owner:id,name,ime,prezime,email,role',
            'poslovnice' => fn ($query) => $query->orderBy('id'),
        ])->findOrFail($id);

        $validated = $request->validate([
            'naziv_brenda' => 'sometimes|required|string|max:255',
            'pravni_naziv' => 'nullable|string|max:255',
            'broj_licence' => 'nullable|string|max:64',
            'telefon' => 'sometimes|required|string|max:64',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'nullable|string|max:5000',
            'status' => 'sometimes|in:pending,verified,rejected,suspended',
            'is_active' => 'sometimes|boolean',

            'branch_naziv' => 'sometimes|nullable|string|max:255',
            'grad' => 'sometimes|required|string|max:100',
            'adresa' => 'sometimes|required|string|max:255',
            'postanski_broj' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|url|max:500',
            'is_24h' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',

            'account_email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ]);

        DB::transaction(function () use ($request, $validated, $firm) {
            $firmData = [];
            foreach (['naziv_brenda', 'pravni_naziv', 'broj_licence', 'telefon', 'website', 'opis'] as $field) {
                if ($request->has($field)) {
                    $firmData[$field] = $validated[$field] ?? null;
                }
            }
            if ($request->has('email')) {
                $firmData['email'] = isset($validated['email']) ? $this->normalizeEmail($validated['email']) : null;
            }
            if ($request->has('is_active')) {
                $firmData['is_active'] = (bool) $validated['is_active'];
            }
            if ($request->has('status')) {
                $firmData['status'] = $validated['status'];
                if ($validated['status'] === 'verified') {
                    $firmData['verified_at'] = $firm->verified_at ?? now();
                    $firmData['verified_by'] = auth()->id();
                }
            }
            if (!empty($firmData)) {
                $firm->update($firmData);
            }

            $this->profileAccessService->sync($firm, $validated, [
                'relation_column' => 'owner_user_id',
                'role' => 'pharmacy_owner',
                'model_class' => ApotekaFirma::class,
                'entity_label' => 'apoteka',
                'invitation_label' => 'profil apoteke',
                'name' => fn (ApotekaFirma $entity) => $entity->naziv_brenda,
            ]);

            $branch = $firm->poslovnice->first();
            if ($branch) {
                $branchData = [];
                if ($request->has('branch_naziv')) {
                    $branchName = trim((string) ($validated['branch_naziv'] ?? ''));
                    if ($branchName !== '') {
                        $branchData['naziv'] = $branchName;
                        $branchData['slug'] = ApotekaPoslovnica::generateUniqueSlug($branchName, $branch->id);
                    }
                }
                if ($request->has('grad')) {
                    $city = $this->resolveCity($validated['grad']);
                    $branchData['grad_id'] = $city?->id;
                    $branchData['grad_naziv'] = $city?->naziv ?? $validated['grad'];
                }
                foreach (['adresa', 'postanski_broj', 'latitude', 'longitude', 'google_maps_link'] as $field) {
                    if ($request->has($field)) {
                        $branchData[$field] = $validated[$field] ?? null;
                    }
                }
                if ($request->has('telefon')) {
                    $branchData['telefon'] = $validated['telefon'];
                }
                if ($request->has('email')) {
                    $branchData['email'] = isset($validated['email']) ? $this->normalizeEmail($validated['email']) : null;
                }
                if ($request->has('is_24h')) {
                    $branchData['is_24h'] = (bool) $validated['is_24h'];
                }
                if ($request->has('is_active')) {
                    $branchData['is_active'] = (bool) $validated['is_active'];
                }

                $verifiedFromStatus = null;
                if ($request->has('status')) {
                    $verifiedFromStatus = $validated['status'] === 'verified';
                }
                if ($request->has('is_verified')) {
                    $verifiedFromStatus = (bool) $validated['is_verified'];
                }
                if ($verifiedFromStatus !== null) {
                    $branchData['is_verified'] = $verifiedFromStatus;
                    $branchData['verified_at'] = $verifiedFromStatus ? ($branch->verified_at ?? now()) : null;
                    $branchData['verified_by'] = $verifiedFromStatus ? auth()->id() : null;
                }

                if (!empty($branchData)) {
                    $branch->update($branchData);
                }
            }
        });

        $fresh = ApotekaFirma::with([
            'owner:id,name,ime,prezime,email,role',
            'poslovnice' => fn ($query) => $query->orderBy('id'),
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Apoteka je uspjesno azurirana.',
            'data' => $this->transformFirm($fresh),
        ]);
    }

    public function sendAccessInvite(Request $request, int $id): JsonResponse
    {
        $firm = ApotekaFirma::with([
            'owner:id,name,ime,prezime,email,role',
            'poslovnice' => fn ($query) => $query->orderBy('id'),
        ])->findOrFail($id);

        $validated = $request->validate([
            'account_email' => 'nullable|email|max:255',
        ]);

        $result = $this->profileAccessService->sendInvitation($firm, $validated, [
            'relation_column' => 'owner_user_id',
            'role' => 'pharmacy_owner',
            'model_class' => ApotekaFirma::class,
            'entity_label' => 'apoteka',
            'invitation_label' => 'profil apoteke',
            'name' => fn (ApotekaFirma $entity) => $entity->naziv_brenda,
        ]);

        $fresh = ApotekaFirma::with([
            'owner:id,name,ime,prezime,email,role',
            'poslovnice' => fn ($query) => $query->orderBy('id'),
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Pozivnica za pristup je uspjesno poslana.',
            'data' => $this->transformFirm($fresh),
            'invitation' => [
                'sent_to' => $result['sent_to'],
                'sent_at' => $result['invitation_sent_at'],
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $firm = ApotekaFirma::with('poslovnice')->findOrFail($id);

        DB::transaction(function () use ($firm) {
            $firm->update(['is_active' => false]);
            $firm->poslovnice()->update(['is_active' => false]);
            $firm->poslovnice()->delete();
            $firm->delete();
        });

        return response()->json([
            'message' => 'Apoteka je uspjesno obrisana.',
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $firms = ApotekaFirma::query()
            ->with(['owner:id,ime,prezime,email', 'poslovnice'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($firms);
    }

    public function verify(int $id): JsonResponse
    {
        $firm = ApotekaFirma::findOrFail($id);
        $firm->update([
            'status' => 'verified',
            'is_active' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        ApotekaPoslovnica::query()
            ->where('firma_id', $firm->id)
            ->update([
                'is_active' => true,
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

        return response()->json([
            'message' => 'Apoteka firma je verifikovana.',
            'firma' => $firm->fresh('poslovnice'),
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $firm = ApotekaFirma::findOrFail($id);
        $firm->update([
            'status' => 'rejected',
            'is_active' => false,
            'verified_by' => auth()->id(),
        ]);

        ApotekaPoslovnica::query()
            ->where('firma_id', $firm->id)
            ->update([
                'is_active' => false,
                'is_verified' => false,
            ]);

        return response()->json([
            'message' => 'Apoteka firma je odbijena.',
            'reason' => $request->input('reason'),
        ]);
    }

    public function suspend(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $firm = ApotekaFirma::findOrFail($id);
        $firm->update([
            'status' => 'suspended',
            'is_active' => false,
            'verified_by' => auth()->id(),
        ]);

        ApotekaPoslovnica::query()
            ->where('firma_id', $firm->id)
            ->update([
                'is_active' => false,
            ]);

        return response()->json([
            'message' => 'Apoteka firma je suspendovana.',
            'reason' => $request->input('reason'),
        ]);
    }

    public function verifyBranch(int $id): JsonResponse
    {
        $branch = ApotekaPoslovnica::findOrFail($id);
        $branch->update([
            'is_active' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Poslovnica je verifikovana.',
            'branch' => $branch->fresh(),
        ]);
    }

    public function importDuty(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'CSV/Excel import dezurstava je planiran u narednoj fazi.',
        ], 501);
    }

    private function resolveCity(string $cityName): ?Grad
    {
        $cityName = trim($cityName);
        if ($cityName === '') {
            return null;
        }

        return Grad::query()
            ->whereRaw('LOWER(naziv) = ?', [mb_strtolower($cityName)])
            ->orWhere('slug', \Illuminate\Support\Str::slug($cityName))
            ->first();
    }

    private function createDefaultWorkingHours(int $branchId): void
    {
        foreach (range(1, 7) as $day) {
            ApotekaRadnoVrijeme::create([
                'poslovnica_id' => $branchId,
                'day_of_week' => $day,
                'open_time' => in_array($day, [1, 2, 3, 4, 5, 6], true) ? '08:00' : null,
                'close_time' => in_array($day, [1, 2, 3, 4, 5], true) ? '20:00' : (in_array($day, [6], true) ? '14:00' : null),
                'closed' => $day === 7,
            ]);
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($email));
        return $normalized === '' ? null : $normalized;
    }

    private function transformFirm(ApotekaFirma $firm): array
    {
        /** @var EloquentCollection<int, ApotekaPoslovnica> $branches */
        $branches = $firm->relationLoaded('poslovnice') ? $firm->poslovnice : collect();
        $mainBranch = $branches->first();

        return [
            'id' => $firm->id,
            'owner_user_id' => $firm->owner_user_id,
            'naziv_brenda' => $firm->naziv_brenda,
            'pravni_naziv' => $firm->pravni_naziv,
            'broj_licence' => $firm->broj_licence,
            'telefon' => $firm->telefon,
            'email' => $firm->email,
            'website' => $firm->website,
            'opis' => $firm->opis,
            'status' => $firm->status,
            'is_active' => (bool) $firm->is_active,
            'verified_at' => $firm->verified_at,
            'verified_by' => $firm->verified_by,
            'created_at' => $firm->created_at,
            'updated_at' => $firm->updated_at,
            'poslovnice_count' => $firm->poslovnice_count ?? $branches->count(),
            'owner' => $firm->owner,
            'glavna_poslovnica' => $mainBranch,
            'poslovnice' => $branches->values(),
        ];
    }
}
