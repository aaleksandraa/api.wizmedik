<?php

namespace App\Services\HealthcareImport\Importers;

class LocationImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $byInstitution = [];
        foreach ($this->context->workbook->sheet('02_LOCATIONS') as $entry) {
            $institutionId = $this->string($entry['data']['Institution ID'] ?? null);
            if ($institutionId) {
                $byInstitution[$institutionId][] = $entry;
            }
        }

        foreach ($byInstitution as $institutionId => $locations) {
            if (count($locations) > 1) {
                foreach (array_slice($locations, 1) as $extra) {
                    $this->record(
                        '02_LOCATIONS',
                        $extra['row'],
                        'review',
                        'location',
                        $this->string($extra['data']['Location ID'] ?? null),
                        $extra['data'],
                        [],
                        [],
                        [],
                        null,
                        [],
                        'extra_location_unsupported'
                    );
                }
            }

            $primary = $locations[0];
            foreach ($locations as $location) {
                if (mb_strtoupper((string) ($location['data']['Primary'] ?? '')) === 'YES') {
                    $primary = $location;
                    break;
                }
            }

            $klinikaId = $this->context->institutionIds[$institutionId] ?? $this->context->mappedId('institution', $institutionId);
            $raw = $primary['data'];
            if (!$klinikaId) {
                $this->record('02_LOCATIONS', $primary['row'], 'skip', 'location', $this->string($raw['Location ID'] ?? null), $raw, [], [], [], null, [], 'institution_not_imported');
                continue;
            }

            $clinic = $klinikaId ? $this->context->resolveClinic($klinikaId) : null;
            if (!$clinic) {
                $this->record('02_LOCATIONS', $primary['row'], 'skip', 'location', $this->string($raw['Location ID'] ?? null), $raw, [], [], [], null, [], 'institution_missing');
                continue;
            }

            if ($this->isClaimed($clinic)) {
                $this->record('02_LOCATIONS', $primary['row'], 'review', 'location', $this->string($raw['Location ID'] ?? null), $raw, [], [], [], $clinic, [], 'claimed_profile');
                continue;
            }

            if (!$this->context->isClinicWritable((int) $clinic->id)) {
                $this->record('02_LOCATIONS', $primary['row'], 'skip', 'location', $this->string($raw['Location ID'] ?? null), $raw, [], [], [], $clinic, [], 'existing_unchanged');
                continue;
            }

            $cityMatch = $this->context->cities->match($this->string($raw['Grad'] ?? null));
            $data = [
                'adresa' => $this->string($raw['Adresa'] ?? null),
                'grad' => $cityMatch['naziv'] ?? $this->string($raw['Grad'] ?? null),
            ];

            $changed = $this->fillEmpty($clinic, $data);

            if (!$this->context->dryRun && $changed) {
                $clinic->save();
            }

            $this->record('02_LOCATIONS', $primary['row'], $changed ? 'update' : 'skip', 'location', $this->string($raw['Location ID'] ?? null), $raw, $data, [], [], $clinic);
        }
    }
}
