<?php

namespace App\Traits;

use App\Http\Controllers\Api\CityController;

/**
 * Trait to automatically invalidate city cache when models are created/updated/deleted
 * Add this trait to models that affect city statistics (Doktor, Klinika, Banja, Dom, Laboratorija)
 */
trait InvalidatesCityCache
{
    protected static function bootInvalidatesCityCache()
    {
        static::created(function ($model) {
            CityController::clearCache();
        });

        static::updated(function ($model) {
            // Only clear cache if 'grad' field changed or active status changed
            if ($model->isDirty('grad') || $model->isDirty('aktivan')) {
                CityController::clearCache();
            }
        });

        static::deleted(function ($model) {
            CityController::clearCache();
        });
    }
}
