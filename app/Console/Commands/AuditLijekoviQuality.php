<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditLijekoviQuality extends Command
{
    protected $signature = 'lijekovi:audit-quality
        {--json : Ispisi rezultat u JSON formatu}
        {--limit=50 : Maksimalan broj detalja po kategoriji}';

    protected $description = 'Audit kvaliteta podataka za lijekove: konfliktne cijene, duple indikacije i missing poslovna polja';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $conflictingPrices = DB::table('lijek_fond_zapisi')
            ->select([
                'lijek_id',
                'verzija_od',
                'verzija_do',
                DB::raw('COUNT(*) as rows_total'),
                DB::raw("COUNT(DISTINCT CONCAT_WS('|', COALESCE(CAST(cijena AS TEXT), ''), COALESCE(CAST(procenat_participacije AS TEXT), ''), COALESCE(CAST(iznos_participacije AS TEXT), ''), COALESCE(lista_id,''))) as distinct_price_sets"),
            ])
            ->groupBy('lijek_id', 'verzija_od', 'verzija_do')
            ->havingRaw("COUNT(DISTINCT CONCAT_WS('|', COALESCE(CAST(cijena AS TEXT), ''), COALESCE(CAST(procenat_participacije AS TEXT), ''), COALESCE(CAST(iznos_participacije AS TEXT), ''), COALESCE(lista_id,''))) > 1")
            ->orderByDesc('distinct_price_sets')
            ->limit($limit)
            ->get();

        $duplicateIndications = DB::table('lijek_fond_zapisi')
            ->where(function ($query) {
                $query->whereRaw("TRIM(COALESCE(indikacija_oznaka, '')) <> ''")
                    ->orWhereRaw("TRIM(COALESCE(indikacija_naziv, '')) <> ''");
            })
            ->select([
                'lijek_id',
                'verzija_od',
                'verzija_do',
                'indikacija_oznaka',
                'indikacija_naziv',
                DB::raw('COUNT(*) as duplicate_count'),
            ])
            ->groupBy('lijek_id', 'verzija_od', 'verzija_do', 'indikacija_oznaka', 'indikacija_naziv')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('duplicate_count')
            ->limit($limit)
            ->get();

        $missingBusinessFields = DB::table('lijekovi')
            ->select([
                'id',
                'lijek_id',
                'naziv',
                'naziv_lijeka',
                'atc_sifra',
                'inn',
                'proizvodjac',
                'nosilac_dozvole',
                'tip_lijeka',
                'broj_dozvole',
                'rezim_izdavanja',
            ])
            ->where(function ($query) {
                $this->whereNullOrEmpty($query, 'atc_sifra')
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'naziv');
                        $this->whereNullOrEmpty($q, 'naziv_lijeka');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'inn');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'proizvodjac');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'nosilac_dozvole');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'tip_lijeka');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'broj_dozvole');
                    })
                    ->orWhere(function ($q) {
                        $this->whereNullOrEmpty($q, 'rezim_izdavanja');
                    });
            })
            ->limit($limit)
            ->get();

        $summary = [
            'generated_at' => now()->toDateTimeString(),
            'conflicting_price_periods_count' => $conflictingPrices->count(),
            'duplicate_indications_count' => $duplicateIndications->count(),
            'missing_business_fields_count' => $missingBusinessFields->count(),
            'conflicting_price_periods' => $conflictingPrices,
            'duplicate_indications' => $duplicateIndications,
            'missing_business_fields' => $missingBusinessFields,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->info('Audit kvaliteta podataka (lijekovi)');
        $this->line('Konfliktni periodi cijena: ' . $summary['conflicting_price_periods_count']);
        $this->line('Dupli indikacija zapisi: ' . $summary['duplicate_indications_count']);
        $this->line('Missing poslovna polja: ' . $summary['missing_business_fields_count']);

        if ($summary['conflicting_price_periods_count'] > 0) {
            $this->newLine();
            $this->warn('Top konfliktni periodi cijena:');
            foreach ($conflictingPrices as $row) {
                $this->line(sprintf(
                    '  lijek_id=%s period=%s..%s rows=%s distinct_sets=%s',
                    (string) $row->lijek_id,
                    $row->verzija_od ?? 'NULL',
                    $row->verzija_do ?? 'NULL',
                    (string) $row->rows_total,
                    (string) $row->distinct_price_sets
                ));
            }
        }

        if ($summary['duplicate_indications_count'] > 0) {
            $this->newLine();
            $this->warn('Top duple indikacije:');
            foreach ($duplicateIndications as $row) {
                $this->line(sprintf(
                    '  lijek_id=%s period=%s..%s oznaka=%s count=%s',
                    (string) $row->lijek_id,
                    $row->verzija_od ?? 'NULL',
                    $row->verzija_do ?? 'NULL',
                    $row->indikacija_oznaka ?? '-',
                    (string) $row->duplicate_count
                ));
            }
        }

        if ($summary['missing_business_fields_count'] > 0) {
            $this->newLine();
            $this->warn('Primjeri lijekova sa missing poslovnim poljima:');
            foreach ($missingBusinessFields as $row) {
                $name = $row->naziv ?: $row->naziv_lijeka ?: 'N/A';
                $this->line(sprintf('  lijek_id=%s naziv=%s', (string) $row->lijek_id, $name));
            }
        }

        return self::SUCCESS;
    }

    private function whereNullOrEmpty($query, string $column): mixed
    {
        return $query->whereNull($column)->orWhereRaw("TRIM(COALESCE({$column}, '')) = ''");
    }
}
