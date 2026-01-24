<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Doktor;
use App\Models\Klinika;

class ActivateDoctorsAndClinics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activate:all {--doctors : Only activate doctors} {--clinics : Only activate clinics} {--dry-run : Show what would be activated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate and verify all doctors and clinics in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $onlyDoctors = $this->option('doctors');
        $onlyClinics = $this->option('clinics');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // If no specific option, activate both
        $activateDoctors = !$onlyClinics;
        $activateClinics = !$onlyDoctors;

        // Activate Doctors
        if ($activateDoctors) {
            $this->info('👨‍⚕️ Processing Doctors...');
            $this->activateDoctors($isDryRun);
            $this->newLine();
        }

        // Activate Clinics
        if ($activateClinics) {
            $this->info('🏥 Processing Clinics...');
            $this->activateClinics($isDryRun);
            $this->newLine();
        }

        if ($isDryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ All done! Doctors and clinics have been activated.');
        }

        return Command::SUCCESS;
    }

    /**
     * Activate all doctors
     */
    private function activateDoctors($isDryRun = false)
    {
        // Get current stats
        $totalDoctors = Doktor::whereNull('deleted_at')->count();
        $activeDoctors = Doktor::whereNull('deleted_at')->where('aktivan', true)->count();
        $verifiedDoctors = Doktor::whereNull('deleted_at')->where('verifikovan', true)->count();
        $activeAndVerified = Doktor::whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Doctors', $totalDoctors],
                ['Currently Active', $activeDoctors],
                ['Currently Verified', $verifiedDoctors],
                ['Active & Verified', $activeAndVerified],
            ]
        );

        $toActivate = $totalDoctors - $activeAndVerified;

        if ($toActivate === 0) {
            $this->info('All doctors are already active and verified!');
            return;
        }

        if ($isDryRun) {
            $this->warn("Would activate {$toActivate} doctors");

            // Show which doctors would be activated
            $doctors = Doktor::whereNull('deleted_at')
                ->where(function($q) {
                    $q->where('aktivan', false)
                      ->orWhere('verifikovan', false);
                })
                ->select('id', 'ime', 'prezime', 'slug', 'aktivan', 'verifikovan')
                ->get();

            if ($doctors->count() > 0) {
                $this->newLine();
                $this->line('Doctors that would be activated:');
                $this->table(
                    ['ID', 'Name', 'Slug', 'Active', 'Verified'],
                    $doctors->map(fn($d) => [
                        $d->id,
                        "{$d->ime} {$d->prezime}",
                        $d->slug,
                        $d->aktivan ? '✓' : '✗',
                        $d->verifikovan ? '✓' : '✗',
                    ])
                );
            }
        } else {
            if ($this->confirm("Activate {$toActivate} doctors?", true)) {
                $updated = Doktor::whereNull('deleted_at')
                    ->update([
                        'aktivan' => true,
                        'verifikovan' => true,
                        'verifikovan_at' => now(),
                    ]);

                $this->info("✅ Activated {$updated} doctors");
            } else {
                $this->warn('Skipped doctors activation');
            }
        }
    }

    /**
     * Activate all clinics
     */
    private function activateClinics($isDryRun = false)
    {
        // Get current stats
        $totalClinics = Klinika::whereNull('deleted_at')->count();
        $activeClinics = Klinika::whereNull('deleted_at')->where('aktivan', true)->count();
        $verifiedClinics = Klinika::whereNull('deleted_at')->where('verifikovan', true)->count();
        $activeAndVerified = Klinika::whereNull('deleted_at')
            ->where('aktivan', true)
            ->where('verifikovan', true)
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Clinics', $totalClinics],
                ['Currently Active', $activeClinics],
                ['Currently Verified', $verifiedClinics],
                ['Active & Verified', $activeAndVerified],
            ]
        );

        $toActivate = $totalClinics - $activeAndVerified;

        if ($toActivate === 0) {
            $this->info('All clinics are already active and verified!');
            return;
        }

        if ($isDryRun) {
            $this->warn("Would activate {$toActivate} clinics");

            // Show which clinics would be activated
            $clinics = Klinika::whereNull('deleted_at')
                ->where(function($q) {
                    $q->where('aktivan', false)
                      ->orWhere('verifikovan', false);
                })
                ->select('id', 'naziv', 'slug', 'grad', 'aktivan', 'verifikovan')
                ->get();

            if ($clinics->count() > 0) {
                $this->newLine();
                $this->line('Clinics that would be activated:');
                $this->table(
                    ['ID', 'Name', 'City', 'Slug', 'Active', 'Verified'],
                    $clinics->map(fn($c) => [
                        $c->id,
                        $c->naziv,
                        $c->grad,
                        $c->slug,
                        $c->aktivan ? '✓' : '✗',
                        $c->verifikovan ? '✓' : '✗',
                    ])
                );
            }
        } else {
            if ($this->confirm("Activate {$toActivate} clinics?", true)) {
                $updated = Klinika::whereNull('deleted_at')
                    ->update([
                        'aktivan' => true,
                        'verifikovan' => true,
                        'verifikovan_at' => now(),
                    ]);

                $this->info("✅ Activated {$updated} clinics");
            } else {
                $this->warn('Skipped clinics activation');
            }
        }
    }
}
