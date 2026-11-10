<?php

namespace App\Services\Seo;

class SitemapXml
{
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Format a stored modification time. Missing values stay absent instead of
     * being replaced with the current time.
     */
    public static function lastModified(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('c', $timestamp);
    }
}
