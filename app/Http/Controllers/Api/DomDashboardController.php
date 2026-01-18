<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDomRequest;
use App\Models\Dom;
use App\Models\DomUpit;
use App\Models\DomRecenzija;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DomDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Get care home manager's dom
     */
    public function mojDom(): JsonResponse
    {
        try {
            $dom = Dom::with([
                'tipDoma',
                'nivoNjege',
                'programiNjege',
                'medicinskUsluge',
                'smjestajUslovi',
                'odobreneRecenzije' => function ($query) {
                    $query->latest()->limit(5);
                }
            ])
            ->where('user_id', auth()->id())
            ->first();

            if (!$dom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nemate registrovan dom'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $dom
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care home: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju doma'
            ], 500);
        }
    }

    /**
     * Update care home information
     */
    public function azurirajDom(UpdateDomRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            // Check if dom is verified - some fields can't be changed
            $validated = $request->validated();

            if ($dom->verifikovan) {
                // Remove critical fields that can't be changed after verification
                unset($validated['naziv'], $validated['tip_doma_id'], $validated['nivo_njege_id']);
            }

            $dom->update($validated);

            // Update programs
            if ($request->filled('programi_njege')) {
                $programs = [];
                foreach ($request->programi_njege as $index => $programId) {
                    $programs[$programId] = ['prioritet' => $index + 1];
                }
                $dom->programiNjege()->sync($programs);
            }

            // Update medical services
            if ($request->filled('medicinske_usluge')) {
                $dom->medicinskUsluge()->sync($request->medicinske_usluge);
            }

            // Update accommodation conditions
            if ($request->filled('smjestaj_uslovi')) {
                $dom->smjestajUslovi()->sync($request->smjestaj_uslovi);
            }

            DB::commit();

            $dom->load(['tipDoma', 'nivoNjege', 'programiNjege', 'medicinskUsluge', 'smjestajUslovi']);

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno ažuriran',
                'data' => $dom
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating care home: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju doma'
            ], 500);
        }
    }

    /**
     * Get care home inquiries
     */
    public function upiti(Request $request): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $query = DomUpit::where('dom_id', $dom->id)
                ->with('user')
                ->latest();

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->filled('tip')) {
                $query->where('tip', $request->tip);
            }

            // Search
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('ime', 'ILIKE', "%{$request->search}%")
                      ->orWhere('email', 'ILIKE', "%{$request->search}%")
                      ->orWhere('poruka', 'ILIKE', "%{$request->search}%");
                });
            }

            $perPage = min($request->get('per_page', 20), 50);
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
            \Log::error('Error fetching care home inquiries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju upita'
            ], 500);
        }
    }

    /**
     * Update inquiry status
     */
    public function azurirajUpit(Request $request, int $id): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $upit = DomUpit::where('dom_id', $dom->id)
                ->findOrFail($id);

            $request->validate([
                'status' => 'required|in:novi,procitan,odgovoren,zatvoren',
                'napomena' => 'nullable|string|max:1000'
            ]);

            $upit->update([
                'status' => $request->status,
                'napomena' => $request->napomena,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status upita je ažuriran',
                'data' => $upit
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating inquiry status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju upita'
            ], 500);
        }
    }

    /**
     * Get care home reviews
     */
    public function recenzije(Request $request): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $query = DomRecenzija::where('dom_id', $dom->id)
                ->with('user')
                ->latest();

            // Filter by approval status
            if ($request->filled('odobreno')) {
                $query->where('odobreno', $request->boolean('odobreno'));
            }

            // Filter by rating
            if ($request->filled('ocjena')) {
                $query->where('ocjena', $request->ocjena);
            }

            $perPage = min($request->get('per_page', 20), 50);
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
            \Log::error('Error fetching care home reviews: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju recenzija'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function statistike(): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $stats = [
                'dom' => [
                    'broj_pregleda' => $dom->broj_pregleda,
                    'prosjecna_ocjena' => $dom->prosjecna_ocjena,
                    'broj_recenzija' => $dom->broj_recenzija,
                    'verifikovan' => $dom->verifikovan,
                    'aktivan' => $dom->aktivan,
                ],
                'upiti' => [
                    'ukupno' => DomUpit::where('dom_id', $dom->id)->count(),
                    'novi' => DomUpit::where('dom_id', $dom->id)->where('status', 'novi')->count(),
                    'procitani' => DomUpit::where('dom_id', $dom->id)->where('status', 'procitan')->count(),
                    'odgovoreni' => DomUpit::where('dom_id', $dom->id)->where('status', 'odgovoren')->count(),
                    'zatvoreni' => DomUpit::where('dom_id', $dom->id)->where('status', 'zatvoren')->count(),
                    'ovaj_mjesec' => DomUpit::where('dom_id', $dom->id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count(),
                ],
                'recenzije' => [
                    'ukupno' => DomRecenzija::where('dom_id', $dom->id)->count(),
                    'odobrene' => DomRecenzija::where('dom_id', $dom->id)->where('odobreno', true)->count(),
                    'na_cekanju' => DomRecenzija::where('dom_id', $dom->id)->where('odobreno', false)->count(),
                    'ovaj_mjesec' => DomRecenzija::where('dom_id', $dom->id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count(),
                ],
                'aktivnost' => [
                    'pregledi_ovaj_mjesec' => $this->getMonthlyViews($dom->id),
                    'upiti_ovaj_mjesec' => DomUpit::where('dom_id', $dom->id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count(),
                    'recenzije_ovaj_mjesec' => DomRecenzija::where('dom_id', $dom->id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count(),
                ]
            ];

            // Rating breakdown
            $ratingBreakdown = DomRecenzija::where('dom_id', $dom->id)
                ->where('odobreno', true)
                ->selectRaw('ocjena, COUNT(*) as count')
                ->groupBy('ocjena')
                ->orderBy('ocjena', 'desc')
                ->get()
                ->pluck('count', 'ocjena')
                ->toArray();

            $stats['recenzije']['rating_breakdown'] = $ratingBreakdown;

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care home stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju statistika'
            ], 500);
        }
    }

    /**
     * Get recent activity
     */
    public function aktivnost(Request $request): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $limit = min($request->get('limit', 10), 50);

            // Get recent inquiries
            $recentUpiti = DomUpit::where('dom_id', $dom->id)
                ->with('user')
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($upit) {
                    return [
                        'type' => 'upit',
                        'id' => $upit->id,
                        'title' => "Novi upit od {$upit->ime}",
                        'description' => substr($upit->poruka, 0, 100) . '...',
                        'status' => $upit->status,
                        'created_at' => $upit->created_at,
                    ];
                });

            // Get recent reviews
            $recentRecenzije = DomRecenzija::where('dom_id', $dom->id)
                ->with('user')
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($recenzija) {
                    return [
                        'type' => 'recenzija',
                        'id' => $recenzija->id,
                        'title' => "Nova recenzija od {$recenzija->ime}",
                        'description' => "Ocjena: {$recenzija->ocjena}/5",
                        'status' => $recenzija->odobreno ? 'odobrena' : 'na_cekanju',
                        'created_at' => $recenzija->created_at,
                    ];
                });

            // Combine and sort by date
            $activity = $recentUpiti->concat($recentRecenzije)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $activity
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching care home activity: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju aktivnosti'
            ], 500);
        }
    }

    /**
     * Upload images for care home
     */
    public function uploadSlike(Request $request): JsonResponse
    {
        try {
            $dom = Dom::where('user_id', auth()->id())->firstOrFail();

            $request->validate([
                'featured_slika' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'galerija' => 'nullable|array|max:10',
                'galerija.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $updates = [];

            // Upload featured image
            if ($request->hasFile('featured_slika')) {
                $featuredPath = $request->file('featured_slika')->store('domovi/featured', 'public');
                $updates['featured_slika'] = $featuredPath;
            }

            // Upload gallery images
            if ($request->hasFile('galerija')) {
                $galleryPaths = [];
                foreach ($request->file('galerija') as $file) {
                    $path = $file->store('domovi/galerija', 'public');
                    $galleryPaths[] = $path;
                }

                // Merge with existing gallery
                $existingGallery = $dom->galerija ?? [];
                $updates['galerija'] = array_merge($existingGallery, $galleryPaths);
            }

            if (!empty($updates)) {
                $dom->update($updates);
            }

            return response()->json([
                'success' => true,
                'message' => 'Slike su uspješno učitane',
                'data' => [
                    'featured_slika' => $dom->featured_slika,
                    'galerija' => $dom->galerija
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading care home images: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju slika'
            ], 500);
        }
    }

    /**
     * Get monthly views (placeholder - would need proper analytics)
     */
    private function getMonthlyViews(int $domId): int
    {
        // This is a placeholder. In a real implementation, you would:
        // 1. Track daily/monthly view counts in a separate table
        // 2. Use analytics service like Google Analytics
        // 3. Store view logs with timestamps

        // For now, return a portion of total views as monthly estimate
        $dom = Dom::find($domId);
        return (int) ($dom->broj_pregleda * 0.3); // Rough estimate
    }
}
