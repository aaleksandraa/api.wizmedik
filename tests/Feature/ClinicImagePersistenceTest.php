<?php

namespace Tests\Feature;

use App\Models\Klinika;
use App\Models\Specijalnost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicImagePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_admin_can_create_clinic_with_gallery_images_and_paths_are_normalized(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-clinic-images@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $specialty = Specijalnost::create([
            'naziv' => 'Ortopedija',
        ]);

        $response = $this->postJson('/api/admin/clinics', [
            'naziv' => 'Galerija Klinika',
            'adresa' => 'Test adresa 1',
            'grad' => 'Doboj',
            'telefon' => '+38761123456',
            'specijalnosti' => [$specialty->id],
            'slike' => [
                'https://api.wizmedik.com/storage/clinics/cover.webp',
                '/storage/clinics/waiting-room.webp',
                'clinics/waiting-room.webp',
            ],
        ]);

        $response->assertStatus(201);

        /** @var Klinika $clinic */
        $clinic = Klinika::query()->latest('id')->firstOrFail();
        $storedImages = json_decode((string) $clinic->getRawOriginal('slike'), true);

        $this->assertSame([
            'clinics/cover.webp',
            'clinics/waiting-room.webp',
        ], $storedImages);

        $returnedImage = (string) $response->json('klinika.slike.0');
        $this->assertStringContainsString('/storage/clinics/cover.webp', $returnedImage);
    }

    public function test_new_clinic_owner_inherits_existing_gallery_and_can_update_it(): void
    {
        $owner = User::factory()->create([
            'email' => 'clinic-owner@example.com',
            'password' => Hash::make('ClinicOwner123!'),
            'role' => 'clinic',
        ]);
        $owner->assignRole('clinic');

        $clinic = Klinika::withoutEvents(fn () => Klinika::create([
            'user_id' => $owner->id,
            'naziv' => 'Naslijedjena Klinika',
            'slug' => 'naslijedjena-klinika',
            'adresa' => 'Naslijedjena 5',
            'grad' => 'Tuzla',
            'telefon' => '+38761111111',
            'slike' => [
                'clinics/cover.webp',
                'https://api.wizmedik.com/storage/clinics/hall.webp',
            ],
            'aktivan' => true,
            'verifikovan' => true,
        ]));

        Sanctum::actingAs($owner);

        $profileResponse = $this->getJson('/api/clinic/profile');
        $profileResponse->assertOk();

        $this->assertStringContainsString(
            '/storage/clinics/cover.webp',
            (string) $profileResponse->json('slike.0')
        );
        $this->assertStringContainsString(
            '/storage/clinics/hall.webp',
            (string) $profileResponse->json('slike.1')
        );

        $updateResponse = $this->putJson('/api/clinic/profile', [
            'slike' => [
                $profileResponse->json('slike.1'),
            ],
        ]);

        $updateResponse->assertOk();

        $clinic->refresh();
        $storedImages = json_decode((string) $clinic->getRawOriginal('slike'), true);

        $this->assertSame(['clinics/hall.webp'], $storedImages);
        $this->assertStringContainsString(
            '/storage/clinics/hall.webp',
            (string) $updateResponse->json('klinika.slike.0')
        );
    }
}
