<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laboratorija;
use App\Models\Analiza;
use App\Models\LaboratorijaGalerija;
use App\Models\LaboratorijaRadnoVrijeme;
use App\Models\PaketAnaliza;
use App\Http\Requests\UpdateLaboratorijaRequest;
use App\Http\Requests\StoreAnalizaRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class LaboratorijaDashboardController extends Controller
{
    /**
     * Get laboratory profile
     */
    public function getProfile(): JsonResponse
    {
        $user = auth()->user();

        $laboratorija = Laboratorija::with([
            'radnoVrijeme',
            'galerija',
            'klinika',
            'doktor'
        ])->where('user_id', $user->id)->firstOrFail();

        // Convert radnoVrijeme relationship to radno_vrijeme format for frontend
        $radnoVrijemeFormatted = [];
        foreach ($laboratorija->radnoVrijeme as $rv) {
            $radnoVrijemeFormatted[$rv->dan] = [
                'open' => $rv->otvaranje ? substr($rv->otvaranje, 0, 5) : null,
                'close' => $rv->zatvaranje ? substr($rv->zatvaranje, 0, 5) : null,
                'closed' => $rv->zatvoreno,
            ];
        }

        $labData = $laboratorija->toArray();
        $labData['radno_vrijeme'] = $radnoVrijemeFormatted;

        return response()->json($labData);
    }

    /**
     * Update laboratory profile
     */
    public function updateProfile(UpdateLaboratorijaRequest $request): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Extract radno_vrijeme if present
            $radnoVrijeme = null;
            if ($request->has('radno_vrijeme')) {
                $radnoVrijeme = $request->input('radno_vrijeme');
                unset($data['radno_vrijeme']); // Remove from main update

                Log::info('Radno vrijeme received', ['radno_vrijeme' => $radnoVrijeme]);
            }

            // Update main profile data
            $laboratorija->update($data);

            // Update working hours in relationship table if provided
            if ($radnoVrijeme && is_array($radnoVrijeme)) {
                foreach ($radnoVrijeme as $dan => $hours) {
                    $isClosed = isset($hours['closed']) && $hours['closed'] === true;

                    \App\Models\LaboratorijaRadnoVrijeme::updateOrCreate(
                        [
                            'laboratorija_id' => $laboratorija->id,
                            'dan' => $dan,
                        ],
                        [
                            'otvaranje' => $isClosed ? null : ($hours['open'] ?? null),
                            'zatvaranje' => $isClosed ? null : ($hours['close'] ?? null),
                            'zatvoreno' => $isClosed,
                        ]
                    );

                    Log::info('Updated working hours', [
                        'dan' => $dan,
                        'otvaranje' => $isClosed ? null : ($hours['open'] ?? null),
                        'zatvaranje' => $isClosed ? null : ($hours['close'] ?? null),
                        'zatvoreno' => $isClosed,
                    ]);
                }
            }

            // Log activity
            Log::info('Laboratory profile updated', [
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            // Reload with working hours
            $laboratorija = $laboratorija->fresh(['radnoVrijeme']);

            // Convert radnoVrijeme relationship to radno_vrijeme format for frontend
            $radnoVrijemeFormatted = [];
            foreach ($laboratorija->radnoVrijeme as $rv) {
                $radnoVrijemeFormatted[$rv->dan] = [
                    'open' => $rv->otvaranje ? substr($rv->otvaranje, 0, 5) : null,
                    'close' => $rv->zatvaranje ? substr($rv->zatvaranje, 0, 5) : null,
                    'closed' => $rv->zatvoreno,
                ];
            }

            $labData = $laboratorija->toArray();
            $labData['radno_vrijeme'] = $radnoVrijemeFormatted;

            return response()->json([
                'message' => 'Profil uspješno ažuriran',
                'laboratorija' => $labData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating laboratory profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju profila: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        // Verify current password
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Trenutna lozinka nije ispravna',
            ], 422);
        }

        try {
            $user->update([
                'password' => \Hash::make($request->new_password),
            ]);

            Log::info('Laboratory password changed', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Lozinka uspješno promijenjena',
            ]);

        } catch (\Exception $e) {
            Log::error('Error changing password', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri promjeni lozinke',
            ], 500);
        }
    }

    /**
     * Get all analyses for laboratory
     */
    public function getAnalize(Request $request): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $kategorijaId = $request->input('kategorija_id');
        $search = $request->input('search');
        $aktivan = $request->input('aktivan');

        $query = Analiza::with('kategorija')
            ->where('laboratorija_id', $laboratorija->id);

        if ($kategorijaId) {
            $query->byKategorija($kategorijaId);
        }

        if ($search) {
            $query->search($search);
        }

        if ($aktivan !== null) {
            $query->where('aktivan', $aktivan);
        }

        $analize = $query->orderBy('redoslijed')->orderBy('id')->get();

        return response()->json($analize);
    }

    /**
     * Create new analysis
     */
    public function createAnaliza(StoreAnalizaRequest $request): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['laboratorija_id'] = $laboratorija->id;

            $analiza = Analiza::create($data);

            Log::info('Analysis created', [
                'analiza_id' => $analiza->id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Analiza uspješno kreirana',
                'analiza' => $analiza->load('kategorija'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating analysis', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri kreiranju analize',
            ], 500);
        }
    }

    /**
     * Update analysis
     */
    public function updateAnaliza(int $id, StoreAnalizaRequest $request): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $analiza = Analiza::where('laboratorija_id', $laboratorija->id)
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            $analiza->update($request->validated());

            Log::info('Analysis updated', [
                'analiza_id' => $analiza->id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Analiza uspješno ažurirana',
                'analiza' => $analiza->fresh(['kategorija']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating analysis', [
                'error' => $e->getMessage(),
                'analiza_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju analize',
            ], 500);
        }
    }

    /**
     * Delete analysis
     */
    public function deleteAnaliza(int $id): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $analiza = Analiza::where('laboratorija_id', $laboratorija->id)
            ->findOrFail($id);

        try {
            $analiza->delete();

            Log::info('Analysis deleted', [
                'analiza_id' => $id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Analiza uspješno obrisana',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting analysis', [
                'error' => $e->getMessage(),
                'analiza_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri brisanju analize',
            ], 500);
        }
    }

    /**
     * Upload gallery image
     */
    public function uploadGalleryImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB
            'naslov' => 'nullable|string|max:255',
            'opis' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            $image = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Store original
            $path = $image->storeAs('laboratorije/galerija', $filename, 'public');

            // Create thumbnail
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = storage_path('app/public/laboratorije/galerija/' . $thumbnailFilename);

            Image::make($image)->fit(400, 300)->save($thumbnailPath);

            // Create gallery entry
            $galerija = LaboratorijaGalerija::create([
                'laboratorija_id' => $laboratorija->id,
                'slika_url' => $path,
                'thumbnail_url' => 'laboratorije/galerija/' . $thumbnailFilename,
                'naslov' => $request->naslov,
                'opis' => $request->opis,
                'redoslijed' => LaboratorijaGalerija::where('laboratorija_id', $laboratorija->id)->max('redoslijed') + 1,
            ]);

            Log::info('Gallery image uploaded', [
                'galerija_id' => $galerija->id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Slika uspješno dodana',
                'galerija' => $galerija,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading gallery image', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri dodavanju slike',
            ], 500);
        }
    }

    /**
     * Delete gallery image
     */
    public function deleteGalleryImage(int $id): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $galerija = LaboratorijaGalerija::where('laboratorija_id', $laboratorija->id)
            ->findOrFail($id);

        try {
            $galerija->delete(); // Model will handle file deletion

            Log::info('Gallery image deleted', [
                'galerija_id' => $id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Slika uspješno obrisana',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting gallery image', [
                'error' => $e->getMessage(),
                'galerija_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri brisanju slike',
            ], 500);
        }
    }

    /**
     * Update working hours
     */
    public function updateRadnoVrijeme(Request $request): JsonResponse
    {
        $request->validate([
            'radno_vrijeme' => 'required|array',
            'radno_vrijeme.*.dan' => 'required|in:ponedeljak,utorak,srijeda,cetvrtak,petak,subota,nedjelja',
            'radno_vrijeme.*.otvaranje' => 'nullable|date_format:H:i',
            'radno_vrijeme.*.zatvaranje' => 'nullable|date_format:H:i',
            'radno_vrijeme.*.pauza_od' => 'nullable|date_format:H:i',
            'radno_vrijeme.*.pauza_do' => 'nullable|date_format:H:i',
            'radno_vrijeme.*.zatvoreno' => 'required|boolean',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            foreach ($request->radno_vrijeme as $rv) {
                LaboratorijaRadnoVrijeme::updateOrCreate(
                    [
                        'laboratorija_id' => $laboratorija->id,
                        'dan' => $rv['dan'],
                    ],
                    [
                        'otvaranje' => $rv['zatvoreno'] ? null : $rv['otvaranje'],
                        'zatvaranje' => $rv['zatvoreno'] ? null : $rv['zatvaranje'],
                        'pauza_od' => $rv['pauza_od'] ?? null,
                        'pauza_do' => $rv['pauza_do'] ?? null,
                        'zatvoreno' => $rv['zatvoreno'],
                    ]
                );
            }

            Log::info('Working hours updated', [
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Radno vrijeme uspješno ažurirano',
                'radno_vrijeme' => $laboratorija->fresh(['radnoVrijeme'])->radnoVrijeme,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating working hours', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju radnog vremena',
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $stats = [
            'ukupno_analiza' => $laboratorija->analize()->count(),
            'aktivne_analize' => $laboratorija->aktivneAnalize()->count(),
            'analize_na_akciji' => $laboratorija->aktivneAnalize()->naAkciji()->count(),
            'ukupno_paketa' => $laboratorija->paketi()->count(),
            'broj_pregleda' => $laboratorija->broj_pregleda,
            'prosjecna_ocjena' => $laboratorija->prosjecna_ocjena,
            'broj_recenzija' => $laboratorija->broj_recenzija,
            'broj_slika' => $laboratorija->galerija()->count(),

            // Analysis by category
            'analize_po_kategorijama' => $laboratorija->aktivneAnalize()
                ->select('kategorija_id', DB::raw('count(*) as broj'))
                ->with('kategorija:id,naziv')
                ->groupBy('kategorija_id')
                ->get(),

            // Recent views trend (last 30 days)
            'trend_pregleda' => [
                'danas' => $laboratorija->broj_pregleda, // Simplified, would need daily tracking
                'ukupno' => $laboratorija->broj_pregleda,
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get packages
     */
    public function getPaketi(): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $paketi = $laboratorija->paketi()->orderBy('redoslijed')->orderBy('id')->get();

        return response()->json($paketi);
    }

    /**
     * Create package
     */
    public function createPaket(Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'required|string|max:255',
            'opis' => 'nullable|string|max:2000',
            'cijena' => 'required|numeric|min:0',
            'analize_ids' => 'required|array|min:2',
            'analize_ids.*' => 'exists:analize,id',
            'prikazi_ustedite' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Calculate savings
            $originalPrice = Analiza::whereIn('id', $request->analize_ids)
                ->sum(DB::raw('COALESCE(akcijska_cijena, cijena)'));

            $ustedite = max(0, $originalPrice - $request->cijena);

            $paket = PaketAnaliza::create([
                'laboratorija_id' => $laboratorija->id,
                'naziv' => $request->naziv,
                'opis' => $request->opis,
                'cijena' => $request->cijena,
                'ustedite' => $ustedite,
                'prikazi_ustedite' => $request->prikazi_ustedite ?? true,
                'analize_ids' => $request->analize_ids,
                'aktivan' => true,
            ]);

            Log::info('Package created', [
                'paket_id' => $paket->id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paket uspješno kreiran',
                'paket' => $paket,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating package', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri kreiranju paketa',
            ], 500);
        }
    }

    /**
     * Update package
     */
    public function updatePaket(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'naziv' => 'sometimes|required|string|max:255',
            'opis' => 'nullable|string|max:2000',
            'cijena' => 'sometimes|required|numeric|min:0',
            'analize_ids' => 'sometimes|required|array|min:2',
            'analize_ids.*' => 'exists:analize,id',
            'aktivan' => 'sometimes|boolean',
            'prikazi_ustedite' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $paket = PaketAnaliza::where('laboratorija_id', $laboratorija->id)
            ->findOrFail($id);

        try {
            DB::beginTransaction();

            $data = $request->only(['naziv', 'opis', 'cijena', 'analize_ids', 'aktivan', 'prikazi_ustedite']);

            // Recalculate savings if price or analyses changed
            if (isset($data['cijena']) || isset($data['analize_ids'])) {
                $analizeIds = $data['analize_ids'] ?? $paket->analize_ids;
                $cijena = $data['cijena'] ?? $paket->cijena;

                $originalPrice = Analiza::whereIn('id', $analizeIds)
                    ->sum(DB::raw('COALESCE(akcijska_cijena, cijena)'));

                $data['ustedite'] = max(0, $originalPrice - $cijena);
            }

            $paket->update($data);

            Log::info('Package updated', [
                'paket_id' => $paket->id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paket uspješno ažuriran',
                'paket' => $paket->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating package', [
                'error' => $e->getMessage(),
                'paket_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju paketa',
            ], 500);
        }
    }

    /**
     * Delete package
     */
    public function deletePaket(int $id): JsonResponse
    {
        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        $paket = PaketAnaliza::where('laboratorija_id', $laboratorija->id)
            ->findOrFail($id);

        try {
            $paket->delete();

            Log::info('Package deleted', [
                'paket_id' => $id,
                'laboratorija_id' => $laboratorija->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Paket uspješno obrisan',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting package', [
                'error' => $e->getMessage(),
                'paket_id' => $id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri brisanju paketa',
            ], 500);
        }
    }

    /**
     * Reorder analyses
     */
    public function reorderAnalize(Request $request): JsonResponse
    {
        $request->validate([
            'analyses' => 'required|array',
            'analyses.*.id' => 'required|integer',
            'analyses.*.redoslijed' => 'required|integer',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            foreach ($request->analyses as $item) {
                Analiza::where('laboratorija_id', $laboratorija->id)
                    ->where('id', $item['id'])
                    ->update(['redoslijed' => $item['redoslijed']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Redoslijed analiza uspješno ažuriran',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering analyses', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju redoslijeda',
            ], 500);
        }
    }

    /**
     * Reorder packages
     */
    public function reorderPaketi(Request $request): JsonResponse
    {
        $request->validate([
            'packages' => 'required|array',
            'packages.*.id' => 'required|integer',
            'packages.*.redoslijed' => 'required|integer',
        ]);

        $user = auth()->user();
        $laboratorija = Laboratorija::where('user_id', $user->id)->firstOrFail();

        try {
            DB::beginTransaction();

            foreach ($request->packages as $item) {
                PaketAnaliza::where('laboratorija_id', $laboratorija->id)
                    ->where('id', $item['id'])
                    ->update(['redoslijed' => $item['redoslijed']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Redoslijed paketa uspješno ažuriran',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering packages', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Greška pri ažuriranju redoslijeda',
            ], 500);
        }
    }
}
