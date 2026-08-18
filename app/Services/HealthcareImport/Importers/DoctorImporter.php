<?php

namespace App\Services\HealthcareImport\Importers;

use App\Models\Doktor;
use App\Services\HealthcareImport\Matchers\DoctorMatcher;
use App\Services\HealthcareImport\Normalizers\DoctorNameNormalizer;
use App\Services\HealthcareImport\Normalizers\PhoneNormalizer;
use App\Services\HealthcareImport\Normalizers\UrlNormalizer;

class DoctorImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $names = new DoctorNameNormalizer();
        $matcher = new DoctorMatcher($names);
        $phones = new PhoneNormalizer();
        $urls = new UrlNormalizer();

        $affiliations = [];
        foreach ($this->context->workbook->sheet('04_DOCTOR_INSTITUTIONS') as $entry) {
            $doctorId = $this->string($entry['data']['Doctor ID'] ?? null);
            if ($doctorId) {
                $affiliations[$doctorId][] = $entry['data'];
            }
        }

        foreach ($this->context->workbook->sheet('03_DOCTORS') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $externalId = $this->string($raw['Doctor ID'] ?? null);
            $parsed = $names->parse($this->string($raw['Ime i prezime / javna titula'] ?? null));

            if ($parsed === null || $parsed['ime'] === null || $parsed['prezime'] === null) {
                $this->record('03_DOCTORS', $row, 'failed', 'doctor', $externalId, $raw, [], [], ['name_unparsed'], null, [], 'name_unparsed');
                continue;
            }

            $primaryAffiliation = $affiliations[$externalId][0] ?? null;
            $institutionExternalId = $this->string($primaryAffiliation['Institution ID'] ?? null);
            $klinikaId = $institutionExternalId
                ? ($this->context->institutionIds[$institutionExternalId] ?? $this->context->mappedId('institution', $institutionExternalId))
                : null;
            $clinic = $klinikaId ? $this->context->resolveClinic($klinikaId) : null;

            if (!$clinic) {
                $this->record('03_DOCTORS', $row, 'review', 'doctor', $externalId, $raw, $parsed, [], [], null, [], 'missing_affiliation');
                continue;
            }

            if ($this->isClaimed($clinic)) {
                $this->record('03_DOCTORS', $row, 'review', 'doctor', $externalId, $raw, $parsed, [], [], $clinic, [], 'claimed_clinic');
                continue;
            }

            $specialtySource = $this->string($raw['Primarna specijalnost'] ?? null)
                ?? $this->string($primaryAffiliation['Specijalnost u ustanovi'] ?? null);
            $specialtyMatch = $this->context->specialties->match($specialtySource);
            $warnings = [];
            if (!$specialtyMatch) {
                $warnings[] = 'specialty_unmapped';
            }

            $profileUrl = $urls->normalize($this->string($raw['Profile URL'] ?? null));
            $normalized = [
                'ime' => $parsed['ime'],
                'prezime' => $parsed['prezime'],
                'match_key' => $parsed['match_key'],
                'specijalnost' => $specialtySource ?? 'Nepoznata',
                'specijalnost_id' => $specialtyMatch['id'] ?? null,
                'klinika_id' => $clinic->id,
                'grad' => $clinic->grad,
                'lokacija' => $clinic->adresa,
                'telefon' => $phones->normalize($clinic->telefon) ?? $clinic->telefon,
                'profile_url' => $profileUrl,
            ];

            $mappedId = $externalId ? $this->context->mappedId('doctor', $externalId) : null;
            $existing = $mappedId ? $this->context->resolveDoctor($mappedId) : null;
            if (!$existing) {
                $match = $matcher->match($normalized);
                if ($match['reason']) {
                    $this->record('03_DOCTORS', $row, 'review', 'doctor', $externalId, $raw, $normalized, $warnings, [], null, $this->candidateList($match['candidates']), $match['reason']);
                    continue;
                }
                $existing = $match['model'];
            }

            if ($existing) {
                if ($this->isClaimed($existing)) {
                    $this->persistMapping('doctor', (string) $externalId, $existing);
                    $this->record('03_DOCTORS', $row, 'review', 'doctor', $externalId, $raw, $normalized, $warnings, [], $existing, [], 'claimed_profile');
                    continue;
                }

                if (!$this->context->updateExisting) {
                    $this->persistMapping('doctor', (string) $externalId, $existing);
                    $this->record('03_DOCTORS', $row, 'skip', 'doctor', $externalId, $raw, $normalized, $warnings, [], $existing, [], 'exists');
                    continue;
                }

                $changed = $this->fillEmpty($existing, [
                    'klinika_id' => $clinic->id,
                    'grad' => $clinic->grad,
                    'lokacija' => $clinic->adresa,
                    'telefon' => $normalized['telefon'],
                    'specijalnost' => $normalized['specijalnost'],
                    'specijalnost_id' => $normalized['specijalnost_id'],
                ]);
                if (!$this->context->dryRun && $changed) {
                    $existing->save();
                }
                $this->attachSpecialty($existing, $specialtyMatch['id'] ?? null);
                $this->persistMapping('doctor', (string) $externalId, $existing);
                $this->context->markDoctorWritable((int) $existing->id);
                $this->record('03_DOCTORS', $row, $changed ? 'update' : 'skip', 'doctor', $externalId, $raw, $normalized, $warnings, [], $existing);
                continue;
            }

            $doctor = new Doktor([
                'ime' => $parsed['ime'],
                'prezime' => $parsed['prezime'],
                'specijalnost' => $normalized['specijalnost'],
                'specijalnost_id' => $normalized['specijalnost_id'],
                'klinika_id' => $clinic->id,
                'grad' => $clinic->grad,
                'lokacija' => $clinic->adresa,
                'telefon' => $normalized['telefon'],
                'aktivan' => true,
                'verifikovan' => true,
                'verifikovan_at' => now(),
                'user_id' => null,
                'prihvata_online' => false,
                'auto_potvrda' => false,
            ]);

            if (!$this->context->dryRun) {
                $doctor->save();
                $this->attachSpecialty($doctor, $specialtyMatch['id'] ?? null);
                if ($externalId) {
                    $this->persistMapping('doctor', $externalId, $doctor);
                }
                $this->context->markDoctorWritable((int) $doctor->id);
            } elseif ($externalId) {
                $id = $this->context->rememberDryDoctor($externalId, $doctor->getAttributes());
                $doctor->id = $id;
            }

            $this->record('03_DOCTORS', $row, $warnings !== [] ? 'review' : 'create', 'doctor', $externalId, $raw, $normalized, $warnings, [], $doctor->id ? $doctor : null, [], $warnings[0] ?? null);
        }
    }

    private function attachSpecialty(Doktor $doctor, ?int $specialtyId): void
    {
        if (!$specialtyId || $this->context->dryRun || !$doctor->id) {
            return;
        }

        $doctor->specijalnosti()->syncWithoutDetaching([$specialtyId]);
    }

    /**
     * @param  list<Doktor>  $candidates
     * @return list<array{id:int,ime:?string,prezime:?string}>
     */
    private function candidateList(array $candidates): array
    {
        return array_map(fn (Doktor $doctor) => [
            'id' => (int) $doctor->id,
            'ime' => $doctor->ime,
            'prezime' => $doctor->prezime,
        ], $candidates);
    }
}
