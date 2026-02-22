<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Termin;
use App\Models\Doktor;
use App\Models\Usluga;
use App\Services\NotifikacijaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function myAppointments(Request $request)
    {
        $user = auth()->user();

        $termini = Termin::where('user_id', $user->id)
            ->with(['doktor', 'usluga'])
            ->orderBy('datum_vrijeme', 'desc')
            ->get();

        return response()->json($termini);
    }

    public function doctorAppointments(Request $request)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['error' => 'Doktor profil nije pronađen'], 404);
        }

        $termini = Termin::where('doktor_id', $doktor->id)
            ->with(['user', 'usluga'])
            ->orderBy('datum_vrijeme', 'desc')
            ->get();

        return response()->json($termini);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doktor_id' => 'required|exists:doktori,id',
            'usluga_id' => 'nullable|exists:usluge,id',
            'datum_vrijeme' => 'required|date|after:now',
            'razlog' => 'nullable|string|max:500',
            'napomene' => 'nullable|string|max:1000',
            'gostovanje_id' => 'nullable|exists:klinika_doktor_gostovanja,id',
            'klinika_id' => 'nullable|exists:klinike,id',
        ]);

        $user = auth()->user();
        $doktor = Doktor::findOrFail($validated['doktor_id']);

        $trajanje = 30;
        $cijena = null;

        if (!empty($validated['usluga_id'])) {
            $usluga = Usluga::find($validated['usluga_id']);
            if ($usluga) {
                $trajanje = $usluga->trajanje_minuti;
                $cijena = $usluga->cijena;
            }
        }

        // Use transaction with lock to prevent double booking
        try {
            $termin = DB::transaction(function () use ($validated, $user, $doktor, $trajanje, $cijena) {
                // Check for existing appointment at the same time (with lock)
                $existingAppointment = Termin::where('doktor_id', $validated['doktor_id'])
                    ->where('datum_vrijeme', $validated['datum_vrijeme'])
                    ->whereIn('status', ['zakazan', 'potvrden', 'završen'])
                    ->lockForUpdate()
                    ->first();

                if ($existingAppointment) {
                    throw new \Exception('Ovaj termin je već zauzet. Molimo izaberite drugo vrijeme.');
                }

                // Determine status based on doctor's auto_potvrda setting
                $status = $doktor->auto_potvrda ? 'potvrden' : 'zakazan';

                return Termin::create([
                    'user_id' => $user->id,
                    'doktor_id' => $validated['doktor_id'],
                    'usluga_id' => $validated['usluga_id'] ?? null,
                    'datum_vrijeme' => $validated['datum_vrijeme'],
                    'razlog' => $validated['razlog'] ?? null,
                    'napomene' => $validated['napomene'] ?? null,
                    'status' => $status,
                    'trajanje_minuti' => $trajanje,
                    'cijena' => $cijena,
                    'gostovanje_id' => $validated['gostovanje_id'] ?? null,
                    'klinika_id' => $validated['klinika_id'] ?? null,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => $e->getMessage()], 409);
        }

        // Send notifications (must not break booking response)
        try {
            $termin->load(['doktor.klinika', 'user', 'usluga', 'gostovanje.klinika']);
            NotifikacijaService::terminZakazan($termin);
        } catch (\Throwable $notificationError) {
            Log::error('Appointment notification failed after booking', [
                'termin_id' => $termin->id,
                'error' => $notificationError->getMessage(),
            ]);
        }

        return response()->json($termin->load(['doktor', 'usluga', 'gostovanje.klinika']), 201);
    }

    public function storeGuest(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();

        $doktor = Doktor::findOrFail($validated['doktor_id']);

        $trajanje = 30;
        $cijena = null;

        if (!empty($validated['usluga_id'])) {
            $usluga = Usluga::find($validated['usluga_id']);
            if ($usluga) {
                $trajanje = $usluga->trajanje_minuti;
                $cijena = $usluga->cijena;
            }
        }

        // Use transaction with lock to prevent double booking
        try {
            $termin = DB::transaction(function () use ($validated, $doktor, $trajanje, $cijena) {
                // Check for existing appointment at the same time (with lock)
                $existingAppointment = Termin::where('doktor_id', $validated['doktor_id'])
                    ->where('datum_vrijeme', $validated['datum_vrijeme'])
                    ->whereIn('status', ['zakazan', 'potvrden', 'završen'])
                    ->lockForUpdate()
                    ->first();

                if ($existingAppointment) {
                    throw new \Exception('Ovaj termin je već zauzet. Molimo izaberite drugo vrijeme.');
                }

                // Determine status based on doctor's auto_potvrda setting
                $status = $doktor->auto_potvrda ? 'potvrden' : 'zakazan';

                return Termin::create([
                    'user_id' => null,
                    'doktor_id' => $validated['doktor_id'],
                    'usluga_id' => $validated['usluga_id'] ?? null,
                    'datum_vrijeme' => $validated['datum_vrijeme'],
                    'razlog' => $validated['razlog'] ?? null,
                    'napomene' => $validated['napomene'] ?? null,
                    'status' => $status,
                    'trajanje_minuti' => $trajanje,
                    'cijena' => $cijena,
                    'guest_ime' => $validated['guest_ime'],
                    'guest_prezime' => $validated['guest_prezime'],
                    'guest_telefon' => $validated['guest_telefon'],
                    'guest_email' => $validated['guest_email'] ?? null,
                    'gostovanje_id' => $validated['gostovanje_id'] ?? null,
                    'klinika_id' => $validated['klinika_id'] ?? null,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => $e->getMessage()], 409);
        }

        // Send notifications (must not break booking response)
        try {
            $termin->load(['doktor.klinika', 'usluga', 'gostovanje.klinika']);
            NotifikacijaService::terminZakazan($termin);
        } catch (\Throwable $notificationError) {
            Log::error('Guest appointment notification failed after booking', [
                'termin_id' => $termin->id,
                'error' => $notificationError->getMessage(),
            ]);
        }

        return response()->json($termin->load(['doktor', 'usluga', 'gostovanje.klinika']), 201);
    }

    public function storeManual(Request $request)
    {
        $user = auth()->user();
        $doktor = Doktor::where('user_id', $user->id)->first();

        if (!$doktor) {
            return response()->json(['error' => 'Doktor profil nije pronađen'], 404);
        }

        $validated = $request->validate([
            'usluga_id' => 'nullable|exists:usluge,id',
            'datum_vrijeme' => 'required|date',
            'razlog' => 'nullable|string|max:500',
            'napomene' => 'nullable|string|max:1000',
            'guest_ime' => 'required|string|max:100',
            'guest_prezime' => 'required|string|max:100',
            'guest_telefon' => 'required|string|max:20',
            'guest_email' => 'nullable|email|max:100',
        ]);

        $trajanje = 30;
        $cijena = null;

        if (!empty($validated['usluga_id'])) {
            $usluga = Usluga::find($validated['usluga_id']);
            if ($usluga) {
                $trajanje = $usluga->trajanje_minuti;
                $cijena = $usluga->cijena;
            }
        }

        // Use transaction with lock to prevent double booking
        try {
            $termin = DB::transaction(function () use ($validated, $doktor, $trajanje, $cijena) {
                // Check for existing appointment at the same time (with lock)
                $existingAppointment = Termin::where('doktor_id', $doktor->id)
                    ->where('datum_vrijeme', $validated['datum_vrijeme'])
                    ->whereIn('status', ['zakazan', 'potvrden', 'završen'])
                    ->lockForUpdate()
                    ->first();

                if ($existingAppointment) {
                    throw new \Exception('Ovaj termin je već zauzet. Molimo izaberite drugo vrijeme.');
                }

                // Manual appointments by doctor are auto-confirmed
                return Termin::create([
                    'user_id' => null,
                    'doktor_id' => $doktor->id,
                    'usluga_id' => $validated['usluga_id'] ?? null,
                    'datum_vrijeme' => $validated['datum_vrijeme'],
                    'razlog' => $validated['razlog'] ?? null,
                    'napomene' => $validated['napomene'] ?? null,
                    'status' => 'potvrden',
                    'trajanje_minuti' => $trajanje,
                    'cijena' => $cijena,
                    'guest_ime' => $validated['guest_ime'],
                    'guest_prezime' => $validated['guest_prezime'],
                    'guest_telefon' => $validated['guest_telefon'],
                    'guest_email' => $validated['guest_email'] ?? null,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => $e->getMessage()], 409);
        }

        // Send notifications (must not break booking response)
        try {
            $termin->load(['doktor.klinika', 'usluga']);
            NotifikacijaService::terminZakazan($termin);
        } catch (\Throwable $notificationError) {
            Log::error('Manual appointment notification failed after booking', [
                'termin_id' => $termin->id,
                'error' => $notificationError->getMessage(),
            ]);
        }

        return response()->json($termin->load(['doktor', 'usluga']), 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:zakazan,potvrden,završen,otkazan',
        ]);

        $user = auth()->user();
        $termin = Termin::findOrFail($id);

        // Check if user is the doctor for this appointment
        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor || $termin->doktor_id !== $doktor->id) {
            return response()->json(['error' => 'Nemate dozvolu za ažuriranje ovog termina'], 403);
        }

        $termin->update(['status' => $validated['status']]);

        return response()->json($termin);
    }

    public function reschedule(Request $request, $id)
    {
        $validated = $request->validate([
            'datum_vrijeme' => 'required|date|after:now',
        ]);

        $user = auth()->user();
        $termin = Termin::findOrFail($id);

        // Check if user owns this appointment
        if ($termin->user_id !== $user->id) {
            return response()->json(['error' => 'Nemate dozvolu za izmjenu ovog termina'], 403);
        }

        if (!$termin->canBeRescheduled()) {
            return response()->json(['error' => 'Ovaj termin se ne može premjestiti'], 400);
        }

        $termin->update(['datum_vrijeme' => $validated['datum_vrijeme']]);

        return response()->json($termin->load(['doktor', 'usluga']));
    }

    public function cancel($id)
    {
        $user = auth()->user();
        $termin = Termin::with(['doktor.klinika', 'user', 'usluga'])->findOrFail($id);

        // Check if user owns this appointment, is the doctor, or is clinic manager
        $doktor = Doktor::where('user_id', $user->id)->first();
        $isDoctor = $doktor && $termin->doktor_id === $doktor->id;
        $isOwner = $termin->user_id === $user->id;

        // Check if user is clinic manager
        $klinika = \App\Models\Klinika::where('user_id', $user->id)->first();
        $isClinicManager = $klinika && $termin->doktor && $termin->doktor->klinika_id === $klinika->id;

        if (!$isOwner && !$isDoctor && !$isClinicManager) {
            return response()->json(['error' => 'Nemate dozvolu za otkazivanje ovog termina'], 403);
        }

        if (!$termin->canBeCancelled()) {
            return response()->json(['error' => 'Ovaj termin se ne može otkazati'], 400);
        }

        // Determine who cancelled
        $cancelledBy = 'patient';
        if ($isDoctor) {
            $cancelledBy = 'doctor';
        } elseif ($isClinicManager) {
            $cancelledBy = 'clinic';
        }

        $termin->update(['status' => 'otkazan']);

        // Send cancellation notifications
        NotifikacijaService::terminOtkazan($termin, $cancelledBy);

        return response()->json(['message' => 'Termin je uspješno otkazan']);
    }
}
