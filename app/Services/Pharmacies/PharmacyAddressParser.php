<?php

namespace App\Services\Pharmacies;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PharmacyAddressParser
{
    /**
     * @param array<string, mixed> $record
     * @param Collection<int, \App\Models\Grad>|array<int, mixed> $cities
     * @return array<string, mixed>
     */
    public function parse(array $record, Collection|array $cities, bool $strict = true): array
    {
        $cityIndex = $this->cityIndex($cities);
        $rawAddress = $this->stringValue($record['address_raw'] ?? null)
            ?? $this->stringValue($record['formatted_address'] ?? null)
            ?? $this->stringValue($record['address'] ?? null)
            ?? $this->stringValue($record['adresa'] ?? null);

        $parsed = [
            'address_raw' => $rawAddress,
            'address' => null,
            'postal_code' => null,
            'city' => null,
            'city_slug' => null,
            'address_quality' => 'invalid',
            'reject_reason' => null,
        ];

        if ($rawAddress === null) {
            return $this->reject($parsed, 'missing_address');
        }

        $componentResult = $this->parseComponents($record, $cityIndex, $rawAddress);
        if ($componentResult !== null) {
            $parsed = array_replace($parsed, $componentResult);
        } else {
            $parsed = array_replace($parsed, $this->parseFormattedAddress($rawAddress, $cityIndex));
        }

        if (!$this->isBosniaAndHerzegovina($record, $rawAddress)) {
            return $this->reject($parsed, 'non_ba_country');
        }

        if ($this->isPlusCodeAddress($rawAddress)) {
            return $this->reject($parsed, 'plus_code_address');
        }

        if ($strict && $parsed['city'] === null) {
            return $this->reject($parsed, 'missing_reliable_city');
        }

        if ($strict && $parsed['address'] === null) {
            return $this->reject($parsed, 'missing_clean_address');
        }

        if ($parsed['address'] === null) {
            $parsed['address'] = $this->removeCountryAndCity($rawAddress, $parsed['city'], $parsed['postal_code']);
            $parsed['address_quality'] = 'fallback';
        }

        return $parsed;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, array{name: string, slug: string}> $cityIndex
     * @return array<string, mixed>|null
     */
    private function parseComponents(array $record, array $cityIndex, string $rawAddress): ?array
    {
        $components = $record['addressComponents'] ?? $record['address_components'] ?? null;
        if (!is_array($components) || $components === []) {
            return null;
        }

        $route = $this->componentByType($components, 'route');
        $streetNumber = $this->componentByType($components, 'street_number');
        $postalCode = $this->componentByType($components, 'postal_code');
        $city = $this->resolveComponentCity($components, $cityIndex)
            ?? $this->resolveCityFromFormatted($rawAddress, $cityIndex);

        $address = trim(implode(' ', array_filter([$route, $streetNumber])));
        if ($address === '') {
            $address = $this->firstAddressSegment($rawAddress, $city['name'] ?? null, $postalCode);
        }

        return [
            'address' => $address !== '' ? $address : null,
            'postal_code' => $postalCode,
            'city' => $city['name'] ?? null,
            'city_slug' => $city['slug'] ?? null,
            'address_quality' => $route !== null ? 'structured' : 'partial',
            'reject_reason' => null,
        ];
    }

    /**
     * @param array<string, array{name: string, slug: string}> $cityIndex
     * @return array<string, mixed>
     */
    private function parseFormattedAddress(string $rawAddress, array $cityIndex): array
    {
        $withoutCountry = $this->stripCountry($rawAddress);
        $postalCode = $this->postalCodeFromText($withoutCountry);
        $city = $this->resolveCityFromFormatted($withoutCountry, $cityIndex);
        $address = $this->firstAddressSegment($withoutCountry, $city['name'] ?? null, $postalCode);

        return [
            'address' => $address !== '' ? $address : null,
            'postal_code' => $postalCode,
            'city' => $city['name'] ?? null,
            'city_slug' => $city['slug'] ?? null,
            'address_quality' => $city !== null ? 'fallback' : 'review',
            'reject_reason' => null,
        ];
    }

    /**
     * @param array<int, mixed> $components
     */
    private function resolveComponentCity(array $components, array $cityIndex): ?array
    {
        $preferredTypes = [
            'locality',
            'postal_town',
            'administrative_area_level_3',
            'administrative_area_level_2',
            'sublocality',
        ];

        foreach ($preferredTypes as $type) {
            $value = $this->componentByType($components, $type);
            $city = $value !== null ? $this->cityByName($value, $cityIndex) : null;
            if ($city !== null) {
                return $city;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function isBosniaAndHerzegovina(array $record, string $rawAddress): bool
    {
        $components = $record['addressComponents'] ?? $record['address_components'] ?? null;
        if (is_array($components)) {
            foreach ($components as $component) {
                if (!is_array($component) || !$this->hasType($component, 'country')) {
                    continue;
                }

                $short = mb_strtoupper((string) ($component['shortText'] ?? $component['short_name'] ?? ''));
                $long = mb_strtolower((string) ($component['longText'] ?? $component['long_name'] ?? ''));

                return $short === 'BA' || str_contains($long, 'bosnia');
            }
        }

        $normalized = mb_strtolower($rawAddress);
        if (preg_match('/,\s*(serbia|croatia|montenegro|srbija|hrvatska|crna gora)\s*$/iu', $normalized) === 1) {
            return false;
        }

        return str_contains($normalized, 'bosnia and herzegovina')
            || preg_match('/^ba\s*,/iu', $rawAddress) === 1;
    }

    private function isPlusCodeAddress(string $rawAddress): bool
    {
        $firstSegment = trim(explode(',', $rawAddress)[0] ?? '');

        return preg_match('/^[23456789CFGHJMPQRVWX]{4,}\+[23456789CFGHJMPQRVWX]{2,}/iu', $firstSegment) === 1;
    }

    private function firstAddressSegment(string $address, ?string $cityName, ?string $postalCode): string
    {
        $segments = array_values(array_filter(array_map(
            fn (string $segment): string => trim($segment),
            explode(',', $this->stripCountry($address))
        )));

        foreach ($segments as $segment) {
            $clean = $this->removeCityAndPostalCode($segment, $cityName, $postalCode);
            if ($clean !== '' && !$this->looksLikeCountryOrCityOnly($clean, $cityName, $postalCode)) {
                return $clean;
            }
        }

        return '';
    }

    private function removeCountryAndCity(string $address, ?string $cityName, ?string $postalCode): string
    {
        return $this->removeCityAndPostalCode($this->stripCountry($address), $cityName, $postalCode);
    }

    private function stripCountry(string $address): string
    {
        $address = preg_replace('/,\s*Bosnia and Herzegovina\s*$/iu', '', $address) ?? $address;
        $address = preg_replace('/,\s*Bosna i Hercegovina\s*$/iu', '', $address) ?? $address;
        $address = preg_replace('/^BA\s*,\s*/iu', '', $address) ?? $address;

        return trim($address, " \t\n\r\0\x0B,");
    }

    private function removeCityAndPostalCode(string $value, ?string $cityName, ?string $postalCode): string
    {
        $clean = trim($value, " \t\n\r\0\x0B,");

        if ($postalCode !== null) {
            $clean = preg_replace('/\b' . preg_quote($postalCode, '/') . '\b/u', '', $clean) ?? $clean;
        }

        if ($cityName !== null) {
            $clean = preg_replace('/\b' . preg_quote($cityName, '/') . '\b/iu', '', $clean) ?? $clean;
        }

        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean, " \t\n\r\0\x0B,");
    }

    private function looksLikeCountryOrCityOnly(string $value, ?string $cityName, ?string $postalCode): bool
    {
        $normalized = Str::slug($value);

        if ($normalized === '' || in_array($normalized, ['ba', 'bosnia-and-herzegovina', 'bosna-i-hercegovina'], true)) {
            return true;
        }

        if ($cityName !== null && $normalized === Str::slug($cityName)) {
            return true;
        }

        return $postalCode !== null && $normalized === Str::slug($postalCode);
    }

    private function postalCodeFromText(string $value): ?string
    {
        if (preg_match('/\b([78]\d{4}|88\d{3}|89\d{3}|70\d{3}|71\d{3}|72\d{3}|73\d{3}|74\d{3}|75\d{3}|76\d{3})\b/u', $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param array<int, mixed> $components
     */
    private function componentByType(array $components, string $type): ?string
    {
        foreach ($components as $component) {
            if (!is_array($component) || !$this->hasType($component, $type)) {
                continue;
            }

            return $this->stringValue($component['longText'] ?? $component['long_name'] ?? null);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $component
     */
    private function hasType(array $component, string $type): bool
    {
        return in_array($type, (array) ($component['types'] ?? []), true);
    }

    /**
     * @param array<string, array{name: string, slug: string}> $cityIndex
     * @return array{name: string, slug: string}|null
     */
    private function resolveCityFromFormatted(string $address, array $cityIndex): ?array
    {
        $segments = array_map('trim', explode(',', $this->stripCountry($address)));

        foreach (array_reverse($segments) as $segment) {
            $withoutPostal = preg_replace('/\b\d{5}\b/u', '', $segment) ?? $segment;
            $city = $this->cityByName($withoutPostal, $cityIndex);
            if ($city !== null) {
                return $city;
            }
        }

        $haystack = Str::slug($address);
        $matches = [];
        foreach ($cityIndex as $slug => $city) {
            if (preg_match('/(^|-)' . preg_quote($slug, '/') . '($|-)/u', $haystack) === 1) {
                $matches[$slug] = $city;
            }
        }

        if ($matches === []) {
            return null;
        }

        uasort($matches, fn (array $a, array $b): int => strlen($b['slug']) <=> strlen($a['slug']));

        return array_values($matches)[0];
    }

    /**
     * @param array<string, array{name: string, slug: string}> $cityIndex
     * @return array{name: string, slug: string}|null
     */
    private function cityByName(string $name, array $cityIndex): ?array
    {
        $slug = Str::slug(trim((string) preg_replace('/\s+/', ' ', $name)));

        return $slug !== '' ? ($cityIndex[$slug] ?? null) : null;
    }

    /**
     * @param Collection<int, \App\Models\Grad>|array<int, mixed> $cities
     * @return array<string, array{name: string, slug: string}>
     */
    private function cityIndex(Collection|array $cities): array
    {
        $index = [];
        foreach ($cities as $city) {
            $name = is_array($city) ? ($city['naziv'] ?? $city['name'] ?? null) : ($city->naziv ?? null);
            $slug = is_array($city) ? ($city['slug'] ?? null) : ($city->slug ?? null);

            $name = $this->stringValue($name);
            if ($name === null) {
                continue;
            }

            $slug = Str::slug($slug ?: $name);
            if ($slug === '') {
                continue;
            }

            $index[$slug] = [
                'name' => $name,
                'slug' => $slug,
            ];
        }

        return $index;
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<string, mixed>
     */
    private function reject(array $parsed, string $reason): array
    {
        $parsed['address_quality'] = 'rejected';
        $parsed['reject_reason'] = $reason;

        return $parsed;
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
