<?php

namespace App\Services\HealthcareImport\Matchers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SpecialtyMatcher
{
    /** @var array<string, array{id:int,naziv:string,slug:string}> */
    private array $bySlug = [];

    /** @var array<string, array{id:int,naziv:string,slug:string}> */
    private array $byName = [];

    public function load(Collection $specialties): void
    {
        foreach ($specialties as $specialty) {
            $entry = [
                'id' => (int) $specialty->id,
                'naziv' => (string) $specialty->naziv,
                'slug' => (string) $specialty->slug,
            ];
            $this->bySlug[mb_strtolower($specialty->slug)] = $entry;
            $this->byName[$this->normalizeName($specialty->naziv)] = $entry;
        }

        foreach ((array) config('healthcare-import.specialty_aliases', []) as $alias => $slug) {
            $hit = $this->bySlug[mb_strtolower((string) $slug)] ?? null;
            if ($hit) {
                $this->byName[$this->normalizeName((string) $alias)] = $hit;
                $this->bySlug[$this->normalizeName((string) $alias)] = $hit;
            }
        }
    }

    /**
     * @return array{id:int,naziv:string,slug:string,confidence:string}|null
     */
    public function match(?string $sourceName, ?string $canonicalCandidate = null): ?array
    {
        foreach ([$canonicalCandidate, $sourceName] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $slug = mb_strtolower(Str::slug($candidate));
            if (isset($this->bySlug[$slug])) {
                return $this->bySlug[$slug] + ['confidence' => 'exact_slug'];
            }

            $normalized = $this->normalizeName($candidate);
            if (isset($this->byName[$normalized])) {
                return $this->byName[$normalized] + ['confidence' => 'alias_or_name'];
            }
            if (isset($this->bySlug[$normalized])) {
                return $this->bySlug[$normalized] + ['confidence' => 'alias'];
            }
        }

        return null;
    }

    public function normalizeName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = strtr($value, [
            'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'd',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
