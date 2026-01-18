<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoviDomovaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipovi = [
            [
                'naziv' => 'Dom za starije osobe',
                'slug' => 'dom-starije',
                'opis' => 'Osnovna njega i smještaj za starije osobe koje trebaju pomoć u svakodnevnim aktivnostima',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dom za starija i bolesna lica',
                'slug' => 'dom-starija-bolesna',
                'opis' => 'Kombinovana njega za starije osobe sa zdravstvenim problemima',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dom sa stalnom medicinskom njegom (24/7)',
                'slug' => 'dom-24-7',
                'opis' => 'Kontinuirana medicinska njega sa stalnim prisustvom medicinskog osoblja',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Gerijatrijski dom',
                'slug' => 'gerijatrijski',
                'opis' => 'Specijalizovana gerijatrijska njega sa fokus om na starije osobe',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Palijativna njega / hospis',
                'slug' => 'palijativna',
                'opis' => 'Palijativna i terminalna njega za osobe u završnoj fazi bolesti',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Rehabilitacioni dom / oporavak',
                'slug' => 'rehabilitacioni',
                'opis' => 'Rehabilitacija i oporavak nakon bolesti ili operacija',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tipovi as $tip) {
            DB::table('tipovi_domova')->updateOrInsert(
                ['slug' => $tip['slug']],
                $tip
            );
        }

        $this->command->info('✅ Tipovi domova uspješno kreirani/ažurirani!');
    }
}
