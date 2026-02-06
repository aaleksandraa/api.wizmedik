<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BanjaUpitRequest;
use App\Http\Requests\BanjaRecenzijaRequest;
use App\Models\Banja;
use App\Models\BanjaUpit;
use App\Models\BanjaRecenzija;
use App\Models\VrstaBanje;
use App\Models\Indikacija;
use App\Models\Terapija;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BanjaController extends Controller
{
    /**
     * Display a listing of banje with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Banja::with(['vrste', 'indikacije', 'terapije'])
                ->aktivan()
                ->verifikovan();

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by grad
            if ($request->filled('grad')) {
                $query->poGradu($request->grad);
            }

            // Filter by regija
            if ($request->filled('regija')) {
                $query->poRegiji($request->regija);
            }

            // Filter by vrste
            if ($request->filled('vrste')) {
                $vrste = is_array($request->vrste) ? $request->vrste : explode(',', $request->vrste);
                $query->whereHas('vrste', function ($q) use ($vrste) {
                    $q->whereIn('vrste_banja.id', $vrste);
                });
            }

            // Filter by indikacije
            if ($request->filled('indikacije')) {
                $indikacije = is_array($request->indikacije) ? $request->indikacije : explode(',', $request->indikacije);
                $query->whereHas('indikacije', function ($q) use ($indikacije) {
                    $q->whereIn('indikacije.id', $indikacije);
                });
            }

            // Filter by terapije
            if ($request->filled('terapije')) {
                $terapije = is_array($request->terapije) ? $request->terapije : explode(',', $request->terapije);
                $query->whereHas('terapije', function ($q) use ($terapije) {
                    $q->whereIn('terapije.id', $terapije);
                });
            }

            // Filter by medicinski_nadzor
            if ($request->filled('medicinski_nadzor')) {
                $query->where('medicinski_nadzor', $request->boolean('medicinski_nadzor'));
            }

            // Filter by fizijatar_prisutan
            if ($request->filled('fizijatar_prisutan')) {
                $query->where('fizijatar_prisutan', $request->boolean('fizijatar_prisutan'));
            }

            // Filter by ima_smjestaj
            if ($request->filled('ima_smjestaj')) {
                $query->where('ima_smjestaj', $request->boolean('ima_smjestaj'));
            }

            // Filter by online_rezervacija
            if ($request->filled('online_rezervacija')) {
                $query->where('online_rezervacija', $request->boolean('online_rezervacija'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'naziv');
            $sortOrder = $request->get('sort_order', 'asc');

            switch ($sortBy) {
                case 'ocjena':
                    $query->orderBy('prosjecna_ocjena', $sortOrder);
                    break;
                case 'pregledi':
                    $query->orderBy('broj_pregleda', $sortOrder);
                    break;
                case 'recenzije':
                    $query->orderBy('broj_recenzija', $sortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortOrder);
                    break;
                default:
                    $query->orderBy('naziv', $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $banje = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $banje->items(),
                'pagination' => [
                    'current_page' => $banje->currentPage(),
                    'last_page' => $banje->lastPage(),
                    'per_page' => $banje->perPage(),
                    'total' => $banje->total(),
                    'from' => $banje->firstItem(),
                    'to' => $banje->lastItem(),
                ],
                'filters' => [
                    'total_count' => $banje->total(),
                    'applied_filters' => $request->only(['search', 'grad', 'regija', 'vrste', 'indikacije', 'terapije', 'medicinski_nadzor', 'fizijatar_prisutan', 'ima_smjestaj', 'online_rezervacija'])
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching banje: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju banja'
            ], 500);
        }
    }

    /**
     * Display the specified banja
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $banja = Banja::with([
                'vrste',
                'indikacije' => function ($query) {
                    $query->orderBy('banja_indikacije.prioritet');
                },
                'terapije',
                'customTerapije',
                'paketi' => function ($query) {
                    $query->aktivan()->ordered();
                },
                'odobreneRecenzije' => function ($query) {
                    $query->with('user')->latest()->limit(10);
                }
            ])
            ->where('slug', $slug)
            ->aktivan()
            ->first();

            if (!$banja) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banja nije pronađena'
                ], 404);
            }

            // Increment view count
            $banja->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $banja
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching banja: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju banje'
            ], 500);
        }
    }

    /**
     * Send inquiry to banja
     */
    public function posaljiUpit(BanjaUpitRequest $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);

            if (!$banja->aktivan || !$banja->online_upit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banja trenutno ne prima upite'
                ], 400);
            }

            $upit = BanjaUpit::create([
                'banja_id' => $banja->id,
                'user_id' => $request->user_id,
                'ime' => $request->ime,
                'email' => $request->email,
                'telefon' => $request->telefon,
                'poruka' => $request->poruka,
                'datum_dolaska' => $request->datum_dolaska,
                'broj_osoba' => $request->broj_osoba,
                'tip' => $request->tip,
                'ip_adresa' => $request->ip_adresa,
            ]);

            // Send email notification to banja
            if ($banja->email) {
                try {
                    \Mail::to($banja->email)->send(new \App\Mail\BanjaUpitMail($upit));
                } catch (\Exception $mailError) {
                    \Log::error('Error sending banja inquiry email: ' . $mailError->getMessage());
                    // Don't fail the request if email fails
                }
            }

            // Send email notification to admin
            // TODO: Implement admin notification
            // Mail::to(config('mail.admin_email'))->send(new AdminBanjaUpitMail($upit));

            return response()->json([
                'success' => true,
                'message' => 'Upit je uspješno poslan. Kontaktiraće vas u najkraćem roku.',
                'data' => [
                    'id' => $upit->id,
                    'tip' => $upit->tip_label,
                    'status' => $upit->status_label
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending banja inquiry: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri slanju upita'
            ], 500);
        }
    }

    /**
     * Add review to banja
     */
    public function dodajRecenziju(BanjaRecenzijaRequest $request, int $id): JsonResponse
    {
        try {
            $banja = Banja::findOrFail($id);

            if (!$banja->aktivan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banja trenutno ne prima recenzije'
                ], 400);
            }

            // Check if user already reviewed this banja
            if (auth()->check()) {
                $existingReview = BanjaRecenzija::where('banja_id', $banja->id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($existingReview) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Već ste ostavili recenziju za ovu banju'
                    ], 400);
                }
            }

            $recenzija = BanjaRecenzija::create([
                'banja_id' => $banja->id,
                'user_id' => $request->user_id,
                'ime' => $request->ime,
                'ocjena' => $request->ocjena,
                'komentar' => $request->komentar,
                'verifikovano' => $request->verifikovano,
                'odobreno' => false, // Admin approval required
                'ip_adresa' => $request->ip_adresa,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recenzija je poslana na odobravanje. Hvala vam!',
                'data' => [
                    'id' => $recenzija->id,
                    'ocjena' => $recenzija->ocjena,
                    'status' => 'Na odobravanje'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error adding banja review: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dodavanju recenzije'
            ], 500);
        }
    }

    /**
     * Get filter options
     */
    public function filterOptions(): JsonResponse
    {
        try {
            $data = [
                'gradovi' => Banja::aktivan()->verifikovan()
                    ->select('grad')
                    ->distinct()
                    ->orderBy('grad')
                    ->pluck('grad'),

                'regije' => Banja::aktivan()->verifikovan()
                    ->select('regija')
                    ->whereNotNull('regija')
                    ->distinct()
                    ->orderBy('regija')
                    ->pluck('regija'),

                'vrste' => VrstaBanje::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'ikona']),

                'indikacije' => Indikacija::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'kategorija']),

                'terapije' => Terapija::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'kategorija']),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching filter options: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju opcija filtera'
            ], 500);
        }
    }

    /**
     * Get banje by grad (for city pages)
     */
    public function poGradu(string $grad): JsonResponse
    {
        try {
            $banje = Banja::with(['vrste', 'indikacije', 'terapije'])
                ->aktivan()
                ->verifikovan()
                ->poGradu($grad)
                ->orderBy('prosjecna_ocjena', 'desc')
                ->orderBy('broj_recenzija', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $banje,
                'meta' => [
                    'grad' => $grad,
                    'count' => $banje->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching banje by grad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju banja'
            ], 500);
        }
    }
}
