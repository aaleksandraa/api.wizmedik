<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laboratorija;
use App\Models\Banja;
use App\Models\DomZaNjegu;
use App\Models\Pitanje;
use Illuminate\Http\Request;

class AdminEntitiesController extends Controller
{
    // LABORATORIES
    public function getLaboratories(Request $request)
    {
        $query = Laboratorija::query();

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function updateLaboratory(Request $request, $id)
    {
        $lab = Laboratorija::findOrFail($id);
        $lab->update($request->only(['aktivan']));
        return response()->json($lab);
    }

    public function deleteLaboratory($id)
    {
        Laboratorija::findOrFail($id)->delete();
        return response()->json(['message' => 'Laboratorija obrisana']);
    }

    // SPAS
    public function getSpas(Request $request)
    {
        $query = Banja::query();

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function updateSpa(Request $request, $id)
    {
        $spa = Banja::findOrFail($id);
        $spa->update($request->only(['aktivan']));
        return response()->json($spa);
    }

    public function deleteSpa($id)
    {
        Banja::findOrFail($id)->delete();
        return response()->json(['message' => 'Banja obrisana']);
    }

    // CARE HOMES
    public function getCareHomes(Request $request)
    {
        $query = DomZaNjegu::query();

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function updateCareHome(Request $request, $id)
    {
        $home = DomZaNjegu::findOrFail($id);
        $home->update($request->only(['aktivan']));
        return response()->json($home);
    }

    public function deleteCareHome($id)
    {
        DomZaNjegu::findOrFail($id)->delete();
        return response()->json(['message' => 'Dom obrisan']);
    }

    // QUESTIONS
    public function getQuestions(Request $request)
    {
        $query = Pitanje::with('korisnik:id,ime,prezime,email')
            ->orderBy('created_at', 'desc');

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = Pitanje::findOrFail($id);
        $question->update($request->only(['status']));
        return response()->json($question);
    }

    public function deleteQuestion($id)
    {
        Pitanje::findOrFail($id)->delete();
        return response()->json(['message' => 'Pitanje obrisano']);
    }
}
