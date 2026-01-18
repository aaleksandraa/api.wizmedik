<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pitanje;
use App\Models\OdgovorNaPitanje;
use App\Models\NotifikacijaPitanja;
use App\Models\Doktor;
use App\Services\NotifikacijaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PitanjeController extends Controller
{
    /**
     * Lista svih javnih pitanja sa filterima
     */
    public function index(Request $request)
    {
        $query = Pitanje::with([
            'specijalnost',
            'odgovori.doktor.user',
            'odgovori.doktor.specijalnosti'
        ])
            ->where('je_javno', true)
            ->orderBy('created_at', 'desc');

        // Filter po specijalnosti (jedna ili više)
        if ($request->has('specijalnost_ids')) {
            $ids = explode(',', $request->specijalnost_ids);
            $query->whereIn('specijalnost_id', $ids);
        } elseif ($request->has('specijalnost_id')) {
            $query->poSpecijalnosti($request->specijalnost_id);
        }

        // Filter po statusu odgovora
        if ($request->has('odgovoreno')) {
            if ($request->odgovoreno === 'true' || $request->odgovoreno === '1') {
                $query->odgovorena();
            } else {
                $query->neodgovorena();
            }
        }

        // Pretraga
        if ($request->has('pretraga') && !empty($request->pretraga)) {
            $query->pretraga($request->pretraga);
        }

        // Filter po tagovima
        if ($request->has('tagovi') && is_array($request->tagovi)) {
            $query->poTagovima($request->tagovi);
        }

        $pitanja = $query->paginate(20);

        return response()->json($pitanja);
    }

    /**
     * Detalji pojedinačnog pitanja
     */
    public function show($slug)
    {
        try {
            $pitanje = Pitanje::with([
                'specijalnost',
                'odgovori' => function ($query) {
                    $query->orderBy('je_prihvacen', 'desc')
                          ->orderBy('broj_lajkova', 'desc')
                          ->orderBy('created_at', 'asc');
                },
                'odgovori.doktor.user',
                'odgovori.doktor.specijalnosti'
            ])
            ->where('slug', $slug)
            ->where('je_javno', true)
            ->firstOrFail();

            $pitanje->increment('broj_pregleda');

            return response()->json($pitanje);
        } catch (\Exception $e) {
            \Log::error('Greška pri učitavanju pitanja: ' . $e->getMessage());
            return response()->json(['message' => 'Pitanje nije pronađeno'], 404);
        }
    }

    /**
     * Postavi novo pitanje (sa ili bez autentikacije)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'naslov' => 'required|string|min:10|max:200',
            'sadrzaj' => 'required|string|min:20|max:5000',
            'ime_korisnika' => 'required|string|max:100',
            'email_korisnika' => 'nullable|email|max:100',
            'specijalnost_id' => 'required|exists:specijalnosti,id',
            'tagovi' => 'nullable|array|max:5',
            'tagovi.*' => 'string|max:50',
            'captcha_token' => 'required|string', // Za Google reCAPTCHA ili hCaptcha
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validacija nije uspjela',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Verifikuj CAPTCHA token
        // $this->verifyCaptcha($request->captcha_token);

        // Spam protection - max 3 pitanja po IP u 24h (skip for logged-in users)
        $ipAdresa = $request->ip();
        if (!$user) {
            $brojPitanjaOdIp = Pitanje::where('ip_adresa', $ipAdresa)
                ->where('created_at', '>', now()->subDay())
                ->count();

            if ($brojPitanjaOdIp >= 3) {
                return response()->json([
                    'message' => 'Dostigli ste maksimalan broj pitanja za danas. Pokušajte sutra.'
                ], 429);
            }
        }

        $pitanje = Pitanje::create([
            'user_id' => $user?->id,
            'naslov' => $request->naslov,
            'sadrzaj' => $request->sadrzaj,
            'ime_korisnika' => $user ? "{$user->ime} {$user->prezime}" : $request->ime_korisnika,
            'email_korisnika' => $user ? $user->email : $request->email_korisnika,
            'specijalnost_id' => $request->specijalnost_id,
            'tagovi' => $request->tagovi,
            'ip_adresa' => $ipAdresa,
            'je_javno' => true,
        ]);

        // Kreiraj notifikacije za sve doktore sa tom specijalnosti
        $this->kreirajNotifikacijeZaDoktore($pitanje);

        return response()->json([
            'message' => 'Pitanje je uspješno postavljeno!',
            'pitanje' => $pitanje->load('specijalnost')
        ], 201);
    }

    /**
     * Odgovori na pitanje (samo doktor)
     */
    public function odgovori(Request $request, $pitanjeId)
    {
        $user = $request->user();

        if (!$user->hasRole('doctor')) {
            return response()->json(['message' => 'Samo doktori mogu odgovarati na pitanja'], 403);
        }

        $doktor = Doktor::where('email', $user->email)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }
        $pitanje = Pitanje::findOrFail($pitanjeId);

        // Provjeri da li doktor ima odgovarajuću specijalnost
        $imaSpecijalnost = $doktor->specijalnosti()
            ->where('specijalnosti.id', $pitanje->specijalnost_id)
            ->exists();

        if (!$imaSpecijalnost) {
            return response()->json([
                'message' => 'Možete odgovarati samo na pitanja iz vaše specijalnosti'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'sadrzaj' => 'required|string|min:20|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validacija nije uspjela',
                'errors' => $validator->errors()
            ], 422);
        }

        // Provjeri da li je doktor već odgovorio
        $postojeciOdgovor = OdgovorNaPitanje::where('pitanje_id', $pitanjeId)
            ->where('doktor_id', $doktor->id)
            ->first();

        if ($postojeciOdgovor) {
            return response()->json([
                'message' => 'Već ste odgovorili na ovo pitanje'
            ], 422);
        }

        $odgovor = OdgovorNaPitanje::create([
            'pitanje_id' => $pitanjeId,
            'doktor_id' => $doktor->id,
            'sadrzaj' => $request->sadrzaj,
        ]);

        // Load doktor relationship for notification
        $odgovor->load('doktor.user', 'doktor.specijalnosti');

        // Označi notifikaciju kao pročitanu
        NotifikacijaPitanja::where('pitanje_id', $pitanjeId)
            ->where('doktor_id', $doktor->id)
            ->update(['je_procitano' => true, 'procitano_u' => now()]);

        // Send notification to question author (logged-in user or guest with email)
        NotifikacijaService::odgovorNaPitanje($pitanje, $odgovor);

        return response()->json([
            'message' => 'Odgovor je uspješno postavljen!',
            'odgovor' => $odgovor
        ], 201);
    }

    /**
     * Notifikacije za doktora
     */
    public function notifikacije(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('doctor')) {
            return response()->json(['message' => 'Samo doktori mogu pristupiti notifikacijama'], 403);
        }

        $doktor = Doktor::where('email', $user->email)->first();
        if (!$doktor) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $notifikacije = NotifikacijaPitanja::with(['pitanje.specijalnost'])
            ->zaDoktora($doktor->id)
            ->orderBy('je_procitano', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifikacije);
    }

    /**
     * Označi notifikaciju kao pročitanu
     */
    public function oznaciNotifikacijuKaoProcitanu(Request $request, $notifikacijaId)
    {
        $user = $request->user();
        $doktor = Doktor::where('email', $user->email)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }

        $notifikacija = NotifikacijaPitanja::where('id', $notifikacijaId)
            ->where('doktor_id', $doktor->id)
            ->firstOrFail();

        $notifikacija->oznacKaoProcitano();

        return response()->json(['message' => 'Notifikacija označena kao pročitana']);
    }

    /**
     * Lajkuj odgovor
     */
    public function lajkujOdgovor($odgovorId)
    {
        $odgovor = OdgovorNaPitanje::findOrFail($odgovorId);
        $odgovor->povecajLajkove();

        return response()->json([
            'message' => 'Hvala na ocjeni!',
            'broj_lajkova' => $odgovor->broj_lajkova
        ]);
    }

    /**
     * Popularni tagovi
     */
    public function popularniTagovi()
    {
        $pitanja = Pitanje::javna()
            ->whereNotNull('tagovi')
            ->get();

        $tagovi = [];
        foreach ($pitanja as $pitanje) {
            if (is_array($pitanje->tagovi)) {
                foreach ($pitanje->tagovi as $tag) {
                    if (!isset($tagovi[$tag])) {
                        $tagovi[$tag] = 0;
                    }
                    $tagovi[$tag]++;
                }
            }
        }

        arsort($tagovi);
        $popularni = array_slice($tagovi, 0, 20, true);

        return response()->json(
            array_map(function ($tag, $count) {
                return ['tag' => $tag, 'count' => $count];
            }, array_keys($popularni), $popularni)
        );
    }

    /**
     * Kreiraj notifikacije za sve doktore sa datom specijalnosti
     */
    private function kreirajNotifikacijeZaDoktore(Pitanje $pitanje)
    {
        $doktori = Doktor::whereHas('specijalnosti', function ($query) use ($pitanje) {
            $query->where('specijalnosti.id', $pitanje->specijalnost_id);
        })->get();

        // Create NotifikacijaPitanja entries (for tracking in pitanja system)
        foreach ($doktori as $doktor) {
            NotifikacijaPitanja::create([
                'pitanje_id' => $pitanje->id,
                'doktor_id' => $doktor->id,
            ]);
        }

        // Also create main notifications (for Navbar bell icon)
        NotifikacijaService::novoPitanje($pitanje, $doktori);
    }
}
