<?php

namespace App\Console\Commands;

use App\Models\RegistrationRequest;
use Illuminate\Console\Command;

class SyncApprovedRegistrationVisibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:sync-visibility
                            {--email= : Filter by registration email}
                            {--request-id= : Process only one registration request ID}
                            {--type=all : doctor, clinic, laboratory, spa, care_home, or all}
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active/verified flags for approved registrations across all profile types.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        $allowedTypes = ['doctor', 'clinic', 'laboratory', 'spa', 'care_home', 'all'];
        if (!in_array($type, $allowedTypes, true)) {
            $this->error("Invalid --type value '{$type}'. Use doctor, clinic, laboratory, spa, care_home, or all.");
            return self::FAILURE;
        }

        $query = RegistrationRequest::query()
            ->where('status', 'approved')
            ->whereIn('type', $type === 'all' ? ['doctor', 'clinic', 'laboratory', 'spa', 'care_home'] : [$type])
            ->with([
                'doctor:id,aktivan,verifikovan,verifikovan_at,verifikovan_by',
                'clinic:id,aktivan,verifikovan,verifikovan_at,verifikovan_by',
                'laboratory:id,aktivan,verifikovan,verifikovan_at',
                'spa:id,aktivan,verifikovan',
                'careHome:id,aktivan,verifikovan',
            ]);

        if ($this->option('request-id')) {
            $query->where('id', (int) $this->option('request-id'));
        }

        if ($this->option('email')) {
            $query->where('email', (string) $this->option('email'));
        }

        $requests = $query->get();
        if ($requests->isEmpty()) {
            $this->warn('No matching approved registration requests found.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updatedDoctors = 0;
        $updatedClinics = 0;
        $updatedLaboratories = 0;
        $updatedSpas = 0;
        $updatedCareHomes = 0;

        foreach ($requests as $registrationRequest) {
            if ($registrationRequest->type === 'doctor' && $registrationRequest->doctor) {
                $doctor = $registrationRequest->doctor;
                $needsUpdate = !$doctor->aktivan || !$doctor->verifikovan;

                if ($needsUpdate) {
                    $payload = [
                        'aktivan' => true,
                        'verifikovan' => true,
                        'verifikovan_at' => $doctor->verifikovan_at ?? now(),
                    ];

                    if (empty($doctor->verifikovan_by) && !empty($registrationRequest->reviewed_by)) {
                        $payload['verifikovan_by'] = $registrationRequest->reviewed_by;
                    }

                    if ($dryRun) {
                        $this->line("DRY-RUN doctor fix: request #{$registrationRequest->id}, doctor #{$doctor->id}");
                    } else {
                        $doctor->update($payload);
                        $updatedDoctors++;
                    }
                }
            }

            if ($registrationRequest->type === 'clinic' && $registrationRequest->clinic) {
                $clinic = $registrationRequest->clinic;
                $needsUpdate = !$clinic->aktivan || !$clinic->verifikovan;

                if ($needsUpdate) {
                    $payload = [
                        'aktivan' => true,
                        'verifikovan' => true,
                        'verifikovan_at' => $clinic->verifikovan_at ?? now(),
                    ];

                    if (empty($clinic->verifikovan_by) && !empty($registrationRequest->reviewed_by)) {
                        $payload['verifikovan_by'] = $registrationRequest->reviewed_by;
                    }

                    if ($dryRun) {
                        $this->line("DRY-RUN clinic fix: request #{$registrationRequest->id}, clinic #{$clinic->id}");
                    } else {
                        $clinic->update($payload);
                        $updatedClinics++;
                    }
                }
            }

            if ($registrationRequest->type === 'laboratory' && $registrationRequest->laboratory) {
                $laboratory = $registrationRequest->laboratory;
                $needsUpdate = !$laboratory->aktivan || !$laboratory->verifikovan;

                if ($needsUpdate) {
                    $payload = [
                        'aktivan' => true,
                        'verifikovan' => true,
                        'verifikovan_at' => $laboratory->verifikovan_at ?? now(),
                    ];

                    if ($dryRun) {
                        $this->line("DRY-RUN laboratory fix: request #{$registrationRequest->id}, laboratory #{$laboratory->id}");
                    } else {
                        $laboratory->update($payload);
                        $updatedLaboratories++;
                    }
                }
            }

            if ($registrationRequest->type === 'spa' && $registrationRequest->spa) {
                $spa = $registrationRequest->spa;
                $needsUpdate = !$spa->aktivan || !$spa->verifikovan;

                if ($needsUpdate) {
                    $payload = [
                        'aktivan' => true,
                        'verifikovan' => true,
                    ];

                    if ($dryRun) {
                        $this->line("DRY-RUN spa fix: request #{$registrationRequest->id}, spa #{$spa->id}");
                    } else {
                        $spa->update($payload);
                        $updatedSpas++;
                    }
                }
            }

            if ($registrationRequest->type === 'care_home' && $registrationRequest->careHome) {
                $careHome = $registrationRequest->careHome;
                $needsUpdate = !$careHome->aktivan || !$careHome->verifikovan;

                if ($needsUpdate) {
                    $payload = [
                        'aktivan' => true,
                        'verifikovan' => true,
                    ];

                    if ($dryRun) {
                        $this->line("DRY-RUN care home fix: request #{$registrationRequest->id}, care home #{$careHome->id}");
                    } else {
                        $careHome->update($payload);
                        $updatedCareHomes++;
                    }
                }
            }
        }

        if ($dryRun) {
            $this->info('Dry run completed. No changes were saved.');
            return self::SUCCESS;
        }

        $this->info("Updated doctors: {$updatedDoctors}");
        $this->info("Updated clinics: {$updatedClinics}");
        $this->info("Updated laboratories: {$updatedLaboratories}");
        $this->info("Updated spas: {$updatedSpas}");
        $this->info("Updated care homes: {$updatedCareHomes}");

        return self::SUCCESS;
    }
}
