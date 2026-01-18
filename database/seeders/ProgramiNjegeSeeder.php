<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramiNjegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programi = [
            [
                'naziv' => 'Demencija / Alzheimer program',
                'slug' => 'demencija-alzheimer',
                'opis' => 'Specijalizovana njega za osobe sa demencijom i Alzheimer bolešću',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Program za nepokretne osobe',
                'slug' => 'nepokretne-osobe',
                'opis' => 'Specijalizovana njega za nepokretne pacijente',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Palijativni program',
                'slug' => 'palijativni',
                'opis' => 'Palijativna njega i podrška za terminalno bolesne',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Postoperativni oporavak',
                'slug' => 'postoperativni',
                'opis' => 'Oporavak i rehabilitacija nakon operacija',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Prevencija i tretman dekubitusa',
                'slug' => 'dekubitus',
                'opis' => 'Prevencija i liječenje dekubitusa (prolježanja)',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dijabetički monitoring',
                'slug' => 'dijabetes',
                'opis' => 'Praćenje i kontrola dijabetesa',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Nutritivna podrška / posebne dijete',
                'slug' => 'nutritivna',
                'opis' => 'Nutritivna podrška i specijalne dijete',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Individualni plan njege',
                'slug' => 'individualni',
                'opis' => 'Personalizovani plan njege prilagođen potrebama',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($programi as $program) {
            DB::table('programi_njege')->updateOrInsert(
                ['slug' => $program['slug']],
                $program
            );
        }

        $this->command->info('✅ Programi njege uspješno kreirani/ažurirani!');
    }
}
