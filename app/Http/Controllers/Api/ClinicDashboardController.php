<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klinika;
use App\Models\Doktor;
use App\Models\Termin;
use App\Models\User;
use App\Models\KlinikaDoktorZahtjev;
use App\Services\NotifikacijaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClinicDashboardController extends Controller
{
    /**
     * Get clinic's own profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)
            ->with(['doktori', 'specijalnosti'])
            ->first();

        if (!$klinika) {
            return response()->json(['message' => 'Profil klinike nije pronađen.'], 404);
        }

        // Add specialties IDs array for easier frontend handling
        $klinika->specijalnosti_ids = $klinika->specijalnosti->pluck('id')->toArray();

        return response()->json($klinika);
    }

    /**
     * Update clinic's own profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'naziv' => 'sometimes|string',
            'opis' => 'nullable|string',
            'adresa' => 'sometimes|string',
            'grad' => 'sometimes|string',
            'telefon' => 'sometimes|string',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'slike' => 'nullable|array',
            'radno_vrijeme' => 'nullable|array',
            'pauze' => 'nullable|array',
            'odmori' => 'nullable|array',
            'specijalnosti' => 'nullable|array',
            'specijalnosti.*' => 'exists:specijalnosti,id',
        ]);

        // Update basic fields
        $klinika->update(collect($validated)->except('specijalnosti')->toArray());

        // Sync specialties if provided
        if (isset($validated['specijalnosti'])) {
            $klinika->specijalnosti()->sync($validated['specijalnosti']);
        }

        // Reload with specialties
        $klinika->load('specijalnosti');

        return response()->json([
            'message' => 'Profil uspješno ažuriran',
            'klinika' => $klinika
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Trenutna lozinka nije ispravna'], 422);
        }

        // Update password
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Lozinka uspješno promijenjena']);
    }

    /**
     * Get all appointments for clinic's doctors
     */
    public function getAppointments(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        // Get all doctor IDs for this clinic
        $doctorIds = $klinika->doktori()->pluck('id');

        $query = Termin::whereIn('doktor_id', $doctorIds)
            ->with(['doktor', 'user', 'usluga'])
            ->orderBy('datum_vrijeme', 'desc');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by doctor if provided
        if ($request->has('doktor_id')) {
            $query->where('doktor_id', $request->doktor_id);
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('datum_vrijeme', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('datum_vrijeme', '<=', $request->end_date);
        }

        $appointments = $query->paginate(20);

        return response()->json($appointments);
    }

    /**
     * Get clinic's doctors
     */
    public function getDoctors(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $doctors = $klinika->doktori()
            ->with(['specijalnostModel'])
            ->get();

        return response()->json($doctors);
    }

    /**
     * Update appointment status (for clinic staff)
     */
    public function updateAppointmentStatus(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $doctorIds = $klinika->doktori()->pluck('id');

        $termin = Termin::whereIn('doktor_id', $doctorIds)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:zakazan,potvrden,otkazan,zavrshen',
        ]);

        $termin->update($validated);

        return response()->json([
            'message' => 'Status termina ažuriran',
            'termin' => $termin->load(['doktor', 'user', 'usluga'])
        ]);
    }

    /**
     * Get clinic statistics
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $doctorIds = $klinika->doktori()->pluck('id');

        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'total_doctors' => $klinika->doktori()->count(),
            'today_appointments' => Termin::whereIn('doktor_id', $doctorIds)
                ->whereDate('datum_vrijeme', $today)
                ->count(),
            'pending_appointments' => Termin::whereIn('doktor_id', $doctorIds)
                ->where('status', 'zakazan')
                ->whereDate('datum_vrijeme', '>=', $today)
                ->count(),
            'month_appointments' => Termin::whereIn('doktor_id', $doctorIds)
                ->where('datum_vrijeme', '>=', $thisMonth)
                ->count(),
            'completed_this_month' => Termin::whereIn('doktor_id', $doctorIds)
                ->where('status', 'zavrshen')
                ->where('datum_vrijeme', '>=', $thisMonth)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get appointments for a specific date (for calendar)
     */
    public function getAppointmentsByDate(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $clinicDoctorIds = $klinika->doktori()->pluck('id')->toArray();

        // Build query:
        // 1. All appointments for clinic's permanent doctors
        // 2. Appointments with klinika_id = this clinic (guest doctor appointments at this clinic)
        $query = Termin::with(['doktor:id,ime,prezime,specijalnost,slika_profila', 'user:id,ime,prezime,telefon,email', 'usluga:id,naziv,trajanje_minuti'])
            ->where(function($q) use ($clinicDoctorIds, $klinika) {
                // Permanent clinic doctors - all their appointments
                $q->whereIn('doktor_id', $clinicDoctorIds)
                  // OR appointments at this clinic (guest doctors)
                  ->orWhere('klinika_id', $klinika->id);
            })
            ->orderBy('datum_vrijeme', 'asc');

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('datum_vrijeme', $request->date);
        }

        // Filter by month (for calendar overview)
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('datum_vrijeme', $request->month)
                  ->whereYear('datum_vrijeme', $request->year);
        }

        // Filter by specific doctor
        if ($request->has('doktor_id') && $request->doktor_id) {
            $query->where('doktor_id', $request->doktor_id);
        }

        return response()->json($query->get());
    }

    /**
     * Get calendar data (appointments count per day)
     */
    public function getCalendarData(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $clinicDoctorIds = $klinika->doktori()->pluck('id')->toArray();

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Get all appointments that this clinic should see:
        // 1. All appointments for clinic's permanent doctors
        // 2. Appointments with klinika_id = this clinic (guest doctor appointments)
        $appointments = Termin::where(function($q) use ($clinicDoctorIds, $klinika) {
                $q->whereIn('doktor_id', $clinicDoctorIds)
                  ->orWhere('klinika_id', $klinika->id);
            })
            ->whereMonth('datum_vrijeme', $month)
            ->whereYear('datum_vrijeme', $year)
            ->selectRaw('DATE(datum_vrijeme) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        return response()->json($appointments);
    }

    /**
     * Create manual appointment (walk-in or phone booking)
     */
    public function createManualAppointment(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $doctorIds = $klinika->doktori()->pluck('id')->toArray();

        // Also allow booking for guest doctors - get all confirmed guest visits
        $guestVisits = \App\Models\Gostovanje::where('klinika_id', $klinika->id)
            ->where('status', 'confirmed')
            ->get();
        $guestDoctorIds = $guestVisits->pluck('doktor_id')->unique()->toArray();

        $allDoctorIds = array_unique(array_merge($doctorIds, $guestDoctorIds));

        $validated = $request->validate([
            'doktor_id' => 'required|exists:doktori,id',
            'datum_vrijeme' => 'required|date',
            'trajanje_minuti' => 'nullable|integer|min:5|max:180',
            'razlog' => 'nullable|string|max:500',
            'napomene' => 'nullable|string|max:1000',
            'guest_ime' => 'required|string|max:100',
            'guest_prezime' => 'required|string|max:100',
            'guest_telefon' => 'required|string|max:20',
            'guest_email' => 'nullable|email|max:100',
        ]);

        // Verify doctor belongs to clinic or is guest
        $doktorId = (int) $validated['doktor_id'];
        $isClinicDoctor = in_array($doktorId, $doctorIds);
        $isGuestDoctor = in_array($doktorId, $guestDoctorIds);

        if (!$isClinicDoctor && !$isGuestDoctor) {
            return response()->json([
                'message' => 'Doktor ne pripada ovoj klinici',
                'debug' => [
                    'doktor_id' => $doktorId,
                    'clinic_doctors' => $doctorIds,
                    'guest_doctors' => $guestDoctorIds
                ]
            ], 403);
        }

        // For guest doctors, verify the date matches a confirmed guest visit
        $gostovanjeId = null;
        $klinikaIdForTermin = null;

        if ($isGuestDoctor && !$isClinicDoctor) {
            $appointmentDate = date('Y-m-d', strtotime($validated['datum_vrijeme']));
            $doctorGuestVisits = $guestVisits->where('doktor_id', $doktorId);

            // Find the matching guest visit
            $matchingVisit = $doctorGuestVisits->first(function ($visit) use ($appointmentDate) {
                return $visit->datum->format('Y-m-d') === $appointmentDate;
            });

            if (!$matchingVisit) {
                $availableDates = $doctorGuestVisits->pluck('datum')->map(fn($d) => $d->format('Y-m-d'))->toArray();
                return response()->json([
                    'message' => 'Gostujući doktor nije dostupan na odabrani datum. Dostupni datumi: ' . implode(', ', $availableDates),
                    'available_dates' => $availableDates,
                    'requested_date' => $appointmentDate
                ], 422);
            }

            // Set gostovanje_id and klinika_id for the appointment
            $gostovanjeId = $matchingVisit->id;
            $klinikaIdForTermin = $klinika->id;
        }

        $termin = Termin::create([
            'user_id' => null,
            'doktor_id' => $validated['doktor_id'],
            'usluga_id' => null,
            'datum_vrijeme' => $validated['datum_vrijeme'],
            'razlog' => $validated['razlog'] ?? null,
            'napomene' => $validated['napomene'] ?? 'Ručno zakazan od strane klinike',
            'status' => 'zakazan',
            'trajanje_minuti' => $validated['trajanje_minuti'] ?? 30,
            'cijena' => null,
            'guest_ime' => $validated['guest_ime'],
            'guest_prezime' => $validated['guest_prezime'],
            'guest_telefon' => $validated['guest_telefon'],
            'guest_email' => $validated['guest_email'] ?? null,
            'gostovanje_id' => $gostovanjeId,
            'klinika_id' => $klinikaIdForTermin,
        ]);

        // Send notifications
        $termin->load(['doktor.klinika', 'usluga']);
        NotifikacijaService::terminZakazan($termin);

        return response()->json($termin->load(['doktor', 'usluga']), 201);
    }

    /**
     * Add a new doctor to the clinic
     */
    public function addDoctor(Request $request)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'ime' => 'required|string|max:100',
            'prezime' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'telefon' => 'required|string|max:50',
            'specijalnost' => 'required|string|max:100',
            'specijalnost_id' => 'nullable|exists:specijalnosti,id',
            'opis' => 'nullable|string',
            'slika_profila' => 'nullable|string',
        ]);

        // Create user account for doctor
        $doctorUser = User::create([
            'name' => $validated['ime'] . ' ' . $validated['prezime'],
            'ime' => $validated['ime'],
            'prezime' => $validated['prezime'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $doctorUser->assignRole('doctor');

        // Create doctor profile
        $doktor = Doktor::create([
            'user_id' => $doctorUser->id,
            'klinika_id' => $klinika->id,
            'ime' => $validated['ime'],
            'prezime' => $validated['prezime'],
            'email' => $validated['email'],
            'telefon' => $validated['telefon'],
            'specijalnost' => $validated['specijalnost'],
            'specijalnost_id' => $validated['specijalnost_id'] ?? null,
            'opis' => $validated['opis'] ?? null,
            'slika_profila' => $validated['slika_profila'] ?? null,
            'grad' => $klinika->grad,
            'lokacija' => $klinika->adresa,
            'latitude' => $klinika->latitude,
            'longitude' => $klinika->longitude,
        ]);

        return response()->json([
            'message' => 'Doktor uspješno dodan',
            'doktor' => $doktor->load('user')
        ], 201);
    }

    /**
     * Update a clinic's doctor
     */
    public function updateDoctor(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $doktor = Doktor::where('id', $id)->where('klinika_id', $klinika->id)->firstOrFail();

        $validated = $request->validate([
            'ime' => 'sometimes|string|max:100',
            'prezime' => 'sometimes|string|max:100',
            'email' => 'sometimes|email',
            'password' => 'nullable|string|min:8',
            'telefon' => 'sometimes|string|max:50',
            'specijalnost' => 'sometimes|string|max:100',
            'specijalnost_id' => 'nullable|exists:specijalnosti,id',
            'opis' => 'nullable|string',
            'slika_profila' => 'nullable|string',
        ]);

        // Check email uniqueness if changed
        if (isset($validated['email']) && $validated['email'] !== $doktor->email) {
            $existingUser = User::where('email', $validated['email'])->first();
            if ($existingUser && (!$doktor->user_id || $existingUser->id !== $doktor->user_id)) {
                return response()->json(['message' => 'Email je već u upotrebi'], 422);
            }
        }

        // Update user account if exists
        if ($doktor->user_id) {
            $doctorUser = User::find($doktor->user_id);
            if ($doctorUser) {
                if (isset($validated['email'])) {
                    $doctorUser->email = $validated['email'];
                }
                if (isset($validated['password'])) {
                    $doctorUser->password = Hash::make($validated['password']);
                }
                if (isset($validated['ime']) || isset($validated['prezime'])) {
                    $doctorUser->name = ($validated['ime'] ?? $doktor->ime) . ' ' . ($validated['prezime'] ?? $doktor->prezime);
                    $doctorUser->ime = $validated['ime'] ?? $doktor->ime;
                    $doctorUser->prezime = $validated['prezime'] ?? $doktor->prezime;
                }
                $doctorUser->save();
            }
        }

        // Remove password from doctor update
        unset($validated['password']);

        $doktor->update($validated);

        return response()->json([
            'message' => 'Doktor uspješno ažuriran',
            'doktor' => $doktor->fresh()->load('user')
        ]);
    }

    /**
     * Remove a doctor from the clinic
     */
    public function removeDoctor(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isClinic() && !$user->isAdmin()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();
        $doktor = Doktor::where('id', $id)->where('klinika_id', $klinika->id)->firstOrFail();

        // Soft delete the doctor
        $doktor->delete();

        // Optionally disable user account (don't delete to preserve history)
        if ($doktor->user_id) {
            // User account remains but doctor profile is deleted
        }

        return response()->json(['message' => 'Doktor uspješno uklonjen']);
    }

    /**
     * Search existing doctors to invite
     */
    public function searchExistingDoctors(Request $request)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $search = $request->input('search', '');

        $doctors = Doktor::where(function($q) use ($search) {
                $q->where('ime', 'ilike', "%{$search}%")
                  ->orWhere('prezime', 'ilike', "%{$search}%")
                  ->orWhere('specijalnost', 'ilike', "%{$search}%")
                  ->orWhereRaw("CONCAT(ime, ' ', prezime) ILIKE ?", ["%{$search}%"]);
            })
            ->where(function($q) use ($klinika) {
                $q->whereNull('klinika_id')
                  ->orWhere('klinika_id', '!=', $klinika->id);
            })
            ->select('id', 'ime', 'prezime', 'specijalnost', 'grad', 'slika_profila', 'slug', 'klinika_id')
            ->limit(20)
            ->get();

        return response()->json($doctors);
    }

    /**
     * Send invitation to existing doctor
     */
    public function inviteDoctor(Request $request)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'doktor_id' => 'required|exists:doktori,id',
            'poruka' => 'nullable|string|max:500',
        ]);

        $doktor = Doktor::findOrFail($validated['doktor_id']);

        // Check if already in this clinic
        if ($doktor->klinika_id === $klinika->id) {
            return response()->json(['message' => 'Doktor već radi u vašoj klinici'], 422);
        }

        // Check if pending request exists
        $existing = KlinikaDoktorZahtjev::where('klinika_id', $klinika->id)
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Zahtjev je već poslan ovom doktoru'], 422);
        }

        $zahtjev = KlinikaDoktorZahtjev::create([
            'klinika_id' => $klinika->id,
            'doktor_id' => $doktor->id,
            'initiated_by' => 'clinic',
            'poruka' => $validated['poruka'] ?? null,
            'status' => 'pending',
        ]);

        // Send notification to doctor
        NotifikacijaService::klinikaPozivDoktoru($zahtjev, $klinika, $doktor);

        return response()->json([
            'message' => 'Poziv uspješno poslan',
            'zahtjev' => $zahtjev->load(['doktor:id,ime,prezime,specijalnost'])
        ], 201);
    }

    /**
     * Get sent invitations
     */
    public function getInvitations(Request $request)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $zahtjevi = KlinikaDoktorZahtjev::where('klinika_id', $klinika->id)
            ->with(['doktor:id,ime,prezime,specijalnost,slika_profila,slug'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($zahtjevi);
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(Request $request, $id)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('klinika_id', $klinika->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $zahtjev->delete();

        return response()->json(['message' => 'Poziv otkazan']);
    }

    /**
     * Get requests from doctors wanting to join
     */
    public function getDoctorRequests(Request $request)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $zahtjevi = KlinikaDoktorZahtjev::where('klinika_id', $klinika->id)
            ->where('initiated_by', 'doctor')
            ->with(['doktor:id,ime,prezime,specijalnost,slika_profila,slug,grad'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($zahtjevi);
    }

    /**
     * Respond to doctor's request to join
     */
    public function respondToDoctorRequest(Request $request, $id)
    {
        $user = $request->user();
        $klinika = Klinika::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'odgovor' => 'nullable|string|max:500',
        ]);

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('klinika_id', $klinika->id)
            ->where('initiated_by', 'doctor')
            ->where('status', 'pending')
            ->firstOrFail();

        $zahtjev->update([
            'status' => $validated['status'],
            'odgovor' => $validated['odgovor'] ?? null,
            'odgovoreno_at' => now(),
        ]);

        // If accepted, link doctor to clinic
        if ($validated['status'] === 'accepted') {
            Doktor::where('id', $zahtjev->doktor_id)->update(['klinika_id' => $klinika->id]);
        }

        return response()->json([
            'message' => $validated['status'] === 'accepted' ? 'Zahtjev prihvaćen' : 'Zahtjev odbijen',
            'zahtjev' => $zahtjev->load('doktor:id,ime,prezime,specijalnost')
        ]);
    }
}
