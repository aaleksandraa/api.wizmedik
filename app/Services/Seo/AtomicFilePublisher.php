<?php

namespace App\Services\Seo;

use RuntimeException;

class AtomicFilePublisher
{
    /**
     * Replace a file only after the full contents are stored in the same directory.
     * A failed publish leaves any existing target file untouched.
     */
    public function publish(string $targetFile, string $contents): void
    {
        $directory = dirname($targetFile);
        if ($directory === '' || !is_dir($directory)) {
            throw new RuntimeException("Output directory not found: {$directory}");
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("Output directory is not writable: {$directory}");
        }

        $temporary = $directory . DIRECTORY_SEPARATOR . '.seo-' . bin2hex(random_bytes(8)) . '.tmp';
        $bytes = @file_put_contents($temporary, $contents, LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException("Failed to write temporary file: {$temporary}");
        }

        if (@rename($temporary, $targetFile)) {
            return;
        }

        if (is_file($targetFile) && @copy($temporary, $targetFile)) {
            @unlink($temporary);
            return;
        }

        @unlink($temporary);
        throw new RuntimeException("Failed to publish file: {$targetFile}");
    }
}
