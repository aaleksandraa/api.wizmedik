<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\HomepageController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm {--force : Forget the warmed keys before rebuilding}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up the cache for the heaviest public endpoints';

    /**
     * Execute the console command.
     *
     * Previous versions warmed bespoke keys (specialties_all, homepage_featured_*,
     * doctors_city_*, ...) that no controller ever read, so the work was wasted
     * and the real controller caches stayed cold. We now warm the exact keys the
     * controllers use by invoking their public entry points, which guarantees the
     * warmed keys and the read keys can never drift apart.
     */
    public function handle(): int
    {
        $this->info('Starting cache warming...');

        if ($this->option('force')) {
            // Forget only the keys we are about to rebuild - never flush the
            // whole cache store (that would wipe every other app cache too).
            $this->info('Forgetting warmed keys...');
            Cache::forget('homepage:data:v2');
            Cache::forget('clinics:list:limit:1000');
        }

        $this->info('Warming homepage cache...');
        try {
            app(HomepageController::class)->getData();
            $this->info('  homepage:data:v2 ready');
        } catch (\Throwable $e) {
            $this->error('  homepage warm failed: ' . $e->getMessage());
        }

        $this->info('Warming clinics listing cache...');
        try {
            $request = Request::create('/api/clinics', 'GET', ['limit' => 1000]);
            app(ClinicController::class)->index($request);
            $this->info('  clinics:list:limit:1000 ready');
        } catch (\Throwable $e) {
            $this->error('  clinics warm failed: ' . $e->getMessage());
        }

        $this->info('Cache warming completed.');

        return Command::SUCCESS;
    }
}
