<?php

namespace App\Services\HealthcareImport\Importers;

use App\Services\HealthcareImport\Normalizers\WorkingHoursParser;

class WorkingHoursImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $parser = new WorkingHoursParser();

        foreach ($this->context->workbook->sheet('09_WORKING_HOURS') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $hoursId = $this->string($raw['Hours ID'] ?? null);
            $institutionExternalId = $this->string($raw['Institution ID'] ?? null);
            $klinikaId = $institutionExternalId
                ? ($this->context->institutionIds[$institutionExternalId] ?? $this->context->mappedId('institution', $institutionExternalId))
                : null;
            $clinic = $klinikaId ? $this->context->resolveClinic($klinikaId) : null;

            if (!$clinic) {
                $this->record('09_WORKING_HOURS', $row, 'skip', 'working_hours', $hoursId, $raw, [], [], [], null, [], 'institution_not_imported');
                continue;
            }

            if ($this->isClaimed($clinic)) {
                $this->record('09_WORKING_HOURS', $row, 'review', 'working_hours', $hoursId, $raw, [], [], [], $clinic, [], 'claimed_profile');
                continue;
            }

            if (!$this->context->isClinicWritable((int) $clinic->id)) {
                $this->record('09_WORKING_HOURS', $row, 'skip', 'working_hours', $hoursId, $raw, [], [], [], $clinic, [], 'existing_unchanged');
                continue;
            }

            $parsed = $parser->parse($this->string($raw['Working hours raw'] ?? null));
            if ($parsed === null) {
                $this->record('09_WORKING_HOURS', $row, 'review', 'working_hours', $hoursId, $raw, [], [], [], $clinic, [], 'hours_unparsed');
                continue;
            }

            $changed = false;
            if ($this->isEmpty($clinic->radno_vrijeme)) {
                $clinic->radno_vrijeme = $parsed;
                $changed = true;
            }

            if (!$this->context->dryRun && $changed) {
                $clinic->save();
            }

            $this->record('09_WORKING_HOURS', $row, $changed ? 'update' : 'skip', 'working_hours', $hoursId, $raw, ['radno_vrijeme' => $parsed], [], [], $clinic);
        }
    }
}
