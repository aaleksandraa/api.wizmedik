<?php

namespace App\Services\HealthcareImport\Normalizers;

class DoctorNameNormalizer
{
    private const TITLES = [
        'dr med',
        'dr. med',
        'mr sci',
        'mr. sci',
        'mr sc',
        'mr. sc',
        'sci med',
        'sci. med',
        'subspec',
        'prim',
        'doc',
        'prof',
        'spec',
        'dr',
        'mr',
    ];

    /**
     * @return array{ime:?string,prezime:?string,title:?string,match_key:string,ascii_key:string}|null
     */
    public function parse(?string $raw): ?array
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $stripped = $value;
        $foundTitle = null;
        $titlePattern = '/\b(' . implode('|', array_map(
            fn (string $title) => preg_quote($title, '/'),
            self::TITLES
        )) . ')\.?\b/iu';

        if (preg_match_all($titlePattern, $stripped, $matches)) {
            $foundTitle = $matches[1][0] ?? null;
        }

        $stripped = preg_replace($titlePattern, ' ', $stripped) ?? $stripped;
        $stripped = str_replace(['.', ',', ';'], ' ', $stripped);
        $stripped = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;
        $stripped = trim($stripped);

        if ($stripped === '') {
            return null;
        }

        $parts = preg_split('/\s+/u', $stripped) ?: [];
        if ($this->isAllCaps($stripped)) {
            $parts = array_map(
                fn (string $part) => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'),
                $parts
            );
        }
        $ime = $parts[0] ?? null;
        $prezime = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        $matchKey = mb_strtolower(trim(implode(' ', $parts)));
        $asciiKey = $this->ascii($matchKey);

        return [
            'ime' => $ime,
            'prezime' => $prezime,
            'title' => $foundTitle,
            'match_key' => $matchKey,
            'ascii_key' => $asciiKey,
        ];
    }

    private function isAllCaps(string $value): bool
    {
        return $value !== ''
            && $value === mb_strtoupper($value, 'UTF-8')
            && (bool) preg_match('/\p{L}/u', $value);
    }

    public function ascii(string $value): string
    {
        $map = [
            'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's', 'đ' => 'd',
            'Č' => 'c', 'Ć' => 'c', 'Ž' => 'z', 'Š' => 's', 'Đ' => 'd',
        ];

        return strtr(mb_strtolower($value), $map);
    }
}
