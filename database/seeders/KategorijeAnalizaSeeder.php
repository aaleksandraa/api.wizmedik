<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategorijaAnalize;
use Illuminate\Support\Str;

class KategorijeAnalizaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategorije = [
            [
                'naziv' => 'Hematologija',
                'opis' => 'Analize krvi - kompletna krvna slika, koagulacija, anemija',
                'ikona' => 'droplet',
                'boja' => '#EF4444',
                'redoslijed' => 1,
                'meta_title' => 'Hematološke analize - Laboratorija',
                'meta_description' => 'Kompletna krvna slika, koagulacija i druge hematološke analize',
            ],
            [
                'naziv' => 'Biohemija',
                'opis' => 'Biohemijske analize - šećer, holesterol, jetra, bubrezi',
                'ikona' => 'flask',
                'boja' => '#0891b2',
                'redoslijed' => 2,
                'meta_title' => 'Biohemijske analize - Laboratorija',
                'meta_description' => 'Šećer, holesterol, jetra, bubrezi i druge biohemijske analize',
            ],
            [
                'naziv' => 'Hormoni',
                'opis' => 'Hormonske analize - štitna žlijezda, reproduktivni hormoni',
                'ikona' => 'activity',
                'boja' => '#8B5CF6',
                'redoslijed' => 3,
                'meta_title' => 'Hormonske analize - Laboratorija',
                'meta_description' => 'Analiza hormona štitne žlijezde, reproduktivnih hormona i drugih',
            ],
            [
                'naziv' => 'Imunologija',
                'opis' => 'Imunološke analize - alergije, autoimune bolesti, infekcije',
                'ikona' => 'shield',
                'boja' => '#10B981',
                'redoslijed' => 4,
                'meta_title' => 'Imunološke analize - Laboratorija',
                'meta_description' => 'Testovi za alergije, autoimune bolesti i infekcije',
            ],
            [
                'naziv' => 'Mikrobiologija',
                'opis' => 'Mikrobiološke analize - bakterije, virusi, gljivice',
                'ikona' => 'microscope',
                'boja' => '#F59E0B',
                'redoslijed' => 5,
                'meta_title' => 'Mikrobiološke analize - Laboratorija',
                'meta_description' => 'Analiza bakterija, virusa i gljivica',
            ],
            [
                'naziv' => 'Urin',
                'opis' => 'Analize urina - opšta analiza, mikroalbuminurija',
                'ikona' => 'beaker',
                'boja' => '#06B6D4',
                'redoslijed' => 6,
                'meta_title' => 'Analize urina - Laboratorija',
                'meta_description' => 'Opšta analiza urina i specijalizovane analize',
            ],
            [
                'naziv' => 'Stolica',
                'opis' => 'Analize stolice - paraziti, okultno krvarenje',
                'ikona' => 'test-tube',
                'boja' => '#84CC16',
                'redoslijed' => 7,
                'meta_title' => 'Analize stolice - Laboratorija',
                'meta_description' => 'Analiza stolice na parazite i okultno krvarenje',
            ],
            [
                'naziv' => 'Tumorski markeri',
                'opis' => 'Tumorski markeri - skrining i praćenje',
                'ikona' => 'alert-circle',
                'boja' => '#EC4899',
                'redoslijed' => 8,
                'meta_title' => 'Tumorski markeri - Laboratorija',
                'meta_description' => 'Analiza tumorskih markera za skrining i praćenje',
            ],
            [
                'naziv' => 'Vitamini i minerali',
                'opis' => 'Analiza vitamina i minerala - vitamin D, B12, gvožđe',
                'ikona' => 'pill',
                'boja' => '#F97316',
                'redoslijed' => 9,
                'meta_title' => 'Vitamini i minerali - Laboratorija',
                'meta_description' => 'Analiza vitamina D, B12, gvožđa i drugih vitamina i minerala',
            ],
            [
                'naziv' => 'Genetika',
                'opis' => 'Genetske analize - DNK testovi, paternitet',
                'ikona' => 'dna',
                'boja' => '#6366F1',
                'redoslijed' => 10,
                'meta_title' => 'Genetske analize - Laboratorija',
                'meta_description' => 'DNK testovi, test paterniteta i druge genetske analize',
            ],
            [
                'naziv' => 'Toksikologija',
                'opis' => 'Toksikološke analize - droge, alkohol, teški metali',
                'ikona' => 'skull',
                'boja' => '#EF4444',
                'redoslijed' => 11,
                'meta_title' => 'Toksikološke analize - Laboratorija',
                'meta_description' => 'Analiza droga, alkohola i teških metala',
            ],
            [
                'naziv' => 'Prenatalna dijagnostika',
                'opis' => 'Prenatalne analize - NIPT, amniocenteza',
                'ikona' => 'baby',
                'boja' => '#EC4899',
                'redoslijed' => 12,
                'meta_title' => 'Prenatalna dijagnostika - Laboratorija',
                'meta_description' => 'NIPT test, amniocenteza i druge prenatalne analize',
            ],
            [
                'naziv' => 'Paketi analiza',
                'opis' => 'Kompleksni paketi analiza - sistematski pregled, check-up',
                'ikona' => 'package',
                'boja' => '#14B8A6',
                'redoslijed' => 13,
                'meta_title' => 'Paketi analiza - Laboratorija',
                'meta_description' => 'Kompleksni paketi analiza za sistematski pregled',
            ],
        ];

        foreach ($kategorije as $kategorija) {
            KategorijaAnalize::updateOrCreate(
                ['slug' => Str::slug($kategorija['naziv'])],
                $kategorija
            );
        }

        $this->command->info('Kategorije analiza uspješno kreirane!');
    }
}
