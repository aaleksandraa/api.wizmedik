<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mkb10Kategorija;
use App\Models\Mkb10Podkategorija;
use App\Models\Mkb10Dijagnoza;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Mkb10Controller extends Controller
{
    /**
     * Dohvati sve kategorije sa brojem dijagnoza
     */
    public function kategorije(): JsonResponse
    {
        $kategorije = Mkb10Kategorija::aktivan()
            ->ordered()
            ->withCount('dijagnoze')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kategorije
        ]);
    }

    /**
     * Dohvati podkategorije za kategoriju
     */
    public function podkategorije(int $kategorijaId): JsonResponse
    {
        $podkategorije = Mkb10Podkategorija::where('kategorija_id', $kategorijaId)
            ->aktivan()
            ->ordered()
            ->withCount('dijagnoze')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $podkategorije
        ]);
    }

    /**
     * Dohvati dijagnoze za kategoriju ili podkategoriju
     */
    public function dijagnoze(Request $request): JsonResponse
    {
        $query = Mkb10Dijagnoza::aktivan()
            ->with(['kategorija:id,kod_od,kod_do,naziv', 'podkategorija:id,kod_od,kod_do,naziv']);

        if ($request->has('kategorija_id')) {
            $query->byKategorija($request->kategorija_id);
        }

        if ($request->has('podkategorija_id')) {
            $query->byPodkategorija($request->podkategorija_id);
        }

        if ($request->has('search') && strlen($request->search) >= 2) {
            $query->search($request->search);
        }

        $dijagnoze = $query->orderBy('kod')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $dijagnoze
        ]);
    }

    /**
     * Pretraga dijagnoza
     */
    public function pretraga(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        if (strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $dijagnoze = Mkb10Dijagnoza::aktivan()
            ->search($term)
            ->with(['kategorija:id,kod_od,kod_do,naziv'])
            ->orderBy('kod')
            ->limit(50)
            ->get(['id', 'kod', 'naziv', 'naziv_lat', 'kategorija_id']);

        return response()->json([
            'success' => true,
            'data' => $dijagnoze
        ]);
    }

    /**
     * Dohvati pojedinačnu dijagnozu
     */
    public function dijagnoza(string $kod): JsonResponse
    {
        $dijagnoza = Mkb10Dijagnoza::where('kod', $kod)
            ->with(['kategorija', 'podkategorija'])
            ->first();

        if (!$dijagnoza) {
            return response()->json([
                'success' => false,
                'message' => 'Dijagnoza nije pronađena'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $dijagnoza
        ]);
    }

    /**
     * Statistika
     */
    public function statistika(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ukupno_kategorija' => Mkb10Kategorija::aktivan()->count(),
                'ukupno_dijagnoza' => Mkb10Dijagnoza::aktivan()->count(),
            ]
        ]);
    }

    /**
     * Dohvati MKB-10 postavke
     */
    public function settings(): JsonResponse
    {
        // Direktno čitaj iz baze bez keša
        $setting = \App\Models\SiteSetting::where('key', 'mkb10_show_category_name_in_tabs')->first();
        $value = $setting ? $setting->value === 'true' : true;

        return response()->json([
            'success' => true,
            'data' => [
                'show_category_name_in_tabs' => $value,
            ]
        ]);
    }
}
