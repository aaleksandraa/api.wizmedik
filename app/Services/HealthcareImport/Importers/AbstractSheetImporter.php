<?php

namespace App\Services\HealthcareImport\Importers;

use App\Models\HealthcareImportRow;
use App\Models\ImportEntityMapping;
use App\Services\HealthcareImport\ImportContext;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractSheetImporter
{
    public function __construct(protected ImportContext $context)
    {
    }

    abstract public function import(): void;

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $normalized
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @param  list<array<string, mixed>>  $candidates
     */
    protected function record(
        string $sheet,
        int $rowNumber,
        string $action,
        ?string $entityType = null,
        ?string $externalId = null,
        array $raw = [],
        array $normalized = [],
        array $warnings = [],
        array $errors = [],
        ?Model $matched = null,
        array $candidates = [],
        ?string $reason = null,
    ): void {
        $this->context->result->count($sheet, $action);

        $entry = [
            'sheet' => $sheet,
            'row' => $rowNumber,
            'external_id' => $externalId,
            'action' => $action,
            'status' => $action,
            'reason' => $reason,
            'warnings' => $warnings,
            'errors' => $errors,
            'candidates' => $candidates,
        ];

        if ($action === 'review') {
            $this->context->result->reviewRows[] = $entry;
        }
        if ($action === 'failed') {
            $this->context->result->failedRows[] = $entry;
        }

        if (!$this->context->canPersistAudit || $this->context->batchId === null) {
            return;
        }

        HealthcareImportRow::query()->create([
            'batch_id' => $this->context->batchId,
            'sheet_name' => $sheet,
            'row_number' => $rowNumber,
            'entity_type' => $entityType,
            'external_id' => $externalId,
            'action' => $action,
            'status' => $action,
            'matched_model_type' => $matched ? $matched::class : null,
            'matched_model_id' => $matched?->getKey(),
            'raw_payload' => $this->safePayload($raw),
            'normalized_payload' => $normalized,
            'warnings' => $warnings ?: null,
            'errors' => $errors ?: ($reason ? [$reason] : null),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function persistMapping(string $entityType, string $externalId, Model $model, ?array $payload = null): void
    {
        $this->context->rememberMapping($entityType, $externalId, $model::class, (int) $model->getKey(), $payload);

        if ($this->context->dryRun || !$this->context->canPersistAudit) {
            return;
        }

        ImportEntityMapping::query()->updateOrCreate(
            [
                'source' => $this->context->source,
                'entity_type' => $entityType,
                'external_id' => $externalId,
            ],
            [
                'model_type' => $model::class,
                'model_id' => $model->getKey(),
                'import_batch_id' => $this->context->batchId,
                'payload' => $payload,
            ]
        );
    }

    protected function isClaimed(Model $model): bool
    {
        return (int) ($model->getAttribute('user_id') ?? 0) > 0;
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function fillEmpty(Model $model, array $data): bool
    {
        $changed = false;
        foreach ($data as $key => $value) {
            if ($this->isEmpty($value)) {
                continue;
            }
            if ($this->isEmpty($model->getAttribute($key))) {
                $model->setAttribute($key, $value);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function safePayload(array $raw): array
    {
        unset($raw['password'], $raw['account_email']);

        return $raw;
    }

    protected function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
