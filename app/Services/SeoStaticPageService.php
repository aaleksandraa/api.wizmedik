<?php

namespace App\Services;

use App\Http\Controllers\SeoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeoStaticPageService
{
    public function prerenderPath(string $path): bool
    {
        $normalizedPath = trim($path, '/');
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

            $requestPath = $normalizedPath === '' ? '/' : '/' . $normalizedPath;
            $request = Request::create($requestPath, 'GET');
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
        $normalizedPath = trim($path, '/');
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
        if ($path === '') {
            return $outputDir . DIRECTORY_SEPARATOR . 'index.html';
        }

        return $outputDir
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $path)
            . DIRECTORY_SEPARATOR
            . 'index.html';
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
