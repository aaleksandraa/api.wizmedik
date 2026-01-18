<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomRequest;
use App\Http\Requests\UpdateDomRequest;
use App\Models\Dom;
use App\Models\DomUpit;
use App\Models\DomRecenzija;
use App\Models\TipDoma;
use App\Models\NivoNjege;
use App\Models\ProgramNjege;
use App\Models\MedicinskUsluga;
use App\Models\SmjestajUslov;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDomController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * Display a listing of all domovi for admin
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Dom::with(['tipDoma', 'nivoNjege', 'programiNjege', 'medicinskUsluge', 'user'])
                ->withTrashed();

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->where('aktivan', true)->whereNull('deleted_at');
                        break;
                    case 'inactive':
                        $query->where('aktivan', false)->whereNull('deleted_at');
                        break;
                    case 'deleted':
                        $query->onlyTrashed();
                        break;
                    case 'unverified':
                        $query->where('verifikovan', false)->whereNull('deleted_at');
                        break;
                    case 'verified':
                        $query->where('verifikovan', true)->whereNull('deleted_at');
                        break;
                }
            }

            // Filter by grad
            if ($request->filled('grad')) {
                $query->poGradu($request->grad);
            }

            // Filter by tip doma
            if ($request->filled('tip_doma')) {
                $query->poTipu($request->tip_doma);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'naziv':
                    $query->orderBy('naziv', $sortOrder);
                    break;
                case 'grad':
                    $query->orderBy('grad', $sortOrder);
                    break;
                case 'ocjena':
                    $query->orderBy('prosjecna_ocjena', $sortOrder);
                    break;
                case 'pregledi':
                    $query->orderBy('broj_pregleda', $sortOrder);
                    break;
                case 'updated_at':
                    $query->orderBy('updated_at', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $domovi = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $domovi->items(),
                'pagination' => [
                    'current_page' => $domovi->currentPage(),
                    'last_page' => $domovi->lastPage(),
                    'per_page' => $domovi->perPage(),
                    'total' => $domovi->total(),
                    'from' => $domovi->firstItem(),
                    'to' => $domovi->lastItem(),
                ],
                'stats' => [
                    'total' => Dom::count(),
                    'active' => Dom::aktivan()->count(),
                    'inactive' => Dom::where('aktivan', false)->count(),
                    'verified' => Dom::verifikovan()->count(),
                    'unverified' => Dom::where('verifikovan', false)->count(),
                    'deleted' => Dom::onlyTrashed()->count(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching admin domovi: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju domova'
            ], 500);
        }
    }

    /**
     * Store a newly created dom
     */
    public function store(StoreDomRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dom = Dom::create($request->validated());

            // Attach programs
            if ($request->filled('programi_njege')) {
                $programs = [];
                foreach ($request->programi_njege as $index => $programId) {
                    $programs[$programId] = ['prioritet' => $index + 1];
                }
                $dom->programiNjege()->attach($programs);
            }

            // Attach medical services
            if ($request->filled('medicinske_usluge')) {
                $dom->medicinskUsluge()->attach($request->medicinske_usluge);
            }

            // Attach accommodation conditions
            if ($request->filled('smjestaj_uslovi')) {
                $dom->smjestajUslovi()->attach($request->smjestaj_uslovi);
            }

            DB::commit();

            $dom->load(['tipDoma', 'nivoNjege', 'programiNjege', 'medicinskUsluge', 'smjestajUslovi']);

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno kreiran',
                'data' => $dom
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri kreiranju doma'
            ], 500);
        }
    }

    /**
     * Display the specified dom
     */
    public function show(int $id): JsonResponse
    {
        try {
            $dom = Dom::with([
                'tipDoma',
                'nivoNjege',
                'programiNjege',
                'medicinskUsluge',
                'smjestajUslovi',
                'recenzije' => function ($query) {
                    $query->with('user')->latest();
                },
                'upiti' => function ($query) {
                    $query->latest()->limit(10);
                },
                'auditLog' => function ($query) {
                    $query->with('user')->latest()->limit(20);
                }
            ])
            ->withTrashed()
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $dom
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Dom nije pronađen'
            ], 404);
        }
    }

    /**
     * Update the specified dom
     */
    public function update(UpdateDomRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dom = Dom::withTrashed()->findOrFail($id);
            $dom->update($request->validated());

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
            \Log::error('Error updating dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri ažuriranju doma'
            ], 500);
        }
    }

    /**
     * Remove the specified dom from storage
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);
            $dom->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno obrisan'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri brisanju doma'
            ], 500);
        }
    }

    /**
     * Restore the specified dom
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $dom = Dom::onlyTrashed()->findOrFail($id);
            $dom->restore();

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno vraćen'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error restoring dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri vraćanju doma'
            ], 500);
        }
    }

    /**
     * Permanently delete the specified dom
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $dom = Dom::onlyTrashed()->findOrFail($id);
            $dom->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Dom je trajno obrisan'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error force deleting dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri trajnom brisanju doma'
            ], 500);
        }
    }

    /**
     * Verify dom
     */
    public function verify(int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);
            $dom->update(['verifikovan' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno verifikovan'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error verifying dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri verifikaciji doma'
            ], 500);
        }
    }

    /**
     * Unverify dom
     */
    public function unverify(int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);
            $dom->update(['verifikovan' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Verifikacija doma je uklonjena'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error unverifying dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri uklanjanju verifikacije'
            ], 500);
        }
    }

    /**
     * Activate dom
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);
            $dom->update(['aktivan' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno aktiviran'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error activating dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri aktivaciji doma'
            ], 500);
        }
    }

    /**
     * Deactivate dom
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);
            $dom->update(['aktivan' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Dom je uspješno deaktiviran'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deactivating dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri deaktivaciji doma'
            ], 500);
        }
    }

    /**
     * Get inquiries for admin
     */
    public function upiti(Request $request): JsonResponse
    {
        try {
            $query = DomUpit::with(['dom', 'user'])
                ->latest();

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by dom
            if ($request->filled('dom_id')) {
                $query->where('dom_id', $request->dom_id);
            }

            // Search
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('ime', 'ILIKE', "%{$request->search}%")
                      ->orWhere('email', 'ILIKE', "%{$request->search}%")
                      ->orWhere('poruka', 'ILIKE', "%{$request->search}%");
                });
            }

            $perPage = min($request->get('per_page', 20), 100);
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
            \Log::error('Error fetching admin upiti: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju upita'
            ], 500);
        }
    }

    /**
     * Get reviews for admin
     */
    public function recenzije(Request $request): JsonResponse
    {
        try {
            $query = DomRecenzija::with(['dom', 'user'])
                ->latest();

            // Filter by approval status
            if ($request->filled('odobreno')) {
                $query->where('odobreno', $request->boolean('odobreno'));
            }

            // Filter by dom
            if ($request->filled('dom_id')) {
                $query->where('dom_id', $request->dom_id);
            }

            // Search
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('ime', 'ILIKE', "%{$request->search}%")
                      ->orWhere('komentar', 'ILIKE', "%{$request->search}%");
                });
            }

            $perPage = min($request->get('per_page', 20), 100);
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
            \Log::error('Error fetching admin recenzije: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju recenzija'
            ], 500);
        }
    }

    /**
     * Approve review
     */
    public function odobriRecenziju(int $id): JsonResponse
    {
        try {
            $recenzija = DomRecenzija::findOrFail($id);
            $recenzija->update(['odobreno' => true]);

            // Update dom rating
            $recenzija->dom->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'Recenzija je odobrena'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error approving review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri odobravanju recenzije'
            ], 500);
        }
    }

    /**
     * Reject review
     */
    public function odbijRecenziju(int $id): JsonResponse
    {
        try {
            $recenzija = DomRecenzija::findOrFail($id);
            $recenzija->update(['odobreno' => false]);

            // Update dom rating
            $recenzija->dom->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'Recenzija je odbijena'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error rejecting review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri odbijanju recenzije'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'domovi' => [
                    'total' => Dom::count(),
                    'active' => Dom::aktivan()->count(),
                    'inactive' => Dom::where('aktivan', false)->count(),
                    'verified' => Dom::verifikovan()->count(),
                    'unverified' => Dom::where('verifikovan', false)->count(),
                    'deleted' => Dom::onlyTrashed()->count(),
                ],
                'upiti' => [
                    'total' => DomUpit::count(),
                    'novi' => DomUpit::where('status', 'novi')->count(),
                    'procitani' => DomUpit::where('status', 'procitan')->count(),
                    'odgovoreni' => DomUpit::where('status', 'odgovoren')->count(),
                    'zatvoreni' => DomUpit::where('status', 'zatvoren')->count(),
                ],
                'recenzije' => [
                    'total' => DomRecenzija::count(),
                    'odobrene' => DomRecenzija::where('odobreno', true)->count(),
                    'na_cekanju' => DomRecenzija::where('odobreno', false)->count(),
                ],
                'recent_activity' => [
                    'novi_domovi' => Dom::where('created_at', '>=', now()->subDays(7))->count(),
                    'novi_upiti' => DomUpit::where('created_at', '>=', now()->subDays(7))->count(),
                    'nove_recenzije' => DomRecenzija::where('created_at', '>=', now()->subDays(7))->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching admin stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju statistika'
            ], 500);
        }
    }
}
