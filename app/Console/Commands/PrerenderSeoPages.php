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
        $primaryOutputDir = $this->resolveOutputDir();
        $outputDirs = $this->resolveOutputDirs($primaryOutputDir);
        $templatePath = $this->resolveTemplatePathForPrerender($primaryOutputDir);

        foreach ($outputDirs as $outputDir) {
            if (!is_dir($outputDir)) {
                $this->error("Output directory not found: {$outputDir}");
                return self::FAILURE;
            }

            if (!is_writable($outputDir)) {
                $this->error("Output directory is not writable: {$outputDir}");
                return self::FAILURE;
            }
        }

        $this->info('Output directories:');
        foreach ($outputDirs as $outputDir) {
            $this->info(" - {$outputDir}");
        }

        if ($templatePath !== null) {
            config(['app.seo_index_template_path' => $templatePath]);
            $this->info("Using SEO template: {$templatePath}");
        } else {
            $this->warn("Could not resolve a build template inside {$primaryOutputDir}; falling back to controller template discovery.");
        }

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

        $this->deleteStalePrerenderedPaths($outputDirs, $paths);

        $seoController = app(SeoController::class);
        $rendered = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($paths as $path) {
            $display = $this->displayPrerenderTarget($path);

            try {
                $request = $this->createPrerenderRequest($path);
                $response = $seoController->index($request);
                $status = $response->getStatusCode();

                if ($status >= 400) {
                    $this->warn("Skip {$display} (status {$status})");
                    $skipped++;
                    continue;
                }

                foreach ($outputDirs as $outputDir) {
                    $targetFile = $this->targetFilePath($outputDir, $path);
                    $targetDir = dirname($targetFile);
                    if (!is_dir($targetDir)) {
                        if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                            throw new \RuntimeException("Failed to create directory: {$targetDir}");
                        }
                    }

                    $bytes = @file_put_contents($targetFile, (string) $response->getContent());
                    if ($bytes === false) {
                        throw new \RuntimeException("Failed to write file: {$targetFile}");
                    }
                }
                $rendered++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Failed {$display}: {$e->getMessage()}");
            }
        }

        $this->syncPrimaryBuildArtifactsToMirrorOutputs($primaryOutputDir, $outputDirs);
        $this->writePrerenderManifest($outputDirs, $paths);

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
    private function resolveOutputDirs(string $primaryOutputDir): array
    {
        $dirs = [rtrim($primaryOutputDir, DIRECTORY_SEPARATOR)];

        $mirrorRaw = trim((string) config('app.sitemap_output_mirror_paths', ''));
        if ($mirrorRaw !== '') {
            $mirrorDirs = preg_split('/[,;]+/', $mirrorRaw) ?: [];
            foreach ($mirrorDirs as $mirrorDir) {
                $normalized = rtrim(trim((string) $mirrorDir), DIRECTORY_SEPARATOR);
                if ($normalized !== '') {
                    $dirs[] = $normalized;
                }
            }
        }

        return array_values(array_unique($dirs));
    }

    private function resolveTemplatePathForPrerender(string $primaryOutputDir): ?string
    {
        $candidates = [
            rtrim($primaryOutputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html',
            rtrim($primaryOutputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'index.html',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function collectPathsFromSitemaps(): array
    {
        $controller = app(SitemapController::class);
        $baseUrl = rtrim(config('app.frontend_url', 'https://wizmedik.com'), '/');

        $methods = config('sitemaps.prerender_methods', [
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
        ]);

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
                $parsedQuery = parse_url($loc, PHP_URL_QUERY);
                if (!is_string($parsedPath)) {
                    continue;
                }

                $normalized = trim($parsedPath, '/');
                if ($normalized === 'robots.txt' || Str::startsWith($normalized, 'api/')) {
                    continue;
                }

                $paths[] = $this->buildPrerenderTarget(
                    $normalized,
                    $this->normalizeSeoQueryForPath($normalized, is_string($parsedQuery) ? $parsedQuery : '')
                );
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
        $relativeOutputPath = $this->relativeOutputPathForTarget($path);

        if ($relativeOutputPath === '') {
            return $outputDir . DIRECTORY_SEPARATOR . 'index.html';
        }

        return $outputDir
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativeOutputPath)
            . DIRECTORY_SEPARATOR
            . 'index.html';
    }

    /**
     * @param array<int, string> $outputDirs
     * @param array<int, string> $paths
     */
    private function deleteStalePrerenderedPaths(array $outputDirs, array $paths): void
    {
        $currentPaths = array_values(array_filter(
            array_map(fn (string $path) => $this->relativeOutputPathForTarget($path), $paths),
            fn (string $path) => $path !== ''
        ));
        $currentPathMap = array_fill_keys($currentPaths, true);

        foreach ($outputDirs as $outputDir) {
            $generatedPaths = $this->collectGeneratedRouteDirectories($outputDir);
            $stalePaths = array_values(array_filter(
                $generatedPaths,
                fn (string $path) => !isset($currentPathMap[$path])
            ));

            if (empty($stalePaths)) {
                continue;
            }

            $this->warn("Removing " . count($stalePaths) . " stale prerendered route(s) from {$outputDir}...");

            foreach ($stalePaths as $stalePath) {
                $targetFile = $this->targetFilePath($outputDir, $stalePath);

                if (is_file($targetFile)) {
                    @unlink($targetFile);
                }

                $this->removeEmptyDirectoriesUpward(dirname($targetFile), $outputDir);
            }
        }
    }

    /**
     * @param array<int, string> $outputDirs
     */
    private function syncPrimaryBuildArtifactsToMirrorOutputs(string $primaryOutputDir, array $outputDirs): void
    {
        $sourceAssetsDir = rtrim($primaryOutputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets';
        $topLevelEntries = @scandir($primaryOutputDir);

        if (!is_array($topLevelEntries)) {
            return;
        }

        foreach ($outputDirs as $outputDir) {
            if ($this->normalizePath($outputDir) === $this->normalizePath($primaryOutputDir)) {
                continue;
            }

            if (!is_dir($outputDir) || !is_writable($outputDir)) {
                $this->warn("Skipping build artifact sync for unavailable mirror output: {$outputDir}");
                continue;
            }

            if (is_dir($sourceAssetsDir)) {
                $targetAssetsDir = $outputDir . DIRECTORY_SEPARATOR . 'assets';
                $this->deletePathRecursively($targetAssetsDir);
                $this->copyDirectoryRecursively($sourceAssetsDir, $targetAssetsDir);
            }

            foreach ($topLevelEntries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === 'assets') {
                    continue;
                }

                $sourcePath = $primaryOutputDir . DIRECTORY_SEPARATOR . $entry;
                $targetPath = $outputDir . DIRECTORY_SEPARATOR . $entry;

                if (!is_file($sourcePath)) {
                    continue;
                }

                if (!@copy($sourcePath, $targetPath)) {
                    throw new \RuntimeException("Failed to copy build artifact '{$entry}' to '{$outputDir}'.");
                }
            }

            $this->info("Mirrored build artifacts to {$outputDir}");
        }
    }

    /**
     * @return array<int, string>
     */
    private function collectGeneratedRouteDirectories(string $baseDir, string $relativeDir = ''): array
    {
        if (!is_dir($baseDir)) {
            return [];
        }

        $entries = @scandir($baseDir);
        if (!is_array($entries)) {
            return [];
        }

        $results = [];
        $hasIndexHtml = in_array('index.html', $entries, true);

        if ($relativeDir !== '' && $hasIndexHtml) {
            $results[] = str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $absolutePath = $baseDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($absolutePath)) {
                continue;
            }

            $nextRelativeDir = $relativeDir === ''
                ? $entry
                : $relativeDir . DIRECTORY_SEPARATOR . $entry;

            $results = array_merge(
                $results,
                $this->collectGeneratedRouteDirectories($absolutePath, $nextRelativeDir)
            );
        }

        return array_values(array_unique($results));
    }

    private function removeEmptyDirectoriesUpward(string $directory, string $stopAt): void
    {
        $current = rtrim($directory, DIRECTORY_SEPARATOR);
        $stop = rtrim($stopAt, DIRECTORY_SEPARATOR);

        while ($current !== '' && $current !== $stop) {
            if (!is_dir($current)) {
                break;
            }

            $entries = @scandir($current);
            if (!is_array($entries)) {
                break;
            }

            $nonDotEntries = array_values(array_diff($entries, ['.', '..']));
            if (!empty($nonDotEntries)) {
                break;
            }

            @rmdir($current);
            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }
    }

    private function deletePathRecursively(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $entries = @scandir($path);
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $this->deletePathRecursively($path . DIRECTORY_SEPARATOR . $entry);
            }
        }

        @rmdir($path);
    }

    private function copyDirectoryRecursively(string $sourceDir, string $targetDir): void
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException("Failed to create directory: {$targetDir}");
        }

        $entries = @scandir($sourceDir);
        if (!is_array($entries)) {
            throw new \RuntimeException("Failed to read directory: {$sourceDir}");
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $entry;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($sourcePath)) {
                $this->copyDirectoryRecursively($sourcePath, $targetPath);
                continue;
            }

            if (!@copy($sourcePath, $targetPath)) {
                throw new \RuntimeException("Failed to copy asset '{$sourcePath}' to '{$targetPath}'.");
            }
        }
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /**
     * @param array<int, string> $outputDirs
     * @param array<int, string> $paths
     */
    private function writePrerenderManifest(array $outputDirs, array $paths): void
    {
        $manifestJson = json_encode(array_values(array_unique($paths)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($manifestJson)) {
            throw new \RuntimeException('Failed to encode prerender manifest.');
        }

        foreach ($outputDirs as $outputDir) {
            $manifestPath = $outputDir . DIRECTORY_SEPARATOR . '.seo-prerendered-paths.json';
            $bytes = @file_put_contents($manifestPath, $manifestJson . PHP_EOL);

            if ($bytes === false) {
                throw new \RuntimeException("Failed to write prerender manifest: {$manifestPath}");
            }
        }
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
            'impressum',
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

            $normalized[] = $this->normalizePrerenderTarget($value);
        }

        return array_values(array_unique(array_filter($normalized, fn (string $value) => $value !== '')));
    }

    private function createPrerenderRequest(string $target): Request
    {
        [$path, $query] = $this->splitPrerenderTarget($target);

        $requestPath = $path === '' ? '/' : '/' . $path;

        return Request::create($requestPath, 'GET', $query);
    }

    private function displayPrerenderTarget(string $target): string
    {
        if ($target === '') {
            return '/';
        }

        return '/' . ltrim($target, '/');
    }

    private function normalizePrerenderTarget(string $value): string
    {
        $rawValue = $value;
        $rawQuery = '';

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $parsedPath = parse_url($value, PHP_URL_PATH);
            $parsedQuery = parse_url($value, PHP_URL_QUERY);

            $rawValue = is_string($parsedPath) ? $parsedPath : '';
            $rawQuery = is_string($parsedQuery) ? $parsedQuery : '';
        } else {
            [$rawValue, $rawQuery] = array_pad(explode('?', $value, 2), 2, '');
        }

        $normalizedPath = trim($rawValue, '/');
        if ($normalizedPath === '') {
            return '';
        }

        return $this->buildPrerenderTarget(
            $normalizedPath,
            $this->normalizeSeoQueryForPath($normalizedPath, $rawQuery)
        );
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function splitPrerenderTarget(string $target): array
    {
        if ($target === '') {
            return ['', []];
        }

        [$path, $queryString] = array_pad(explode('?', $target, 2), 2, '');
        $normalizedPath = trim($path, '/');

        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        return [$normalizedPath, is_array($query) ? $query : []];
    }

    private function buildPrerenderTarget(string $path, array $query): string
    {
        $normalizedPath = trim($path, '/');
        if ($normalizedPath === '') {
            return '';
        }

        if ($query === []) {
            return $normalizedPath;
        }

        return $normalizedPath . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function relativeOutputPathForTarget(string $target): string
    {
        [$path, $query] = $this->splitPrerenderTarget($target);

        if ($path === '') {
            return '';
        }

        if (preg_match('/^apoteke\/([^\/]+)$/', $path, $matches)) {
            $citySlug = $matches[1];

            if ($this->queryFlagIsEnabled($query['dezurna_now'] ?? null) && !$this->queryFlagIsEnabled($query['is_24h'] ?? null)) {
                return "__query/apoteke/{$citySlug}/dezurna_now";
            }

            if ($this->queryFlagIsEnabled($query['is_24h'] ?? null) && !$this->queryFlagIsEnabled($query['dezurna_now'] ?? null)) {
                return "__query/apoteke/{$citySlug}/is_24h";
            }
        }

        return $path;
    }

    private function normalizeSeoQueryForPath(string $path, string $queryString): array
    {
        if (!preg_match('/^apoteke\/([^\/]+)$/', $path, $matches)) {
            return [];
        }

        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        if (!is_array($query)) {
            return [];
        }

        return $this->normalizePharmacySeoQuery($query, $matches[1]);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, string>
     */
    private function normalizePharmacySeoQuery(array $query, string $citySlug): array
    {
        $hasDuty = $this->queryFlagIsEnabled($query['dezurna_now'] ?? null);
        $has24Hour = $this->queryFlagIsEnabled($query['is_24h'] ?? null);

        if ($hasDuty === $has24Hour) {
            return [];
        }

        $normalized = [
            'grad' => $citySlug,
        ];

        if ($hasDuty) {
            $normalized['dezurna_now'] = '1';
        }

        if ($has24Hour) {
            $normalized['is_24h'] = '1';
        }

        return $normalized;
    }

    private function queryFlagIsEnabled(mixed $value): bool
    {
        return in_array(
            mb_strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'on'],
            true
        );
    }
}
