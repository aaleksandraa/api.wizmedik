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
        $outputDir = $this->outputDirectory();

        if (!is_dir($outputDir) || !is_writable($outputDir)) {
            Log::warning('SEO prerender skipped: output dir unavailable', [
                'output_dir' => $outputDir,
                'path' => $normalizedPath,
            ]);
            return false;
        }

        try {
            $requestPath = $normalizedPath === '' ? '/' : '/' . $normalizedPath;
            $request = Request::create($requestPath, 'GET');
            $response = app(SeoController::class)->index($request);
            $statusCode = (int) $response->getStatusCode();

            // Do not write error pages as static route HTML.
            if ($statusCode >= 400) {
                return false;
            }

            $targetFile = $this->targetFilePath($outputDir, $normalizedPath);
            $targetDir = dirname($targetFile);

            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                Log::warning('SEO prerender failed: cannot create target directory', [
                    'target_dir' => $targetDir,
                    'path' => $normalizedPath,
                ]);
                return false;
            }

            file_put_contents($targetFile, (string) $response->getContent());
            return true;
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

        $outputDir = $this->outputDirectory();
        $filePath = $this->targetFilePath($outputDir, $normalizedPath);

        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $directory = dirname($filePath);
        $this->removeEmptyDirectoriesUpward($directory, $outputDir);
    }

    private function outputDirectory(): string
    {
        $configured = (string) config('app.sitemap_output_path', base_path('../frontend/dist'));
        return rtrim($configured, DIRECTORY_SEPARATOR);
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

