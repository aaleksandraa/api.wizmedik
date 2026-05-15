<?php

namespace App\Console\Commands;

use App\Services\Pharmacies\PharmacyGooglePlaceMapper;
use App\Services\Pharmacies\PharmacyGooglePlacesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class EnrichPharmaciesGoogle extends Command
{
    protected $signature = 'wizmedik:enrich-pharmacies-google
        {--file= : Candidate JSON path ili naziv iz storage/app/imports/pharmacies}
        {--merge-from= : Opcionalni enriched JSON iz kojeg se podaci spajaju po google_place_id bez Google poziva}
        {--limit=300 : Maksimalan broj Place Details poziva}
        {--offset=0 : Preskoci prvih N zapisa}
        {--skip-enriched=true : Preskoci zapise koji vec imaju telefon, website ili radno vrijeme}
        {--overwrite : Prepisati ulazni JSON umjesto pravljenja novog fajla}';

    protected $description = 'Dopuni Google Places candidate JSON telefonima, website-om i radnim vremenom bez ponovnog Text Search-a.';

    public function handle(
        PharmacyGooglePlacesClient $client,
        PharmacyGooglePlaceMapper $mapper
    ): int {
        $inputPath = $this->resolveInputPath((string) $this->option('file'));
        $payload = $this->readPayload($inputPath);
        $records = $payload['records'] ?? null;

        if (!is_array($records)) {
            throw new RuntimeException('JSON mora imati records niz.');
        }

        $mergedRecords = 0;
        $mergeFrom = trim((string) $this->option('merge-from'));
        if ($mergeFrom !== '') {
            $mergePath = $this->resolveInputPath($mergeFrom);
            $mergePayload = $this->readPayload($mergePath);
            $records = $this->mergeFromExisting($records, $mergePayload['records'] ?? [], $mergedRecords);
        }

        $limit = max(0, (int) $this->option('limit'));
        $offset = max(0, (int) $this->option('offset'));
        $skipEnriched = filter_var($this->option('skip-enriched'), FILTER_VALIDATE_BOOLEAN);
        $detailCalls = 0;
        $processed = 0;

        $progress = $this->output->createProgressBar($limit);
        $progress->start();

        foreach ($records as $index => $record) {
            if ($index < $offset || $detailCalls >= $limit) {
                continue;
            }

            if (!is_array($record)) {
                continue;
            }

            if ($skipEnriched && $this->alreadyEnriched($record)) {
                continue;
            }

            $placeId = $this->stringValue($record['google_place_id'] ?? null);
            if ($placeId === null) {
                continue;
            }

            $details = $client->details($placeId);
            $detailCalls++;
            $processed++;

            $mapped = $mapper->toCandidate(
                $details,
                $this->stringValue($record['city'] ?? null) ?? '',
                $this->stringValue($record['city_slug'] ?? null) ?? ''
            );

            $records[$index] = array_replace($record, array_filter(
                $mapped,
                fn (mixed $value): bool => $value !== null && $value !== []
            ));

            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        $payload['records'] = $records;
        $payload['enrichment_runs'][] = [
            'generated_at' => now()->toIso8601String(),
            'offset' => $offset,
            'limit' => $limit,
            'detail_calls' => $detailCalls,
            'processed_records' => $processed,
            'merged_records' => $mergedRecords,
        ];
        $payload['stats']['detail_calls'] = (int) ($payload['stats']['detail_calls'] ?? 0) + $detailCalls;

        $outputPath = $this->option('overwrite')
            ? $inputPath
            : $this->enrichedPath($inputPath);

        file_put_contents($outputPath, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        $csvPath = preg_replace('/\.json$/', '.csv', $outputPath) ?: $outputPath . '.csv';
        $this->writeCsv($csvPath, $records);

        $this->info('Enrichment zavrsen.');
        $this->line('JSON: ' . $outputPath);
        $this->line('CSV: ' . $csvPath);
        $this->line("Place Details pozivi: {$detailCalls}");
        if ($mergedRecords > 0) {
            $this->line("Spojeno iz postojeceg JSON-a: {$mergedRecords}");
        }

        return self::SUCCESS;
    }

    private function resolveInputPath(string $file): string
    {
        $file = trim($file);
        if ($file === '') {
            throw new RuntimeException('Obavezno proslijedite --file.');
        }

        $candidates = [];

        if (Str::startsWith($file, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $file) === 1) {
            $candidates[] = $file;
        } else {
            $candidates[] = storage_path('app/imports/pharmacies/' . $file);
            $candidates[] = base_path($file);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Candidate JSON nije pronadjen: ' . $file);
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Neispravan JSON: ' . $path);
        }

        return $decoded;
    }

    private function enrichedPath(string $inputPath): string
    {
        $timestamp = now('Europe/Sarajevo')->format('Ymd-His');

        return preg_replace('/\.json$/', "-enriched-{$timestamp}.json", $inputPath)
            ?: $inputPath . "-enriched-{$timestamp}.json";
    }

    /**
     * @param array<string, mixed> $record
     */
    private function alreadyEnriched(array $record): bool
    {
        if ($this->stringValue($record['phone'] ?? null) !== null) {
            return true;
        }

        if ($this->stringValue($record['international_phone'] ?? null) !== null) {
            return true;
        }

        if ($this->stringValue($record['website'] ?? null) !== null) {
            return true;
        }

        return isset($record['working_hours'])
            && is_array($record['working_hours'])
            && $record['working_hours'] !== [];
    }

    /**
     * @param array<int, mixed> $records
     * @param mixed $sourceRecords
     * @return array<int, mixed>
     */
    private function mergeFromExisting(array $records, mixed $sourceRecords, int &$mergedRecords): array
    {
        if (!is_array($sourceRecords)) {
            return $records;
        }

        $sourceByPlaceId = [];
        foreach ($sourceRecords as $sourceRecord) {
            if (!is_array($sourceRecord)) {
                continue;
            }

            $placeId = $this->stringValue($sourceRecord['google_place_id'] ?? null);
            if ($placeId !== null) {
                $sourceByPlaceId[$placeId] = $sourceRecord;
            }
        }

        $mergeFields = [
            'name',
            'brand',
            'address',
            'phone',
            'international_phone',
            'website',
            'google_maps_link',
            'latitude',
            'longitude',
            'types',
            'opening_hours_json',
            'working_hours',
            'is_24h',
        ];

        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                continue;
            }

            $placeId = $this->stringValue($record['google_place_id'] ?? null);
            if ($placeId === null || !isset($sourceByPlaceId[$placeId])) {
                continue;
            }

            $changed = false;
            foreach ($mergeFields as $field) {
                $value = $sourceByPlaceId[$placeId][$field] ?? null;
                if ($value === null || $value === []) {
                    continue;
                }

                $record[$field] = $value;
                $changed = true;
            }

            if ($changed) {
                $records[$index] = $record;
                $mergedRecords++;
            }
        }

        return $records;
    }

    /**
     * @param array<int, mixed> $records
     */
    private function writeCsv(string $path, array $records): void
    {
        $csv = fopen($path, 'wb');
        fputcsv($csv, [
            'google_place_id',
            'name',
            'address',
            'city',
            'phone',
            'international_phone',
            'website',
            'latitude',
            'longitude',
            'google_maps_link',
            'is_24h',
        ]);

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            fputcsv($csv, [
                $record['google_place_id'] ?? '',
                $record['name'] ?? '',
                $record['address'] ?? '',
                $record['city'] ?? '',
                $record['phone'] ?? '',
                $record['international_phone'] ?? '',
                $record['website'] ?? '',
                $record['latitude'] ?? '',
                $record['longitude'] ?? '',
                $record['google_maps_link'] ?? '',
                !empty($record['is_24h']) ? '1' : '0',
            ]);
        }

        fclose($csv);
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
