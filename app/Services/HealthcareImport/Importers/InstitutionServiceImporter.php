<?php

namespace App\Services\HealthcareImport\Importers;

class InstitutionServiceImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        foreach ($this->context->workbook->sheet('07_INST_SERVICES') as $entry) {
            $this->record(
                '07_INST_SERVICES',
                $entry['row'],
                'review',
                'institution_service',
                $this->string($entry['data']['Relation ID'] ?? null),
                $entry['data'],
                [],
                [],
                [],
                null,
                [],
                'clinic_services_unsupported'
            );
        }
    }
}
