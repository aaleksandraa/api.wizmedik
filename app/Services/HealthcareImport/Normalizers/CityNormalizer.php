<?php

namespace App\Services\HealthcareImport\Normalizers;

class CityNormalizer
{
    public function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = strtr($value, [
            'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'd',
        ]);
        $value = preg_replace('/^grad\s+/u', '', $value) ?? $value;

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
