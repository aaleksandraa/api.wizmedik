<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, Doktor, Klinika, Grad, Specijalnost};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Users Management
    public function getUsers(Request $request)
    {
        $users = User::with('roles')->paginate(20);
        return response()->json($users);
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate(['role' => 'required|in:admin,doctor,patient']);

        $user->syncRoles([$validated['role']]);

        return response()->json(['message' => 'Role updated successfully', 'user' => $user]);
    }

    // Doctors Management
    public function createDoctor(Request $request)
    {
        $validated = $request->validate([
            'ime' => 'required|string',
            'prezime' => 'required|string',
            'email' => 'required|email|unique:doktori,email',
            'telefon' => 'required|string',
            'specijalnost' => 'required|string',
            'specijalnost_id' => 'required|exists:specijalnosti,id',
            'grad' => 'required|string',
            'lokacija' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'slika_profila' => 'nullable|string',
            'klinika_id' => 'nullable|exists:klinike,id',
            'opis' => 'nullable|string',
            'prihvata_online' => 'boolean',
            'slot_trajanje_minuti' => 'integer|min:5',
        ]);

        $doktor = Doktor::create($validated);

        // Create user account
        $user = User::create([
            'name' => $validated['ime'] . ' ' . $validated['prezime'],
            'ime' => $validated['ime'],
            'prezime' => $validated['prezime'],
            'email' => $validated['email'],
            'password' => Hash::make('password123'), // Default password
        ]);
        $user->assignRole('doctor');

        $doktor->update(['user_id' => $user->id]);

        return response()->json(['message' => 'Doctor created successfully', 'doktor' => $doktor], 201);
    }

    public function updateDoktor(Request $request, $id)
    {
        $doktor = Doktor::findOrFail($id);

        // Handle specialty_ids separately for sync
        $specialtyIds = $request->input('specialty_ids');

        $doktor->update($request->only([
            'ime', 'prezime', 'email', 'telefon', 'specijalnost', 'specijalnost_id',
            'grad', 'lokacija', 'postanski_broj', 'mjesto', 'opstina', 'latitude', 'longitude', 'google_maps_link',
            'slika_profila', 'klinika_id', 'opis', 'prihvata_online', 'slot_trajanje_minuti', 'radno_vrijeme'
        ]));

        // Sync specialties if provided
        if ($specialtyIds !== null) {
            $doktor->specijalnosti()->sync($specialtyIds);
        }

        return response()->json(['message' => 'Doctor updated', 'doktor' => $doktor->fresh()->load('specijalnosti')]);
    }

    public function deleteDoktor($id)
    {
        $doktor = Doktor::findOrFail($id);
        $doktor->delete();
        return response()->json(['message' => 'Doctor deleted successfully']);
    }

    // Clinics Management
    public function createClinic(Request $request)
    {
        $validated = $request->validate([
            'naziv' => 'required|string',
            'opis' => 'nullable|string',
            'adresa' => 'required|string',
            'grad' => 'required|string',
            'telefon' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_maps_link' => 'nullable|url',
            'slike' => 'nullable|array',
            'radno_vrijeme' => 'nullable|array',
            'aktivan' => 'boolean',
        ]);

        // Create user account for clinic
        $user = \App\Models\User::create([
            'name' => $validated['naziv'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole('clinic');

        // Create clinic profile
        $clinicData = $validated;
        $clinicData['user_id'] = $user->id;
        unset($clinicData['password']);

        $klinika = Klinika::create($clinicData);

        return response()->json(['message' => 'Clinic created', 'klinika' => $klinika], 201);
    }

    public function updateClinic(Request $request, $id)
    {
        $klinika = Klinika::findOrFail($id);

        // Check if email is unique (excluding current clinic's user)
        $emailRule = 'sometimes|email';
        if ($request->has('email') && $request->email) {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser && (!$klinika->user_id || $existingUser->id !== $klinika->user_id)) {
                return response()->json(['message' => 'Email je već u upotrebi'], 422);
            }
        }

        $validated = $request->validate([
            'naziv' => 'sometimes|string',
            'opis' => 'nullable|string',
            'adresa' => 'sometimes|string',
            'grad' => 'sometimes|string',
            'telefon' => 'sometimes|string',
            'email' => $emailRule,
            'password' => 'nullable|string|min:8',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'google_maps_link' => 'nullable|url',
            'slike' => 'nullable|array',
            'radno_vrijeme' => 'nullable|array',
            'aktivan' => 'sometimes|boolean',
        ]);

        $email = $validated['email'] ?? null;
        $password = $validated['password'] ?? null;

        // If clinic has user_id, update existing user
        if ($klinika->user_id) {
            $user = \App\Models\User::find($klinika->user_id);
            if ($user) {
                if ($email) {
                    $user->email = $email;
                }
                if ($password) {
                    $user->password = bcrypt($password);
                }
                if ($request->has('naziv')) {
                    $user->name = $validated['naziv'];
                }
                $user->save();
            }
        }
        // If no user_id but email and password provided, create new user
        elseif ($email && $password) {
            $user = \App\Models\User::create([
                'name' => $validated['naziv'] ?? $klinika->naziv,
                'email' => $email,
                'password' => bcrypt($password),
            ]);
            $user->assignRole('clinic');
            $validated['user_id'] = $user->id;
        }

        // Remove password from validated data (not stored in klinike table)
        unset($validated['password']);

        $klinika->update($validated);

        // Load user relationship for response
        $klinika->load('user');

        return response()->json([
            'message' => 'Clinic updated',
            'klinika' => $klinika,
            'user_created' => isset($user) && !$klinika->wasChanged('user_id') ? false : (isset($user) ? true : false)
        ]);
    }

    public function deleteClinic($id)
    {
        Klinika::findOrFail($id)->delete();
        return response()->json(['message' => 'Clinic deleted']);
    }

    // Cities Management
    public function getCities()
    {
        // Return all cities with all fields for admin (no cache)
        $cities = Grad::orderBy('naziv')->get();
        return response()->json($cities);
    }

    public function createCity(Request $request)
    {
        $validated = $request->validate([
            'naziv' => 'required|string',
            'u_gradu' => 'nullable|string',
            'slug' => 'nullable|string',
            'opis' => 'required|string',
            'detaljni_opis' => 'required|string',
            'populacija' => 'nullable|string',
            'broj_bolnica' => 'nullable|integer|min:0',
            'hitna_pomoc' => 'nullable|string',
            'kljucne_tacke' => 'nullable|array',
            'kljucne_tacke.*' => 'nullable',
            'aktivan' => 'boolean',
        ]);

        if (array_key_exists('kljucne_tacke', $validated)) {
            $validated['kljucne_tacke'] = $this->normalizeCityKeyPoints($validated['kljucne_tacke'] ?? []);
        }

        $grad = Grad::create($validated);

        // Invalidate city cache after create
        $this->invalidateCityCache($grad->slug);

        return response()->json(['message' => 'City created', 'grad' => $grad], 201);
    }

    public function updateCity(Request $request, $id)
    {
        $grad = Grad::findOrFail($id);

        \Log::info('Updating city', [
            'id' => $id,
            'request_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'naziv' => 'sometimes|string',
            'u_gradu' => 'nullable|string',
            'slug' => 'nullable|string',
            'opis' => 'sometimes|string',
            'detaljni_opis' => 'sometimes|string',
            'populacija' => 'nullable|string',
            'broj_bolnica' => 'nullable|integer|min:0',
            'hitna_pomoc' => 'nullable|string',
            'kljucne_tacke' => 'nullable|array',
            'kljucne_tacke.*' => 'nullable',
            'aktivan' => 'sometimes|boolean',
        ]);

        if ($request->has('kljucne_tacke')) {
            $validated['kljucne_tacke'] = $this->normalizeCityKeyPoints($validated['kljucne_tacke'] ?? []);
        }

        $grad->update($validated);

        // Invalidate city cache
        $this->invalidateCityCache($grad->slug);

        return response()->json(['message' => 'City updated', 'grad' => $grad->fresh()]);
    }

    public function deleteCity($id)
    {
        $grad = Grad::findOrFail($id);
        $slug = $grad->slug;
        $grad->delete();

        // Invalidate city cache
        $this->invalidateCityCache($slug);

        return response()->json(['message' => 'City deleted']);
    }

    /**
     * Invalidate city cache after update/delete
     */
    private function invalidateCityCache(?string $slug = null): void
    {
        $cacheDriver = config('cache.default');

        if ($cacheDriver === 'redis') {
            \Illuminate\Support\Facades\Cache::tags(['cities'])->flush();
        } else {
            \Illuminate\Support\Facades\Cache::forget('cities_with_counts_v2');
            if ($slug) {
                \Illuminate\Support\Facades\Cache::forget("city_{$slug}_v2");
            }
        }
    }

    /**
     * Normalize city key points to a consistent shape:
     * [
     *   ['naziv' => '...', 'url' => '...']
     * ]
     */
    private function normalizeCityKeyPoints(array $keyPoints): array
    {
        return collect($keyPoints)
            ->map(function ($item) {
                if (is_string($item)) {
                    $naziv = trim($item);
                    return $naziv !== '' ? ['naziv' => $naziv] : null;
                }

                if (!is_array($item)) {
                    return null;
                }

                $naziv = trim((string) ($item['naziv'] ?? $item['name'] ?? ''));
                $url = trim((string) ($item['url'] ?? $item['link'] ?? ''));

                if ($naziv === '') {
                    return null;
                }

                return array_filter(
                    [
                        'naziv' => $naziv,
                        'url' => $url !== '' ? $url : null,
                    ],
                    fn($value) => $value !== null
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    // Specialties Management
    public function createSpecialty(Request $request)
    {
        // Parse JSON fields if they come as strings (from FormData)
        $data = $request->all();
        foreach (['kljucne_rijeci', 'youtube_linkovi', 'faq', 'usluge'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true);
                $data[$field] = $decoded !== null ? $decoded : [];
            }
        }

        // Convert boolean strings to actual booleans
        foreach (['aktivan', 'prikazi_video_savjete', 'prikazi_faq', 'prikazi_usluge'] as $field) {
            if (isset($data[$field])) {
                if (is_string($data[$field])) {
                    $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
                } else {
                    $data[$field] = (bool) $data[$field];
                }
            }
        }

        $validated = validator($data, [
            'naziv' => 'required|string',
            'parent_id' => 'nullable|exists:specijalnosti,id',
            'opis' => 'nullable|string',
            'icon_url' => 'nullable|string',
            'aktivan' => 'boolean',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'kljucne_rijeci' => 'nullable|array',
            'detaljan_opis' => 'nullable|string',
            'prikazi_video_savjete' => 'boolean',
            'youtube_linkovi' => 'nullable|array',
            'youtube_linkovi.*.url' => 'nullable|url',
            'youtube_linkovi.*.naslov' => 'nullable|string',
            'prikazi_faq' => 'boolean',
            'faq' => 'nullable|array',
            'faq.*.pitanje' => 'nullable|string',
            'faq.*.odgovor' => 'nullable|string',
            'prikazi_usluge' => 'boolean',
            'usluge' => 'nullable|array',
            'usluge.*.naziv' => 'nullable|string',
            'usluge.*.opis' => 'nullable|string',
            'uvodni_tekst' => 'nullable|string',
            'zakljucni_tekst' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'og_image' => 'nullable|string',
        ])->validate();

        // Filter out empty items from arrays
        if (isset($validated['youtube_linkovi'])) {
            $validated['youtube_linkovi'] = array_values(array_filter($validated['youtube_linkovi'], function($item) {
                return !empty($item['url']) && !empty($item['naslov']);
            }));
        }

        if (isset($validated['faq'])) {
            $validated['faq'] = array_values(array_filter($validated['faq'], function($item) {
                return !empty($item['pitanje']) && !empty($item['odgovor']);
            }));
        }

        if (isset($validated['usluge'])) {
            $validated['usluge'] = array_values(array_filter($validated['usluge'], function($item) {
                return !empty($item['naziv']);
            }));
        }

        // Handle icon upload if present
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            // Create temporary specialty to get ID
            $tempSpecialty = Specijalnost::create(array_merge($validated, ['icon_url' => null]));

            $filename = 'specialty_' . $tempSpecialty->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('specialties/icons', $filename, 'public');
            $tempSpecialty->update(['icon_url' => url('/storage/' . $path)]);

            // Clear specialty caches
            \Cache::forget('specialties:all');

            return response()->json(['message' => 'Specialty created', 'specijalnost' => $tempSpecialty], 201);
        }

        $specijalnost = Specijalnost::create($validated);

        // Clear specialty caches
        \Cache::forget('specialties:all');

        return response()->json(['message' => 'Specialty created', 'specijalnost' => $specijalnost], 201);
    }

    public function updateSpecialty(Request $request, $id)
    {
        $specijalnost = Specijalnost::findOrFail($id);

        // Validate icon separately if present
        if ($request->hasFile('icon')) {
            $request->validate([
                'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);
        }

        // Parse JSON fields if they come as strings (from FormData)
        $data = $request->all();

        foreach (['kljucne_rijeci', 'youtube_linkovi', 'faq', 'usluge'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true);
                $data[$field] = $decoded !== null ? $decoded : [];
            }
        }

        // Convert boolean strings to actual booleans
        foreach (['aktivan', 'prikazi_video_savjete', 'prikazi_faq', 'prikazi_usluge'] as $field) {
            if (isset($data[$field])) {
                if (is_string($data[$field])) {
                    $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
                } else {
                    $data[$field] = (bool) $data[$field];
                }
            }
        }

        $validated = validator($data, [
            'naziv' => 'sometimes|string',
            'parent_id' => 'nullable|exists:specijalnosti,id',
            'opis' => 'nullable|string',
            'icon_url' => 'nullable|string',
            'aktivan' => 'boolean',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'kljucne_rijeci' => 'nullable|array',
            'detaljan_opis' => 'nullable|string',
            'prikazi_video_savjete' => 'boolean',
            'youtube_linkovi' => 'nullable|array',
            'prikazi_faq' => 'boolean',
            'faq' => 'nullable|array',
            'prikazi_usluge' => 'boolean',
            'usluge' => 'nullable|array',
            'uvodni_tekst' => 'nullable|string',
            'zakljucni_tekst' => 'nullable|string',
            'canonical_url' => 'nullable|url',
            'og_image' => 'nullable|string',
        ])->validate();

        // Filter out empty items from arrays
        if (isset($validated['youtube_linkovi'])) {
            $validated['youtube_linkovi'] = array_values(array_filter($validated['youtube_linkovi'], function($item) {
                return !empty($item['url']) && !empty($item['naslov']);
            }));
        }

        if (isset($validated['faq'])) {
            $validated['faq'] = array_values(array_filter($validated['faq'], function($item) {
                return !empty($item['pitanje']) && !empty($item['odgovor']);
            }));
        }

        if (isset($validated['usluge'])) {
            $validated['usluge'] = array_values(array_filter($validated['usluge'], function($item) {
                return !empty($item['naziv']);
            }));
        }

        // Handle icon deletion (when icon_url is explicitly set to empty string)
        if (isset($validated['icon_url']) && $validated['icon_url'] === '') {
            // Delete old uploaded icon file if exists (not predefined icons)
            if ($specijalnost->icon_url && !str_starts_with($specijalnost->icon_url, 'icon:')) {
                $oldPath = str_replace([url('/storage/'), '/storage/'], '', $specijalnost->icon_url);
                \Storage::disk('public')->delete($oldPath);
            }
            $validated['icon_url'] = null;
        }

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = 'specialty_' . $specijalnost->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('specialties/icons', $filename, 'public');
            $validated['icon_url'] = url('/storage/' . $path);

            // Delete old uploaded icon if exists (not predefined icons)
            if ($specijalnost->icon_url && !str_starts_with($specijalnost->icon_url, 'icon:')) {
                $oldPath = str_replace([url('/storage/'), '/storage/'], '', $specijalnost->icon_url);
                \Storage::disk('public')->delete($oldPath);
            }
        }

        $specijalnost->update($validated);
        $specijalnost->refresh();

        // Clear specialty caches
        \Cache::forget("specialty:{$specijalnost->slug}");
        \Cache::forget('specialties:all');

        // Load children for response
        $specijalnost->load('children');

        return response()->json(['message' => 'Specialty updated', 'specijalnost' => $specijalnost]);
    }

    public function deleteSpecialty($id)
    {
        $specialty = Specijalnost::findOrFail($id);
        $slug = $specialty->slug;
        $specialty->delete();

        // Clear specialty caches
        \Cache::forget("specialty:{$slug}");
        \Cache::forget('specialties:all');

        return response()->json(['message' => 'Specialty deleted']);
    }

    /**
     * Get single specialty with all data (for admin editing)
     */
    public function getSpecialty($id)
    {
        $specijalnost = Specijalnost::with(['children', 'parent'])->findOrFail($id);
        return response()->json($specijalnost);
    }

    /**
     * Update specialty sort order
     */
    public function updateSpecialtySortOrder(Request $request)
    {
        $validated = $request->validate([
            'specialties' => 'required|array',
            'specialties.*.id' => 'required|exists:specijalnosti,id',
            'specialties.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            \DB::beginTransaction();

            foreach ($validated['specialties'] as $item) {
                Specijalnost::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            \DB::commit();

            // Clear specialty caches
            \Cache::forget('specialties:all');

            return response()->json([
                'message' => 'Redoslijed specijalnosti ažuriran',
                'success' => true
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Greška pri ažuriranju redoslijeda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Admin Profile Management

    /**
     * Get current admin profile
     */
    public function getProfile(Request $request)
    {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    /**
     * Update admin profile (name and email)
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
            'current_password' => 'required|string',
        ], [
            'name.required' => 'Ime je obavezno.',
            'email.required' => 'Email je obavezan.',
            'email.email' => 'Email nije validan.',
            'email.unique' => 'Ovaj email je već u upotrebi.',
            'current_password.required' => 'Trenutna lozinka je obavezna za izmjenu profila.',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Trenutna lozinka nije tačna.',
                'errors' => [
                    'current_password' => ['Trenutna lozinka nije tačna.']
                ]
            ], 422);
        }

        $request->user()->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Profil je uspješno ažuriran.',
            'user' => $request->user()->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * Change admin password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/',
            ],
        ], [
            'current_password.required' => 'Trenutna lozinka je obavezna.',
            'new_password.required' => 'Nova lozinka je obavezna.',
            'new_password.confirmed' => 'Lozinke se ne poklapaju.',
            'new_password.min' => 'Lozinka mora imati najmanje 12 karaktera.',
            'new_password.regex' => 'Lozinka mora sadržati velika i mala slova, brojeve i specijalne karaktere.',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Trenutna lozinka nije tačna.',
                'errors' => [
                    'current_password' => ['Trenutna lozinka nije tačna.']
                ]
            ], 422);
        }

        // Check if new password is same as current
        if (Hash::check($validated['new_password'], $request->user()->password)) {
            return response()->json([
                'message' => 'Nova lozinka mora biti različita od trenutne.',
                'errors' => [
                    'new_password' => ['Nova lozinka mora biti različita od trenutne.']
                ]
            ], 422);
        }

        $request->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Lozinka je uspješno promijenjena.',
        ]);
    }
}
