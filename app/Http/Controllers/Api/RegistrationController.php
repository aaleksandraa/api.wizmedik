<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorRegistrationRequest;
use App\Http\Requests\ClinicRegistrationRequest;
use App\Models\RegistrationRequest;
use App\Models\SiteSetting;
use App\Mail\RegistrationVerificationMail;
use App\Mail\RegistrationReceivedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;

class RegistrationController extends Controller
{
    /**
     * Register a new doctor
     */
    public function registerDoctor(DoctorRegistrationRequest $request)
    {
        // Rate limiting
        $key = 'register-doctor:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        // Create registration request
        $registrationRequest = $this->createRegistrationRequest('doctor', $request);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        // Send confirmation email
        $this->sendConfirmationEmail($registrationRequest);

        return response()->json([
            'message' => 'Zahtjev za registraciju je uspješno poslat. Molimo provjerite vaš email za verifikaciju.',
            'request_id' => $registrationRequest->id,
        ], 201);
    }

    /**
     * Register a new clinic
     */
    public function registerClinic(ClinicRegistrationRequest $request)
    {
        // Rate limiting
        $key = 'register-clinic:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        // Create registration request
        $registrationRequest = $this->createRegistrationRequest('clinic', $request);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        // Send confirmation email
        $this->sendConfirmationEmail($registrationRequest);

        return response()->json([
            'message' => 'Zahtjev za registraciju je uspješno poslat. Molimo provjerite vaš email za verifikaciju.',
            'request_id' => $registrationRequest->id,
        ], 201);
    }

    /**
     * Register a new laboratory
     */
    public function registerLaboratory(\App\Http\Requests\LaboratoryRegistrationRequest $request)
    {
        // Rate limiting
        $key = 'register-laboratory:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        // Create registration request
        $registrationRequest = $this->createRegistrationRequest('laboratory', $request);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        // Send confirmation email
        $this->sendConfirmationEmail($registrationRequest);

        return response()->json([
            'message' => 'Zahtjev za registraciju je uspješno poslat. Molimo provjerite vaš email za verifikaciju.',
            'request_id' => $registrationRequest->id,
        ], 201);
    }

    /**
     * Register a new spa
     */
    public function registerSpa(\App\Http\Requests\SpaRegistrationRequest $request)
    {
        // Rate limiting
        $key = 'register-spa:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        // Create registration request
        $registrationRequest = $this->createRegistrationRequest('spa', $request);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        // Send confirmation email
        $this->sendConfirmationEmail($registrationRequest);

        return response()->json([
            'message' => 'Zahtjev za registraciju je uspješno poslat. Molimo provjerite vaš email za verifikaciju.',
            'request_id' => $registrationRequest->id,
        ], 201);
    }

    /**
     * Register a new care home (dom za njegu)
     */
    public function registerCareHome(\App\Http\Requests\CareHomeRegistrationRequest $request)
    {
        // Rate limiting
        $key = 'register-care-home:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Previše pokušaja. Pokušajte ponovo za {$seconds} sekundi."
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        // Create registration request
        $registrationRequest = $this->createRegistrationRequest('care_home', $request);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        // Send confirmation email
        $this->sendConfirmationEmail($registrationRequest);

        return response()->json([
            'message' => 'Zahtjev za registraciju je uspješno poslat. Molimo provjerite vaš email za verifikaciju.',
            'request_id' => $registrationRequest->id,
        ], 201);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token)
    {
        $registrationRequest = RegistrationRequest::where('email_verification_token', $token)
            ->where('status', 'pending')
            ->whereNull('email_verified_at')
            ->first();

        if (!$registrationRequest) {
            return response()->json([
                'message' => 'Nevažeći ili istekao verifikacioni link.'
            ], 404);
        }

        // Check expiration
        $registrationRequest->checkExpiration();
        if ($registrationRequest->is_expired) {
            return response()->json([
                'message' => 'Verifikacioni link je istekao.'
            ], 410);
        }

        // Mark as verified
        $registrationRequest->markAsVerified();

        // Check if auto-approve is enabled for free registrations
        $isFree = $this->isRegistrationFree($registrationRequest->type);
        $autoApprove = SiteSetting::get('registration_auto_approve', 'false') === 'true';

        if ($isFree && $autoApprove) {
            // Auto-approve and create profile
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                app(AdminRegistrationController::class)->approveRequest($registrationRequest->id, $admin);
            }

            return response()->json([
                'message' => 'Email je uspješno verifikovan! Vaš profil je automatski aktiviran.',
                'auto_approved' => true,
            ]);
        }

        return response()->json([
            'message' => 'Email je uspješno verifikovan! Vaš zahtjev će biti pregledan u najkraćem roku.',
            'auto_approved' => false,
        ]);
    }

    /**
     * Verify email with code
     */
    public function verifyEmailWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $registrationRequest = RegistrationRequest::where('email', $request->email)
            ->where('verification_code', $request->code)
            ->where('status', 'pending')
            ->whereNull('email_verified_at')
            ->first();

        if (!$registrationRequest) {
            $registrationRequest = RegistrationRequest::where('email', $request->email)
                ->where('status', 'pending')
                ->whereNull('email_verified_at')
                ->first();

            if ($registrationRequest) {
                $registrationRequest->incrementAttempts();
            }

            return response()->json([
                'message' => 'Nevažeći verifikacioni kod.'
            ], 400);
        }

        // Check expiration
        $registrationRequest->checkExpiration();
        if ($registrationRequest->is_expired) {
            return response()->json([
                'message' => 'Verifikacioni kod je istekao.'
            ], 410);
        }

        // Mark as verified
        $registrationRequest->markAsVerified();

        // Check auto-approve
        $isFree = $this->isRegistrationFree($registrationRequest->type);
        $autoApprove = SiteSetting::get('registration_auto_approve', 'false') === 'true';

        if ($isFree && $autoApprove) {
            $admin = \App\Models\User::where('role', 'admin')->first();
            if ($admin) {
                app(AdminRegistrationController::class)->approveRequest($registrationRequest->id, $admin);
            }

            return response()->json([
                'message' => 'Email je uspješno verifikovan! Vaš profil je automatski aktiviran.',
                'auto_approved' => true,
            ]);
        }

        return response()->json([
            'message' => 'Email je uspješno verifikovan! Vaš zahtjev će biti pregledan u najkraćem roku.',
            'auto_approved' => false,
        ]);
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $registrationRequest = RegistrationRequest::where('email', $request->email)
            ->where('status', 'pending')
            ->whereNull('email_verified_at')
            ->first();

        if (!$registrationRequest) {
            return response()->json([
                'message' => 'Zahtjev nije pronađen.'
            ], 404);
        }

        // Check expiration
        $registrationRequest->checkExpiration();
        if ($registrationRequest->is_expired) {
            return response()->json([
                'message' => 'Zahtjev je istekao. Molimo registrujte se ponovo.'
            ], 410);
        }

        // Check attempts
        $maxAttempts = (int) SiteSetting::get('registration_max_attempts', 3);
        if ($registrationRequest->attempts >= $maxAttempts) {
            return response()->json([
                'message' => 'Dostigli ste maksimalan broj pokušaja.'
            ], 429);
        }

        // Generate new code
        $registrationRequest->update([
            'verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);

        // Send verification email
        $this->sendVerificationEmail($registrationRequest);

        return response()->json([
            'message' => 'Verifikacioni email je ponovo poslat.'
        ]);
    }

    /**
     * Get registration settings
     */
    public function getSettings()
    {
        return response()->json([
            'doctor' => [
                'enabled' => SiteSetting::get('doctor_registration_enabled', 'true') === 'true',
                'free' => SiteSetting::get('doctor_registration_free', 'true') === 'true',
                'price' => (float) SiteSetting::get('doctor_registration_price', 0),
                'message' => SiteSetting::get('doctor_registration_message', ''),
            ],
            'clinic' => [
                'enabled' => SiteSetting::get('clinic_registration_enabled', 'true') === 'true',
                'free' => SiteSetting::get('clinic_registration_free', 'true') === 'true',
                'price' => (float) SiteSetting::get('clinic_registration_price', 0),
                'message' => SiteSetting::get('clinic_registration_message', ''),
            ],
            'laboratory' => [
                'enabled' => SiteSetting::get('laboratory_registration_enabled', 'true') === 'true',
                'free' => SiteSetting::get('laboratory_registration_free', 'true') === 'true',
                'price' => (float) SiteSetting::get('laboratory_registration_price', 0),
                'message' => SiteSetting::get('laboratory_registration_message', ''),
            ],
            'spa' => [
                'enabled' => SiteSetting::get('spa_registration_enabled', 'true') === 'true',
                'free' => SiteSetting::get('spa_registration_free', 'true') === 'true',
                'price' => (float) SiteSetting::get('spa_registration_price', 0),
                'message' => SiteSetting::get('spa_registration_message', ''),
            ],
            'care_home' => [
                'enabled' => SiteSetting::get('care_home_registration_enabled', 'true') === 'true',
                'free' => SiteSetting::get('care_home_registration_free', 'true') === 'true',
                'price' => (float) SiteSetting::get('care_home_registration_price', 0),
                'message' => SiteSetting::get('care_home_registration_message', ''),
            ],
            'require_documents' => SiteSetting::get('registration_require_documents', 'false') === 'true',
        ]);
    }

    /**
     * Create registration request
     */
    private function createRegistrationRequest(string $type, $request): RegistrationRequest
    {
        // For spa and care_home, use account_email for login, otherwise use email
        $accountEmail = in_array($type, ['spa', 'care_home'])
            ? $request->account_email
            : $request->email;

        $data = [
            'type' => $type,
            'status' => 'pending',
            'email' => $accountEmail, // Email for user account/login
            'password' => Hash::make($request->password),
            'telefon' => $request->telefon,
            'adresa' => $request->adresa,
            'grad' => $request->grad,
            'message' => $request->message,
            'email_verification_token' => Str::random(64),
            'verification_code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempts' => 0,
            'expires_at' => now()->addDays((int) SiteSetting::get('registration_expiry_days', 7)),
        ];

        if ($type === 'doctor') {
            $data['ime'] = $request->ime;
            $data['prezime'] = $request->prezime;
            // Support both old single specialty and new multiple specialties
            if ($request->has('specialty_ids')) {
                $data['specijalnost_id'] = $request->specialty_ids[0]; // Primary specialty
                $data['message'] = json_encode([
                    'specialty_ids' => $request->specialty_ids,
                    'message' => $request->message,
                ]);
            } else {
                $data['specijalnost_id'] = $request->specijalnost_id;
            }
        } elseif ($type === 'laboratory') {
            $data['naziv'] = $request->naziv;
            $data['ime'] = $request->ime; // Contact person
            // Use account_email if provided, otherwise use public email
            if ($request->account_email) {
                $data['email'] = $request->account_email;
                // Store public email in message
                $data['message'] = json_encode([
                    'public_email' => $request->email,
                    'message' => $request->message,
                ]);
            }
        } elseif ($type === 'spa') {
            $data['naziv'] = $request->naziv;
            $data['ime'] = $request->kontakt_ime . ' ' . ($request->kontakt_prezime ?? ''); // Contact person
            // Store additional spa data in message as JSON (including public email)
            $data['message'] = json_encode([
                'public_email' => $request->email, // Public email for profile display
                'vrste' => $request->vrste,
                'medicinski_nadzor' => $request->medicinski_nadzor,
                'ima_smjestaj' => $request->ima_smjestaj,
                'website' => $request->website,
                'opis' => $request->opis,
                'napomena' => $request->napomena,
            ]);
        } elseif ($type === 'care_home') {
            $data['naziv'] = $request->naziv;
            $data['ime'] = $request->kontakt_ime; // Contact person
            // Store additional care home data in message as JSON
            // Store additional care home data in message as JSON (including public email)
            $data['message'] = json_encode([
                'public_email' => $request->email, // Public email for profile display
                'tip_doma_id' => $request->tip_doma_id,
                'nivo_njege_id' => $request->nivo_njege_id,
                'programi_njege' => $request->programi_njege,
                'nurses_availability' => $request->nurses_availability,
                'doctor_availability' => $request->doctor_availability,
                'has_physiotherapist' => $request->has_physiotherapist,
                'has_physiatrist' => $request->has_physiatrist,
                'emergency_protocol' => $request->emergency_protocol,
                'website' => $request->website,
                'opis' => $request->opis,
                'napomena' => $request->napomena,
            ]);
        } else {
            $data['naziv'] = $request->naziv;
            $data['ime'] = $request->ime; // Contact person
        }

        // Handle document uploads
        if ($request->hasFile('documents')) {
            $documents = [];
            foreach ($request->file('documents') as $file) {
                $path = $file->store('registration-documents', 'private');
                $documents[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
            $data['documents'] = $documents;
        }

        return RegistrationRequest::create($data);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(RegistrationRequest $registrationRequest): void
    {
        Mail::to($registrationRequest->email)->send(
            new RegistrationVerificationMail($registrationRequest)
        );
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail(RegistrationRequest $registrationRequest): void
    {
        Mail::to($registrationRequest->email)->send(
            new RegistrationReceivedMail($registrationRequest)
        );
    }

    /**
     * Check if registration is free
     */
    private function isRegistrationFree(string $type): bool
    {
        $key = $type . '_registration_free';
        return SiteSetting::get($key, 'true') === 'true';
    }
}
