<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    public function index(Request $request)
    {
        $normalizedUrl = $this->normalizeListingQueryUrl($request);
        if ($normalizedUrl) {
            return redirect($normalizedUrl, 301);
        }

        $path = trim($request->path(), '/');
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

    private function getMetaTagsForPath(string $path): array
    {
        if ($path === '' || $path === 'index.php') {
            return $this->getDefaultMeta();
        }

        if ($path === 'gradovi') {
            return $this->getCitiesListingMeta();
        }

        if ($path === 'blog') {
            return $this->getBlogListingMeta();
        }

        if ($path === 'medicinski-kalendar') {
            return $this->getMedicalCalendarMeta();
        }

        if ($path === 'pitanja') {
            return $this->getQuestionsListingMeta();
        }
        if ($path === 'doktori/lista') {
            return $this->getNoindexMeta(
                'Kompaktna lista doktora | wizMedik',
                'Pomocna stranica za pregled doktora.',
                'doktori/lista'
            );
        }
        if ($path === 'banje/indikacije-terapije') {
            return $this->getSpaGuideMeta();
        }
        if ($path === 'domovi-njega/vodic') {
            return $this->getCareHomesGuideMeta();
        }

        // Listing routes (critical for SSR SEO fallback)
        if ($path === 'doktori') {
            return $this->getDoctorsListingMeta();
        }
        if (preg_match('/^doktori\/specijalnost\/([^\/]+)$/', $path, $matches)) {
            return $this->getDoctorsListingMeta(null, $matches[1]);
        }
        if (preg_match('/^doktori\/([^\/]+)\/([^\/]+)$/', $path, $matches)) {
            return $this->getDoctorsListingMeta($matches[1], $matches[2]);
        }
        if (preg_match('/^doktori\/([^\/]+)$/', $path, $matches)) {
            return $this->getDoctorsListingMeta($matches[1]);
        }

        if ($path === 'klinike') {
            return $this->getClinicsListingMeta();
        }
        if (preg_match('/^klinike\/specijalnost\/([^\/]+)$/', $path, $matches)) {
            return $this->getClinicsListingMeta(null, $matches[1]);
        }
        if (preg_match('/^klinike\/([^\/]+)$/', $path, $matches)) {
            return $this->getClinicsListingMeta($matches[1]);
        }

        if ($path === 'laboratorije') {
            return $this->getLaboratoriesListingMeta();
        }
        if (preg_match('/^laboratorije\/([^\/]+)$/', $path, $matches)) {
            return $this->getLaboratoriesListingMeta($matches[1]);
        }

        if ($path === 'banje') {
            return $this->getSpasListingMeta();
        }
        if (preg_match('/^banje\/([^\/]+)$/', $path, $matches)) {
            return $this->getSpasListingMeta($matches[1]);
        }

        if ($path === 'domovi-njega') {
            return $this->getCareHomesListingMeta();
        }
        if (preg_match('/^domovi-njega\/([^\/]+)$/', $path, $matches)) {
            return $this->getCareHomesListingMeta($matches[1]);
        }

        // Profile routes
        if (preg_match('/^doktor\/([^\/]+)$/', $path, $matches)) {
            return $this->getDoctorMeta($matches[1]);
        }
        if (preg_match('/^klinika\/([^\/]+)$/', $path, $matches)) {
            return $this->getClinicMeta($matches[1]);
        }
        if (preg_match('/^laboratorija\/([^\/]+)$/', $path, $matches)) {
            return $this->getLaboratoryMeta($matches[1]);
        }
        if (preg_match('/^banja\/([^\/]+)$/', $path, $matches)) {
            return $this->getSpaMeta($matches[1]);
        }
        if (preg_match('/^dom-njega\/([^\/]+)$/', $path, $matches)) {
            return $this->getCareHomeMeta($matches[1]);
        }

        // Other SEO-enabled pages
        if (preg_match('/^specijalnost\/([^\/]+)$/', $path, $matches)) {
            return $this->getSpecialtyMeta($matches[1]);
        }
        if (preg_match('/^grad\/([^\/]+)$/', $path, $matches)) {
            return $this->getCityMeta($matches[1]);
        }
        if (preg_match('/^blog\/([^\/]+)$/', $path, $matches)) {
            return $this->getBlogMeta($matches[1]);
        }
        if (preg_match('/^pitanja\/([^\/]+)$/', $path, $matches)) {
            return $this->getQuestionMeta($matches[1]);
        }
        if ($path === 'postavi-pitanje') {
            return $this->getAskQuestionMeta();
        }

        return $this->getDefaultMeta();
    }

    private function getDoctorsListingMeta(?string $citySlug = null, ?string $specialtySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;
        $specialty = $specialtySlug ? $this->resolveSpecialtyNameBySlug($specialtySlug) : null;

        $query = DB::table('doktori')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);
        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }
        if ($specialty) {
            $query->whereRaw('LOWER(specijalnost) = ?', [mb_strtolower($specialty)]);
        }

        $count = (clone $query)->count();
        $locationPart = $city ? " u {$city}" : ' u Bosni i Hercegovini';
        $specialtyPart = $specialty ? "{$specialty} " : '';

        $title = "{$specialtyPart}doktori{$locationPart} | wizMedik";
        $description = "Pronadite {$specialtyPart}doktore{$locationPart}. Dostupno {$count}+ profila sa online zakazivanjem termina i kontakt informacijama.";

        $path = 'doktori';
        if ($citySlug && $specialtySlug) {
            $path .= "/{$citySlug}/{$specialtySlug}";
        } elseif ($specialtySlug) {
            $path .= "/specijalnost/{$specialtySlug}";
        } elseif ($citySlug) {
            $path .= "/{$citySlug}";
        }

        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getClinicsListingMeta(?string $citySlug = null, ?string $specialtySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;
        $specialty = $specialtySlug ? $this->resolveSpecialtyNameBySlug($specialtySlug) : null;

        $query = DB::table('klinike')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);
        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }
        $count = (clone $query)->count();

        $locationPart = $city ? " u {$city}" : ' u Bosni i Hercegovini';
        $specialtyPart = $specialty ? " za {$specialty}" : '';

        $title = "Klinike{$specialtyPart}{$locationPart} | wizMedik";
        $description = "Pregledajte privatne i specijalisticke klinike{$locationPart}{$specialtyPart}. Ukupno {$count}+ klinika sa detaljnim profilima i kontakt podacima.";

        $path = 'klinike';
        if ($specialtySlug) {
            $path .= "/specijalnost/{$specialtySlug}";
        } elseif ($citySlug) {
            $path .= "/{$citySlug}";
        }

        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getLaboratoriesListingMeta(?string $citySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;

        $query = DB::table('laboratorije')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);
        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }
        $count = (clone $query)->count();

        $locationPart = $city ? " u {$city}" : ' u Bosni i Hercegovini';
        $title = "Laboratorije{$locationPart} | wizMedik";
        $description = "Pronadite medicinske laboratorije{$locationPart}. Uporedite analize, cijene, radno vrijeme i kontakt podatke za {$count}+ laboratorija.";

        $path = $citySlug ? "laboratorije/{$citySlug}" : 'laboratorije';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getSpasListingMeta(?string $citySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;

        $query = DB::table('banje')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);
        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }
        $count = (clone $query)->count();

        $locationPart = $city ? " u {$city}" : ' u Bosni i Hercegovini';
        $title = "Banje{$locationPart} | wizMedik";
        $description = "Pregledajte banje i rehabilitacione centre{$locationPart}. Dostupno {$count}+ profila sa terapijama, smjestajem i kontakt informacijama.";

        $path = $citySlug ? "banje/{$citySlug}" : 'banje';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getCareHomesListingMeta(?string $citySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;

        $query = DB::table('domovi_njega')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);
        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }
        $count = (clone $query)->count();

        $locationPart = $city ? " u {$city}" : ' u Bosni i Hercegovini';
        $title = "Domovi za njegu{$locationPart} | wizMedik";
        $description = "Uporedite domove za njegu i staracke domove{$locationPart}. Pregled {$count}+ verifikovanih profila sa uslugama i kontakt podacima.";

        $path = $citySlug ? "domovi-njega/{$citySlug}" : 'domovi-njega';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getCitiesListingMeta(): array
    {
        $count = DB::table('gradovi')
            ->where('aktivan', true)
            ->count();

        $title = 'Gradovi BiH - doktori, klinike, laboratorije, banje i domovi | wizMedik';
        $description = "Pregledajte {$count}+ gradova u Bosni i Hercegovini i pronadite doktore, klinike, laboratorije, banje i domove za njegu po gradu.";
        $url = $this->buildUrl('gradovi');
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getBlogListingMeta(): array
    {
        $count = DB::table('blog_posts')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();

        $title = 'Zdravstveni savjeti i blog | wizMedik';
        $description = "Procitajte {$count}+ strucnih blog postova i zdravstvenih savjeta od doktora na wizMedik platformi.";
        $url = $this->buildUrl('blog');
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getQuestionsListingMeta(): array
    {
        $count = DB::table('pitanja')
            ->where('je_javno', true)
            ->count();

        $title = 'Medicinska pitanja i odgovori | wizMedik';
        $description = "Procitajte {$count}+ javnih medicinskih pitanja i odgovora verifikovanih doktora na wizMedik platformi.";
        $url = $this->buildUrl('pitanja');
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getMedicalCalendarMeta(): array
    {
        $year = (int) now()->format('Y');

        $countForCurrentYear = DB::table('medical_calendar')
            ->where('is_active', true)
            ->whereYear('date', $year)
            ->count();

        // Keep a stable 2026 fallback if current year has not been populated yet.
        $targetYear = $countForCurrentYear > 0 ? $year : 2026;
        $eventsCount = DB::table('medical_calendar')
            ->where('is_active', true)
            ->whereYear('date', $targetYear)
            ->count();

        $title = "Medicinski kalendar {$targetYear} - Kalendar zdravlja {$targetYear} | wizMedik";
        $description = "Kompletan kalendar zdravlja {$targetYear} sa svjetskim danima zdravlja, kampanjama i edukativnim datumima. Dostupno {$eventsCount}+ zdravstvenih datuma.";
        $url = $this->buildUrl('medicinski-kalendar');
        $schema = $this->buildCollectionSchema($title, $description, $url, $eventsCount);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', $schema),
        ];
    }

    private function getSpaGuideMeta(): array
    {
        $title = 'Indikacije i terapije u banjama | wizMedik';
        $description = 'Vodic kroz indikacije i terapije dostupne u banjama u Bosni i Hercegovini.';
        $url = $this->buildUrl('banje/indikacije-terapije');

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'article'),
        ];
    }

    private function getCareHomesGuideMeta(): array
    {
        $title = 'Vodic za domove njege | wizMedik';
        $description = 'Detaljan vodic kroz tipove domova njege, nivoe njege i usluge.';
        $url = $this->buildUrl('domovi-njega/vodic');

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'article'),
        ];
    }

    private function getDoctorMeta(string $slug): array
    {
        $doctor = DB::table('doktori')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->first();

        if (!$doctor) {
            return $this->getDefaultMeta();
        }

        $title = "Dr. {$doctor->ime} {$doctor->prezime} - {$doctor->specijalnost} | wizMedik";
        $description = "Zakažite pregled kod Dr. {$doctor->ime} {$doctor->prezime}, {$doctor->specijalnost} u {$doctor->grad}. Online zakazivanje termina.";
        $image = $this->absoluteImage($doctor->slika_profila ?? null);
        $url = $this->buildUrl("doktor/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'profile'),
        ];
    }

    private function getClinicMeta(string $slug): array
    {
        $clinic = DB::table('klinike')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->first();

        if (!$clinic) {
            return $this->getDefaultMeta();
        }

        $title = "{$clinic->naziv} - Klinika u {$clinic->grad} | wizMedik";
        $description = $this->cleanDescription(
            $clinic->opis ?? null,
            "Zakažite pregled u {$clinic->naziv}, {$clinic->grad}. Online zakazivanje termina."
        );
        $image = $this->defaultImage();
        $url = $this->buildUrl("klinika/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getSpecialtyMeta(string $slug): array
    {
        $specialty = DB::table('specijalnosti')
            ->where('slug', $slug)
            ->first();

        if (!$specialty) {
            return $this->getDefaultMeta();
        }

        $doctorCount = DB::table('doktori')
            ->whereRaw('LOWER(specijalnost) = ?', [mb_strtolower($specialty->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $title = "{$specialty->naziv} - Pronadite doktora | wizMedik";
        $description = $specialty->seo_opis
            ?? "Pronadite najboljeg doktora za {$specialty->naziv} u BiH. Dostupno {$doctorCount}+ doktora za online zakazivanje.";
        $image = $this->absoluteImage($specialty->slika_url ?? null);
        $url = $this->buildUrl("specijalnost/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getCityMeta(string $slug): array
    {
        $city = DB::table('gradovi')
            ->where('slug', $slug)
            ->first();

        if (!$city) {
            return $this->getDefaultMeta();
        }

        $doctorCount = DB::table('doktori')
            ->whereRaw('LOWER(grad) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $clinicCount = DB::table('klinike')
            ->whereRaw('LOWER(grad) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $labCount = DB::table('laboratorije')
            ->whereRaw('LOWER(grad) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $spaCount = DB::table('banje')
            ->whereRaw('LOWER(grad) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $careHomeCount = DB::table('domovi_njega')
            ->whereRaw('LOWER(grad) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $title = "Zdravstvene usluge u {$city->naziv} | wizMedik";
        $description = "U {$city->naziv} pronađite {$doctorCount}+ doktora, {$clinicCount}+ klinika, {$labCount}+ laboratorija, {$spaCount}+ banja i {$careHomeCount}+ domova za njegu.";
        $image = $this->defaultImage();
        $url = $this->buildUrl("grad/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getLaboratoryMeta(string $slug): array
    {
        $lab = DB::table('laboratorije')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->first();

        if (!$lab) {
            return $this->getDefaultMeta();
        }

        $title = "{$lab->naziv} - Laboratorija u {$lab->grad} | wizMedik";
        $description = $this->cleanDescription(
            $lab->opis ?? null,
            "Laboratorijske analize u {$lab->grad}. Provjerite cijene i kontakt podatke."
        );
        $image = $this->defaultImage();
        $url = $this->buildUrl("laboratorija/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getSpaMeta(string $slug): array
    {
        $spa = DB::table('banje')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->first();

        if (!$spa) {
            return $this->getDefaultMeta();
        }

        $title = "{$spa->naziv} - Banja u {$spa->grad} | wizMedik";
        $description = $this->cleanDescription(
            $spa->opis ?? null,
            "Banjsko lijeciliste {$spa->naziv} u {$spa->grad}. Provjerite ponudu i kontakt."
        );
        $image = $this->defaultImage();
        $url = $this->buildUrl("banja/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getCareHomeMeta(string $slug): array
    {
        $home = DB::table('domovi_njega')
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->first();

        if (!$home) {
            return $this->getDefaultMeta();
        }

        $title = "{$home->naziv} - Dom njege u {$home->grad} | wizMedik";
        $description = $this->cleanDescription(
            $home->opis ?? null,
            "Dom za njegu starih i nemocnih osoba {$home->naziv} u {$home->grad}."
        );
        $image = $this->defaultImage();
        $url = $this->buildUrl("dom-njega/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getBlogMeta(string $slug): array
    {
        $post = DB::table('blog_posts')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();

        if (!$post) {
            return $this->getDefaultMeta();
        }

        $title = "{$post->naslov} | wizMedik Blog";
        $description = $this->cleanDescription(
            $post->excerpt ?? strip_tags($post->sadrzaj ?? ''),
            'Strucni zdravstveni savjeti na wizMedik blogu.'
        );
        $image = $this->absoluteImage($post->thumbnail ?? null);
        $url = $this->buildUrl("blog/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'article'),
        ];
    }

    private function getQuestionMeta(string $slug): array
    {
        $question = DB::table('pitanja')
            ->leftJoin('specijalnosti', 'specijalnosti.id', '=', 'pitanja.specijalnost_id')
            ->select(
                'pitanja.id',
                'pitanja.slug',
                'pitanja.naslov',
                'pitanja.sadrzaj',
                'pitanja.ime_korisnika',
                'pitanja.updated_at',
                'pitanja.created_at',
                'pitanja.je_javno',
                'specijalnosti.naziv as specijalnost_naziv'
            )
            ->where('pitanja.slug', $slug)
            ->where('pitanja.je_javno', true)
            ->first();

        if (!$question) {
            return $this->getNoindexMeta(
                'Pitanje nije pronadjeno | wizMedik',
                'Trazeno pitanje nije dostupno.',
                "pitanja/{$slug}"
            );
        }

        $url = $this->buildUrl("pitanja/{$slug}");
        $title = "{$question->naslov} | Medicinska pitanja | wizMedik";
        $description = $this->cleanDescription(
            $question->sadrzaj ?? null,
            'Javno medicinsko pitanje i odgovori doktora na wizMedik platformi.'
        );

        $answers = DB::table('odgovori_na_pitanja')
            ->leftJoin('doktori', 'doktori.id', '=', 'odgovori_na_pitanja.doktor_id')
            ->leftJoin('users', 'users.id', '=', 'doktori.user_id')
            ->select(
                'odgovori_na_pitanja.id',
                'odgovori_na_pitanja.sadrzaj',
                'odgovori_na_pitanja.je_prihvacen',
                'odgovori_na_pitanja.updated_at',
                'odgovori_na_pitanja.created_at',
                'doktori.slug as doktor_slug',
                'users.ime as doktor_ime',
                'users.prezime as doktor_prezime'
            )
            ->where('pitanje_id', $question->id)
            ->orderByDesc('odgovori_na_pitanja.je_prihvacen')
            ->orderByDesc('odgovori_na_pitanja.broj_lajkova')
            ->orderBy('odgovori_na_pitanja.created_at')
            ->limit(5)
            ->get();

        $acceptedAnswer = $answers->firstWhere('je_prihvacen', true);
        $suggestedAnswers = $answers->map(function ($answer) use ($question) {
            $answerUrl = $this->buildUrl("pitanja/{$question->slug}#odgovor-{$answer->id}");
            $doctorName = trim((string) ($answer->doktor_ime ?? '') . ' ' . (string) ($answer->doktor_prezime ?? ''));
            $doctorUrl = $answer->doktor_slug
                ? $this->buildUrl("doktor/{$answer->doktor_slug}")
                : $this->buildUrl('doktori');

            return [
                '@type' => 'Answer',
                'text' => trim(strip_tags((string) $answer->sadrzaj)),
                'dateCreated' => $this->toIsoDate($answer->created_at),
                'dateModified' => $this->toIsoDate($answer->updated_at ?? $answer->created_at),
                'url' => $answerUrl,
                'author' => [
                    '@type' => 'Person',
                    'name' => $doctorName !== '' ? $doctorName : 'Doktor wizMedik',
                    'url' => $doctorUrl,
                ],
            ];
        })->values()->all();

        $mainEntity = [
            '@type' => 'Question',
            'name' => $question->naslov,
            'text' => trim(strip_tags((string) $question->sadrzaj)),
            'dateCreated' => $this->toIsoDate($question->created_at),
            'dateModified' => $this->toIsoDate($question->updated_at ?? $question->created_at),
            'url' => $url,
            'about' => $question->specijalnost_naziv,
            'author' => [
                '@type' => 'Person',
                'name' => trim((string) $question->ime_korisnika) !== '' ? trim((string) $question->ime_korisnika) : 'Anonimni korisnik',
                'url' => $url . '#question-author',
            ],
            'suggestedAnswer' => $suggestedAnswers,
        ];

        if ($acceptedAnswer) {
            $acceptedDoctorName = trim((string) ($acceptedAnswer->doktor_ime ?? '') . ' ' . (string) ($acceptedAnswer->doktor_prezime ?? ''));
            $acceptedDoctorUrl = $acceptedAnswer->doktor_slug
                ? $this->buildUrl("doktor/{$acceptedAnswer->doktor_slug}")
                : $this->buildUrl('doktori');
            $acceptedAnswerUrl = $this->buildUrl("pitanja/{$question->slug}#odgovor-{$acceptedAnswer->id}");

            $mainEntity['acceptedAnswer'] = [
                '@type' => 'Answer',
                'text' => trim(strip_tags((string) $acceptedAnswer->sadrzaj)),
                'dateCreated' => $this->toIsoDate($acceptedAnswer->created_at),
                'dateModified' => $this->toIsoDate($acceptedAnswer->updated_at ?? $acceptedAnswer->created_at),
                'url' => $acceptedAnswerUrl,
                'author' => [
                    '@type' => 'Person',
                    'name' => $acceptedDoctorName !== '' ? $acceptedDoctorName : 'Doktor wizMedik',
                    'url' => $acceptedDoctorUrl,
                ],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'QAPage',
            'mainEntity' => $mainEntity,
        ];

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'article', $schema),
        ];
    }

    private function getAskQuestionMeta(): array
    {
        $title = 'Postavi medicinsko pitanje | wizMedik';
        $description = 'Postavite medicinsko pitanje i dobijte odgovor verifikovanih doktora.';
        $url = $this->buildUrl('postavi-pitanje');

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', null, 'noindex, follow'),
        ];
    }

    private function getDefaultMeta(): array
    {
        $title = 'wizMedik - Pronadite doktore, klinike, laboratorije, banje i domove njege';
        $description = 'Vodeca platforma za pronalazenje zdravstvenih usluga i online zakazivanje termina u Bosni i Hercegovini.';
        $image = $this->defaultImage();
        $url = $this->buildUrl('/');

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getNoindexMeta(string $title, string $description, string $path): array
    {
        $url = $this->buildUrl($path);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', null, 'noindex, nofollow'),
        ];
    }

    private function buildMetaTags(
        string $title,
        string $description,
        string $image,
        string $url,
        string $type = 'website',
        ?array $structuredData = null,
        string $robots = 'index, follow'
    ): string {
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $escapedDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $escapedImage = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $escapedRobots = htmlspecialchars($robots, ENT_QUOTES, 'UTF-8');

        $jsonLd = '';
        if ($structuredData) {
            $encoded = json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $jsonLd = "\n    <script type=\"application/ld+json\">{$encoded}</script>";
            }
        }

        return <<<HTML
    <!-- SEO Meta Tags -->
    <meta name="description" content="{$escapedDescription}">
    <meta name="robots" content="{$escapedRobots}">
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
    <meta name="twitter:image" content="{$escapedImage}">{$jsonLd}
HTML;
    }

    private function buildCollectionSchema(string $title, string $description, string $url, int $count): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $count,
            ],
        ];
    }

    private function getBaseUrl(): string
    {
        return rtrim(config('app.frontend_url', 'https://wizmedik.com'), '/');
    }

    private function buildUrl(string $path): string
    {
        if ($path === '/' || $path === '') {
            return $this->getBaseUrl() . '/';
        }

        return $this->getBaseUrl() . '/' . ltrim($path, '/');
    }

    private function defaultImage(): string
    {
        return $this->buildUrl('/wizmedik-logo.png');
    }

    private function absoluteImage(?string $image): string
    {
        if (!$image) {
            return $this->defaultImage();
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return $this->buildUrl($image);
    }

    private function cleanDescription(?string $description, string $fallback): string
    {
        $clean = trim(strip_tags((string) $description));
        if ($clean === '') {
            return $fallback;
        }

        return mb_substr($clean, 0, 160);
    }

    private function toIsoDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if (empty($value)) {
            return now()->format('c');
        }

        return date('c', strtotime((string) $value));
    }

    private function decodeSegment(string $value): string
    {
        $decoded = urldecode(str_replace('+', ' ', $value));
        $decoded = trim(str_replace('-', ' ', $decoded));

        return mb_convert_case($decoded, MB_CASE_TITLE, 'UTF-8');
    }

    private function resolveSpecialtyNameBySlug(string $slug): string
    {
        $specialty = DB::table('specijalnosti')
            ->where('slug', $slug)
            ->first();

        if ($specialty && !empty($specialty->naziv)) {
            return $specialty->naziv;
        }

        return $this->decodeSegment($slug);
    }

    private function resolveCityNameBySlug(string $slug): string
    {
        $city = DB::table('gradovi')
            ->where('slug', $slug)
            ->first();

        if ($city && !empty($city->naziv)) {
            return $city->naziv;
        }

        return $this->decodeSegment($slug);
    }

    private function normalizeListingQueryUrl(Request $request): ?string
    {
        $path = trim($request->path(), '/');
        $query = $request->query();

        if (!in_array($path, ['doktori', 'klinike', 'laboratorije', 'banje', 'domovi-njega'], true)) {
            return null;
        }

        $city = $request->query('grad');
        $specialty = $request->query('specijalnost');
        $search = $request->query('pretraga');

        if ($path === 'doktori') {
            if (!empty($search)) {
                return null;
            }

            $citySlug = $city ? $this->queryValueToSlug($city) : null;
            $specialtySlug = $specialty ? $this->queryValueToSlug($specialty) : null;

            if ($citySlug && $specialtySlug) {
                return $this->buildUrl("doktori/{$citySlug}/{$specialtySlug}");
            }
            if ($citySlug) {
                return $this->buildUrl("doktori/{$citySlug}");
            }
            if ($specialtySlug) {
                return $this->buildUrl("doktori/specijalnost/{$specialtySlug}");
            }
            return null;
        }

        if ($path === 'klinike') {
            $citySlug = $city ? $this->queryValueToSlug($city) : null;
            $specialtySlug = $specialty ? $this->queryValueToSlug($specialty) : null;

            if ($specialtySlug && count($query) === 1) {
                return $this->buildUrl("klinike/specijalnost/{$specialtySlug}");
            }
            if ($citySlug && count($query) === 1) {
                return $this->buildUrl("klinike/{$citySlug}");
            }
            return null;
        }

        if (in_array($path, ['laboratorije', 'banje', 'domovi-njega'], true)) {
            $citySlug = $city ? $this->queryValueToSlug($city) : null;
            if ($citySlug && count($query) === 1) {
                return $this->buildUrl("{$path}/{$citySlug}");
            }
        }

        return null;
    }

    private function queryValueToSlug(string $value): string
    {
        $decoded = urldecode(str_replace('+', ' ', $value));
        $slug = Str::slug($decoded);

        return $slug !== '' ? $slug : rawurlencode(trim($decoded));
    }
}
