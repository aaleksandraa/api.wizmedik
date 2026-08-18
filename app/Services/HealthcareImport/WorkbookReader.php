<?php

namespace App\Services\HealthcareImport;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class WorkbookReader
{
    /** @var array<string, list<array{row:int,data:array<string, mixed>}>> */
    private array $sheets = [];

    /** @var array<string, int> */
    public array $headerRow = [];

    public function load(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("Excel fajl nije pronadjen: {$path}");
        }

        $spreadsheet = IOFactory::load($path);
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $name = $worksheet->getTitle();
            $this->sheets[$name] = $this->readSheet($worksheet);
        }
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * @return list<array{row:int,data:array<string, mixed>}>
     */
    public function sheet(string $name): array
    {
        return $this->sheets[$name] ?? [];
    }

    public function hasSheet(string $name): bool
    {
        return isset($this->sheets[$name]);
    }

    /**
     * @return list<array{row:int,data:array<string, mixed>}>
     */
    private function readSheet(Worksheet $worksheet): array
    {
        $rows = [];
        $headers = [];
        $highestRow = (int) $worksheet->getHighestDataRow();
        $highestColumn = $worksheet->getHighestDataColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $values = [];
            $colIndex = 0;
            foreach ($worksheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0] ?? [] as $cell) {
                $values[$colIndex] = is_string($cell) ? trim($cell) : $cell;
                $colIndex++;
            }

            if ($row === 1) {
                $headers = array_map(fn ($header) => is_string($header) ? trim($header) : (string) $header, $values);
                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $data = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $data[$header] = $values[$index] ?? null;
            }

            $rows[] = ['row' => $row, 'data' => $data];
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
