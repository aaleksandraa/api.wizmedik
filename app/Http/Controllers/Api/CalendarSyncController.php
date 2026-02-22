<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doktor;
use App\Models\Termin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarSyncController extends Controller
{
    /**
     * Get calendar sync settings for authenticated doctor.
     */
    public function getSettings(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $doctor = Doktor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        if (!$doctor->calendar_sync_token) {
            $doctor->calendar_sync_token = Str::random(64);
            $doctor->save();
        }

        $icalUrl = $this->buildIcalUrl($doctor, $request);

        return response()->json([
            'enabled' => (bool) $doctor->calendar_sync_enabled,
            'token' => $doctor->calendar_sync_token,
            'ical_url' => $icalUrl,
            'google_calendar_url' => $doctor->google_calendar_url,
            'outlook_calendar_url' => $doctor->outlook_calendar_url,
            'last_synced' => $doctor->calendar_last_synced,
            'instructions' => [
                'google' => 'Kopirajte iCal URL i dodajte ga u Google Calendar preko "Dodaj kalendar" > "Sa URL-a"',
                'apple' => 'Kopirajte iCal URL i dodajte ga u iPhone Calendar preko Settings > Calendar > Accounts > Add Account > Other > Add Subscribed Calendar',
                'outlook' => 'Kopirajte iCal URL i dodajte ga u Outlook preko "Dodaj kalendar" > "Sa interneta"',
            ],
        ]);
    }

    /**
     * Update calendar sync settings.
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $doctor = Doktor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        $validated = $request->validate([
            'enabled' => 'boolean',
            'google_calendar_url' => 'nullable|url',
            'outlook_calendar_url' => 'nullable|url',
        ]);

        $doctor->update([
            'calendar_sync_enabled' => $validated['enabled'] ?? $doctor->calendar_sync_enabled,
            'google_calendar_url' => $validated['google_calendar_url'] ?? $doctor->google_calendar_url,
            'outlook_calendar_url' => $validated['outlook_calendar_url'] ?? $doctor->outlook_calendar_url,
        ]);

        return response()->json([
            'message' => 'Postavke kalendara ažurirane',
            'settings' => [
                'enabled' => (bool) $doctor->calendar_sync_enabled,
                'google_calendar_url' => $doctor->google_calendar_url,
                'outlook_calendar_url' => $doctor->outlook_calendar_url,
            ],
        ]);
    }

    /**
     * Regenerate calendar sync token.
     */
    public function regenerateToken(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $doctor = Doktor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        $doctor->calendar_sync_token = Str::random(64);
        $doctor->save();

        $icalUrl = $this->buildIcalUrl($doctor, $request);

        return response()->json([
            'message' => 'Token regenerisan',
            'token' => $doctor->calendar_sync_token,
            'ical_url' => $icalUrl,
        ]);
    }

    /**
     * Generate iCal feed for doctor's appointments.
     * Public endpoint - no authentication required.
     */
    public function generateICalFeed(string $token)
    {
        // Light validation to avoid unnecessary DB work on malformed tokens.
        if (!preg_match('/^[A-Za-z0-9]{32,128}$/', $token)) {
            return response('Calendar not found or disabled', 404);
        }

        $doctor = Doktor::where('calendar_sync_token', $token)
            ->where('calendar_sync_enabled', true)
            ->first();

        if (!$doctor) {
            return response('Calendar not found or disabled', 404);
        }

        // Best-effort timestamp update; do not break feed if this fails.
        try {
            $doctor->calendar_last_synced = now();
            $doctor->save();
        } catch (\Throwable $e) {
            report($e);
        }

        $appointments = Termin::with(['user:id,ime,prezime'])
            ->where('doktor_id', $doctor->id)
            ->where('datum_vrijeme', '>=', now()->subDays(30))
            ->orderBy('datum_vrijeme')
            ->get();

        $ical = $this->generateICalContent($doctor, $appointments);

        return response($ical, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="calendar.ics"')
            ->header('Cache-Control', 'private, max-age=300');
    }

    /**
     * Generate iCal format content.
     */
    private function generateICalContent(Doktor $doctor, $appointments): string
    {
        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//WizMedik//Calendar Sync//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . $this->escapeString("Dr. {$doctor->ime} {$doctor->prezime} - Termini");
        $lines[] = 'X-WR-TIMEZONE:Europe/Sarajevo';
        $lines[] = 'X-WR-CALDESC:' . $this->escapeString('Automatski sinhronizovani termini sa WizMedik platforme');

        foreach ($appointments as $appointment) {
            try {
                $lines = array_merge($lines, $this->generateEventLines($appointment, $doctor));
            } catch (\Throwable $e) {
                // Skip a single broken row instead of breaking the entire calendar feed.
                report($e);
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate iCal event lines for a single appointment.
     */
    private function generateEventLines(Termin $appointment, Doktor $doctor): array
    {
        $lines = [];
        $lines[] = 'BEGIN:VEVENT';

        $uid = "termin-{$appointment->id}-doktor-{$doctor->id}@wizmedik.com";
        $lines[] = "UID:{$uid}";
        $lines[] = 'DTSTAMP:' . Carbon::now('UTC')->format('Ymd\THis\Z');

        $startDateTime = Carbon::parse($appointment->datum_vrijeme)->setTimezone('Europe/Sarajevo');
        $duration = max((int) ($appointment->trajanje_minuti ?? 30), 5);
        $endDateTime = $startDateTime->copy()->addMinutes($duration);

        $lines[] = 'DTSTART;TZID=Europe/Sarajevo:' . $startDateTime->format('Ymd\THis');
        $lines[] = 'DTEND;TZID=Europe/Sarajevo:' . $endDateTime->format('Ymd\THis');

        $patientName = $this->resolvePatientName($appointment);
        $lines[] = 'SUMMARY:' . $this->escapeString("Termin: {$patientName}");

        $descriptionLines = ["Pacijent: {$patientName}"];

        $reason = $this->safeReadEncryptedField($appointment, 'razlog');
        if (!empty($reason)) {
            $descriptionLines[] = "Razlog: {$reason}";
        }

        $notes = $this->safeReadEncryptedField($appointment, 'napomene');
        if (!empty($notes)) {
            $descriptionLines[] = "Napomene: {$notes}";
        }

        $descriptionLines[] = 'Status: ' . $this->getStatusLabel((string) $appointment->status);
        $description = implode("\n", $descriptionLines);
        $lines[] = 'DESCRIPTION:' . $this->escapeString($description);

        $location = trim((string) ($doctor->lokacija ?? ''));
        if (!empty($doctor->grad)) {
            $location = $location ? "{$location}, {$doctor->grad}" : $doctor->grad;
        }
        if (!empty($location)) {
            $lines[] = 'LOCATION:' . $this->escapeString($location);
        }

        $lines[] = 'STATUS:' . $this->getICalStatus((string) $appointment->status);
        $lines[] = 'CATEGORIES:' . $this->escapeString('Medicinski termin,WizMedik');

        $lines[] = 'BEGIN:VALARM';
        $lines[] = 'TRIGGER:-PT30M';
        $lines[] = 'ACTION:DISPLAY';
        $lines[] = 'DESCRIPTION:' . $this->escapeString('Podsjetnik: Termin za 30 minuta');
        $lines[] = 'END:VALARM';

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function buildIcalUrl(Doktor $doctor, ?Request $request = null): string
    {
        $configuredApiUrl = trim((string) config('app.api_url', ''));
        if ($configuredApiUrl !== '') {
            return rtrim($configuredApiUrl, '/') . "/api/calendar/ical/{$doctor->calendar_sync_token}";
        }

        $hostUrl = $request ? rtrim($request->getSchemeAndHttpHost(), '/') : '';
        if ($hostUrl !== '') {
            return "{$hostUrl}/api/calendar/ical/{$doctor->calendar_sync_token}";
        }

        $fallbackAppUrl = rtrim((string) config('app.url', ''), '/');
        return "{$fallbackAppUrl}/api/calendar/ical/{$doctor->calendar_sync_token}";
    }

    private function safeReadEncryptedField(Termin $appointment, string $field): ?string
    {
        try {
            $value = $appointment->{$field};
        } catch (\Throwable $e) {
            // Historical plaintext/corrupt records should not break calendar export.
            report($e);
            return null;
        }

        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function resolvePatientName(Termin $appointment): string
    {
        if ($appointment->user) {
            $name = trim("{$appointment->user->ime} {$appointment->user->prezime}");
            if ($name !== '') {
                return $name;
            }
        }

        $guestName = trim("{$appointment->guest_ime} {$appointment->guest_prezime}");
        return $guestName !== '' ? $guestName : 'Pacijent';
    }

    /**
     * Escape special characters for iCal format.
     */
    private function escapeString(string $string): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', ''],
            $string
        );
    }

    private function getICalStatus(string $status): string
    {
        return match ($status) {
            'otkazan' => 'CANCELLED',
            'zakazan', 'potvrden', 'potvrdjen', 'zavrshen', 'zavrsen', 'završen' => 'CONFIRMED',
            default => 'TENTATIVE',
        };
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'zakazan' => 'Zakazan',
            'potvrden', 'potvrdjen' => 'Potvrđen',
            'otkazan' => 'Otkazan',
            'zavrshen', 'zavrsen', 'završen' => 'Završen',
            default => 'Nepoznat',
        };
    }
}
