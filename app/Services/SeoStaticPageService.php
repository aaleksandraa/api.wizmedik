<?php

namespace App\Services;

use App\Http\Controllers\SeoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeoStaticPageService
{
    public function prerenderPath(string $path): bool
    {
        $normalizedPath = $this->normalizePrerenderTarget($path);
        $outputDirs = $this->outputDirectories();
        if (empty($outputDirs)) {
            Log::warning('SEO prerender skipped: no output directories configured', [
                'path' => $normalizedPath,
            ]);
            return false;
        }

        try {
            $templatePath = $this->resolveTemplatePath($outputDirs[0]);
            if ($templatePath !== null) {
                config(['app.seo_index_template_path' => $templatePath]);
            }

            [$requestPath, $query] = $this->requestComponentsForTarget($normalizedPath);
            $request = Request::create($requestPath, 'GET', $query);
            $response = app(SeoController::class)->index($request);
            $statusCode = (int) $response->getStatusCode();

            // Do not write error pages as static route HTML.
            if ($statusCode >= 400) {
                return false;
            }

            $html = (string) $response->getContent();
            $hasSuccess = false;

            foreach ($outputDirs as $outputDir) {
                if (!is_dir($outputDir) || !is_writable($outputDir)) {
                    Log::warning('SEO prerender skipped: output dir unavailable', [
                        'output_dir' => $outputDir,
                        'path' => $normalizedPath,
                    ]);
                    continue;
                }

                $targetFile = $this->targetFilePath($outputDir, $normalizedPath);
                $targetDir = dirname($targetFile);

                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                    Log::warning('SEO prerender failed: cannot create target directory', [
                        'target_dir' => $targetDir,
                        'path' => $normalizedPath,
                    ]);
                    continue;
                }

                $bytes = @file_put_contents($targetFile, $html);
                if ($bytes === false) {
                    Log::warning('SEO prerender failed: cannot write target file', [
                        'target_file' => $targetFile,
                        'path' => $normalizedPath,
                    ]);
                    continue;
                }

                $hasSuccess = true;
            }

            return $hasSuccess;
        } catch (\Throwable $e) {
            Log::warning('SEO prerender failed', [
                'path' => $normalizedPath,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @param array<int, string> $paths
     */
    public function prerenderPaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }

            $value = trim($path);
            if ($value === '') {
                continue;
            }

            $this->prerenderPath($value);
        }
    }

    public function deletePath(string $path): void
    {
        $normalizedPath = $this->normalizePrerenderTarget($path);
        if ($normalizedPath === '') {
            return;
        }

        foreach ($this->outputDirectories() as $outputDir) {
            $filePath = $this->targetFilePath($outputDir, $normalizedPath);

            if (is_file($filePath)) {
                @unlink($filePath);
            }

            $directory = dirname($filePath);
            $this->removeEmptyDirectoriesUpward($directory, $outputDir);
        }
    }

    /**
     * @return array<int, string>
     */
    private function outputDirectories(): array
    {
        $configured = (string) config('app.sitemap_output_path', base_path('../frontend/dist'));
        $dirs = [];

        $primary = rtrim($configured, DIRECTORY_SEPARATOR);
        if ($primary !== '') {
            $dirs[] = $primary;
        }

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

    private function resolveTemplatePath(string $primaryOutputDir): ?string
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

    private function normalizePrerenderTarget(string $target): string
    {
        $value = trim($target);
        if ($value === '') {
            return '';
        }

        [$path, $query] = $this->splitTarget($value);

        if (preg_match('/^apoteke\/([^\/]+)$/', $path, $matches)) {
            $query = $this->normalizePharmacySeoQuery($query, $matches[1]);
        } else {
            $query = [];
        }

        return $this->buildTarget($path, $query);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function requestComponentsForTarget(string $target): array
    {
        [$path, $query] = $this->splitTarget($target);

        return [$path === '' ? '/' : '/' . $path, $query];
    }

    private function relativeOutputPathForTarget(string $target): string
    {
        [$path, $query] = $this->splitTarget($target);

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

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function splitTarget(string $target): array
    {
        [$path, $queryString] = array_pad(explode('?', trim($target, '/'), 2), 2, '');

        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        return [trim($path, '/'), is_array($query) ? $query : []];
    }

    /**
     * @param array<string, string> $query
     */
    private function buildTarget(string $path, array $query): string
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

    private function removeEmptyDirectoriesUpward(string $directory, string $stopAt): void
    {
        $current = rtrim($directory, DIRECTORY_SEPARATOR);
        $stop = rtrim($stopAt, DIRECTORY_SEPARATOR);

        while ($current !== '' && $current !== $stop) {
            if (!is_dir($current)) {
                break;
            }

            $files = @scandir($current);
            if (!is_array($files)) {
                break;
            }

            $nonDotEntries = array_values(array_diff($files, ['.', '..']));
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
}
