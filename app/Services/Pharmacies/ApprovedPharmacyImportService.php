<?php

namespace App\Services\Pharmacies;

use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\ApotekaRadnoVrijeme;
use App\Models\Grad;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ApprovedPharmacyImportService
{
    /**
     * @return array{inserted: int, skipped: int, failed: int, total: int}
     */
    public function importFromFile(string $path, ?string $adminEmail = null, bool $publishNew = true): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Approved pharmacy import fajl nije pronadjen: {$path}");
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (!is_array($payload)) {
            throw new RuntimeException("Approved pharmacy import fajl nije validan JSON: {$path}");
        }

        return $this->importPayload($payload, $adminEmail, $publishNew, pathinfo($path, PATHINFO_FILENAME));
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $payload
     * @return array{inserted: int, skipped: int, failed: int, total: int}
     */
    public function importPayload(array $payload, ?string $adminEmail = null, bool $publishNew = true, ?string $fallbackBatch = null): array
    {
        $records = $this->recordsFromPayload($payload);
        $admin = $this->resolveAdmin($adminEmail);
        $defaultCity = $this->stringValue($payload['city'] ?? null);
        $defaultCitySlug = $this->stringValue($payload['city_slug'] ?? null);
        $importBatch = $this->limitedString(
            $this->stringValue($payload['import_batch'] ?? null) ?? $fallbackBatch ?? 'approved-pharmacies',
            120
        ) ?? 'approved-pharmacies';
        $counts = [
            'inserted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'total' => count($records),
        ];

        foreach ($records as $record) {
            if (!is_array($record)) {
                $counts['failed']++;
                continue;
            }

            try {
                $normalized = $this->normalizeRecord($record, $defaultCity, $defaultCitySlug);
                if ($normalized === null) {
                    $counts['failed']++;
                    continue;
                }

                if ($this->findExistingBranch($normalized)) {
                    $counts['skipped']++;
                    continue;
                }

                DB::transaction(function () use ($normalized, $admin, $publishNew, $importBatch): void {
                    $this->createPharmacy($normalized, $admin, $publishNew, $importBatch);
                });

                $counts['inserted']++;
            } catch (\Throwable) {
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $payload
     * @return array<int, mixed>
     */
    private function recordsFromPayload(array $payload): array
    {
        if (isset($payload['records']) && is_array($payload['records'])) {
            return array_values($payload['records']);
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    private function resolveAdmin(?string $adminEmail): User
    {
        $email = $this->normalizeEmail($adminEmail ?: env('PHARMACY_IMPORT_ADMIN_EMAIL'));
        if ($email === null) {
            throw new RuntimeException('PHARMACY_IMPORT_ADMIN_EMAIL nije postavljen.');
        }

        $admin = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$admin) {
            throw new RuntimeException("Admin korisnik za import nije pronadjen: {$email}");
        }

        return $admin;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>|null
     */
    private function normalizeRecord(array $record, ?string $defaultCity, ?string $defaultCitySlug): ?array
    {
        $name = $this->stringValue($record['name'] ?? $record['naziv'] ?? null);
        $address = $this->stringValue($record['address'] ?? $record['adresa'] ?? null);
        $cityName = $this->stringValue($record['city'] ?? $record['grad'] ?? null) ?? $defaultCity;

        if ($name === null || $address === null || $cityName === null) {
            return null;
        }

        $city = $this->resolveOrCreateCity($cityName, $this->stringValue($record['city_slug'] ?? null) ?? $defaultCitySlug);
        $phone = $this->stringValue($record['phone'] ?? $record['telefon'] ?? null)
            ?? $this->stringValue($record['national_phone'] ?? null)
            ?? $this->stringValue($record['international_phone'] ?? null);

        return [
            'google_place_id' => $this->stringValue($record['google_place_id'] ?? $record['place_id'] ?? null),
            'brand' => $this->limitedString($this->stringValue($record['brand'] ?? $record['naziv_brenda'] ?? null) ?? $name, 255),
            'name' => $this->limitedString($name, 255),
            'address' => $this->limitedString($address, 255),
            'city' => $city,
            'city_name' => $city->naziv,
            'postal_code' => $this->limitedString($this->stringValue($record['postal_code'] ?? $record['postanski_broj'] ?? null), 20),
            'phone' => $this->limitedString($phone, 64),
            'email' => $this->normalizeEmail($record['email'] ?? null),
            'website' => $this->urlValue($record['website'] ?? null, 255),
            'international_phone' => $this->limitedString($this->stringValue($record['international_phone'] ?? null), 64),
            'short_description' => $this->stringValue($record['short_description'] ?? $record['kratki_opis'] ?? null),
            'google_maps_link' => $this->urlValue($record['google_maps_link'] ?? $record['google_maps_url'] ?? null, 500),
            'latitude' => $this->numericValue($record['latitude'] ?? $record['lat'] ?? null),
            'longitude' => $this->numericValue($record['longitude'] ?? $record['lng'] ?? null),
            'is_24h' => filter_var($record['is_24h'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'opening_hours_json' => is_array($record['opening_hours_json'] ?? null) ? $record['opening_hours_json'] : null,
            'working_hours' => is_array($record['working_hours'] ?? null) ? $record['working_hours'] : [],
        ];
    }

    private function resolveOrCreateCity(string $cityName, ?string $citySlug): Grad
    {
        $slug = $citySlug ? Str::slug($citySlug) : Str::slug($cityName);
        $normalized = mb_strtolower(trim($cityName));

        $city = Grad::query()
            ->where('slug', $slug)
            ->orWhereRaw('LOWER(naziv) = ?', [$normalized])
            ->first();

        if ($city) {
            return $city;
        }

        return Grad::query()->create([
            'naziv' => $cityName,
            'slug' => $slug,
            'opis' => "Apoteke u gradu {$cityName}.",
            'detaljni_opis' => "Pregled apoteka, lokacija i radnog vremena za {$cityName}.",
            'aktivan' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function findExistingBranch(array $record): ?ApotekaPoslovnica
    {
        $googlePlaceId = $record['google_place_id'] ?? null;
        if (is_string($googlePlaceId) && $googlePlaceId !== '') {
            $branch = ApotekaPoslovnica::query()
                ->where('google_place_id', $googlePlaceId)
                ->first();
            if ($branch) {
                return $branch;
            }
        }

        $phone = $this->normalizePhone($record['phone'] ?? null);
        if ($phone !== null) {
            $branch = ApotekaPoslovnica::query()
                ->whereNotNull('telefon')
                ->get(['id', 'telefon'])
                ->first(fn (ApotekaPoslovnica $candidate) => $this->normalizePhone($candidate->telefon) === $phone);

            if ($branch) {
                return ApotekaPoslovnica::query()->find($branch->id);
            }
        }

        $city = $record['city'];
        $name = mb_strtolower($this->squashWhitespace((string) $record['name']));
        $address = mb_strtolower($this->squashWhitespace((string) $record['address']));

        return ApotekaPoslovnica::query()
            ->where('grad_id', $city->id)
            ->whereRaw('LOWER(naziv) = ?', [$name])
            ->whereRaw('LOWER(adresa) = ?', [$address])
            ->first();
    }

    /**
     * @param array<string, mixed> $record
     */
    private function createPharmacy(array $record, User $admin, bool $publishNew, string $importBatch): void
    {
        $now = now();
        $verifiedAt = $publishNew ? $now : null;

        $firm = ApotekaFirma::query()->create([
            'owner_user_id' => null,
            'naziv_brenda' => $record['brand'],
            'pravni_naziv' => null,
            'telefon' => $record['phone'],
            'email' => $record['email'],
            'website' => $record['website'],
            'opis' => null,
            'status' => $publishNew ? 'verified' : 'pending',
            'is_active' => true,
            'verified_at' => $verifiedAt,
            'verified_by' => $publishNew ? $admin->id : null,
            'source' => 'google_places',
            'imported_at' => $now,
            'imported_by' => $admin->id,
            'import_batch' => $importBatch,
        ]);

        $branch = ApotekaPoslovnica::query()->create([
            'firma_id' => $firm->id,
            'naziv' => $record['name'],
            'slug' => ApotekaPoslovnica::generateUniqueSlug($record['name']),
            'grad_id' => $record['city']->id,
            'grad_naziv' => $record['city_name'],
            'adresa' => $record['address'],
            'postanski_broj' => $record['postal_code'],
            'latitude' => $record['latitude'],
            'longitude' => $record['longitude'],
            'telefon' => $record['phone'],
            'email' => $record['email'],
            'kratki_opis' => $record['short_description'],
            'galerija_slike' => [],
            'google_maps_link' => $record['google_maps_link'],
            'ima_dostavu' => false,
            'ima_parking' => false,
            'pristup_invalidima' => false,
            'is_24h' => (bool) $record['is_24h'],
            'is_active' => true,
            'is_verified' => $publishNew,
            'verified_at' => $verifiedAt,
            'verified_by' => $publishNew ? $admin->id : null,
            'google_place_id' => $record['google_place_id'],
            'international_phone' => $record['international_phone'],
            'opening_hours_json' => $record['opening_hours_json'],
            'source' => 'google_places',
            'imported_at' => $now,
            'imported_by' => $admin->id,
            'import_batch' => $importBatch,
        ]);

        $this->syncWorkingHours($branch, $record['working_hours'], (bool) $record['is_24h']);
    }

    /**
     * @param array<int, mixed> $hours
     */
    private function syncWorkingHours(ApotekaPoslovnica $branch, array $hours, bool $is24h): void
    {
        $normalized = $this->normalizeWorkingHours($hours, $is24h);

        foreach ($normalized as $entry) {
            ApotekaRadnoVrijeme::query()->updateOrCreate(
                [
                    'poslovnica_id' => $branch->id,
                    'day_of_week' => $entry['day_of_week'],
                ],
                [
                    'open_time' => $entry['closed'] ? null : $entry['open_time'],
                    'close_time' => $entry['closed'] ? null : $entry['close_time'],
                    'closed' => $entry['closed'],
                ]
            );
        }
    }

    /**
     * @param array<int, mixed> $hours
     * @return array<int, array{day_of_week: int, open_time: string|null, close_time: string|null, closed: bool}>
     */
    private function normalizeWorkingHours(array $hours, bool $is24h): array
    {
        $byDay = [];
        foreach ($hours as $entry) {
            if (!is_array($entry) || !isset($entry['day_of_week'])) {
                continue;
            }

            $day = (int) $entry['day_of_week'];
            if ($day < 1 || $day > 7) {
                continue;
            }

            $closed = filter_var($entry['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $byDay[$day] = [
                'day_of_week' => $day,
                'open_time' => $closed ? null : $this->normalizeTime($entry['open_time'] ?? null, $is24h ? '00:00' : '08:00'),
                'close_time' => $closed ? null : $this->normalizeTime($entry['close_time'] ?? null, $is24h ? '23:59' : ($day === 6 ? '14:00' : '20:00')),
                'closed' => $closed,
            ];
        }

        $result = [];
        for ($day = 1; $day <= 7; $day++) {
            if (isset($byDay[$day])) {
                $result[] = $byDay[$day];
                continue;
            }

            $closed = !$is24h && $day === 7;
            $result[] = [
                'day_of_week' => $day,
                'open_time' => $closed ? null : ($is24h ? '00:00' : '08:00'),
                'close_time' => $closed ? null : ($is24h ? '23:59' : ($day === 6 ? '14:00' : '20:00')),
                'closed' => $closed,
            ];
        }

        return $result;
    }

    private function normalizeTime(mixed $value, string $fallback): string
    {
        $time = $this->stringValue($value);
        if ($time === null) {
            return $fallback;
        }

        $time = substr($time, 0, 5);

        return $time === '24:00' ? '23:59' : $time;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = $this->stringValue($phone);
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = $this->stringValue($email);

        return $email !== null ? mb_strtolower($email) : null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function limitedString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }

    private function urlValue(mixed $value, int $maxLength): ?string
    {
        $url = $this->stringValue($value);
        if ($url === null || mb_strlen($url) > $maxLength) {
            return null;
        }

        return $url;
    }

    private function numericValue(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function squashWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
