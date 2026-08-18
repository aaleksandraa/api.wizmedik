<?php

namespace App\Services\HealthcareImport\Importers;

use App\Models\Klinika;
use App\Services\HealthcareImport\Matchers\InstitutionMatcher;
use App\Services\HealthcareImport\Normalizers\PhoneNormalizer;
use App\Services\HealthcareImport\Normalizers\UrlNormalizer;

class InstitutionImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $matcher = new InstitutionMatcher();
        $phones = new PhoneNormalizer();
        $urls = new UrlNormalizer();
        $clinicTypes = array_map('strval', config('healthcare-import.clinic_types', []));
        $skippedTypes = array_map('strval', config('healthcare-import.skipped_types', []));

        foreach ($this->context->workbook->sheet('01_INSTITUTIONS') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $externalId = $this->string($raw['Institution ID'] ?? null);
            $tip = mb_strtolower((string) ($raw['Tip'] ?? ''));
            $warnings = [];

            if (in_array($tip, $skippedTypes, true)) {
                $this->record('01_INSTITUTIONS', $row, 'skip', 'institution', $externalId, $raw, ['tip' => $tip], [], [], null, [], 'skipped_type');
                continue;
            }

            if ($tip !== '' && !in_array($tip, $clinicTypes, true)) {
                $this->record('01_INSTITUTIONS', $row, 'review', 'institution', $externalId, $raw, ['tip' => $tip], [], [], null, [], 'unknown_type');
                continue;
            }

            $confidence = mb_strtolower((string) ($raw['Confidence'] ?? ''));
            if ($confidence === 'low') {
                $this->record('01_INSTITUTIONS', $row, 'review', 'institution', $externalId, $raw, [], [], [], null, [], 'low_confidence');
                continue;
            }

            $cityMatch = $this->context->cities->match($this->string($raw['Grad'] ?? null));
            $grad = $cityMatch['naziv'] ?? $this->string($raw['Grad'] ?? null);
            if (!$cityMatch) {
                $warnings[] = 'city_unmapped';
            }

            $normalized = [
                'naziv' => $this->string($raw['Naziv'] ?? null),
                'grad' => $grad,
                'adresa' => $this->string($raw['Adresa'] ?? null),
                'telefon' => $phones->normalize($this->string($raw['Telefon normalizovan'] ?? null) ?? $this->string($raw['Telefon'] ?? null))
                    ?? $this->string($raw['Telefon'] ?? null),
                'email' => $this->email($raw['Email'] ?? null),
                'website' => $urls->normalize($this->string($raw['Official website'] ?? null)),
                'google_place_id' => $this->string($raw['Google Place ID'] ?? null),
                'tip' => $tip,
            ];

            if ($this->string($raw['Official website'] ?? null) && $normalized['website'] === null) {
                $this->record('01_INSTITUTIONS', $row, 'review', 'institution', $externalId, $raw, $normalized, $warnings, ['invalid_url'], null, [], 'invalid_url');
                continue;
            }

            $missing = [];
            foreach (['naziv', 'adresa', 'grad', 'telefon'] as $required) {
                if ($this->isEmpty($normalized[$required] ?? null)) {
                    $missing[] = $required;
                }
            }
            if ($missing !== []) {
                $this->record('01_INSTITUTIONS', $row, 'failed', 'institution', $externalId, $raw, $normalized, $warnings, $missing, null, [], 'missing_required');
                continue;
            }

            $mappedId = $externalId ? $this->context->mappedId('institution', $externalId) : null;
            $existing = $mappedId ? $this->context->resolveClinic($mappedId) : null;
            $match = ['model' => $existing, 'candidates' => $existing ? [$existing] : [], 'reason' => null];
            if (!$existing) {
                $match = $matcher->match($normalized);
            }

            if ($match['reason'] === 'multiple_institution_candidates') {
                $this->record('01_INSTITUTIONS', $row, 'review', 'institution', $externalId, $raw, $normalized, $warnings, [], null, $this->candidateList($match['candidates']), 'multiple_institution_candidates');
                continue;
            }

            $existing = $match['model'];
            if ($existing) {
                if ($this->isClaimed($existing)) {
                    $this->persistMapping('institution', (string) $externalId, $existing, $this->mappingPayload($normalized));
                    $this->record('01_INSTITUTIONS', $row, 'review', 'institution', $externalId, $raw, $normalized, $warnings, [], $existing, [], 'claimed_profile');
                    continue;
                }

                if (!$this->context->updateExisting) {
                    $this->persistMapping('institution', (string) $externalId, $existing, $this->mappingPayload($normalized));
                    $this->record('01_INSTITUTIONS', $row, 'skip', 'institution', $externalId, $raw, $normalized, $warnings, [], $existing, [], 'exists');
                    continue;
                }

                $changed = $this->fillEmpty($existing, $this->clinicAttributes($normalized));
                if (!$this->context->dryRun && $changed) {
                    $existing->save();
                }
                $this->persistMapping('institution', (string) $externalId, $existing, $this->mappingPayload($normalized));
                $this->context->markClinicWritable((int) $existing->id);
                $this->record('01_INSTITUTIONS', $row, $changed ? 'update' : 'skip', 'institution', $externalId, $raw, $normalized, $warnings, [], $existing);
                continue;
            }

            $attributes = $this->clinicAttributes($normalized);
            $attributes['aktivan'] = true;
            $attributes['verifikovan'] = true;
            $attributes['verifikovan_at'] = now();
            $attributes['user_id'] = null;

            if ($normalized['google_place_id']) {
                $attributes['google_maps_link'] = 'https://www.google.com/maps/place/?q=place_id:' . $normalized['google_place_id'];
            }

            $clinic = new Klinika($attributes);
            if (!$this->context->dryRun) {
                $clinic->save();
                if ($externalId) {
                    $this->persistMapping('institution', $externalId, $clinic, $this->mappingPayload($normalized));
                }
                $this->context->markClinicWritable((int) $clinic->id);
            } elseif ($externalId) {
                $id = $this->context->rememberDryClinic($externalId, $attributes + $normalized);
                $clinic->id = $id;
            }

            $action = $warnings !== [] ? 'review' : 'create';
            if ($action === 'review') {
                $this->context->result->count('01_INSTITUTIONS', 'create');
                $this->context->result->sheets['01_INSTITUTIONS']['rows']--;
            }
            $this->record('01_INSTITUTIONS', $row, $action, 'institution', $externalId, $raw, $normalized, $warnings, [], $clinic->id ? $clinic : null, [], $warnings[0] ?? null);
        }
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function clinicAttributes(array $normalized): array
    {
        return [
            'naziv' => $normalized['naziv'],
            'grad' => $normalized['grad'],
            'adresa' => $normalized['adresa'],
            'telefon' => $normalized['telefon'],
            'email' => $normalized['email'],
            'website' => $normalized['website'],
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function mappingPayload(array $normalized): array
    {
        return [
            'tip' => $normalized['tip'] ?? null,
            'google_place_id' => $normalized['google_place_id'] ?? null,
        ];
    }

    /**
     * @param  list<Klinika>  $candidates
     * @return list<array{id:int,naziv:?string,grad:?string}>
     */
    private function candidateList(array $candidates): array
    {
        return array_map(fn (Klinika $clinic) => [
            'id' => (int) $clinic->id,
            'naziv' => $clinic->naziv,
            'grad' => $clinic->grad,
        ], $candidates);
    }

    private function email(mixed $value): ?string
    {
        $email = $this->string($value);
        if ($email === null) {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;
    }
}
