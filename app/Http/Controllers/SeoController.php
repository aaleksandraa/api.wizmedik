<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoController extends Controller
{
    /**
     * Serve index.html with dynamic meta tags
     */
    public function index(Request $request)
    {
        $path = $request->path();
        $metaTags = $this->getMetaTagsForPath($path);

        // Try multiple paths for index.html
        $indexPath = base_path('../frontend/dist/index.html');
        if (!file_exists($indexPath)) {
            $indexPath = base_path('frontend/dist/index.html');
        }
        if (!file_exists($indexPath)) {
            $indexPath = base_path('../dist/index.html');
        }
        if (!file_exists($indexPath)) {
            $indexPath = public_path('index.html');
        }

        if (!file_exists($indexPath)) {
            return response('Index file not found at: ' . $indexPath, 404);
        }

        $html = file_get_contents($indexPath);

        // Inject meta tags before </head>
        $html = str_replace(
            '</head>',
            $metaTags['meta'] . "\n</head>",
            $html
        );

        // Replace title
        $html = preg_replace(
            '/<title>.*?<\/title>/',
            $metaTags['title'],
            $html
        );

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Get meta tags based on route
     */
    private function getMetaTagsForPath($path)
    {
        // Doctor profile
        if (preg_match('/^doktor\/(.+)$/', $path, $matches)) {
            return $this->getDoctorMeta($matches[1]);
        }

        // Clinic profile
        if (preg_match('/^klinika\/(.+)$/', $path, $matches)) {
            return $this->getClinicMeta($matches[1]);
        }

        // Specialty page
        if (preg_match('/^specijalnost\/(.+)$/', $path, $matches)) {
            return $this->getSpecialtyMeta($matches[1]);
        }

        // City page
        if (preg_match('/^grad\/(.+)$/', $path, $matches)) {
            return $this->getCityMeta($matches[1]);
        }

        // Laboratory profile
        if (preg_match('/^laboratorija\/(.+)$/', $path, $matches)) {
            return $this->getLaboratoryMeta($matches[1]);
        }

        // Spa profile
        if (preg_match('/^banja\/(.+)$/', $path, $matches)) {
            return $this->getSpaMeta($matches[1]);
        }

        // Care home profile
        if (preg_match('/^dom-njega\/(.+)$/', $path, $matches)) {
            return $this->getCareHomeMeta($matches[1]);
        }

        // Blog post
        if (preg_match('/^blog\/(.+)$/', $path, $matches)) {
            return $this->getBlogMeta($matches[1]);
        }

        // Default homepage
        return $this->getDefaultMeta();
    }

    /**
     * Doctor meta tags
     */
    private function getDoctorMeta($slug)
    {
        $doctor = DB::table('doktori')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$doctor) {
            return $this->getDefaultMeta();
        }

        $title = "Dr. {$doctor->ime} {$doctor->prezime} - {$doctor->specijalnost} | wizMedik";
        $description = "Zakažite pregled kod Dr. {$doctor->ime} {$doctor->prezime}, {$doctor->specijalnost} u {$doctor->grad}. Online zakazivanje termina.";
        $image = $doctor->slika_profila ?? 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/doktor/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'profile')
        ];
    }

    /**
     * Clinic meta tags
     */
    private function getClinicMeta($slug)
    {
        $clinic = DB::table('klinike')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$clinic) {
            return $this->getDefaultMeta();
        }

        $title = "{$clinic->naziv} - Klinika u {$clinic->grad} | wizMedik";
        $description = $clinic->opis ? substr(strip_tags($clinic->opis), 0, 160) : "Zakažite pregled u {$clinic->naziv}, {$clinic->grad}. Online zakazivanje termina.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/klinika/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Specialty meta tags
     */
    private function getSpecialtyMeta($slug)
    {
        $specialty = DB::table('specijalnosti')
            ->where('slug', $slug)
            ->first();

        if (!$specialty) {
            return $this->getDefaultMeta();
        }

        $doctorCount = DB::table('doktori')
            ->where('specijalnost', $specialty->naziv)
            ->whereNull('deleted_at')
            ->count();

        $title = "{$specialty->naziv} - Pronađite doktora | wizMedik";
        $description = $specialty->seo_opis ?? "Pronađite najboljeg doktora za {$specialty->naziv} u BiH. {$doctorCount}+ doktora dostupno za online zakazivanje.";
        $image = $specialty->slika_url ?? 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/specijalnost/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * City meta tags
     */
    private function getCityMeta($slug)
    {
        $city = DB::table('gradovi')
            ->where('slug', $slug)
            ->first();

        if (!$city) {
            return $this->getDefaultMeta();
        }

        $doctorCount = DB::table('doktori')
            ->where('grad', $city->naziv)
            ->whereNull('deleted_at')
            ->count();

        $title = "Doktori u {$city->naziv} - Online zakazivanje | wizMedik";
        $description = "Pronađite i zakažite pregled kod doktora u {$city->naziv}. {$doctorCount}+ doktora dostupno za online zakazivanje termina.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/grad/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Laboratory meta tags
     */
    private function getLaboratoryMeta($slug)
    {
        $lab = DB::table('laboratorije')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$lab) {
            return $this->getDefaultMeta();
        }

        $title = "{$lab->naziv} - Laboratorija u {$lab->grad} | wizMedik";
        $description = $lab->opis ? substr(strip_tags($lab->opis), 0, 160) : "Laboratorijske analize u {$lab->grad}. Provjerite cijene i zakažite termin online.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/laboratorija/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Spa meta tags
     */
    private function getSpaMeta($slug)
    {
        $spa = DB::table('banje')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$spa) {
            return $this->getDefaultMeta();
        }

        $title = "{$spa->naziv} - Banja u {$spa->grad} | wizMedik";
        $description = $spa->opis ? substr(strip_tags($spa->opis), 0, 160) : "Banjsko-klimatsko lječilište {$spa->naziv} u {$spa->grad}. Provjerite ponudu i cijene.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/banja/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Care home meta tags
     */
    private function getCareHomeMeta($slug)
    {
        $home = DB::table('domovi_njega')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$home) {
            return $this->getDefaultMeta();
        }

        $title = "{$home->naziv} - Dom njege u {$home->grad} | wizMedik";
        $description = $home->opis ? substr(strip_tags($home->opis), 0, 160) : "Dom za njegu starih i nemoćnih osoba {$home->naziv} u {$home->grad}. Provjerite usluge i cijene.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/dom-njega/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Blog meta tags
     */
    private function getBlogMeta($slug)
    {
        $post = DB::table('blog_postovi')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$post) {
            return $this->getDefaultMeta();
        }

        $title = "{$post->naslov} | wizMedik Blog";
        $description = $post->kratak_opis ?? substr(strip_tags($post->sadrzaj), 0, 160);
        $image = $post->slika_url ?? 'https://medibih.ba/og-image.jpg';
        $url = "https://medibih.ba/blog/{$slug}";

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'article')
        ];
    }

    /**
     * Default homepage meta tags
     */
    private function getDefaultMeta()
    {
        $title = "wizMedik - Pronađite doktore u Bosni i Hercegovini | Online zakazivanje";
        $description = "Vodeća platforma za pronalaženje doktora i online zakazivanje termina u BiH. 500+ doktora, 50+ specijalnosti u Sarajevu, Banja Luci, Tuzli i drugim gradovima.";
        $image = 'https://medibih.ba/og-image.jpg';
        $url = 'https://medibih.ba/';

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website')
        ];
    }

    /**
     * Build meta tags HTML
     */
    private function buildMetaTags($title, $description, $image, $url, $type = 'website')
    {
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $escapedDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $escapedImage = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
    <!-- SEO Meta Tags -->
    <meta name="description" content="{$escapedDescription}">
    <link rel="canonical" href="{$escapedUrl}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{$type}">
    <meta property="og:url" content="{$escapedUrl}">
    <meta property="og:title" content="{$escapedTitle}">
    <meta property="og:description" content="{$escapedDescription}">
    <meta property="og:image" content="{$escapedImage}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="bs_BA">
    <meta property="og:site_name" content="wizMedik">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{$escapedUrl}">
    <meta name="twitter:title" content="{$escapedTitle}">
    <meta name="twitter:description" content="{$escapedDescription}">
    <meta name="twitter:image" content="{$escapedImage}">
HTML;
    }
}
