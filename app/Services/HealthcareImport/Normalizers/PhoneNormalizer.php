<?php

namespace App\Services\HealthcareImport\Normalizers;

class PhoneNormalizer
{
    public function normalize(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = '+' . substr($digits, 2);
        }

        if (str_starts_with($digits, '387') && !str_starts_with($digits, '+')) {
            $digits = '+' . $digits;
        }

        if (preg_match('/^0[3-6]\d+/', $digits)) {
            $digits = '+387' . substr($digits, 1);
        }

        return $digits;
    }
}
