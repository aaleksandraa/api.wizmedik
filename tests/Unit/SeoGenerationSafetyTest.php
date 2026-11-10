<?php

namespace Tests\Unit;

use App\Services\Seo\AtomicFilePublisher;
use App\Services\Seo\DoctorSnapshotHtml;
use App\Services\Seo\SeoPrerenderPolicy;
use App\Services\Seo\SitemapXml;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SeoGenerationSafetyTest extends TestCase
{
    private string $directory = '';

    protected function tearDown(): void
    {
        if ($this->directory !== '' && is_dir($this->directory)) {
            $this->removeDirectory($this->directory);
        }

        parent::tearDown();
    }

    public function test_publish_writes_complete_content_and_removes_the_temporary_file(): void
    {
        $this->directory = $this->makeDirectory();
        $target = $this->directory . DIRECTORY_SEPARATOR . 'sitemap-doctors.xml';
        $contents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset><loc>https://wizmedik.com/doktor/ana&amp;iva</loc></urlset>";

        (new AtomicFilePublisher())->publish($target, $contents);

        $this->assertSame($contents, file_get_contents($target));
        $this->assertSame([], $this->temporaryFiles($this->directory));
    }

    public function test_publish_replaces_a_file_without_leaving_the_previous_partial_contents(): void
    {
        $this->directory = $this->makeDirectory();
        $target = $this->directory . DIRECTORY_SEPARATOR . 'index.html';
        file_put_contents($target, '<h1>Old</h1>');

        (new AtomicFilePublisher())->publish($target, '<h1>Dr. Ana Ivić</h1>');

        $this->assertSame('<h1>Dr. Ana Ivić</h1>', file_get_contents($target));
        $this->assertSame([], $this->temporaryFiles($this->directory));
    }

    public function test_missing_output_directory_fails_and_does_not_create_it(): void
    {
        $this->directory = $this->makeDirectory();
        $missing = $this->directory . DIRECTORY_SEPARATOR . 'missing' . DIRECTORY_SEPARATOR . 'sitemap.xml';

        try {
            (new AtomicFilePublisher())->publish($missing, '<urlset></urlset>');
            $this->fail('Missing directory should fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Output directory not found', $exception->getMessage());
        }

        $this->assertDirectoryDoesNotExist($this->directory . DIRECTORY_SEPARATOR . 'missing');
    }

    public function test_failed_publish_preserves_the_previous_file(): void
    {
        $this->directory = $this->makeDirectory();
        $target = $this->directory . DIRECTORY_SEPARATOR . 'sitemap.xml';
        file_put_contents($target, '<sitemapindex>previous</sitemapindex>');
        $publisher = new class extends AtomicFilePublisher {
            public function publish(string $targetFile, string $contents): void
            {
                throw new RuntimeException('disk full');
            }
        };

        try {
            $publisher->publish($target, '<sitemapindex>broken</sitemapindex>');
            $this->fail('Write failure should be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('disk full', $exception->getMessage());
        }

        $this->assertSame('<sitemapindex>previous</sitemapindex>', file_get_contents($target));
    }

    public function test_stale_files_are_removed_only_after_a_complete_reconciliation(): void
    {
        $this->assertTrue(SeoPrerenderPolicy::allowsStaleDeletion(true, false, 0));
        $this->assertFalse(SeoPrerenderPolicy::allowsStaleDeletion(true, true, 0));
        $this->assertFalse(SeoPrerenderPolicy::allowsStaleDeletion(true, false, 2));
        $this->assertFalse(SeoPrerenderPolicy::allowsStaleDeletion(false, false, 0));
        $this->assertFalse(SeoPrerenderPolicy::shouldPublish(404));
        $this->assertFalse(SeoPrerenderPolicy::shouldPublish(500));
        $this->assertTrue(SeoPrerenderPolicy::shouldPublish(200));
    }

    public function test_sitemap_timestamps_use_stored_dates_and_escape_urls(): void
    {
        $this->assertNull(SitemapXml::lastModified(null));
        $this->assertNull(SitemapXml::lastModified(''));
        $formatted = SitemapXml::lastModified('2026-08-30 00:00:00 UTC');
        $this->assertIsString($formatted);
        $this->assertStringContainsString('2026-08-30', $formatted);
        $this->assertNotSame(date('c'), $formatted);
        $this->assertSame('https://wizmedik.com/doktor/a&amp;b', SitemapXml::escape('https://wizmedik.com/doktor/a&b'));
    }

    public function test_doctor_snapshot_for_a_complete_profile_has_one_heading_and_canonical_links(): void
    {
        $html = DoctorSnapshotHtml::render(
            $this->doctor('Ana', 'Ivić', 'Kardiologija', 'Sarajevo', 'Pregled srca.'),
            'https://wizmedik.com/doktor/ana-ivic',
            'https://wizmedik.com/doktori',
            'ana-ivic'
        );

        $this->assertSame(1, substr_count($html, '<h1>'));
        $this->assertSame(1, substr_count($html, '<main'));
        $this->assertStringContainsString('data-doctor-slug="ana-ivic"', $html);
        $this->assertStringContainsString('href="https://wizmedik.com/doktor/ana-ivic"', $html);
        $this->assertStringContainsString('href="https://wizmedik.com/doktori"', $html);
        $this->assertStringNotContainsString('061', $html);
        $this->assertStringNotContainsString('ana@example.com', $html);
    }

    public function test_doctor_snapshot_escapes_special_characters_and_skips_incomplete_names(): void
    {
        $html = DoctorSnapshotHtml::render(
            $this->doctor('Ana', 'Ivić', 'Srce & pluća', 'Sarajevo', '<script>alert("x")</script> Opis'),
            'https://wizmedik.com/doktor/ana-ivic?x=1&y=2',
            'https://wizmedik.com/doktori',
            'ana"ivic'
        );

        $this->assertStringContainsString('Srce &amp; pluća', $html);
        $this->assertStringContainsString('alert(&quot;x&quot;) Opis', $html);
        $this->assertStringContainsString('data-doctor-slug="ana&quot;ivic"', $html);
        $this->assertStringContainsString('href="https://wizmedik.com/doktor/ana-ivic?x=1&amp;y=2"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame('', DoctorSnapshotHtml::render($this->doctor('', 'Ivić', '', '', ''), 'https://wizmedik.com/doktor/x', 'https://wizmedik.com/doktori', 'x'));
    }

    public function test_scheduler_passes_the_output_directory_without_manual_shell_escaping(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/routes/console.php');

        $this->assertIsString($source);
        $this->assertStringNotContainsString('escapeshellarg', $source);
        $this->assertStringContainsString("'--output' => \$seoOutputPath", $source);
    }

    private function doctor(string $first, string $last, string $specialty, string $city, string $biography): object
    {
        return (object) [
            'ime' => $first,
            'prezime' => $last,
            'specijalnost' => $specialty,
            'grad' => $city,
            'opis' => $biography,
            'telefon' => '061-000-000',
            'email' => 'ana@example.com',
            'public_email' => 'ana@example.com',
        ];
    }

    private function makeDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wizmedik-seo-' . bin2hex(random_bytes(6));
        mkdir($directory);

        return $directory;
    }

    /**
     * @return array<int, string>
     */
    private function temporaryFiles(string $directory): array
    {
        $entries = scandir($directory);
        if (!is_array($entries)) {
            return [];
        }

        return array_values(array_filter(
            $entries,
            static fn (string $entry): bool => str_contains($entry, '.seo-') || str_ends_with($entry, '.tmp')
        ));
    }

    private function removeDirectory(string $directory): void
    {
        $entries = scandir($directory);
        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
