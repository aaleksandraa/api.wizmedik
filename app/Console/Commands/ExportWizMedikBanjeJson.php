<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ExportWizMedikBanjeJson extends Command
{
    protected $signature = 'wizmedik:export-banje-json
        {--excel= : Path to v12 Excel workbook}
        {--output= : Output JSON path}';

    protected $description = 'Export approved banje rows from Excel sheet 21_BACKEND_BANJE to JSON import payload';

    public function handle(): int
    {
        $excelPath = $this->option('excel')
            ?: base_path('../docs/banjadomovi/WizMedik_BANJE_DOMOVI_FINAL_BACKEND_IMPORT_v12.xlsx');
        $outputPath = $this->option('output')
            ?: base_path('../docs/banjadomovi/wizmedik_banje_import_approved_v12.json');

        if (!is_file($excelPath)) {
            $this->error("Excel file not found: {$excelPath}");

            return self::FAILURE;
        }

        $workbook = IOFactory::load($excelPath);
        $sheet = $workbook->getSheetByName('21_BACKEND_BANJE');
        if (!$sheet) {
            $this->error('Sheet 21_BACKEND_BANJE not found.');

            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_shift($rows);
        $headerMap = $this->buildHeaderMap($headers);
        $payload = [];

        foreach ($rows as $row) {
            $record = $this->mapRow($row, $headerMap);
            if ($record === null) {
                continue;
            }

            $gate = data_get($record, '_research.import_gate');
            if (!in_array($gate, ['IMPORT_READY_FULL', 'IMPORT_READY_CORE'], true)) {
                continue;
            }

            $payload[] = $record;
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Failed to encode banje JSON payload.');
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create output directory: {$dir}");
        }

        file_put_contents($outputPath, $json . PHP_EOL);

        $this->info("Exported " . count($payload) . " banje to {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int|string, mixed>  $headers
     * @return array<string, string>
     */
    private function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $column => $header) {
            if (is_string($header) && $header !== '') {
                $map[$column] = $header;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $headerMap
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row, array $headerMap): ?array
    {
        $data = [];
        foreach ($headerMap as $column => $header) {
            $data[$header] = $row[$column] ?? null;
        }

        $sourceId = trim((string) ($data['source_id'] ?? ''));
        $naziv = trim((string) ($data['naziv'] ?? ''));
        if ($sourceId === '' || $naziv === '') {
            return null;
        }

        $radnoVrijeme = $this->decodeJson($data['radno_vrijeme_json'] ?? null);

        return [
            'source_id' => $sourceId,
            'naziv' => $naziv,
            'slug' => trim((string) ($data['slug'] ?? '')),
            'grad' => trim((string) ($data['grad'] ?? '')),
            'regija' => $this->nullableString($data['regija'] ?? null),
            'adresa' => trim((string) ($data['adresa'] ?? '')),
            'latitude' => null,
            'longitude' => null,
            'google_maps_link' => $this->nullableString($data['google_maps_link'] ?? null),
            'telefon' => $this->nullableString($data['telefon'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'website' => $this->nullableString($data['website'] ?? null),
            'opis' => trim((string) ($data['opis'] ?? '')),
            'detaljni_opis' => $this->nullableString($data['detaljni_opis'] ?? null),
            'medicinski_nadzor' => $this->parseBool($data['medicinski_nadzor'] ?? false),
            'fizijatar_prisutan' => $this->parseBool($data['fizijatar_prisutan'] ?? false),
            'medicinsko_osoblje' => null,
            'ima_smjestaj' => $this->parseBool($data['ima_smjestaj'] ?? false),
            'broj_kreveta' => $this->nullableInt($data['broj_kreveta'] ?? null),
            'online_rezervacija' => $this->parseBool($data['online_rezervacija'] ?? false),
            'online_upit' => $this->parseBool($data['online_upit'] ?? true),
            'verifikovan' => true,
            'aktivan' => true,
            'featured_slika' => null,
            'galerija' => [],
            'radno_vrijeme' => $radnoVrijeme,
            'meta_title' => $this->nullableString($data['meta_title'] ?? null),
            'meta_description' => $this->nullableString($data['meta_description'] ?? null),
            'meta_keywords' => $this->nullableString($data['meta_keywords'] ?? null),
            'vrste_slugs' => $this->decodeJson($data['vrste_slugs'] ?? null),
            'indikacije_slugs' => $this->decodeJson($data['indikacije_slugs'] ?? null),
            'terapije_slugs' => $this->decodeJson($data['terapije_slugs'] ?? null),
            '_research' => [
                'import_gate' => trim((string) ($data['import_gate'] ?? '')),
                'source_url' => $this->nullableString($data['source_url'] ?? null),
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtoupper(trim((string) $value)), ['TRUE', '1', 'YES', 'DA'], true);
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
