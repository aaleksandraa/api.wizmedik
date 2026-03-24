<?php

namespace App\Console\Commands;

use App\Http\Controllers\SeoController;
use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrerenderSeoPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:prerender-pages
        {--output= : Output directory (default: frontend dist)}
        {--max=0 : Max number of pages to render (0 = all)}
        {--path=* : Specific path(s) to prerender (without domain)}
        {--include-root : Also prerender homepage index.html}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate static prerendered HTML pages with route-specific SEO meta tags for static hosting.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputDir = $this->resolveOutputDir();
        if (!is_dir($outputDir)) {
            $this->error("Output directory not found: {$outputDir}");
            return self::FAILURE;
        }

        if (!is_writable($outputDir)) {
            $this->error("Output directory is not writable: {$outputDir}");
            return self::FAILURE;
        }

        $this->info("Output directory: {$outputDir}");

        $pathsFromOptions = $this->normalizeSpecificPathsOption();
        $paths = !empty($pathsFromOptions)
            ? $pathsFromOptions
            : $this->collectPathsFromSitemaps();

        if (empty($pathsFromOptions)) {
            $paths = array_values(array_unique(array_merge($paths, $this->additionalStaticPaths())));
        }

        if (!$this->option('include-root')) {
            $paths = array_values(array_filter($paths, fn (string $path) => $path !== ''));
        }

        $max = (int) $this->option('max');
        if ($max > 0 && count($paths) > $max) {
            $paths = array_slice($paths, 0, $max);
        }

        if (empty($paths)) {
            $this->warn('No paths found for prerendering.');
            return self::SUCCESS;
        }

        $seoController = app(SeoController::class);
        $rendered = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($paths as $path) {
            $display = $path === '' ? '/' : '/' . $path;

            try {
                $request = Request::create($display, 'GET');
                $response = $seoController->index($request);
                $status = $response->getStatusCode();

                if ($status >= 400) {
                    $this->warn("Skip {$display} (status {$status})");
                    $skipped++;
                    continue;
                }

                $targetFile = $this->targetFilePath($outputDir, $path);
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                file_put_contents($targetFile, (string) $response->getContent());
                $rendered++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Failed {$display}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Rendered: {$rendered}");
        $this->info("Skipped: {$skipped}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
            return self::FAILURE;
        }

        $this->info('Prerender complete.');
        return self::SUCCESS;
    }

    private function resolveOutputDir(): string
    {
        $outputOption = $this->option('output');
        if (is_string($outputOption) && trim($outputOption) !== '') {
            return rtrim($outputOption, DIRECTORY_SEPARATOR);
        }

        $default = config('app.sitemap_output_path', base_path('../frontend/dist'));
        return rtrim((string) $default, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<int, string>
     */
    private function collectPathsFromSitemaps(): array
    {
        $controller = app(SitemapController::class);
        $baseUrl = rtrim(config('app.frontend_url', 'https://wizmedik.com'), '/');

        $methods = [
            'pages',
            'doctors',
            'clinics',
            'specialties',
            'servicePages',
            'cities',
            'laboratories',
            'medicines',
            'pharmacies',
            'spas',
            'careHomes',
            'doctorCitySpecialties',
            'blog',
            'questions',
        ];

        $paths = [];

        foreach ($methods as $method) {
            try {
                $response = $controller->{$method}();
                $xml = (string) $response->getContent();
            } catch (\Throwable $e) {
                $this->warn("Sitemap method {$method} failed: {$e->getMessage()}");
                continue;
            }

            if (!preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $matches)) {
                continue;
            }

            foreach ($matches[1] as $locRaw) {
                $loc = html_entity_decode((string) $locRaw, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $loc = trim($loc);
                if ($loc === '' || Str::endsWith($loc, '.xml')) {
                    continue;
                }

                if (Str::startsWith($loc, $baseUrl)) {
                    $loc = substr($loc, strlen($baseUrl));
                }

                $parsedPath = parse_url($loc, PHP_URL_PATH);
                if (!is_string($parsedPath)) {
                    continue;
                }

                $normalized = trim($parsedPath, '/');
                if ($normalized === 'robots.txt' || Str::startsWith($normalized, 'api/')) {
                    continue;
                }

                $paths[] = $normalized;
            }
        }

        $paths = array_values(array_unique($paths));
        usort(
            $paths,
            fn (string $a, string $b) => strlen($a) <=> strlen($b) ?: strcmp($a, $b)
        );

        return $paths;
    }

    private function targetFilePath(string $outputDir, string $path): string
    {
        if ($path === '') {
            return $outputDir . DIRECTORY_SEPARATOR . 'index.html';
        }

        return $outputDir
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $path)
            . DIRECTORY_SEPARATOR
            . 'index.html';
    }

    /**
     * @return array<int, string>
     */
    private function additionalStaticPaths(): array
    {
        return [
            '',
            'about',
            'contact',
            'o-nama',
            'kontakt',
            'specijalnosti',
            'kalkulatori',
            'faq',
            'mkb10',
            'politika-privatnosti',
            'privacy-policy',
            'uslovi-koristenja',
            'terms-of-service',
            'cookie-policy',
            'registration-options',
            'register/doctor',
            'register/clinic',
            'register/laboratory',
            'register/pharmacy',
            'register/spa',
            'register/care-home',
            'postavi-pitanje',
            'medicinski-kalendar',
            'blog',
            'pitanja',
            'doktori',
            'klinike',
            'laboratorije',
            'apoteke',
            'banje',
            'banje/indikacije-terapije',
            'domovi-njega',
            'domovi-njega/vodic',
            'gradovi',
            'lijekovi',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeSpecificPathsOption(): array
    {
        $paths = $this->option('path');
        if (!is_array($paths) || empty($paths)) {
            return [];
        }

        $normalized = [];
        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }

            $value = trim($path);
            if ($value === '') {
                continue;
            }

            if (Str::startsWith($value, ['http://', 'https://'])) {
                $parsedPath = parse_url($value, PHP_URL_PATH);
                if (is_string($parsedPath)) {
                    $value = $parsedPath;
                }
            }

            $value = trim($value, '/');
            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
