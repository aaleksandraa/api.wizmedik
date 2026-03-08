<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class LijekCacheVersion
{
    private const KEY = 'lijekovi_cache_version';

    public static function current(): int
    {
        return (int) Cache::get(self::KEY, 1);
    }

    public static function bump(): int
    {
        $next = self::current() + 1;
        Cache::forever(self::KEY, $next);

        return $next;
    }
}
