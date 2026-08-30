<?php

namespace App\Console\Commands;

use App\Models\Banja;
use App\Models\Indikacija;
use App\Models\Terapija;
use App\Models\VrstaBanje;
use App\Services\SpasHomes\WizMedikFacilityImportSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ImportWizMedikBanje extends Command
{
    use WizMedikFacilityImportSupport;

    protected $signature = 'wizmedik:import-banje
        {file : Path to JSON payload}
        {--dry-run : Validate and resolve mappings without writing}';

    protected $description = 'Insert-only import of researched WizMedik banje from JSON (never overwrites claimed profiles)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows)) {
            $this->error('Invalid JSON payload.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $created = $skippedExists = $skippedClaimed = $skippedGate = $errors = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors++;
                continue;
            }

            $sourceId = (string) data_get($row, 'source_id', '#'.($index + 1));
            $gate = (string) data_get($row, '_research.import_gate', 'IMPORT_READY');

            if (!in_array($gate, ['IMPORT_READY_FULL', 'IMPORT_READY_CORE', 'IMPORT_READY'], true)) {
                $skippedGate++;
                $this->warn("SKIP {$sourceId}: gate={$gate}");

                continue;
            }

            try {
                $result = $this->importRow($row, $dryRun);
                match ($result) {
                    'created' => $created++,
                    'skipped_exists' => $skippedExists++,
                    'skipped_claimed' => $skippedClaimed++,
                    default => throw new RuntimeException("Unknown import result: {$result}"),
                };

                if ($result === 'created') {
                    $this->info(($dryRun ? 'OK ' : 'IMPORTED ')."{$sourceId}: {$row['naziv']}");
                } else {
                    $this->line("SKIP {$sourceId} ({$result}): {$row['naziv']}");
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("ERROR {$sourceId}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['created', 'skipped_exists', 'skipped_claimed', 'skipped_gate', 'errors', 'dry_run'],
            [[$created, $skippedExists, $skippedClaimed, $skippedGate, $errors, $dryRun ? 'yes' : 'no']]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(array $row, bool $dryRun): string
    {
        $vrsteSlugs = $this->decodeJsonArray($row['vrste_slugs'] ?? []);
        $indikacijeSlugs = $this->decodeJsonArray($row['indikacije_slugs'] ?? []);
        $terapijeSlugs = $this->decodeJsonArray($row['terapije_slugs'] ?? []);

        $vrsteIds = VrstaBanje::query()->whereIn('slug', $vrsteSlugs)->pluck('id')->all();
        $indikacijeIds = Indikacija::query()->whereIn('slug', $indikacijeSlugs)->pluck('id')->all();
        $terapijeIds = Terapija::query()->whereIn('slug', $terapijeSlugs)->pluck('id')->all();

        if ($vrsteIds === [] || $indikacijeIds === [] || $terapijeIds === []) {
            $missing = [];
            if ($vrsteIds === []) {
                $missing[] = 'vrste: '.implode(', ', $vrsteSlugs);
            }
            if ($indikacijeIds === []) {
                $missing[] = 'indikacije: '.implode(', ', $indikacijeSlugs);
            }
            if ($terapijeIds === []) {
                $missing[] = 'terapije: '.implode(', ', $terapijeSlugs);
            }

            throw new RuntimeException('unresolved required taxonomy ('.implode('; ', $missing).'). Run BanjeLegacyTaxonomySeeder if needed.');
        }

        $data = collect($row)->except([
            'source_id',
            'vrste_slugs',
            'indikacije_slugs',
            'terapije_slugs',
            '_research',
        ])->toArray();

        $data['user_id'] = null;

        $validator = Validator::make($data, [
            'naziv' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'grad' => 'required|string|max:100',
            'regija' => 'nullable|string|max:100',
            'adresa' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'telefon' => 'nullable|string|max:50|regex:/^[\d\s\+\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'required|string|max:1000',
            'detaljni_opis' => 'nullable|string|max:10000',
            'medicinski_nadzor' => 'boolean',
            'fizijatar_prisutan' => 'boolean',
            'medicinsko_osoblje' => 'nullable|string|max:500',
            'ima_smjestaj' => 'boolean',
            'broj_kreveta' => 'nullable|integer|min:0|max:1000',
            'online_rezervacija' => 'boolean',
            'online_upit' => 'boolean',
            'verifikovan' => 'boolean',
            'aktivan' => 'boolean',
            'radno_vrijeme' => 'nullable|array',
            'galerija' => 'nullable|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first() ?? 'validation failed');
        }

        $validated = $validator->validated();
        $existing = $this->findExistingFacility(
            Banja::class,
            (string) $validated['slug'],
            (string) $validated['naziv'],
            (string) $validated['grad']
        );

        if ($existing) {
            if ($this->isClaimedByRealOwner($existing)) {
                return 'skipped_claimed';
            }

            return 'skipped_exists';
        }

        if ($dryRun) {
            return 'created';
        }

        DB::transaction(function () use ($validated, $vrsteIds, $indikacijeIds, $terapijeIds): void {
            $slug = $this->uniqueSlugForModel(Banja::class, (string) $validated['slug']);
            $validated['slug'] = $slug;
            $validated['user_id'] = null;
            $validated['prosjecna_ocjena'] = 0;
            $validated['broj_recenzija'] = 0;
            $validated['broj_pregleda'] = 0;

            $banja = Banja::create($validated);
            $banja->vrste()->sync($vrsteIds);

            $indikacijeSync = [];
            foreach ($indikacijeIds as $priority => $id) {
                $indikacijeSync[$id] = ['prioritet' => $priority + 1, 'napomena' => null];
            }
            $banja->indikacije()->sync($indikacijeSync);
            $banja->terapije()->sync($terapijeIds);
        });

        return 'created';
    }
}
