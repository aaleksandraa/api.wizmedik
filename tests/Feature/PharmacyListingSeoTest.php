<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use App\Models\ApotekaDezurstvo;
use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\Grad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PharmacyListingSeoTest extends TestCase
{
    use DatabaseTransactions;

    private string $seoIndexTemplatePath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_url' => 'https://wizmedik.com']);

        $this->seoIndexTemplatePath = storage_path('framework/testing-seo/index.html');
        if (!is_dir(dirname($this->seoIndexTemplatePath))) {
            mkdir(dirname($this->seoIndexTemplatePath), 0777, true);
        }

        file_put_contents(
            $this->seoIndexTemplatePath,
            '<!doctype html><html lang="bs"><head><title>wizMedik</title></head><body><div id="root"></div></body></html>'
        );

        putenv('SEO_INDEX_TEMPLATE_PATH=' . $this->seoIndexTemplatePath);
        $_ENV['SEO_INDEX_TEMPLATE_PATH'] = $this->seoIndexTemplatePath;
        $_SERVER['SEO_INDEX_TEMPLATE_PATH'] = $this->seoIndexTemplatePath;
    }

    protected function tearDown(): void
    {
        @unlink($this->seoIndexTemplatePath);

        putenv('SEO_INDEX_TEMPLATE_PATH');
        unset($_ENV['SEO_INDEX_TEMPLATE_PATH'], $_SERVER['SEO_INDEX_TEMPLATE_PATH']);

        parent::tearDown();
    }

    public function test_pharmacy_listing_query_redirects_to_city_path_with_duty_filter(): void
    {
        $this->seedDutyPharmacyInModrica();

        $response = $this->get('/apoteke?grad=modrica&dezurna_now=1');

        $response->assertRedirect('https://wizmedik.com/apoteke/modrica?grad=modrica&dezurna_now=1');
    }

    public function test_pharmacy_city_path_redirects_when_duty_query_city_does_not_match_path(): void
    {
        $this->seedDutyPharmacyInModrica();

        $response = $this->get('/apoteke/modrica?grad=sarajevo&dezurna_now=1');

        $response->assertRedirect('https://wizmedik.com/apoteke/modrica?grad=modrica&dezurna_now=1');
    }

    public function test_duty_pharmacy_city_page_has_specific_seo_title_description_and_canonical(): void
    {
        $this->seedDutyPharmacyInModrica();

        $response = app(SeoController::class)->index(
            Request::create('/apoteke/modrica', 'GET', [
                'grad' => 'modrica',
                'dezurna_now' => '1',
            ])
        );

        $content = (string) $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<title>Dezurna apoteka - Modrica | wizMedik</title>', $content);
        $this->assertStringContainsString('Pronadjite 1+ dezurnih apoteka za Modrica.', $content);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $content);
        $this->assertStringContainsString(
            '<link rel="canonical" href="https://wizmedik.com/apoteke/modrica?grad=modrica&amp;dezurna_now=1">',
            $content
        );
    }

    public function test_city_sitemap_includes_duty_pharmacy_listing_urls(): void
    {
        $this->seedDutyPharmacyInModrica();

        $response = $this->get('/sitemap-cities.xml');

        $response->assertOk();
        $response->assertSee('<loc>https://wizmedik.com/apoteke/modrica</loc>', false);
        $response->assertSee(
            '<loc>https://wizmedik.com/apoteke/modrica?grad=modrica&amp;dezurna_now=1</loc>',
            false
        );
    }

    private function seedDutyPharmacyInModrica(): void
    {
        $city = Grad::withoutEvents(fn () => Grad::create([
            'naziv' => "Modri\u{010D}a",
            'slug' => 'modrica',
            'opis' => 'Testni SEO grad za apoteke.',
            'detaljni_opis' => 'Detaljni opis testnog grada za pharmacy SEO verifikaciju.',
            'aktivan' => true,
        ]));

        $firm = ApotekaFirma::create([
            'owner_user_id' => User::factory()->create([
                'email' => 'owner.modrica@gmail.com',
            ])->id,
            'naziv_brenda' => "Apoteka Modri\u{010D}a",
            'telefon' => '+38761123000',
            'email' => 'apoteka.modrica@gmail.com',
            'status' => 'verified',
            'is_active' => true,
        ]);

        $branch = ApotekaPoslovnica::create([
            'firma_id' => $firm->id,
            'naziv' => "Dezurna Modri\u{010D}a",
            'grad_id' => $city->id,
            'grad_naziv' => "Modri\u{010D}a",
            'adresa' => 'Cara Lazara 10',
            'telefon' => '+38761123111',
            'is_active' => true,
            'is_verified' => true,
        ]);

        ApotekaDezurstvo::create([
            'poslovnica_id' => $branch->id,
            'grad_id' => $city->id,
            'starts_at' => now('UTC')->subHour(),
            'ends_at' => now('UTC')->addHour(),
            'tip' => 'night',
            'is_nonstop' => false,
            'source' => 'manual',
            'status' => 'confirmed',
        ]);
    }
}
