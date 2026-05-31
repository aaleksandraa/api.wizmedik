<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfileSlugRedirect extends Model
{
    protected $table = 'profile_slug_redirects';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'old_slug',
    ];

    public static function remember(string $entityType, int $entityId, ?string $oldSlug, ?string $currentSlug): void
    {
        $oldSlug = trim((string) $oldSlug);
        $currentSlug = trim((string) $currentSlug);

        if ($oldSlug === '' || $currentSlug === '' || $oldSlug === $currentSlug) {
            return;
        }

        if (!Schema::hasTable('profile_slug_redirects')) {
            return;
        }

        static::updateOrCreate(
            [
                'entity_type' => $entityType,
                'old_slug' => $oldSlug,
            ],
            [
                'entity_id' => $entityId,
            ]
        );
    }

    public static function resolveCurrentSlug(string $entityType, string $oldSlug, string $table): ?string
    {
        if (!Schema::hasTable('profile_slug_redirects')) {
            return null;
        }

        return DB::table('profile_slug_redirects')
            ->join($table, "{$table}.id", '=', 'profile_slug_redirects.entity_id')
            ->where('profile_slug_redirects.entity_type', $entityType)
            ->where('profile_slug_redirects.old_slug', $oldSlug)
            ->value("{$table}.slug");
    }
}
