<?php

namespace Tests\Feature;

use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApprovedPharmaciesImportTest extends TestCase
{
    use RefreshDatabase;

    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_url' => 'https://wizmedik.com']);
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function test_fetch_command_writes_candidate_files_without_database_import(): void
    {
        $this->setEnv('GOOGLE_MAPS_API_KEY', 'test-google-key');
        $outputDir = storage_path('framework/testing-pharmacy-import/fetch');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        Http::fake([
            'https://places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [[
                    'id' => 'place-banja-luka-1',
                    'displayName' => ['text' => 'Apoteka Test Banja Luka'],
                    'formattedAddress' => 'Gospodska 1, Banja Luka',
                    'location' => ['latitude' => 44.7722, 'longitude' => 17.1910],
                    'googleMapsUri' => 'https://maps.google.com/?cid=1',
                    'types' => ['pharmacy', 'health'],
                ]],
            ]),
        ]);

        $this->artisan('wizmedik:fetch-pharmacies-google', [
            '--city' => 'banja-luka',
            '--limit' => 1,
            '--details' => 'false',
            '--output' => $outputDir,
        ])->assertSuccessful();

        $this->assertSame(0, ApotekaFirma::query()->count());
        $this->assertSame(0, ApotekaPoslovnica::query()->count());
        $this->assertNotEmpty(glob($outputDir . DIRECTORY_SEPARATOR . 'banja-luka-candidates-*.json'));
        $this->assertNotEmpty(glob($outputDir . DIRECTORY_SEPARATOR . 'banja-luka-candidates-*.csv'));
    }

    public function test_approved_seeder_is_idempotent_and_creates_seo_visible_pharmacy(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-import@example.com',
            'role' => 'admin',
        ]);

        $path = storage_path('framework/testing-pharmacy-import/banja-luka-approved.json');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode($this->approvedPayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->setEnv('PHARMACY_IMPORT_ADMIN_EMAIL', $admin->email);
        $this->setEnv('PHARMACY_IMPORT_FILE', $path);
        $this->setEnv('PHARMACY_IMPORT_PUBLISH_NEW', 'true');

        $this->artisan('db:seed', ['--class' => 'ApprovedPharmaciesImportSeeder'])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => 'ApprovedPharmaciesImportSeeder'])->assertSuccessful();

        $this->assertSame(1, ApotekaFirma::query()->count());
        $this->assertSame(1, ApotekaPoslovnica::query()->count());

        $firm = ApotekaFirma::firstOrFail();
        $branch = ApotekaPoslovnica::with('radnoVrijeme')->firstOrFail();

        $this->assertNull($firm->owner_user_id);
        $this->assertSame('verified', $firm->status);
        $this->assertTrue((bool) $firm->is_active);
        $this->assertTrue((bool) $branch->is_active);
        $this->assertTrue((bool) $branch->is_verified);
        $this->assertSame('google_places', $branch->source);
        $this->assertSame('place-banja-luka-approved-1', $branch->google_place_id);
        $this->assertSame($admin->id, $branch->imported_by);
        $this->assertCount(7, $branch->radnoVrijeme);

        $apiResponse = $this->getJson('/api/apoteke?grad=banja-luka');
        $apiResponse
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.grad_naziv', 'Banja Luka');

        $this->get('/sitemap-pharmacies.xml')
            ->assertOk()
            ->assertSee('/apoteka/' . $branch->slug, false);

        $this->get('/sitemap-cities.xml')
            ->assertOk()
            ->assertSee('/apoteke/banja-luka', false);
    }

    public function test_approved_seeder_skips_existing_pharmacy_by_phone(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-import-phone@example.com',
            'role' => 'admin',
        ]);

        $path = storage_path('framework/testing-pharmacy-import/banja-luka-phone-duplicate.json');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode($this->approvedPayload([
            'google_place_id' => 'first-place-id',
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->setEnv('PHARMACY_IMPORT_ADMIN_EMAIL', $admin->email);
        $this->setEnv('PHARMACY_IMPORT_FILE', $path);

        $this->artisan('db:seed', ['--class' => 'ApprovedPharmaciesImportSeeder'])->assertSuccessful();
        $this->assertSame(1, ApotekaPoslovnica::query()->count());

        file_put_contents($path, json_encode($this->approvedPayload([
            'google_place_id' => 'different-place-id',
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->artisan('db:seed', ['--class' => 'ApprovedPharmaciesImportSeeder'])->assertSuccessful();
        $this->assertSame(1, ApotekaPoslovnica::query()->count());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function approvedPayload(array $overrides = []): array
    {
        return [
            'city' => 'Banja Luka',
            'city_slug' => 'banja-luka',
            'import_batch' => 'banja-luka-approved-test',
            'records' => [
                array_merge([
                    'google_place_id' => 'place-banja-luka-approved-1',
                    'name' => 'Apoteka Test Banja Luka',
                    'brand' => 'Apoteka Test Banja Luka',
                    'address' => 'Gospodska 1',
                    'phone' => '+387 51 123 456',
                    'international_phone' => '+387 51 123 456',
                    'website' => 'https://apoteka-test.example',
                    'google_maps_link' => 'https://maps.google.com/?cid=1',
                    'latitude' => 44.7722,
                    'longitude' => 17.1910,
                    'is_24h' => false,
                    'working_hours' => [
                        ['day_of_week' => 1, 'open_time' => '08:00', 'close_time' => '20:00', 'closed' => false],
                        ['day_of_week' => 2, 'open_time' => '08:00', 'close_time' => '20:00', 'closed' => false],
                    ],
                ], $overrides),
            ],
        ];
    }

    private function setEnv(string $key, string $value): void
    {
        if (!array_key_exists($key, $this->previousEnv)) {
            $this->previousEnv[$key] = getenv($key);
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
