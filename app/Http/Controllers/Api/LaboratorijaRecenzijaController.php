<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaboratorijaRecenzija;
use App\Models\Laboratorija;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class LaboratorijaRecenzijaController extends Controller
{
    /**
     * Dohvati sve odobrene recenzije za laboratoriju
     */
    public function index($laboratorijaId)
    {
        $cacheKey = "laboratorija_recenzije_{$laboratorijaId}";

        $recenzije = Cache::remember($cacheKey, 600, function () use ($laboratorijaId) {
            return LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
                ->odobreno()
                ->with('user:id,ime,prezime')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return response()->json($recenzije);
    }

    /**
     * Kreiraj novu recenziju
     */
    public function store(Request $request, $laboratorijaId)
    {
        // Rate limiting - 3 recenzije po satu po IP adresi
        $key = 'review_attempt_' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        $validated = $request->validate([
            'ocjena' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
            'ime' => 'required_without:user_id|string|max:100',
        ]);

        // Provjeri da li laboratorija postoji
        $laboratorija = Laboratorija::findOrFail($laboratorijaId);

        $user = auth()->user();

        // Ako je korisnik prijavljen, provjeri da li već ima recenziju
        if ($user) {
            $existing = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => 'Već ste ostavili recenziju za ovu laboratoriju'
                ], 403);
            }
        }

        // Kreiraj recenziju
        $recenzija = LaboratorijaRecenzija::create([
            'laboratorija_id' => $laboratorijaId,
            'user_id' => $user?->id,
            'ime' => $user ? null : $validated['ime'],
            'ocjena' => $validated['ocjena'],
            'komentar' => $validated['komentar'] ?? null,
            'verifikovano' => $user ? true : false, // Prijavljeni korisnici su automatski verifikovani
            'odobreno' => false, // Sve recenzije čekaju odobrenje
            'ip_adresa' => $request->ip(),
        ]);

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");
        Cache::forget("laboratorija_stats_{$laboratorijaId}");

        // Log
        Log::info("Nova recenzija za laboratoriju {$laboratorijaId}", [
            'recenzija_id' => $recenzija->id,
            'user_id' => $user?->id,
            'ocjena' => $validated['ocjena'],
        ]);

        return response()->json([
            'message' => 'Recenzija uspješno poslata. Čeka odobrenje administratora.',
            'recenzija' => $recenzija->load('user')
        ], 201);
    }

    /**
     * Ažuriraj recenziju (samo vlasnik)
     */
    public function update(Request $request, $laboratorijaId, $recenzijaId)
    {
        $validated = $request->validate([
            'ocjena' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        $recenzija = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->findOrFail($recenzijaId);

        $user = auth()->user();

        // Samo vlasnik može editovati
        if (!$user || $recenzija->user_id !== $user->id) {
            return response()->json(['error' => 'Nemate dozvolu za editovanje ove recenzije'], 403);
        }

        // Ne može se editovati ako je već odobrena
        if ($recenzija->odobreno) {
            return response()->json(['error' => 'Ne možete editovati odobrenu recenziju'], 403);
        }

        $recenzija->update($validated);

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");

        return response()->json($recenzija->load('user'));
    }

    /**
     * Obriši recenziju
     */
    public function destroy($laboratorijaId, $recenzijaId)
    {
        $recenzija = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->findOrFail($recenzijaId);

        $user = auth()->user();

        // Samo vlasnik ili admin može obrisati
        if (!$user || ($recenzija->user_id !== $user->id && $user->tip !== 'admin')) {
            return response()->json(['error' => 'Nemate dozvolu za brisanje ove recenzije'], 403);
        }

        $recenzija->delete();

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");
        Cache::forget("laboratorija_stats_{$laboratorijaId}");

        return response()->json(['message' => 'Recenzija uspješno obrisana']);
    }

    /**
     * Dohvati statistiku recenzija
     */
    public function stats($laboratorijaId)
    {
        $cacheKey = "laboratorija_stats_{$laboratorijaId}";

        $stats = Cache::remember($cacheKey, 600, function () use ($laboratorijaId) {
            $laboratorija = Laboratorija::findOrFail($laboratorijaId);

            $recenzije = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
                ->odobreno()
                ->select('ocjena')
                ->get();

            $distribution = [
                5 => $recenzije->where('ocjena', 5)->count(),
                4 => $recenzije->where('ocjena', 4)->count(),
                3 => $recenzije->where('ocjena', 3)->count(),
                2 => $recenzije->where('ocjena', 2)->count(),
                1 => $recenzije->where('ocjena', 1)->count(),
            ];

            return [
                'average' => (float) $laboratorija->prosjecna_ocjena,
                'total' => (int) $laboratorija->broj_recenzija,
                'distribution' => $distribution,
                'rating_display' => $laboratorija->rating_display,
            ];
        });

        return response()->json($stats);
    }

    /**
     * Admin: Dohvati sve recenzije (uključujući neodobrene)
     */
    public function adminIndex(Request $request, $laboratorijaId)
    {
        $this->authorize('viewAny', LaboratorijaRecenzija::class);

        $query = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->with('user:id,ime,prezime');

        // Filter po statusu
        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->where('odobreno', false);
            } elseif ($request->status === 'approved') {
                $query->where('odobreno', true);
            }
        }

        // Filter po verifikaciji
        if ($request->has('verified')) {
            $query->where('verifikovano', $request->verified === 'true');
        }

        $recenzije = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($recenzije);
    }

    /**
     * Admin: Odobri recenziju
     */
    public function approve($laboratorijaId, $recenzijaId)
    {
        $this->authorize('update', LaboratorijaRecenzija::class);

        $recenzija = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->findOrFail($recenzijaId);

        $recenzija->approve();

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");
        Cache::forget("laboratorija_stats_{$laboratorijaId}");

        return response()->json([
            'message' => 'Recenzija odobrena',
            'recenzija' => $recenzija
        ]);
    }

    /**
     * Admin: Odbij recenziju
     */
    public function reject($laboratorijaId, $recenzijaId)
    {
        $this->authorize('update', LaboratorijaRecenzija::class);

        $recenzija = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->findOrFail($recenzijaId);

        $recenzija->disapprove();

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");
        Cache::forget("laboratorija_stats_{$laboratorijaId}");

        return response()->json([
            'message' => 'Recenzija odbijena',
            'recenzija' => $recenzija
        ]);
    }

    /**
     * Admin: Bulk odobri recenzije
     */
    public function bulkApprove(Request $request, $laboratorijaId)
    {
        $this->authorize('update', LaboratorijaRecenzija::class);

        $validated = $request->validate([
            'recenzija_ids' => 'required|array',
            'recenzija_ids.*' => 'integer|exists:laboratorija_recenzije,id',
        ]);

        $count = LaboratorijaRecenzija::where('laboratorija_id', $laboratorijaId)
            ->whereIn('id', $validated['recenzija_ids'])
            ->update(['odobreno' => true]);

        // Invalidate cache
        Cache::forget("laboratorija_recenzije_{$laboratorijaId}");
        Cache::forget("laboratorija_stats_{$laboratorijaId}");

        return response()->json([
            'message' => "{$count} recenzija odobreno",
            'count' => $count
        ]);
    }
}
