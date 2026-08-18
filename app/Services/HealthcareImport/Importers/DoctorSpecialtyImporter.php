<?php

namespace App\Services\HealthcareImport\Importers;

class DoctorSpecialtyImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        foreach ($this->context->workbook->sheet('05_DOCTOR_SPECIALITIES') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $relationId = $this->string($raw['Relation ID'] ?? null);
            $doctorExternalId = $this->string($raw['Doctor ID'] ?? null);
            $doctorId = $doctorExternalId ? ($this->context->doctorIds[$doctorExternalId] ?? $this->context->mappedId('doctor', $doctorExternalId)) : null;

            $match = $this->context->specialties->match(
                $this->string($raw['Source specialty'] ?? null),
                $this->string($raw['Canonical candidate'] ?? null)
            );

            if (!$match) {
                $this->record('05_DOCTOR_SPECIALITIES', $row, 'review', 'doctor_specialty', $relationId, $raw, [], [], [], null, [], 'specialty_unmapped');
                continue;
            }

            if (!$doctorId) {
                $this->record('05_DOCTOR_SPECIALITIES', $row, 'skip', 'doctor_specialty', $relationId, $raw, $match, [], [], null, [], 'doctor_not_imported');
                continue;
            }

            $doctor = $this->context->resolveDoctor($doctorId);
            if (!$doctor) {
                $this->record('05_DOCTOR_SPECIALITIES', $row, 'skip', 'doctor_specialty', $relationId, $raw, $match, [], [], null, [], 'doctor_missing');
                continue;
            }

            if ($this->isClaimed($doctor)) {
                $this->record('05_DOCTOR_SPECIALITIES', $row, 'review', 'doctor_specialty', $relationId, $raw, $match, [], [], $doctor, [], 'claimed_profile');
                continue;
            }

            if (!$this->context->isDoctorWritable((int) $doctor->id)) {
                $this->record('05_DOCTOR_SPECIALITIES', $row, 'skip', 'doctor_specialty', $relationId, $raw, $match, [], [], $doctor, [], 'existing_unchanged');
                continue;
            }

            if (!$this->context->dryRun) {
                $doctor->specijalnosti()->syncWithoutDetaching([$match['id']]);
                if ($this->isEmpty($doctor->specijalnost_id)) {
                    $doctor->specijalnost_id = $match['id'];
                    $doctor->specijalnost = $doctor->specijalnost ?: $match['naziv'];
                    $doctor->save();
                }
            }

            $this->record('05_DOCTOR_SPECIALITIES', $row, 'update', 'doctor_specialty', $relationId, $raw, $match, [], [], $doctor);
        }
    }
}
