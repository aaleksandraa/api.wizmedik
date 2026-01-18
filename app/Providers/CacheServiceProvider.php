<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Cache TTL constants (in seconds)
     */
    const CACHE_TTL_SHORT = 300;      // 5 minutes
    const CACHE_TTL_MEDIUM = 1800;    // 30 minutes
    const CACHE_TTL_LONG = 3600;      // 1 hour
    const CACHE_TTL_DAY = 86400;      // 24 hours

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache helper
        $this->app->singleton('cache.helper', function () {
            return new class {
                public function rememberDoctor($doctorId, $callback)
                {
                    return Cache::remember(
                        "doctor:{$doctorId}",
                        CacheServiceProvider::CACHE_TTL_MEDIUM,
                        $callback
                    );
                }

                public function rememberClinic($clinicId, $callback)
                {
                    return Cache::remember(
                        "clinic:{$clinicId}",
                        CacheServiceProvider::CACHE_TTL_MEDIUM,
                        $callback
                    );
                }

                public function rememberSpecialties($callback)
                {
                    return Cache::remember(
                        'specialties:all',
                        CacheServiceProvider::CACHE_TTL_LONG,
                        $callback
                    );
                }

                public function rememberSettings($key, $callback)
                {
                    return Cache::remember(
                        "settings:{$key}",
                        CacheServiceProvider::CACHE_TTL_DAY,
                        $callback
                    );
                }

                public function forgetDoctor($doctorId)
                {
                    Cache::forget("doctor:{$doctorId}");
                }

                public function forgetClinic($clinicId)
                {
                    Cache::forget("clinic:{$clinicId}");
                }

                public function forgetSpecialties()
                {
                    Cache::forget('specialties:all');
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Enable query caching for read operations
        if (config('cache.default') === 'redis') {
            DB::listen(function ($query) {
                // Log slow queries (> 1 second)
                if ($query->time > 1000) {
                    logger()->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'bindings' => $query->bindings
                    ]);
                }
            });
        }
    }
}
