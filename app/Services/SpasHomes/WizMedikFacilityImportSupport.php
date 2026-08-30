<?php

namespace App\Services\SpasHomes;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait WizMedikFacilityImportSupport
{
    /** @var list<string> */
    protected array $testOwnerEmails = [
        'spa@example.com',
        'dom@example.com',
        'banja.test@wizmedik.com',
    ];

    /** @var list<string> */
    protected array $demoBanjaSlugs = [
        'terme-ilidza',
        'banja-vrucica',
        'reumal-fojnica',
        'banja-slatina',
        'banja-guber',
        'banja-laktasi',
        'banja-dvorovi',
        'test-banja-termalni-raj',
    ];

    /** @var list<string> */
    protected array $demoDomSlugs = [
        'dom-za-starije-sunce',
        'rehabilitacioni-centar-nada',
        'palijativni-dom-mir',
        'gerijatrijski-centar-zlatna-jesen',
        'dom-oaza-mira',
        'test-dom-suncani-dom',
    ];

    protected function isTestOwnerEmail(?string $email): bool
    {
        if ($email === null || trim($email) === '') {
            return false;
        }

        $normalized = Str::lower(trim($email));

        if (in_array($normalized, $this->testOwnerEmails, true)) {
            return true;
        }

        return Str::endsWith($normalized, '@example.com');
    }

    protected function isClaimedByRealOwner(Model $entity, string $userIdColumn = 'user_id'): bool
    {
        $userId = $entity->getAttribute($userIdColumn);
        if (!$userId) {
            return false;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return false;
        }

        return !$this->isTestOwnerEmail($user->email);
    }

    protected function isDemoFeaturedImage(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        return str_contains($value, 'images.unsplash.com');
    }

    protected function isDemoBanja(Model $banja): bool
    {
        if (in_array((string) $banja->slug, $this->demoBanjaSlugs, true)) {
            return true;
        }

        return $this->isDemoFeaturedImage($banja->featured_slika);
    }

    protected function isDemoDom(Model $dom): bool
    {
        if (in_array((string) $dom->slug, $this->demoDomSlugs, true)) {
            return true;
        }

        return $this->isDemoFeaturedImage($dom->featured_slika);
    }

    protected function retiredDemoSlug(string $slug): string
    {
        $base = 'demo-retired-' . Str::slug($slug);
        if ($base === 'demo-retired-') {
            $base = 'demo-retired-record';
        }

        return Str::limit($base, 240, '');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function findExistingFacility(
        string $modelClass,
        string $slug,
        string $naziv,
        string $grad
    ): ?Model {
        /** @var Model|null $bySlug */
        $bySlug = $modelClass::query()->where('slug', $slug)->first();
        if ($bySlug) {
            return $bySlug;
        }

        return $modelClass::query()
            ->whereRaw('LOWER(naziv) = ?', [Str::lower(trim($naziv))])
            ->whereRaw('LOWER(grad) = ?', [Str::lower(trim($grad))])
            ->first();
    }

    protected function decodeJsonArray(mixed $value): array
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

    protected function parseExcelBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = Str::upper(trim((string) $value));

        return in_array($normalized, ['TRUE', '1', 'YES', 'DA'], true);
    }

    protected function uniqueSlugForModel(string $modelClass, string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = Str::slug($baseSlug);
        if ($slug === '') {
            $slug = 'facility';
        }

        $candidate = $slug;
        $suffix = 1;

        while ($this->slugExists($modelClass, $candidate, $ignoreId)) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function slugExists(string $modelClass, string $slug, ?int $ignoreId = null): bool
    {
        $query = $modelClass::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
