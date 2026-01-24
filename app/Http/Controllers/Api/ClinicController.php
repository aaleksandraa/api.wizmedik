<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klinika;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 15), 50);

        $query = Klinika::active()
            ->verifikovan()
            ->select('id', 'naziv', 'slug', 'grad', 'adresa', 'telefon', 'email',
                     'ocjena', 'broj_ocjena', 'slike', 'latitude', 'longitude')
            ->with(['doktori' => function($q) {
                $q->select('id', 'ime', 'prezime', 'slug', 'specijalnost', 'ocjena',
                          'slika_profila', 'klinika_id')
                  ->aktivan()
                  ->verifikovan();
            }]);

        if ($request->has('grad')) {
            $query->byCity($request->grad);
        }

        if ($request->has('search')) {
            $query->where('naziv', 'ilike', '%'.$request->search.'%');
        }

        // Apply limit if requested
        if ($request->has('limit')) {
            $limit = min($request->get('limit'), 50);
            return response()->json($query->limit($limit)->get());
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($slug)
    {
        $clinic = Klinika::where('slug', $slug)
            ->with(['doktori' => function($q) {
                $q->aktivan()->verifikovan();
            }])
            ->first();

        if (!$clinic) {
            return response()->json([
                'message' => 'Klinika nije pronađena',
                'slug' => $slug
            ], 404);
        }

        // Check if clinic is active and verified
        if (!$clinic->aktivan || !$clinic->verifikovan) {
            return response()->json([
                'message' => 'Klinika trenutno nije dostupna',
                'slug' => $slug,
                'aktivan' => $clinic->aktivan,
                'verifikovan' => $clinic->verifikovan
            ], 404);
        }

        return response()->json($clinic);
    }
}
