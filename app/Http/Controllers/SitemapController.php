<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Get the base URL for the frontend
     */
    private function getBaseUrl(): string
    {
        return config('app.frontend_url', 'https://wizmedik.com');
    }

    /**
     * Main sitemap index
     */
    public function index()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $sitemaps = [
            'sitemap-pages.xml',
            'sitemap-doctors.xml',
            'sitemap-clinics.xml',
            'sitemap-specialties.xml',
            'sitemap-cities.xml',
            'sitemap-laboratories.xml',
            'sitemap-spas.xml',
            'sitemap-care-homes.xml',
            'sitemap-blog.xml'
        ];

        foreach ($sitemaps as $sitemap) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . $baseUrl . '/' . $sitemap . '</loc>';
            $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $xml .= '</sitemap>';
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Static pages sitemap
     */
    public function pages()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $pages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/doktori', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/klinike', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/specijalnosti', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['url' => '/gradovi', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/laboratorije', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['url' => '/banje', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/domovi-njega', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/blog', 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => '/pitanja', 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => '/o-nama', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/kontakt', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/uslovi-koristenja', 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['url' => '/politika-privatnosti', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        foreach ($pages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . $page['url'] . '</loc>';
            $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $page['priority'] . '</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Doctors sitemap
     */
    public function doctors()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $doctors = DB::table('doktori')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($doctors as $doctor) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/doktor/' . htmlspecialchars($doctor->slug) . '</loc>';
            $xml .= '<lastmod>' . $doctor->updated_at . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Clinics sitemap
     */
    public function clinics()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $clinics = DB::table('klinike')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($clinics as $clinic) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/klinika/' . htmlspecialchars($clinic->slug) . '</loc>';
            $xml .= '<lastmod>' . $clinic->updated_at . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Specialties sitemap
     */
    public function specialties()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $specialties = DB::table('specijalnosti')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($specialties as $specialty) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/specijalnost/' . htmlspecialchars($specialty->slug) . '</loc>';
            $xml .= '<lastmod>' . $specialty->updated_at . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Cities sitemap
     */
    public function cities()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $cities = DB::table('gradovi')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($cities as $city) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/grad/' . htmlspecialchars($city->slug) . '</loc>';
            $xml .= '<lastmod>' . $city->updated_at . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Laboratories sitemap
     */
    public function laboratories()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $laboratories = DB::table('laboratorije')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($laboratories as $lab) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/laboratorija/' . htmlspecialchars($lab->slug) . '</loc>';
            $xml .= '<lastmod>' . $lab->updated_at . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Spas sitemap
     */
    public function spas()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $spas = DB::table('banje')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($spas as $spa) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/banja/' . htmlspecialchars($spa->slug) . '</loc>';
            $xml .= '<lastmod>' . $spa->updated_at . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Care homes sitemap
     */
    public function careHomes()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $homes = DB::table('domovi_njega')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($homes as $home) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/dom-njega/' . htmlspecialchars($home->slug) . '</loc>';
            $xml .= '<lastmod>' . $home->updated_at . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Blog sitemap
     */
    public function blog()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $posts = DB::table('blog_postovi')
            ->where('status', 'published')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($posts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . $baseUrl . '/blog/' . htmlspecialchars($post->slug) . '</loc>';
            $xml .= '<lastmod>' . $post->updated_at . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
