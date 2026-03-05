<?php

namespace App\Services;

use App\Models\ApotekaPoslovnica;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ApotekaAvailabilityService
{
    private const TIMEZONE = 'Europe/Sarajevo';

    public function resolveStatus(ApotekaPoslovnica $poslovnica, ?CarbonImmutable $now = null): array
    {
        $localNow = ($now ?? CarbonImmutable::now(self::TIMEZONE))->setTimezone(self::TIMEZONE);
        $today = $localNow->startOfDay();
        $todayDate = $today->toDateString();

        $poslovnica->loadMissing(['radnoVrijeme', 'radnoVrijemeIzuzeci', 'dezurstva']);

        $activeDuty = $poslovnica->dezurstva
            ->first(function ($shift) use ($localNow) {
                if ($shift->status !== 'confirmed') {
                    return false;
                }

                $startsAt = CarbonImmutable::parse($shift->starts_at)->setTimezone(self::TIMEZONE);
                $endsAt = CarbonImmutable::parse($shift->ends_at)->setTimezone(self::TIMEZONE);

                return $startsAt->lte($localNow) && $endsAt->gt($localNow);
            });

        if ($activeDuty) {
            $dutyEnd = CarbonImmutable::parse($activeDuty->ends_at)->setTimezone(self::TIMEZONE);

            return [
                'open_now' => true,
                'is_dezurna' => true,
                'is_24h' => (bool) $poslovnica->is_24h,
                'status_label' => 'Dezurna',
                'next_change_at' => $dutyEnd->toIso8601String(),
            ];
        }

        if ($poslovnica->is_24h) {
            return [
                'open_now' => true,
                'is_dezurna' => false,
                'is_24h' => true,
                'status_label' => 'Otvorena 24/7',
                'next_change_at' => null,
            ];
        }

        $exceptions = $poslovnica->radnoVrijemeIzuzeci->keyBy(fn ($item) => CarbonImmutable::parse($item->date)->toDateString());
        $hoursByDay = $poslovnica->radnoVrijeme->keyBy('day_of_week');

        $todayException = $exceptions->get($todayDate);
        if ($todayException) {
            $fromException = $this->resolveFromSlot(
                $localNow,
                $today,
                $todayException->open_time,
                $todayException->close_time,
                (bool) $todayException->closed
            );

            if ($fromException['resolved']) {
                return [
                    'open_now' => $fromException['open_now'],
                    'is_dezurna' => false,
                    'is_24h' => false,
                    'status_label' => $fromException['open_now'] ? 'Otvorena' : 'Zatvorena',
                    'next_change_at' => $fromException['next_change_at'],
                ];
            }
        }

        $todaySlot = $hoursByDay->get($localNow->isoWeekday());
        $todayResolved = $this->resolveFromSlot(
            $localNow,
            $today,
            $todaySlot?->open_time,
            $todaySlot?->close_time,
            (bool) ($todaySlot?->closed ?? true)
        );

        if ($todayResolved['open_now']) {
            return [
                'open_now' => true,
                'is_dezurna' => false,
                'is_24h' => false,
                'status_label' => 'Otvorena',
                'next_change_at' => $todayResolved['next_change_at'],
            ];
        }

        $yesterday = $today->subDay();
        $yesterdaySlot = $hoursByDay->get($yesterday->isoWeekday());
        if ($this->isCrossMidnightSlot($yesterdaySlot?->open_time, $yesterdaySlot?->close_time, (bool) ($yesterdaySlot?->closed ?? true))) {
            $yesterdayResolved = $this->resolveFromSlot(
                $localNow,
                $yesterday,
                $yesterdaySlot?->open_time,
                $yesterdaySlot?->close_time,
                false
            );

            if ($yesterdayResolved['open_now']) {
                return [
                    'open_now' => true,
                    'is_dezurna' => false,
                    'is_24h' => false,
                    'status_label' => 'Otvorena',
                    'next_change_at' => $yesterdayResolved['next_change_at'],
                ];
            }
        }

        return [
            'open_now' => false,
            'is_dezurna' => false,
            'is_24h' => false,
            'status_label' => 'Zatvorena',
            'next_change_at' => $this->resolveNextOpenAt($localNow, $hoursByDay, $exceptions),
        ];
    }

    public function isWindowActive(
        $startsAt,
        $endsAt,
        ?array $daysOfWeek = null,
        ?string $timeFrom = null,
        ?string $timeTo = null,
        ?CarbonImmutable $now = null
    ): bool {
        $localNow = ($now ?? CarbonImmutable::now(self::TIMEZONE))->setTimezone(self::TIMEZONE);

        if ($startsAt && CarbonImmutable::parse($startsAt)->setTimezone(self::TIMEZONE)->gt($localNow)) {
            return false;
        }

        if ($endsAt && CarbonImmutable::parse($endsAt)->setTimezone(self::TIMEZONE)->lt($localNow)) {
            return false;
        }

        if (is_array($daysOfWeek) && !empty($daysOfWeek) && !in_array($localNow->isoWeekday(), $daysOfWeek, true)) {
            return false;
        }

        if ($timeFrom && $timeTo) {
            $dayStart = $localNow->startOfDay();
            $interval = $this->buildInterval($dayStart, $timeFrom, $timeTo);
            if (!$interval['start'] || !$interval['end']) {
                return false;
            }

            if (!$localNow->betweenIncluded($interval['start'], $interval['end'])) {
                return false;
            }
        }

        return true;
    }

    private function resolveFromSlot(
        CarbonImmutable $now,
        CarbonImmutable $date,
        ?string $openTime,
        ?string $closeTime,
        bool $closed
    ): array {
        if ($closed || !$openTime || !$closeTime) {
            return [
                'resolved' => true,
                'open_now' => false,
                'next_change_at' => null,
            ];
        }

        $interval = $this->buildInterval($date, $openTime, $closeTime);
        if (!$interval['start'] || !$interval['end']) {
            return [
                'resolved' => true,
                'open_now' => false,
                'next_change_at' => null,
            ];
        }

        $openNow = $now->gte($interval['start']) && $now->lt($interval['end']);
        return [
            'resolved' => true,
            'open_now' => $openNow,
            'next_change_at' => $openNow ? $interval['end']->toIso8601String() : null,
        ];
    }

    private function resolveNextOpenAt(
        CarbonImmutable $now,
        Collection $hoursByDay,
        Collection $exceptions
    ): ?string {
        $today = $now->startOfDay();

        for ($offset = 0; $offset <= 7; $offset++) {
            $date = $today->addDays($offset);
            $dateKey = $date->toDateString();

            $exception = $exceptions->get($dateKey);
            if ($exception) {
                if ($exception->closed || !$exception->open_time || !$exception->close_time) {
                    continue;
                }

                $interval = $this->buildInterval($date, $exception->open_time, $exception->close_time);
                if ($interval['start'] && $interval['start']->gt($now)) {
                    return $interval['start']->toIso8601String();
                }

                continue;
            }

            $slot = $hoursByDay->get($date->isoWeekday());
            if (!$slot || $slot->closed || !$slot->open_time || !$slot->close_time) {
                continue;
            }

            $interval = $this->buildInterval($date, $slot->open_time, $slot->close_time);
            if ($interval['start'] && $interval['start']->gt($now)) {
                return $interval['start']->toIso8601String();
            }
        }

        return null;
    }

    private function buildInterval(CarbonImmutable $date, ?string $openTime, ?string $closeTime): array
    {
        if (!$openTime || !$closeTime) {
            return ['start' => null, 'end' => null];
        }

        $start = $date->setTimeFromTimeString($openTime);
        $end = $date->setTimeFromTimeString($closeTime);

        if ($end->lte($start)) {
            $end = $end->addDay();
        }

        return ['start' => $start, 'end' => $end];
    }

    private function isCrossMidnightSlot(?string $openTime, ?string $closeTime, bool $closed): bool
    {
        if ($closed || !$openTime || !$closeTime) {
            return false;
        }

        return $openTime > $closeTime;
    }
}

