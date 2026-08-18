<?php

namespace App\Services\HealthcareImport\Normalizers;

class UrlNormalizer
{
    public function normalize(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return null;
        }

        return $value;
    }

    public function domain(?string $url): ?string
    {
        $normalized = $this->normalize($url);
        if ($normalized === null) {
            return null;
        }

        $host = parse_url($normalized, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        return preg_replace('/^www\./i', '', mb_strtolower($host));
    }
}
