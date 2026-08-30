<?php

namespace App\Console\Commands;

use App\Models\Banja;
use App\Models\Dom;
use App\Services\SpasHomes\WizMedikFacilityImportSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetireDemoSpasAndHomes extends Command
{
    use WizMedikFacilityImportSupport;

    protected $signature = 'wizmedik:retire-demo-spas-homes
        {--dry-run : Preview retire actions without writing}';

    protected $description = 'Soft-retire demo seed banje/domovi and free slugs for real import (never touches real claimed profiles)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $retiredBanje = $skippedClaimedBanje = 0;
        $retiredDomovi = $skippedClaimedDomovi = 0;

        foreach (Banja::withTrashed()->get() as $banja) {
            if ($this->isClaimedByRealOwner($banja)) {
                $skippedClaimedBanje++;
                $this->line("SKIP banja #{$banja->id} (claimed): {$banja->naziv}");

                continue;
            }

            if (!$this->isDemoBanja($banja) && !$this->isTestOwnerEmail($banja->user?->email)) {
                continue;
            }

            if ($banja->trashed()) {
                continue;
            }

            $newSlug = $this->retiredDemoSlug((string) $banja->slug);
            $this->info("RETIRE banja #{$banja->id}: {$banja->slug} -> {$newSlug}");

            if (!$dryRun) {
                DB::transaction(function () use ($banja, $newSlug): void {
                    $banja->update([
                        'slug' => $this->uniqueSlugForModel(Banja::class, $newSlug, (int) $banja->id),
                        'aktivan' => false,
                        'user_id' => null,
                    ]);
                    $banja->delete();
                });
            }

            $retiredBanje++;
        }

        foreach (Dom::withTrashed()->get() as $dom) {
            if ($this->isClaimedByRealOwner($dom)) {
                $skippedClaimedDomovi++;
                $this->line("SKIP dom #{$dom->id} (claimed): {$dom->naziv}");

                continue;
            }

            if (!$this->isDemoDom($dom) && !$this->isTestOwnerEmail($dom->user?->email)) {
                continue;
            }

            if ($dom->trashed()) {
                continue;
            }

            $newSlug = $this->retiredDemoSlug((string) $dom->slug);
            $this->info("RETIRE dom #{$dom->id}: {$dom->slug} -> {$newSlug}");

            if (!$dryRun) {
                DB::transaction(function () use ($dom, $newSlug): void {
                    $dom->update([
                        'slug' => $this->uniqueSlugForModel(Dom::class, $newSlug, (int) $dom->id),
                        'aktivan' => false,
                        'user_id' => null,
                    ]);
                    $dom->delete();
                });
            }

            $retiredDomovi++;
        }

        $this->newLine();
        $this->table(
            ['retired_banje', 'skipped_claimed_banje', 'retired_domovi', 'skipped_claimed_domovi', 'dry_run'],
            [[$retiredBanje, $skippedClaimedBanje, $retiredDomovi, $skippedClaimedDomovi, $dryRun ? 'yes' : 'no']]
        );

        return self::SUCCESS;
    }
}
