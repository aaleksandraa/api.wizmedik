<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBanjaRequest;
use App\Http\Requests\UpdateBanjaRequest;
use App\Models\Banja;
use App\Models\BanjaUpit;
use App\Models\BanjaRecenzija;
use App\Models\BanjaAuditLog;
use App\Models\VrstaBanje;
use App\Models\Indikacija;
use App\Models\Terapija;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminBanjaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * Display a listing of all banje for admin
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Banja::with(['vrste', 'indikacije', 'terapije', 'user']);

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->where('aktivan', true);
                        break;
                    case 'inactive':
                        $query->where('aktivan', false);
                        break;
                    case 'verified':
                        $query->where('verifikovan', true);
                        break;
                    case 'unverified':
                        $query->where('verifikovan', false);
                        break;
                }
            }

            // Filter by grad
            if ($request->filled('grad')) {
                $query->poGradu($request->grad);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $banje = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $banje->items(),
                'pagination' => [
                    'current_page' => $banje->currentPage(),
                    'last_page' => $banje->lastPage(),
                    'per_page' => $banje->perPage(),
                    'total' => $banje->total(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error fetching banje: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju banja'
            ], 500);
        }
    }

    /**
     * Store a newly created banja
     */
    public function store(StoreBanjaRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $banja = Banja::create($request->validated());

            // Attach relationships
            if ($request->filled('vrste')) {
                $banja->vrste()->attach($request->vrste);
            }

            if ($request->filled('indikacije')) {
                $indikacije = [];
                foreach ($request->indikacije as $index => $indikacijaId) {
                    $indikacije[$indikacijaId] = ['prioritet' => $index + 1];
                }
                $banja->indikacije()->attach($indikacije);
            }

            if ($request->filled('terapije')) {
                $banja->terapije()->attach($request->terapije);
            }

            DB::commit();

            $banja->load(['vrste', 'indikacije', 'terapije']);

            return response()->json([
                'success' => true,
                'message' => 'Banja je uspješno kreirana',
                'data' => $banja
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Admin error creating banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri kreiranju banje'
            ], 500);
        }
    }

    /**
     * Display the specified banja
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
                'user',
                'recenzije' => function ($query) {
                    $query->with('user')->latest();
                },
                'upiti' => function ($query) {
                    $query->latest()->limit(10);
                },
                'auditLog' => function ($query) {
                    $query->with('user')->latest()->limit(20);
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error fetching banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Banja nije pronađena'
            ], 404);
        }
    }

    /**
     * Update the specified banja
     */
    public function update(UpdateBanjaRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $banja = Banja::findOrFail($id);
            $banja->update($request->validated());

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
            \Log::error('Admin error updating banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju banje'
            ], 500);
        }
    }

    /**
     * Remove the specified banja
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);
            $naziv = $banja->naziv;

            $banja->delete();

            return response()->json([
                'success' => true,
                'message' => "Banja '{$naziv}' je uspješno obrisana"
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error deleting banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri brisanju banje'
            ], 500);
        }
    }

    /**
     * Verify banja
     */
    public function verify(int $id): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);
            $banja->update(['verifikovan' => true]);

            $banja->logAudit('verify', ['verifikovan' => false], ['verifikovan' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Banja je uspješno verifikovana',
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error verifying banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri verifikaciji banje'
            ], 500);
        }
    }

    /**
     * Activate/Deactivate banja
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);
            $newStatus = !$banja->aktivan;

            $banja->update(['aktivan' => $newStatus]);

            $action = $newStatus ? 'activate' : 'deactivate';
            $banja->logAudit($action, ['aktivan' => !$newStatus], ['aktivan' => $newStatus]);

            $message = $newStatus ? 'aktivirana' : 'deaktivirana';

            return response()->json([
                'success' => true,
                'message' => "Banja je uspješno {$message}",
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error toggling banja status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri mijenjanju statusa banje'
            ], 500);
        }
    }

    /**
     * Get banja statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Banja::count(),
                'active' => Banja::where('aktivan', true)->count(),
                'verified' => Banja::where('verifikovan', true)->count(),
                'with_accommodation' => Banja::where('ima_smjestaj', true)->count(),
                'with_medical_supervision' => Banja::where('medicinski_nadzor', true)->count(),
                'with_online_booking' => Banja::where('online_rezervacija', true)->count(),
                'total_views' => Banja::sum('broj_pregleda'),
                'total_reviews' => BanjaRecenzija::count(),
                'pending_reviews' => BanjaRecenzija::where('odobreno', false)->count(),
                'total_inquiries' => BanjaUpit::count(),
                'pending_inquiries' => BanjaUpit::whereIn('status', ['novi', 'procitan'])->count(),
            ];

            // Top cities
            $topCities = Banja::select('grad', DB::raw('count(*) as count'))
                ->groupBy('grad')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // Recent activity
            $recentActivity = BanjaAuditLog::with(['banja', 'user'])
                ->latest()
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'top_cities' => $topCities,
                    'recent_activity' => $recentActivity
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error fetching banja statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju statistika'
            ], 500);
        }
    }

    /**
     * Get audit log for a specific banja
     */
    public function auditLog(int $id, Request $request): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);

            $query = $banja->auditLog()
                ->with('user')
                ->latest();

            if ($request->filled('akcija')) {
                $query->where('akcija', $request->akcija);
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin error fetching banja audit log: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri dohvatanju audit loga'
            ], 500);
        }
    }

    /**
     * Get all banja reviews for admin moderation
     */
    public function recenzije(Request $request): JsonResponse
    {
        try {
            $query = BanjaRecenzija::with([
                'banja:id,naziv,slug',
                'user:id,name,email'
            ])->latest();

            if ($request->filled('banja_id')) {
                $query->where('banja_id', (int) $request->banja_id);
            }

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
            \Log::error('Admin error fetching banja reviews: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri dohvatanju recenzija'
            ], 500);
        }
    }

    /**
     * Approve a banja review
     */
    public function odobriRecenziju(int $id): JsonResponse
    {
        try {
            $recenzija = BanjaRecenzija::findOrFail($id);
            $recenzija->approve();

            return response()->json([
                'success' => true,
                'message' => 'Recenzija je odobrena',
                'data' => $recenzija
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin error approving banja review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri odobravanju recenzije'
            ], 500);
        }
    }

    /**
     * Delete/reject a banja review
     */
    public function obrisiRecenziju(int $id): JsonResponse
    {
        try {
            $recenzija = BanjaRecenzija::findOrFail($id);
            $recenzija->delete();

            return response()->json([
                'success' => true,
                'message' => 'Recenzija je obrisana'
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin error deleting banja review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri brisanju recenzije'
            ], 500);
        }
    }

    /**
     * Get all banja inquiries for admin
     */
    public function upiti(Request $request): JsonResponse
    {
        try {
            $query = BanjaUpit::with([
                'banja:id,naziv,slug',
                'user:id,name,email'
            ])->latest();

            if ($request->filled('banja_id')) {
                $query->where('banja_id', (int) $request->banja_id);
            }

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
            \Log::error('Admin error fetching banja inquiries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'GreÅ¡ka pri dohvatanju upita'
            ], 500);
        }
    }

    /**
     * Get taxonomies for forms
     */
    public function taxonomies(): JsonResponse
    {
        try {
            $data = [
                'vrste' => VrstaBanje::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'ikona']),
                'indikacije' => Indikacija::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'kategorija']),
                'terapije' => Terapija::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'kategorija']),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin error fetching taxonomies: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju taksonomija'
            ], 500);
        }
    }
}
