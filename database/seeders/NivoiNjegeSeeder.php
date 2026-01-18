<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NivoiNjegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nivoi = [
            [
                'naziv' => 'Osnovna njega',
                'slug' => 'osnovna',
                'opis' => 'Pomoć u svakodnevnim aktivnostima kao što su kupanje, oblačenje i ishrana',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Pojačana njega',
                'slug' => 'pojacana',
                'opis' => 'Intenzivnija pomoć i nadzor za osobe sa većim potrebama',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Stalna medicinska njega (24/7)',
                'slug' => 'stalna-24-7',
                'opis' => 'Kontinuirana medicinska podrška sa stalnim prisustvom medicinskog osoblja',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Specijalizovana njega',
                'slug' => 'specijalizovana',
                'opis' => 'Specijalizovana medicinska njega za specifične zdravstvene stanja',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($nivoi as $nivo) {
            DB::table('nivoi_njege')->updateOrInsert(
                ['slug' => $nivo['slug']],
                $nivo
            );
        }

        $this->command->info('✅ Nivoi njege uspješno kreirani/ažurirani!');
    }
}
