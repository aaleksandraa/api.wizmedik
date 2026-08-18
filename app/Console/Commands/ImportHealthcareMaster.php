<?php

namespace App\Console\Commands;

use App\Services\HealthcareImport\HealthcareMasterImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportHealthcareMaster extends Command
{
    protected $signature = 'wizmedik:import-healthcare-master
        {file : Putanja do Excel workbook-a}
        {--dry-run : Analiza i matching bez upisa u klinike/doktore}
        {--only= : Faze odvojene zarezom (institutions,doctors,...)}
        {--chunk=500 : Velicina chunka}
        {--update-existing : Dopuni samo prazna polja na neclaimed profilima}
        {--skip-media : Preskoci media (default)}
        {--force : Potvrdi pravi import bez interaktivnog pitanja}
        {--report= : Direktorij za JSON/CSV report}';

    protected $description = 'Idempotentni import klinika i doktora iz WizMedik Healthcare Master Excel fajla';

    public function handle(HealthcareMasterImporter $importer): int
    {
        $file = (string) $this->argument('file');
        $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only')))));
        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !$this->option('force')) {
            $ok = $this->confirm(
                'Ovo ce upisati javne klinike i doktore. Apoteke, lijekovi i banje se ne diraju. Nastaviti?',
                false
            );
            if (!$ok) {
                $this->warn('Import otkazan.');

                return self::SUCCESS;
            }
        }

        $this->info($dryRun
            ? 'DRY RUN — klinike i doktori se ne upisuju.'
            : 'Pravi import u klinike/doktore. Apoteke i lijekovi se ne diraju.');

        try {
            $result = $importer->import($file, [
                'dry_run' => $dryRun,
                'update_existing' => (bool) $this->option('update-existing'),
                'skip_media' => true,
                'chunk' => (int) $this->option('chunk'),
                'only' => $only,
                'report' => $this->option('report') ?: storage_path('app/import-reports'),
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach ($result->sheets as $sheet => $stats) {
            $rows[] = [
                $sheet,
                $stats['rows'],
                $stats['create'],
                $stats['update'],
                $stats['skip'],
                $stats['review'],
                $stats['failed'],
            ];
        }
        $this->table(['Sheet', 'Rows', 'Create', 'Update', 'Skip', 'Review', 'Failed'], $rows);

        $totals = $result->totals();
        $this->newLine();
        $this->info(sprintf(
            'Ukupno: rows=%d create=%d update=%d skip=%d review=%d failed=%d',
            $totals['rows'],
            $totals['create'],
            $totals['update'],
            $totals['skip'],
            $totals['review'],
            $totals['failed']
        ));

        if ($result->jsonPath) {
            $this->info('JSON report: ' . $result->jsonPath);
        }
        if ($result->csvPath) {
            $this->info('CSV report: ' . $result->csvPath);
        }

        return self::SUCCESS;
    }
}
