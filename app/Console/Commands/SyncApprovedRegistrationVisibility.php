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
                            {--type=all : doctor, clinic, or all}
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active/verified flags for approved doctor and clinic registrations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        if (!in_array($type, ['doctor', 'clinic', 'all'], true)) {
            $this->error("Invalid --type value '{$type}'. Use doctor, clinic, or all.");
            return self::FAILURE;
        }

        $query = RegistrationRequest::query()
            ->where('status', 'approved')
            ->whereIn('type', $type === 'all' ? ['doctor', 'clinic'] : [$type])
            ->with([
                'doctor:id,aktivan,verifikovan,verifikovan_at,verifikovan_by',
                'clinic:id,aktivan,verifikovan,verifikovan_at,verifikovan_by',
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
        }

        if ($dryRun) {
            $this->info('Dry run completed. No changes were saved.');
            return self::SUCCESS;
        }

        $this->info("Updated doctors: {$updatedDoctors}");
        $this->info("Updated clinics: {$updatedClinics}");

        return self::SUCCESS;
    }
}

