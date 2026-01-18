<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laboratorija;
use App\Models\Analiza;
use App\Models\PaketAnaliza;
use App\Models\LaboratorijaRadnoVrijeme;
use App\Models\KategorijaAnalize;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LaboratorijeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧪 Seeding laboratories...');

        // Get categories
        $kategorije = KategorijaAnalize::all()->keyBy('slug');

        if ($kategorije->isEmpty()) {
            $this->command->error('❌ No categories found! Please run KategorijeAnalizaSeeder first.');
            return;
        }

        // Laboratory 1: Laboratorija Sarajevo
        $user1 = User::firstOrCreate(
            ['email' => 'info@lab-sarajevo.ba'],
            [
                'name' => 'Laboratorija Sarajevo',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$user1->hasRole('laboratory')) {
            $user1->assignRole('laboratory');
        }

        $lab1 = Laboratorija::firstOrCreate(
            ['slug' => 'laboratorija-sarajevo'],
            [
                'user_id' => $user1->id,
                'naziv' => 'Laboratorija Sarajevo',
            'email' => 'info@lab-sarajevo.ba',
            'telefon' => '+387 33 123 456',
            'adresa' => 'Zmaja od Bosne 8',
            'grad' => 'Sarajevo',
            'opis' => 'Moderna medicinska laboratorija sa najnovijom opremom i iskusnim timom. Nudimo širok spektar analiza sa brzim rezultatima i online preuzimanjem.',
            'prosjecna_ocjena' => 4.8,
            'broj_recenzija' => 156,
            'broj_pregleda' => 2340,
            'verifikovan' => true,
            'aktivan' => true,
            'online_rezultati' => true,
            'prosjecno_vrijeme_rezultata' => '24 sata',
            ]
        );

        // Working hours for Lab 1
        $dani = ['ponedeljak', 'utorak', 'srijeda', 'cetvrtak', 'petak', 'subota', 'nedjelja'];
        foreach ($dani as $index => $dan) {
            LaboratorijaRadnoVrijeme::firstOrCreate(
                ['laboratorija_id' => $lab1->id, 'dan' => $dan],
                [
                    'otvaranje' => $index < 5 ? '07:00' : ($index === 5 ? '08:00' : null),
                    'zatvaranje' => $index < 5 ? '20:00' : ($index === 5 ? '14:00' : null),
                    'zatvoreno' => $index === 6,
                ]
            );
        }

        // Analyses for Lab 1
        $analize1 = [
            ['naziv' => 'Kompletna krvna slika', 'kategorija' => 'hematologija', 'cijena' => 15.00, 'prosjecno_vrijeme_rezultata' => '2-4 sata'],
            ['naziv' => 'Sedimentacija (SE)', 'kategorija' => 'hematologija', 'cijena' => 8.00, 'prosjecno_vrijeme_rezultata' => '1 sat'],
            ['naziv' => 'Glukoza', 'kategorija' => 'biohemija', 'cijena' => 10.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'Holesterol ukupni', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'HDL holesterol', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'LDL holesterol', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'Trigliceridi', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'Urea', 'kategorija' => 'biohemija', 'cijena' => 10.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'Kreatinin', 'kategorija' => 'biohemija', 'cijena' => 10.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'TSH', 'kategorija' => 'hormoni', 'cijena' => 25.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'FT4', 'kategorija' => 'hormoni', 'cijena' => 25.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'FT3', 'kategorija' => 'hormoni', 'cijena' => 25.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Vitamin D', 'kategorija' => 'vitamini', 'cijena' => 35.00, 'akcijska_cijena' => 29.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Vitamin B12', 'kategorija' => 'vitamini', 'cijena' => 30.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'CRP', 'kategorija' => 'imunologija', 'cijena' => 15.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
        ];

        $lab1Analize = [];
        foreach ($analize1 as $analiza) {
            $kat = $kategorije->get($analiza['kategorija']);
            if ($kat) {
                $lab1Analize[] = Analiza::firstOrCreate(
                    ['laboratorija_id' => $lab1->id, 'slug' => Str::slug($analiza['naziv'] . '-' . $lab1->id)],
                    [
                        'kategorija_id' => $kat->id,
                        'naziv' => $analiza['naziv'],
                        'cijena' => $analiza['cijena'],
                        'akcijska_cijena' => $analiza['akcijska_cijena'] ?? null,
                        'prosjecno_vrijeme_rezultata' => $analiza['prosjecno_vrijeme_rezultata'],
                        'opis' => 'Standardna laboratorijska analiza sa brzim i preciznim rezultatima.',
                    ]
                );
            }
        }

        // Package for Lab 1
        $paket1 = PaketAnaliza::firstOrCreate(
            ['laboratorija_id' => $lab1->id, 'slug' => 'osnovni-checkup-sarajevo'],
            [
                'naziv' => 'Osnovni Check-up',
                'opis' => 'Kompletna provjera osnovnih parametara zdravlja',
                'cijena' => 65.00,
                'ustedite' => 15.00,
                'analize_ids' => array_slice(array_map(fn($a) => $a->id, $lab1Analize), 0, 8),
            ]
        );

        // Laboratory 2: Medlab Banja Luka
        $user2 = User::firstOrCreate(
            ['email' => 'kontakt@medlab-bl.ba'],
            [
                'name' => 'Medlab Banja Luka',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$user2->hasRole('laboratory')) {
            $user2->assignRole('laboratory');
        }

        $lab2 = Laboratorija::firstOrCreate(
            ['slug' => 'medlab-banja-luka'],
            [
                'user_id' => $user2->id,
                'naziv' => 'Medlab Banja Luka',
            'email' => 'kontakt@medlab-bl.ba',
            'telefon' => '+387 51 234 567',
            'adresa' => 'Kralja Petra I Karađorđevića 45',
            'grad' => 'Banja Luka',
            'opis' => 'Renomirana laboratorija sa dugogodišnjim iskustvom. Specijalizovani za hormonske analize i dijagnostiku.',
            'prosjecna_ocjena' => 4.9,
            'broj_recenzija' => 203,
            'broj_pregleda' => 3120,
            'verifikovan' => true,
            'aktivan' => true,
            'online_rezultati' => true,
            'prosjecno_vrijeme_rezultata' => '12-24 sata',
            ]
        );

        // Working hours for Lab 2
        foreach ($dani as $index => $dan) {
            LaboratorijaRadnoVrijeme::firstOrCreate(
                ['laboratorija_id' => $lab2->id, 'dan' => $dan],
                [
                    'otvaranje' => $index < 6 ? '07:30' : null,
                    'zatvaranje' => $index < 6 ? '19:00' : null,
                    'zatvoreno' => $index === 6,
                ]
            );
        }

        // Analyses for Lab 2
        $analize2 = [
            ['naziv' => 'Kompletna krvna slika', 'kategorija' => 'hematologija', 'cijena' => 18.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'Glukoza', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'HbA1c', 'kategorija' => 'biohemija', 'cijena' => 35.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Lipidogram', 'kategorija' => 'biohemija', 'cijena' => 40.00, 'akcijska_cijena' => 35.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'TSH', 'kategorija' => 'hormoni', 'cijena' => 28.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'FT4', 'kategorija' => 'hormoni', 'cijena' => 28.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Testosteron', 'kategorija' => 'hormoni', 'cijena' => 35.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Estradiol', 'kategorija' => 'hormoni', 'cijena' => 35.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Progesteron', 'kategorija' => 'hormoni', 'cijena' => 35.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Kortizol', 'kategorija' => 'hormoni', 'cijena' => 30.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Vitamin D', 'kategorija' => 'vitamini', 'cijena' => 38.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Feritin', 'kategorija' => 'hematologija', 'cijena' => 25.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
        ];

        $lab2Analize = [];
        foreach ($analize2 as $analiza) {
            $kat = $kategorije->get($analiza['kategorija']);
            if ($kat) {
                $lab2Analize[] = Analiza::firstOrCreate(
                    ['laboratorija_id' => $lab2->id, 'slug' => Str::slug($analiza['naziv'] . '-' . $lab2->id)],
                    [
                        'kategorija_id' => $kat->id,
                        'naziv' => $analiza['naziv'],
                        'cijena' => $analiza['cijena'],
                        'akcijska_cijena' => $analiza['akcijska_cijena'] ?? null,
                        'prosjecno_vrijeme_rezultata' => $analiza['prosjecno_vrijeme_rezultata'],
                        'opis' => 'Precizna analiza sa najsavremenijom opremom.',
                    ]
                );
            }
        }

        // Packages for Lab 2
        $paket2 = PaketAnaliza::firstOrCreate(
            ['laboratorija_id' => $lab2->id, 'slug' => 'hormonski-status-bl'],
            [
                'naziv' => 'Hormonski Status',
                'opis' => 'Kompletan pregled hormona štitne žlijezde',
                'cijena' => 45.00,
                'ustedite' => 11.00,
                'analize_ids' => [
                    $lab2Analize[4]->id, // TSH
                    $lab2Analize[5]->id, // FT4
                ],
            ]
        );

        // Laboratory 3: BioLab Mostar
        $user3 = User::firstOrCreate(
            ['email' => 'info@biolab-mostar.ba'],
            [
                'name' => 'BioLab Mostar',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$user3->hasRole('laboratory')) {
            $user3->assignRole('laboratory');
        }

        $lab3 = Laboratorija::firstOrCreate(
            ['slug' => 'biolab-mostar'],
            [
                'user_id' => $user3->id,
                'naziv' => 'BioLab Mostar',
            'email' => 'info@biolab-mostar.ba',
            'telefon' => '+387 36 345 678',
            'adresa' => 'Bulevar narodne revolucije 120',
            'grad' => 'Mostar',
            'opis' => 'Savremena laboratorija sa fokusom na mikrobiologiju i molekularnu dijagnostiku. Brzi rezultati i profesionalna usluga.',
            'prosjecna_ocjena' => 4.7,
            'broj_recenzija' => 98,
            'broj_pregleda' => 1560,
            'verifikovan' => true,
            'aktivan' => true,
            'online_rezultati' => true,
            'prosjecno_vrijeme_rezultata' => '24-48 sati',
            ]
        );

        // Working hours for Lab 3
        foreach ($dani as $index => $dan) {
            LaboratorijaRadnoVrijeme::firstOrCreate(
                ['laboratorija_id' => $lab3->id, 'dan' => $dan],
                [
                    'otvaranje' => $index < 5 ? '08:00' : ($index === 5 ? '09:00' : null),
                    'zatvaranje' => $index < 5 ? '18:00' : ($index === 5 ? '13:00' : null),
                    'zatvoreno' => $index === 6,
                ]
            );
        }

        // Analyses for Lab 3
        $analize3 = [
            ['naziv' => 'Kompletna krvna slika', 'kategorija' => 'hematologija', 'cijena' => 16.00, 'prosjecno_vrijeme_rezultata' => '3 sata'],
            ['naziv' => 'Urinokultura', 'kategorija' => 'mikrobiologija', 'cijena' => 25.00, 'prosjecno_vrijeme_rezultata' => '48-72 sata'],
            ['naziv' => 'Bris grla', 'kategorija' => 'mikrobiologija', 'cijena' => 20.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
            ['naziv' => 'Glukoza', 'kategorija' => 'biohemija', 'cijena' => 11.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'AST', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'ALT', 'kategorija' => 'biohemija', 'cijena' => 12.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'Bilirubin ukupni', 'kategorija' => 'biohemija', 'cijena' => 10.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'CRP', 'kategorija' => 'imunologija', 'cijena' => 18.00, 'prosjecno_vrijeme_rezultata' => '3 sata'],
            ['naziv' => 'RF faktor', 'kategorija' => 'imunologija', 'cijena' => 22.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Vitamin D', 'kategorija' => 'vitamini', 'cijena' => 40.00, 'akcijska_cijena' => 32.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
        ];

        $lab3Analize = [];
        foreach ($analize3 as $analiza) {
            $kat = $kategorije->get($analiza['kategorija']);
            if ($kat) {
                $lab3Analize[] = Analiza::firstOrCreate(
                    ['laboratorija_id' => $lab3->id, 'slug' => Str::slug($analiza['naziv'] . '-' . $lab3->id)],
                    [
                        'kategorija_id' => $kat->id,
                        'naziv' => $analiza['naziv'],
                        'cijena' => $analiza['cijena'],
                        'akcijska_cijena' => $analiza['akcijska_cijena'] ?? null,
                        'prosjecno_vrijeme_rezultata' => $analiza['prosjecno_vrijeme_rezultata'],
                        'opis' => 'Kvalitetna laboratorijska usluga sa brzim rezultatima.',
                    ]
                );
            }
        }

        // Package for Lab 3
        $paket3 = PaketAnaliza::firstOrCreate(
            ['laboratorija_id' => $lab3->id, 'slug' => 'jetreni-panel-mostar'],
            [
                'naziv' => 'Jetreni Panel',
                'opis' => 'Provjera funkcije jetre',
                'cijena' => 30.00,
                'ustedite' => 4.00,
                'analize_ids' => [
                    $lab3Analize[4]->id, // AST
                    $lab3Analize[5]->id, // ALT
                    $lab3Analize[6]->id, // Bilirubin
                ],
            ]
        );

        // Laboratory 4: HealthLab Tuzla
        $user4 = User::firstOrCreate(
            ['email' => 'info@healthlab-tuzla.ba'],
            [
                'name' => 'HealthLab Tuzla',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        if (!$user4->hasRole('laboratory')) {
            $user4->assignRole('laboratory');
        }

        $lab4 = Laboratorija::firstOrCreate(
            ['slug' => 'healthlab-tuzla'],
            [
                'user_id' => $user4->id,
                'naziv' => 'HealthLab Tuzla',
            'email' => 'info@healthlab-tuzla.ba',
            'telefon' => '+387 35 456 789',
            'adresa' => 'Turalibegova 15',
            'grad' => 'Tuzla',
            'opis' => 'Moderna laboratorija sa širokim spektrom analiza. Specijalizovani za alergološke testove i imunološke analize.',
            'prosjecna_ocjena' => 4.6,
            'broj_recenzija' => 87,
            'broj_pregleda' => 1340,
            'verifikovan' => true,
            'aktivan' => true,
            'online_rezultati' => false,
            'prosjecno_vrijeme_rezultata' => '24 sata',
            ]
        );

        // Working hours for Lab 4
        foreach ($dani as $index => $dan) {
            LaboratorijaRadnoVrijeme::firstOrCreate(
                ['laboratorija_id' => $lab4->id, 'dan' => $dan],
                [
                    'otvaranje' => $index < 5 ? '07:00' : null,
                    'zatvaranje' => $index < 5 ? '19:00' : null,
                    'zatvoreno' => $index >= 5,
                ]
            );
        }

        // Analyses for Lab 4
        $analize4 = [
            ['naziv' => 'Kompletna krvna slika', 'kategorija' => 'hematologija', 'cijena' => 17.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'Alergološki panel', 'kategorija' => 'alergologija', 'cijena' => 120.00, 'akcijska_cijena' => 99.00, 'prosjecno_vrijeme_rezultata' => '5-7 dana'],
            ['naziv' => 'IgE ukupni', 'kategorija' => 'imunologija', 'cijena' => 30.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Glukoza', 'kategorija' => 'biohemija', 'cijena' => 10.00, 'prosjecno_vrijeme_rezultata' => '2 sata'],
            ['naziv' => 'Holesterol', 'kategorija' => 'biohemija', 'cijena' => 13.00, 'prosjecno_vrijeme_rezultata' => '4 sata'],
            ['naziv' => 'TSH', 'kategorija' => 'hormoni', 'cijena' => 26.00, 'prosjecno_vrijeme_rezultata' => '24 sata'],
            ['naziv' => 'Vitamin D', 'kategorija' => 'vitamini', 'cijena' => 36.00, 'prosjecno_vrijeme_rezultata' => '48 sati'],
        ];

        foreach ($analize4 as $analiza) {
            $kat = $kategorije->get($analiza['kategorija']);
            if ($kat) {
                Analiza::firstOrCreate(
                    ['laboratorija_id' => $lab4->id, 'slug' => Str::slug($analiza['naziv'] . '-' . $lab4->id)],
                    [
                        'kategorija_id' => $kat->id,
                        'naziv' => $analiza['naziv'],
                        'cijena' => $analiza['cijena'],
                        'akcijska_cijena' => $analiza['akcijska_cijena'] ?? null,
                        'prosjecno_vrijeme_rezultata' => $analiza['prosjecno_vrijeme_rezultata'],
                        'opis' => 'Profesionalna laboratorijska analiza.',
                    ]
                );
            }
        }

        $this->command->info('✅ Seeded 4 laboratories with analyses and packages');
        $this->command->info('📊 Total analyses: ' . Analiza::count());
        $this->command->info('📦 Total packages: ' . PaketAnaliza::count());
    }
}
