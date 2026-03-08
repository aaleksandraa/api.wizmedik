<?php

namespace App\Console\Commands;

use App\Models\Lijek;
use App\Models\LijekFondZapis;
use App\Support\LijekCacheVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportLijekoviXml extends Command
{
    protected $signature = 'lijekovi:import 
        {--path=public/cjenovnik-lijekova_wf.xml : Putanja do XML fajla}
        {--reset : Obrisi i tabelu lijekovi prije importa}';

    protected $description = 'Importuj RFZO cjenovnik lijekova iz XML fajla';

    public function handle(): int
    {
        $xmlPath = $this->resolvePath((string) $this->option('path'));

        if (!is_file($xmlPath)) {
            $this->error("XML fajl nije pronadjen: {$xmlPath}");
            return self::FAILURE;
        }

        $xml = @simplexml_load_file($xmlPath, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_PARSEHUGE);

        if (!$xml) {
            $this->error('Neuspjelo ucitavanje XML fajla.');
            return self::FAILURE;
        }

        $exportDate = $this->parseDate($this->normalizeValue((string) ($xml['datum-izvoza'] ?? null)));
        $groupedRows = $this->groupRows($xml);

        if (empty($groupedRows)) {
            $this->warn('XML ne sadrzi nijedan validan <cjenovnik-lijeka> zapis.');
            return self::SUCCESS;
        }

        $this->info('Pronadjeno lijekova: ' . count($groupedRows));
        $this->info('XML datum izvoza: ' . ($exportDate ?: 'n/a'));
        $this->newLine();

        $now = now();
        $processedRows = 0;

        DB::beginTransaction();

        try {
            // Fond redovi su iskljucivo XML source, pa ih osvjezavamo svaki import.
            LijekFondZapis::query()->delete();

            if ($this->option('reset')) {
                $this->warn('Opcija --reset je aktivna: brisem i tabelu lijekovi.');
                Lijek::query()->delete();
            }

            $progress = $this->output->createProgressBar(count($groupedRows));
            $progress->start();

            $fondBatch = [];

            foreach ($groupedRows as $sourceLijekId => $rows) {
                [$currentRows, $currentMeta] = $this->resolveCurrentRows($rows);
                $base = $rows[0];

                $lijek = Lijek::query()->firstOrNew([
                    'lijek_id' => (int) $sourceLijekId,
                ]);

                $lijek->slug = $this->makeSlug($base, (int) $sourceLijekId);
                $lijek->atc_sifra = $base['atc_sifra'];
                $lijek->naziv_atc5_nivo = $base['naziv_atc5_nivo'];
                $lijek->naziv = $base['naziv'];
                $lijek->brend = $base['brend'];
                $lijek->oblik = $base['oblik'];
                $lijek->doza = $base['doza'];
                $lijek->pakovanje = $base['pakovanje'];
                $lijek->jedinica_mjere = $base['jedinica_mjere'];
                $lijek->sifra_projekta = $base['sifra_projekta'];
                $lijek->aktuelna_cijena = $currentMeta['cijena'];
                $lijek->aktuelni_procenat_participacije = $currentMeta['procenat_participacije'];
                $lijek->aktuelni_iznos_participacije = $currentMeta['iznos_participacije'];
                $lijek->aktuelna_lista_id = $currentMeta['lista_id'];
                $lijek->aktuelna_verzija_od = $currentMeta['verzija_od'];
                $lijek->aktuelna_verzija_do = $currentMeta['verzija_do'];
                $lijek->aktuelni_broj_indikacija = $currentMeta['broj_indikacija'];
                $lijek->xml_datum_izvoza = $exportDate;

                // Polja za buduce izvore podataka ostaju nullable, ali inicijalno mapiramo najblize vrijednosti.
                if (empty($lijek->naziv_lijeka)) {
                    $lijek->naziv_lijeka = $base['naziv'];
                }
                if (empty($lijek->farmaceutski_oblik)) {
                    $lijek->farmaceutski_oblik = $base['oblik'];
                }
                if (empty($lijek->jacina)) {
                    $lijek->jacina = $base['doza'];
                }
                if (empty($lijek->pakovanje_registar)) {
                    $lijek->pakovanje_registar = $base['pakovanje'];
                }
                if (empty($lijek->lista_rfzo_pojasnjenje)) {
                    $lijek->lista_rfzo_pojasnjenje = Lijek::defaultListaPojasnjenje($currentMeta['lista_id']);
                }

                $lijek->save();

                foreach ($rows as $row) {
                    $fondBatch[] = [
                        'lijek_id' => $lijek->id,
                        'cijena' => $row['cijena'],
                        'procenat_participacije' => $row['procenat_participacije'],
                        'iznos_participacije' => $row['iznos_participacije'],
                        'lista_id' => $row['lista_id'],
                        'indikacija_oznaka' => $row['indikacija_oznaka'],
                        'indikacija_naziv' => $row['indikacija_naziv'],
                        'cijena_ref_lijeka' => $row['cijena_ref_lijeka'],
                        'verzija_od' => $row['verzija_od'],
                        'verzija_do' => $row['verzija_do'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($fondBatch) >= 1000) {
                        LijekFondZapis::query()->insert($fondBatch);
                        $processedRows += count($fondBatch);
                        $fondBatch = [];
                    }
                }

                $progress->advance();
            }

            if (!empty($fondBatch)) {
                LijekFondZapis::query()->insert($fondBatch);
                $processedRows += count($fondBatch);
            }

            $progress->finish();
            $this->newLine(2);

            DB::commit();

            LijekCacheVersion::bump();

            $this->info('Import uspjesno zavrsen.');
            $this->info('Upisano fond redova: ' . $processedRows);
            $this->info('Lijekovi ukupno u bazi: ' . Lijek::query()->count());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import nije uspio: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return base_path('public/cjenovnik-lijekova_wf.xml');
        }

        if (
            Str::startsWith($path, ['/','\\']) ||
            preg_match('/^[A-Za-z]:\\\\/', $path) === 1
        ) {
            return $path;
        }

        return base_path($path);
    }

    private function groupRows(\SimpleXMLElement $xml): array
    {
        $grouped = [];

        foreach ($xml->{'cjenovnik-lijeka'} as $node) {
            $row = $this->parseRow($node);
            if ($row === null) {
                continue;
            }

            $grouped[$row['source_lijek_id']][] = $row;
        }

        ksort($grouped, SORT_NUMERIC);

        return $grouped;
    }

    private function parseRow(\SimpleXMLElement $node): ?array
    {
        $sourceLijekId = $this->parseInteger((string) ($node['lijek-id'] ?? null));
        if ($sourceLijekId === null) {
            return null;
        }

        return [
            'source_lijek_id' => $sourceLijekId,
            'atc_sifra' => $this->normalizeValue((string) ($node['atc-sifra'] ?? null)),
            'naziv_atc5_nivo' => $this->normalizeValue((string) ($node['naziv-atc5-nivo'] ?? null)),
            'naziv' => $this->normalizeValue((string) ($node['naziv'] ?? null)),
            'brend' => $this->normalizeValue((string) ($node['brend'] ?? null)),
            'oblik' => $this->normalizeValue((string) ($node['oblik'] ?? null)),
            'doza' => $this->normalizeValue((string) ($node['doza'] ?? null)),
            'pakovanje' => $this->normalizeValue((string) ($node['pakovanje'] ?? null)),
            'jedinica_mjere' => $this->normalizeValue((string) ($node['jedinica-mjere'] ?? null)),
            'cijena' => $this->parseDecimal((string) ($node['cijena'] ?? null)),
            'procenat_participacije' => $this->parseDecimal((string) ($node['procenat-participacije'] ?? null)),
            'iznos_participacije' => $this->parseDecimal((string) ($node['iznos-participacije'] ?? null)),
            'sifra_projekta' => $this->normalizeValue((string) ($node['sifra-projekta'] ?? null)),
            'indikacija_oznaka' => $this->normalizeValue((string) ($node['indikacija-oznaka'] ?? null)),
            'indikacija_naziv' => $this->normalizeValue((string) ($node['indikacija-naziv'] ?? null)),
            'lista_id' => $this->normalizeValue((string) ($node['lista-id'] ?? null)),
            'cijena_ref_lijeka' => $this->parseDecimal((string) ($node['cijena-ref-lijeka'] ?? null)),
            'verzija_od' => $this->parseDate((string) ($node['verzija-od'] ?? null)),
            'verzija_do' => $this->parseDate((string) ($node['verzija-do'] ?? null)),
        ];
    }

    private function resolveCurrentRows(array $rows): array
    {
        $sorted = $rows;

        usort($sorted, function (array $a, array $b) {
            $aOpenEnded = $a['verzija_do'] === null;
            $bOpenEnded = $b['verzija_do'] === null;

            if ($aOpenEnded !== $bOpenEnded) {
                return $aOpenEnded ? -1 : 1;
            }

            $aFrom = $a['verzija_od'] ?? '';
            $bFrom = $b['verzija_od'] ?? '';

            if ($aFrom !== $bFrom) {
                return strcmp($bFrom, $aFrom); // noviji datum prvi
            }

            return 0;
        });

        $primary = $sorted[0];

        $currentRows = array_values(array_filter($rows, function (array $row) use ($primary) {
            return $row['verzija_od'] === $primary['verzija_od']
                && $row['verzija_do'] === $primary['verzija_do'];
        }));

        $indikacije = [];
        foreach ($currentRows as $row) {
            $oznaka = $row['indikacija_oznaka'] ?? null;
            $naziv = $row['indikacija_naziv'] ?? null;

            if ($oznaka === null && $naziv === null) {
                continue;
            }

            $key = ($oznaka ?? '') . '|' . ($naziv ?? '');
            $indikacije[$key] = true;
        }

        return [
            $currentRows,
            [
                'cijena' => $primary['cijena'],
                'procenat_participacije' => $primary['procenat_participacije'],
                'iznos_participacije' => $primary['iznos_participacije'],
                'lista_id' => $primary['lista_id'],
                'verzija_od' => $primary['verzija_od'],
                'verzija_do' => $primary['verzija_do'],
                'broj_indikacija' => count($indikacije),
            ],
        ];
    }

    private function makeSlug(array $row, int $sourceLijekId): string
    {
        $parts = array_filter([
            $row['naziv'] ?? null,
            $row['oblik'] ?? null,
            $row['doza'] ?? null,
            $sourceLijekId,
        ]);

        $base = Str::slug(implode('-', $parts));
        if ($base === '') {
            $base = 'lijek-' . $sourceLijekId;
        }

        return $base;
    }

    private function normalizeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseDecimal(?string $value): ?string
    {
        $normalized = $this->normalizeValue($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = str_replace(',', '.', $normalized);
        if (!is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function parseInteger(?string $value): ?int
    {
        $normalized = $this->normalizeValue($value);
        if ($normalized === null || !is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function parseDate(?string $value): ?string
    {
        $normalized = $this->normalizeValue($value);
        if ($normalized === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $normalized)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
