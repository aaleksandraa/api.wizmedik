<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klinika;
use App\Models\Specijalnost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 30), 1000);

        $query = Klinika::active()
            ->verifikovan()
            ->select(
                'id',
                'naziv',
                'slug',
                'opis',
                'grad',
                'adresa',
                'telefon',
                'email',
                'website',
                'ocjena',
                'broj_ocjena',
                'slike',
                'radno_vrijeme',
                'latitude',
                'longitude'
            )
            ->with([
                'doktori' => function ($doctorQuery) {
                    $doctorQuery->select(
                        'id',
                        'ime',
                        'prezime',
                        'slug',
                        'specijalnost',
                        'specijalnost_id',
                        'ocjena',
                        'slika_profila',
                        'klinika_id'
                    )
                        ->aktivan()
                        ->verifikovan();
                },
                'specijalnosti' => function ($specialtyQuery) {
                    $specialtyQuery->select(
                        'specijalnosti.id',
                        'specijalnosti.naziv',
                        'specijalnosti.slug',
                        'specijalnosti.parent_id'
                    );
                },
            ]);

        if ($request->filled('grad')) {
            $cityValue = trim((string) $request->grad);
            $normalizedCity = mb_strtolower(str_replace('-', ' ', $cityValue));
            $query->whereRaw('LOWER(grad) = ?', [$normalizedCity]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('naziv', 'ilike', '%' . $search . '%')
                    ->orWhere('opis', 'ilike', '%' . $search . '%')
                    ->orWhere('adresa', 'ilike', '%' . $search . '%');
            });
        }

        if ($request->filled('specijalnost')) {
            $specialtyIds = $this->resolveSpecialtyIds((string) $request->specijalnost);
            $specialtyNames = Specijalnost::query()
                ->whereIn('id', $specialtyIds)
                ->pluck('naziv')
                ->map(fn ($name) => mb_strtolower((string) $name))
                ->values()
                ->all();

            if ($specialtyIds !== [] || $specialtyNames !== []) {
                $query->where(function ($builder) use ($specialtyIds, $specialtyNames) {
                    if ($specialtyIds !== []) {
                        $builder->whereHas('specijalnosti', function ($specialtyQuery) use ($specialtyIds) {
                            $specialtyQuery->whereIn('specijalnosti.id', $specialtyIds);
                        });
                    }

                    $builder->orWhereHas('doktori', function ($doctorQuery) use ($specialtyIds, $specialtyNames) {
                        $doctorQuery->aktivan()
                            ->verifikovan()
                            ->where(function ($matchQuery) use ($specialtyIds, $specialtyNames) {
                                if ($specialtyIds !== []) {
                                    $matchQuery->whereIn('specijalnost_id', $specialtyIds);
                                }

                                if ($specialtyNames !== []) {
                                    $method = $specialtyIds !== [] ? 'orWhereIn' : 'whereIn';
                                    $matchQuery->{$method}(DB::raw('LOWER(specijalnost)'), $specialtyNames);
                                }
                            });
                    });
                });
            }
        }

        if ($request->has('limit')) {
            $limit = min((int) $request->get('limit'), 1000);

            return response()->json($query->limit($limit)->get());
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($slug)
    {
        $clinic = Klinika::where('slug', $slug)
            ->with([
                'doktori' => function ($doctorQuery) {
                    $doctorQuery->aktivan()->verifikovan();
                },
                'specijalnosti' => function ($specialtyQuery) {
                    $specialtyQuery->select('specijalnosti.id', 'specijalnosti.naziv', 'specijalnosti.slug');
                },
            ])
            ->first();

        if (!$clinic) {
            return response()->json([
                'message' => 'Klinika nije pronaÄ‘ena',
                'slug' => $slug,
            ], 404);
        }

        if (!$clinic->aktivan || !$clinic->verifikovan) {
            return response()->json([
                'message' => 'Klinika trenutno nije dostupna',
                'slug' => $slug,
                'aktivan' => $clinic->aktivan,
                'verifikovan' => $clinic->verifikovan,
            ], 404);
        }

        return response()->json($clinic);
    }

    private function resolveSpecialtyIds(string $value): array
    {
        $normalizedValue = trim($value);
        if ($normalizedValue === '') {
            return [];
        }

        $decodedName = str_replace('-', ' ', urldecode($normalizedValue));

        $baseSpecialty = Specijalnost::query()
            ->where('aktivan', true)
            ->where(function ($query) use ($normalizedValue, $decodedName) {
                if (is_numeric($normalizedValue)) {
                    $query->orWhere('id', (int) $normalizedValue);
                }

                $query->orWhere('slug', $normalizedValue)
                    ->orWhereRaw('LOWER(naziv) = ?', [mb_strtolower($decodedName)]);
            })
            ->first();

        if (!$baseSpecialty) {
            return [];
        }

        $childIds = Specijalnost::query()
            ->where('parent_id', $baseSpecialty->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge([(int) $baseSpecialty->id], $childIds)));
    }
}
