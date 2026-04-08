<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use App\Models\Grad;
use App\Models\Klinika;
use App\Models\Specijalnost;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClinicListingSeoTest extends TestCase
{
    use DatabaseTransactions;

    private string $seoIndexTemplatePath;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_url' => 'https://wizmedik.com']);

        $this->seoIndexTemplatePath = storage_path('framework/testing-seo-clinics/index.html');
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

    public function test_clinic_listing_query_redirects_to_city_and_specialty_path(): void
    {
        $this->seedOrthopedicClinicInDoboj();

        $response = $this->get('/klinike?grad=doboj&specijalnost=ortopedija');

        $response->assertRedirect('https://wizmedik.com/klinike/doboj/ortopedija');
    }

    public function test_clinic_city_specialty_page_has_specific_seo_title_description_and_canonical(): void
    {
        $this->seedOrthopedicClinicInDoboj();

        $response = app(SeoController::class)->index(
            Request::create('/klinike/doboj/ortopedija', 'GET')
        );

        $content = (string) $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<title>Klinike za Ortopedija - Doboj | wizMedik</title>', $content);
        $this->assertStringContainsString('Pronadjite klinike za Ortopedija u Doboj.', $content);
        $this->assertStringContainsString(
            '<link rel="canonical" href="https://wizmedik.com/klinike/doboj/ortopedija">',
            $content
        );
    }

    public function test_clinic_city_specialties_sitemap_includes_combined_url(): void
    {
        $this->seedOrthopedicClinicInDoboj();

        $response = $this->get('/sitemap-clinic-city-specialties.xml');

        $response->assertOk();
        $response->assertSee('<loc>https://wizmedik.com/klinike/doboj/ortopedija</loc>', false);
    }

    public function test_clinic_listing_api_filters_by_clinic_specialty_relationship_without_doctors(): void
    {
        $this->seedOrthopedicClinicInDoboj();

        $response = $this->getJson('/api/clinics?grad=doboj&specijalnost=ortopedija&limit=10');

        $response->assertOk();
        $response->assertJsonFragment([
            'naziv' => 'Orto Centar Doboj',
            'grad' => 'Doboj',
        ]);
    }

    public function test_clinic_city_specialties_sitemap_includes_parent_specialty_route_for_child_assignments(): void
    {
        $this->seedOrthopedicClinicInDoboj();

        $response = $this->get('/sitemap-clinic-city-specialties.xml');

        $response->assertOk();
        $response->assertSee('<loc>https://wizmedik.com/klinike/doboj/hirurgija</loc>', false);
    }

    private function seedOrthopedicClinicInDoboj(): void
    {
        $city = Grad::withoutEvents(fn () => Grad::create([
            'naziv' => 'Doboj',
            'slug' => 'doboj',
            'opis' => 'Testni grad za klinike.',
            'detaljni_opis' => 'Detaljni opis testnog grada za clinic SEO verifikaciju.',
            'aktivan' => true,
        ]));

        $parentSpecialty = Specijalnost::create([
            'naziv' => 'Hirurgija',
            'slug' => 'hirurgija',
            'aktivan' => true,
        ]);

        $specialty = Specijalnost::create([
            'naziv' => 'Ortopedija',
            'slug' => 'ortopedija',
            'parent_id' => $parentSpecialty->id,
            'aktivan' => true,
        ]);

        $clinic = Klinika::create([
            'naziv' => 'Orto Centar Doboj',
            'opis' => 'Specijalisticka klinika za ortopediju.',
            'adresa' => 'Svetog Save 12',
            'grad' => $city->naziv,
            'telefon' => '+38753111222',
            'email' => 'info@ortodoboj.ba',
            'website' => 'https://ortodoboj.ba',
            'aktivan' => true,
            'verifikovan' => true,
        ]);

        $clinic->specijalnosti()->sync([$specialty->id]);
    }
}
