<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBanjaRequest;
use App\Models\Banja;
use App\Models\BanjaUpit;
use App\Models\BanjaRecenzija;
use App\Models\BanjaCustomTerapija;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BanjaDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:spa_manager']);
    }

    /**
     * Get spa manager's banje
     */
    public function index(): JsonResponse
    {
        try {
            $banje = Banja::with(['vrste', 'indikacije', 'terapije'])
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $banje
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error fetching banje: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju banja'
            ], 500);
        }
    }

    /**
     * Get specific banja details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $banja = Banja::with([
                'vrste',
                'indikacije',
                'terapije',
                'customTerapije',
                'paketi',
                'odobreneRecenzije' => function ($query) {
                    $query->with('user')->latest()->limit(10);
                }
            ])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error fetching banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Banja nije pronađena'
            ], 404);
        }
    }

    /**
     * Update banja
     */
    public function update(UpdateBanjaRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $banja = Banja::where('user_id', auth()->id())->findOrFail($id);

            // Remove admin-only fields for spa managers
            $data = $request->validated();
            unset($data['verifikovan'], $data['aktivan']);

            $banja->update($data);

            // Update relationships
            if ($request->filled('vrste')) {
                $banja->vrste()->sync($request->vrste);
            }

            if ($request->filled('indikacije')) {
                $indikacije = [];
                foreach ($request->indikacije as $index => $indikacijaId) {
                    $indikacije[$indikacijaId] = ['prioritet' => $index + 1];
                }
                $banja->indikacije()->sync($indikacije);
            }

            if ($request->filled('terapije')) {
                $banja->terapije()->sync($request->terapije);
            }

            DB::commit();

            $banja->load(['vrste', 'indikacije', 'terapije']);

            return response()->json([
                'success' => true,
                'message' => 'Banja je uspješno ažurirana',
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Dashboard error updating banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju banje'
            ], 500);
        }
    }

    /**
     * Get banja statistics for dashboard
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($id);

            $stats = [
                'total_views' => $banja->broj_pregleda,
                'average_rating' => round($banja->prosjecna_ocjena, 2),
                'total_reviews' => $banja->broj_recenzija,
                'total_inquiries' => $banja->upiti()->count(),
                'pending_inquiries' => $banja->upiti()->neobradjeni()->count(),
                'this_month_inquiries' => $banja->upiti()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'this_month_views' => $banja->broj_pregleda, // TODO: Implement monthly tracking
            ];

            // Recent inquiries
            $recentInquiries = $banja->upiti()
                ->latest()
                ->limit(5)
                ->get();

            // Recent reviews
            $recentReviews = $banja->odobreneRecenzije()
                ->with('user')
                ->latest()
                ->limit(5)
                ->get();

            // Monthly stats (last 6 months)
            $monthlyStats = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyStats[] = [
                    'month' => $date->format('M Y'),
                    'inquiries' => $banja->upiti()
                        ->whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->count(),
                    'reviews' => $banja->recenzije()
                        ->where('odobreno', true)
                        ->whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'recent_inquiries' => $recentInquiries,
                    'recent_reviews' => $recentReviews,
                    'monthly_stats' => $monthlyStats
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error fetching statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju statistika'
            ], 500);
        }
    }

    /**
     * Get inquiries for banja
     */
    public function inquiries(Request $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($id);

            $query = $banja->upiti();

            // Filter by status
            if ($request->filled('status')) {
                $query->poStatusu($request->status);
            }

            // Filter by type
            if ($request->filled('tip')) {
                $query->poTipu($request->tip);
            }

            // Search
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('ime', 'ILIKE', "%{$request->search}%")
                      ->orWhere('email', 'ILIKE', "%{$request->search}%")
                      ->orWhere('poruka', 'ILIKE', "%{$request->search}%");
                });
            }

            $upiti = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

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
            \Log::error('Dashboard error fetching inquiries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju upita'
            ], 500);
        }
    }

    /**
     * Update inquiry status
     */
    public function updateInquiryStatus(Request $request, int $banjaId, int $upitId): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($banjaId);
            $upit = $banja->upiti()->findOrFail($upitId);

            $request->validate([
                'status' => 'required|in:novi,procitan,odgovoren,zatvoren'
            ]);

            $upit->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Status upita je ažuriran',
                'data' => $upit
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error updating inquiry status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju statusa upita'
            ], 500);
        }
    }

    /**
     * Get reviews for banja
     */
    public function reviews(Request $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($id);

            $query = $banja->recenzije()->with('user');

            // Filter by approval status
            if ($request->filled('odobreno')) {
                $query->where('odobreno', $request->boolean('odobreno'));
            }

            // Filter by rating
            if ($request->filled('ocjena')) {
                $query->poOcjeni($request->ocjena);
            }

            $recenzije = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

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
            \Log::error('Dashboard error fetching reviews: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju recenzija'
            ], 500);
        }
    }

    /**
     * Add custom therapy
     */
    public function addCustomTherapy(Request $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($id);

            $request->validate([
                'naziv' => 'required|string|max:200',
                'opis' => 'nullable|string|max:1000',
                'cijena' => 'nullable|numeric|min:0',
                'trajanje_minuta' => 'nullable|integer|min:1|max:480',
                'redoslijed' => 'nullable|integer|min:0'
            ]);

            $customTerapija = BanjaCustomTerapija::create([
                'banja_id' => $banja->id,
                'naziv' => $request->naziv,
                'opis' => $request->opis,
                'cijena' => $request->cijena,
                'trajanje_minuta' => $request->trajanje_minuta,
                'redoslijed' => $request->redoslijed ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prilagođena terapija je dodana',
                'data' => $customTerapija
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error adding custom therapy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dodavanju prilagođene terapije'
            ], 500);
        }
    }

    /**
     * Update custom therapy
     */
    public function updateCustomTherapy(Request $request, int $banjaId, int $terapijaId): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($banjaId);
            $customTerapija = $banja->customTerapije()->findOrFail($terapijaId);

            $request->validate([
                'naziv' => 'required|string|max:200',
                'opis' => 'nullable|string|max:1000',
                'cijena' => 'nullable|numeric|min:0',
                'trajanje_minuta' => 'nullable|integer|min:1|max:480',
                'redoslijed' => 'nullable|integer|min:0'
            ]);

            $customTerapija->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Prilagođena terapija je ažurirana',
                'data' => $customTerapija
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error updating custom therapy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju prilagođene terapije'
            ], 500);
        }
    }

    /**
     * Delete custom therapy
     */
    public function deleteCustomTherapy(int $banjaId, int $terapijaId): JsonResponse
    {
        try {
            $banja = Banja::where('user_id', auth()->id())->findOrFail($banjaId);
            $customTerapija = $banja->customTerapije()->findOrFail($terapijaId);

            $naziv = $customTerapija->naziv;
            $customTerapija->delete();

            return response()->json([
                'success' => true,
                'message' => "Prilagođena terapija '{$naziv}' je obrisana"
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard error deleting custom therapy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri brisanju prilagođene terapije'
            ], 500);
        }
    }
}
