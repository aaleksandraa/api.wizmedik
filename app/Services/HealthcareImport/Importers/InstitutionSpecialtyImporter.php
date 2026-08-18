<?php

namespace App\Services\HealthcareImport\Importers;

class InstitutionSpecialtyImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        foreach ($this->context->workbook->sheet('06_INST_SPECIALITIES') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $relationId = $this->string($raw['Relation ID'] ?? null);
            $institutionExternalId = $this->string($raw['Institution ID'] ?? null);
            $klinikaId = $institutionExternalId
                ? ($this->context->institutionIds[$institutionExternalId] ?? $this->context->mappedId('institution', $institutionExternalId))
                : null;

            $match = $this->context->specialties->match(
                $this->string($raw['Source specialty'] ?? null),
                $this->string($raw['Canonical candidate'] ?? null)
            );

            if (!$match) {
                $this->record('06_INST_SPECIALITIES', $row, 'review', 'institution_specialty', $relationId, $raw, [], [], [], null, [], 'specialty_unmapped');
                continue;
            }

            if (!$klinikaId) {
                $this->record('06_INST_SPECIALITIES', $row, 'skip', 'institution_specialty', $relationId, $raw, $match, [], [], null, [], 'institution_not_imported');
                continue;
            }

            $clinic = $this->context->resolveClinic($klinikaId);
            if (!$clinic) {
                $this->record('06_INST_SPECIALITIES', $row, 'skip', 'institution_specialty', $relationId, $raw, $match, [], [], null, [], 'institution_missing');
                continue;
            }

            if ($this->isClaimed($clinic)) {
                $this->record('06_INST_SPECIALITIES', $row, 'review', 'institution_specialty', $relationId, $raw, $match, [], [], $clinic, [], 'claimed_profile');
                continue;
            }

            if (!$this->context->isClinicWritable((int) $clinic->id)) {
                $this->record('06_INST_SPECIALITIES', $row, 'skip', 'institution_specialty', $relationId, $raw, $match, [], [], $clinic, [], 'existing_unchanged');
                continue;
            }

            if (!$this->context->dryRun) {
                $clinic->specijalnosti()->syncWithoutDetaching([$match['id']]);
            }

            $this->record('06_INST_SPECIALITIES', $row, 'update', 'institution_specialty', $relationId, $raw, $match, [], [], $clinic);
        }
    }
}
