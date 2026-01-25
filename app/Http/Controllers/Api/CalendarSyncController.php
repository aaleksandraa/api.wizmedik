<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doktor;
use App\Models\Termin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CalendarSyncController extends Controller
{
    /**
     * Get calendar sync settings for authenticated doctor
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

        // Generate token if doesn't exist
        if (!$doctor->calendar_sync_token) {
            $doctor->calendar_sync_token = Str::random(64);
            $doctor->save();
        }

        $baseUrl = config('app.url');
        $icalUrl = "{$baseUrl}/api/calendar/ical/{$doctor->calendar_sync_token}";

        return response()->json([
            'enabled' => $doctor->calendar_sync_enabled,
            'token' => $doctor->calendar_sync_token,
            'ical_url' => $icalUrl,
            'google_calendar_url' => $doctor->google_calendar_url,
            'outlook_calendar_url' => $doctor->outlook_calendar_url,
            'last_synced' => $doctor->calendar_last_synced,
            'instructions' => [
                'google' => 'Kopirajte iCal URL i dodajte ga u Google Calendar preko "Dodaj kalendar" > "Sa URL-a"',
                'apple' => 'Kopirajte iCal URL i dodajte ga u iPhone Calendar preko Settings > Calendar > Accounts > Add Account > Other > Add Subscribed Calendar',
                'outlook' => 'Kopirajte iCal URL i dodajte ga u Outlook preko "Dodaj kalendar" > "Sa interneta"'
            ]
        ]);
    }

    /**
     * Update calendar sync settings
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
                'enabled' => $doctor->calendar_sync_enabled,
                'google_calendar_url' => $doctor->google_calendar_url,
                'outlook_calendar_url' => $doctor->outlook_calendar_url,
            ]
        ]);
    }

    /**
     * Regenerate calendar sync token
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

        $baseUrl = config('app.url');
        $icalUrl = "{$baseUrl}/api/calendar/ical/{$doctor->calendar_sync_token}";

        return response()->json([
            'message' => 'Token regenerisan',
            'token' => $doctor->calendar_sync_token,
            'ical_url' => $icalUrl,
        ]);
    }

    /**
     * Generate iCal feed for doctor's appointments
     * Public endpoint - no authentication required
     */
    public function generateICalFeed($token)
    {
        $doctor = Doktor::where('calendar_sync_token', $token)
            ->where('calendar_sync_enabled', true)
            ->first();

        if (!$doctor) {
            return response('Calendar not found or disabled', 404);
        }

        // Update last synced timestamp
        $doctor->calendar_last_synced = now();
        $doctor->save();

        // Get all future appointments
        $appointments = Termin::where('doktor_id', $doctor->id)
            ->where('datum', '>=', now()->subDays(30)) // Include past 30 days
            ->orderBy('datum')
            ->orderBy('vrijeme')
            ->get();

        // Generate iCal content
        $ical = $this->generateICalContent($doctor, $appointments);

        return response($ical, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="calendar.ics"');
    }

    /**
     * Generate iCal format content
     */
    private function generateICalContent($doctor, $appointments)
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
            $lines = array_merge($lines, $this->generateEventLines($appointment, $doctor));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /**
     * Generate iCal event lines for an appointment
     */
    private function generateEventLines($appointment, $doctor)
    {
        $lines = [];
        $lines[] = 'BEGIN:VEVENT';

        // Unique ID
        $uid = "appointment-{$appointment->id}@wizmedik.com";
        $lines[] = "UID:{$uid}";

        // Timestamps
        $lines[] = 'DTSTAMP:' . now()->format('Ymd\THis\Z');

        // Start time
        $startDateTime = \Carbon\Carbon::parse($appointment->datum . ' ' . $appointment->vrijeme);
        $lines[] = 'DTSTART:' . $startDateTime->format('Ymd\THis');

        // End time (assume 30 minutes if not specified)
        $duration = $appointment->trajanje ?? 30;
        $endDateTime = $startDateTime->copy()->addMinutes($duration);
        $lines[] = 'DTEND:' . $endDateTime->format('Ymd\THis');

        // Summary (title)
        $patientName = $appointment->pacijent
            ? "{$appointment->pacijent->ime} {$appointment->pacijent->prezime}"
            : 'Pacijent';
        $summary = "Termin: {$patientName}";
        $lines[] = 'SUMMARY:' . $this->escapeString($summary);

        // Description
        $description = "Pacijent: {$patientName}\n";
        if ($appointment->razlog) {
            $description .= "Razlog: {$appointment->razlog}\n";
        }
        if ($appointment->napomena) {
            $description .= "Napomena: {$appointment->napomena}\n";
        }
        $description .= "\nStatus: " . $this->getStatusLabel($appointment->status);
        $lines[] = 'DESCRIPTION:' . $this->escapeString($description);

        // Location
        if ($doctor->adresa) {
            $lines[] = 'LOCATION:' . $this->escapeString($doctor->adresa);
        }

        // Status
        $status = match($appointment->status) {
            'potvrdjen' => 'CONFIRMED',
            'otkazan' => 'CANCELLED',
            'zavrsen' => 'CONFIRMED',
            default => 'TENTATIVE'
        };
        $lines[] = "STATUS:{$status}";

        // Categories
        $lines[] = 'CATEGORIES:' . $this->escapeString('Medicinski termin,WizMedik');

        // Alarm (reminder 30 minutes before)
        $lines[] = 'BEGIN:VALARM';
        $lines[] = 'TRIGGER:-PT30M';
        $lines[] = 'ACTION:DISPLAY';
        $lines[] = 'DESCRIPTION:' . $this->escapeString("Podsjetnik: Termin za 30 minuta");
        $lines[] = 'END:VALARM';

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Escape special characters for iCal format
     */
    private function escapeString($string)
    {
        $string = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $string);
        return $string;
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel($status)
    {
        return match($status) {
            'na_cekanju' => 'Na čekanju',
            'potvrdjen' => 'Potvrđen',
            'otkazan' => 'Otkazan',
            'zavrsen' => 'Završen',
            default => 'Nepoznat'
        };
    }
}
