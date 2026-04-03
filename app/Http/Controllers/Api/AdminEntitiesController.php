<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banja;
use App\Models\Dom;
use App\Models\Laboratorija;
use App\Models\Pitanje;
use App\Services\AdminProfileAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminEntitiesController extends Controller
{
    public function __construct(private AdminProfileAccessService $profileAccessService)
    {
    }

    // LABORATORIES
    public function getLaboratories(Request $request)
    {
        $query = Laboratorija::query()
            ->with(['user:id,name,ime,prezime,email,role']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('grad')) {
            $query->where('grad', $request->grad);
        }

        if ($request->has('aktivan')) {
            $query->where('aktivan', $request->boolean('aktivan'));
        }

        if ($request->has('verifikovan')) {
            $query->where('verifikovan', $request->boolean('verifikovan'));
        }

        $query->orderBy(
            $request->get('sort_by', 'created_at'),
            $request->get('sort_order', 'desc')
        );

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function showLaboratory(int $id)
    {
        return response()->json(
            Laboratorija::with(['user:id,name,ime,prezime,email,role'])->findOrFail($id)
        );
    }

    public function createLaboratory(Request $request)
    {
        $validated = $this->validateLaboratoryPayload($request);

        $laboratory = DB::transaction(function () use ($validated, $request) {
            $profileData = collect($validated)
                ->except(['account_email', 'password'])
                ->all();

            $profileData['aktivan'] = $request->boolean('aktivan', true);
            $profileData['verifikovan'] = $request->boolean('verifikovan', true);
            $profileData['verifikovan_at'] = $profileData['verifikovan'] ? now() : null;

            $laboratory = Laboratorija::create($profileData);

            $this->profileAccessService->sync($laboratory, $validated, [
                'role' => 'laboratory',
                'model_class' => Laboratorija::class,
                'entity_label' => 'laboratorija',
                'name' => fn (Laboratorija $entity) => $entity->naziv,
            ]);

            return $laboratory->fresh()->load('user');
        });

        return response()->json([
            'message' => 'Laboratorija je uspjesno kreirana.',
            'laboratory' => $laboratory,
        ], 201);
    }

    public function updateLaboratory(Request $request, int $id)
    {
        $laboratory = Laboratorija::findOrFail($id);
        $validated = $this->validateLaboratoryPayload($request, $laboratory);

        $laboratory = DB::transaction(function () use ($laboratory, $validated, $request) {
            $laboratory->update(collect($validated)
                ->except(['account_email', 'password'])
                ->all());

            $this->profileAccessService->sync($laboratory, $validated, [
                'role' => 'laboratory',
                'model_class' => Laboratorija::class,
                'entity_label' => 'laboratorija',
                'name' => fn (Laboratorija $entity) => $entity->naziv,
            ]);

            if ($request->boolean('verifikovan')) {
                $laboratory->forceFill([
                    'verifikovan_at' => $laboratory->verifikovan_at ?? now(),
                ])->save();
            } elseif ($request->has('verifikovan') && !$request->boolean('verifikovan')) {
                $laboratory->forceFill([
                    'verifikovan_at' => null,
                ])->save();
            }

            return $laboratory->fresh()->load('user');
        });

        return response()->json($laboratory);
    }

    public function deleteLaboratory(int $id)
    {
        Laboratorija::findOrFail($id)->delete();

        return response()->json(['message' => 'Laboratorija obrisana']);
    }

    public function sendLaboratoryAccessInvite(Request $request, int $id)
    {
        $laboratory = Laboratorija::findOrFail($id);
        $validated = $request->validate([
            'account_email' => 'nullable|email|max:255',
        ]);

        $result = $this->profileAccessService->sendInvitation($laboratory, $validated, [
            'role' => 'laboratory',
            'model_class' => Laboratorija::class,
            'entity_label' => 'laboratorija',
            'invitation_label' => 'profil laboratorije',
            'name' => fn (Laboratorija $entity) => $entity->naziv,
        ]);

        return response()->json([
            'message' => 'Pozivnica za pristup je uspjesno poslana.',
            'laboratory' => $laboratory->fresh()->load('user'),
            'invitation' => [
                'sent_to' => $result['sent_to'],
                'sent_at' => $result['invitation_sent_at'],
            ],
        ]);
    }

    // SPAS
    public function getSpas(Request $request)
    {
        $query = Banja::query()
            ->with([
                'user:id,name,ime,prezime,email,role',
                'vrste:id,naziv,slug',
                'indikacije:id,naziv,slug',
                'terapije:id,naziv,slug',
            ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('grad')) {
            $query->poGradu($request->grad);
        }

        if ($request->has('aktivan')) {
            $query->where('aktivan', $request->boolean('aktivan'));
        }

        if ($request->has('verifikovan')) {
            $query->where('verifikovan', $request->boolean('verifikovan'));
        }

        $query->orderBy(
            $request->get('sort_by', 'created_at'),
            $request->get('sort_order', 'desc')
        );

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function showSpa(int $id)
    {
        return response()->json(
            Banja::with([
                'user:id,name,ime,prezime,email,role',
                'vrste:id,naziv,slug',
                'indikacije:id,naziv,slug',
                'terapije:id,naziv,slug',
            ])->findOrFail($id)
        );
    }

    public function createSpa(Request $request)
    {
        $validated = $this->validateSpaPayload($request);

        $spa = DB::transaction(function () use ($validated, $request) {
            $profileData = collect($validated)
                ->except(['account_email', 'password', 'vrste', 'indikacije', 'terapije'])
                ->all();

            $profileData['aktivan'] = $request->boolean('aktivan', true);
            $profileData['verifikovan'] = $request->boolean('verifikovan', true);

            $spa = Banja::create($profileData);
            $this->syncSpaRelations($spa, $validated);

            $this->profileAccessService->sync($spa, $validated, [
                'role' => 'spa_manager',
                'model_class' => Banja::class,
                'entity_label' => 'banja',
                'name' => fn (Banja $entity) => $entity->naziv,
            ]);

            return $spa->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'vrste:id,naziv,slug',
                'indikacije:id,naziv,slug',
                'terapije:id,naziv,slug',
            ]);
        });

        return response()->json([
            'message' => 'Banja je uspjesno kreirana.',
            'spa' => $spa,
        ], 201);
    }

    public function updateSpa(Request $request, int $id)
    {
        $spa = Banja::findOrFail($id);
        $validated = $this->validateSpaPayload($request, $spa);

        $spa = DB::transaction(function () use ($spa, $validated) {
            $spa->update(collect($validated)
                ->except(['account_email', 'password', 'vrste', 'indikacije', 'terapije'])
                ->all());

            $this->syncSpaRelations($spa, $validated);

            $this->profileAccessService->sync($spa, $validated, [
                'role' => 'spa_manager',
                'model_class' => Banja::class,
                'entity_label' => 'banja',
                'name' => fn (Banja $entity) => $entity->naziv,
            ]);

            return $spa->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'vrste:id,naziv,slug',
                'indikacije:id,naziv,slug',
                'terapije:id,naziv,slug',
            ]);
        });

        return response()->json($spa);
    }

    public function deleteSpa(int $id)
    {
        Banja::findOrFail($id)->delete();

        return response()->json(['message' => 'Banja obrisana']);
    }

    public function sendSpaAccessInvite(Request $request, int $id)
    {
        $spa = Banja::findOrFail($id);
        $validated = $request->validate([
            'account_email' => 'nullable|email|max:255',
        ]);

        $result = $this->profileAccessService->sendInvitation($spa, $validated, [
            'role' => 'spa_manager',
            'model_class' => Banja::class,
            'entity_label' => 'banja',
            'invitation_label' => 'profil banje',
            'name' => fn (Banja $entity) => $entity->naziv,
        ]);

        return response()->json([
            'message' => 'Pozivnica za pristup je uspjesno poslana.',
            'spa' => $spa->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'vrste:id,naziv,slug',
                'indikacije:id,naziv,slug',
                'terapije:id,naziv,slug',
            ]),
            'invitation' => [
                'sent_to' => $result['sent_to'],
                'sent_at' => $result['invitation_sent_at'],
            ],
        ]);
    }

    // CARE HOMES
    public function getCareHomes(Request $request)
    {
        $query = Dom::query()
            ->with([
                'user:id,name,ime,prezime,email,role',
                'tipDoma:id,naziv,slug',
                'nivoNjege:id,naziv,slug',
                'programiNjege:id,naziv,slug',
                'medicinskUsluge:id,naziv,slug',
                'smjestajUslovi:id,naziv,slug,kategorija',
            ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('grad')) {
            $query->poGradu($request->grad);
        }

        if ($request->has('aktivan')) {
            $query->where('aktivan', $request->boolean('aktivan'));
        }

        if ($request->has('verifikovan')) {
            $query->where('verifikovan', $request->boolean('verifikovan'));
        }

        $query->orderBy(
            $request->get('sort_by', 'created_at'),
            $request->get('sort_order', 'desc')
        );

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function showCareHome(int $id)
    {
        return response()->json(
            Dom::with([
                'user:id,name,ime,prezime,email,role',
                'tipDoma:id,naziv,slug',
                'nivoNjege:id,naziv,slug',
                'programiNjege:id,naziv,slug',
                'medicinskUsluge:id,naziv,slug',
                'smjestajUslovi:id,naziv,slug,kategorija',
            ])->findOrFail($id)
        );
    }

    public function createCareHome(Request $request)
    {
        $validated = $this->validateCareHomePayload($request);

        $home = DB::transaction(function () use ($validated, $request) {
            $profileData = collect($validated)
                ->except(['account_email', 'password', 'programi_njege', 'medicinske_usluge', 'smjestaj_uslovi'])
                ->all();

            $profileData['aktivan'] = $request->boolean('aktivan', true);
            $profileData['verifikovan'] = $request->boolean('verifikovan', true);
            $profileData['nurses_availability'] = $profileData['nurses_availability'] ?? 'shifts';
            $profileData['doctor_availability'] = $profileData['doctor_availability'] ?? 'on_call';
            $profileData['pricing_mode'] = $profileData['pricing_mode'] ?? 'on_request';

            $home = Dom::create($profileData);
            $this->syncCareHomeRelations($home, $validated);

            $this->profileAccessService->sync($home, $validated, [
                'role' => 'dom_manager',
                'model_class' => Dom::class,
                'entity_label' => 'dom',
                'name' => fn (Dom $entity) => $entity->naziv,
            ]);

            return $home->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'tipDoma:id,naziv,slug',
                'nivoNjege:id,naziv,slug',
                'programiNjege:id,naziv,slug',
                'medicinskUsluge:id,naziv,slug',
                'smjestajUslovi:id,naziv,slug,kategorija',
            ]);
        });

        return response()->json([
            'message' => 'Dom je uspjesno kreiran.',
            'care_home' => $home,
        ], 201);
    }

    public function updateCareHome(Request $request, int $id)
    {
        $home = Dom::findOrFail($id);
        $validated = $this->validateCareHomePayload($request, $home);

        $home = DB::transaction(function () use ($home, $validated) {
            $home->update(collect($validated)
                ->except(['account_email', 'password', 'programi_njege', 'medicinske_usluge', 'smjestaj_uslovi'])
                ->all());

            $this->syncCareHomeRelations($home, $validated);

            $this->profileAccessService->sync($home, $validated, [
                'role' => 'dom_manager',
                'model_class' => Dom::class,
                'entity_label' => 'dom',
                'name' => fn (Dom $entity) => $entity->naziv,
            ]);

            return $home->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'tipDoma:id,naziv,slug',
                'nivoNjege:id,naziv,slug',
                'programiNjege:id,naziv,slug',
                'medicinskUsluge:id,naziv,slug',
                'smjestajUslovi:id,naziv,slug,kategorija',
            ]);
        });

        return response()->json($home);
    }

    public function deleteCareHome(int $id)
    {
        Dom::findOrFail($id)->delete();

        return response()->json(['message' => 'Dom obrisan']);
    }

    public function sendCareHomeAccessInvite(Request $request, int $id)
    {
        $home = Dom::findOrFail($id);
        $validated = $request->validate([
            'account_email' => 'nullable|email|max:255',
        ]);

        $result = $this->profileAccessService->sendInvitation($home, $validated, [
            'role' => 'dom_manager',
            'model_class' => Dom::class,
            'entity_label' => 'dom',
            'invitation_label' => 'profil doma za njegu',
            'name' => fn (Dom $entity) => $entity->naziv,
        ]);

        return response()->json([
            'message' => 'Pozivnica za pristup je uspjesno poslana.',
            'care_home' => $home->fresh()->load([
                'user:id,name,ime,prezime,email,role',
                'tipDoma:id,naziv,slug',
                'nivoNjege:id,naziv,slug',
                'programiNjege:id,naziv,slug',
                'medicinskUsluge:id,naziv,slug',
                'smjestajUslovi:id,naziv,slug,kategorija',
            ]),
            'invitation' => [
                'sent_to' => $result['sent_to'],
                'sent_at' => $result['invitation_sent_at'],
            ],
        ]);
    }

    // QUESTIONS
    public function getQuestions(Request $request)
    {
        $query = Pitanje::with('user:id,ime,prezime,email')
            ->orderBy('created_at', 'desc');

        if ($request->per_page) {
            return response()->json($query->paginate($request->per_page));
        }

        return response()->json($query->get());
    }

    public function updateQuestion(Request $request, int $id)
    {
        $question = Pitanje::findOrFail($id);
        $question->update($request->only(['status']));

        return response()->json($question);
    }

    public function deleteQuestion(int $id)
    {
        Pitanje::findOrFail($id)->delete();

        return response()->json(['message' => 'Pitanje obrisano']);
    }

    private function validateLaboratoryPayload(Request $request, ?Laboratorija $laboratory = null): array
    {
        $required = $laboratory ? 'sometimes|required' : 'required';

        return $request->validate([
            'naziv' => [$required, 'string', 'max:255', Rule::unique('laboratorije', 'naziv')->ignore($laboratory?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('laboratorije', 'email')->ignore($laboratory?->id)],
            'telefon' => 'nullable|string|max:50',
            'telefon_2' => 'nullable|string|max:50',
            'adresa' => [$required, 'string', 'max:500'],
            'grad' => [$required, 'string', 'max:100'],
            'opis' => 'nullable|string',
            'kratak_opis' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'postanski_broj' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'featured_slika' => 'nullable|string|max:500',
            'profilna_slika' => 'nullable|string|max:500',
            'galerija' => 'nullable|array|max:20',
            'galerija.*' => 'string|max:500',
            'radno_vrijeme' => 'nullable|array',
            'online_rezultati' => 'sometimes|boolean',
            'prosjecno_vrijeme_rezultata' => 'nullable|string|max:100',
            'napomena' => 'nullable|string',
            'aktivan' => 'sometimes|boolean',
            'verifikovan' => 'sometimes|boolean',
            'account_email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ]);
    }

    private function validateSpaPayload(Request $request, ?Banja $spa = null): array
    {
        $required = $spa ? 'sometimes|required' : 'required';

        return $request->validate([
            'naziv' => [$required, 'string', 'max:255', Rule::unique('banje', 'naziv')->ignore($spa?->id)],
            'grad' => [$required, 'string', 'max:100'],
            'regija' => 'nullable|string|max:100',
            'adresa' => [$required, 'string', 'max:500'],
            'telefon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => [$required, 'string'],
            'detaljni_opis' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'featured_slika' => 'nullable|string|max:500',
            'galerija' => 'nullable|array|max:20',
            'galerija.*' => 'string|max:500',
            'radno_vrijeme' => 'nullable|array',
            'medicinski_nadzor' => 'sometimes|boolean',
            'fizijatar_prisutan' => 'sometimes|boolean',
            'ima_smjestaj' => 'sometimes|boolean',
            'broj_kreveta' => 'nullable|integer|min:0|max:1000',
            'online_rezervacija' => 'sometimes|boolean',
            'online_upit' => 'sometimes|boolean',
            'vrste' => 'nullable|array',
            'vrste.*' => 'integer|exists:vrste_banja,id',
            'indikacije' => 'nullable|array',
            'indikacije.*' => 'integer|exists:indikacije,id',
            'terapije' => 'nullable|array',
            'terapije.*' => 'integer|exists:terapije,id',
            'aktivan' => 'sometimes|boolean',
            'verifikovan' => 'sometimes|boolean',
            'account_email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ]);
    }

    private function validateCareHomePayload(Request $request, ?Dom $home = null): array
    {
        $required = $home ? 'sometimes|required' : 'required';

        return $request->validate([
            'naziv' => [$required, 'string', 'max:255', Rule::unique('domovi_njega', 'naziv')->ignore($home?->id)],
            'grad' => [$required, 'string', 'max:100'],
            'regija' => 'nullable|string|max:100',
            'adresa' => [$required, 'string', 'max:500'],
            'telefon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => [$required, 'string'],
            'detaljni_opis' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'featured_slika' => 'nullable|string|max:500',
            'galerija' => 'nullable|array|max:20',
            'galerija.*' => 'string|max:500',
            'radno_vrijeme' => 'nullable|array',
            'tip_doma_id' => [$required, 'integer', 'exists:tipovi_domova,id'],
            'nivo_njege_id' => [$required, 'integer', 'exists:nivoi_njege,id'],
            'accepts_tags' => 'nullable|array',
            'not_accepts_text' => 'nullable|string|max:1000',
            'nurses_availability' => 'nullable|in:24_7,shifts,on_demand',
            'doctor_availability' => 'nullable|in:permanent,periodic,on_call',
            'has_physiotherapist' => 'sometimes|boolean',
            'has_physiatrist' => 'sometimes|boolean',
            'emergency_protocol' => 'sometimes|boolean',
            'emergency_protocol_text' => 'nullable|string|max:1000',
            'controlled_entry' => 'sometimes|boolean',
            'video_surveillance' => 'sometimes|boolean',
            'visiting_rules' => 'nullable|string|max:2000',
            'pricing_mode' => 'nullable|in:public,on_request',
            'price_from' => 'nullable|numeric|min:0',
            'price_includes' => 'nullable|string|max:1000',
            'extra_charges' => 'nullable|string|max:1000',
            'online_upit' => 'sometimes|boolean',
            'programi_njege' => 'nullable|array',
            'programi_njege.*' => 'integer|exists:programi_njege,id',
            'medicinske_usluge' => 'nullable|array',
            'medicinske_usluge.*' => 'integer|exists:medicinske_usluge,id',
            'smjestaj_uslovi' => 'nullable|array',
            'smjestaj_uslovi.*' => 'integer|exists:smjestaj_uslovi,id',
            'faqs' => 'nullable|array|max:20',
            'faqs.*.pitanje' => 'required_with:faqs|string|max:500',
            'faqs.*.odgovor' => 'required_with:faqs|string|max:2000',
            'aktivan' => 'sometimes|boolean',
            'verifikovan' => 'sometimes|boolean',
            'account_email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ]);
    }

    private function syncSpaRelations(Banja $spa, array $validated): void
    {
        if (array_key_exists('vrste', $validated)) {
            $spa->vrste()->sync($validated['vrste'] ?? []);
        }

        if (array_key_exists('indikacije', $validated)) {
            $spa->indikacije()->sync($this->toPriorityMap($validated['indikacije'] ?? []));
        }

        if (array_key_exists('terapije', $validated)) {
            $spa->terapije()->sync($validated['terapije'] ?? []);
        }
    }

    private function syncCareHomeRelations(Dom $home, array $validated): void
    {
        if (array_key_exists('programi_njege', $validated)) {
            $home->programiNjege()->sync($this->toPriorityMap($validated['programi_njege'] ?? []));
        }

        if (array_key_exists('medicinske_usluge', $validated)) {
            $home->medicinskUsluge()->sync($validated['medicinske_usluge'] ?? []);
        }

        if (array_key_exists('smjestaj_uslovi', $validated)) {
            $home->smjestajUslovi()->sync($validated['smjestaj_uslovi'] ?? []);
        }
    }

    private function toPriorityMap(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->values()
            ->mapWithKeys(fn ($id, $index) => [(int) $id => ['prioritet' => $index + 1]])
            ->all();
    }
}
