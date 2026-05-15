<?php

namespace Tests\Feature;

use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\Grad;
use App\Services\Pharmacies\PharmacyAddressParser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrepareApprovedPharmaciesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Banja Luka', 'Laktaši', 'Brčko', 'Široki Brijeg', 'Srebrenik'] as $city) {
            Grad::query()->updateOrCreate(
                ['slug' => Str::slug($city)],
                [
                    'naziv' => $city,
                    'opis' => "Apoteke u gradu {$city}.",
                    'detaljni_opis' => "Pregled apoteka za {$city}.",
                    'aktivan' => true,
                ]
            );
        }
    }

    public function test_address_parser_cleans_google_formatted_addresses(): void
    {
        $parser = app(PharmacyAddressParser::class);
        $cities = Grad::query()->get(['naziv', 'slug']);

        $brcko = $parser->parse([
            'address' => 'Branislava Nušića 5, Brčko, Bosnia and Herzegovina',
        ], $cities);
        $this->assertSame('Branislava Nušića 5', $brcko['address']);
        $this->assertSame('Brčko', $brcko['city']);
        $this->assertNull($brcko['reject_reason']);

        $siroki = $parser->parse([
            'address' => 'BA, Stjepana Radića, Široki Brijeg 88220, Bosnia and Herzegovina',
        ], $cities);
        $this->assertSame('Stjepana Radića', $siroki['address']);
        $this->assertSame('88220', $siroki['postal_code']);
        $this->assertSame('Široki Brijeg', $siroki['city']);

        $serbia = $parser->parse([
            'address' => 'Kolarova 21, Bački Petrovac 21470, Serbia',
        ], $cities);
        $this->assertSame('non_ba_country', $serbia['reject_reason']);

        $partial = $parser->parse([
            'address' => 'M1.8, Srebrenik, Bosnia and Herzegovina',
        ], $cities);
        $this->assertSame('M1.8', $partial['address']);
        $this->assertSame('Srebrenik', $partial['city']);
        $this->assertNull($partial['reject_reason']);

        $missingCity = $parser->parse([
            'address' => 'Generala Izeta Nanića 36, Bosnia and Herzegovina',
        ], $cities);
        $this->assertSame('missing_reliable_city', $missingCity['reject_reason']);

        $plusCode = $parser->parse([
            'address' => 'Q662+W6M, Banja Luka 78000, Bosnia and Herzegovina',
        ], $cities);
        $this->assertSame('plus_code_address', $plusCode['reject_reason']);
    }

    public function test_prepare_command_creates_approved_review_and_rejected_files_without_database_import(): void
    {
        $inputDir = storage_path('framework/testing-pharmacy-import/prepare');
        $outputDir = storage_path('framework/testing-pharmacy-import/prepare-output');
        if (!is_dir($inputDir)) {
            mkdir($inputDir, 0777, true);
        }
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $inputPath = $inputDir . DIRECTORY_SEPARATOR . 'country-candidates.json';
        file_put_contents($inputPath, json_encode([
            'records' => [
                [
                    'google_place_id' => 'place-laktasi',
                    'name' => 'Apoteka Laktaši',
                    'city' => 'Banja Luka',
                    'city_slug' => 'banja-luka',
                    'address' => 'Nemanjina 6, Laktaši 78250, Bosnia and Herzegovina',
                ],
                [
                    'google_place_id' => 'place-serbia',
                    'name' => 'Apoteka Srbija',
                    'city' => 'Banja Luka',
                    'city_slug' => 'banja-luka',
                    'address' => 'Kolarova 21, Bački Petrovac 21470, Serbia',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->artisan('wizmedik:prepare-approved-pharmacies', [
            '--file' => $inputPath,
            '--fetch-address-components' => 'false',
            '--output' => $outputDir,
        ])->assertSuccessful();

        $this->assertSame(0, ApotekaFirma::query()->count());
        $this->assertSame(0, ApotekaPoslovnica::query()->count());

        $approvedPath = glob($outputDir . DIRECTORY_SEPARATOR . '*-approved.json')[0] ?? null;
        $rejectedPath = glob($outputDir . DIRECTORY_SEPARATOR . '*-rejected.json')[0] ?? null;
        $reviewPath = glob($outputDir . DIRECTORY_SEPARATOR . '*-review.csv')[0] ?? null;

        $this->assertNotNull($approvedPath);
        $this->assertNotNull($rejectedPath);
        $this->assertNotNull($reviewPath);

        $approved = json_decode((string) file_get_contents($approvedPath), true);
        $rejected = json_decode((string) file_get_contents($rejectedPath), true);

        $this->assertSame('Nemanjina 6', $approved['records'][0]['address']);
        $this->assertSame('78250', $approved['records'][0]['postal_code']);
        $this->assertSame('Laktaši', $approved['records'][0]['city']);
        $this->assertSame('laktasi', $approved['records'][0]['city_slug']);
        $this->assertSame('non_ba_country', $rejected['records'][0]['reject_reason']);
    }
}
