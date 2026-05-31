<?php

namespace Tests\Feature;

use App\Models\Grad;
use App\Models\ApotekaDezurstvo;
use App\Models\ApotekaPoslovnica;
use App\Models\ApotekaSlugRedirect;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPharmacyWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_admin_can_create_pharmacy_with_custom_working_hours(): void
    {
        Sanctum::actingAs($this->adminUser());
        $city = $this->createCity('Sarajevo');

        $response = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Test Apoteka',
            'telefon' => '+38761111111',
            'grad_id' => $city->id,
            'adresa' => 'Ulica 1',
            'status' => 'verified',
            'is_active' => true,
            'radno_vrijeme' => $this->workingHoursPayload('07:30', '21:00'),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.day_of_week', 1)
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.open_time', '07:30:00')
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.close_time', '21:00:00');

        $branch = ApotekaPoslovnica::with('radnoVrijeme')->firstOrFail();

        $this->assertCount(7, $branch->radnoVrijeme);
        $this->assertSame($city->id, $branch->grad_id);
        $this->assertSame('Sarajevo', $branch->grad_naziv);
        $this->assertSame('07:30', substr((string) $branch->radnoVrijeme->firstWhere('day_of_week', 1)?->open_time, 0, 5));
        $this->assertSame('21:00', substr((string) $branch->radnoVrijeme->firstWhere('day_of_week', 1)?->close_time, 0, 5));
    }

    public function test_admin_created_pharmacy_uses_brand_name_as_public_branch_name(): void
    {
        Sanctum::actingAs($this->adminUser());
        $city = $this->createCity('Sarajevo');

        $response = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Apoteka Alipasin Most',
            'telefon' => '+38761111111',
            'grad_id' => $city->id,
            'adresa' => 'Bulevar 1',
            'status' => 'verified',
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.glavna_poslovnica.naziv', 'Apoteka Alipasin Most')
            ->assertJsonPath('data.glavna_poslovnica.slug', 'apoteka-alipasin-most');

        $this->assertDatabaseHas('apoteke_poslovnice', [
            'naziv' => 'Apoteka Alipasin Most',
            'slug' => 'apoteka-alipasin-most',
        ]);
    }

    public function test_admin_rename_updates_public_pharmacy_name_slug_and_redirects_old_slug(): void
    {
        Sanctum::actingAs($this->adminUser());
        config(['app.frontend_url' => 'https://wizmedik.com']);
        $city = $this->createCity('Sarajevo');

        $createResponse = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Pharmacy Alipasin Most',
            'telefon' => '+38761111111',
            'grad_id' => $city->id,
            'adresa' => 'Bulevar 1',
            'status' => 'verified',
            'is_active' => true,
        ]);
        $createResponse->assertCreated();
        $firmId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/pharmacies/{$firmId}", [
            'naziv_brenda' => 'Apoteka Alipasin Most',
            'telefon' => '+38761111111',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.naziv_brenda', 'Apoteka Alipasin Most')
            ->assertJsonPath('data.glavna_poslovnica.naziv', 'Apoteka Alipasin Most')
            ->assertJsonPath('data.glavna_poslovnica.slug', 'apoteka-alipasin-most');

        $branch = ApotekaPoslovnica::firstOrFail();
        $this->assertSame('Apoteka Alipasin Most', $branch->naziv);
        $this->assertSame('apoteka-alipasin-most', $branch->slug);
        $this->assertSame(1, ApotekaSlugRedirect::query()->where('old_slug', 'pharmacy-alipasin-most')->count());

        $this->getJson('/api/apoteke/apoteka-alipasin-most')
            ->assertOk()
            ->assertJsonPath('naziv', 'Apoteka Alipasin Most')
            ->assertJsonPath('slug', 'apoteka-alipasin-most');

        $this->getJson('/api/apoteke/pharmacy-alipasin-most')
            ->assertOk()
            ->assertJsonPath('redirect_to', '/apoteka/apoteka-alipasin-most')
            ->assertJsonPath('slug', 'apoteka-alipasin-most');

        $this->get('/apoteka/pharmacy-alipasin-most')
            ->assertRedirect('https://wizmedik.com/apoteka/apoteka-alipasin-most');
    }

    public function test_admin_can_update_existing_pharmacy_working_hours(): void
    {
        Sanctum::actingAs($this->adminUser());
        $city = $this->createCity('Tuzla');

        $createResponse = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Apoteka Update',
            'telefon' => '+38762222222',
            'grad_id' => $city->id,
            'adresa' => 'Glavna 2',
            'status' => 'verified',
            'is_active' => true,
        ]);

        $createResponse->assertCreated();

        $firmId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/pharmacies/{$firmId}", [
            'radno_vrijeme' => $this->workingHoursPayload('09:00', '18:30'),
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.day_of_week', 1)
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.open_time', '09:00:00')
            ->assertJsonPath('data.glavna_poslovnica.radno_vrijeme.0.close_time', '18:30:00');

        $branch = ApotekaPoslovnica::with('radnoVrijeme')->firstOrFail();

        $this->assertSame('09:00', substr((string) $branch->radnoVrijeme->firstWhere('day_of_week', 1)?->open_time, 0, 5));
        $this->assertSame('18:30', substr((string) $branch->radnoVrijeme->firstWhere('day_of_week', 1)?->close_time, 0, 5));
    }

    public function test_admin_can_mark_pharmacy_as_currently_on_duty(): void
    {
        Sanctum::actingAs($this->adminUser());
        $city = $this->createCity('Tuzla');

        $response = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Dezurna Apoteka Tuzla',
            'telefon' => '+38763333444',
            'grad_id' => $city->id,
            'adresa' => 'Dezurna 1',
            'status' => 'verified',
            'is_active' => true,
            'is_dezurna' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.glavna_poslovnica.is_dezurna', true);

        $branch = ApotekaPoslovnica::firstOrFail();

        $this->assertDatabaseHas('apoteke_dezurstva', [
            'poslovnica_id' => $branch->id,
            'grad_id' => $city->id,
            'status' => 'confirmed',
            'source' => 'manual',
        ]);

        $publicResponse = $this->getJson('/api/apoteke?grad=tuzla&dezurna_now=1');

        $publicResponse
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.is_dezurna', true);
    }

    public function test_admin_can_remove_current_duty_status(): void
    {
        Sanctum::actingAs($this->adminUser());
        $city = $this->createCity('Mostar');

        $createResponse = $this->postJson('/api/admin/pharmacies', [
            'naziv_brenda' => 'Apoteka Mostar',
            'telefon' => '+38763333555',
            'grad_id' => $city->id,
            'adresa' => 'Mostarska 1',
            'status' => 'verified',
            'is_active' => true,
            'is_dezurna' => true,
        ]);
        $createResponse->assertCreated();

        $firmId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/pharmacies/{$firmId}", [
            'is_dezurna' => false,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.glavna_poslovnica.is_dezurna', false);

        $this->assertSame(0, ApotekaDezurstvo::query()->where('status', 'confirmed')->count());
        $this->assertSame(1, ApotekaDezurstvo::query()->where('status', 'cancelled')->count());
    }

    public function test_self_service_pharmacy_registration_stores_selected_city_id_for_later_approval(): void
    {
        Mail::fake();
        $city = $this->createCity('Doboj');

        $response = $this->postJson('/api/register/pharmacy', [
            'naziv_brenda' => 'Apoteka Doboj',
            'pravni_naziv' => 'Apoteka Doboj d.o.o.',
            'broj_licence' => 'LIC-123',
            'ime' => 'Kontakt Osoba',
            'email' => 'apoteka.public@gmail.com',
            'account_email' => 'apoteka.login@gmail.com',
            'telefon' => '+38763111222',
            'adresa' => 'Kralja Petra 1',
            'grad_id' => $city->id,
            'website' => 'https://apoteka-doboj.test',
            'password' => 'PharmacyCompat123!',
            'password_confirmation' => 'PharmacyCompat123!',
            'prihvatam_uslove' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'request_id']);

        /** @var RegistrationRequest $registration */
        $registration = RegistrationRequest::query()->latest('id')->firstOrFail();
        $message = json_decode((string) $registration->message, true);

        $this->assertSame('pharmacy', $registration->type);
        $this->assertSame('Doboj', $registration->grad);
        $this->assertSame($city->id, $message['grad_id'] ?? null);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create([
            'email' => 'admin-pharmacy-hours@example.com',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function createCity(string $name): Grad
    {
        return Grad::query()->create([
            'naziv' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'opis' => "Osnovni opis za {$name}.",
            'detaljni_opis' => "Detaljni opis za {$name} koji je dovoljan za testiranje izbora grada.",
            'aktivan' => true,
        ]);
    }

    /**
     * @return array<int, array<string, int|string|bool|null>>
     */
    private function workingHoursPayload(string $defaultOpen, string $defaultClose): array
    {
        return [
            ['day_of_week' => 1, 'open_time' => $defaultOpen, 'close_time' => $defaultClose, 'closed' => false],
            ['day_of_week' => 2, 'open_time' => $defaultOpen, 'close_time' => $defaultClose, 'closed' => false],
            ['day_of_week' => 3, 'open_time' => $defaultOpen, 'close_time' => $defaultClose, 'closed' => false],
            ['day_of_week' => 4, 'open_time' => $defaultOpen, 'close_time' => $defaultClose, 'closed' => false],
            ['day_of_week' => 5, 'open_time' => $defaultOpen, 'close_time' => $defaultClose, 'closed' => false],
            ['day_of_week' => 6, 'open_time' => '08:00', 'close_time' => '14:00', 'closed' => false],
            ['day_of_week' => 7, 'open_time' => null, 'close_time' => null, 'closed' => true],
        ];
    }
}
