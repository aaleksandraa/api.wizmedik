<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    public function index(Request $request)
    {
        $canonicalRedirect = $this->normalizeCanonicalRedirect($request);
        if ($canonicalRedirect) {
            return redirect($canonicalRedirect, 301);
        }

        $normalizedUrl = $this->normalizeListingQueryUrl($request);
        if ($normalizedUrl) {
            return redirect($normalizedUrl, 301);
        }

        $path = trim($request->path(), '/');
        $metaTags = $this->getMetaTagsForPath($path, $request);
        $statusCode = $metaTags['status'] ?? 200;

        [$indexPath, $checkedPaths] = $this->resolveIndexTemplatePath();
        if ($indexPath === null) {
            return response('Index file not found. Checked: ' . implode(' | ', $checkedPaths), 404);
        }

        $html = file_get_contents($indexPath);
        $html = $this->stripExistingSeoTags($html);

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

        return response($html, $statusCode)->header('Content-Type', 'text/html');
    }

    private function resolveIndexTemplatePath(): array
    {
        $sitemapOutputPath = trim((string) config('app.sitemap_output_path', ''));
        $runtimeTemplatePath = trim((string) config('app.seo_index_template_path', ''));
        $seoTemplatePath = trim((string) env('SEO_INDEX_TEMPLATE_PATH', ''));
        $mirrorRaw = trim((string) config('app.sitemap_output_mirror_paths', ''));

        $outputDirs = [];
        if ($sitemapOutputPath !== '') {
            $outputDirs[] = rtrim($sitemapOutputPath, DIRECTORY_SEPARATOR);
        }

        if ($mirrorRaw !== '') {
            $mirrorDirs = preg_split('/[,;]+/', $mirrorRaw) ?: [];
            foreach ($mirrorDirs as $mirrorDir) {
                $normalized = rtrim(trim((string) $mirrorDir), DIRECTORY_SEPARATOR);
                if ($normalized !== '') {
                    $outputDirs[] = $normalized;
                }
            }
        }

        $outputDirs = array_values(array_unique($outputDirs));
        $outputCandidates = [];
        foreach ($outputDirs as $dir) {
            // If output base is httpdocs, prefer the actual frontend docroot template (httpdocs/dist/index.html)
            $outputCandidates[] = $dir . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'index.html';
            $outputCandidates[] = $dir . DIRECTORY_SEPARATOR . 'index.html';
        }

        $candidates = [
            $runtimeTemplatePath !== '' ? $runtimeTemplatePath : null,
            ...$outputCandidates,
            $seoTemplatePath !== '' ? $seoTemplatePath : null,
            base_path('../frontend/dist/index.html'),
            base_path('frontend/dist/index.html'),
            base_path('../dist/index.html'),
            public_path('index.html'),
        ];

        $checkedPaths = [];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $normalized = trim($candidate);
            if ($normalized === '') {
                continue;
            }

            $checkedPaths[] = $normalized;

            if (is_file($normalized) && is_readable($normalized)) {
                return [$normalized, $checkedPaths];
            }
        }

        return [null, $checkedPaths];
    }

    private function getMetaTagsForPath(string $path, ?Request $request = null): array
    {
        if ($path === '' || $path === 'index.php') {
            return $this->getDefaultMeta();
        }

        $staticMeta = $this->getStaticMetaForPath($path);
        if ($staticMeta !== null) {
            return $staticMeta;
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
        if (preg_match('/^klinike\/([^\/]+)\/([^\/]+)$/', $path, $matches)) {
            return $this->getClinicsListingMeta($matches[1], $matches[2]);
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

        if ($path === 'apoteke') {
            return $this->getPharmaciesListingMeta(null, $request);
        }
        if (preg_match('/^apoteke\/([^\/]+)$/', $path, $matches)) {
            return $this->getPharmaciesListingMeta($matches[1], $request);
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

        if ($path === 'lijekovi') {
            return $this->getMedicinesListingMeta();
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
        if (preg_match('/^apoteka\/([^\/]+)$/', $path, $matches)) {
            return $this->getPharmacyMeta($matches[1]);
        }
        if (preg_match('/^banja\/([^\/]+)$/', $path, $matches)) {
            return $this->getSpaMeta($matches[1]);
        }
        if (preg_match('/^dom-njega\/([^\/]+)$/', $path, $matches)) {
            return $this->getCareHomeMeta($matches[1]);
        }
        if (preg_match('/^lijekovi\/([^\/]+)$/', $path, $matches)) {
            return $this->getMedicineMeta($matches[1]);
        }

        // Other SEO-enabled pages
        if (preg_match('/^specijalnost\/([^\/]+)\/([^\/]+)$/', $path, $matches)) {
            return $this->getSpecialtyServiceMeta($matches[1], $matches[2]);
        }
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

        return $this->getNotFoundMeta($path);
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
        $listingImage = (clone $query)
            ->whereNotNull('slika_profila')
            ->where('slika_profila', '!=', '')
            ->orderByDesc('id')
            ->value('slika_profila');
        $image = $this->resolveImageCandidates([$listingImage]);

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
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
        ];
    }

    private function getClinicsListingMeta(?string $citySlug = null, ?string $specialtySlug = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;
        $specialtyContext = $specialtySlug ? $this->resolveSpecialtyContextBySlug($specialtySlug) : null;
        $specialty = $specialtyContext['name'] ?? null;
        $specialtyIds = $specialtyContext['ids'] ?? [];

        $query = DB::table('klinike')
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true);

        if ($city) {
            $query->whereRaw('LOWER(grad) = ?', [mb_strtolower($city)]);
        }

        if ($specialty) {
            $this->applyClinicSpecialtyFilter($query, $specialtyIds, $specialty);
        }

        $count = (clone $query)->count();

        if ($city && $specialty) {
            $title = "Klinike za {$specialty} - {$city} | wizMedik";
            $description = "Pronadjite klinike za {$specialty} u {$city}. Dostupno {$count}+ klinika sa profilima, uslugama, doktorima i kontakt podacima.";
        } elseif ($specialty) {
            $title = "Klinike za {$specialty} | wizMedik";
            $description = "Pronadjite klinike za {$specialty} u Bosni i Hercegovini. Dostupno {$count}+ klinika sa profilima, uslugama i kontakt informacijama.";
        } elseif ($city) {
            $title = "Klinike - {$city} | wizMedik";
            $description = "Pregledajte privatne i specijalisticke klinike u {$city}. Dostupno {$count}+ klinika sa detaljnim profilima, uslugama i kontakt podacima.";
        } else {
            $title = "Klinike | wizMedik";
            $description = "Pregledajte privatne i specijalisticke klinike u Bosni i Hercegovini. Dostupno {$count}+ klinika sa detaljnim profilima i kontakt podacima.";
        }

        $listingImage = (clone $query)
            ->whereNotNull('slike')
            ->orderByDesc('id')
            ->value('slike');
        $image = $this->resolveImageCandidates([
            $this->firstImageFromJson($listingImage),
        ]);

        $path = 'klinike';
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
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
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
        $listingLab = (clone $query)
            ->where(function ($inner) {
                $inner->whereNotNull('featured_slika')
                    ->where('featured_slika', '!=', '')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('profilna_slika')
                            ->where('profilna_slika', '!=', '');
                    });
            })
            ->orderByDesc('id')
            ->first(['featured_slika', 'profilna_slika']);
        $image = $this->resolveImageCandidates([
            $listingLab->featured_slika ?? null,
            $listingLab->profilna_slika ?? null,
        ]);

        $path = $citySlug ? "laboratorije/{$citySlug}" : 'laboratorije';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
        ];
    }

    private function getPharmaciesListingMeta(?string $citySlug = null, ?Request $request = null): array
    {
        $city = $citySlug ? $this->resolveCityNameBySlug($citySlug) : null;
        $seoCity = $citySlug ? $this->decodeSegment($citySlug) : null;
        $dutyNow = $request?->boolean('dezurna_now') ?? false;
        $openNow = $request?->boolean('open_now') ?? false;
        $is24h = $request?->boolean('is_24h') ?? false;
        $pensionerDiscount = $request?->boolean('pensioner_discount') ?? false;
        $hasActions = $request?->boolean('has_actions') ?? false;
        $search = trim((string) ($request?->query('search', '')));
        $hasGeo = $request?->filled('lat') || $request?->filled('lng') || $request?->filled('radius_km');

        $isDutySeoPage = $citySlug !== null
            && $dutyNow
            && $search === ''
            && !$openNow
            && !$is24h
            && !$pensionerDiscount
            && !$hasActions
            && !$hasGeo;

        $isGenericCityPage = $citySlug !== null
            && $search === ''
            && !$openNow
            && !$dutyNow
            && !$is24h
            && !$pensionerDiscount
            && !$hasActions
            && !$hasGeo;

        $isBaseListingPage = $citySlug === null
            && $search === ''
            && !$openNow
            && !$dutyNow
            && !$is24h
            && !$pensionerDiscount
            && !$hasActions
            && !$hasGeo;

        $robots = ($isDutySeoPage || $isGenericCityPage || $isBaseListingPage)
            ? 'index, follow'
            : 'noindex, follow';

        $query = DB::table('apoteke_poslovnice')
            ->join('apoteke_firme', 'apoteke_firme.id', '=', 'apoteke_poslovnice.firma_id')
            ->leftJoin('gradovi', 'gradovi.id', '=', 'apoteke_poslovnice.grad_id')
            ->whereNull('apoteke_poslovnice.deleted_at')
            ->whereNull('apoteke_firme.deleted_at')
            ->where('apoteke_poslovnice.is_active', true)
            ->where('apoteke_poslovnice.is_verified', true)
            ->where('apoteke_firme.is_active', true)
            ->where('apoteke_firme.status', 'verified');

        if ($city) {
            $query->whereRaw(
                "LOWER(COALESCE(NULLIF(apoteke_poslovnice.grad_naziv, ''), gradovi.naziv, '')) = ?",
                [mb_strtolower($city)]
            );
        }
        $count = (clone $query)->count();

        $titleLocationPart = $seoCity ? " - {$seoCity}" : '';
        $listingImage = (clone $query)
            ->whereNotNull('apoteke_poslovnice.profilna_slika_url')
            ->where('apoteke_poslovnice.profilna_slika_url', '!=', '')
            ->orderByDesc('apoteke_poslovnice.id')
            ->value('apoteke_poslovnice.profilna_slika_url');
        $image = $this->resolveImageCandidates([$listingImage]);

        if ($isDutySeoPage) {
            $dutySeoCity = $seoCity ?? $city;
            $momentUtc = now('Europe/Sarajevo')->setTimezone('UTC');
            $dutyCount = DB::table('apoteke_dezurstva')
                ->join('apoteke_poslovnice', 'apoteke_poslovnice.id', '=', 'apoteke_dezurstva.poslovnica_id')
                ->join('apoteke_firme', 'apoteke_firme.id', '=', 'apoteke_poslovnice.firma_id')
                ->leftJoin('gradovi', 'gradovi.id', '=', 'apoteke_poslovnice.grad_id')
                ->whereNull('apoteke_poslovnice.deleted_at')
                ->whereNull('apoteke_firme.deleted_at')
                ->where('apoteke_poslovnice.is_active', true)
                ->where('apoteke_poslovnice.is_verified', true)
                ->where('apoteke_firme.is_active', true)
                ->where('apoteke_firme.status', 'verified')
                ->where('apoteke_dezurstva.status', 'confirmed')
                ->where('apoteke_dezurstva.starts_at', '<=', $momentUtc)
                ->where('apoteke_dezurstva.ends_at', '>', $momentUtc)
                ->when(
                    $city !== null,
                    fn ($dutyQuery) => $dutyQuery->whereRaw(
                        "LOWER(COALESCE(NULLIF(apoteke_poslovnice.grad_naziv, ''), gradovi.naziv, '')) = ?",
                        [mb_strtolower($city)]
                    )
                )
                ->distinct('apoteke_poslovnice.id')
                ->count('apoteke_poslovnice.id');

            $title = $dutySeoCity ? "Dezurna apoteka - {$dutySeoCity} | wizMedik" : 'Dezurne apoteke | wizMedik';
            $description = $dutySeoCity
                ? ($dutyCount > 0
                    ? "Pronadjite {$dutyCount}+ dezurnih apoteka za {$dutySeoCity}. Dostupni su kontakt, lokacija, status dezurstva i radno vrijeme."
                    : "Provjerite koje apoteke su trenutno dezurne za {$dutySeoCity}. Dostupni su kontakt, lokacija, status dezurstva i radno vrijeme na jednom mjestu.")
                : 'Pronadjite apoteke koje su trenutno dezurne, sa kontakt informacijama i lokacijom.';
            $url = $this->appendQueryParameters(
                $this->buildUrl("apoteke/{$citySlug}"),
                [
                    'grad' => $citySlug,
                    'dezurna_now' => '1',
                ]
            );
            $schema = $this->buildCollectionSchema($title, $description, $url, $dutyCount);

            return [
                'title' => "<title>{$title}</title>",
                'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema, $robots),
            ];
        }

        $title = "Apoteke{$titleLocationPart} | wizMedik";
        $description = $seoCity
            ? "Pronadjite dezurne i otvorene apoteke za {$seoCity}. Dostupno {$count}+ poslovnica sa kontaktima, lokacijom i radnim vremenom."
            : "Pronadjite dezurne i otvorene apoteke u Bosni i Hercegovini. Dostupno {$count}+ poslovnica sa kontaktima, lokacijom i radnim vremenom.";
        $path = $citySlug ? "apoteke/{$citySlug}" : 'apoteke';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema, $robots),
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
        $listingSpa = (clone $query)
            ->where(function ($inner) {
                $inner->whereNotNull('featured_slika')
                    ->where('featured_slika', '!=', '')
                    ->orWhereNotNull('galerija');
            })
            ->orderByDesc('id')
            ->first(['featured_slika', 'galerija']);
        $image = $this->resolveImageCandidates([
            $listingSpa->featured_slika ?? null,
            $this->firstImageFromJson($listingSpa->galerija ?? null),
        ]);

        $path = $citySlug ? "banje/{$citySlug}" : 'banje';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
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
        $listingHome = (clone $query)
            ->where(function ($inner) {
                $inner->whereNotNull('featured_slika')
                    ->where('featured_slika', '!=', '')
                    ->orWhereNotNull('galerija');
            })
            ->orderByDesc('id')
            ->first(['featured_slika', 'galerija']);
        $image = $this->resolveImageCandidates([
            $listingHome->featured_slika ?? null,
            $this->firstImageFromJson($listingHome->galerija ?? null),
        ]);

        $path = $citySlug ? "domovi-njega/{$citySlug}" : 'domovi-njega';
        $url = $this->buildUrl($path);
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
        ];
    }

    private function getMedicinesListingMeta(): array
    {
        $count = DB::table('lijekovi')->count();

        $title = 'Lijekovi - cijene, doplate i indikacije | wizMedik';
        $description = "Pretrazite {$count}+ lijekova sa aktuelnom cijenom, participacijom i indikacijama iz RFZO cjenovnika.";
        $url = $this->buildUrl('lijekovi');
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
        $listingImage = DB::table('blog_posts')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->orderByDesc('published_at')
            ->value('thumbnail');
        $image = $this->resolveImageCandidates([$listingImage]);
        $url = $this->buildUrl('blog');
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
        ];
    }

    private function getQuestionsListingMeta(): array
    {
        $count = DB::table('pitanja')
            ->where('je_javno', true)
            ->count();

        $title = 'Medicinska pitanja i odgovori | wizMedik';
        $description = "Procitajte {$count}+ javnih medicinskih pitanja i odgovora verifikovanih doktora na wizMedik platformi.";
        $listingImage = DB::table('specijalnosti')
            ->whereNotNull('og_image')
            ->where('og_image', '!=', '')
            ->orderBy('id')
            ->value('og_image');
        $image = $this->resolveImageCandidates([$listingImage]);
        $url = $this->buildUrl('pitanja');
        $schema = $this->buildCollectionSchema($title, $description, $url, $count);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
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
            return $this->getNotFoundMeta("doktor/{$slug}");
        }

        $title = "Dr. {$doctor->ime} {$doctor->prezime} - {$doctor->specijalnost} | wizMedik";
        $description = $this->cleanDescription(
            $doctor->opis ?? null,
            "Zakazite pregled kod Dr. {$doctor->ime} {$doctor->prezime}, {$doctor->specijalnost} u {$doctor->grad}. Online zakazivanje termina."
        );
        $image = $this->resolveImageCandidates([
            $doctor->slika_profila ?? null,
        ]);
        $url = $this->buildUrl("doktor/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'profile'),
        ];
    }

    private function getMedicineMeta(string $slug): array
    {
        $lijek = DB::table('lijekovi')
            ->where('slug', $slug)
            ->first();

        if (!$lijek) {
            return $this->getNotFoundMeta("lijekovi/{$slug}");
        }

        $naziv = $lijek->naziv ?: $lijek->naziv_lijeka ?: 'Lijek';
        $title = "{$naziv} - profil lijeka i fond podaci | wizMedik";

        $descriptionParts = [];
        if (!empty($lijek->doza)) {
            $descriptionParts[] = $lijek->doza;
        }
        if (!empty($lijek->pakovanje)) {
            $descriptionParts[] = $lijek->pakovanje;
        }
        if (!empty($lijek->brend)) {
            $descriptionParts[] = 'Brend: ' . $lijek->brend;
        }
        if (!empty($lijek->aktuelna_cijena)) {
            $descriptionParts[] = 'Aktuelna cijena: ' . number_format((float) $lijek->aktuelna_cijena, 2, ',', '.') . ' KM';
        }
        if (!empty($lijek->aktuelni_iznos_participacije)) {
            $descriptionParts[] = 'Doplata osiguranika: ' . number_format((float) $lijek->aktuelni_iznos_participacije, 2, ',', '.') . ' KM';
        }

        $description = $naziv;
        if (!empty($descriptionParts)) {
            $description .= '. ' . implode('. ', $descriptionParts) . '.';
        } else {
            $description .= '. Pregled osnovnih podataka, cijene i indikacija.';
        }

        $url = $this->buildUrl("lijekovi/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'profile'),
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
            return $this->getNotFoundMeta("klinika/{$slug}");
        }

        $title = "{$clinic->naziv} - Klinika u {$clinic->grad} | wizMedik";
        $description = $this->cleanDescription(
            $clinic->opis ?? null,
            "Zakažite pregled u {$clinic->naziv}, {$clinic->grad}. Online zakazivanje termina."
        );
        $image = $this->resolveImageCandidates([
            $this->firstImageFromJson($clinic->slike ?? null),
        ]);
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
            return $this->getNotFoundMeta("specijalnost/{$slug}");
        }

        $doctorCount = DB::table('doktori')
            ->whereRaw('LOWER(specijalnost) = ?', [mb_strtolower($specialty->naziv)])
            ->whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $title = $specialty->meta_title
            ?: "{$specialty->naziv} - Pronadite doktora | wizMedik";
        $description = $this->cleanDescription(
            $specialty->meta_description ?? $specialty->opis ?? null,
            "Pronadite najboljeg doktora za {$specialty->naziv} u BiH. Dostupno {$doctorCount}+ doktora za online zakazivanje."
        );
        $image = $this->resolveImageCandidates([
            $specialty->og_image ?? null,
            $specialty->icon_url ?? null,
        ]);
        $url = $this->buildUrl("specijalnost/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getSpecialtyServiceMeta(string $specialtySlug, string $serviceSlug): array
    {
        $service = DB::table('specialty_service_pages')
            ->join('specijalnosti', 'specijalnosti.id', '=', 'specialty_service_pages.specialty_id')
            ->where('specijalnosti.slug', $specialtySlug)
            ->where('specialty_service_pages.slug', $serviceSlug)
            ->whereNull('specialty_service_pages.deleted_at')
            ->where('specialty_service_pages.status', 'published')
            ->where('specijalnosti.aktivan', true)
            ->where(function ($query) {
                $query->whereNull('specialty_service_pages.published_at')
                    ->orWhere('specialty_service_pages.published_at', '<=', now());
            })
            ->select([
                'specialty_service_pages.naziv',
                'specialty_service_pages.kratki_opis',
                'specialty_service_pages.sadrzaj',
                'specialty_service_pages.meta_title',
                'specialty_service_pages.meta_description',
                'specialty_service_pages.meta_keywords',
                'specialty_service_pages.canonical_url',
                'specialty_service_pages.og_image',
                'specialty_service_pages.is_indexable',
                'specijalnosti.naziv as specialty_naziv',
                'specijalnosti.og_image as specialty_og_image',
                'specijalnosti.icon_url as specialty_icon_url',
            ])
            ->first();

        if (!$service) {
            return $this->getNotFoundMeta("specijalnost/{$specialtySlug}/{$serviceSlug}");
        }

        $title = $service->meta_title
            ?: "{$service->naziv} | {$service->specialty_naziv} | wizMedik";

        $description = $this->cleanDescription(
            $service->meta_description ?? $service->kratki_opis ?? null,
            "Detaljan pregled usluge {$service->naziv} iz oblasti {$service->specialty_naziv}."
        );

        $image = $this->resolveImageCandidates([
            $service->og_image ?? null,
            $this->firstImageFromHtml($service->sadrzaj ?? null),
            $service->specialty_og_image ?? null,
            $service->specialty_icon_url ?? null,
        ]);

        $defaultUrl = $this->buildUrl("specijalnost/{$specialtySlug}/{$serviceSlug}");
        $url = !empty($service->canonical_url) ? (string) $service->canonical_url : $defaultUrl;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'MedicalWebPage',
            'name' => $service->naziv,
            'description' => $description,
            'url' => $url,
            'about' => [
                '@type' => 'MedicalSpecialty',
                'name' => $service->specialty_naziv,
            ],
        ];

        if (!empty($service->meta_keywords)) {
            $schema['keywords'] = $service->meta_keywords;
        }

        $robots = (bool) $service->is_indexable ? 'index, follow' : 'noindex, follow';

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'article', $schema, $robots),
        ];
    }

    private function getCityMeta(string $slug): array
    {
        $city = DB::table('gradovi')
            ->where('slug', $slug)
            ->first();

        if (!$city) {
            return $this->getNotFoundMeta("grad/{$slug}");
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

        $pharmacyCount = DB::table('apoteke_poslovnice')
            ->join('apoteke_firme', 'apoteke_firme.id', '=', 'apoteke_poslovnice.firma_id')
            ->whereRaw('LOWER(COALESCE(apoteke_poslovnice.grad_naziv, \'\')) = ?', [mb_strtolower($city->naziv)])
            ->whereNull('apoteke_poslovnice.deleted_at')
            ->whereNull('apoteke_firme.deleted_at')
            ->where('apoteke_poslovnice.is_active', true)
            ->where('apoteke_poslovnice.is_verified', true)
            ->where('apoteke_firme.is_active', true)
            ->where('apoteke_firme.status', 'verified')
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
        $description = "U {$city->naziv} pronadjite {$doctorCount}+ doktora, {$clinicCount}+ klinika, {$labCount}+ laboratorija, {$pharmacyCount}+ apoteka, {$spaCount}+ banja i {$careHomeCount}+ domova za njegu.";
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
            return $this->getNotFoundMeta("laboratorija/{$slug}");
        }

        $title = $lab->meta_title ?: "{$lab->naziv} - Laboratorija u {$lab->grad} | wizMedik";
        $description = $this->cleanDescription(
            $lab->meta_description ?? $lab->kratak_opis ?? $lab->opis ?? null,
            "Laboratorijske analize u {$lab->grad}. Provjerite cijene i kontakt podatke."
        );
        $image = $this->resolveImageCandidates([
            $lab->featured_slika ?? null,
            $lab->profilna_slika ?? null,
            $this->firstImageFromJson($lab->galerija ?? null),
        ]);
        $url = $this->buildUrl("laboratorija/{$slug}");

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website'),
        ];
    }

    private function getPharmacyMeta(string $slug): array
    {
        $pharmacy = DB::table('apoteke_poslovnice')
            ->join('apoteke_firme', 'apoteke_firme.id', '=', 'apoteke_poslovnice.firma_id')
            ->select(
                'apoteke_poslovnice.naziv',
                'apoteke_poslovnice.slug',
                'apoteke_poslovnice.grad_naziv',
                'apoteke_poslovnice.adresa',
                'apoteke_poslovnice.kratki_opis',
                'apoteke_poslovnice.profilna_slika_url',
                'apoteke_poslovnice.galerija_slike',
                'apoteke_poslovnice.is_24h',
                'apoteke_firme.naziv_brenda'
            )
            ->where('apoteke_poslovnice.slug', $slug)
            ->whereNull('apoteke_poslovnice.deleted_at')
            ->whereNull('apoteke_firme.deleted_at')
            ->where('apoteke_poslovnice.is_active', true)
            ->where('apoteke_poslovnice.is_verified', true)
            ->where('apoteke_firme.is_active', true)
            ->where('apoteke_firme.status', 'verified')
            ->first();

        if (!$pharmacy) {
            return $this->getNotFoundMeta("apoteka/{$slug}");
        }

        $city = $pharmacy->grad_naziv ?: 'BiH';
        $title = "{$pharmacy->naziv} - Apoteka u {$city} | wizMedik";
        $description = $this->cleanDescription(
            $pharmacy->kratki_opis ?? null,
            "Kontakt i radno vrijeme apoteke {$pharmacy->naziv} u {$city}. Provjerite lokaciju i dostupnost."
        );
        $image = $this->resolveImageCandidates([
            $pharmacy->profilna_slika_url ?? null,
            $this->firstImageFromJson($pharmacy->galerija_slike ?? null),
        ]);
        $url = $this->buildUrl("apoteka/{$slug}");

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Pharmacy',
            'name' => $pharmacy->naziv,
            'url' => $url,
            'image' => $image,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $pharmacy->adresa,
                'addressLocality' => $city,
                'addressCountry' => 'BA',
            ],
        ];

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'website', $schema),
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
            return $this->getNotFoundMeta("banja/{$slug}");
        }

        $title = $spa->meta_title ?: "{$spa->naziv} - Banja u {$spa->grad} | wizMedik";
        $description = $this->cleanDescription(
            $spa->meta_description ?? $spa->opis ?? null,
            "Banjsko lijeciliste {$spa->naziv} u {$spa->grad}. Provjerite ponudu i kontakt."
        );
        $image = $this->resolveImageCandidates([
            $spa->featured_slika ?? null,
            $this->firstImageFromJson($spa->galerija ?? null),
        ]);
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
            return $this->getNotFoundMeta("dom-njega/{$slug}");
        }

        $title = $home->meta_title ?: "{$home->naziv} - Dom njege u {$home->grad} | wizMedik";
        $description = $this->cleanDescription(
            $home->meta_description ?? $home->opis ?? null,
            "Dom za njegu starih i nemocnih osoba {$home->naziv} u {$home->grad}."
        );
        $image = $this->resolveImageCandidates([
            $home->featured_slika ?? null,
            $this->firstImageFromJson($home->galerija ?? null),
        ]);
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
            return $this->getNotFoundMeta("blog/{$slug}");
        }

        $title = $post->meta_title ?: "{$post->naslov} | wizMedik Blog";
        $description = $this->cleanDescription(
            $post->meta_description ?? $post->excerpt ?? strip_tags($post->sadrzaj ?? ''),
            'Strucni zdravstveni savjeti na wizMedik blogu.'
        );
        $image = $this->resolveImageCandidates([
            $post->thumbnail ?? null,
            $this->firstImageFromHtml($post->sadrzaj ?? null),
        ]);
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
                'specijalnosti.naziv as specijalnost_naziv',
                'specijalnosti.og_image as specijalnost_og_image'
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
        $image = $this->resolveImageCandidates([
            $question->specijalnost_og_image ?? null,
        ]);

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
            'meta' => $this->buildMetaTags($title, $description, $image, $url, 'article', $schema),
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
        $title = 'wizMedik - Pronadite doktore, klinike, laboratorije, apoteke, banje i domove njege';
        $description = 'Vodeca platforma za pronalazenje zdravstvenih usluga, apoteka i online zakazivanje termina u Bosni i Hercegovini.';
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

    private function getNotFoundMeta(string $path): array
    {
        $title = '404 - Stranica nije pronadjena | wizMedik';
        $description = 'Trazeni sadrzaj nije pronadjen.';
        $url = $this->buildUrl($path);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website', null, 'noindex, nofollow'),
            'status' => 404,
        ];
    }

    private function getStaticMetaForPath(string $path): ?array
    {
        if ($path === 'about') {
            return $this->simplePageMeta(
                'O wizMedik platformi | wizMedik',
                'Saznajte vise o wizMedik platformi za pronalazak doktora, klinika, laboratorija i apoteka u Bosni i Hercegovini.',
                'about'
            );
        }

        if ($path === 'contact') {
            return $this->simplePageMeta(
                'Kontakt | wizMedik',
                'Kontaktirajte wizMedik tim za podrsku, saradnju i dodatne informacije o platformi.',
                'contact'
            );
        }

        if ($path === 'specijalnosti') {
            return $this->simplePageMeta(
                'Medicinske specijalnosti | wizMedik',
                'Pregledajte medicinske specijalnosti i pronadjite doktore i klinike za oblast koja vas zanima.',
                'specijalnosti'
            );
        }

        if ($path === 'kalkulatori') {
            return $this->simplePageMeta(
                'Zdravstveni kalkulatori | wizMedik',
                'Koristite zdravstvene kalkulatore za brzu informativnu procjenu i planiranje zdravstvenih ciljeva.',
                'kalkulatori'
            );
        }

        if ($path === 'mkb10') {
            return $this->simplePageMeta(
                'MKB-10 sifarnik dijagnoza | wizMedik',
                'Pretrazite MKB-10 sifarnik i pronadjite dijagnoze po kategorijama sa jasnim opisima.',
                'mkb10'
            );
        }

        if ($path === 'faq') {
            return $this->simplePageMeta(
                'Cesta pitanja | wizMedik',
                'Odgovori na najcesca pitanja o koristenju wizMedik platforme, pretrazi i zakazivanju.',
                'faq'
            );
        }

        if ($path === 'politika-privatnosti' || $path === 'privacy-policy') {
            return $this->simplePageMeta(
                'Politika privatnosti | wizMedik',
                'Procitajte kako wizMedik prikuplja, cuva i obraduje licne podatke korisnika.',
                'politika-privatnosti'
            );
        }

        if ($path === 'uslovi-koristenja' || $path === 'terms-of-service') {
            return $this->simplePageMeta(
                'Uslovi koristenja | wizMedik',
                'Upoznajte se sa uslovima koristenja wizMedik platforme i pravilima upotrebe.',
                'uslovi-koristenja'
            );
        }

        if ($path === 'cookie-policy') {
            return $this->simplePageMeta(
                'Politika kolacica | wizMedik',
                'Informacije o vrstama kolacica koje wizMedik koristi i nacinu upravljanja postavkama privatnosti.',
                'cookie-policy'
            );
        }

        if ($path === 'impressum') {
            return $this->simplePageMeta(
                'Impressum | wizMedik',
                'Osnovni pravni i kontakt podaci o WizMedik platformi, odgovornosti, autorskim pravima i zastiti podataka.',
                'impressum'
            );
        }

        if ($path === 'registration-options') {
            return $this->simplePageMeta(
                'Registracija na platformu | wizMedik',
                'Odaberite tip registracije i pridruzite se wizMedik platformi kao zdravstveni profesionalac ili ustanova.',
                'registration-options'
            );
        }

        if ($path === 'register/doctor') {
            return $this->simplePageMeta(
                'Registracija doktora | wizMedik',
                'Kreirajte profil doktora na wizMedik i budite vidljiviji pacijentima koji traze pregled.',
                'register/doctor'
            );
        }

        if ($path === 'register/clinic') {
            return $this->simplePageMeta(
                'Registracija klinike | wizMedik',
                'Registrujte kliniku na wizMedik platformu i predstavite usluge, tim i termine pacijentima.',
                'register/clinic'
            );
        }

        if ($path === 'register/laboratory') {
            return $this->simplePageMeta(
                'Registracija laboratorije | wizMedik',
                'Registrujte laboratoriju i omogucite pacijentima laksi pristup analizama i informacijama.',
                'register/laboratory'
            );
        }

        if ($path === 'register/pharmacy') {
            return $this->simplePageMeta(
                'Registracija apoteke | wizMedik',
                'Registrujte apoteku i poslovnice na wizMedik platformu radi bolje lokalne vidljivosti.',
                'register/pharmacy'
            );
        }

        if ($path === 'register/spa') {
            return $this->simplePageMeta(
                'Registracija banje | wizMedik',
                'Registrujte banju ili rehabilitacioni centar i predstavite terapije i smjestajne kapacitete.',
                'register/spa'
            );
        }

        if ($path === 'register/care-home') {
            return $this->simplePageMeta(
                'Registracija doma za njegu | wizMedik',
                'Registrujte dom za njegu i prikazite usluge, smjestaj i kontakt informacije korisnicima.',
                'register/care-home'
            );
        }

        if (preg_match('/^register\/verify\/[^\/]+$/', $path)) {
            return $this->getNoindexMeta(
                'Verifikacija registracije | wizMedik',
                'Verifikacija registracije korisnickog naloga.',
                $path
            );
        }

        if (in_array($path, ['auth', 'forgot-password', 'reset-password'], true)) {
            return $this->getNoindexMeta(
                'Korisnicki pristup | wizMedik',
                'Siguran pristup korisnickom nalogu na wizMedik platformi.',
                $path
            );
        }

        if (in_array($path, [
            'dashboard',
            'doctor-dashboard',
            'clinic-dashboard',
            'laboratory-dashboard',
            'pharmacy-dashboard',
            'spa-dashboard',
            'dom-dashboard',
            'my-blog-posts',
            'blog/editor',
        ], true) || str_starts_with($path, 'admin') || preg_match('/^blog\/editor\/[^\/]+$/', $path)) {
            return $this->getNoindexMeta(
                'Privatna stranica | wizMedik',
                'Privatna korisnicka stranica.',
                $path
            );
        }

        return null;
    }

    private function simplePageMeta(string $title, string $description, string $path): array
    {
        $url = $this->buildUrl($path);

        return [
            'title' => "<title>{$title}</title>",
            'meta' => $this->buildMetaTags($title, $description, $this->defaultImage(), $url, 'website'),
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
        return $this->buildUrl('/og-image.jpg');
    }

    private function absoluteImage(?string $image): string
    {
        if (!$image) {
            return $this->defaultImage();
        }

        $normalized = trim($image);
        if ($normalized === '') {
            return $this->defaultImage();
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return $normalized;
        }

        if (str_starts_with($normalized, '//')) {
            return 'https:' . $normalized;
        }

        if (str_starts_with($normalized, '/storage/')) {
            return $this->buildUrl($normalized);
        }

        if (str_starts_with($normalized, 'storage/')) {
            return $this->buildUrl('/' . $normalized);
        }

        // Most uploaded files are stored as relative disk paths (e.g. "domovi/featured/x.jpg").
        if (!str_starts_with($normalized, '/') && str_contains($normalized, '/')) {
            return $this->buildUrl('/storage/' . ltrim($normalized, '/'));
        }

        return $this->buildUrl($normalized);
    }

    private function resolveImageCandidates(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $normalized = trim($candidate);
            if ($normalized === '') {
                continue;
            }

            return $this->absoluteImage($normalized);
        }

        return $this->defaultImage();
    }

    private function firstImageFromJson($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        foreach ($decoded as $item) {
            if (is_string($item) && trim($item) !== '') {
                return trim($item);
            }

            if (!is_array($item)) {
                continue;
            }

            foreach (['url', 'src', 'image', 'slika'] as $key) {
                if (isset($item[$key]) && is_string($item[$key]) && trim($item[$key]) !== '') {
                    return trim($item[$key]);
                }
            }
        }

        return null;
    }

    private function firstImageFromHtml(?string $html): ?string
    {
        if (!$html) {
            return null;
        }

        if (!preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            return null;
        }

        $src = html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $src = trim($src);

        return $src !== '' ? $src : null;
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

    private function resolveSpecialtyContextBySlug(string $slug): array
    {
        $decodedName = $this->decodeSegment($slug);

        $specialty = DB::table('specijalnosti')
            ->select('id', 'naziv')
            ->where(function ($query) use ($slug, $decodedName) {
                $query->where('slug', $slug)
                    ->orWhereRaw('LOWER(naziv) = ?', [mb_strtolower($decodedName)]);
            })
            ->first();

        if (!$specialty) {
            return [
                'name' => $decodedName,
                'ids' => [],
            ];
        }

        $childIds = DB::table('specijalnosti')
            ->where('parent_id', $specialty->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'name' => $specialty->naziv,
            'ids' => array_values(array_unique(array_merge([(int) $specialty->id], $childIds))),
        ];
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

    private function applyClinicSpecialtyFilter($query, array $specialtyIds, string $specialtyName): void
    {
        $specialtyNames = collect([$specialtyName])
            ->merge(
                DB::table('specijalnosti')
                    ->whereIn('id', $specialtyIds)
                    ->pluck('naziv')
            )
            ->filter()
            ->map(fn ($name) => mb_strtolower((string) $name))
            ->unique()
            ->values()
            ->all();

        $query->where(function ($builder) use ($specialtyIds, $specialtyNames) {
            if ($specialtyIds !== []) {
                $builder->whereExists(function ($specialtyQuery) use ($specialtyIds) {
                    $specialtyQuery->select(DB::raw(1))
                        ->from('klinika_specijalnost')
                        ->whereColumn('klinika_specijalnost.klinika_id', 'klinike.id')
                        ->whereIn('klinika_specijalnost.specijalnost_id', $specialtyIds);
                });
            }

            $builder->orWhereExists(function ($doctorQuery) use ($specialtyIds, $specialtyNames) {
                $doctorQuery->select(DB::raw(1))
                    ->from('doktori')
                    ->whereColumn('doktori.klinika_id', 'klinike.id')
                    ->whereNull('doktori.deleted_at')
                    ->where('doktori.aktivan', true)
                    ->where('doktori.verifikovan', true)
                    ->where(function ($matchQuery) use ($specialtyIds, $specialtyNames) {
                        if ($specialtyIds !== []) {
                            $matchQuery->whereIn('doktori.specijalnost_id', $specialtyIds);
                        }

                        if ($specialtyNames !== []) {
                            $method = $specialtyIds !== [] ? 'orWhereIn' : 'whereIn';
                            $matchQuery->{$method}(DB::raw('LOWER(doktori.specijalnost)'), $specialtyNames);
                        }
                    });
            });
        });
    }

    private function normalizeListingQueryUrl(Request $request): ?string
    {
        $path = trim($request->path(), '/');
        $query = $request->query();

        if ($path === 'apoteke') {
            $city = $request->query('grad');
            $citySlug = $city ? $this->queryValueToSlug($city) : null;

            if (!$citySlug) {
                return null;
            }

            $normalizedQuery = $this->normalizePharmacyListingQuery($query, $citySlug);
            $targetUrl = $this->buildUrl("apoteke/{$citySlug}");

            if ($normalizedQuery === []) {
                return $targetUrl;
            }

            return $this->appendQueryParameters($targetUrl, $normalizedQuery);
        }

        if (preg_match('/^apoteke\/([^\/]+)$/', $path, $matches)) {
            $citySlug = $matches[1];
            $normalizedQuery = $this->normalizePharmacyListingQuery($query, $citySlug);
            $targetUrl = $this->buildUrl("apoteke/{$citySlug}");

            if ($normalizedQuery === []) {
                return count($query) > 0 ? $targetUrl : null;
            }

            $canonicalizedCurrentQuery = $this->canonicalizeCurrentPharmacyListingQuery($query);
            if (count($query) === count($canonicalizedCurrentQuery) && $canonicalizedCurrentQuery == $normalizedQuery) {
                return null;
            }

            return $this->appendQueryParameters($targetUrl, $normalizedQuery);
        }

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

            if ($citySlug && $specialtySlug && count($query) === 2) {
                return $this->buildUrl("klinike/{$citySlug}/{$specialtySlug}");
            }
            if ($specialtySlug && count($query) === 1) {
                return $this->buildUrl("klinike/specijalnost/{$specialtySlug}");
            }
            if ($citySlug && count($query) === 1) {
                return $this->buildUrl("klinike/{$citySlug}");
            }
            return null;
        }

        if (preg_match('/^klinike\/specijalnost\/([^\/]+)$/', $path, $matches)) {
            $citySlug = $city ? $this->queryValueToSlug($city) : null;
            if ($citySlug && count($query) === 1) {
                return $this->buildUrl("klinike/{$citySlug}/{$matches[1]}");
            }

            return null;
        }

        if (preg_match('/^klinike\/([^\/]+)$/', $path, $matches)) {
            $specialtySlug = $specialty ? $this->queryValueToSlug($specialty) : null;
            if ($specialtySlug && count($query) === 1) {
                return $this->buildUrl("klinike/{$matches[1]}/{$specialtySlug}");
            }
        }

        if (in_array($path, ['laboratorije', 'apoteke', 'banje', 'domovi-njega'], true)) {
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

    private function normalizePharmacyListingQuery(array $query, string $citySlug): array
    {
        $normalized = [];
        $hasSerializableFilters = false;

        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $normalized['search'] = $search;
            $hasSerializableFilters = true;
        }

        foreach (['open_now', 'dezurna_now', 'is_24h', 'pensioner_discount', 'has_actions'] as $flag) {
            if ($this->queryFlagIsEnabled($query[$flag] ?? null)) {
                $normalized[$flag] = '1';
                $hasSerializableFilters = true;
            }
        }

        if (!$hasSerializableFilters) {
            return [];
        }

        return ['grad' => $citySlug] + $normalized;
    }

    private function canonicalizeCurrentPharmacyListingQuery(array $query): array
    {
        $normalized = [];
        $hasSerializableFilters = false;

        $city = trim((string) ($query['grad'] ?? ''));
        $citySlug = $city !== '' ? $this->queryValueToSlug($city) : '';

        $search = trim((string) ($query['search'] ?? ''));
        if ($search !== '') {
            $normalized['search'] = $search;
            $hasSerializableFilters = true;
        }

        foreach (['open_now', 'dezurna_now', 'is_24h', 'pensioner_discount', 'has_actions'] as $flag) {
            if ($this->queryFlagIsEnabled($query[$flag] ?? null)) {
                $normalized[$flag] = '1';
                $hasSerializableFilters = true;
            }
        }

        if (!$hasSerializableFilters || $citySlug === '') {
            return [];
        }

        return ['grad' => $citySlug] + $normalized;
    }

    private function queryFlagIsEnabled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeCanonicalRedirect(Request $request): ?string
    {
        $pathInfo = $request->getPathInfo();
        $path = trim($request->path(), '/');
        $queryString = $request->getQueryString();

        if ($pathInfo !== '/' && str_ends_with($pathInfo, '/')) {
            $trimmedPath = trim($pathInfo, '/');
            return $this->appendQueryString($this->buildUrl($trimmedPath), $queryString);
        }

        $aliases = [
            'kontakt' => 'contact',
            'o-nama' => 'about',
        ];

        if (isset($aliases[$path])) {
            return $this->appendQueryString($this->buildUrl($aliases[$path]), $queryString);
        }

        return null;
    }

    private function appendQueryString(string $url, ?string $queryString): string
    {
        if ($queryString === null || $queryString === '') {
            return $url;
        }

        return $url . '?' . $queryString;
    }

    private function appendQueryParameters(string $url, array $parameters): string
    {
        if ($parameters === []) {
            return $url;
        }

        return $url . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    private function stripExistingSeoTags(string $html): string
    {
        $patterns = [
            '/\s*<meta[^>]+name=(["\'])description\1[^>]*>\s*/i',
            '/\s*<meta[^>]+name=(["\'])robots\1[^>]*>\s*/i',
            '/\s*<link[^>]+rel=(["\'])canonical\1[^>]*>\s*/i',
            '/\s*<meta[^>]+property=(["\'])og:[^"\']+\1[^>]*>\s*/i',
            '/\s*<meta[^>]+name=(["\'])twitter:[^"\']+\1[^>]*>\s*/i',
        ];

        $stripped = preg_replace($patterns, "\n", $html);

        return $stripped ?? $html;
    }
}
