<?php

namespace App\Services\HealthcareImport\Importers;

use App\Services\HealthcareImport\Normalizers\PhoneNormalizer;
use App\Services\HealthcareImport\Normalizers\UrlNormalizer;

class ContactImporter extends AbstractSheetImporter
{
    public function import(): void
    {
        $phones = new PhoneNormalizer();
        $urls = new UrlNormalizer();

        foreach ($this->context->workbook->sheet('08_CONTACTS') as $entry) {
            $raw = $entry['data'];
            $row = $entry['row'];
            $contactId = $this->string($raw['Contact ID'] ?? null);
            $entityType = mb_strtolower((string) ($raw['Entity type'] ?? ''));
            $entityId = $this->string($raw['Entity ID'] ?? null);
            $type = mb_strtolower((string) ($raw['Contact type'] ?? ''));

            if ($entityType !== 'institution') {
                $this->record('08_CONTACTS', $row, 'skip', 'contact', $contactId, $raw, [], [], [], null, [], 'unsupported_entity_type');
                continue;
            }

            $klinikaId = $entityId ? ($this->context->institutionIds[$entityId] ?? $this->context->mappedId('institution', $entityId)) : null;
            $clinic = $klinikaId ? $this->context->resolveClinic($klinikaId) : null;
            if (!$clinic) {
                $this->record('08_CONTACTS', $row, 'skip', 'contact', $contactId, $raw, [], [], [], null, [], 'institution_not_imported');
                continue;
            }

            if ($this->isClaimed($clinic)) {
                $this->record('08_CONTACTS', $row, 'review', 'contact', $contactId, $raw, [], [], [], $clinic, [], 'claimed_profile');
                continue;
            }

            if (!$this->context->isClinicWritable((int) $clinic->id)) {
                $this->record('08_CONTACTS', $row, 'skip', 'contact', $contactId, $raw, [], [], [], $clinic, [], 'existing_unchanged');
                continue;
            }

            $value = $this->string($raw['Normalized'] ?? null) ?? $this->string($raw['Raw'] ?? null);
            $data = [];
            if ($type === 'phone') {
                $data['telefon'] = $phones->normalize($value) ?? $value;
            } elseif ($type === 'email') {
                $email = filter_var((string) $value, FILTER_VALIDATE_EMAIL) ? mb_strtolower((string) $value) : null;
                if ($email === null) {
                    $this->record('08_CONTACTS', $row, 'review', 'contact', $contactId, $raw, [], [], ['invalid_email'], $clinic, [], 'invalid_email');
                    continue;
                }
                $data['email'] = $email;
            } elseif ($type === 'website') {
                $url = $urls->normalize($value);
                if ($url === null) {
                    $this->record('08_CONTACTS', $row, 'review', 'contact', $contactId, $raw, [], [], ['invalid_url'], $clinic, [], 'invalid_url');
                    continue;
                }
                $data['website'] = $url;
            } else {
                $this->record('08_CONTACTS', $row, 'review', 'contact', $contactId, $raw, [], [], [], $clinic, [], 'unsupported_contact_type');
                continue;
            }

            $changed = $this->fillEmpty($clinic, $data);
            if (!$this->context->dryRun && $changed) {
                $clinic->save();
            }

            $this->record('08_CONTACTS', $row, $changed ? 'update' : 'skip', 'contact', $contactId, $raw, $data, [], [], $clinic);
        }
    }
}
