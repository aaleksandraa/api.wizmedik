<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadImageFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_authenticated_admin_can_upload_image_successfully(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->adminUser());

        $file = UploadedFile::fake()->image('clinic.jpg', 1200, 800);

        $response = $this->postJson('/api/upload/image', [
            'folder' => 'clinics',
            'image' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonMissing(['error' => true]);

        $path = (string) $response->json('path');
        $url = (string) $response->json('url');

        $this->assertStringStartsWith('clinics/', $path);
        $this->assertTrue(
            str_ends_with($path, '.webp') || str_ends_with($path, '.jpg'),
            'Uploaded image should be persisted with a supported extension.'
        );
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/' . $path, $url);
    }

    public function test_upload_falls_back_to_original_file_when_image_decode_fails(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->adminUser());

        Image::shouldReceive('read')
            ->once()
            ->andThrow(new \RuntimeException('Decoder unavailable'));

        $file = UploadedFile::fake()->image('clinic.jpg', 1200, 800);

        $response = $this->postJson('/api/upload/image', [
            'folder' => 'clinics',
            'image' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('fallback', 'original');

        $path = (string) $response->json('path');

        $this->assertStringStartsWith('clinics/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create([
            'email' => 'upload-admin@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
