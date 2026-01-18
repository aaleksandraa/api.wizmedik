<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomUpitRequest;
use App\Http\Requests\DomRecenzijaRequest;
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

class DomController extends Controller
{
    /**
     * Display a listing of domovi with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Dom::with(['tipDoma', 'nivoNjege', 'programiNjege', 'medicinskUsluge', 'smjestajUslovi'])
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

            // Filter by tip doma (accepts ID or slug)
            if ($request->filled('tip_doma')) {
                $tipovi = is_array($request->tip_doma) ? $request->tip_doma : explode(',', $request->tip_doma);
                // Check if values are slugs or IDs
                if (!is_numeric($tipovi[0])) {
                    $tipIds = TipDoma::whereIn('slug', $tipovi)->pluck('id')->toArray();
                    $query->whereIn('tip_doma_id', $tipIds);
                } else {
                    $query->whereIn('tip_doma_id', $tipovi);
                }
            }

            // Filter by nivo njege (accepts ID or slug)
            if ($request->filled('nivo_njege')) {
                $nivoi = is_array($request->nivo_njege) ? $request->nivo_njege : explode(',', $request->nivo_njege);
                // Check if values are slugs or IDs
                if (!is_numeric($nivoi[0])) {
                    $nivoIds = NivoNjege::whereIn('slug', $nivoi)->pluck('id')->toArray();
                    $query->whereIn('nivo_njege_id', $nivoIds);
                } else {
                    $query->whereIn('nivo_njege_id', $nivoi);
                }
            }

            // Filter by programi njege (accepts ID or slug)
            if ($request->filled('programi_njege')) {
                $programi = is_array($request->programi_njege) ? $request->programi_njege : explode(',', $request->programi_njege);
                $query->whereHas('programiNjege', function ($q) use ($programi) {
                    // Check if values are slugs or IDs
                    if (!is_numeric($programi[0])) {
                        $q->whereIn('programi_njege.slug', $programi);
                    } else {
                        $q->whereIn('programi_njege.id', $programi);
                    }
                });
            }

            // Filter by medicinske usluge
            if ($request->filled('medicinske_usluge')) {
                $usluge = is_array($request->medicinske_usluge) ? $request->medicinske_usluge : explode(',', $request->medicinske_usluge);
                $query->whereHas('medicinskUsluge', function ($q) use ($usluge) {
                    $q->whereIn('medicinske_usluge.id', $usluge);
                });
            }

            // Filter by smjestaj uslovi
            if ($request->filled('smjestaj_uslovi')) {
                $uslovi = is_array($request->smjestaj_uslovi) ? $request->smjestaj_uslovi : explode(',', $request->smjestaj_uslovi);
                $query->whereHas('smjestajUslovi', function ($q) use ($uslovi) {
                    $q->whereIn('smjestaj_uslovi.id', $uslovi);
                });
            }

            // Filter by nurses availability
            if ($request->filled('nurses_availability')) {
                $query->where('nurses_availability', $request->nurses_availability);
            }

            // Filter by doctor availability
            if ($request->filled('doctor_availability')) {
                $query->where('doctor_availability', $request->doctor_availability);
            }

            // Filter by physiotherapist
            if ($request->filled('has_physiotherapist')) {
                $query->where('has_physiotherapist', $request->boolean('has_physiotherapist'));
            }

            // Filter by physiatrist
            if ($request->filled('has_physiatrist')) {
                $query->where('has_physiatrist', $request->boolean('has_physiatrist'));
            }

            // Filter by emergency protocol
            if ($request->filled('emergency_protocol')) {
                $query->where('emergency_protocol', $request->boolean('emergency_protocol'));
            }

            // Filter by pricing mode
            if ($request->filled('pricing_mode')) {
                $query->where('pricing_mode', $request->pricing_mode);
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
                'filters' => [
                    'total_count' => $domovi->total(),
                    'applied_filters' => $request->only([
                        'search', 'grad', 'regija', 'tip_doma', 'nivo_njege',
                        'programi_njege', 'medicinske_usluge', 'smjestaj_uslovi',
                        'nurses_availability', 'doctor_availability', 'has_physiotherapist',
                        'has_physiatrist', 'emergency_protocol', 'pricing_mode'
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching domovi: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju domova'
            ], 500);
        }
    }

    /**
     * Display the specified dom
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $dom = Dom::with([
                'tipDoma',
                'nivoNjege',
                'programiNjege' => function ($query) {
                    $query->orderBy('dom_programi_njege.prioritet');
                },
                'medicinskUsluge',
                'smjestajUslovi' => function ($query) {
                    $query->orderBy('kategorija')->orderBy('redoslijed');
                },
                'odobreneRecenzije' => function ($query) {
                    $query->with('user')->latest()->limit(10);
                }
            ])
            ->where('slug', $slug)
            ->aktivan()
            ->first();

            if (!$dom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dom nije pronađen'
                ], 404);
            }

            // Increment view count
            $dom->incrementViews();

            return response()->json([
                'success' => true,
                'data' => $dom
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching dom: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju doma'
            ], 500);
        }
    }

    /**
     * Send inquiry to dom
     */
    public function posaljiUpit(DomUpitRequest $request, int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);

            if (!$dom->aktivan || !$dom->online_upit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dom trenutno ne prima upite'
                ], 400);
            }

            $upit = DomUpit::create([
                'dom_id' => $dom->id,
                'user_id' => $request->user_id,
                'ime' => $request->ime,
                'email' => $request->email,
                'telefon' => $request->telefon,
                'poruka' => $request->poruka,
                'opis_potreba' => $request->opis_potreba,
                'zelja_posjeta' => $request->zelja_posjeta,
                'tip' => $request->tip,
                'ip_adresa' => $request->ip_adresa,
            ]);

            // Send email notification to dom
            if ($dom->email) {
                // TODO: Implement email notification
                // Mail::to($dom->email)->send(new DomUpitMail($upit));
            }

            // Send email notification to admin
            // TODO: Implement admin notification
            // Mail::to(config('mail.admin_email'))->send(new AdminDomUpitMail($upit));

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
            \Log::error('Error sending dom inquiry: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri slanju upita'
            ], 500);
        }
    }

    /**
     * Add review to dom
     */
    public function dodajRecenziju(DomRecenzijaRequest $request, int $id): JsonResponse
    {
        try {
            $dom = Dom::findOrFail($id);

            if (!$dom->aktivan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dom trenutno ne prima recenzije'
                ], 400);
            }

            // Check if user already reviewed this dom
            if (auth()->check()) {
                $existingReview = DomRecenzija::where('dom_id', $dom->id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($existingReview) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Već ste ostavili recenziju za ovaj dom'
                    ], 400);
                }
            }

            $recenzija = DomRecenzija::create([
                'dom_id' => $dom->id,
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
            \Log::error('Error adding dom review: ' . $e->getMessage());

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
                'gradovi' => Dom::aktivan()->verifikovan()
                    ->select('grad')
                    ->distinct()
                    ->orderBy('grad')
                    ->pluck('grad'),

                'regije' => Dom::aktivan()->verifikovan()
                    ->select('regija')
                    ->whereNotNull('regija')
                    ->distinct()
                    ->orderBy('regija')
                    ->pluck('regija'),

                'tipovi_domova' => TipDoma::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'opis']),

                'nivoi_njege' => NivoNjege::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'opis']),

                'programi_njege' => ProgramNjege::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'opis']),

                'medicinske_usluge' => MedicinskUsluga::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'opis']),

                'smjestaj_uslovi' => SmjestajUslov::aktivan()->ordered()->get(['id', 'naziv', 'slug', 'kategorija', 'opis']),
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
     * Get domovi by grad (for city pages)
     */
    public function poGradu(string $grad): JsonResponse
    {
        try {
            $domovi = Dom::with(['tipDoma', 'nivoNjege', 'programiNjege', 'medicinskUsluge'])
                ->aktivan()
                ->verifikovan()
                ->poGradu($grad)
                ->orderBy('prosjecna_ocjena', 'desc')
                ->orderBy('broj_recenzija', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $domovi,
                'meta' => [
                    'grad' => $grad,
                    'count' => $domovi->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching domovi by grad: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Greška pri dohvatanju domova'
            ], 500);
        }
    }
}
