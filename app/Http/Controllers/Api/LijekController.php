<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lijek;
use App\Support\LijekCacheVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LijekController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
            'atc_sifra' => 'nullable|string|max:64',
            'lista_id' => 'nullable|string|max:16',
            'cijena_min' => 'nullable|numeric|min:0',
            'cijena_max' => 'nullable|numeric|min:0|gte:cijena_min',
            'participacija_min' => 'nullable|numeric|min:0',
            'participacija_max' => 'nullable|numeric|min:0|gte:participacija_min',
            'procenat_participacije_min' => 'nullable|numeric|min:0|max:100',
            'procenat_participacije_max' => 'nullable|numeric|min:0|max:100|gte:procenat_participacije_min',
            'ima_indikacije' => 'nullable|boolean',
        ]);

        $perPage = $validated['per_page'] ?? 24;
        $page = (int) $request->query('page', 1);

        $cacheParams = $validated;
        $cacheParams['page'] = $page;
        ksort($cacheParams);
        $cacheKey = 'lijekovi:list:v' . LijekCacheVersion::current() . ':' . md5(json_encode($cacheParams));

        $lijekovi = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($validated, $perPage) {
            $query = Lijek::query()
                ->select([
                    'id',
                    'lijek_id',
                    'slug',
                    'naziv',
                    'naziv_lijeka',
                    'brend',
                    'proizvodjac',
                    'atc_sifra',
                    'inn',
                    'oblik',
                    'doza',
                    'pakovanje',
                    'aktuelna_cijena',
                    'aktuelni_procenat_participacije',
                    'aktuelni_iznos_participacije',
                    'aktuelna_lista_id',
                    'aktuelni_broj_indikacija',
                ]);

            if (!empty($validated['search'])) {
                $query->search($validated['search']);
            }

            if (!empty($validated['atc_sifra'])) {
                $query->where('atc_sifra', 'like', trim((string) $validated['atc_sifra']) . '%');
            }

            if (!empty($validated['lista_id'])) {
                $query->where('aktuelna_lista_id', trim((string) $validated['lista_id']));
            }

            if (isset($validated['cijena_min'])) {
                $query->where('aktuelna_cijena', '>=', $validated['cijena_min']);
            }

            if (isset($validated['cijena_max'])) {
                $query->where('aktuelna_cijena', '<=', $validated['cijena_max']);
            }

            if (isset($validated['participacija_min'])) {
                $query->where('aktuelni_iznos_participacije', '>=', $validated['participacija_min']);
            }

            if (isset($validated['participacija_max'])) {
                $query->where('aktuelni_iznos_participacije', '<=', $validated['participacija_max']);
            }

            if (isset($validated['procenat_participacije_min'])) {
                $query->where('aktuelni_procenat_participacije', '>=', $validated['procenat_participacije_min']);
            }

            if (isset($validated['procenat_participacije_max'])) {
                $query->where('aktuelni_procenat_participacije', '<=', $validated['procenat_participacije_max']);
            }

            if (array_key_exists('ima_indikacije', $validated)) {
                if ((bool) $validated['ima_indikacije']) {
                    $query->where('aktuelni_broj_indikacija', '>', 0);
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('aktuelni_broj_indikacija')
                            ->orWhere('aktuelni_broj_indikacija', 0);
                    });
                }
            }

            return $query
                ->orderByRaw("COALESCE(naziv, naziv_lijeka, '') ASC")
                ->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $lijekovi,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $cacheKey = 'lijekovi:show:v' . LijekCacheVersion::current() . ':' . $slug;
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($slug) {
            $lijek = Lijek::query()
                ->where('slug', $slug)
                ->first();

            if (!$lijek) {
                return null;
            }

            $currentRows = $this->fetchCurrentRows($lijek);
            $indikacije = $this->extractIndications($currentRows);

            $data = $lijek->toArray();
            $data['opis'] = $lijek->opis ?: $this->fallbackOpis($lijek);
            $data['lista_rfzo_pojasnjenje'] = $lijek->resolveListaPojasnjenje();
            $data['aktuelni_fond'] = [
                'verzija_od' => $lijek->aktuelna_verzija_od?->toDateString(),
                'verzija_do' => $lijek->aktuelna_verzija_do?->toDateString(),
                'lista_id' => $lijek->aktuelna_lista_id,
                'lista_pojasnjenje' => $lijek->resolveListaPojasnjenje(),
                'cijena' => $lijek->aktuelna_cijena,
                'procenat_participacije' => $lijek->aktuelni_procenat_participacije,
                'iznos_participacije' => $lijek->aktuelni_iznos_participacije,
                'indikacije' => $indikacije,
            ];

            return $data;
        });

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Lijek nije pronadjen.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function fetchCurrentRows(Lijek $lijek): Collection
    {
        $query = $lijek->fondZapisi();

        if ($lijek->aktuelna_verzija_od) {
            $query->whereDate('verzija_od', $lijek->aktuelna_verzija_od);
        } else {
            $query->whereNull('verzija_od');
        }

        if ($lijek->aktuelna_verzija_do) {
            $query->whereDate('verzija_do', $lijek->aktuelna_verzija_do);
        } else {
            $query->whereNull('verzija_do');
        }

        return $query->get();
    }

    private function extractIndications(Collection $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $oznaka = $row->indikacija_oznaka ? trim((string) $row->indikacija_oznaka) : null;
            $naziv = $row->indikacija_naziv ? trim((string) $row->indikacija_naziv) : null;

            if (!$oznaka && !$naziv) {
                continue;
            }

            $key = ($oznaka ?? '') . '|' . ($naziv ?? '');
            $map[$key] = [
                'oznaka' => $oznaka,
                'naziv' => $naziv,
            ];
        }

        $indikacije = array_values($map);

        usort($indikacije, function (array $a, array $b) {
            return strnatcasecmp((string) ($a['oznaka'] ?? ''), (string) ($b['oznaka'] ?? ''));
        });

        return $indikacije;
    }

    private function fallbackOpis(Lijek $lijek): string
    {
        $naziv = $lijek->naziv ?: $lijek->naziv_lijeka ?: 'Lijek';
        $oblik = $lijek->farmaceutski_oblik ?: $lijek->oblik ?: 'farmaceutski oblik nije unesen';
        $doza = $lijek->doza ?: $lijek->jacina ?: 'doza nije unesena';
        $pakovanje = $lijek->pakovanje ?: $lijek->pakovanje_registar ?: 'pakovanje nije uneseno';

        return "{$naziv} - {$oblik}, {$doza}, {$pakovanje}.";
    }
}
