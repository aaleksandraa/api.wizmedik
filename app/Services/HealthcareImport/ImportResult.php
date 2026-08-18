<?php

namespace App\Services\HealthcareImport;

class ImportResult
{
    /** @var array<string, array{rows:int,create:int,update:int,skip:int,review:int,failed:int}> */
    public array $sheets = [];

    /** @var list<array<string, mixed>> */
    public array $reviewRows = [];

    /** @var list<array<string, mixed>> */
    public array $failedRows = [];

    public string $uuid = '';

    public ?string $jsonPath = null;

    public ?string $csvPath = null;

    public function ensureSheet(string $sheet): void
    {
        if (!isset($this->sheets[$sheet])) {
            $this->sheets[$sheet] = [
                'rows' => 0,
                'create' => 0,
                'update' => 0,
                'skip' => 0,
                'review' => 0,
                'failed' => 0,
            ];
        }
    }

    public function count(string $sheet, string $action): void
    {
        $this->ensureSheet($sheet);
        $this->sheets[$sheet]['rows']++;
        $key = match ($action) {
            'create' => 'create',
            'update' => 'update',
            'skip' => 'skip',
            'review' => 'review',
            default => 'failed',
        };
        $this->sheets[$sheet][$key]++;
    }

    public function totals(): array
    {
        $totals = ['rows' => 0, 'create' => 0, 'update' => 0, 'skip' => 0, 'review' => 0, 'failed' => 0];
        foreach ($this->sheets as $sheet) {
            foreach ($totals as $key => $_) {
                $totals[$key] += $sheet[$key];
            }
        }

        return $totals;
    }
}
