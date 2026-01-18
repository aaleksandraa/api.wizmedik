<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Doktor;
use App\Models\Klinika;
use App\Models\Laboratorija;
use App\Models\Banja;
use App\Models\Specijalnost;
use App\Models\Grad;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm {--force : Force cache refresh}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up the cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cache warming...');

        // Clear cache if force flag is set
        if ($this->option('force')) {
            $this->info('Clearing existing cache...');
            Cache::flush();
        }

        // Warm up specialties
        $this->info('Warming specialties cache...');
        Cache::remember('specialties_all', 3600, function () {
            return Specijalnost::with('children')
                ->whereNull('parent_id')
                ->orderBy('naziv')
                ->get();
        });

        // Warm up cities
        $this->info('Warming cities cache...');
        Cache::remember('cities_all', 3600, function () {
            return Grad::orderBy('naziv')->get();
        });

        // Warm up popular doctors by city
        $this->info('Warming popular doctors cache...');
        $popularCities = ['Sarajevo', 'Banja Luka', 'Tuzla', 'Mostar', 'Zenica'];
        foreach ($popularCities as $city) {
            Cache::remember("doctors_city_{$city}", 600, function () use ($city) {
                return Doktor::where('grad', $city)
                    ->with(['specijalnostModel:id,naziv,slug', 'klinika:id,naziv,slug'])
                    ->orderBy('ocjena', 'desc')
                    ->limit(20)
                    ->get();
            });
        }

        // Warm up top-rated clinics
        $this->info('Warming top clinics cache...');
        Cache::remember('clinics_top_rated', 600, function () {
            return Klinika::active()
                ->orderBy('ocjena', 'desc')
                ->limit(20)
                ->get();
        });

        // Warm up laboratories by city
        $this->info('Warming laboratories cache...');
        foreach ($popularCities as $city) {
            Cache::remember("laboratories_city_{$city}", 600, function () use ($city) {
                return Laboratorija::where('grad', $city)
                    ->aktivan()
                    ->verifikovan()
                    ->limit(20)
                    ->get();
            });
        }

        // Warm up spas
        $this->info('Warming spas cache...');
        Cache::remember('spas_all', 600, function () {
            return Banja::aktivan()
                ->verifikovan()
                ->with(['vrste:id,naziv,slug,ikona'])
                ->orderBy('prosjecna_ocjena', 'desc')
                ->get();
        });

        // Warm up homepage data
        $this->info('Warming homepage cache...');
        Cache::remember('homepage_featured_doctors', 600, function () {
            return Doktor::orderBy('ocjena', 'desc')
                ->with(['specijalnostModel:id,naziv,slug'])
                ->limit(8)
                ->get();
        });

        Cache::remember('homepage_featured_clinics', 600, function () {
            return Klinika::active()
                ->orderBy('ocjena', 'desc')
                ->limit(6)
                ->get();
        });

        $this->info('✓ Cache warming completed successfully!');
        return Command::SUCCESS;
    }
}
