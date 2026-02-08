<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Mkb10KategorijeSeeder extends Seeder
{
    public function run(): void
    {
        $kategorije = [
            [
                'kod_od' => 'A00',
                'kod_do' => 'B99',
                'naziv' => 'Zarazne i parazitarne bolesti',
                'opis' => 'Bolesti uzrokovane mikroorganizmima i parazitima',
                'boja' => '#ef4444',
                'ikona' => 'bug',
                'redoslijed' => 1,
            ],
            [
                'kod_od' => 'C00',
                'kod_do' => 'D48',
                'naziv' => 'Novotvorine (tumori)',
                'opis' => 'Maligni i benigni tumori',
                'boja' => '#8b5cf6',
                'ikona' => 'activity',
                'redoslijed' => 2,
            ],
            [
                'kod_od' => 'D50',
                'kod_do' => 'D89',
                'naziv' => 'Bolesti krvi i krvotvornih organa te određeni poremećaji imunološkog sistema',
                'opis' => 'Anemije, poremećaji koagulacije, imunodeficijencije',
                'boja' => '#dc2626',
                'ikona' => 'droplet',
                'redoslijed' => 3,
            ],
            [
                'kod_od' => 'E00',
                'kod_do' => 'E90',
                'naziv' => 'Endokrine bolesti, bolesti ishrane i bolesti metabolizma',
                'opis' => 'Dijabetes, bolesti štitnjače, poremećaji metabolizma',
                'boja' => '#f59e0b',
                'ikona' => 'zap',
                'redoslijed' => 4,
            ],
            [
                'kod_od' => 'F00',
                'kod_do' => 'F99',
                'naziv' => 'Mentalni poremećaji i poremećaji ponašanja',
                'opis' => 'Psihijatrijske bolesti, poremećaji ličnosti, ovisnosti',
                'boja' => '#6366f1',
                'ikona' => 'brain',
                'redoslijed' => 5,
            ],
            [
                'kod_od' => 'G00',
                'kod_do' => 'G99',
                'naziv' => 'Bolesti nervnog sistema',
                'opis' => 'Neurološke bolesti, epilepsija, Parkinsonova bolest',
                'boja' => '#0891b2',
                'ikona' => 'cpu',
                'redoslijed' => 6,
            ],
            [
                'kod_od' => 'H00',
                'kod_do' => 'H59',
                'naziv' => 'Bolesti oka i adneksa oka',
                'opis' => 'Oftalmološke bolesti, katarakta, glaukom',
                'boja' => '#14b8a6',
                'ikona' => 'eye',
                'redoslijed' => 7,
            ],
            [
                'kod_od' => 'H60',
                'kod_do' => 'H95',
                'naziv' => 'Bolesti uha i mastoidnog nastavka',
                'opis' => 'ORL bolesti uha, gluhoća, vrtoglavica',
                'boja' => '#06b6d4',
                'ikona' => 'ear',
                'redoslijed' => 8,
            ],
            [
                'kod_od' => 'I00',
                'kod_do' => 'I99',
                'naziv' => 'Bolesti cirkulacijskog sistema',
                'opis' => 'Kardiovaskularne bolesti, hipertenzija, infarkt',
                'boja' => '#e11d48',
                'ikona' => 'heart',
                'redoslijed' => 9,
            ],
            [
                'kod_od' => 'J00',
                'kod_do' => 'J99',
                'naziv' => 'Bolesti respiratornog sistema',
                'opis' => 'Bolesti pluća, astma, pneumonija, KOPB',
                'boja' => '#22c55e',
                'ikona' => 'wind',
                'redoslijed' => 10,
            ],
            [
                'kod_od' => 'K00',
                'kod_do' => 'K93',
                'naziv' => 'Bolesti digestivnog sistema',
                'opis' => 'Bolesti probavnog trakta, jetre, žučne kese',
                'boja' => '#eab308',
                'ikona' => 'utensils',
                'redoslijed' => 11,
            ],
            [
                'kod_od' => 'L00',
                'kod_do' => 'L99',
                'naziv' => 'Bolesti kože i potkožnog tkiva',
                'opis' => 'Dermatološke bolesti, psorijaza, dermatitis',
                'boja' => '#f97316',
                'ikona' => 'layers',
                'redoslijed' => 12,
            ],
            [
                'kod_od' => 'M00',
                'kod_do' => 'M99',
                'naziv' => 'Bolesti mišićno-koštanog sistema i vezivnog tkiva',
                'opis' => 'Artritis, osteoporoza, bolesti kičme',
                'boja' => '#84cc16',
                'ikona' => 'bone',
                'redoslijed' => 13,
            ],
            [
                'kod_od' => 'N00',
                'kod_do' => 'N99',
                'naziv' => 'Bolesti genitourinarnog sistema',
                'opis' => 'Bolesti bubrega, mokraćnog sistema, reproduktivnih organa',
                'boja' => '#a855f7',
                'ikona' => 'filter',
                'redoslijed' => 14,
            ],
            [
                'kod_od' => 'O00',
                'kod_do' => 'O99',
                'naziv' => 'Trudnoća, porođaj i babinje',
                'opis' => 'Komplikacije trudnoće, porođaja i postporođajnog perioda',
                'boja' => '#ec4899',
                'ikona' => 'baby',
                'redoslijed' => 15,
            ],
            [
                'kod_od' => 'P00',
                'kod_do' => 'P96',
                'naziv' => 'Određena stanja nastala u perinatalnom periodu',
                'opis' => 'Bolesti novorođenčadi, perinatalne komplikacije',
                'boja' => '#f472b6',
                'ikona' => 'baby',
                'redoslijed' => 16,
            ],
            [
                'kod_od' => 'Q00',
                'kod_do' => 'Q99',
                'naziv' => 'Urođene malformacije, deformiteti i hromozomske abnormalnosti',
                'opis' => 'Kongenitalne anomalije, genetski poremećaji',
                'boja' => '#c084fc',
                'ikona' => 'dna',
                'redoslijed' => 17,
            ],
            [
                'kod_od' => 'R00',
                'kod_do' => 'R99',
                'naziv' => 'Simptomi, znakovi i abnormalni klinički i laboratorijski nalazi, neklasifikovani na drugom mjestu',
                'opis' => 'Nespecifični simptomi i nalazi',
                'boja' => '#64748b',
                'ikona' => 'help-circle',
                'redoslijed' => 18,
            ],
            [
                'kod_od' => 'S00',
                'kod_do' => 'T98',
                'naziv' => 'Povrede, trovanja i određene druge posljedice spoljašnjih uzroka',
                'opis' => 'Traume, frakture, opekotine, trovanja',
                'boja' => '#f43f5e',
                'ikona' => 'alert-triangle',
                'redoslijed' => 19,
            ],
            [
                'kod_od' => 'U00',
                'kod_do' => 'U89',
                'naziv' => 'Šifre za posebne namjene',
                'opis' => 'Rezervisane šifre za posebne svrhe (npr. COVID-19)',
                'boja' => '#475569',
                'ikona' => 'bookmark',
                'redoslijed' => 20,
            ],
            [
                'kod_od' => 'V01',
                'kod_do' => 'Y98',
                'naziv' => 'Spoljašnji uzroci morbiditeta i mortaliteta',
                'opis' => 'Nesreće, povrede, samopovređivanje',
                'boja' => '#78716c',
                'ikona' => 'alert-octagon',
                'redoslijed' => 21,
            ],
            [
                'kod_od' => 'Z00',
                'kod_do' => 'Z99',
                'naziv' => 'Faktori koji utiču na zdravstveno stanje i kontakt sa zdravstvenom službom',
                'opis' => 'Preventivni pregledi, vakcinacije, anamnestički podaci',
                'boja' => '#0891b2',
                'ikona' => 'clipboard-list',
                'redoslijed' => 22,
            ],
        ];

        foreach ($kategorije as $kategorija) {
            DB::table('mkb10_kategorije')->updateOrInsert(
                ['kod_od' => $kategorija['kod_od'], 'kod_do' => $kategorija['kod_do']],
                array_merge($kategorija, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('MKB-10 kategorije uspješno unesene!');
    }
}
