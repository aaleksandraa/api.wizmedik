<?php

namespace App\Console\Commands;

use App\Models\Dom;
use App\Models\MedicinskUsluga;
use App\Models\NivoNjege;
use App\Models\ProgramNjege;
use App\Models\SmjestajUslov;
use App\Models\TipDoma;
use App\Services\SpasHomes\WizMedikFacilityImportSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class ImportWizMedikDomovi extends Command
{
    use WizMedikFacilityImportSupport;

    protected $signature = 'wizmedik:import-domovi
        {file : Path to JSON payload}
        {--dry-run : Validate and resolve mappings without writing}
        {--include-hold : Also import HOLD_QA rows as inactive/unverified}';

    protected $description = 'Insert-only import of researched WizMedik care homes from JSON (never overwrites claimed profiles)';

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
        $created = $skippedExists = $skippedClaimed = $skippedHold = $errors = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors++;
                continue;
            }

            $sourceId = (string) data_get($row, 'source_id', '#'.($index + 1));
            $gate = (string) data_get($row, '_research.import_gate', 'IMPORT_READY');

            if ($gate === 'HOLD_QA' && !$this->option('include-hold')) {
                $skippedHold++;
                $this->warn("SKIP {$sourceId}: HOLD_QA");

                continue;
            }

            try {
                $result = $this->importRow($row, $dryRun, $gate === 'HOLD_QA');
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
            ['created', 'skipped_exists', 'skipped_claimed', 'skipped_hold', 'errors', 'dry_run'],
            [[$created, $skippedExists, $skippedClaimed, $skippedHold, $errors, $dryRun ? 'yes' : 'no']]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRow(array $row, bool $dryRun, bool $isHold): string
    {
        $tip = TipDoma::query()->where('slug', $row['tip_doma_slug'] ?? null)->first();
        $nivo = NivoNjege::query()->where('slug', $row['nivo_njege_slug'] ?? null)->first();
        $programIds = ProgramNjege::query()
            ->whereIn('slug', $row['programi_njege_slugs'] ?? [])
            ->pluck('id')
            ->all();
        $medicalIds = MedicinskUsluga::query()
            ->whereIn('slug', $row['medicinske_usluge_slugs'] ?? [])
            ->pluck('id')
            ->all();
        $smjestajIds = SmjestajUslov::query()
            ->whereIn('slug', $row['smjestaj_uslovi_slugs'] ?? [])
            ->pluck('id')
            ->all();

        if (!$tip || !$nivo || $programIds === [] || $medicalIds === []) {
            throw new RuntimeException('unresolved required taxonomy.');
        }

        $data = collect($row)->except([
            'source_id',
            'tip_doma_slug',
            'nivo_njege_slug',
            'programi_njege_slugs',
            'medicinske_usluge_slugs',
            'smjestaj_uslovi_slugs',
            '_research',
        ])->toArray();

        $data['tip_doma_id'] = $tip->id;
        $data['nivo_njege_id'] = $nivo->id;
        $data['user_id'] = null;

        if ($isHold) {
            $data['aktivan'] = false;
            $data['verifikovan'] = false;
        }

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
            'tip_doma_id' => 'required|exists:tipovi_domova,id',
            'nivo_njege_id' => 'required|exists:nivoi_njege,id',
            'nurses_availability' => 'required|in:24_7,shifts,on_demand',
            'doctor_availability' => 'required|in:permanent,periodic,on_call',
            'pricing_mode' => 'required|in:public,on_request',
            'price_from' => 'nullable|numeric|min:0',
            'aktivan' => 'boolean',
            'verifikovan' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first() ?? 'validation failed');
        }

        $validated = $validator->validated();
        $existing = $this->findExistingFacility(
            Dom::class,
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

        DB::transaction(function () use ($validated, $programIds, $medicalIds, $smjestajIds): void {
            $slug = $this->uniqueSlugForModel(Dom::class, (string) $validated['slug']);
            $validated['slug'] = $slug;
            $validated['user_id'] = null;
            $validated['prosjecna_ocjena'] = 0;
            $validated['broj_recenzija'] = 0;
            $validated['broj_pregleda'] = 0;

            $dom = Dom::create($validated);

            $programSync = [];
            foreach ($programIds as $priority => $id) {
                $programSync[$id] = ['prioritet' => $priority + 1, 'napomena' => null];
            }
            $dom->programiNjege()->sync($programSync);

            $medicalSync = [];
            foreach ($medicalIds as $id) {
                $medicalSync[$id] = ['napomena' => null];
            }
            $dom->medicinskUsluge()->sync($medicalSync);
            $dom->smjestajUslovi()->sync($smjestajIds);
        });

        return 'created';
    }
}
