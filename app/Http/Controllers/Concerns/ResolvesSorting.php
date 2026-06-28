<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesSorting
{
    /**
     * Safely apply an ORDER BY clause using only allowlisted columns and directions.
     *
     * User-supplied sort_by / sort_order values are never passed to the query
     * unless they exactly match the allowlist, preventing SQL injection through
     * the order-by column name (which Laravel does not bind).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<int, string>  $allowedColumns
     */
    protected function applySafeSort(
        $query,
        Request $request,
        array $allowedColumns,
        string $defaultColumn,
        string $defaultDirection = 'desc'
    ) {
        $sortBy = (string) $request->get('sort_by', $defaultColumn);
        if (! in_array($sortBy, $allowedColumns, true)) {
            $sortBy = $defaultColumn;
        }

        $sortOrder = strtolower((string) $request->get('sort_order', $defaultDirection));
        if (! in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = $defaultDirection;
        }

        return $query->orderBy($sortBy, $sortOrder);
    }
}
