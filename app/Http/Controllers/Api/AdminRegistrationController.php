<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\Doktor;
use App\Models\Klinika;
use App\Models\SiteSetting;
use App\Mail\RegistrationApprovedMail;
use App\Mail\RegistrationRejectedMail;
use App\Mail\NewRegistrationRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminRegistrationController extends Controller
{
    /**
     * Get all registration requests
     */
    public function index(Request $request)
    {
        $query = RegistrationRequest::with(['reviewer', 'specialty', 'laboratory', 'spa', 'careHome'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['doctor', 'clinic', 'laboratory', 'spa', 'care_home'])) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected', 'expired'])) {
            $query->where('status', $request->status);
        }

        // Filter by verified
        if ($request->has('verified')) {
            if ($request->verified === 'true') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                  ->orWhere('ime', 'ilike', "%{$search}%")
                  ->orWhere('prezime', 'ilike', "%{$search}%")
                  ->orWhere('naziv', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        return $query->paginate($perPage);
    }

    /**
     * Get single registration request
     */
    public function show(int $id)
    {
        $request = RegistrationRequest::with(['reviewer', 'specialty', 'user', 'doctor', 'clinic', 'laboratory', 'spa', 'careHome'])
            ->findOrFail($id);

        return response()->json($request);
    }

    /**
     * Approve registration request
     */
    public function approve(int $id, Request $request)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $registrationRequest = RegistrationRequest::findOrFail($id);

        // Check if can be approved
        if (!$registrationRequest->can_be_approved) {
            return response()->json([
                'message' => 'Zahtjev ne može biti odobren. Provjerite status i verifikaciju.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create user and profile
            $result = $this->createUserAndProfile($registrationRequest, auth()->user());

            // Update registration request
            $registrationRequest->approve(auth()->user());
            $registrationRequest->update([
                'admin_notes' => $request->admin_notes,
                'user_id' => $result['user']->id,
                'doctor_id' => $result['doctor']->id ?? null,
                'clinic_id' => $result['clinic']->id ?? null,
                'laboratory_id' => $result['laboratory']->id ?? null,
                'spa_id' => $result['spa']->id ?? null,
                'care_home_id' => $result['care_home']->id ?? null,
            ]);
            $this->ensureApprovedProfileIsVisible($registrationRequest);

            // Send approval email
            Mail::to($registrationRequest->email)->send(
                new RegistrationApprovedMail($registrationRequest, $result['user'])
            );

            DB::commit();

            return response()->json([
                'message' => 'Zahtjev je uspješno odobren.',
                'user' => $result['user'],
                'profile' => $result['doctor'] ?? $result['clinic'] ?? $result['laboratory'] ?? $result['spa'] ?? $result['care_home'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration approval failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Greška prilikom odobravanja zahtjeva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject registration request
     */
    public function reject(int $id, Request $request)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $registrationRequest = RegistrationRequest::findOrFail($id);

        if ($registrationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Samo pending zahtjevi mogu biti odbijeni.'
            ], 400);
        }

        // Reject request
        $registrationRequest->reject(auth()->user(), $request->rejection_reason);
        $registrationRequest->update([
            'admin_notes' => $request->admin_notes,
        ]);

        // Send rejection email
        Mail::to($registrationRequest->email)->send(
            new RegistrationRejectedMail($registrationRequest)
        );

        return response()->json([
            'message' => 'Zahtjev je odbijen.'
        ]);
    }

    /**
     * Delete registration request completely (including user if created)
     */
    public function delete(int $id)
    {
        $registrationRequest = RegistrationRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete associated user and profile if they exist
            if ($registrationRequest->user_id) {
                $user = User::find($registrationRequest->user_id);
                if ($user) {
                    // Delete profile based on type
                    if ($registrationRequest->doctor_id) {
                        Doktor::where('id', $registrationRequest->doctor_id)->delete();
                    }
                    if ($registrationRequest->clinic_id) {
                        Klinika::where('id', $registrationRequest->clinic_id)->delete();
                    }
                    if ($registrationRequest->laboratory_id) {
                        \App\Models\Laboratorija::where('id', $registrationRequest->laboratory_id)->delete();
                    }
                    if ($registrationRequest->spa_id) {
                        \App\Models\Banja::where('id', $registrationRequest->spa_id)->delete();
                    }
                    if ($registrationRequest->care_home_id) {
                        \App\Models\Dom::where('id', $registrationRequest->care_home_id)->delete();
                    }

                    // Delete user (this will cascade delete related data)
                    $user->delete();
                }
            }

            // Delete registration request
            $registrationRequest->delete();

            DB::commit();

            return response()->json([
                'message' => 'Zahtjev i svi povezani podaci su potpuno obrisani.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration deletion failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Greška prilikom brisanja zahtjeva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update registration settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'doctor_registration_enabled' => 'required|boolean',
            'doctor_registration_free' => 'required|boolean',
            'doctor_registration_price' => 'nullable|numeric|min:0',
            'doctor_auto_approve' => 'required|boolean',
            'doctor_registration_message' => 'nullable|string|max:1000',
            'clinic_registration_enabled' => 'required|boolean',
            'clinic_registration_free' => 'required|boolean',
            'clinic_registration_price' => 'nullable|numeric|min:0',
            'clinic_auto_approve' => 'required|boolean',
            'clinic_registration_message' => 'nullable|string|max:1000',
            'laboratory_registration_enabled' => 'required|boolean',
            'laboratory_registration_free' => 'required|boolean',
            'laboratory_registration_price' => 'nullable|numeric|min:0',
            'laboratory_auto_approve' => 'required|boolean',
            'laboratory_registration_message' => 'nullable|string|max:1000',
            'spa_registration_enabled' => 'required|boolean',
            'spa_registration_free' => 'required|boolean',
            'spa_registration_price' => 'nullable|numeric|min:0',
            'spa_auto_approve' => 'required|boolean',
            'spa_registration_message' => 'nullable|string|max:1000',
            'care_home_registration_enabled' => 'required|boolean',
            'care_home_registration_free' => 'required|boolean',
            'care_home_registration_price' => 'nullable|numeric|min:0',
            'care_home_auto_approve' => 'required|boolean',
            'care_home_registration_message' => 'nullable|string|max:1000',
            'registration_admin_email' => 'nullable|email',
            'registration_auto_approve' => 'required|boolean',
            'registration_require_documents' => 'required|boolean',
            'registration_max_attempts' => 'required|integer|min:1|max:10',
            'registration_expiry_days' => 'required|integer|min:1|max:30',
        ]);

        foreach ($request->all() as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            SiteSetting::set($key, $value);
        }

        return response()->json([
            'message' => 'Postavke su uspješno ažurirane.'
        ]);
    }

    /**
     * Get registration settings
     */
    public function getSettings()
    {
        return response()->json([
            'doctor_registration_enabled' => SiteSetting::get('doctor_registration_enabled', 'true') === 'true',
            'doctor_registration_free' => SiteSetting::get('doctor_registration_free', 'true') === 'true',
            'doctor_registration_price' => (float) SiteSetting::get('doctor_registration_price', 0),
            'doctor_auto_approve' => SiteSetting::get('doctor_auto_approve', 'false') === 'true',
            'doctor_registration_message' => SiteSetting::get('doctor_registration_message', ''),
            'clinic_registration_enabled' => SiteSetting::get('clinic_registration_enabled', 'true') === 'true',
            'clinic_registration_free' => SiteSetting::get('clinic_registration_free', 'true') === 'true',
            'clinic_registration_price' => (float) SiteSetting::get('clinic_registration_price', 0),
            'clinic_auto_approve' => SiteSetting::get('clinic_auto_approve', 'false') === 'true',
            'clinic_registration_message' => SiteSetting::get('clinic_registration_message', ''),
            'laboratory_registration_enabled' => SiteSetting::get('laboratory_registration_enabled', 'true') === 'true',
            'laboratory_registration_free' => SiteSetting::get('laboratory_registration_free', 'true') === 'true',
            'laboratory_registration_price' => (float) SiteSetting::get('laboratory_registration_price', 0),
            'laboratory_auto_approve' => SiteSetting::get('laboratory_auto_approve', 'false') === 'true',
            'laboratory_registration_message' => SiteSetting::get('laboratory_registration_message', ''),
            'spa_registration_enabled' => SiteSetting::get('spa_registration_enabled', 'true') === 'true',
            'spa_registration_free' => SiteSetting::get('spa_registration_free', 'true') === 'true',
            'spa_registration_price' => (float) SiteSetting::get('spa_registration_price', 0),
            'spa_auto_approve' => SiteSetting::get('spa_auto_approve', 'false') === 'true',
            'spa_registration_message' => SiteSetting::get('spa_registration_message', ''),
            'care_home_registration_enabled' => SiteSetting::get('care_home_registration_enabled', 'true') === 'true',
            'care_home_registration_free' => SiteSetting::get('care_home_registration_free', 'true') === 'true',
            'care_home_registration_price' => (float) SiteSetting::get('care_home_registration_price', 0),
            'care_home_auto_approve' => SiteSetting::get('care_home_auto_approve', 'false') === 'true',
            'care_home_registration_message' => SiteSetting::get('care_home_registration_message', ''),
            'registration_admin_email' => SiteSetting::get('registration_admin_email', ''),
            'registration_auto_approve' => SiteSetting::get('registration_auto_approve', 'false') === 'true',
            'registration_require_documents' => SiteSetting::get('registration_require_documents', 'false') === 'true',
            'registration_max_attempts' => (int) SiteSetting::get('registration_max_attempts', 3),
            'registration_expiry_days' => (int) SiteSetting::get('registration_expiry_days', 7),
        ]);
    }

    /**
     * Create user and profile (doctor, clinic, laboratory, or spa)
     */
    private function createUserAndProfile(RegistrationRequest $registrationRequest, ?User $approver = null): array
    {
        $decodedMessage = null;
        if (is_string($registrationRequest->message) && $registrationRequest->message !== '') {
            $parsed = json_decode($registrationRequest->message, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $decodedMessage = $parsed;
            }
        }

        $messageData = $decodedMessage ?? [];
        $textMessage = is_string($messageData['message'] ?? null)
            ? trim($messageData['message'])
            : (($decodedMessage === null && is_string($registrationRequest->message))
                ? trim($registrationRequest->message)
                : '');

        $publicEmail = is_string($messageData['public_email'] ?? null) && trim($messageData['public_email']) !== ''
            ? trim($messageData['public_email'])
            : $registrationRequest->email;

        // Determine role based on type
        $roleMapping = [
            'doctor' => 'doctor',
            'clinic' => 'clinic',
            'laboratory' => 'laboratory',
            'spa' => 'spa_manager',
            'care_home' => 'dom_manager',
        ];

        $roleName = $roleMapping[$registrationRequest->type] ?? 'patient';

        // Create user
        $user = User::create([
            'name' => $registrationRequest->type === 'doctor'
                ? $registrationRequest->ime . ' ' . $registrationRequest->prezime
                : $registrationRequest->naziv,
            'email' => $registrationRequest->email,
            'password' => $registrationRequest->password, // Already hashed
            'role' => $roleName, // Keep for backward compatibility
            'email_verified_at' => now(),
        ]);

        // ✅ CRITICAL: Assign Spatie Permission role
        $user->assignRole($roleName);

        if ($registrationRequest->type === 'doctor') {
            // Parse message to get specialty_ids if available
            $messageData = null;
            if ($registrationRequest->message) {
                $messageData = json_decode($registrationRequest->message, true);
            }

            $specialtyIds = $messageData['specialty_ids'] ?? [$registrationRequest->specijalnost_id];

            // Create doctor profile
            $doctor = Doktor::create([
                'user_id' => $user->id,
                'ime' => $registrationRequest->ime,
                'prezime' => $registrationRequest->prezime,
                'slug' => Str::slug($registrationRequest->ime . '-' . $registrationRequest->prezime . '-' . $user->id),
                'email' => $registrationRequest->email,
                'telefon' => $registrationRequest->telefon,
                'lokacija' => $registrationRequest->adresa ?? $registrationRequest->grad, // Use adresa as lokacija, fallback to grad
                'grad' => $registrationRequest->grad,
                'specijalnost' => $registrationRequest->specialty->naziv ?? '',
                'specijalnost_id' => $registrationRequest->specijalnost_id,
                'opis' => '',
                'ocjena' => 0,
                'broj_ocjena' => 0,
                'aktivan' => true,
                'verifikovan' => true,
                'verifikovan_at' => now(),
                'verifikovan_by' => $approver?->id,
                'prihvata_online' => true,
                'cijena_od' => 0,
                'cijena_do' => 0,
            ]);

            // Sync multiple specialties if available
            if (count($specialtyIds) > 0) {
                $doctor->specijalnosti()->sync($specialtyIds);
            }

            return ['user' => $user, 'doctor' => $doctor];
        } elseif ($registrationRequest->type === 'laboratory') {
            // Create laboratory profile
            $laboratory = \App\Models\Laboratorija::create([
                'user_id' => $user->id,
                'naziv' => $registrationRequest->naziv,
                'slug' => Str::slug($registrationRequest->naziv . '-' . $user->id),
                'email' => $publicEmail,
                'telefon' => $registrationRequest->telefon,
                'adresa' => $registrationRequest->adresa,
                'grad' => $registrationRequest->grad,
                'opis' => $textMessage,
                'website' => $messageData['website'] ?? null,
                'prosjecna_ocjena' => 0,
                'broj_recenzija' => 0,
                'broj_pregleda' => 0,
                'verifikovan' => true,
                'aktivan' => true,
            ]);

            return ['user' => $user, 'laboratory' => $laboratory];
        } elseif ($registrationRequest->type === 'spa') {
            // Create spa profile
            $spa = \App\Models\Banja::create([
                'user_id' => $user->id,
                'naziv' => $registrationRequest->naziv,
                'slug' => Str::slug($registrationRequest->naziv . '-' . $user->id),
                'email' => $publicEmail,
                'telefon' => $registrationRequest->telefon,
                'adresa' => $registrationRequest->adresa,
                'grad' => $registrationRequest->grad,
                'regija' => $messageData['regija'] ?? '',
                'opis' => $messageData['opis'] ?? '',
                'website' => $messageData['website'] ?? null,
                'medicinski_nadzor' => (bool) ($messageData['medicinski_nadzor'] ?? false),
                'ima_smjestaj' => (bool) ($messageData['ima_smjestaj'] ?? false),
                'prosjecna_ocjena' => 0,
                'broj_recenzija' => 0,
                'broj_pregleda' => 0,
                'verifikovan' => true,
                'aktivan' => true,
            ]);

            $vrsteIds = collect($messageData['vrste'] ?? [])
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($vrsteIds)) {
                $spa->vrste()->sync($vrsteIds);
            }

            return ['user' => $user, 'spa' => $spa];
        } elseif ($registrationRequest->type === 'care_home') {
            $tipDomaId = isset($messageData['tip_doma_id']) && is_numeric($messageData['tip_doma_id'])
                ? (int) $messageData['tip_doma_id']
                : \App\Models\TipDoma::query()->value('id');
            $nivoNjegeId = isset($messageData['nivo_njege_id']) && is_numeric($messageData['nivo_njege_id'])
                ? (int) $messageData['nivo_njege_id']
                : \App\Models\NivoNjege::query()->value('id');

            if (!$tipDomaId || !$nivoNjegeId) {
                throw new \RuntimeException('Nedostaju obavezni tip doma ili nivo njege za kreiranje profila doma.');
            }

            // Create care home profile
            $careHome = \App\Models\Dom::create([
                'user_id' => $user->id,
                'naziv' => $registrationRequest->naziv,
                'slug' => Str::slug($registrationRequest->naziv . '-' . $user->id),
                'email' => $publicEmail,
                'telefon' => $registrationRequest->telefon,
                'adresa' => $registrationRequest->adresa,
                'grad' => $registrationRequest->grad,
                'website' => $messageData['website'] ?? null,
                'opis' => $messageData['opis'] ?? '',
                'tip_doma_id' => $tipDomaId,
                'nivo_njege_id' => $nivoNjegeId,
                'nurses_availability' => $messageData['nurses_availability'] ?? 'shifts',
                'doctor_availability' => $messageData['doctor_availability'] ?? 'on_call',
                'has_physiotherapist' => (bool) ($messageData['has_physiotherapist'] ?? false),
                'has_physiatrist' => (bool) ($messageData['has_physiatrist'] ?? false),
                'emergency_protocol' => (bool) ($messageData['emergency_protocol'] ?? false),
                'prosjecna_ocjena' => 0,
                'broj_recenzija' => 0,
                'broj_pregleda' => 0,
                'verifikovan' => true,
                'aktivan' => true,
            ]);

            $programIds = collect($messageData['programi_njege'] ?? [])
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($programIds)) {
                $careHome->programiNjege()->sync($programIds);
            }

            return ['user' => $user, 'care_home' => $careHome];
        } else {
            // Create clinic profile
            $clinic = Klinika::create([
                'user_id' => $user->id,
                'naziv' => $registrationRequest->naziv,
                'slug' => Str::slug($registrationRequest->naziv . '-' . $user->id),
                'email' => $registrationRequest->email,
                'telefon' => $registrationRequest->telefon,
                'adresa' => $registrationRequest->adresa,
                'grad' => $registrationRequest->grad,
                'opis' => '',
                'ocjena' => 0,
                'broj_ocjena' => 0,
                'aktivan' => true,
                'verifikovan' => true,
                'verifikovan_at' => now(),
                'verifikovan_by' => $approver?->id,
            ]);

            return ['user' => $user, 'clinic' => $clinic];
        }
    }

    /**
     * Public method for auto-approval (called from RegistrationController)
     */
    public function approveRequest(int $id, User $admin, bool $sendApprovalEmail = true): void
    {
        $registrationRequest = RegistrationRequest::findOrFail($id);

        DB::beginTransaction();
        try {
            $result = $this->createUserAndProfile($registrationRequest, $admin);

            $registrationRequest->approve($admin);
            $registrationRequest->update([
                'user_id' => $result['user']->id,
                'doctor_id' => $result['doctor']->id ?? null,
                'clinic_id' => $result['clinic']->id ?? null,
                'laboratory_id' => $result['laboratory']->id ?? null,
                'spa_id' => $result['spa']->id ?? null,
                'care_home_id' => $result['care_home']->id ?? null,
            ]);
            $this->ensureApprovedProfileIsVisible($registrationRequest);

            if ($sendApprovalEmail) {
                Mail::to($registrationRequest->email)->send(
                    new RegistrationApprovedMail($registrationRequest, $result['user'])
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function ensureApprovedProfileIsVisible(RegistrationRequest $registrationRequest): void
    {
        if ($registrationRequest->type === 'doctor' && $registrationRequest->doctor_id) {
            $doctor = Doktor::find($registrationRequest->doctor_id);
            if ($doctor) {
                $doctor->update([
                    'aktivan' => true,
                    'verifikovan' => true,
                    'verifikovan_at' => $doctor->verifikovan_at ?? now(),
                    'verifikovan_by' => $doctor->verifikovan_by ?? $registrationRequest->reviewed_by,
                ]);
            }
        }

        if ($registrationRequest->type === 'clinic' && $registrationRequest->clinic_id) {
            $clinic = Klinika::find($registrationRequest->clinic_id);
            if ($clinic) {
                $clinic->update([
                    'aktivan' => true,
                    'verifikovan' => true,
                    'verifikovan_at' => $clinic->verifikovan_at ?? now(),
                    'verifikovan_by' => $clinic->verifikovan_by ?? $registrationRequest->reviewed_by,
                ]);
            }
        }
    }
}
