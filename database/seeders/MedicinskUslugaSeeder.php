<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicinskUslugaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usluge = [
            [
                'naziv' => 'Sestrinska njega',
                'slug' => 'sestrinska',
                'opis' => 'Osnovna sestrinska njega i medicinska podrška',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Terapija lijekovima',
                'slug' => 'terapija-lijekovi',
                'opis' => 'Administracija i praćenje terapije lijekovima',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Praćenje vitalnih parametara',
                'slug' => 'vitalni-parametri',
                'opis' => 'Monitoring vitalnih funkcija (pritisak, puls, temperatura)',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Previjanje i njega rana',
                'slug' => 'previjanje-rane',
                'opis' => 'Profesionalna njega rana i previjanje',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Njega nepokretnih osoba',
                'slug' => 'njega-nepokretnih',
                'opis' => 'Specijalizovana njega za nepokretne pacijente',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Kateter / stoma njega',
                'slug' => 'kateter-stoma',
                'opis' => 'Profesionalna njega katetera i stoma',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Fizikalna terapija (osnovna)',
                'slug' => 'fizikalna-osnovna',
                'opis' => 'Osnovna fizikalna terapija i vježbe',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Rehabilitacione vježbe',
                'slug' => 'rehabilitacione-vjezbe',
                'opis' => 'Vježbe za rehabilitaciju i oporavak',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Palijativna njega',
                'slug' => 'palijativna-njega',
                'opis' => 'Palijativna medicinska njega i podrška',
                'redoslijed' => 9,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Hitna medicinska intervencija',
                'slug' => 'hitna-intervencija',
                'opis' => 'Hitna medicinska pomoć i intervencije',
                'redoslijed' => 10,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Saradnja sa vanjskim ljekarima',
                'slug' => 'vanjski-ljekari',
                'opis' => 'Koordinacija sa vanjskim ljekarima i specijalistima',
                'redoslijed' => 11,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Saradnja sa bolnicama/klinikama',
                'slug' => 'bolnice-klinike',
                'opis' => 'Koordinacija sa zdravstvenim ustanovama',
                'redoslijed' => 12,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($usluge as $usluga) {
            DB::table('medicinske_usluge')->updateOrInsert(
                ['slug' => $usluga['slug']],
                $usluga
            );
        }

        $this->command->info('✅ Medicinske usluge uspješno kreirane/ažurirane!');
    }
}
