<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recenzija;
use App\Models\Termin;
use App\Models\Doktor;
use App\Models\Klinika;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RecenzijaController extends Controller
{
    /**
     * Kreiraj novu recenziju
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'termin_id' => 'required|exists:termini,id',
            'recenziran_type' => 'required|in:App\Models\Doktor,App\Models\Klinika',
            'recenziran_id' => 'required|integer',
            'ocjena' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        
        if (!$user || $user->tip !== 'pacijent') {
            return response()->json(['error' => 'Samo pacijenti mogu ostavljati recenzije'], 403);
        }

        $termin = Termin::findOrFail($validated['termin_id']);
        
        // Validacije
        if ($termin->user_id !== $user->id) {
            return response()->json(['error' => 'Možete recenzirati samo svoje termine'], 403);
        }

        if ($termin->status !== 'završen') {
            return response()->json(['error' => 'Možete recenzirati samo završene termine'], 403);
        }

        if ($termin->datum_vrijeme > now()) {
            return response()->json(['error' => 'Možete recenzirati samo prošle termine'], 403);
        }

        // Provjeri da li već postoji recenzija
        $existingRecenzija = Recenzija::where('user_id', $user->id)
            ->where('termin_id', $validated['termin_id'])
            ->first();

        if ($existingRecenzija) {
            return response()->json(['error' => 'Već ste ostavili recenziju za ovaj termin'], 403);
        }

        // Kreiraj recenziju
        $recenzija = Recenzija::create([
            'user_id' => $user->id,
            'termin_id' => $validated['termin_id'],
            'recenziran_type' => $validated['recenziran_type'],
            'recenziran_id' => $validated['recenziran_id'],
            'ocjena' => $validated['ocjena'],
            'komentar' => $validated['komentar'],
        ]);

        // Ažuriraj prosječnu ocjenu i invalidate cache
        $this->updateAverageRating($validated['recenziran_type'], $validated['recenziran_id']);

        return response()->json($recenzija->load('user'), 201);
    }

    /**
     * Ažuriraj postojeću recenziju
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'ocjena' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        $recenzija = Recenzija::findOrFail($id);
        $user = auth()->user();

        if ($recenzija->user_id !== $user->id) {
            return response()->json(['error' => 'Možete editovati samo svoje recenzije'], 403);
        }

        $recenzija->update($validated);

        // Ažuriraj prosječnu ocjenu i invalidate cache
        $this->updateAverageRating($recenzija->recenziran_type, $recenzija->recenziran_id);

        return response()->json($recenzija->load('user'));
    }

    /**
     * Obriši recenziju
     */
    public function destroy($id)
    {
        $recenzija = Recenzija::findOrFail($id);
        $user = auth()->user();

        // Provjera dozvola
        if ($user->tip === 'pacijent' && $recenzija->user_id !== $user->id) {
            return response()->json(['error' => 'Možete obrisati samo svoje recenzije'], 403);
        }

        if ($user->tip !== 'admin' && $user->tip !== 'pacijent') {
            return response()->json(['error' => 'Nemate dozvolu za brisanje recenzija'], 403);
        }

        $recenziranType = $recenzija->recenziran_type;
        $recenziranId = $recenzija->recenziran_id;

        $recenzija->delete();

        // Ažuriraj prosječnu ocjenu nakon brisanja i invalidate cache
        $this->updateAverageRating($recenziranType, $recenziranId);

        return response()->json(['message' => 'Recenzija uspješno obrisana'], 200);
    }

    /**
     * Dodaj odgovor na recenziju (za doktore/klinike)
     */
    public function addResponse(Request $request, $id)
    {
        $validated = $request->validate([
            'odgovor' => 'required|string|max:1000',
        ]);

        $recenzija = Recenzija::findOrFail($id);
        $user = auth()->user();

        // Provjeri da li je već odgovoreno
        if ($recenzija->odgovor) {
            return response()->json(['error' => 'Već ste odgovorili na ovu recenziju'], 403);
        }

        $canRespond = false;

        // Doktor može odgovoriti na recenzije svog profila
        if ($user->tip === 'doktor' && $recenzija->recenziran_type === 'App\Models\Doktor') {
            $doktor = Doktor::where('user_id', $user->id)->first();
            if ($doktor && $doktor->id === $recenzija->recenziran_id) {
                $canRespond = true;
            }
        }

        // Klinika može odgovoriti na recenzije svog profila
        if ($user->tip === 'klinika' && $recenzija->recenziran_type === 'App\Models\Klinika') {
            $klinika = Klinika::where('user_id', $user->id)->first();
            if ($klinika && $klinika->id === $recenzija->recenziran_id) {
                $canRespond = true;
            }
        }

        if (!$canRespond) {
            return response()->json(['error' => 'Možete odgovoriti samo na recenzije na svom profilu'], 403);
        }

        $recenzija->update([
            'odgovor' => $validated['odgovor'],
            'odgovor_datum' => now(),
        ]);

        // Invalidate recenzije cache
        $this->invalidateRecenzijeCache($recenzija->recenziran_type, $recenzija->recenziran_id);

        return response()->json($recenzija->load('user'));
    }

    /**
     * Dohvati sve recenzije za doktora (sa cachingom)
     */
    public function getByDoktor($doktorId)
    {
        $cacheKey = "recenzije_doktor_{$doktorId}";
        
        $recenzije = Cache::remember($cacheKey, 300, function () use ($doktorId) {
            return Recenzija::where('recenziran_type', 'App\Models\Doktor')
                ->where('recenziran_id', $doktorId)
                ->with('user:id,ime,prezime')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return response()->json($recenzije);
    }

    /**
     * Dohvati sve recenzije za kliniku (sa cachingom)
     */
    public function getByKlinika($klinikaId)
    {
        $cacheKey = "recenzije_klinika_{$klinikaId}";
        
        $recenzije = Cache::remember($cacheKey, 300, function () use ($klinikaId) {
            return Recenzija::where('recenziran_type', 'App\Models\Klinika')
                ->where('recenziran_id', $klinikaId)
                ->with('user:id,ime,prezime')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return response()->json($recenzije);
    }

    /**
     * Dohvati statistiku ocjena za doktora ili kliniku (sa cachingom)
     */
    public function getRatingStats($type, $id)
    {
        // Validacija type parametra
        if (!in_array($type, ['doktor', 'klinika'])) {
            return response()->json(['error' => 'Invalid type. Must be "doktor" or "klinika"'], 400);
        }

        // Cache key
        $cacheKey = "rating_stats_{$type}_{$id}";
        
        // Cache na 10 minuta (600 sekundi)
        $data = Cache::remember($cacheKey, 600, function () use ($type, $id) {
            // Dohvati model
            $modelClass = $type === 'doktor' ? Doktor::class : Klinika::class;
            $model = $modelClass::find($id);
            
            if (!$model) {
                return null;
            }

            // Dohvati sve recenzije
            $recenziranType = $type === 'doktor' ? 'App\Models\Doktor' : 'App\Models\Klinika';
            $recenzije = Recenzija::where('recenziran_type', $recenziranType)
                ->where('recenziran_id', $id)
                ->select('ocjena')
                ->get();

            // Računaj distribuciju
            $distribution = [
                5 => $recenzije->where('ocjena', 5)->count(),
                4 => $recenzije->where('ocjena', 4)->count(),
                3 => $recenzije->where('ocjena', 3)->count(),
                2 => $recenzije->where('ocjena', 2)->count(),
                1 => $recenzije->where('ocjena', 1)->count(),
            ];

            return [
                'average' => (float) $model->ocjena,
                'total' => (int) $model->broj_ocjena,
                'distribution' => $distribution,
                'rating_display' => $model->getRatingDisplayAttribute(),
                'rating_percentage' => $model->getRatingPercentageAttribute(),
            ];
        });

        if (!$data) {
            return response()->json(['error' => ucfirst($type) . ' not found'], 404);
        }

        return response()->json($data);
    }

    /**
     * Provjeri da li korisnik može recenzirati određeni termin
     */
    public function canReview($terminId)
    {
        $user = auth()->user();
        
        if (!$user || $user->tip !== 'pacijent') {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Samo pacijenti mogu recenzirati'
            ]);
        }

        $termin = Termin::find($terminId);
        
        if (!$termin) {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Termin ne postoji'
            ]);
        }

        if ($termin->user_id !== $user->id) {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Nije vaš termin'
            ]);
        }

        if ($termin->status !== 'završen') {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Termin nije završen'
            ]);
        }

        if ($termin->datum_vrijeme > now()) {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Termin je u budućnosti'
            ]);
        }

        $existingRecenzija = Recenzija::where('user_id', $user->id)
            ->where('termin_id', $terminId)
            ->first();

        if ($existingRecenzija) {
            return response()->json([
                'can_review' => false, 
                'reason' => 'Već ste recenzirali', 
                'recenzija_id' => $existingRecenzija->id
            ]);
        }

        return response()->json(['can_review' => true]);
    }

    /**
     * Dohvati sve recenzije trenutnog korisnika (pacijenta)
     */
    public function myRecenzije()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $recenzije = Recenzija::where('user_id', $user->id)
            ->with(['termin.doktor', 'recenziran'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($recenzije);
    }

    /**
     * Dohvati termine koji mogu biti recenzirani
     */
    public function getEligibleTermini()
    {
        $user = auth()->user();
        
        if (!$user || $user->tip !== 'pacijent') {
            return response()->json(['termini' => []]);
        }

        $termini = Termin::where('user_id', $user->id)
            ->where('status', 'završen')
            ->where('datum_vrijeme', '<', now())
            ->whereDoesntHave('recenzija')
            ->with(['doktor:id,ime,prezime', 'klinika:id,naziv'])
            ->orderBy('datum_vrijeme', 'desc')
            ->limit(50) // Ograniči broj rezultata
            ->get();

        return response()->json(['termini' => $termini]);
    }

    /**
     * Privatna metoda za ažuriranje prosječne ocjene
     */
    private function updateAverageRating($type, $id)
    {
        try {
            // Računaj average i count u jednom query-u
            $stats = Recenzija::where('recenziran_type', $type)
                ->where('recenziran_id', $id)
                ->selectRaw('ROUND(AVG(ocjena), 1) as avg_ocjena, COUNT(*) as total')
                ->first();

            $avgRating = $stats->avg_ocjena ?? 0;
            $count = $stats->total ?? 0;

            // Ažuriraj odgovarajući model
            if ($type === 'App\Models\Doktor') {
                Doktor::where('id', $id)->update([
                    'ocjena' => $avgRating,
                    'broj_ocjena' => $count,
                ]);
            } elseif ($type === 'App\Models\Klinika') {
                Klinika::where('id', $id)->update([
                    'ocjena' => $avgRating,
                    'broj_ocjena' => $count,
                ]);
            }

            // Invalidate cache
            $this->invalidateRecenzijeCache($type, $id);

            Log::info("Updated rating for {$type} ID {$id}: {$avgRating} ({$count} reviews)");
        } catch (\Exception $e) {
            Log::error("Error updating average rating: " . $e->getMessage());
        }
    }

    /**
     * Invalidate sve cache-ove vezane za recenzije
     */
    private function invalidateRecenzijeCache($type, $id)
    {
        $cacheType = $type === 'App\Models\Doktor' ? 'doktor' : 'klinika';
        
        // Clear rating stats cache
        Cache::forget("rating_stats_{$cacheType}_{$id}");
        
        // Clear recenzije cache
        Cache::forget("recenzije_{$cacheType}_{$id}");
    }
}