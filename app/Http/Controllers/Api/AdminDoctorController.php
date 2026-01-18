<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doktor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminDoctorController extends Controller
{
    /**
     * Get all doctors (including inactive and unverified) for admin panel
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 1000);

        $query = Doktor::query()
            ->select([
                'id', 'ime', 'prezime', 'slug', 'specijalnost', 'grad',
                'ocjena', 'broj_ocjena', 'slika_profila', 'klinika_id',
                'prihvata_online', 'aktivan', 'verifikovan', 'verifikovan_at', 'verifikovan_by'
            ])
            ->with([
                'specijalnostModel:id,naziv,slug',
                'klinika:id,naziv,grad,slug',
                'specijalnosti:id,naziv,slug',
                'verifikovaoAdmin:id,ime,prezime,email'
            ]);

        // Filters
        if ($request->has('grad')) {
            $query->byCity($request->grad);
        }

        if ($request->has('specijalnost')) {
            $query->bySpecialty($request->specijalnost);
        }

        if ($request->has('klinika_id')) {
            $query->where('klinika_id', $request->klinika_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
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
        $doctors = $query->paginate($perPage);

        return response()->json($doctors);
    }

    /**
     * Verify a doctor
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $doktor = Doktor::findOrFail($id);

        $doktor->update([
            'verifikovan' => true,
            'verifikovan_at' => now(),
            'verifikovan_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Doktor uspješno verifikovan',
            'doktor' => $doktor->load('verifikovaoAdmin')
        ]);
    }

    /**
     * Revoke verification
     */
    public function unverify(Request $request, int $id): JsonResponse
    {
        $doktor = Doktor::findOrFail($id);

        $doktor->update([
            'verifikovan' => false,
            'verifikovan_at' => null,
            'verifikovan_by' => null,
        ]);

        return response()->json([
            'message' => 'Verifikacija doktora uklonjena',
            'doktor' => $doktor
        ]);
    }

    /**
     * Activate a doctor
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $doktor = Doktor::findOrFail($id);

        $doktor->update(['aktivan' => true]);

        return response()->json([
            'message' => 'Doktor aktiviran',
            'doktor' => $doktor
        ]);
    }

    /**
     * Deactivate a doctor
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $doktor = Doktor::findOrFail($id);

        $doktor->update(['aktivan' => false]);

        return response()->json([
            'message' => 'Doktor deaktiviran',
            'doktor' => $doktor
        ]);
    }

    /**
     * Get doctor statistics for admin
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Doktor::count(),
            'active' => Doktor::where('aktivan', true)->count(),
            'inactive' => Doktor::where('aktivan', false)->count(),
            'verified' => Doktor::where('verifikovan', true)->count(),
            'unverified' => Doktor::where('verifikovan', false)->count(),
            'pending' => Doktor::where('aktivan', true)->where('verifikovan', false)->count(),
        ];

        return response()->json($stats);
    }
}
