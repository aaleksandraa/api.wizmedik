<?php

namespace Database\Seeders;

use App\Services\Pharmacies\ApprovedPharmacyImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApprovedPharmaciesImportSeeder extends Seeder
{
    public function run(): void
    {
        $file = trim((string) env('PHARMACY_IMPORT_FILE', 'banja-luka-approved.json'));
        $publishNew = filter_var(env('PHARMACY_IMPORT_PUBLISH_NEW', true), FILTER_VALIDATE_BOOLEAN);
        $path = $this->resolveImportPath($file);

        $counts = app(ApprovedPharmacyImportService::class)->importFromFile(
            $path,
            env('PHARMACY_IMPORT_ADMIN_EMAIL'),
            $publishNew
        );

        $this->command?->info(sprintf(
            'Approved pharmacy import zavrsen. Total: %d, inserted: %d, skipped: %d, failed: %d.',
            $counts['total'],
            $counts['inserted'],
            $counts['skipped'],
            $counts['failed']
        ));
    }

    private function resolveImportPath(string $file): string
    {
        if ($file === '') {
            $file = 'banja-luka-approved.json';
        }

        if (Str::startsWith($file, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $file) === 1) {
            return $file;
        }

        $candidates = [
            database_path('seeders/data/pharmacies/' . $file),
            storage_path('app/imports/approved/' . $file),
            storage_path('app/imports/pharmacies/' . $file),
            base_path($file),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }
}
