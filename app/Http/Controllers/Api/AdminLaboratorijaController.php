<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laboratorija;
use App\Models\KategorijaAnalize;
use App\Models\Analiza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin Controller for Laboratory Management
 *
 * Handles all administrative operations for laboratories:
 * - CRUD operations for laboratories
 * - Verification and approval
 * - Category management
 * - Statistics and reporting
 */
class AdminLaboratorijaController extends Controller
{
    /**
     * Get all laboratories with filters and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Laboratorija::with(['grad', 'analize', 'paketi'])
                ->withCount(['analize', 'paketi']);

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('naziv', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%")
                      ->orWhere('telefon', 'LIKE', "%{$search}%");
                });
            }

            // Filter by city
            if ($request->has('grad')) {
                $query->where('grad', $request->grad);
            }

            // Filter by verification status
            if ($request->has('verified')) {
                $query->where('verified', $request->verified === 'true');
            }

            // Filter by active status
            if ($request->has('active')) {
                $query->where('active', $request->active === 'true');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 20);
            $laboratories = $query->paginate($perPage);

            return response()->json($laboratories);

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching laboratories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Greška prilikom učitavanja laboratorija'
            ], 500);
        }
    }

    /**
     * Get single laboratory details
     */
    public function show(int $id)
    {
        try {
            $laboratorija = Laboratorija::with([
                'grad',
                'analize.kategorija',
                'paketi.analize',
                'galerija',
                'radnoVrijeme',
                'user'
            ])->findOrFail($id);

            return response()->json($laboratorija);

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching laboratory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Laboratorija nije pronađena'
            ], 404);
        }
    }

    /**
     * Create new laboratory
     */
    public function store(Request $request)
    {
        $request->validate([
            'naziv' => 'required|string|max:255',
            'email' => 'required|email|unique:laboratorije,email',
            'telefon' => 'required|string|max:50',
            'adresa' => 'required|string|max:500',
            'grad' => 'required|string|max:100',
            'opis' => 'nullable|string',
            'featured_slika' => 'nullable|url',
            'profilna_slika' => 'nullable|url',
            'verified' => 'boolean',
            'active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $laboratorija = Laboratorija::create([
                'naziv' => $request->naziv,
                'slug' => Str::slug($request->naziv . '-' . Str::random(6)),
                'email' => $request->email,
                'telefon' => $request->telefon,
                'adresa' => $request->adresa,
                'grad' => $request->grad,
                'opis' => $request->opis,
                'featured_slika' => $request->featured_slika,
                'profilna_slika' => $request->profilna_slika,
                'verified' => $request->get('verified', false),
                'active' => $request->get('active', true),
                'ocjena' => 0,
                'broj_ocjena' => 0,
                'broj_pregleda' => 0,
            ]);

            DB::commit();

            Log::info('Admin: Laboratory created', [
                'id' => $laboratorija->id,
                'naziv' => $laboratorija->naziv,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Laboratorija je uspješno kreirana',
                'data' => $laboratorija
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin: Error creating laboratory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Greška prilikom kreiranja laboratorije'
            ], 500);
        }
    }

    /**
     * Update laboratory
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'naziv' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:laboratorije,email,' . $id,
            'telefon' => 'sometimes|required|string|max:50',
            'adresa' => 'sometimes|required|string|max:500',
            'grad' => 'sometimes|required|string|max:100',
            'opis' => 'nullable|string',
            'featured_slika' => 'nullable|url',
            'profilna_slika' => 'nullable|url',
            'verified' => 'boolean',
            'active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $laboratorija = Laboratorija::findOrFail($id);

            $laboratorija->update($request->only([
                'naziv', 'email', 'telefon', 'adresa', 'grad', 'opis',
                'featured_slika', 'profilna_slika', 'verified', 'active'
            ]));

            // Update slug if naziv changed
            if ($request->has('naziv') && $request->naziv !== $laboratorija->naziv) {
                $laboratorija->slug = Str::slug($request->naziv . '-' . $laboratorija->id);
                $laboratorija->save();
            }

            DB::commit();

            Log::info('Admin: Laboratory updated', [
                'id' => $laboratorija->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Laboratorija je uspješno ažurirana',
                'data' => $laboratorija
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin: Error updating laboratory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom ažuriranja laboratorije'
            ], 500);
        }
    }

    /**
     * Delete laboratory (soft delete)
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $laboratorija = Laboratorija::findOrFail($id);
            $laboratorija->delete();

            DB::commit();

            Log::info('Admin: Laboratory deleted', [
                'id' => $id,
                'naziv' => $laboratorija->naziv,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Laboratorija je uspješno obrisana'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin: Error deleting laboratory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom brisanja laboratorije'
            ], 500);
        }
    }

    /**
     * Verify laboratory
     */
    public function verify(int $id)
    {
        DB::beginTransaction();
        try {
            $laboratorija = Laboratorija::findOrFail($id);
            $laboratorija->update(['verified' => true]);

            DB::commit();

            Log::info('Admin: Laboratory verified', [
                'id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Laboratorija je uspješno verifikovana',
                'data' => $laboratorija
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin: Error verifying laboratory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom verifikacije laboratorije'
            ], 500);
        }
    }

    /**
     * Toggle laboratory active status
     */
    public function toggleActive(int $id)
    {
        DB::beginTransaction();
        try {
            $laboratorija = Laboratorija::findOrFail($id);
            $laboratorija->update(['active' => !$laboratorija->active]);

            DB::commit();

            Log::info('Admin: Laboratory active status toggled', [
                'id' => $id,
                'active' => $laboratorija->active,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => $laboratorija->active
                    ? 'Laboratorija je aktivirana'
                    : 'Laboratorija je deaktivirana',
                'data' => $laboratorija
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin: Error toggling laboratory status', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom promjene statusa'
            ], 500);
        }
    }

    /**
     * Get all analysis categories
     */
    public function getKategorije()
    {
        try {
            $kategorije = KategorijaAnalize::withCount('analize')
                ->orderBy('naziv')
                ->get();

            return response()->json($kategorije);

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching categories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom učitavanja kategorija'
            ], 500);
        }
    }

    /**
     * Create new analysis category
     */
    public function storeKategorija(Request $request)
    {
        $request->validate([
            'naziv' => 'required|string|max:255|unique:kategorije_analiza,naziv',
            'opis' => 'nullable|string',
            'ikona' => 'nullable|string|max:50',
        ]);

        try {
            $kategorija = KategorijaAnalize::create([
                'naziv' => $request->naziv,
                'slug' => Str::slug($request->naziv),
                'opis' => $request->opis,
                'ikona' => $request->ikona,
            ]);

            Log::info('Admin: Category created', [
                'id' => $kategorija->id,
                'naziv' => $kategorija->naziv,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Kategorija je uspješno kreirana',
                'data' => $kategorija
            ], 201);

        } catch (\Exception $e) {
            Log::error('Admin: Error creating category', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom kreiranja kategorije'
            ], 500);
        }
    }

    /**
     * Update analysis category
     */
    public function updateKategorija(Request $request, int $id)
    {
        $request->validate([
            'naziv' => 'sometimes|required|string|max:255|unique:kategorije_analiza,naziv,' . $id,
            'opis' => 'nullable|string',
            'ikona' => 'nullable|string|max:50',
        ]);

        try {
            $kategorija = KategorijaAnalize::findOrFail($id);
            $kategorija->update($request->only(['naziv', 'opis', 'ikona']));

            if ($request->has('naziv')) {
                $kategorija->slug = Str::slug($request->naziv);
                $kategorija->save();
            }

            Log::info('Admin: Category updated', [
                'id' => $kategorija->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Kategorija je uspješno ažurirana',
                'data' => $kategorija
            ]);

        } catch (\Exception $e) {
            Log::error('Admin: Error updating category', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom ažuriranja kategorije'
            ], 500);
        }
    }

    /**
     * Delete analysis category
     */
    public function destroyKategorija(int $id)
    {
        try {
            $kategorija = KategorijaAnalize::findOrFail($id);

            // Check if category has analyses
            if ($kategorija->analize()->count() > 0) {
                return response()->json([
                    'message' => 'Ne možete obrisati kategoriju koja ima analize'
                ], 400);
            }

            $kategorija->delete();

            Log::info('Admin: Category deleted', [
                'id' => $id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Kategorija je uspješno obrisana'
            ]);

        } catch (\Exception $e) {
            Log::error('Admin: Error deleting category', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom brisanja kategorije'
            ], 500);
        }
    }

    /**
     * Get laboratory statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total' => Laboratorija::count(),
                'verified' => Laboratorija::where('verified', true)->count(),
                'active' => Laboratorija::where('active', true)->count(),
                'total_analyses' => Analiza::count(),
                'total_categories' => KategorijaAnalize::count(),
                'by_city' => Laboratorija::select('grad', DB::raw('count(*) as count'))
                    ->groupBy('grad')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'recent' => Laboratorija::latest()
                    ->limit(5)
                    ->get(['id', 'naziv', 'grad', 'created_at', 'verified']),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Admin: Error fetching statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Greška prilikom učitavanja statistike'
            ], 500);
        }
    }
}
