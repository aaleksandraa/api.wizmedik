<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Usluga, Doktor};
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function myServices(Request $request)
    {
        $user = $request->user();
        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json([]);
        }

        $services = Usluga::where('doktor_id', $doktor->id)->get();

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }

        $validated = $request->validate([
            'naziv' => 'required|string',
            'opis' => 'nullable|string',
            'cijena' => 'nullable|numeric',
            'trajanje_minuti' => 'required|integer|min:5',
            'aktivan' => 'boolean',
        ]);

        $service = Usluga::create([
            'doktor_id' => $doktor->id,
            ...$validated
        ]);

        return response()->json(['message' => 'Service created', 'service' => $service], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }
        $service = Usluga::where('doktor_id', $doktor->id)->findOrFail($id);

        $service->update($request->all());

        return response()->json(['message' => 'Service updated', 'service' => $service]);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doktor = Doktor::where('user_id', $user->id)->first();
        if (!$doktor) {
            return response()->json(['message' => 'Profil doktora nije pronađen'], 404);
        }
        $service = Usluga::where('doktor_id', $doktor->id)->findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }
}
