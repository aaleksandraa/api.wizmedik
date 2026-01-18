<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndikacijeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $indikacije = [
            // Reumatologija
            [
                'naziv' => 'Reumatološka oboljenja',
                'slug' => 'reumatologija',
                'kategorija' => 'reumatologija',
                'opis' => 'Reumatske bolesti, artritisi i upalne bolesti zglobova',
                'medicinski_opis' => 'Obuhvata reumatoidni artritis, psorijazni artritis, ankilozirajući spondilitis i druge upalne reumatske bolesti.',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Degenerativna oboljenja zglobova',
                'slug' => 'degenerativna-zglobovi',
                'kategorija' => 'reumatologija',
                'opis' => 'Osteoartritis, degenerativne promjene zglobova',
                'medicinski_opis' => 'Degenerativna oboljenja zglobova uključujući osteoartritis kuka, koljena, ramena i drugih zglobova.',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Ortopedija
            [
                'naziv' => 'Ortopedska i lokomotorna stanja',
                'slug' => 'ortopedija',
                'kategorija' => 'ortopedija',
                'opis' => 'Ortopedska stanja, povrede i deformiteti',
                'medicinski_opis' => 'Ortopedska stanja lokomotornog sistema, uključujući posttraumatska stanja i deformitete.',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Bolovi u leđima i kičmi',
                'slug' => 'bolovi-leda',
                'kategorija' => 'ortopedija',
                'opis' => 'Lumbalna i cervikalna spondiloza, bolovi u kičmi',
                'medicinski_opis' => 'Hronični bolovi u lumbalnoj i cervikalnoj kičmi, spondiloza, diskus hernija.',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Postoperativna rehabilitacija',
                'slug' => 'postoperativna',
                'kategorija' => 'ortopedija',
                'opis' => 'Oporavak nakon ortopedskih operacija',
                'medicinski_opis' => 'Rehabilitacija nakon ugradnje proteza, rekonstrukcije ligamenata, osteosinteza i drugih ortopedskih procedura.',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Neurologija
            [
                'naziv' => 'Neurološka rehabilitacija',
                'slug' => 'neurologija',
                'kategorija' => 'neurologija',
                'opis' => 'Oporavak nakon moždanog udara, neuropatije',
                'medicinski_opis' => 'Rehabilitacija nakon cerebrovaskularnog insulta, neuropatije, određena neurološka stanja.',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Sport
            [
                'naziv' => 'Sportske povrede',
                'slug' => 'sportske-povrede',
                'kategorija' => 'sport',
                'opis' => 'Rehabilitacija sportskih povreda',
                'medicinski_opis' => 'Rehabilitacija povreda mišića, tetiva, ligamenata i funkcionalni oporavak sportista.',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Respiratorno
            [
                'naziv' => 'Respiratorna oboljenja',
                'slug' => 'respiratorna',
                'kategorija' => 'respiratorno',
                'opis' => 'Astma, hronični bronhitis, respiratorni oporavak',
                'medicinski_opis' => 'Astma, hronični bronhitis, HOBP, postinfektivni respiratorni oporavak.',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Kardiovaskularno
            [
                'naziv' => 'Kardiovaskularna rehabilitacija',
                'slug' => 'kardiovaskularna',
                'kategorija' => 'kardiovaskularno',
                'opis' => 'Oporavak i kondicioniranje (prema indikaciji)',
                'medicinski_opis' => 'Programi kardiovaskularne rehabilitacije i kondicioniranja uz medicinski nadzor.',
                'redoslijed' => 9,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Dermatologija
            [
                'naziv' => 'Dermatološka stanja',
                'slug' => 'dermatologija',
                'kategorija' => 'dermatologija',
                'opis' => 'Psorijaza, ekcemi, hronične dermatoze',
                'medicinski_opis' => 'Psorijaza, atopijski dermatitis, hronični ekcemi i druge dermatoze.',
                'redoslijed' => 10,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Hronični bol
            [
                'naziv' => 'Hronični bolni sindromi',
                'slug' => 'hronicni-bol',
                'kategorija' => 'bol',
                'opis' => 'Fibromialgija, hronični bol, oporavak',
                'medicinski_opis' => 'Fibromialgija, hronični bolni sindromi, stres-related oporavak.',
                'redoslijed' => 11,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Metabolizam
            [
                'naziv' => 'Metabolička stanja',
                'slug' => 'metabolizam',
                'kategorija' => 'metabolizam',
                'opis' => 'Gojaznost, metabolički sindrom (suportno)',
                'medicinski_opis' => 'Suportna terapija kod gojaznosti i metaboličkog sindroma kroz program kretanja i edukacije.',
                'redoslijed' => 12,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($indikacije as $indikacija) {
            DB::table('indikacije')->updateOrInsert(
                ['slug' => $indikacija['slug']],
                $indikacija
            );
        }

        $this->command->info('✅ Indikacije uspješno kreirane/ažurirane!');
    }
}
