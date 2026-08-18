<?php

namespace App\Services\HealthcareImport;

use App\Models\HealthcareImportBatch;
use App\Services\HealthcareImport\Importers\ContactImporter;
use App\Services\HealthcareImport\Importers\DoctorImporter;
use App\Services\HealthcareImport\Importers\DoctorInstitutionImporter;
use App\Services\HealthcareImport\Importers\DoctorSpecialtyImporter;
use App\Services\HealthcareImport\Importers\InstitutionImporter;
use App\Services\HealthcareImport\Importers\InstitutionServiceImporter;
use App\Services\HealthcareImport\Importers\InstitutionSpecialtyImporter;
use App\Services\HealthcareImport\Importers\LocationImporter;
use App\Services\HealthcareImport\Importers\WorkingHoursImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class HealthcareMasterImporter
{
    /**
     * @param  array{
     *     dry_run?: bool,
     *     update_existing?: bool,
     *     skip_media?: bool,
     *     chunk?: int,
     *     only?: list<string>,
     *     report?: string
     * }  $options
     */
    public function import(string $path, array $options = []): ImportResult
    {
        $absolute = $this->resolvePath($path);
        $context = new ImportContext();
        $context->dryRun = (bool) ($options['dry_run'] ?? true);
        $context->updateExisting = (bool) ($options['update_existing'] ?? false);
        $context->skipMedia = (bool) ($options['skip_media'] ?? true);
        $context->chunk = max(50, (int) ($options['chunk'] ?? 500));
        $context->only = $options['only'] ?? [];
        $context->filename = basename($absolute);
        $context->fileHash = hash_file('sha256', $absolute);
        $context->canPersistAudit = !$context->dryRun
            && Schema::hasTable('healthcare_import_batches')
            && Schema::hasTable('healthcare_import_rows');
        $context->workbook = new WorkbookReader();
        $context->workbook->load($absolute);
        $context->preloadLookups();

        if (!$context->dryRun && !$context->canPersistAudit) {
            throw new RuntimeException(
                'Audit tabele nisu migrirane. Pokrenite samo nove healthcare_import migracije prije pravog importa.'
            );
        }

        if ($context->canPersistAudit) {
            $batch = HealthcareImportBatch::query()->create([
                'uuid' => $context->uuid,
                'source_filename' => $context->filename,
                'source_hash' => $context->fileHash,
                'status' => 'running',
                'dry_run' => $context->dryRun,
                'started_at' => now(),
                'metadata' => [
                    'only' => $context->only,
                    'update_existing' => $context->updateExisting,
                ],
            ]);
            $context->batchId = (int) $batch->id;
        }

        $phases = [
            'institutions' => fn () => (new InstitutionImporter($context))->import(),
            'locations' => fn () => (new LocationImporter($context))->import(),
            'doctors' => fn () => (new DoctorImporter($context))->import(),
            'doctor-institutions' => fn () => (new DoctorInstitutionImporter($context))->import(),
            'doctor-specialities' => fn () => (new DoctorSpecialtyImporter($context))->import(),
            'institution-specialities' => fn () => (new InstitutionSpecialtyImporter($context))->import(),
            'institution-services' => fn () => (new InstitutionServiceImporter($context))->import(),
            'contacts' => fn () => (new ContactImporter($context))->import(),
            'working-hours' => fn () => (new WorkingHoursImporter($context))->import(),
        ];

        try {
            $runPhases = function () use ($phases, $context): void {
                foreach ($phases as $phase => $runner) {
                    if ($context->shouldRun($phase)) {
                        $runner();
                    }
                }
            };

            if ($context->dryRun) {
                $runPhases();
            } else {
                DB::transaction($runPhases);
            }
        } catch (Throwable $e) {
            if ($context->batchId) {
                HealthcareImportBatch::query()->whereKey($context->batchId)->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'metadata' => [
                        'error' => $e->getMessage(),
                    ],
                ]);
            }
            throw $e;
        }

        $reportDir = $options['report'] ?? storage_path('app/import-reports');
        $paths = (new ImportReportWriter())->write($context, $reportDir);
        $totals = $context->result->totals();

        if ($context->batchId) {
            HealthcareImportBatch::query()->whereKey($context->batchId)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_rows' => $totals['rows'],
                'created_rows' => $totals['create'],
                'updated_rows' => $totals['update'],
                'skipped_rows' => $totals['skip'],
                'review_rows' => $totals['review'],
                'failed_rows' => $totals['failed'],
                'report_path' => $paths['json'],
            ]);
        }

        return $context->result;
    }

    private function resolvePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $fromBase = base_path($path);
        if (is_file($fromBase)) {
            return $fromBase;
        }

        throw new RuntimeException("Excel fajl nije pronadjen: {$path}");
    }
}
