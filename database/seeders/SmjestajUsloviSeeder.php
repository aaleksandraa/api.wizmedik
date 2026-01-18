<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmjestajUsloviSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uslovi = [
            // Smještaj
            [
                'naziv' => 'Jednokrevetne sobe',
                'slug' => 'jednokrevetne',
                'kategorija' => 'smjestaj',
                'opis' => 'Privatne jednokrevetne sobe',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dvokrevetne sobe',
                'slug' => 'dvokrevetne',
                'kategorija' => 'smjestaj',
                'opis' => 'Sobe za dvije osobe',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Višekrevetne sobe',
                'slug' => 'visekrevetne',
                'kategorija' => 'smjestaj',
                'opis' => 'Sobe za više osoba (3-4 kreveta)',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Privremeni boravak',
                'slug' => 'privremeni',
                'kategorija' => 'smjestaj',
                'opis' => 'Kratkoročni privremeni smještaj',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dugoročni boravak',
                'slug' => 'dugorocni',
                'kategorija' => 'smjestaj',
                'opis' => 'Trajni ili dugoročni smještaj',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pristupačnost
            [
                'naziv' => 'Lift',
                'slug' => 'lift',
                'kategorija' => 'pristupacnost',
                'opis' => 'Lift za lakši pristup spratovima',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Pristup invalidskim kolicima',
                'slug' => 'invalidska-kolica',
                'kategorija' => 'pristupacnost',
                'opis' => 'Prilagođen pristup za invalidska kolica',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Prilagođena kupatila',
                'slug' => 'prilagodjena-kupatila',
                'kategorija' => 'pristupacnost',
                'opis' => 'Kupatila prilagođena osobama sa invaliditetom',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Ishrana
            [
                'naziv' => 'Standardna ishrana',
                'slug' => 'standardna-ishrana',
                'kategorija' => 'ishrana',
                'opis' => 'Redovna, uravnotežena ishrana',
                'redoslijed' => 9,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dijetetska ishrana',
                'slug' => 'dijetetska',
                'kategorija' => 'ishrana',
                'opis' => 'Specijalna dijetetska ishrana',
                'redoslijed' => 10,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Posebne dijete',
                'slug' => 'posebne-dijete',
                'kategorija' => 'ishrana',
                'opis' => 'Posebne dijete za specifične zdravstvene potrebe',
                'redoslijed' => 11,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($uslovi as $uslov) {
            DB::table('smjestaj_uslovi')->updateOrInsert(
                ['slug' => $uslov['slug']],
                $uslov
            );
        }

        $this->command->info('✅ Smještaj i uslovi uspješno kreirani/ažurirani!');
    }
}
