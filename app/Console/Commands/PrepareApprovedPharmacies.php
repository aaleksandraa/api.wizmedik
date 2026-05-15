<?php

namespace App\Console\Commands;

use App\Models\Grad;
use App\Services\Pharmacies\PharmacyAddressParser;
use App\Services\Pharmacies\PharmacyGooglePlacesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class PrepareApprovedPharmacies extends Command
{
    protected $signature = 'wizmedik:prepare-approved-pharmacies
        {--file= : Candidate JSON path ili naziv iz storage/app/imports/pharmacies}
        {--strict=true : Strict rezim odbacuje nepouzdane adrese}
        {--fetch-address-components=true : Dohvati Google addressComponents kada nedostaju}
        {--max-address-details=2000 : Maksimalan broj address-only Place Details poziva}
        {--output= : Output direktorij, default storage/app/imports/approved}';

    protected $description = 'Pripremi approved/review/rejected fajlove za import apoteka bez upisa u bazu.';

    public function handle(
        PharmacyAddressParser $addressParser,
        PharmacyGooglePlacesClient $googleClient
    ): int {
        $inputPath = $this->resolveInputPath((string) $this->option('file'));
        $payload = $this->readPayload($inputPath);
        $records = $payload['records'] ?? null;

        if (!is_array($records)) {
            throw new RuntimeException('JSON mora imati records niz.');
        }

        $strict = filter_var($this->option('strict'), FILTER_VALIDATE_BOOLEAN);
        $fetchAddressComponents = filter_var($this->option('fetch-address-components'), FILTER_VALIDATE_BOOLEAN);
        $maxAddressDetails = max(0, (int) $this->option('max-address-details'));
        $addressDetailsCalls = 0;
        $cities = Grad::query()->where('aktivan', true)->orderBy('naziv')->get(['naziv', 'slug']);

        if ($cities->isEmpty()) {
            throw new RuntimeException('Nema aktivnih gradova u tabeli gradovi. Pokrenite CitiesSeeder prije pripreme approved fajla.');
        }

        $approved = [];
        $rejected = [];
        $reviewRows = [];

        $progress = $this->output->createProgressBar(count($records));
        $progress->start();

        foreach ($records as $record) {
            if (!is_array($record)) {
                $rejected[] = [
                    'reject_reason' => 'invalid_record',
                    'record' => $record,
                ];
                $progress->advance();
                continue;
            }

            $record = $this->normalizeRawAddressFields($record);

            if (
                $fetchAddressComponents
                && $addressDetailsCalls < $maxAddressDetails
                && !isset($record['addressComponents'])
                && !isset($record['address_components'])
                && $this->stringValue($record['google_place_id'] ?? null) !== null
            ) {
                $details = $googleClient->addressDetails((string) $record['google_place_id']);
                $addressDetailsCalls++;
                $record = $this->mergeAddressDetails($record, $details);
            }

            $parsed = $addressParser->parse($record, $cities, $strict);
            $prepared = array_replace($record, [
                'address_raw' => $parsed['address_raw'],
                'formatted_address' => $record['formatted_address'] ?? $parsed['address_raw'],
                'address' => $parsed['address'],
                'postal_code' => $parsed['postal_code'],
                'city' => $parsed['city'] ?? $record['city'] ?? null,
                'city_slug' => $parsed['city_slug'] ?? $record['city_slug'] ?? null,
                'address_quality' => $parsed['address_quality'],
                'reject_reason' => $parsed['reject_reason'],
            ]);

            if ($parsed['reject_reason'] === null) {
                unset($prepared['reject_reason']);
                $approved[] = $prepared;
            } else {
                $rejected[] = $prepared;
            }

            $reviewRows[] = $this->reviewRow($prepared, $parsed['reject_reason'] === null ? 'approved' : 'rejected');
            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        $paths = $this->writeOutputs($inputPath, $payload, $approved, $rejected, $reviewRows, [
            'input_records' => count($records),
            'approved' => count($approved),
            'rejected' => count($rejected),
            'address_details_calls' => $addressDetailsCalls,
            'strict' => $strict,
        ]);

        $this->info('Priprema approved fajlova zavrsena.');
        $this->line('Approved JSON: ' . $paths['approved']);
        $this->line('Review CSV: ' . $paths['review']);
        $this->line('Rejected JSON: ' . $paths['rejected']);
        $this->line('Approved: ' . count($approved));
        $this->line('Rejected: ' . count($rejected));
        $this->line("Address-only Place Details pozivi: {$addressDetailsCalls}");
        $this->warn('Baza nije mijenjana. Seeder pokrenuti tek nakon pregleda approved fajla.');

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function normalizeRawAddressFields(array $record): array
    {
        $rawAddress = $this->stringValue($record['address_raw'] ?? null)
            ?? $this->stringValue($record['formatted_address'] ?? null)
            ?? $this->stringValue($record['address'] ?? null)
            ?? $this->stringValue($record['adresa'] ?? null);

        if ($rawAddress !== null) {
            $record['address_raw'] = $rawAddress;
            $record['formatted_address'] = $record['formatted_address'] ?? $rawAddress;
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function mergeAddressDetails(array $record, array $details): array
    {
        if (isset($details['addressComponents']) && is_array($details['addressComponents'])) {
            $record['addressComponents'] = $details['addressComponents'];
        }

        $formatted = $this->stringValue($details['formattedAddress'] ?? null);
        if ($formatted !== null) {
            $record['formatted_address'] = $formatted;
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function reviewRow(array $record, string $status): array
    {
        return [
            'status' => $status,
            'reject_reason' => $record['reject_reason'] ?? '',
            'google_place_id' => $record['google_place_id'] ?? '',
            'name' => $record['name'] ?? '',
            'city' => $record['city'] ?? '',
            'city_slug' => $record['city_slug'] ?? '',
            'address' => $record['address'] ?? '',
            'postal_code' => $record['postal_code'] ?? '',
            'address_quality' => $record['address_quality'] ?? '',
            'address_raw' => $record['address_raw'] ?? '',
            'phone' => $record['phone'] ?? '',
            'website' => $record['website'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $approved
     * @param array<int, array<string, mixed>> $rejected
     * @param array<int, array<string, mixed>> $reviewRows
     * @param array<string, mixed> $stats
     * @return array{approved: string, rejected: string, review: string}
     */
    private function writeOutputs(string $inputPath, array $payload, array $approved, array $rejected, array $reviewRows, array $stats): array
    {
        $directory = $this->resolveOutputDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $baseName = pathinfo($inputPath, PATHINFO_FILENAME);
        $timestamp = now('Europe/Sarajevo')->format('Ymd-His');
        $outputBase = "{$baseName}-prepared-{$timestamp}";
        $approvedPath = $directory . DIRECTORY_SEPARATOR . "{$outputBase}-approved.json";
        $rejectedPath = $directory . DIRECTORY_SEPARATOR . "{$outputBase}-rejected.json";
        $reviewPath = $directory . DIRECTORY_SEPARATOR . "{$outputBase}-review.csv";

        $common = [
            'source_file' => $inputPath,
            'generated_at' => now()->toIso8601String(),
            'import_batch' => "{$outputBase}-approved",
            'stats' => $stats,
        ];

        file_put_contents($approvedPath, json_encode(array_replace($common, [
            'records' => $approved,
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        file_put_contents($rejectedPath, json_encode(array_replace($common, [
            'records' => $rejected,
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->writeReviewCsv($reviewPath, $reviewRows);

        return [
            'approved' => $approvedPath,
            'rejected' => $rejectedPath,
            'review' => $reviewPath,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function writeReviewCsv(string $path, array $rows): void
    {
        $csv = fopen($path, 'wb');
        $headers = [
            'status',
            'reject_reason',
            'google_place_id',
            'name',
            'city',
            'city_slug',
            'address',
            'postal_code',
            'address_quality',
            'address_raw',
            'phone',
            'website',
        ];

        fputcsv($csv, $headers);
        foreach ($rows as $row) {
            fputcsv($csv, array_map(
                fn (string $header): mixed => $row[$header] ?? '',
                $headers
            ));
        }

        fclose($csv);
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
            $candidates[] = storage_path('app/imports/approved/' . $file);
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

    private function resolveOutputDirectory(): string
    {
        $output = trim((string) $this->option('output'));
        if ($output === '') {
            return storage_path('app/imports/approved');
        }

        if (Str::startsWith($output, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $output) === 1) {
            return $output;
        }

        return base_path($output);
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
