<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banja;
use App\Models\BanjaPaket;
use App\Models\BanjaCustomTerapija;
use App\Models\BanjaUpit;
use App\Models\BanjaRecenzija;
use App\Models\VrstaBanje;
use App\Models\Indikacija;
use App\Models\Terapija;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpaDashboardController extends Controller
{
    /**
     * Get spa manager's banja profile
     */
    public function profile(): JsonResponse
    {
        try {
            $banja = Banja::with(['vrste', 'indikacije', 'terapije', 'customTerapije'])
                ->where('user_id', auth()->id())
                ->first();

            if (!$banja) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nemate registrovanu banju'
                ], 404);
            }

            return response()->json($banja);

        } catch (\Exception $e) {
            \Log::error('Spa profile error: ' . $e->getMessage());
            return response()->json(['message' => 'Greška pri dohvatanju profila'], 500);
        }
    }

    /**
     * Update spa profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $banja->update($request->only([
                'naziv', 'grad', 'regija', 'adresa', 'telefon', 'email', 'website',
                'opis', 'detaljni_opis', 'medicinsko_osoblje', 'medicinski_nadzor',
                'fizijatar_prisutan', 'ima_smjestaj', 'broj_kreveta',
                'online_rezervacija', 'online_upit', 'radno_vrijeme',
                'latitude', 'longitude', 'google_maps_link'
            ]));

            // Update vrste
            if ($request->has('vrste')) {
                $banja->vrste()->sync($request->vrste);
            }

            // Update indikacije
            if ($request->has('indikacije')) {
                $indikacije = [];
                foreach ($request->indikacije as $index => $id) {
                    $indikacije[$id] = ['prioritet' => $index + 1];
                }
                $banja->indikacije()->sync($indikacije);
            }

            // Update terapije with prices and durations
            if ($request->has('terapije')) {
                $terapije = [];
                $cijene = $request->terapije_cijena ?? [];
                $trajanja = $request->terapije_trajanje ?? [];

                foreach ($request->terapije as $index => $id) {
                    $terapije[$id] = [
                        'cijena' => $cijene[$index] ?? null,
                        'trajanje_minuta' => $trajanja[$index] ?? null,
                    ];
                }
                $banja->terapije()->sync($terapije);
            }

            DB::commit();

            $banja->load(['vrste', 'indikacije', 'terapije', 'customTerapije']);

            return response()->json([
                'success' => true,
                'message' => 'Profil uspješno ažuriran',
                'banja' => $banja
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Spa update error: ' . $e->getMessage());
            return response()->json(['message' => 'Greška pri ažuriranju profila'], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            return response()->json([
                'broj_pregleda' => $banja->broj_pregleda,
                'prosjecna_ocjena' => $banja->prosjecna_ocjena,
                'broj_recenzija' => $banja->broj_recenzija,
                'ukupno_upita' => $banja->upiti()->count(),
                'novi_upiti' => $banja->upiti()->where('status', 'novi')->count(),
                'procitani_upiti' => $banja->upiti()->where('status', 'procitan')->count(),
                'odgovoreni_upiti' => $banja->upiti()->where('status', 'odgovoren')->count(),
                'recenzije_na_cekanju' => $banja->recenzije()->where('odobreno', false)->count(),
                'odobrene_recenzije' => $banja->recenzije()->where('odobreno', true)->count(),
                'status' => [
                    'aktivan' => $banja->aktivan,
                    'verifikovan' => $banja->verifikovan
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Spa statistics error: ' . $e->getMessage());
            return response()->json(['message' => 'Greška pri dohvatanju statistika'], 500);
        }
    }

    /**
     * Get paketi
     */
    public function paketi(): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            return response()->json($banja->paketi()->orderBy('redoslijed')->get());
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Create paket
     */
    public function createPaket(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $paket = $banja->paketi()->create($request->all());

            return response()->json(['success' => true, 'data' => $paket]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška pri kreiranju paketa'], 500);
        }
    }

    /**
     * Update paket
     */
    public function updatePaket(Request $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $paket = $banja->paketi()->findOrFail($id);
            $paket->update($request->all());

            return response()->json(['success' => true, 'data' => $paket]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška pri ažuriranju paketa'], 500);
        }
    }

    /**
     * Delete paket
     */
    public function deletePaket(int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $banja->paketi()->findOrFail($id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška pri brisanju paketa'], 500);
        }
    }

    /**
     * Reorder paketi
     */
    public function reorderPaketi(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            foreach ($request->paketi as $item) {
                $banja->paketi()->where('id', $item['id'])->update(['redoslijed' => $item['redoslijed']]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Get available vrste
     */
    public function availableVrste(): JsonResponse
    {
        return response()->json(VrstaBanje::aktivan()->ordered()->get());
    }

    /**
     * Get available indikacije
     */
    public function availableIndikacije(): JsonResponse
    {
        return response()->json(Indikacija::aktivan()->ordered()->get());
    }

    /**
     * Get available terapije
     */
    public function availableTerapije(): JsonResponse
    {
        return response()->json(Terapija::aktivan()->ordered()->get());
    }

    /**
     * Get custom terapije
     */
    public function customTerapije(): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            return response()->json($banja->customTerapije()->orderBy('redoslijed')->get());
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Create custom terapija
     */
    public function createCustomTerapija(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $terapija = $banja->customTerapije()->create($request->all());

            return response()->json(['success' => true, 'data' => $terapija]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Update custom terapija
     */
    public function updateCustomTerapija(Request $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $terapija = $banja->customTerapije()->findOrFail($id);
            $terapija->update($request->all());

            return response()->json(['success' => true, 'data' => $terapija]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Delete custom terapija
     */
    public function deleteCustomTerapija(int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $banja->customTerapije()->findOrFail($id)->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Reorder terapije
     */
    public function reorderTerapije(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            foreach ($request->terapije as $item) {
                if ($item['type'] === 'custom') {
                    $banja->customTerapije()->where('id', $item['id'])->update(['redoslijed' => $item['redoslijed']]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Upload featured image
     */
    public function uploadFeaturedImage(Request $request): JsonResponse
    {
        try {
            $request->validate(['image' => 'required|image|max:2048']);

            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $path = $request->file('image')->store('banje/featured', 'public');
            $fullUrl = url('/storage/' . $path);
            $banja->update(['featured_slika' => $fullUrl]);

            return response()->json(['success' => true, 'url' => $fullUrl]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška pri uploadu'], 500);
        }
    }

    /**
     * Upload gallery image
     */
    public function uploadGalleryImage(Request $request): JsonResponse
    {
        try {
            $request->validate(['image' => 'required|image|max:2048']);

            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $path = $request->file('image')->store('banje/galerija', 'public');

            // Get raw galerija from database (without accessor transformation)
            $galerija = json_decode($banja->getRawOriginal('galerija') ?? '[]', true) ?: [];
            // Store full URL directly to avoid accessor double-prefixing
            $galerija[] = url('/storage/' . $path);
            $banja->update(['galerija' => $galerija]);

            // Return the galerija (accessor will handle URL formatting)
            return response()->json(['success' => true, 'galerija' => $banja->fresh()->galerija]);
        } catch (\Exception $e) {
            \Log::error('Gallery upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Greška pri uploadu'], 500);
        }
    }

    /**
     * Delete gallery image
     */
    public function deleteGalleryImage(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            // Get raw galerija from database
            $galerija = json_decode($banja->getRawOriginal('galerija') ?? '[]', true) ?: [];

            $urlToDelete = $request->url;
            \Log::info('Deleting gallery image', ['url' => $urlToDelete, 'galerija' => $galerija]);

            // Normalize URL for comparison - extract just the path part
            $normalizedUrlToDelete = $this->normalizeImageUrl($urlToDelete);

            $galerija = array_values(array_filter($galerija, function($img) use ($normalizedUrlToDelete) {
                $normalizedImg = $this->normalizeImageUrl($img);
                return $normalizedImg !== $normalizedUrlToDelete;
            }));

            $banja->update(['galerija' => $galerija]);

            // Return fresh galerija with accessor transformation
            return response()->json(['success' => true, 'galerija' => $banja->fresh()->galerija]);
        } catch (\Exception $e) {
            \Log::error('Gallery delete error: ' . $e->getMessage());
            return response()->json(['message' => 'Greška'], 500);
        }
    }

    /**
     * Toggle active status for manager's spa.
     */
    public function toggleActive(): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $newStatus = !$banja->aktivan;

            $banja->update(['aktivan' => $newStatus]);
            $banja->logAudit(
                $newStatus ? 'activate' : 'deactivate',
                ['aktivan' => !$newStatus],
                ['aktivan' => $newStatus]
            );

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'Banja je aktivirana' : 'Banja je deaktivirana',
                'data' => [
                    'aktivan' => $banja->aktivan,
                    'verifikovan' => $banja->verifikovan,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Spa toggle active error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri promjeni statusa banje'
            ], 500);
        }
    }

    /**
     * List inquiries for manager's spa.
     */
    public function upiti(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $query = BanjaUpit::where('banja_id', $banja->id)
                ->with('user')
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('tip')) {
                $query->where('tip', $request->tip);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ime', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('poruka', 'like', "%{$search}%");
                });
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            $upiti = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $upiti->items(),
                'pagination' => [
                    'current_page' => $upiti->currentPage(),
                    'last_page' => $upiti->lastPage(),
                    'per_page' => $upiti->perPage(),
                    'total' => $upiti->total(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Spa inquiries error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri dohvatanju upita'
            ], 500);
        }
    }

    /**
     * Mark inquiry as read.
     */
    public function oznaciUpitProcitan(int $id): JsonResponse
    {
        return $this->azurirajStatusUpita($id, 'procitan');
    }

    /**
     * Mark inquiry as answered.
     */
    public function oznaciUpitOdgovoren(int $id): JsonResponse
    {
        return $this->azurirajStatusUpita($id, 'odgovoren');
    }

    /**
     * Close inquiry.
     */
    public function zatvoriUpit(int $id): JsonResponse
    {
        return $this->azurirajStatusUpita($id, 'zatvoren');
    }

    /**
     * List reviews for manager's spa.
     */
    public function recenzije(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();

            $query = BanjaRecenzija::where('banja_id', $banja->id)
                ->with('user')
                ->latest();

            if ($request->filled('odobreno')) {
                $query->where('odobreno', $request->boolean('odobreno'));
            }

            if ($request->filled('ocjena')) {
                $query->where('ocjena', (int) $request->ocjena);
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            $recenzije = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $recenzije->items(),
                'pagination' => [
                    'current_page' => $recenzije->currentPage(),
                    'last_page' => $recenzije->lastPage(),
                    'per_page' => $recenzije->perPage(),
                    'total' => $recenzije->total(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Spa reviews error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri dohvatanju recenzija'
            ], 500);
        }
    }

    /**
     * Internal helper for inquiry status update.
     */
    private function azurirajStatusUpita(int $upitId, string $status): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $upit = BanjaUpit::where('banja_id', $banja->id)->findOrFail($upitId);

            $upit->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Status upita je aÅ¾uriran',
                'data' => $upit
            ]);
        } catch (\Exception $e) {
            \Log::error('Spa inquiry status update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri aÅ¾uriranju statusa upita'
            ], 500);
        }
    }

    /**
     * Normalize image URL for comparison
     */
    private function normalizeImageUrl(string $url): string
    {
        // Remove domain prefix if present
        $url = preg_replace('#^https?://[^/]+#', '', $url);
        // Remove multiple /storage/ prefixes
        $url = preg_replace('#(/storage)+#', '/storage', $url);
        return $url;
    }

    /**
     * Set featured image from gallery
     */
    public function setFeaturedImage(Request $request): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->firstOrFail();
            $banja->update(['featured_slika' => $request->image_url]);

            return response()->json(['success' => true, 'featured_slika' => $request->image_url]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Greška'], 500);
        }
    }
}
