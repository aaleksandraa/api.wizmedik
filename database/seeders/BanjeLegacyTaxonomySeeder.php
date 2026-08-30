<?php

namespace Database\Seeders;

use App\Models\Indikacija;
use App\Models\Terapija;
use Illuminate\Database\Seeder;

/**
 * Upserts indikacije/terapije slugovi koje koristi v12 banje import (BanjeSeeder set),
 * bez kreiranja demo banja.
 */
class BanjeLegacyTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->indikacije() as $indikacija) {
            Indikacija::updateOrCreate(
                ['slug' => $indikacija['slug']],
                array_merge($indikacija, ['aktivan' => true])
            );
        }

        foreach ($this->terapije() as $terapija) {
            Terapija::updateOrCreate(
                ['slug' => $terapija['slug']],
                array_merge($terapija, ['aktivan' => true])
            );
        }

        $this->command?->info('✅ Banje legacy taksonomija (indikacije/terapije) ažurirana.');
    }

    private function indikacije(): array
    {
        return [
            ['naziv' => 'Reumatske bolesti', 'slug' => 'reumatske-bolesti', 'opis' => 'Artritis, artroza, reumatoidni artritis', 'redoslijed' => 101],
            ['naziv' => 'Bolesti lokomotornog sistema', 'slug' => 'lokomotorni-sistem', 'opis' => 'Bolesti kostiju, zglobova i mišića', 'redoslijed' => 102],
            ['naziv' => 'Neurološke bolesti', 'slug' => 'neuroloske-bolesti', 'opis' => 'Stanja nakon moždanog udara, multiple skleroze', 'redoslijed' => 103],
            ['naziv' => 'Kardiovaskularne bolesti', 'slug' => 'kardiovaskularne', 'opis' => 'Bolesti srca i krvnih sudova', 'redoslijed' => 104],
            ['naziv' => 'Respiratorne bolesti', 'slug' => 'respiratorne', 'opis' => 'Astma, bronhitis, KOPB', 'redoslijed' => 105],
            ['naziv' => 'Kožne bolesti', 'slug' => 'kozne-bolesti', 'opis' => 'Psorijaza, ekcem, dermatitis', 'redoslijed' => 106],
            ['naziv' => 'Ginekološke bolesti', 'slug' => 'ginekoloske', 'opis' => 'Upale, neplodnost, menopauza', 'redoslijed' => 107],
            ['naziv' => 'Postoperativna rehabilitacija', 'slug' => 'postoperativna', 'opis' => 'Oporavak nakon operacija', 'redoslijed' => 108],
            ['naziv' => 'Stres i anksioznost', 'slug' => 'stres-anksioznost', 'opis' => 'Mentalno zdravlje i opuštanje', 'redoslijed' => 109],
            ['naziv' => 'Dijabetes', 'slug' => 'dijabetes', 'opis' => 'Šećerna bolest tip 1 i 2', 'redoslijed' => 110],
        ];
    }

    private function terapije(): array
    {
        return [
            ['naziv' => 'Hidroterapija', 'slug' => 'hidroterapija', 'opis' => 'Terapija vodom - bazeni, kupke, tuševi', 'kategorija' => 'voda', 'redoslijed' => 101],
            ['naziv' => 'Balneoterapija', 'slug' => 'balneoterapija', 'opis' => 'Kupanje u mineralnoj vodi', 'kategorija' => 'voda', 'redoslijed' => 102],
            ['naziv' => 'Peloidoterapija', 'slug' => 'peloidoterapija', 'opis' => 'Terapija ljekovitim blatom', 'kategorija' => 'blato', 'redoslijed' => 103],
            ['naziv' => 'Kineziterapija', 'slug' => 'kineziterapija', 'opis' => 'Terapija pokretom - vježbe', 'kategorija' => 'fizikalna', 'redoslijed' => 104],
            ['naziv' => 'Elektroterapija', 'slug' => 'elektroterapija', 'opis' => 'Terapija električnom strujom', 'kategorija' => 'fizikalna', 'redoslijed' => 105],
            ['naziv' => 'Magnetoterapija', 'slug' => 'magnetoterapija', 'opis' => 'Terapija magnetnim poljem', 'kategorija' => 'fizikalna', 'redoslijed' => 106],
            ['naziv' => 'Ultrazvuk', 'slug' => 'ultrazvuk', 'opis' => 'Terapija ultrazvukom', 'kategorija' => 'fizikalna', 'redoslijed' => 107],
            ['naziv' => 'Laser terapija', 'slug' => 'laser', 'opis' => 'Terapija laserom', 'kategorija' => 'fizikalna', 'redoslijed' => 108],
            ['naziv' => 'Masaža', 'slug' => 'masaza', 'opis' => 'Klasična i terapeutska masaža', 'kategorija' => 'manualna', 'redoslijed' => 109],
            ['naziv' => 'Limfna drenaža', 'slug' => 'limfna-drenaza', 'opis' => 'Manualna limfna drenaža', 'kategorija' => 'manualna', 'redoslijed' => 110],
            ['naziv' => 'Inhalacije', 'slug' => 'inhalacije', 'opis' => 'Udisanje ljekovitih para', 'kategorija' => 'respiratorna', 'redoslijed' => 111],
            ['naziv' => 'Sauna', 'slug' => 'sauna', 'opis' => 'Finska sauna, infracrvena sauna', 'kategorija' => 'wellness', 'redoslijed' => 112],
            ['naziv' => 'Aromaterapija', 'slug' => 'aromaterapija', 'opis' => 'Terapija eteričnim uljima', 'kategorija' => 'wellness', 'redoslijed' => 113],
            ['naziv' => 'Akupunktura', 'slug' => 'akupunktura', 'opis' => 'Tradicionalna kineska medicina', 'kategorija' => 'alternativna', 'redoslijed' => 114],
        ];
    }
}
