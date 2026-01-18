<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TerapijeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terapije = [
            // Balneoterapija
            [
                'naziv' => 'Balneoterapija',
                'slug' => 'balneoterapija',
                'kategorija' => 'balneoterapija',
                'opis' => 'Kupke u mineralnoj ili termalnoj vodi',
                'medicinski_opis' => 'Terapijske kupke u prirodnoj mineralnoj ili termalnoj vodi sa specifičnim hemijskim sastavom.',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Hidroterapija',
                'slug' => 'hidroterapija',
                'kategorija' => 'balneoterapija',
                'opis' => 'Terapija u vodi, vježbe u vodi',
                'medicinski_opis' => 'Terapijske vježbe i tretmani u vodi, korišćenje hidrostatskog pritiska i otpora vode.',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Terapijske vježbe u vodi',
                'slug' => 'vjezbe-voda',
                'kategorija' => 'balneoterapija',
                'opis' => 'Vođene vježbe u bazenima',
                'medicinski_opis' => 'Strukturirane terapijske vježbe u vodi pod nadzorom fizioterapeuta.',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Podvodna masaža',
                'slug' => 'podvodna-masaza',
                'kategorija' => 'balneoterapija',
                'opis' => 'Masaža mlazom vode pod pritiskom',
                'medicinski_opis' => 'Terapijska masaža pomoću mlaza vode pod kontrolisanim pritiskom.',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Fizikalna terapija
            [
                'naziv' => 'Kineziterapija',
                'slug' => 'kineziterapija',
                'kategorija' => 'fizikalna',
                'opis' => 'Terapijske vježbe i pokret',
                'medicinski_opis' => 'Terapija pokretom, aktivne i pasivne vježbe za obnovu funkcije.',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Fizikalna terapija aparatima',
                'slug' => 'fizikalna-aparati',
                'kategorija' => 'fizikalna',
                'opis' => 'Elektroterapija, ultrazvuk, laser',
                'medicinski_opis' => 'Primjena fizikalnih agenasa: elektroterapija, TENS, ultrazvuk, magnetoterapija, laser.',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Manualna terapija',
                'slug' => 'manualna',
                'kategorija' => 'fizikalna',
                'opis' => 'Terapija rukama, mobilizacija',
                'medicinski_opis' => 'Manualne tehnike mobilizacije zglobova, mišića i mekih tkiva.',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Radna terapija',
                'slug' => 'radna',
                'kategorija' => 'fizikalna',
                'opis' => 'Obnova svakodnevnih aktivnosti',
                'medicinski_opis' => 'Terapija fokusirana na obnovu sposobnosti za obavljanje svakodnevnih aktivnosti.',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Programi postoperativne rehabilitacije',
                'slug' => 'postoperativna-rehabilitacija',
                'kategorija' => 'fizikalna',
                'opis' => 'Strukturirani rehabilitacioni programi',
                'medicinski_opis' => 'Individualno prilagođeni programi rehabilitacije nakon ortopedskih i drugih operacija.',
                'redoslijed' => 9,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Peloidne i termo procedure
            [
                'naziv' => 'Terapija ljekovitim blatom',
                'slug' => 'blato',
                'kategorija' => 'peloidna',
                'opis' => 'Obloge i kupke ljekovitim blatom',
                'medicinski_opis' => 'Primjena peloidnih obloga i kupki sa ljekovitim blatom.',
                'redoslijed' => 10,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Parafinske obloge',
                'slug' => 'parafin',
                'kategorija' => 'peloidna',
                'opis' => 'Toplinske obloge parafinom',
                'medicinski_opis' => 'Termoterapija pomoću parafinskih obloga.',
                'redoslijed' => 11,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Termoterapija',
                'slug' => 'termoterapija',
                'kategorija' => 'peloidna',
                'opis' => 'Terapija toplinom',
                'medicinski_opis' => 'Primjena toplote u terapijske svrhe različitim metodama.',
                'redoslijed' => 12,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Krioterapija',
                'slug' => 'krioterapija',
                'kategorija' => 'peloidna',
                'opis' => 'Terapija hladnoćom',
                'medicinski_opis' => 'Primjena kontrolisane hladnoće u terapijske svrhe.',
                'redoslijed' => 13,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Respiratorne procedure
            [
                'naziv' => 'Inhalacije',
                'slug' => 'inhalacije',
                'kategorija' => 'respiratorna',
                'opis' => 'Inhalacije mineralnom vodom, slani aerosoli',
                'medicinski_opis' => 'Inhalaciona terapija mineralnom vodom ili slanim aerosolima.',
                'redoslijed' => 14,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Respiratorna fizioterapija',
                'slug' => 'respiratorna-fizioterapija',
                'kategorija' => 'respiratorna',
                'opis' => 'Vježbe disanja, drenažne tehnike',
                'medicinski_opis' => 'Tehnike respiratorne fizioterapije, vježbe disanja, drenažne pozicije.',
                'redoslijed' => 15,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Medicinske usluge
            [
                'naziv' => 'Pregled fizijatra',
                'slug' => 'pregled-fizijatra',
                'kategorija' => 'medicinska',
                'opis' => 'Specijalistički pregled',
                'medicinski_opis' => 'Specijalistički pregled fizijatra sa procjenom stanja i planom terapije.',
                'redoslijed' => 16,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Individualni rehabilitacioni plan',
                'slug' => 'rehabilitacioni-plan',
                'kategorija' => 'medicinska',
                'opis' => 'Izrada personalizovanog plana',
                'medicinski_opis' => 'Kreiranje individualnog rehabilitacionog plana prilagođenog pacijentu.',
                'redoslijed' => 17,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Procjena funkcionalnog statusa',
                'slug' => 'procjena-statusa',
                'kategorija' => 'medicinska',
                'opis' => 'Testovi pokreta, snage, funkcije',
                'medicinski_opis' => 'Dijagnostička procjena funkcionalnog statusa, testovi pokretljivosti i snage.',
                'redoslijed' => 18,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Dodatne usluge
            [
                'naziv' => 'Medicinska masaža',
                'slug' => 'medicinska-masaza',
                'kategorija' => 'dodatna',
                'opis' => 'Terapijska masaža',
                'medicinski_opis' => 'Medicinska masaža u terapijske svrhe.',
                'redoslijed' => 19,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Nutricionističko savjetovanje',
                'slug' => 'nutricionista',
                'kategorija' => 'dodatna',
                'opis' => 'Savjeti o ishrani',
                'medicinski_opis' => 'Nutricionističko savjetovanje i edukacija o pravilnoj ishrani.',
                'redoslijed' => 20,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($terapije as $terapija) {
            DB::table('terapije')->updateOrInsert(
                ['slug' => $terapija['slug']],
                $terapija
            );
        }

        $this->command->info('✅ Terapije uspješno kreirane/ažurirane!');
    }
}
