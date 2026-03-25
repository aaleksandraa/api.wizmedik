<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--output= : Output directory path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate static sitemap XML files for frontend deployment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating sitemap files...');
        $this->newLine();

        $primaryOutputDir = (string) ($this->option('output') ?: config('app.sitemap_output_path', base_path('../frontend/dist')));
        $outputDirs = $this->resolveOutputDirs($primaryOutputDir);

        foreach ($outputDirs as $outputDir) {
            if (!is_dir($outputDir)) {
                $this->error("Output directory not found: {$outputDir}");
                $this->info('Specify output directory with --output option');
                $this->info('Example: php artisan sitemap:generate --output=/var/www/html');
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
        $this->newLine();

        $controller = app(SitemapController::class);
        $sitemaps = config('sitemaps.generators', [
            'sitemap.xml' => 'index',
            'sitemap-pages.xml' => 'pages',
            'sitemap-doctors.xml' => 'doctors',
            'sitemap-clinics.xml' => 'clinics',
            'sitemap-laboratories.xml' => 'laboratories',
            'sitemap-lijekovi.xml' => 'medicines',
            'sitemap-pharmacies.xml' => 'pharmacies',
            'sitemap-spas.xml' => 'spas',
            'sitemap-care-homes.xml' => 'careHomes',
            'sitemap-doctor-city-specialties.xml' => 'doctorCitySpecialties',
            'sitemap-specialties.xml' => 'specialties',
            'sitemap-service-pages.xml' => 'servicePages',
            'sitemap-cities.xml' => 'cities',
            'sitemap-blog.xml' => 'blog',
            'sitemap-pitanja.xml' => 'questions',
        ]);

        $successCount = 0;
        $errorCount = 0;

        foreach ($sitemaps as $filename => $method) {
            try {
                $this->info("Generating {$filename}...");

                $response = $controller->{$method}();
                $content = (string) $response->getContent();

                $primaryFilePath = null;
                foreach ($outputDirs as $outputDir) {
                    $filePath = "{$outputDir}/{$filename}";
                    file_put_contents($filePath, $content);
                    if ($primaryFilePath === null) {
                        $primaryFilePath = $filePath;
                    }
                }

                $size = $primaryFilePath ? (int) filesize($primaryFilePath) : 0;
                $sizeKb = round($size / 1024, 2);

                $this->info("   Generated: {$filename} ({$sizeKb} KB)");
                $successCount++;
            } catch (\Throwable $e) {
                $this->error("   Failed: {$filename}");
                $this->error('   Error: ' . $e->getMessage());

                $emptyXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                if ($filename === 'sitemap.xml') {
                    $emptyXml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                    $emptyXml .= '<!-- This sitemap is empty due to generation error -->' . "\n";
                    $emptyXml .= '</sitemapindex>';
                } else {
                    $emptyXml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                    $emptyXml .= '<!-- This sitemap is empty due to generation error -->' . "\n";
                    $emptyXml .= '</urlset>';
                }

                foreach ($outputDirs as $outputDir) {
                    $filePath = "{$outputDir}/{$filename}";
                    file_put_contents($filePath, $emptyXml);
                }

                $this->warn('   Created empty sitemap to prevent 404 errors');
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("   Success: {$successCount}");
        if ($errorCount > 0) {
            $this->error("   Failed: {$errorCount}");
        }

        $this->newLine();
        if ($errorCount === 0) {
            $this->info('All sitemaps generated successfully.');
            $this->newLine();
            $this->info('Next steps:');
            $this->info('   1. Ensure web server serves one of the generated output directories');
            $this->info('   2. Test: curl https://wizmedik.com/sitemap.xml');
            $this->info('   3. Submit to Google Search Console');
            $this->newLine();
            $this->info('Tip: set up a cron job to regenerate sitemaps daily');
            $this->info('   0 2 * * * cd /path/to/backend && php artisan sitemap:generate');
            return self::SUCCESS;
        }

        $this->error('Some sitemaps failed to generate. Check errors above.');
        return self::FAILURE;
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
}
