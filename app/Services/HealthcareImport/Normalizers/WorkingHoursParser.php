<?php

namespace App\Services\HealthcareImport\Normalizers;

class WorkingHoursParser
{
    private const DAYS = ['ponedeljak', 'utorak', 'srijeda', 'cetvrtak', 'petak', 'subota', 'nedjelja'];

    /**
     * @return array<string, array{open:string,close:string,closed:bool}>|null
     */
    public function parse(?string $raw): ?array
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (preg_match('/24\s*\/\s*7/i', $value) || preg_match('/non[-\s]?stop/i', $value)) {
            return $this->allDays('00:00', '23:59', false);
        }

        $hours = $this->closedTemplate();
        $matched = false;

        $segments = preg_split('/[;|]/', $value) ?: [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (preg_match('/(pon|uto|sri|sre|cet|čet|pet)\s*[-–]\s*(pet|sub)/iu', $segment, $range)
                && preg_match('/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', $segment, $time)
            ) {
                $from = $this->dayIndex($range[1]);
                $to = $this->dayIndex($range[2]);
                if ($from !== null && $to !== null && $from <= $to) {
                    for ($i = $from; $i <= $to; $i++) {
                        $hours[self::DAYS[$i]] = [
                            'open' => $this->hhmm($time[1]),
                            'close' => $this->hhmm($time[2]),
                            'closed' => false,
                        ];
                    }
                    $matched = true;
                }
            }

            if (preg_match('/(subota|sub)\b/iu', $segment)
                && preg_match('/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', $segment, $time)
            ) {
                $hours['subota'] = [
                    'open' => $this->hhmm($time[1]),
                    'close' => $this->hhmm($time[2]),
                    'closed' => false,
                ];
                $matched = true;
            }

            if (preg_match('/(nedjelja|nedelja|ned)\b/iu', $segment)) {
                if (preg_match('/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', $segment, $time)) {
                    $hours['nedjelja'] = [
                        'open' => $this->hhmm($time[1]),
                        'close' => $this->hhmm($time[2]),
                        'closed' => false,
                    ];
                } else {
                    $hours['nedjelja']['closed'] = true;
                }
                $matched = true;
            }
        }

        return $matched ? $hours : null;
    }

    /**
     * @return array<string, array{open:string,close:string,closed:bool}>
     */
    private function closedTemplate(): array
    {
        $hours = [];
        foreach (self::DAYS as $day) {
            $hours[$day] = ['open' => '08:00', 'close' => '20:00', 'closed' => $day === 'nedjelja'];
        }

        return $hours;
    }

    /**
     * @return array<string, array{open:string,close:string,closed:bool}>
     */
    private function allDays(string $open, string $close, bool $closed): array
    {
        $hours = [];
        foreach (self::DAYS as $day) {
            $hours[$day] = ['open' => $open, 'close' => $close, 'closed' => $closed];
        }

        return $hours;
    }

    private function dayIndex(string $token): ?int
    {
        $token = mb_strtolower($token);
        $map = [
            'pon' => 0, 'uto' => 1, 'sri' => 2, 'sre' => 2, 'cet' => 3, 'čet' => 3,
            'pet' => 4, 'sub' => 5, 'ned' => 6,
        ];

        return $map[$token] ?? null;
    }

    private function hhmm(string $time): string
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '00');

        return sprintf('%02d:%02d', (int) $h, (int) $m);
    }
}
