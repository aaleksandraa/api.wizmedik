<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doktor;
use App\Models\DoktorKategorijaUsluga;
use App\Models\Usluga;
use App\Models\KlinikaDoktorZahtjev;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DoctorDashboardController extends Controller
{
    /**
     * Get doctor's profile
     */
    public function getProfile(): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::with(['specijalnosti', 'klinika', 'kategorijeUsluga.usluge'])
            ->where('user_id', $user->id)
            ->first();

        if (!$doktor) {
            return response()->json([
                'message' => 'Nemate registrovan doktorski profil.',
            ], 404);
        }

        return response()->json($doktor);
    }

    /**
     * Get service categories
     */
    public function getKategorije(): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $kategorije = $doktor->kategorijeUsluga()
            ->with('usluge')
            ->ordered()
            ->get();

        return response()->json($kategorije);
    }

    /**
     * Create service category
     */
    public function createKategorija(Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'required|string|max:255',
            'opis' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Get max redoslijed
            $maxRedoslijed = DoktorKategorijaUsluga::where('doktor_id', $doktor->id)
                ->max('redoslijed') ?? 0;

            $kategorija = DoktorKategorijaUsluga::create([
                'doktor_id' => $doktor->id,
                'naziv' => $request->naziv,
                'opis' => $request->opis,
                'redoslijed' => $maxRedoslijed + 1,
                'aktivan' => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Kategorija uspješno kreirana',
                'kategorija' => $kategorija,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Greška pri kreiranju kategorije',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update service category
     */
    public function updateKategorija(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'sometimes|required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'aktivan' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $kategorija = DoktorKategorijaUsluga::where('doktor_id', $doktor->id)
            ->findOrFail($id);

        try {
            $kategorija->update($request->only(['naziv', 'opis', 'aktivan']));

            return response()->json([
                'message' => 'Kategorija uspješno ažurirana',
                'kategorija' => $kategorija->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Greška pri ažuriranju kategorije',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete service category
     */
    public function deleteKategorija(int $id): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $kategorija = DoktorKategorijaUsluga::where('doktor_id', $doktor->id)
            ->findOrFail($id);

        try {
            // Set kategorija_id to null for all services in this category
            Usluga::where('kategorija_id', $id)->update(['kategorija_id' => null]);

            $kategorija->delete();

            return response()->json([
                'message' => 'Kategorija uspješno obrisana',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Greška pri brisanju kategorije',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder categories
     */
    public function reorderKategorije(Request $request): JsonResponse
    {
        $request->validate([
            'kategorije' => 'required|array',
            'kategorije.*.id' => 'required|integer',
            'kategorije.*.redoslijed' => 'required|integer',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            foreach ($request->kategorije as $item) {
                DoktorKategorijaUsluga::where('doktor_id', $doktor->id)
                    ->where('id', $item['id'])
                    ->update(['redoslijed' => $item['redoslijed']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Redoslijed kategorija uspješno ažuriran',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Greška pri ažuriranju redoslijeda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder services
     */
    public function reorderUsluge(Request $request): JsonResponse
    {
        $request->validate([
            'usluge' => 'required|array',
            'usluge.*.id' => 'required|integer',
            'usluge.*.redoslijed' => 'required|integer',
            'usluge.*.kategorija_id' => 'nullable|integer',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            foreach ($request->usluge as $item) {
                Usluga::where('doktor_id', $doktor->id)
                    ->where('id', $item['id'])
                    ->update([
                        'redoslijed' => $item['redoslijed'],
                        'kategorija_id' => $item['kategorija_id'] ?? null,
                    ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Redoslijed usluga uspješno ažuriran',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Greška pri ažuriranju redoslijeda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get services
     */
    public function getUsluge(): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $usluge = $doktor->usluge()->with('kategorija')->get();

        return response()->json($usluge);
    }

    /**
     * Create service
     */
    public function createUsluga(Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'required|numeric|min:0',
            'cijena_popust' => 'nullable|numeric|min:0',
            'trajanje_minuti' => 'required|integer|min:1',
            'kategorija_id' => 'nullable|integer|exists:doktor_kategorije_usluga,id',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Get max redoslijed
            $maxRedoslijed = Usluga::where('doktor_id', $doktor->id)
                ->max('redoslijed') ?? 0;

            $usluga = Usluga::create([
                'doktor_id' => $doktor->id,
                'kategorija_id' => $request->kategorija_id,
                'naziv' => $request->naziv,
                'opis' => $request->opis,
                'cijena' => $request->cijena,
                'cijena_popust' => $request->cijena_popust,
                'trajanje_minuti' => $request->trajanje_minuti,
                'redoslijed' => $maxRedoslijed + 1,
                'aktivan' => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Usluga uspješno kreirana',
                'usluga' => $usluga->load('kategorija'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Greška pri kreiranju usluge',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update service
     */
    public function updateUsluga(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'sometimes|required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'sometimes|required|numeric|min:0',
            'cijena_popust' => 'nullable|numeric|min:0',
            'trajanje_minuti' => 'sometimes|required|integer|min:1',
            'kategorija_id' => 'nullable|integer|exists:doktor_kategorije_usluga,id',
            'aktivan' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $usluga = Usluga::where('doktor_id', $doktor->id)
            ->findOrFail($id);

        try {
            $usluga->update($request->only([
                'naziv', 'opis', 'cijena', 'cijena_popust',
                'trajanje_minuti', 'kategorija_id', 'aktivan'
            ]));

            return response()->json([
                'message' => 'Usluga uspješno ažurirana',
                'usluga' => $usluga->fresh(['kategorija']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Greška pri ažuriranju usluge',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete service
     */
    public function deleteUsluga(int $id): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $usluga = Usluga::where('doktor_id', $doktor->id)
            ->findOrFail($id);

        try {
            $usluga->delete();

            return response()->json([
                'message' => 'Usluga uspješno obrisana',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Greška pri brisanju usluge',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search clinics for doctor to join
     */
    public function searchClinics(Request $request): JsonResponse
    {
        $search = $request->input('search', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $clinics = \App\Models\Klinika::where('aktivan', true)
            ->where(function ($query) use ($search) {
                $query->where('naziv', 'ILIKE', "%{$search}%")
                    ->orWhere('grad', 'ILIKE', "%{$search}%")
                    ->orWhere('adresa', 'ILIKE', "%{$search}%");
            })
            ->select('id', 'naziv', 'grad', 'adresa', 'slike')
            ->limit(10)
            ->get()
            ->map(function ($clinic) {
                // Add slika_profila for backward compatibility (first image from slike array)
                $clinic->slika_profila = !empty($clinic->slike) && is_array($clinic->slike) ? $clinic->slike[0] : null;
                return $clinic;
            });

        return response()->json($clinics);
    }

    /**
     * Get clinic invitations and doctor requests
     */
    public function getClinicInvitations(): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $requests = KlinikaDoktorZahtjev::with(['klinika', 'doktor'])
            ->where('doktor_id', $doktor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Request to join a clinic
     */
    public function requestToJoinClinic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'klinika_id' => 'required|exists:klinike,id',
            'poruka' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        // Check if doctor already has a clinic
        if ($doktor->klinika_id) {
            return response()->json([
                'message' => 'Već ste član klinike. Morate prvo napustiti trenutnu kliniku.',
            ], 400);
        }

        // Check if request already exists
        $existing = KlinikaDoktorZahtjev::where('doktor_id', $doktor->id)
            ->where('klinika_id', $validated['klinika_id'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Već ste poslali zahtjev ovoj klinici.',
            ], 400);
        }

        $zahtjev = KlinikaDoktorZahtjev::create([
            'doktor_id' => $doktor->id,
            'klinika_id' => $validated['klinika_id'],
            'poruka' => $validated['poruka'] ?? null,
            'initiated_by' => 'doctor',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Zahtjev uspješno poslan',
            'data' => $zahtjev->load(['klinika', 'doktor']),
        ], 201);
    }

    /**
     * Cancel clinic request
     */
    public function cancelClinicRequest(int $id): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $zahtjev->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Zahtjev otkazan',
        ]);
    }

    /**
     * Respond to clinic invitation
     */
    public function respondToInvitation(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'odgovor' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->where('initiated_by', 'clinic')
            ->firstOrFail();

        $zahtjev->update([
            'status' => $validated['status'],
            'odgovor' => $validated['odgovor'] ?? null,
        ]);

        // If accepted, update doctor's clinic
        if ($validated['status'] === 'accepted') {
            $doktor->update(['klinika_id' => $zahtjev->klinika_id]);
        }

        return response()->json([
            'message' => $validated['status'] === 'accepted' ? 'Poziv prihvaćen' : 'Poziv odbijen',
        ]);
    }

    /**
     * Leave clinic
     */
    public function leaveClinic(): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        if (!$doktor->klinika_id) {
            return response()->json([
                'message' => 'Niste član nijedne klinike.',
            ], 400);
        }

        $doktor->update(['klinika_id' => null]);

        return response()->json([
            'message' => 'Uspješno ste napustili kliniku',
        ]);
    }

    /**
     * Get doctor's guest visits
     */
    public function getMyGuestVisits(Request $request): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $query = \App\Models\DoktorGostovanje::with(['klinika'])
            ->where('doktor_id', $doktor->id);

        if ($request->boolean('upcoming')) {
            $query->where('datum_od', '>=', now());
        }

        $gostovanja = $query->orderBy('datum_od', 'desc')->get();

        return response()->json($gostovanja);
    }

    /**
     * Get guest visit services
     */
    public function getGuestVisitServices(int $id): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $services = \App\Models\DoktorGostovanjeUsluga::where('gostovanje_id', $id)
            ->orderBy('redoslijed')
            ->get();

        return response()->json($services);
    }

    /**
     * Add service to guest visit
     */
    public function addGuestVisitService(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'naziv' => 'required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'required|numeric|min:0',
            'trajanje_minuti' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $maxRedoslijed = \App\Models\DoktorGostovanjeUsluga::where('gostovanje_id', $id)
            ->max('redoslijed') ?? 0;

        $usluga = \App\Models\DoktorGostovanjeUsluga::create([
            'gostovanje_id' => $id,
            'naziv' => $validated['naziv'],
            'opis' => $validated['opis'],
            'cijena' => $validated['cijena'],
            'trajanje_minuti' => $validated['trajanje_minuti'],
            'redoslijed' => $maxRedoslijed + 1,
            'aktivan' => true,
        ]);

        return response()->json([
            'message' => 'Usluga uspješno dodana',
            'data' => $usluga,
        ], 201);
    }

    /**
     * Update guest visit service
     */
    public function updateGuestVisitService(int $gostovanjeId, int $uslugaId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'naziv' => 'sometimes|required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'sometimes|required|numeric|min:0',
            'trajanje_minuti' => 'sometimes|required|integer|min:1',
            'aktivan' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $gostovanjeId)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $usluga = \App\Models\DoktorGostovanjeUsluga::where('id', $uslugaId)
            ->where('gostovanje_id', $gostovanjeId)
            ->firstOrFail();

        $usluga->update($validated);

        return response()->json([
            'message' => 'Usluga uspješno ažurirana',
            'data' => $usluga->fresh(),
        ]);
    }

    /**
     * Delete guest visit service
     */
    public function deleteGuestVisitService(int $gostovanjeId, int $uslugaId): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $gostovanjeId)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $usluga = \App\Models\DoktorGostovanjeUsluga::where('id', $uslugaId)
            ->where('gostovanje_id', $gostovanjeId)
            ->firstOrFail();

        $usluga->delete();

        return response()->json([
            'message' => 'Usluga uspješno obrisana',
        ]);
    }

    /**
     * Respond to guest visit invitation
     */
    public function respondToGuestVisit(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled',
            'napomena' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $gostovanje->update([
            'status' => $validated['status'],
            'napomena' => $validated['napomena'] ?? null,
        ]);

        return response()->json([
            'message' => $validated['status'] === 'confirmed' ? 'Gostovanje potvrđeno' : 'Gostovanje otkazano',
        ]);
    }

    /**
     * Cancel guest visit
     */
    public function cancelGuestVisit(int $id, Request $request): JsonResponse
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $gostovanje = \App\Models\DoktorGostovanje::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $gostovanje->update([
            'status' => 'cancelled',
            'napomena' => $request->input('napomena'),
        ]);

        return response()->json([
            'message' => 'Gostovanje otkazano',
        ]);
    }
}
