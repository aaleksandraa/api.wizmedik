<?php

namespace App\Console\Commands;

use App\Models\Lijek;
use App\Models\LijekRegistarZapis;
use App\Support\LijekCacheVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLijekoviRegistar extends Command
{
    protected $signature = 'lijekovi:import-registar
        {--path=public/lijekovi-registar.csv : Putanja do CSV ili XML fajla}
        {--truncate : Obrisi postojece registar zapise prije importa}
        {--allow-overwrite : Dozvoli overwrite postojecih vrijednosti u tabeli lijekovi}
        {--json : Ispisi rezultat kao JSON}';

    protected $description = 'Import registracijskih podataka za lijekove i mapiranje na postojece lijekovi zapise';

    private const REGISTAR_FIELDS = [
        'source_lijek_id',
        'atc_sifra',
        'inn',
        'jidl',
        'naziv_lijeka',
        'proizvodjac',
        'nosilac_dozvole',
        'oblik',
        'jacina',
        'pakovanje',
        'broj_dozvole',
        'tip_lijeka',
        'podtip_lijeka',
        'vazi_od',
        'vazi_do',
        'datum_rjesenja',
        'rezim_izdavanja',
        'posebne_oznake',
        'nalaz_prve_serije',
        'nalaz_prve_serije_prethodno_rjesenje',
    ];

    private const LIJEK_MAP = [
        'atc_sifra' => 'atc_sifra',
        'inn' => 'inn',
        'jidl' => 'jidl',
        'naziv_lijeka' => 'naziv_lijeka',
        'proizvodjac' => 'proizvodjac',
        'nosilac_dozvole' => 'nosilac_dozvole',
        'oblik' => 'oblik_registar',
        'jacina' => 'jacina',
        'pakovanje' => 'pakovanje_registar',
        'broj_dozvole' => 'broj_dozvole',
        'tip_lijeka' => 'tip_lijeka',
        'podtip_lijeka' => 'podtip_lijeka',
        'vazi_od' => 'vazi_od',
        'vazi_do' => 'vazi_do',
        'datum_rjesenja' => 'datum_rjesenja',
        'rezim_izdavanja' => 'rezim_izdavanja',
        'posebne_oznake' => 'posebne_oznake',
        'nalaz_prve_serije' => 'nalaz_prve_serije',
        'nalaz_prve_serije_prethodno_rjesenje' => 'nalaz_prve_serije_prethodno_rjesenje',
    ];

    public function handle(): int
    {
        $sourcePath = $this->resolvePath((string) $this->option('path'));
        if (!is_file($sourcePath)) {
            $this->error("Fajl nije pronadjen: {$sourcePath}");
            return self::FAILURE;
        }

        $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xml'], true)) {
            $this->error('Podrzani su samo CSV i XML fajlovi.');
            return self::FAILURE;
        }

        $rows = $extension === 'xml'
            ? $this->parseXml($sourcePath)
            : $this->parseCsv($sourcePath);

        if (empty($rows)) {
            $this->warn('Nema validnih redova za import.');
            return self::SUCCESS;
        }

        $batchId = now()->format('YmdHis') . '-' . Str::random(8);
        $allowOverwrite = (bool) $this->option('allow-overwrite');
        $truncate = (bool) $this->option('truncate');
        $jsonOutput = (bool) $this->option('json');

        if (!$jsonOutput) {
            $this->info('Pronadjeno registar redova: ' . count($rows));
            $this->newLine();
        }

        $summary = [
            'rows_total' => count($rows),
            'rows_matched' => 0,
            'rows_unmatched' => 0,
            'rows_ambiguous' => 0,
            'lijekovi_updated' => 0,
            'fields_updated' => 0,
            'fields_preserved' => 0,
        ];

        DB::beginTransaction();

        try {
            if ($truncate) {
                LijekRegistarZapis::query()->delete();
            }

            $progress = null;
            if (!$jsonOutput) {
                $progress = $this->output->createProgressBar(count($rows));
                $progress->start();
            }

            foreach ($rows as $idx => $row) {
                $rowNumber = $idx + 1;
                $match = $this->resolveMatch($row);

                $registarPayload = $row;
                $registarPayload['import_batch_id'] = $batchId;
                $registarPayload['import_row_number'] = $rowNumber;
                $registarPayload['match_status'] = $match['status'];
                $registarPayload['match_note'] = $match['note'];
                $registarPayload['raw_payload'] = $row['raw_payload'] ?? null;
                $registarPayload['lijek_id'] = $match['lijek']?->id;

                LijekRegistarZapis::query()->create($registarPayload);

                if ($match['status'] === 'matched' && $match['lijek']) {
                    $summary['rows_matched']++;
                    $updateStats = $this->applyToLijek($match['lijek'], $row, $allowOverwrite);
                    if ($updateStats['updated']) {
                        $summary['lijekovi_updated']++;
                        $summary['fields_updated'] += $updateStats['fields_updated'];
                    }
                    $summary['fields_preserved'] += $updateStats['fields_preserved'];
                } elseif ($match['status'] === 'ambiguous') {
                    $summary['rows_ambiguous']++;
                } else {
                    $summary['rows_unmatched']++;
                }

                if ($progress) {
                    $progress->advance();
                }
            }

            if ($progress) {
                $progress->finish();
                $this->newLine(2);
            }

            DB::commit();

            LijekCacheVersion::bump();

            $summary['batch_id'] = $batchId;
            $summary['allow_overwrite'] = $allowOverwrite;

            if ($jsonOutput) {
                $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                return self::SUCCESS;
            }

            $this->info('Registar import uspjesno zavrsen.');
            $this->line('Batch ID: ' . $batchId);
            $this->line('Matched: ' . $summary['rows_matched']);
            $this->line('Ambiguous: ' . $summary['rows_ambiguous']);
            $this->line('Unmatched: ' . $summary['rows_unmatched']);
            $this->line('Lijekovi azurirani: ' . $summary['lijekovi_updated']);
            $this->line('Polja azurirana: ' . $summary['fields_updated']);
            $this->line('Polja sacuvana (no-overwrite): ' . $summary['fields_preserved']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import nije uspio: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function applyToLijek(Lijek $lijek, array $row, bool $allowOverwrite): array
    {
        $fieldsUpdated = 0;
        $fieldsPreserved = 0;
        $changed = false;

        foreach (self::LIJEK_MAP as $sourceField => $targetField) {
            $incoming = $row[$sourceField] ?? null;
            if ($incoming === null || $incoming === '') {
                continue;
            }

            $current = $lijek->{$targetField};
            if (!$allowOverwrite && !$this->isEmptyValue($current)) {
                $fieldsPreserved++;
                continue;
            }

            if ((string) $current === (string) $incoming) {
                continue;
            }

            $lijek->{$targetField} = $incoming;
            $fieldsUpdated++;
            $changed = true;
        }

        if ($changed) {
            $lijek->save();
        }

        return [
            'updated' => $changed,
            'fields_updated' => $fieldsUpdated,
            'fields_preserved' => $fieldsPreserved,
        ];
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    private function resolveMatch(array $row): array
    {
        $sourceLijekId = $row['source_lijek_id'] ?? null;
        if ($sourceLijekId !== null) {
            $byId = Lijek::query()->where('lijek_id', (int) $sourceLijekId)->first();
            if ($byId) {
                return [
                    'status' => 'matched',
                    'lijek' => $byId,
                    'note' => 'matched_by_lijek_id',
                ];
            }
        }

        $query = Lijek::query();
        $hasMatchCriteria = false;

        if (!empty($row['atc_sifra'])) {
            $query->where('atc_sifra', $row['atc_sifra']);
            $hasMatchCriteria = true;
        }

        if (!empty($row['naziv_lijeka'])) {
            $name = mb_strtolower(trim((string) $row['naziv_lijeka']));
            $query->where(function ($q) use ($name) {
                $q->whereRaw("LOWER(TRIM(COALESCE(naziv_lijeka, ''))) = ?", [$name])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(naziv, ''))) = ?", [$name]);
            });
            $hasMatchCriteria = true;
        }

        if (!empty($row['oblik'])) {
            $shape = mb_strtolower(trim((string) $row['oblik']));
            $query->where(function ($q) use ($shape) {
                $q->whereRaw("LOWER(TRIM(COALESCE(oblik_registar, ''))) = ?", [$shape])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(oblik, ''))) = ?", [$shape])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(farmaceutski_oblik, ''))) = ?", [$shape]);
            });
            $hasMatchCriteria = true;
        }

        if (!empty($row['jacina'])) {
            $strength = mb_strtolower(trim((string) $row['jacina']));
            $query->where(function ($q) use ($strength) {
                $q->whereRaw("LOWER(TRIM(COALESCE(jacina, ''))) = ?", [$strength])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(doza, ''))) = ?", [$strength]);
            });
            $hasMatchCriteria = true;
        }

        if (!empty($row['pakovanje'])) {
            $pack = mb_strtolower(trim((string) $row['pakovanje']));
            $query->where(function ($q) use ($pack) {
                $q->whereRaw("LOWER(TRIM(COALESCE(pakovanje_registar, ''))) = ?", [$pack])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(pakovanje, ''))) = ?", [$pack]);
            });
            $hasMatchCriteria = true;
        }

        if (!$hasMatchCriteria) {
            return [
                'status' => 'unmatched',
                'lijek' => null,
                'note' => 'nema_dovoljno_polja_za_mapiranje',
            ];
        }

        $matches = $query->limit(2)->get();
        if ($matches->count() === 1) {
            return [
                'status' => 'matched',
                'lijek' => $matches->first(),
                'note' => 'matched_by_business_fields',
            ];
        }

        if ($matches->count() > 1) {
            return [
                'status' => 'ambiguous',
                'lijek' => null,
                'note' => 'vise_lijekova_odgovara_kriterijumu',
            ];
        }

        return [
            'status' => 'unmatched',
            'lijek' => null,
            'note' => 'nije_pronadjen_odgovarajuci_lijek',
        ];
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return [];
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                return [];
            }

            $mappedHeaders = array_map(fn ($header) => $this->mapIncomingKey((string) $header), $headers);
            $rows = [];

            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                $raw = [];
                $parsed = array_fill_keys(self::REGISTAR_FIELDS, null);

                foreach ($headers as $idx => $header) {
                    $value = isset($line[$idx]) ? $this->normalizeValue($line[$idx]) : null;
                    $raw[(string) $header] = $value;

                    $targetField = $mappedHeaders[$idx] ?? null;
                    if ($targetField && array_key_exists($targetField, $parsed)) {
                        $parsed[$targetField] = $this->normalizeByField($targetField, $value);
                    }
                }

                if ($this->isEmptyParsedRow($parsed)) {
                    continue;
                }

                $parsed['raw_payload'] = $raw;
                $rows[] = $parsed;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function parseXml(string $path): array
    {
        $xml = @simplexml_load_file($path, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_PARSEHUGE);
        if (!$xml) {
            return [];
        }

        $nodes = [];
        foreach ($xml->children() as $child) {
            $nodes[] = $child;
        }

        $rows = [];
        foreach ($nodes as $node) {
            $raw = [];
            foreach ($node->attributes() as $key => $value) {
                $raw[(string) $key] = $this->normalizeValue((string) $value);
            }
            foreach ($node->children() as $key => $value) {
                $raw[(string) $key] = $this->normalizeValue((string) $value);
            }

            $parsed = array_fill_keys(self::REGISTAR_FIELDS, null);
            foreach ($raw as $incomingKey => $incomingValue) {
                $targetField = $this->mapIncomingKey($incomingKey);
                if ($targetField && array_key_exists($targetField, $parsed)) {
                    $parsed[$targetField] = $this->normalizeByField($targetField, $incomingValue);
                }
            }

            if ($this->isEmptyParsedRow($parsed)) {
                continue;
            }

            $parsed['raw_payload'] = $raw;
            $rows[] = $parsed;
        }

        return $rows;
    }

    private function mapIncomingKey(string $key): ?string
    {
        $normalized = $this->normalizeKey($key);

        $map = [
            'lijekid' => 'source_lijek_id',
            'lekid' => 'source_lijek_id',
            'sourcelijekid' => 'source_lijek_id',
            'source_lijek_id' => 'source_lijek_id',
            'sourcelekid' => 'source_lijek_id',
            'atc' => 'atc_sifra',
            'atcsifra' => 'atc_sifra',
            'atc_sifra' => 'atc_sifra',
            'inn' => 'inn',
            'jidl' => 'jidl',
            'nazivlijeka' => 'naziv_lijeka',
            'nazivlek' => 'naziv_lijeka',
            'naziv' => 'naziv_lijeka',
            'proizvodjac' => 'proizvodjac',
            'proizvodac' => 'proizvodjac',
            'nosilacdozvole' => 'nosilac_dozvole',
            'oblik' => 'oblik',
            'jacina' => 'jacina',
            'pakovanje' => 'pakovanje',
            'brojdozvole' => 'broj_dozvole',
            'tiplijeka' => 'tip_lijeka',
            'podtiplijeka' => 'podtip_lijeka',
            'vaziod' => 'vazi_od',
            'vazido' => 'vazi_do',
            'datumrjesenja' => 'datum_rjesenja',
            'rezimizdavanja' => 'rezim_izdavanja',
            'posebneoznake' => 'posebne_oznake',
            'nalazprveserije' => 'nalaz_prve_serije',
            'nalaz1serijepoprethodnomrjesenju' => 'nalaz_prve_serije_prethodno_rjesenje',
            'nalazprveserijepoprethodnomrjesenju' => 'nalaz_prve_serije_prethodno_rjesenje',
        ];

        return $map[$normalized] ?? null;
    }

    private function normalizeByField(string $field, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($field === 'source_lijek_id') {
            return is_numeric($value) ? (int) $value : null;
        }

        if (in_array($field, ['vazi_od', 'vazi_do', 'datum_rjesenja'], true)) {
            return $this->parseDate($value);
        }

        return $value;
    }

    private function parseDate(?string $value): ?string
    {
        $normalized = $this->normalizeValue($value);
        if ($normalized === null) {
            return null;
        }

        $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->toDateString();
            } catch (\Throwable $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function detectDelimiter(string $firstLine): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $best = ',';
        $maxHits = -1;

        foreach ($delimiters as $delimiter) {
            $hits = substr_count($firstLine, $delimiter);
            if ($hits > $maxHits) {
                $maxHits = $hits;
                $best = $delimiter;
            }
        }

        return $best;
    }

    private function isEmptyParsedRow(array $row): bool
    {
        foreach (self::REGISTAR_FIELDS as $field) {
            if (!$this->isEmptyValue($row[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeKey(string $value): string
    {
        $ascii = Str::ascii(mb_strtolower(trim($value)));
        return preg_replace('/[^a-z0-9]+/', '', $ascii) ?? '';
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return base_path('public/lijekovi-registar.csv');
        }

        if (
            Str::startsWith($path, ['/', '\\']) ||
            preg_match('/^[A-Za-z]:\\\\/', $path) === 1
        ) {
            return $path;
        }

        return base_path($path);
    }
}
