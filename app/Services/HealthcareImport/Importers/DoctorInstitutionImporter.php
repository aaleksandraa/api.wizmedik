<?php

namespace App\Services\HealthcareImport\Importers;

class DoctorInstitutionImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $seen = [];

        foreach ($this->context->workbook->sheet('04_DOCTOR_INSTITUTIONS') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $doctorExternalId = $this->string($raw['Doctor ID'] ?? null);
            $institutionExternalId = $this->string($raw['Institution ID'] ?? null);
            $affiliationId = $this->string($raw['Affiliation ID'] ?? null);

            $doctorId = $doctorExternalId ? ($this->context->doctorIds[$doctorExternalId] ?? $this->context->mappedId('doctor', $doctorExternalId)) : null;
            $klinikaId = $institutionExternalId ? ($this->context->institutionIds[$institutionExternalId] ?? $this->context->mappedId('institution', $institutionExternalId)) : null;

            if (!$doctorId || !$klinikaId) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'skip', 'affiliation', $affiliationId, $raw, [], [], [], null, [], 'missing_mapped_entities');
                continue;
            }

            $key = $doctorExternalId;
            if (isset($seen[$key])) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'review', 'affiliation', $affiliationId, $raw, [
                    'doctor_id' => $doctorId,
                    'klinika_id' => $klinikaId,
                ], [], [], null, [], 'extra_affiliation_unsupported');
                continue;
            }
            $seen[$key] = $klinikaId;

            $doctor = $this->context->resolveDoctor($doctorId);
            if (!$doctor) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'skip', 'affiliation', $affiliationId, $raw, [], [], [], null, [], 'doctor_missing');
                continue;
            }

            if ($this->isClaimed($doctor)) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'review', 'affiliation', $affiliationId, $raw, [], [], [], $doctor, [], 'claimed_profile');
                continue;
            }

            if ((int) $doctor->klinika_id === (int) $klinikaId) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'skip', 'affiliation', $affiliationId, $raw, [], [], [], $doctor, [], 'already_linked');
                continue;
            }

            if ($doctor->klinika_id && (int) $doctor->klinika_id !== (int) $klinikaId) {
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'review', 'affiliation', $affiliationId, $raw, [
                    'existing_klinika_id' => $doctor->klinika_id,
                    'incoming_klinika_id' => $klinikaId,
                ], [], [], $doctor, [], 'extra_affiliation_unsupported');
                continue;
            }

            if ($this->isEmpty($doctor->klinika_id) && ($this->context->isDoctorWritable((int) $doctor->id) || $this->context->updateExisting)) {
                $doctor->klinika_id = $klinikaId;
                if (!$this->context->dryRun) {
                    $doctor->save();
                }
                $this->record('04_DOCTOR_INSTITUTIONS', $row, 'update', 'affiliation', $affiliationId, $raw, [], [], [], $doctor);
                continue;
            }

            $this->record('04_DOCTOR_INSTITUTIONS', $row, 'skip', 'affiliation', $affiliationId, $raw, [], [], [], $doctor, [], 'existing_unchanged');
        }
    }
}
