<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPharmacyController extends Controller
{
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
}

