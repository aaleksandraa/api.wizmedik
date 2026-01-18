<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klinika;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminClinicController extends Controller
{
    /**
     * Get all clinics (including inactive and unverified) for admin panel
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 1000);

        $query = Klinika::query()
            ->select([
                'id', 'naziv', 'slug', 'grad', 'adresa', 'telefon', 'email',
                'ocjena', 'broj_ocjena', 'slike', 'latitude', 'longitude',
                'aktivan', 'verifikovan', 'verifikovan_at', 'verifikovan_by'
            ])
            ->with([
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
