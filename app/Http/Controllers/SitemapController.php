<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    private function getBaseUrl(): string
    {
        return rtrim(config('app.frontend_url', 'https://wizmedik.com'), '/');
    }

    private function xmlEscape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xmlDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if (empty($value)) {
            return now()->format('c');
        }

        return date('c', strtotime((string) $value));
    }

    private function appendUrl(
        string &$xml,
        string $loc,
        $lastmod,
        string $changefreq,
        string $priority
    ): void {
        $xml .= '<url>';
        $xml .= '<loc>' . $this->xmlEscape($loc) . '</loc>';
        $xml .= '<lastmod>' . $this->xmlDate($lastmod) . '</lastmod>';
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';
    }

    private function citySlugMap(): array
    {
        $map = [];

        $cities = DB::table('gradovi')
            ->select('naziv', 'slug')
            ->whereNotNull('naziv')
            ->whereNotNull('slug')
            ->get();

        foreach ($cities as $city) {
            $key = mb_strtolower(trim((string) $city->naziv));
            if ($key !== '') {
                $map[$key] = (string) $city->slug;
            }
        }

        return $map;
    }

    private function specialtySlugMap(): array
    {
        $map = [];

        $specialties = DB::table('specijalnosti')
            ->select('naziv', 'slug')
            ->whereNotNull('naziv')
            ->whereNotNull('slug')
            ->get();

        foreach ($specialties as $specialty) {
            $key = mb_strtolower(trim((string) $specialty->naziv));
            if ($key !== '') {
                $map[$key] = (string) $specialty->slug;
            }
        }

        return $map;
    }

    private function resolveCitySlug(string $cityName, array $citySlugMap): string
    {
        $key = mb_strtolower(trim($cityName));
        if (isset($citySlugMap[$key]) && $citySlugMap[$key] !== '') {
            return $citySlugMap[$key];
        }

        $slug = Str::slug($cityName);
        return $slug !== '' ? $slug : rawurlencode($cityName);
    }

    private function resolveSpecialtySlug(string $specialtyName, array $specialtySlugMap): string
    {
        $key = mb_strtolower(trim($specialtyName));
        if (isset($specialtySlugMap[$key]) && $specialtySlugMap[$key] !== '') {
            return $specialtySlugMap[$key];
        }

        $slug = Str::slug($specialtyName);
        return $slug !== '' ? $slug : rawurlencode($specialtyName);
    }

    private function cityRowsForTable(string $table): Collection
    {
        return DB::table($table)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->whereNotNull('grad')
            ->where('grad', '!=', '')
            ->selectRaw('grad, MAX(updated_at) AS updated_at')
            ->groupBy('grad')
            ->get();
    }

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
            'sitemap-doctor-city-specialties.xml',
            'sitemap-blog.xml',
            'sitemap-pitanja.xml',
        ];

        foreach ($sitemaps as $sitemap) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . $this->xmlEscape($baseUrl . '/' . $sitemap) . '</loc>';
            $xml .= '<lastmod>' . now()->format('c') . '</lastmod>';
            $xml .= '</sitemap>';
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

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
            ['url' => '/banje/indikacije-terapije', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['url' => '/domovi-njega', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => '/domovi-njega/vodic', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['url' => '/blog', 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => '/pitanja', 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => '/medicinski-kalendar', 'priority' => '0.75', 'changefreq' => 'weekly'],
            ['url' => '/about', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/kalkulatori', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/mkb10', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => '/uslovi-koristenja', 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['url' => '/politika-privatnosti', 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        $lastmod = now()->format('c');

        foreach ($pages as $page) {
            $this->appendUrl(
                $xml,
                $baseUrl . $page['url'],
                $lastmod,
                $page['changefreq'],
                $page['priority']
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

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
            $this->appendUrl(
                $xml,
                $baseUrl . '/doktor/' . $doctor->slug,
                $doctor->updated_at,
                'weekly',
                '0.8'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

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
            $this->appendUrl(
                $xml,
                $baseUrl . '/klinika/' . $clinic->slug,
                $clinic->updated_at,
                'weekly',
                '0.8'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function specialties()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $specialties = DB::table('specijalnosti')
            ->select('slug', 'updated_at')
            ->where('aktivan', true)
            ->whereNotNull('slug')
            ->get();

        foreach ($specialties as $specialty) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/specijalnost/' . $specialty->slug,
                $specialty->updated_at,
                'monthly',
                '0.7'
            );

            $this->appendUrl(
                $xml,
                $baseUrl . '/doktori/specijalnost/' . $specialty->slug,
                $specialty->updated_at,
                'weekly',
                '0.85'
            );

            $this->appendUrl(
                $xml,
                $baseUrl . '/klinike/specijalnost/' . $specialty->slug,
                $specialty->updated_at,
                'weekly',
                '0.8'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function cities()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $cities = DB::table('gradovi')
            ->select('slug', 'updated_at')
            ->where('aktivan', true)
            ->whereNotNull('slug')
            ->get();

        foreach ($cities as $city) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/grad/' . $city->slug,
                $city->updated_at,
                'weekly',
                '0.8'
            );
        }

        $citySlugMap = $this->citySlugMap();
        $added = [];

        $cityVerticalRoutes = [
            ['prefix' => 'doktori', 'rows' => $this->cityRowsForTable('doktori')],
            ['prefix' => 'klinike', 'rows' => $this->cityRowsForTable('klinike')],
            ['prefix' => 'laboratorije', 'rows' => $this->cityRowsForTable('laboratorije')],
            ['prefix' => 'banje', 'rows' => $this->cityRowsForTable('banje')],
            ['prefix' => 'domovi-njega', 'rows' => $this->cityRowsForTable('domovi_njega')],
        ];

        foreach ($cityVerticalRoutes as $route) {
            foreach ($route['rows'] as $row) {
                $citySlug = $this->resolveCitySlug((string) $row->grad, $citySlugMap);
                if ($citySlug === '') {
                    continue;
                }

                $path = $route['prefix'] . '/' . $citySlug;
                if (isset($added[$path])) {
                    continue;
                }
                $added[$path] = true;

                $this->appendUrl(
                    $xml,
                    $baseUrl . '/' . $path,
                    $row->updated_at,
                    'weekly',
                    '0.8'
                );
            }
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function laboratories()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $laboratories = DB::table('laboratorije')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($laboratories as $lab) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/laboratorija/' . $lab->slug,
                $lab->updated_at,
                'weekly',
                '0.7'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function spas()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $spas = DB::table('banje')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($spas as $spa) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/banja/' . $spa->slug,
                $spa->updated_at,
                'monthly',
                '0.7'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function careHomes()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $homes = DB::table('domovi_njega')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($homes as $home) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/dom-njega/' . $home->slug,
                $home->updated_at,
                'monthly',
                '0.7'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function doctorCitySpecialties()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $citySlugMap = $this->citySlugMap();
        $specialtySlugMap = $this->specialtySlugMap();
        $seen = [];

        $pairs = DB::table('doktori')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->whereNotNull('grad')
            ->where('grad', '!=', '')
            ->whereNotNull('specijalnost')
            ->where('specijalnost', '!=', '')
            ->selectRaw('grad, specijalnost, MAX(updated_at) AS updated_at')
            ->groupBy('grad', 'specijalnost')
            ->get();

        foreach ($pairs as $pair) {
            $citySlug = $this->resolveCitySlug((string) $pair->grad, $citySlugMap);
            $specialtySlug = $this->resolveSpecialtySlug((string) $pair->specijalnost, $specialtySlugMap);

            if ($citySlug === '' || $specialtySlug === '') {
                continue;
            }

            $path = '/doktori/' . $citySlug . '/' . $specialtySlug;
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;

            $this->appendUrl(
                $xml,
                $baseUrl . $path,
                $pair->updated_at,
                'weekly',
                '0.88'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function blog()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        // Use BlogPost model with published scope
        $posts = BlogPost::published()
            ->select('slug', 'updated_at', 'published_at', 'thumbnail', 'naslov', 'excerpt')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($posts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . $this->xmlEscape($baseUrl . '/blog/' . $post->slug) . '</loc>';

            $xml .= '<lastmod>' . $this->xmlDate($post->updated_at ?? $post->published_at) . '</lastmod>';

            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.6</priority>';

            if ($post->thumbnail) {
                $imageUrl = $post->thumbnail;
                if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = config('app.url') . '/storage/' . ltrim($imageUrl, '/');
                }

                $xml .= '<image:image>';
                $xml .= '<image:loc>' . $this->xmlEscape($imageUrl) . '</image:loc>';
                $xml .= '<image:title>' . $this->xmlEscape($post->naslov) . '</image:title>';
                if ($post->excerpt) {
                    $xml .= '<image:caption>' . $this->xmlEscape(strip_tags($post->excerpt)) . '</image:caption>';
                }
                $xml .= '</image:image>';
            }

            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function questions()
    {
        $baseUrl = $this->getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $questions = DB::table('pitanja')
            ->where('je_javno', true)
            ->select('slug', 'updated_at', 'created_at', 'je_odgovoreno')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($questions as $question) {
            $this->appendUrl(
                $xml,
                $baseUrl . '/pitanja/' . $question->slug,
                $question->updated_at ?? $question->created_at,
                'weekly',
                $question->je_odgovoreno ? '0.7' : '0.6'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
