<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gostovanje;
use App\Models\Doktor;
use App\Models\Klinika;
use App\Services\NotifikacijaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GostovanjeController extends Controller
{
    public function clinicIndex(Request $request)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();

        if (!$klinika) {
            return response()->json(['error' => 'Klinika nije pronađena'], 404);
        }

        $query = Gostovanje::with(['doktor:id,ime,prezime,specijalnost,slika_profila,slug'])
            ->forClinic($klinika->id)
            ->orderBy('datum', 'asc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->upcoming === 'true') {
            $query->upcoming();
        }

        return response()->json($query->get());
    }

    public function doctorIndex(Request $request)
    {
        $user = Auth::user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            // Return empty array instead of 404
            return response()->json([]);
        }

        $query = Gostovanje::with(['klinika:id,naziv,adresa,grad,slug,slike'])
            ->forDoctor($doktor->id)
            ->orderBy('datum', 'asc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->upcoming === 'true') {
            $query->upcoming();
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();

        if (!$klinika) {
            return response()->json(['error' => 'Klinika nije pronađena'], 404);
        }

        $validated = $request->validate([
            'doktor_id' => 'required|exists:doktori,id',
            'datum' => 'required|date|after_or_equal:today',
            'vrijeme_od' => 'required|date_format:H:i',
            'vrijeme_do' => 'required|date_format:H:i|after:vrijeme_od',
            'slot_trajanje_minuti' => 'nullable|integer|min:10|max:120',
            'pauze' => 'nullable|array',
            'usluge' => 'nullable|array',
            'prihvata_online_rezervacije' => 'nullable|boolean',
            'napomena' => 'nullable|string|max:500',
        ]);

        $existing = Gostovanje::where('klinika_id', $klinika->id)
            ->where('doktor_id', $validated['doktor_id'])
            ->where('datum', $validated['datum'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Gostovanje za ovaj datum već postoji'], 422);
        }

        $gostovanje = Gostovanje::create([
            'klinika_id' => $klinika->id,
            'doktor_id' => $validated['doktor_id'],
            'datum' => $validated['datum'],
            'vrijeme_od' => $validated['vrijeme_od'],
            'vrijeme_do' => $validated['vrijeme_do'],
            'slot_trajanje_minuti' => $validated['slot_trajanje_minuti'] ?? 30,
            'pauze' => $validated['pauze'] ?? [],
            'usluge' => $validated['usluge'] ?? null,
            'prihvata_online_rezervacije' => $validated['prihvata_online_rezervacije'] ?? true,
            'napomena' => $validated['napomena'] ?? null,
            'status' => 'pending',
        ]);

        // Send notification to doctor
        $doktor = Doktor::find($validated['doktor_id']);
        if ($doktor) {
            NotifikacijaService::gostovanjePoziv($gostovanje, $klinika, $doktor);
        }

        return response()->json($gostovanje->load('doktor:id,ime,prezime,specijalnost'), 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();

        $gostovanje = Gostovanje::where('id', $id)
            ->where('klinika_id', $klinika->id)
            ->firstOrFail();

        if ($gostovanje->status === 'cancelled') {
            return response()->json(['error' => 'Ne možete ažurirati otkazano gostovanje'], 422);
        }

        $validated = $request->validate([
            'vrijeme_od' => 'sometimes|date_format:H:i',
            'vrijeme_do' => 'sometimes|date_format:H:i',
            'slot_trajanje_minuti' => 'nullable|integer|min:10|max:120',
            'pauze' => 'nullable|array',
            'usluge' => 'nullable|array',
            'prihvata_online_rezervacije' => 'nullable|boolean',
            'napomena' => 'nullable|string|max:500',
        ]);

        $gostovanje->update($validated);

        return response()->json($gostovanje->load('doktor:id,ime,prezime,specijalnost'));
    }

    public function respond(Request $request, $id)
    {
        $user = Auth::user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['error' => 'Doktor nije pronađen'], 404);
        }

        $gostovanje = Gostovanje::where('id', $id)
            ->where('doktor_id', $doktor->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled',
            'napomena' => 'nullable|string|max:500',
        ]);

        $gostovanje->update([
            'status' => $validated['status'],
            'napomena' => $validated['napomena'] ?? $gostovanje->napomena,
        ]);

        return response()->json($gostovanje->load('klinika:id,naziv,adresa,grad'));
    }

    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();
        $doktor = Doktor::where('user_id', $user->id)->first();

        $gostovanje = Gostovanje::where('id', $id)
            ->where(function($q) use ($klinika, $doktor) {
                if ($klinika) $q->orWhere('klinika_id', $klinika->id);
                if ($doktor) $q->orWhere('doktor_id', $doktor->id);
            })
            ->whereIn('status', ['pending', 'confirmed'])
            ->firstOrFail();

        $validated = $request->validate([
            'napomena' => 'nullable|string|max:500',
        ]);

        $gostovanje->update([
            'status' => 'cancelled',
            'napomena' => $validated['napomena'] ?? 'Otkazano',
        ]);

        return response()->json(['message' => 'Gostovanje otkazano']);
    }

    public function publicClinicSchedule($clinicId)
    {
        $gostovanja = Gostovanje::with(['doktor:id,ime,prezime,specijalnost,slika_profila,slug,ocjena'])
            ->where('klinika_id', $clinicId)
            ->confirmed()
            ->upcoming()
            ->orderBy('datum', 'asc')
            ->get();

        return response()->json($gostovanja);
    }

    public function searchDoctors(Request $request)
    {
        $query = Doktor::select('id', 'ime', 'prezime', 'specijalnost', 'grad', 'slika_profila', 'slug');

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ime', 'ilike', "%{$search}%")
                  ->orWhere('prezime', 'ilike', "%{$search}%")
                  ->orWhere('specijalnost', 'ilike', "%{$search}%")
                  ->orWhereRaw("CONCAT(ime, ' ', prezime) ILIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->specijalnost) {
            $query->where('specijalnost', 'ilike', "%{$request->specijalnost}%");
        }

        return response()->json($query->limit(20)->get());
    }

    /**
     * Public endpoint - get doctor's confirmed guest visits for profile display
     * Returns only confirmed visits where datum >= today with clinic info
     */
    public function publicDoctorVisits($doctorId)
    {
        $gostovanja = Gostovanje::with(['klinika:id,naziv,adresa,grad,slug,google_maps_link,telefon', 'aktivneUsluge'])
            ->where('doktor_id', $doctorId)
            ->confirmed()
            ->upcoming()
            ->orderBy('datum', 'asc')
            ->get()
            ->map(function ($gostovanje) {
                return [
                    'id' => $gostovanje->id,
                    'datum' => $gostovanje->datum->format('Y-m-d'),
                    'vrijeme_od' => $gostovanje->vrijeme_od,
                    'vrijeme_do' => $gostovanje->vrijeme_do,
                    'slot_trajanje_minuti' => $gostovanje->slot_trajanje_minuti,
                    'prihvata_online_rezervacije' => $gostovanje->prihvata_online_rezervacije,
                    'usluge' => $gostovanje->aktivneUsluge->map(function ($usluga) {
                        return [
                            'id' => $usluga->id,
                            'naziv' => $usluga->naziv,
                            'opis' => $usluga->opis,
                            'cijena' => $usluga->cijena,
                            'trajanje_minuti' => $usluga->trajanje_minuti,
                        ];
                    }),
                    'klinika' => $gostovanje->klinika ? [
                        'id' => $gostovanje->klinika->id,
                        'naziv' => $gostovanje->klinika->naziv,
                        'lokacija' => $gostovanje->klinika->adresa,
                        'grad' => $gostovanje->klinika->grad,
                        'slug' => $gostovanje->klinika->slug,
                        'google_maps_link' => $gostovanje->klinika->google_maps_link,
                        'telefon' => $gostovanje->klinika->telefon,
                    ] : null,
                ];
            });

        return response()->json($gostovanja);
    }

    /**
     * Add service to guest visit (by clinic or doctor)
     */
    public function addService(Request $request, $gostovanjeId)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();
        $doktor = Doktor::where('user_id', $user->id)->first();

        $gostovanje = Gostovanje::findOrFail($gostovanjeId);

        // Check authorization
        $dodao = null;
        if ($klinika && $gostovanje->klinika_id === $klinika->id) {
            $dodao = 'klinika';
        } elseif ($doktor && $gostovanje->doktor_id === $doktor->id) {
            $dodao = 'doktor';
        }

        if (!$dodao) {
            return response()->json(['error' => 'Nemate dozvolu za dodavanje usluge'], 403);
        }

        $validated = $request->validate([
            'naziv' => 'required|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'nullable|numeric|min:0',
            'trajanje_minuti' => 'required|integer|min:10|max:240',
        ]);

        $usluga = \App\Models\GostovanjeUsluga::create([
            'gostovanje_id' => $gostovanjeId,
            'naziv' => $validated['naziv'],
            'opis' => $validated['opis'] ?? null,
            'cijena' => $validated['cijena'] ?? null,
            'trajanje_minuti' => $validated['trajanje_minuti'],
            'dodao' => $dodao,
            'aktivna' => true,
        ]);

        return response()->json($usluga, 201);
    }

    /**
     * Update guest visit service
     */
    public function updateService(Request $request, $gostovanjeId, $uslugaId)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();
        $doktor = Doktor::where('user_id', $user->id)->first();

        $usluga = \App\Models\GostovanjeUsluga::where('id', $uslugaId)
            ->where('gostovanje_id', $gostovanjeId)
            ->firstOrFail();

        // Check authorization - only the one who added can edit
        $canEdit = false;
        if ($klinika && $usluga->dodao === 'klinika') {
            $gostovanje = Gostovanje::find($gostovanjeId);
            $canEdit = $gostovanje && $gostovanje->klinika_id === $klinika->id;
        } elseif ($doktor && $usluga->dodao === 'doktor') {
            $gostovanje = Gostovanje::find($gostovanjeId);
            $canEdit = $gostovanje && $gostovanje->doktor_id === $doktor->id;
        }

        if (!$canEdit) {
            return response()->json(['error' => 'Nemate dozvolu za izmjenu ove usluge'], 403);
        }

        $validated = $request->validate([
            'naziv' => 'sometimes|string|max:255',
            'opis' => 'nullable|string|max:1000',
            'cijena' => 'nullable|numeric|min:0',
            'trajanje_minuti' => 'sometimes|integer|min:10|max:240',
            'aktivna' => 'sometimes|boolean',
        ]);

        $usluga->update($validated);

        return response()->json($usluga);
    }

    /**
     * Delete guest visit service
     */
    public function deleteService($gostovanjeId, $uslugaId)
    {
        $user = Auth::user();
        $klinika = Klinika::where('user_id', $user->id)->first();
        $doktor = Doktor::where('user_id', $user->id)->first();

        $usluga = \App\Models\GostovanjeUsluga::where('id', $uslugaId)
            ->where('gostovanje_id', $gostovanjeId)
            ->firstOrFail();

        // Check authorization
        $canDelete = false;
        if ($klinika && $usluga->dodao === 'klinika') {
            $gostovanje = Gostovanje::find($gostovanjeId);
            $canDelete = $gostovanje && $gostovanje->klinika_id === $klinika->id;
        } elseif ($doktor && $usluga->dodao === 'doktor') {
            $gostovanje = Gostovanje::find($gostovanjeId);
            $canDelete = $gostovanje && $gostovanje->doktor_id === $doktor->id;
        }

        if (!$canDelete) {
            return response()->json(['error' => 'Nemate dozvolu za brisanje ove usluge'], 403);
        }

        $usluga->delete();

        return response()->json(['message' => 'Usluga obrisana']);
    }

    /**
     * Get services for a guest visit
     */
    public function getServices($gostovanjeId)
    {
        $usluge = \App\Models\GostovanjeUsluga::where('gostovanje_id', $gostovanjeId)
            ->where('aktivna', true)
            ->get();

        return response()->json($usluge);
    }
}
