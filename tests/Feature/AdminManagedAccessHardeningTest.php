<?php

namespace Tests\Feature;

use App\Models\Klinika;
use App\Models\RegistrationRequest;
use App\Models\Specijalnost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
