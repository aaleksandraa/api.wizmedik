<?php

namespace App\Console\Commands;

use App\Models\Grad;
use App\Services\Pharmacies\PharmacyGooglePlaceMapper;
use App\Services\Pharmacies\PharmacyGooglePlacesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FetchPharmaciesGoogle extends Command
{
    protected $signature = 'wizmedik:fetch-pharmacies-google
        {--city=banja-luka : Grad naziv ili slug}
        {--all-cities : Povuci kandidate za sve aktivne gradove iz tabele gradovi}
        {--max-cities= : Maksimalan broj gradova za --all-cities}
        {--limit=100 : Maksimalan broj kandidata}
        {--details=true : Da li pozivati Place Details za deduplicirane kandidate}
        {--pages=3 : Maksimalan broj Google Text Search stranica po query-ju}
        {--query-set=full : Query set: compact ili full}
        {--max-text-calls=300 : Sigurnosni limit za Text Search pozive}
        {--max-detail-calls=300 : Sigurnosni limit za Place Details pozive}
        {--output= : Opcionalni output direktorij}
        {--queries=* : Opcionalni custom Google Text Search query}';

    protected $description = 'Povuci Google Places kandidate za apoteke i sacuvaj JSON/CSV bez upisa u bazu.';

    public function handle(
        PharmacyGooglePlacesClient $client,
        PharmacyGooglePlaceMapper $mapper
    ): int {
        $cityInput = trim((string) $this->option('city'));
        $limit = max(1, (int) $this->option('limit'));
        $withDetails = filter_var($this->option('details'), FILTER_VALIDATE_BOOLEAN);
        $maxPages = max(1, (int) $this->option('pages'));
        $maxTextCalls = max(1, (int) $this->option('max-text-calls'));
        $maxDetailCalls = max(0, (int) $this->option('max-detail-calls'));
        $cities = $this->cities($cityInput);

        $placesById = [];
        $placeCity = [];
        $queriesByCity = [];
        $textCalls = 0;

        $this->info('Pripremam Google Places kandidate za: ' . ($this->option('all-cities') ? 'sve gradove' : $cities->first()['name']));

        foreach ($cities as $cityEntry) {
            if (count($placesById) >= $limit || $textCalls >= $maxTextCalls) {
                break;
            }

            $cityName = $cityEntry['name'];
            $citySlug = $cityEntry['slug'];
            $queries = $this->queries($cityName);
            $queriesByCity[$citySlug] = $queries;

            foreach ($queries as $query) {
                if (count($placesById) >= $limit || $textCalls >= $maxTextCalls) {
                    break;
                }

                $this->line("Text Search: {$query}");
                $pageToken = null;

                for ($page = 1; $page <= $maxPages; $page++) {
                    if (count($placesById) >= $limit || $textCalls >= $maxTextCalls) {
                        break;
                    }

                    $remaining = $limit - count($placesById);
                    $result = $client->textSearch($query, min($remaining, 20), $pageToken);
                    $textCalls++;

                    foreach ($result['places'] as $place) {
                        $placeId = is_array($place) ? ($place['id'] ?? null) : null;
                        if (!is_string($placeId) || trim($placeId) === '') {
                            continue;
                        }

                        if (!isset($placesById[$placeId])) {
                            $placesById[$placeId] = $place;
                            $placeCity[$placeId] = [
                                'name' => $cityName,
                                'slug' => $citySlug,
                            ];
                        }

                        if (count($placesById) >= $limit) {
                            break;
                        }
                    }

                    $pageToken = $result['next_page_token'];
                    if ($pageToken === null) {
                        break;
                    }
                }
            }
        }

        $candidates = [];
        $detailCalls = 0;
        $progress = $this->output->createProgressBar(count($placesById));
        $progress->start();

        foreach ($placesById as $placeId => $place) {
            if ($withDetails && $detailCalls < $maxDetailCalls) {
                $place = array_replace_recursive($place, $client->details($placeId));
                $detailCalls++;
            }

            $cityForPlace = $placeCity[$placeId] ?? $cities->first();
            $candidates[] = $mapper->toCandidate($place, $cityForPlace['name'], $cityForPlace['slug']);
            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);

        $reportSlug = $this->option('all-cities') ? 'bosna-i-hercegovina' : $cities->first()['slug'];
        $reportName = $this->option('all-cities') ? 'Bosna i Hercegovina' : $cities->first()['name'];
        $paths = $this->writeReports($reportSlug, $reportName, $queriesByCity, $candidates, [
            'text_calls' => $textCalls,
            'detail_calls' => $detailCalls,
            'city_count' => $cities->count(),
        ]);

        $this->info('Kandidati sacuvani.');
        $this->line('JSON: ' . $paths['json']);
        $this->line('CSV: ' . $paths['csv']);
        $this->line("Text Search pozivi: {$textCalls}");
        $this->line("Place Details pozivi: {$detailCalls}");
        $this->warn('Seeder NE zove Google API. Pregledajte JSON/CSV, napravite approved JSON i tek onda pokrenite seeder.');

        return self::SUCCESS;
    }

    private function resolveCity(string $city): ?Grad
    {
        if ($city === '') {
            return null;
        }

        $normalized = mb_strtolower($city);

        return Grad::query()
            ->where('slug', Str::slug($city))
            ->orWhereRaw('LOWER(naziv) = ?', [$normalized])
            ->first();
    }

    /**
     * @return Collection<int, array{name: string, slug: string}>
     */
    private function cities(string $cityInput): Collection
    {
        if (!$this->option('all-cities')) {
            $city = $this->resolveCity($cityInput);
            $cityName = $city?->naziv ?? $this->humanizeCity($cityInput);

            return collect([[
                'name' => $cityName,
                'slug' => $city?->slug ?? Str::slug($cityName),
            ]]);
        }

        $query = Grad::query()
            ->where('aktivan', true)
            ->orderBy('naziv');

        $maxCities = $this->option('max-cities');
        if (is_numeric($maxCities) && (int) $maxCities > 0) {
            $query->limit((int) $maxCities);
        }

        $cities = $query->get(['naziv', 'slug'])
            ->map(fn (Grad $city) => [
                'name' => $city->naziv,
                'slug' => $city->slug ?: Str::slug($city->naziv),
            ])
            ->values();

        if ($cities->isEmpty()) {
            throw new \RuntimeException('Nema aktivnih gradova u tabeli gradovi. Pokrenite CitiesSeeder prije --all-cities fetch-a.');
        }

        return $cities;
    }

    private function humanizeCity(string $city): string
    {
        $value = str_replace(['-', '_'], ' ', trim($city));

        return Str::title($value !== '' ? $value : 'Banja Luka');
    }

    /**
     * @return string[]
     */
    private function queries(string $cityName): array
    {
        $customQueries = array_values(array_filter(array_map(
            fn ($query) => trim((string) $query),
            (array) $this->option('queries')
        )));

        if ($customQueries !== []) {
            return $customQueries;
        }

        return [
            "apoteka {$cityName}",
            "apoteke {$cityName}",
            ...($this->option('query-set') === 'compact'
                ? []
                : [
                    "ljekarna {$cityName}",
                    "pharmacy {$cityName} Bosnia and Herzegovina",
                    "apoteka {$cityName} Bosnia and Herzegovina",
                ]),
        ];
    }

    /**
     * @param array<string, string[]> $queries
     * @param array<int, array<string, mixed>> $candidates
     * @param array<string, int> $stats
     * @return array{json: string, csv: string}
     */
    private function writeReports(string $citySlug, string $cityName, array $queries, array $candidates, array $stats): array
    {
        $directory = $this->resolveOutputDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $timestamp = now('Europe/Sarajevo')->format('Ymd-His');
        $baseName = "{$citySlug}-candidates-{$timestamp}";
        $jsonPath = $directory . DIRECTORY_SEPARATOR . $baseName . '.json';
        $csvPath = $directory . DIRECTORY_SEPARATOR . $baseName . '.csv';

        file_put_contents($jsonPath, json_encode([
            'city' => $cityName,
            'city_slug' => $citySlug,
            'generated_at' => now()->toIso8601String(),
            'queries' => $queries,
            'stats' => $stats,
            'records' => $candidates,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $csv = fopen($csvPath, 'wb');
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

        foreach ($candidates as $candidate) {
            fputcsv($csv, [
                $candidate['google_place_id'] ?? '',
                $candidate['name'] ?? '',
                $candidate['address'] ?? '',
                $candidate['city'] ?? '',
                $candidate['phone'] ?? '',
                $candidate['international_phone'] ?? '',
                $candidate['website'] ?? '',
                $candidate['latitude'] ?? '',
                $candidate['longitude'] ?? '',
                $candidate['google_maps_link'] ?? '',
                !empty($candidate['is_24h']) ? '1' : '0',
            ]);
        }

        fclose($csv);

        return [
            'json' => $jsonPath,
            'csv' => $csvPath,
        ];
    }

    private function resolveOutputDirectory(): string
    {
        $output = trim((string) $this->option('output'));

        if ($output === '') {
            return storage_path('app/imports/pharmacies');
        }

        if (Str::startsWith($output, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $output) === 1) {
            return $output;
        }

        return base_path($output);
    }
}
