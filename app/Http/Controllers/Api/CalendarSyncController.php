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

        // If sync is already enabled but token is missing, create token automatically.
        if ($doctor->calendar_sync_enabled && !$doctor->calendar_sync_token) {
            $doctor->calendar_sync_token = Str::random(64);
            $doctor->save();
        }

        // Legacy compatibility: some doctors had token but sync stayed disabled by default.
        // If no previous sync activity/URLs exist, auto-enable once.
        if (
            !$doctor->calendar_sync_enabled
            && !$doctor->calendar_last_synced
            && empty($doctor->google_calendar_url)
            && empty($doctor->outlook_calendar_url)
        ) {
            $doctor->calendar_sync_enabled = true;
            $doctor->save();
        }

        $icalUrl = $doctor->calendar_sync_token
            ? $this->buildIcalUrl($doctor, $request)
            : null;

        return response()->json([
            'enabled' => (bool) $doctor->calendar_sync_enabled,
            'token' => $doctor->calendar_sync_token,
            'ical_url' => $icalUrl,
            'google_calendar_url' => $doctor->google_calendar_url,
            'outlook_calendar_url' => $doctor->outlook_calendar_url,
            'last_synced' => $doctor->calendar_last_synced,
            'instructions' => [
                'google' => 'Kopirajte iCal URL i dodajte ga u Google Calendar preko "Dodaj kalendar" > "Sa URL-a".',
                'apple' => 'Kopirajte iCal URL i dodajte ga u Apple Calendar kao subscribed calendar.',
                'outlook' => 'Kopirajte iCal URL i dodajte ga u Outlook preko "Add calendar" > "Subscribe from web".',
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

        $enabledWasProvided = array_key_exists('enabled', $validated);

        if ($enabledWasProvided) {
            if ((bool) $validated['enabled']) {
                if (!$doctor->calendar_sync_token) {
                    $doctor->calendar_sync_token = Str::random(64);
                }
                $doctor->calendar_sync_enabled = true;
            } else {
                // Turning sync off revokes the old URL immediately.
                $doctor->calendar_sync_enabled = false;
                $doctor->calendar_sync_token = null;
                $doctor->calendar_last_synced = null;
            }
        }

        if (array_key_exists('google_calendar_url', $validated)) {
            $doctor->google_calendar_url = $validated['google_calendar_url'];
        }

        if (array_key_exists('outlook_calendar_url', $validated)) {
            $doctor->outlook_calendar_url = $validated['outlook_calendar_url'];
        }

        $doctor->save();

        $icalUrl = $doctor->calendar_sync_token ? $this->buildIcalUrl($doctor, $request) : null;

        return response()->json([
            'message' => 'Calendar settings updated',
            'settings' => [
                'enabled' => (bool) $doctor->calendar_sync_enabled,
                'token' => $doctor->calendar_sync_token,
                'ical_url' => $icalUrl,
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

        // Regenerated token should be immediately usable.
        $doctor->calendar_sync_token = Str::random(64);
        $doctor->calendar_sync_enabled = true;
        $doctor->save();

        $icalUrl = $this->buildIcalUrl($doctor, $request);

        return response()->json([
            'message' => 'Token regenerated',
            'token' => $doctor->calendar_sync_token,
            'enabled' => (bool) $doctor->calendar_sync_enabled,
            'ical_url' => $icalUrl,
        ]);
    }

    /**
     * Manual sync action for doctor dashboard testing.
     * This does not force Google/Outlook to refresh immediately, but validates and regenerates feed now.
     */
    public function syncNow(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $doctor = Doktor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        if (!$doctor->calendar_sync_enabled) {
            return response()->json([
                'message' => 'Calendar sync is disabled. Enable sync first.',
            ], 422);
        }

        if (!$doctor->calendar_sync_token) {
            $doctor->calendar_sync_token = Str::random(64);
            $doctor->save();
        }

        $appointments = $this->getAppointmentsForFeed($doctor);
        $ical = $this->generateICalContent($doctor, $appointments);

        // Mark manual sync timestamp for dashboard visibility.
        $doctor->calendar_last_synced = now();
        $doctor->save();

        return response()->json([
            'message' => 'Calendar feed regenerated successfully.',
            'ical_url' => $this->buildIcalUrl($doctor, $request),
            'last_synced' => $doctor->calendar_last_synced,
            'events_count' => $appointments->count(),
            'feed_size_bytes' => strlen($ical),
            'feed_hash' => substr(sha1($ical), 0, 20),
            'note' => 'Google and Outlook refresh subscribed calendars periodically (not instantly).',
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
            return response('Calendar not found or disabled', 404)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $doctor = Doktor::where('calendar_sync_token', $token)
            ->where('calendar_sync_enabled', true)
            ->first();

        if (!$doctor) {
            return response('Calendar not found or disabled', 404)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // Best-effort timestamp update; never fail the feed because of this.
        try {
            $doctor->calendar_last_synced = now();
            $doctor->save();
        } catch (\Throwable $e) {
            report($e);
        }

        $appointments = $this->getAppointmentsForFeed($doctor);

        $ical = $this->generateICalContent($doctor, $appointments);

        return response($ical, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="wizmedik-calendar.ics"')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function getAppointmentsForFeed(Doktor $doctor)
    {
        return Termin::with(['user:id,ime,prezime'])
            ->where('doktor_id', $doctor->id)
            // Include a wider historical window so already-added appointments are visible after first subscribe.
            ->where('datum_vrijeme', '>=', now()->subYear())
            ->orderBy('datum_vrijeme')
            ->get();
    }

    /**
     * Build full iCal URL.
     */
    private function buildIcalUrl(Doktor $doctor, ?Request $request = null): string
    {
        $hostUrl = $request ? rtrim($request->getSchemeAndHttpHost(), '/') : '';
        if ($hostUrl !== '') {
            return "{$hostUrl}/api/calendar/ical/{$doctor->calendar_sync_token}.ics";
        }

        $configuredApiUrl = trim((string) config('app.api_url', ''));
        if ($configuredApiUrl !== '') {
            return rtrim($configuredApiUrl, '/') . "/api/calendar/ical/{$doctor->calendar_sync_token}.ics";
        }

        $fallbackAppUrl = rtrim((string) config('app.url', ''), '/');
        return "{$fallbackAppUrl}/api/calendar/ical/{$doctor->calendar_sync_token}.ics";
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
        $lines[] = 'X-WR-CALDESC:' . $this->escapeString('Automatski sinhronizovani termini sa WizMedik platforme');
        $lines[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT5M';
        $lines[] = 'X-PUBLISHED-TTL:PT5M';

        foreach ($appointments as $appointment) {
            try {
                $lines = array_merge($lines, $this->generateEventLines($appointment, $doctor));
            } catch (\Throwable $e) {
                // Skip a single broken row instead of failing the entire calendar feed.
                report($e);
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $this->foldIcalLines($lines)) . "\r\n";
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

        // Emit UTC timestamps for maximum Google/Outlook compatibility.
        $startDateTimeUtc = Carbon::parse($appointment->datum_vrijeme)->utc();
        $duration = max((int) ($appointment->trajanje_minuti ?? 30), 5);
        $endDateTimeUtc = $startDateTimeUtc->copy()->addMinutes($duration);

        $lines[] = 'DTSTART:' . $startDateTimeUtc->format('Ymd\THis\Z');
        $lines[] = 'DTEND:' . $endDateTimeUtc->format('Ymd\THis\Z');

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

    /**
     * Safely read encrypted attributes without failing the whole feed.
     */
    private function safeReadEncryptedField(Termin $appointment, string $field): ?string
    {
        try {
            $value = $appointment->{$field};
        } catch (\Throwable $e) {
            // Historical plaintext/corrupt rows should not break export.
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
            'potvrden', 'potvrdjen' => 'Potvrdjen',
            'otkazan' => 'Otkazan',
            'zavrshen', 'zavrsen', 'završen' => 'Zavrsen',
            default => 'Nepoznat',
        };
    }

    /**
     * Fold iCal lines to respect RFC line length rules (75 octets).
     */
    private function foldIcalLines(array $lines): array
    {
        $folded = [];

        foreach ($lines as $line) {
            if (strlen($line) <= 75) {
                $folded[] = $line;
                continue;
            }

            $remaining = $line;
            $isFirstChunk = true;

            while ($remaining !== '') {
                $maxChunkLength = $isFirstChunk ? 75 : 74; // Continuation line starts with one space.

                if (function_exists('mb_strcut')) {
                    $chunk = mb_strcut($remaining, 0, $maxChunkLength, 'UTF-8');
                    if ($chunk === '') {
                        $chunk = substr($remaining, 0, $maxChunkLength);
                    }
                } else {
                    $chunk = substr($remaining, 0, $maxChunkLength);
                }

                $remaining = (string) substr($remaining, strlen($chunk));
                $folded[] = $isFirstChunk ? $chunk : " {$chunk}";
                $isFirstChunk = false;
            }
        }

        return $folded;
    }
}
