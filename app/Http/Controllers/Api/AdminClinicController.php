<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klinika;
use App\Services\AdminProfileAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminClinicController extends Controller
{
    public function __construct(private AdminProfileAccessService $profileAccessService)
    {
    }

    /**
     * Get all clinics (including inactive and unverified) for admin panel
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 1000);

        $query = Klinika::query()
            ->with([
                'user:id,name,ime,prezime,email,role',
                'doktori' => function($q) {
                    $q->select('id', 'ime', 'prezime', 'slug', 'specijalnost', 'ocjena',
                              'slika_profila', 'klinika_id', 'aktivan', 'verifikovan');
                },
                'verifikovaoAdmin:id,ime,prezime,email'
            ]);

        // Filters
        if ($request->has('grad')) {
            $query->byCity($request->grad);
        }

        if ($request->has('search')) {
            $query->where('naziv', 'ilike', '%'.$request->search.'%');
        }

        // Admin-specific filters
        if ($request->has('aktivan')) {
            $query->where('aktivan', $request->boolean('aktivan'));
        }

        if ($request->has('verifikovan')) {
            $query->where('verifikovan', $request->boolean('verifikovan'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $clinics = $query->paginate($perPage);

        return response()->json($clinics);
    }

    /**
     * Update a clinic from the admin panel without coupling public and access email.
     */
    public function updateManaged(Request $request, int $id): JsonResponse
    {
        $klinika = Klinika::findOrFail($id);

        $validated = $request->validate([
            'naziv' => 'sometimes|string',
            'opis' => 'nullable|string',
            'adresa' => 'sometimes|string',
            'grad' => 'sometimes|string',
            'telefon' => 'sometimes|string',
            'email' => 'nullable|email',
            'account_email' => 'nullable|email',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_maps_link' => 'nullable|url',
            'slike' => 'nullable|array',
            'radno_vrijeme' => 'nullable|array',
            'aktivan' => 'sometimes|boolean',
            'verifikovan' => 'sometimes|boolean',
        ]);

        $klinika = DB::transaction(function () use ($klinika, $validated, $request) {
            $klinika->update(collect($validated)->only([
                'naziv', 'opis', 'adresa', 'grad', 'telefon', 'email', 'contact_email',
                'website', 'latitude', 'longitude', 'google_maps_link', 'slike',
                'radno_vrijeme', 'aktivan', 'verifikovan',
            ])->all());

            $this->profileAccessService->sync($klinika, $validated, [
                'role' => 'clinic',
                'model_class' => Klinika::class,
                'entity_label' => 'klinika',
                'name' => fn (Klinika $clinic) => $clinic->naziv,
            ]);

            if ($request->boolean('verifikovan')) {
                $klinika->forceFill([
                    'verifikovan_at' => $klinika->verifikovan_at ?? now(),
                    'verifikovan_by' => $klinika->verifikovan_by ?? $request->user()->id,
                ])->save();
            } elseif ($request->has('verifikovan') && !$request->boolean('verifikovan')) {
                $klinika->forceFill([
                    'verifikovan_at' => null,
                    'verifikovan_by' => null,
                ])->save();
            }

            return $klinika->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'doktori:id,ime,prezime,slug,specijalnost,ocjena,slika_profila,klinika_id,aktivan,verifikovan',
                'verifikovaoAdmin:id,ime,prezime,email',
            ]);
        });

        return response()->json([
            'message' => 'Clinic updated',
            'klinika' => $klinika,
        ]);
    }

    /**
     * Verify a clinic
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $klinika = Klinika::findOrFail($id);

        $klinika->update([
            'verifikovan' => true,
            'verifikovan_at' => now(),
            'verifikovan_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Klinika uspješno verifikovana',
            'klinika' => $klinika->load('verifikovaoAdmin')
        ]);
    }

    /**
     * Revoke verification
     */
    public function unverify(Request $request, int $id): JsonResponse
    {
        $klinika = Klinika::findOrFail($id);

        $klinika->update([
            'verifikovan' => false,
            'verifikovan_at' => null,
            'verifikovan_by' => null,
        ]);

        return response()->json([
            'message' => 'Verifikacija klinike uklonjena',
            'klinika' => $klinika
        ]);
    }

    /**
     * Activate a clinic
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $klinika = Klinika::findOrFail($id);

        $klinika->update(['aktivan' => true]);

        return response()->json([
            'message' => 'Klinika aktivirana',
            'klinika' => $klinika
        ]);
    }

    /**
     * Deactivate a clinic
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $klinika = Klinika::findOrFail($id);

        $klinika->update(['aktivan' => false]);

        return response()->json([
            'message' => 'Klinika deaktivirana',
            'klinika' => $klinika
        ]);
    }

    /**
     * Get clinic statistics for admin
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Klinika::count(),
            'active' => Klinika::where('aktivan', true)->count(),
            'inactive' => Klinika::where('aktivan', false)->count(),
            'verified' => Klinika::where('verifikovan', true)->count(),
            'unverified' => Klinika::where('verifikovan', false)->count(),
            'pending' => Klinika::where('aktivan', true)->where('verifikovan', false)->count(),
        ];

        return response()->json($stats);
    }
}
