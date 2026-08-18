<?php

namespace App\Services\HealthcareImport\Matchers;

use App\Models\Klinika;
use App\Services\HealthcareImport\Normalizers\PhoneNormalizer;
use App\Services\HealthcareImport\Normalizers\UrlNormalizer;

class InstitutionMatcher
{
    public function __construct(
        private readonly PhoneNormalizer $phones = new PhoneNormalizer(),
        private readonly UrlNormalizer $urls = new UrlNormalizer(),
    ) {
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{model:?Klinika,candidates:list<Klinika>,reason:?string}
     */
    public function match(array $normalized): array
    {
        $candidates = collect();

        $placeId = $normalized['google_place_id'] ?? null;
        if (is_string($placeId) && $placeId !== '') {
            $byPlace = Klinika::query()
                ->where('google_maps_link', 'like', '%' . $placeId . '%')
                ->limit(5)
                ->get();
            $candidates = $candidates->concat($byPlace);
        }

        $naziv = mb_strtolower((string) ($normalized['naziv'] ?? ''));
        $grad = mb_strtolower((string) ($normalized['grad'] ?? ''));
        if ($naziv !== '' && $grad !== '') {
            $byName = Klinika::query()
                ->whereRaw('LOWER(naziv) = ?', [$naziv])
                ->whereRaw('LOWER(grad) = ?', [$grad])
                ->limit(5)
                ->get();
            $candidates = $candidates->concat($byName);
        }

        $phone = $this->phones->normalize($normalized['telefon'] ?? null);
        if ($phone && $grad !== '') {
            $byPhone = Klinika::query()
                ->whereRaw("regexp_replace(telefon, '[^0-9+]', '', 'g') = ?", [$phone])
                ->whereRaw('LOWER(grad) = ?', [$grad])
                ->limit(5)
                ->get();
            $candidates = $candidates->concat($byPhone);
        }

        $domain = $this->urls->domain($normalized['website'] ?? null);
        if ($domain && $grad !== '') {
            $byWeb = Klinika::query()
                ->whereRaw('LOWER(website) like ?', ['%' . $domain . '%'])
                ->whereRaw('LOWER(grad) = ?', [$grad])
                ->limit(5)
                ->get();
            $candidates = $candidates->concat($byWeb);
        }

        $unique = $candidates->unique('id')->values();
        if ($unique->count() === 1) {
            return ['model' => $unique->first(), 'candidates' => $unique->all(), 'reason' => null];
        }
        if ($unique->count() > 1) {
            return [
                'model' => null,
                'candidates' => $unique->all(),
                'reason' => 'multiple_institution_candidates',
            ];
        }

        return ['model' => null, 'candidates' => [], 'reason' => null];
    }
}
