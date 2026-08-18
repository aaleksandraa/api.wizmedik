<?php

namespace App\Services\HealthcareImport\Matchers;

use App\Services\HealthcareImport\Normalizers\CityNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CityMatcher
{
    /** @var array<string, array{id:int,naziv:string,slug:string}> */
    private array $byNormalized = [];

    public function __construct(
        private readonly CityNormalizer $normalizer = new CityNormalizer(),
    ) {
    }

    public function load(Collection $cities): void
    {
        foreach ($cities as $city) {
            $entry = [
                'id' => (int) $city->id,
                'naziv' => (string) $city->naziv,
                'slug' => (string) $city->slug,
            ];
            $this->byNormalized[$this->normalizer->normalize($city->naziv)] = $entry;
            $this->byNormalized[$this->normalizer->normalize($city->slug)] = $entry;
        }

        foreach ((array) config('healthcare-import.city_aliases', []) as $alias => $canonical) {
            $hit = $this->byNormalized[$this->normalizer->normalize($canonical)] ?? null;
            if ($hit) {
                $this->byNormalized[$this->normalizer->normalize((string) $alias)] = $hit;
            }
        }
    }

    /**
     * @return array{id:int,naziv:string,slug:string,confidence:string}|null
     */
    public function match(?string $name): ?array
    {
        $normalized = $this->normalizer->normalize((string) $name);
        if ($normalized === '') {
            return null;
        }

        if (isset($this->byNormalized[$normalized])) {
            return $this->byNormalized[$normalized] + ['confidence' => 'exact'];
        }

        return null;
    }

    public function normalize(string $value): string
    {
        return $this->normalizer->normalize($value);
    }

    public function slug(?string $name): string
    {
        return Str::slug((string) $name);
    }
}
