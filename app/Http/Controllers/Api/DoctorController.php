<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doktor;
use App\Models\Klinika;
use App\Models\KlinikaDoktorZahtjev;
use App\Services\NotifikacijaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    /**
     * Get list of doctors with filters
     */
    public function index(Request $request)
    {
        // Limit per_page to prevent performance issues
        $perPage = min($request->get('per_page', 15), 100);

        $query = Doktor::query()
            ->select([
                'id', 'ime', 'prezime', 'slug', 'specijalnost', 'grad',
                'ocjena', 'broj_ocjena', 'slika_profila', 'klinika_id',
                'prihvata_online'
            ])
            ->with([
                'specijalnostModel:id,naziv,slug',
                'klinika:id,naziv,grad,slug',
                'specijalnosti:id,naziv,slug'
            ])
            ->aktivan()
            ->verifikovan();

        // Filters
        if ($request->has('grad')) {
            $query->byCity($request->grad);
        }

        if ($request->has('specijalnost')) {
            $query->bySpecialty($request->specijalnost);
        }

        if ($request->has('klinika_id')) {
            $query->where('klinika_id', $request->klinika_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->boolean('prihvata_online')) {
            $query->acceptingOnline();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'ocjena');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $doctors = $query->paginate($perPage);

        return response()->json($doctors);
    }

    /**
     * Get doctor by slug
     */
    public function show($slug)
    {
        $doktor = Doktor::where('slug', $slug)
            ->with([
                'specijalnostModel',
                'klinika',
                'specijalnosti',
                'kategorijeUsluga' => function($query) {
                    $query->where('aktivan', true)->orderBy('redoslijed')->orderBy('naziv');
                },
                'kategorijeUsluga.usluge' => function($query) {
                    $query->where('aktivan', true)->orderBy('redoslijed')->orderBy('naziv');
                }
            ])
            ->first();

        if (!$doktor) {
            return response()->json([
                'message' => 'Doktor nije pronađen',
                'slug' => $slug
            ], 404);
        }

        // Check if doctor is active and verified
        if (!$doktor->aktivan || !$doktor->verifikovan) {
            return response()->json([
                'message' => 'Doktor trenutno nije dostupan',
                'slug' => $slug,
                'aktivan' => $doktor->aktivan,
                'verifikovan' => $doktor->verifikovan
            ], 404);
        }

        // Ensure the relationship is loaded
        $doktor->load(['kategorijeUsluga' => function($query) {
            $query->where('aktivan', true)->orderBy('redoslijed')->orderBy('naziv');
        }, 'kategorijeUsluga.usluge' => function($query) {
            $query->where('aktivan', true)->orderBy('redoslijed')->orderBy('naziv');
        }]);

        // Convert to array and manually add kategorije
        $doktorArray = $doktor->toArray();
        $doktorArray['kategorije_usluga'] = $doktor->kategorijeUsluga->map(function($kategorija) {
            return [
                'id' => $kategorija->id,
                'naziv' => $kategorija->naziv,
                'opis' => $kategorija->opis,
                'redoslijed' => $kategorija->redoslijed,
                'aktivan' => $kategorija->aktivan,
                'usluge' => $kategorija->usluge->map(function($usluga) {
                    return [
                        'id' => $usluga->id,
                        'naziv' => $usluga->naziv,
                        'opis' => $usluga->opis,
                        'cijena' => $usluga->cijena,
                        'cijena_popust' => $usluga->cijena_popust,
                        'trajanje_minuti' => $usluga->trajanje_minuti,
                        'kategorija_id' => $usluga->kategorija_id,
                        'redoslijed' => $usluga->redoslijed,
                        'aktivan' => $usluga->aktivan,
                    ];
                })->toArray()
            ];
        })->toArray();

        return response()->json($doktorArray);
    }

    public function getServices($id)
    {
        $services = \App\Models\Usluga::where('doktor_id', $id)
            ->where('aktivan', true)
            ->select('id', 'naziv', 'cijena', 'cijena_popust', 'trajanje_minuti', 'opis', 'kategorija_id', 'redoslijed')
            ->orderBy('redoslijed')
            ->orderBy('naziv')
            ->get();

        return response()->json($services);
    }
    /**
     * Get doctor by ID
     */
    public function showById($id)
    {
        $doktor = Doktor::where('id', $id)
            ->with([
                'specijalnostModel',
                'klinika',
                'specijalnosti',
                'usluge' => function($query) {
                    $query->where('aktivan', true);
                },
                'recenzije' => function($query) {
                    $query->with('user:id,ime,prezime')->latest()->limit(10);
                }
            ])
            ->firstOrFail();

        return response()->json($doktor);
    }

    /**
     * Get available time slots for doctor
     */
    public function availableSlots(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $doktor = Doktor::findOrFail($id);
        $date = $request->date;

        // Get doctor's working hours for the day
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        $radnoVrijeme = $doktor->radno_vrijeme[$dayOfWeek] ?? null;

        if (!$radnoVrijeme || !$radnoVrijeme['radi']) {
            return response()->json(['slots' => []]);
        }

        // Generate time slots
        $slots = $this->generateTimeSlots(
            $radnoVrijeme['od'],
            $radnoVrijeme['do'],
            $doktor->slot_trajanje_minuti
        );

        // Get all booked appointments for the day (all non-cancelled statuses)
        $bookedAppointments = $doktor->termini()
            ->whereDate('datum_vrijeme', $date)
            ->whereIn('status', ['zakazan', 'potvrden', 'završen'])
            ->select('datum_vrijeme', 'trajanje_minuti')
            ->get();

        // Remove booked slots (considering appointment duration)
        $bookedSlots = [];
        foreach ($bookedAppointments as $appointment) {
            $startTime = strtotime($appointment->datum_vrijeme);
            $duration = $appointment->trajanje_minuti;
            $slotDuration = $doktor->slot_trajanje_minuti;

            // Block all slots that overlap with this appointment
            $slotsToBlock = ceil($duration / $slotDuration);
            for ($i = 0; $i < $slotsToBlock; $i++) {
                $blockedTime = date('H:i', $startTime + ($i * $slotDuration * 60));
                $bookedSlots[] = $blockedTime;
            }
        }

        $availableSlots = array_diff($slots, array_unique($bookedSlots));

        return response()->json(['slots' => array_values($availableSlots)]);
    }

    /**
     * Get booked slots for doctor
     */
    public function bookedSlots(Request $request, $id)
    {
        $doktor = Doktor::findOrFail($id);

        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->addMonth()->toDateString());

        // Include all non-cancelled appointments (zakazan, potvrden, završen)
        // This ensures slots are blocked immediately when booked
        $bookedSlots = $doktor->termini()
            ->whereBetween('datum_vrijeme', [$startDate, $endDate])
            ->whereIn('status', ['zakazan', 'potvrden', 'završen'])
            ->select('datum_vrijeme', 'trajanje_minuti')
            ->get();

        return response()->json(['booked_slots' => $bookedSlots]);
    }

    /**
     * Get doctor's own profile (for authenticated doctor)
     */
    public function myProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)
            ->with(['specijalnostModel', 'klinika', 'usluge', 'specijalnosti'])
            ->first();

        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen.'], 404);
        }

        $doktor->setAttribute('account_email', $user->email);

        return response()->json($doktor);
    }

    /**
     * Update doctor's own profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        if ($request->has('public_email') && $request->input('public_email') === '') {
            $request->merge(['public_email' => null]);
        }

        $validated = $request->validate([
            'telefon' => 'sometimes|string|max:20',
            'opis' => 'sometimes|string',
            'youtube_linkovi' => 'sometimes|nullable|array',
            'youtube_linkovi.*.url' => 'nullable|url',
            'youtube_linkovi.*.naslov' => 'nullable|string|max:255',
            'lokacija' => 'sometimes|string',
            'postanski_broj' => 'sometimes|string|max:10',
            'mjesto' => 'sometimes|string',
            'opstina' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'google_maps_link' => 'sometimes|nullable|url',
            'slika_profila' => 'sometimes|string',
            'public_email' => 'sometimes|nullable|email:rfc|max:255',
            'account_email' => 'sometimes|required|email:rfc|max:255|unique:users,email,' . $user->id,
            // klinika_id removed - doctors can only join/leave clinics through requests
            'specialty_ids' => 'sometimes|array',
            'specialty_ids.*' => 'exists:specijalnosti,id',
            'prihvata_online' => 'sometimes|boolean',
            'auto_potvrda' => 'sometimes|boolean',
            'slot_trajanje_minuti' => 'sometimes|integer|min:5|max:120',
            'radno_vrijeme' => 'sometimes|array',
            'pauze' => 'sometimes|array',
            'odmori' => 'sometimes|array',
            'telemedicine_enabled' => 'sometimes|boolean',
            'telemedicine_phone' => 'sometimes|nullable|string|max:50',
        ]);

        // Filter out empty YouTube links
        if (isset($validated['youtube_linkovi'])) {
            $validated['youtube_linkovi'] = array_values(array_filter($validated['youtube_linkovi'], function($item) {
                return !empty($item['url']) && !empty($item['naslov']);
            }));
        }

        $specialtyIds = $validated['specialty_ids'] ?? null;
        $accountEmail = $validated['account_email'] ?? null;
        unset($validated['specialty_ids'], $validated['account_email']);

        DB::transaction(function () use ($doktor, $validated, $specialtyIds, $accountEmail, $user) {
            $doktor->update($validated);

            // Sync specialties if provided
            if ($specialtyIds !== null) {
                $doktor->specijalnosti()->sync($specialtyIds);
            }

            // Login email belongs to users table.
            if ($accountEmail !== null && $accountEmail !== $user->email) {
                $user->email = $accountEmail;
                $user->save();
            }
        });

        $updatedDoktor = $doktor->fresh()->load('specijalnosti');
        $updatedDoktor->setAttribute('account_email', $user->fresh()->email);

        return response()->json([
            'message' => 'Profil uspješno ažuriran',
            'doktor' => $updatedDoktor
        ]);
    }

    /**
     * Update doctor's schedule
     */
    public function updateSchedule(Request $request)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'radno_vrijeme' => 'sometimes|array',
            'pauze' => 'sometimes|array',
            'odmori' => 'sometimes|array',
        ]);

        $doktor->update($validated);

        return response()->json([
            'message' => 'Raspored uspješno ažuriran',
            'doktor' => $doktor
        ]);
    }

    /**
     * Helper function to generate time slots
     */
    private function generateTimeSlots($start, $end, $interval)
    {
        $slots = [];
        $current = strtotime($start);
        $end = strtotime($end);

        while ($current < $end) {
            $slots[] = date('H:i', $current);
            $current = strtotime("+{$interval} minutes", $current);
        }

        return $slots;
    }

    /**
     * Get clinic invitations for doctor
     */
    public function getClinicInvitations(Request $request)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        // Find doctor by linked user account (same as myProfile)
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            // Return empty array instead of 404 to avoid breaking the frontend
            return response()->json([]);
        }

        $zahtjevi = KlinikaDoktorZahtjev::where('doktor_id', $doktor->id)
            ->with(['klinika:id,naziv,adresa,grad,slug,slike'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($zahtjevi);
    }

    /**
     * Respond to clinic invitation
     */
    public function respondToInvitation(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen.'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
            'odgovor' => 'nullable|string|max:500',
        ]);

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $zahtjev->update([
            'status' => $validated['status'],
            'odgovor' => $validated['odgovor'] ?? null,
            'odgovoreno_at' => now(),
        ]);

        // If accepted, link doctor to clinic
        if ($validated['status'] === 'accepted') {
            $doktor->update(['klinika_id' => $zahtjev->klinika_id]);
        }

        return response()->json([
            'message' => $validated['status'] === 'accepted' ? 'Poziv prihvaćen' : 'Poziv odbijen',
            'zahtjev' => $zahtjev->load('klinika:id,naziv,grad')
        ]);
    }

    /**
     * Search clinics to request joining
     */
    public function searchClinics(Request $request)
    {
        $user = $request->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        $search = $request->input('search', '');

        $clinics = \App\Models\Klinika::where(function($q) use ($search) {
                $q->where('naziv', 'ilike', "%{$search}%")
                  ->orWhere('grad', 'ilike', "%{$search}%");
            })
            ->where('aktivan', true)
            ->when($doktor && $doktor->klinika_id, function($q) use ($doktor) {
                $q->where('id', '!=', $doktor->klinika_id);
            })
            ->select('id', 'naziv', 'adresa', 'grad', 'slug', 'slike')
            ->limit(20)
            ->get();

        return response()->json($clinics);
    }

    /**
     * Send request to join a clinic
     */
    public function requestToJoinClinic(Request $request)
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Nemate dozvolu za pristup.'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen.'], 404);
        }

        $validated = $request->validate([
            'klinika_id' => 'required|exists:klinike,id',
            'poruka' => 'nullable|string|max:500',
        ]);

        // Check if already in this clinic
        if ($doktor->klinika_id === (int)$validated['klinika_id']) {
            return response()->json(['message' => 'Već radite u ovoj klinici'], 422);
        }

        // Check if pending request exists
        $existing = KlinikaDoktorZahtjev::where('klinika_id', $validated['klinika_id'])
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Zahtjev je već poslan ovoj klinici'], 422);
        }

        $zahtjev = KlinikaDoktorZahtjev::create([
            'klinika_id' => $validated['klinika_id'],
            'doktor_id' => $doktor->id,
            'initiated_by' => 'doctor',
            'poruka' => $validated['poruka'] ?? null,
            'status' => 'pending',
        ]);

        // Send notification to clinic
        $klinika = Klinika::find($validated['klinika_id']);
        if ($klinika) {
            NotifikacijaService::doktorZahtjevKlinici($zahtjev, $klinika, $doktor);
        }

        return response()->json([
            'message' => 'Zahtjev uspješno poslan',
            'zahtjev' => $zahtjev->load(['klinika:id,naziv,grad'])
        ], 201);
    }

    /**
     * Cancel request to join clinic
     */
    public function cancelClinicRequest(Request $request, $id)
    {
        $user = $request->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen.'], 404);
        }

        $zahtjev = KlinikaDoktorZahtjev::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->where('initiated_by', 'doctor')
            ->where('status', 'pending')
            ->firstOrFail();

        $zahtjev->delete();

        return response()->json(['message' => 'Zahtjev otkazan']);
    }

    /**
     * Leave current clinic
     */
    public function leaveClinic(Request $request)
    {
        $user = $request->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen.'], 404);
        }

        if (!$doktor->klinika_id) {
            return response()->json(['message' => 'Niste povezani sa klinikom.'], 422);
        }

        $doktor->update(['klinika_id' => null]);

        return response()->json(['message' => 'Uspješno ste napustili kliniku']);
    }

    /**
     * Get public guest visits for a doctor
     */
    public function publicGuestVisits(int $id)
    {
        $doktor = Doktor::findOrFail($id);

        $gostovanja = \App\Models\DoktorGostovanje::with(['klinika'])
            ->where('doktor_id', $id)
            ->where('status', 'confirmed')
            ->where('datum_od', '>=', now())
            ->orderBy('datum_od')
            ->get();

        return response()->json($gostovanja);
    }
}
