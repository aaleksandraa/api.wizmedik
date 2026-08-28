<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait SortsByDistance
{
    /**
     * Apply Haversine distance sort when sort_by=distance and lat/lng are present.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyDistanceSort($query, Request $request, string $table): bool
    {
        if (! $request->filled('lat') || ! $request->filled('lng')) {
            return false;
        }

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        $query->selectRaw(
            "(6371 * acos(LEAST(1, GREATEST(-1,
                cos(radians(?)) * cos(radians({$table}.latitude)) * cos(radians({$table}.longitude) - radians(?))
                + sin(radians(?)) * sin(radians({$table}.latitude))
            )))) as distance",
            [$lat, $lng, $lat]
        );

        if ((string) $request->get('sort_by') !== 'distance') {
            return false;
        }

        $query
            ->whereNotNull("{$table}.latitude")
            ->whereNotNull("{$table}.longitude")
            ->orderBy('distance')
            ->orderBy("{$table}.id");

        return true;
    }
}
