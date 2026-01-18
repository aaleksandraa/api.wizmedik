<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VrsteBanjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prvo obriši sve postojeće vrste
        DB::table('vrste_banja')->truncate();

        $vrste = [
            [
                'naziv' => 'Termalna banja',
                'slug' => 'termalna',
                'opis' => 'Banje sa termalnim izvorima tople vode prirodnog porijekla, bogate mineralima. Koriste se kod reumatskih oboljenja, bolnih sindroma i rehabilitacije.',
                'ikona' => 'thermometer',
                'redoslijed' => 1,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Mineralna banja',
                'slug' => 'mineralna',
                'opis' => 'Banje sa mineralnim vodama specifičnog hemijskog sastava. Koriste se kroz kupke, obloge ili druge balneoterapijske postupke.',
                'ikona' => 'droplet',
                'redoslijed' => 2,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Sumporna banja',
                'slug' => 'sumporna',
                'opis' => 'Posebna vrsta mineralnih banja sa sumpornom vodom, često korištena za terapiju kožnih i reumatskih oboljenja.',
                'ikona' => 'atom',
                'redoslijed' => 3,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Klimatska banja',
                'slug' => 'klimatska',
                'opis' => 'Banje gdje je primarni terapijski faktor klima – nadmorska visina, kvalitet zraka i mikroklimatski uslovi. Koriste se kod respiratornih stanja.',
                'ikona' => 'cloud-sun',
                'redoslijed' => 4,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Peloidna banja',
                'slug' => 'peloidna',
                'opis' => 'Banje koje primjenjuju ljekovito blato (peloid) u obliku obloga ili kupki, najčešće kod degenerativnih oboljenja zglobova.',
                'ikona' => 'layers',
                'redoslijed' => 5,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Wellness centar',
                'slug' => 'wellness',
                'opis' => 'Moderni wellness i spa centri sa širokim spektrom tretmana za opuštanje, njegu tijela i poboljšanje opšteg zdravlja.',
                'ikona' => 'spa',
                'redoslijed' => 6,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Rehabilitacijski centar',
                'slug' => 'rehabilitacijski',
                'opis' => 'Specijalizovane ustanove fokusirane na medicinsku rehabilitaciju uz stručni tim (fizijatar, fizioterapeuti) sa individualno prilagođenim programima.',
                'ikona' => 'activity',
                'redoslijed' => 7,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Banjsko liječenje',
                'slug' => 'banjsko-lijecenje',
                'opis' => 'Specijalizovane ustanove koje kombinuju prirodne terapijske faktore i medicinski nadzor u cilju liječenja i oporavka.',
                'ikona' => 'home',
                'redoslijed' => 8,
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('vrste_banja')->insert($vrste);

        $this->command->info('✅ Vrste banja uspješno kreirane (8 vrsta)!');
    }
}
