<?php

namespace App\Services\HealthcareImport\Matchers;

use App\Models\Doktor;
use App\Services\HealthcareImport\Normalizers\DoctorNameNormalizer;

class DoctorMatcher
{
    public function __construct(
        private readonly DoctorNameNormalizer $names = new DoctorNameNormalizer(),
    ) {
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{model:?Doktor,candidates:list<Doktor>,reason:?string}
     */
    public function match(array $normalized): array
    {
        $matchKey = (string) ($normalized['match_key'] ?? '');
        $ime = (string) ($normalized['ime'] ?? '');
        $prezime = (string) ($normalized['prezime'] ?? '');
        if ($ime === '' || $prezime === '') {
            return ['model' => null, 'candidates' => [], 'reason' => 'name_unparsed'];
        }

        $query = Doktor::query()
            ->whereRaw('LOWER(ime) = ?', [mb_strtolower($ime)])
            ->whereRaw('LOWER(prezime) = ?', [mb_strtolower($prezime)]);

        $ranked = collect();

        $klinikaId = $normalized['klinika_id'] ?? null;
        if ($klinikaId) {
            $sameClinic = (clone $query)->where('klinika_id', $klinikaId)->limit(5)->get();
            if ($sameClinic->count() === 1) {
                return ['model' => $sameClinic->first(), 'candidates' => $sameClinic->all(), 'reason' => null];
            }
            $ranked = $ranked->concat($sameClinic);
        }

        $specialtyId = $normalized['specijalnost_id'] ?? null;
        $grad = $normalized['grad'] ?? null;
        if ($specialtyId && $grad) {
            $sameSpecCity = (clone $query)
                ->where('specijalnost_id', $specialtyId)
                ->whereRaw('LOWER(grad) = ?', [mb_strtolower((string) $grad)])
                ->limit(5)
                ->get();
            if ($sameSpecCity->count() === 1 && $ranked->isEmpty()) {
                return ['model' => $sameSpecCity->first(), 'candidates' => $sameSpecCity->all(), 'reason' => null];
            }
            $ranked = $ranked->concat($sameSpecCity);
        }

        $unique = $ranked->unique('id')->values();
        if ($unique->count() > 1) {
            return [
                'model' => null,
                'candidates' => $unique->all(),
                'reason' => 'multiple_doctor_candidates',
            ];
        }

        $byNameOnly = $query->limit(5)->get();
        if ($byNameOnly->count() > 1) {
            return [
                'model' => null,
                'candidates' => $byNameOnly->all(),
                'reason' => 'same_name_insufficient',
            ];
        }

        if ($matchKey !== '' && $byNameOnly->count() === 1) {
            $existing = $this->names->parse(trim($byNameOnly[0]->ime . ' ' . $byNameOnly[0]->prezime));
            if (($existing['match_key'] ?? null) === $matchKey && !$klinikaId && !$specialtyId) {
                return [
                    'model' => null,
                    'candidates' => $byNameOnly->all(),
                    'reason' => 'name_only_not_enough',
                ];
            }
        }

        return ['model' => null, 'candidates' => $byNameOnly->all(), 'reason' => $byNameOnly->isEmpty() ? null : 'name_only_not_enough'];
    }
}
