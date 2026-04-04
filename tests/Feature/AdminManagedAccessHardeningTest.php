<?php

namespace Tests\Feature;

use App\Models\Doktor;
use App\Models\Klinika;
use App\Models\RegistrationRequest;
use App\Models\Specijalnost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminManagedAccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_admin_clinic_access_email_cannot_collide_with_pending_registration_request(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $clinic = Klinika::withoutEvents(fn () => Klinika::create([
            'naziv' => 'Test Klinika',
            'slug' => 'test-klinika',
            'adresa' => 'Test adresa 1',
            'grad' => 'Sarajevo',
            'telefon' => '+38761111222',
            'aktivan' => true,
            'verifikovan' => true,
        ]));

        RegistrationRequest::create([
            'type' => 'clinic',
            'status' => 'pending',
            'email' => 'rezervisan@example.com',
            'password' => Hash::make('Registration123!'),
            'ime' => 'Kontakt',
            'naziv' => 'Pending Klinika',
            'telefon' => '+38761111333',
            'adresa' => 'Druga adresa 2',
            'grad' => 'Tuzla',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->putJson("/api/admin/clinics/{$clinic->id}", [
            'account_email' => 'rezervisan@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['account_email']);
    }

    public function test_admin_clinic_access_email_cannot_attach_legacy_patient_user(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin2@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        User::factory()->create([
            'email' => 'legacy-patient@example.com',
            'password' => Hash::make('LegacyPatient123!'),
            'role' => 'patient',
        ]);

        $clinic = Klinika::withoutEvents(fn () => Klinika::create([
            'naziv' => 'Sigurna Klinika',
            'slug' => 'sigurna-klinika',
            'adresa' => 'Sigurna adresa 3',
            'grad' => 'Mostar',
            'telefon' => '+38761111444',
            'aktivan' => true,
            'verifikovan' => true,
        ]));

        $response = $this->putJson("/api/admin/clinics/{$clinic->id}", [
            'account_email' => 'legacy-patient@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['account_email']);
    }

    public function test_admin_clinic_transfer_creates_new_owner_and_revokes_old_owner_sessions(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-transfer@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $oldOwner = User::factory()->create([
            'email' => 'old-owner@example.com',
            'password' => Hash::make('OldOwnerPass123!'),
            'role' => 'clinic',
        ]);
        $oldOwner->assignRole('clinic');
        $oldOwner->createToken('existing-device');

        $clinic = Klinika::withoutEvents(fn () => Klinika::create([
            'naziv' => 'Transfer Klinika',
            'slug' => 'transfer-klinika',
            'adresa' => 'Transfer adresa 5',
            'grad' => 'Zenica',
            'telefon' => '+38761111777',
            'user_id' => $oldOwner->id,
            'aktivan' => true,
            'verifikovan' => true,
        ]));

        $response = $this->putJson("/api/admin/clinics/{$clinic->id}", [
            'account_email' => 'new-owner@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('klinika.user.email', 'new-owner@example.com');

        $clinic->refresh();
        $oldOwner->refresh();
        $newOwner = User::where('email', 'new-owner@example.com')->first();

        $this->assertNotNull($newOwner);
        $this->assertSame($newOwner->id, $clinic->user_id);
        $this->assertSame('old-owner@example.com', $oldOwner->email);
        $this->assertSame(0, $oldOwner->tokens()->count());
        $this->assertFalse($oldOwner->hasRole('clinic'));
        $this->assertSame('patient', $oldOwner->role);
        $this->assertTrue($oldOwner->hasRole('patient'));
        $this->assertTrue($newOwner->hasRole('clinic'));
    }

    public function test_admin_clinic_access_email_cannot_attach_user_with_conflicting_managed_role(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-managed-role@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $doctorAccount = User::factory()->create([
            'email' => 'doctor-business@example.com',
            'password' => Hash::make('DoctorBusiness123!'),
            'role' => 'doctor',
        ]);
        $doctorAccount->assignRole('doctor');

        $clinic = Klinika::withoutEvents(fn () => Klinika::create([
            'naziv' => 'Konflikt Klinika',
            'slug' => 'konflikt-klinika',
            'adresa' => 'Konflikt adresa 7',
            'grad' => 'Bihac',
            'telefon' => '+38761111888',
            'aktivan' => true,
            'verifikovan' => true,
        ]));

        $response = $this->putJson("/api/admin/clinics/{$clinic->id}", [
            'account_email' => 'doctor-business@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['account_email']);
    }

    public function test_password_reset_revokes_existing_access_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-owner@example.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'clinic',
        ]);
        $user->assignRole('clinic');
        $user->createToken('existing-device');

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/password/reset', [
            'email' => 'reset-owner@example.com',
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_admin_can_create_doctor_with_maps_link_and_normalized_working_hours(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-doctor-create@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $specialty = Specijalnost::create([
            'naziv' => 'Dermatologija',
        ]);

        $response = $this->postJson('/api/admin/doctors', [
            'ime' => 'Lejla',
            'prezime' => 'Doktor',
            'telefon' => '+38761111999',
            'specijalnost' => 'Dermatolog',
            'specijalnost_id' => $specialty->id,
            'grad' => 'Sarajevo',
            'lokacija' => 'Zdravstvena 11',
            'google_maps_link' => 'https://maps.google.com/?q=43.8563,18.4131',
            'radno_vrijeme' => [
                'ponedjeljak' => ['open' => '07:30', 'close' => '15:30', 'closed' => false],
                'sreda' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                'nedelja' => ['open' => '10:00', 'close' => '13:00', 'closed' => true],
            ],
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('doktor.google_maps_link', 'https://maps.google.com/?q=43.8563,18.4131');

        /** @var Doktor $doctor */
        $doctor = Doktor::query()->latest('id')->firstOrFail();

        $this->assertSame('https://maps.google.com/?q=43.8563,18.4131', $doctor->google_maps_link);
        $this->assertSame('07:30', $doctor->radno_vrijeme['ponedeljak']['open']);
        $this->assertSame('09:00', $doctor->radno_vrijeme['srijeda']['open']);
        $this->assertTrue($doctor->radno_vrijeme['nedjelja']['closed']);
    }

    public function test_self_service_clinic_registration_still_creates_pending_request(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register/clinic', [
            'naziv' => 'Kompatibilna Klinika',
            'ime' => 'Kontakt',
            'email' => 'clinic.compat@gmail.com',
            'telefon' => '+38761111555',
            'password' => 'ClinicCompat123!',
            'password_confirmation' => 'ClinicCompat123!',
            'adresa' => 'Glavna 12',
            'grad' => 'Sarajevo',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'request_id']);

        $this->assertDatabaseHas('registration_requests', [
            'type' => 'clinic',
            'status' => 'pending',
            'email' => 'clinic.compat@gmail.com',
            'naziv' => 'Kompatibilna Klinika',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'clinic.compat@gmail.com',
        ]);
    }

    public function test_self_service_doctor_registration_still_creates_pending_request(): void
    {
        Mail::fake();

        $specialty = Specijalnost::create([
            'naziv' => 'Kardiologija',
        ]);

        $response = $this->postJson('/api/register/doctor', [
            'ime' => 'Amar',
            'prezime' => 'Ljekar',
            'email' => 'doctor.compat@gmail.com',
            'telefon' => '+38761111666',
            'password' => 'DoctorCompat123!',
            'password_confirmation' => 'DoctorCompat123!',
            'specialty_ids' => [$specialty->id],
            'adresa' => 'Medicinska 8',
            'grad' => 'Tuzla',
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'request_id']);

        $this->assertDatabaseHas('registration_requests', [
            'type' => 'doctor',
            'status' => 'pending',
            'email' => 'doctor.compat@gmail.com',
            'ime' => 'Amar',
            'prezime' => 'Ljekar',
            'specijalnost_id' => $specialty->id,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'doctor.compat@gmail.com',
        ]);
    }
}
