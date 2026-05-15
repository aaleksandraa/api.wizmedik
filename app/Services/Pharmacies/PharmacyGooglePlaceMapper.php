<?php

namespace App\Services\Pharmacies;

use Illuminate\Support\Str;

class PharmacyGooglePlaceMapper
{
    /**
     * @param array<string, mixed> $place
     * @return array<string, mixed>
     */
    public function toCandidate(array $place, string $cityName, string $citySlug): array
    {
        $openingHours = $place['regularOpeningHours'] ?? null;

        return [
            'google_place_id' => $this->stringValue($place['id'] ?? null),
            'name' => $this->displayName($place),
            'brand' => $this->displayName($place),
            'address' => $this->stringValue($place['formattedAddress'] ?? null),
            'city' => $cityName,
            'city_slug' => $citySlug,
            'phone' => $this->stringValue($place['nationalPhoneNumber'] ?? null),
            'international_phone' => $this->stringValue($place['internationalPhoneNumber'] ?? null),
            'website' => $this->stringValue($place['websiteUri'] ?? null),
            'google_maps_link' => $this->stringValue($place['googleMapsUri'] ?? null),
            'latitude' => $place['location']['latitude'] ?? null,
            'longitude' => $place['location']['longitude'] ?? null,
            'types' => $place['types'] ?? [],
            'opening_hours_json' => $openingHours,
            'working_hours' => $this->mapWorkingHours($openingHours),
            'is_24h' => $this->isTwentyFourSeven($openingHours),
        ];
    }

    /**
     * @return array<int, array<string, int|string|bool|null>>
     */
    private function mapWorkingHours(mixed $openingHours): array
    {
        if (!is_array($openingHours) || !isset($openingHours['periods']) || !is_array($openingHours['periods'])) {
            return [];
        }

        $hoursByDay = [];

        foreach ($openingHours['periods'] as $period) {
            if (!is_array($period) || !isset($period['open']) || !is_array($period['open'])) {
                continue;
            }

            $open = $period['open'];
            $close = is_array($period['close'] ?? null) ? $period['close'] : null;
            $dayOfWeek = $this->googleDayToWizMedikDay((int) ($open['day'] ?? 0));

            $hoursByDay[$dayOfWeek] = [
                'day_of_week' => $dayOfWeek,
                'open_time' => $this->googleTime($open, '00:00'),
                'close_time' => $close ? $this->googleTime($close, '23:59') : '23:59',
                'closed' => false,
            ];
        }

        ksort($hoursByDay);

        return array_values($hoursByDay);
    }

    private function isTwentyFourSeven(mixed $openingHours): bool
    {
        if (!is_array($openingHours)) {
            return false;
        }

        if (($openingHours['openNow'] ?? null) === true && count($openingHours['periods'] ?? []) === 1) {
            $period = $openingHours['periods'][0] ?? null;
            if (is_array($period) && isset($period['open']) && !isset($period['close'])) {
                return true;
            }
        }

        $mapped = $this->mapWorkingHours($openingHours);
        if (count($mapped) !== 7) {
            return false;
        }

        foreach ($mapped as $day) {
            if (($day['closed'] ?? true) || ($day['open_time'] ?? null) !== '00:00') {
                return false;
            }

            if (!in_array($day['close_time'] ?? null, ['23:59', '24:00', '00:00'], true)) {
                return false;
            }
        }

        return true;
    }

    private function displayName(array $place): ?string
    {
        $displayName = $place['displayName']['text'] ?? $place['displayName'] ?? null;

        return $this->stringValue($displayName);
    }

    private function googleDayToWizMedikDay(int $googleDay): int
    {
        return $googleDay === 0 ? 7 : $googleDay;
    }

    /**
     * @param array<string, mixed> $time
     */
    private function googleTime(array $time, string $fallback): string
    {
        $hour = $time['hour'] ?? null;
        $minute = $time['minute'] ?? 0;

        if (!is_numeric($hour) || !is_numeric($minute)) {
            return $fallback;
        }

        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    public function slugForCity(string $city): string
    {
        return Str::slug($city);
    }
}
