<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SitemapController;

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
    public function handle()
    {
        $this->info('🗺️  Generating sitemap files...');
        $this->newLine();

        // Determine output directory
        $outputDir = $this->option('output') ?: base_path('../frontend/dist');

        // Check if directory exists
        if (!is_dir($outputDir)) {
            $this->error("❌ Output directory not found: {$outputDir}");
            $this->info("💡 Specify output directory with --output option");
            $this->info("   Example: php artisan sitemap:generate --output=/var/www/html");
            return 1;
        }

        // Check if directory is writable
        if (!is_writable($outputDir)) {
            $this->error("❌ Output directory is not writable: {$outputDir}");
            return 1;
        }

        $this->info("📁 Output directory: {$outputDir}");
        $this->newLine();

        $controller = new SitemapController();

        $sitemaps = [
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
            'sitemap-cities.xml' => 'cities',
            'sitemap-blog.xml' => 'blog',
            'sitemap-pitanja.xml' => 'questions',
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($sitemaps as $filename => $method) {
            try {
                $this->info("⏳ Generating {$filename}...");

                $response = $controller->$method();
                $content = $response->getContent();

                $filepath = "{$outputDir}/{$filename}";
                file_put_contents($filepath, $content);

                $size = filesize($filepath);
                $sizeKb = round($size / 1024, 2);

                $this->info("   ✅ Generated: {$filename} ({$sizeKb} KB)");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("   ❌ Failed: {$filename}");
                $this->error("   Error: " . $e->getMessage());

                // Create empty sitemap to prevent 404 errors
                $baseUrl = config('app.frontend_url', 'https://wizmedik.com');
                $emptyXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

                if ($filename === 'sitemap.xml') {
                    // Empty sitemap index
                    $emptyXml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                    $emptyXml .= '<!-- This sitemap is empty due to generation error -->' . "\n";
                    $emptyXml .= '</sitemapindex>';
                } else {
                    // Empty urlset
                    $emptyXml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                    $emptyXml .= '<!-- This sitemap is empty due to generation error -->' . "\n";
                    $emptyXml .= '</urlset>';
                }

                $filepath = "{$outputDir}/{$filename}";
                file_put_contents($filepath, $emptyXml);
                $this->warn("   ⚠️  Created empty sitemap to prevent 404 errors");

                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   ✅ Success: {$successCount}");

        if ($errorCount > 0) {
            $this->error("   ❌ Failed: {$errorCount}");
        }

        $this->newLine();

        if ($errorCount === 0) {
            $this->info('🎉 All sitemaps generated successfully!');
            $this->newLine();
            $this->info('📝 Next steps:');
            $this->info('   1. Deploy the generated files to your web server');
            $this->info('   2. Test: curl https://wizmedik.com/sitemap.xml');
            $this->info('   3. Submit to Google Search Console');
            $this->newLine();
            $this->info('💡 Tip: Set up a cron job to regenerate sitemaps daily:');
            $this->info('   0 2 * * * cd /path/to/backend && php artisan sitemap:generate');
            return 0;
        } else {
            $this->error('⚠️  Some sitemaps failed to generate. Check errors above.');
            return 1;
        }
    }
}
