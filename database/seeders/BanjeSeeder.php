<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Banja;
use App\Models\VrstaBanje;
use App\Models\Indikacija;
use App\Models\Terapija;

class BanjeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏨 Kreiranje banja i povezanih podataka...');

        // Kreiraj indikacije ako ne postoje
        $this->createIndikacije();

        // Kreiraj terapije ako ne postoje
        $this->createTerapije();

        // Kreiraj test banje
        $this->createBanje();

        // Poveži banju sa spa_manager korisnikom
        $this->linkBanjaToUser();

        $this->command->info('✅ Banje uspješno kreirane!');
    }

    private function createIndikacije(): void
    {
        $indikacije = [
            ['naziv' => 'Reumatske bolesti', 'slug' => 'reumatske-bolesti', 'opis' => 'Artritis, artroza, reumatoidni artritis', 'redoslijed' => 1],
            ['naziv' => 'Bolesti lokomotornog sistema', 'slug' => 'lokomotorni-sistem', 'opis' => 'Bolesti kostiju, zglobova i mišića', 'redoslijed' => 2],
            ['naziv' => 'Neurološke bolesti', 'slug' => 'neuroloske-bolesti', 'opis' => 'Stanja nakon moždanog udara, multiple skleroze', 'redoslijed' => 3],
            ['naziv' => 'Kardiovaskularne bolesti', 'slug' => 'kardiovaskularne', 'opis' => 'Bolesti srca i krvnih sudova', 'redoslijed' => 4],
            ['naziv' => 'Respiratorne bolesti', 'slug' => 'respiratorne', 'opis' => 'Astma, bronhitis, KOPB', 'redoslijed' => 5],
            ['naziv' => 'Kožne bolesti', 'slug' => 'kozne-bolesti', 'opis' => 'Psorijaza, ekcem, dermatitis', 'redoslijed' => 6],
            ['naziv' => 'Ginekološke bolesti', 'slug' => 'ginekoloske', 'opis' => 'Upale, neplodnost, menopauza', 'redoslijed' => 7],
            ['naziv' => 'Postoperativna rehabilitacija', 'slug' => 'postoperativna', 'opis' => 'Oporavak nakon operacija', 'redoslijed' => 8],
            ['naziv' => 'Stres i anksioznost', 'slug' => 'stres-anksioznost', 'opis' => 'Mentalno zdravlje i opuštanje', 'redoslijed' => 9],
            ['naziv' => 'Dijabetes', 'slug' => 'dijabetes', 'opis' => 'Šećerna bolest tip 1 i 2', 'redoslijed' => 10],
        ];

        foreach ($indikacije as $indikacija) {
            Indikacija::updateOrCreate(
                ['slug' => $indikacija['slug']],
                array_merge($indikacija, ['aktivan' => true])
            );
        }

        $this->command->info('  ✓ Indikacije kreirane');
    }

    private function createTerapije(): void
    {
        $terapije = [
            ['naziv' => 'Hidroterapija', 'slug' => 'hidroterapija', 'opis' => 'Terapija vodom - bazeni, kupke, tuševi', 'kategorija' => 'voda', 'redoslijed' => 1],
            ['naziv' => 'Balneoterapija', 'slug' => 'balneoterapija', 'opis' => 'Kupanje u mineralnoj vodi', 'kategorija' => 'voda', 'redoslijed' => 2],
            ['naziv' => 'Peloidoterapija', 'slug' => 'peloidoterapija', 'opis' => 'Terapija ljekovitim blatom', 'kategorija' => 'blato', 'redoslijed' => 3],
            ['naziv' => 'Kineziterapija', 'slug' => 'kineziterapija', 'opis' => 'Terapija pokretom - vježbe', 'kategorija' => 'fizikalna', 'redoslijed' => 4],
            ['naziv' => 'Elektroterapija', 'slug' => 'elektroterapija', 'opis' => 'Terapija električnom strujom', 'kategorija' => 'fizikalna', 'redoslijed' => 5],
            ['naziv' => 'Magnetoterapija', 'slug' => 'magnetoterapija', 'opis' => 'Terapija magnetnim poljem', 'kategorija' => 'fizikalna', 'redoslijed' => 6],
            ['naziv' => 'Ultrazvuk', 'slug' => 'ultrazvuk', 'opis' => 'Terapija ultrazvukom', 'kategorija' => 'fizikalna', 'redoslijed' => 7],
            ['naziv' => 'Laser terapija', 'slug' => 'laser', 'opis' => 'Terapija laserom', 'kategorija' => 'fizikalna', 'redoslijed' => 8],
            ['naziv' => 'Masaža', 'slug' => 'masaza', 'opis' => 'Klasična i terapeutska masaža', 'kategorija' => 'manualna', 'redoslijed' => 9],
            ['naziv' => 'Limfna drenaža', 'slug' => 'limfna-drenaza', 'opis' => 'Manualna limfna drenaža', 'kategorija' => 'manualna', 'redoslijed' => 10],
            ['naziv' => 'Inhalacije', 'slug' => 'inhalacije', 'opis' => 'Udisanje ljekovitih para', 'kategorija' => 'respiratorna', 'redoslijed' => 11],
            ['naziv' => 'Sauna', 'slug' => 'sauna', 'opis' => 'Finska sauna, infracrvena sauna', 'kategorija' => 'wellness', 'redoslijed' => 12],
            ['naziv' => 'Aromaterapija', 'slug' => 'aromaterapija', 'opis' => 'Terapija eteričnim uljima', 'kategorija' => 'wellness', 'redoslijed' => 13],
            ['naziv' => 'Akupunktura', 'slug' => 'akupunktura', 'opis' => 'Tradicionalna kineska medicina', 'kategorija' => 'alternativna', 'redoslijed' => 14],
        ];

        foreach ($terapije as $terapija) {
            Terapija::updateOrCreate(
                ['slug' => $terapija['slug']],
                array_merge($terapija, ['aktivan' => true])
            );
        }

        $this->command->info('  ✓ Terapije kreirane');
    }

    private function createBanje(): void
    {
        $banje = [
            [
                'naziv' => 'Terme Ilidža',
                'slug' => 'terme-ilidza',
                'grad' => 'Sarajevo',
                'regija' => 'Sarajevska',
                'adresa' => 'Butmirska cesta 18, Ilidža',
                'latitude' => 43.8297,
                'longitude' => 18.3103,
                'telefon' => '+387 33 772 000',
                'email' => 'info@terme-ilidza.ba',
                'website' => 'https://terme-ilidza.ba',
                'opis' => 'Najpoznatija banja u BiH sa termalnim izvorima i modernim wellness sadržajima. Idealno mjesto za odmor i rehabilitaciju.',
                'detaljni_opis' => 'Terme Ilidža su najstarija i najpoznatija banja u Bosni i Hercegovini. Smještene u predgrađu Sarajeva, nude širok spektar wellness i medicinskih tretmana baziranih na termalnoj vodi temperature 58°C.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatar, fizioterapeuti, medicinske sestre',
                'ima_smjestaj' => true,
                'broj_kreveta' => 200,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.6,
                'broj_recenzija' => 156,
                'broj_pregleda' => 2500,
                'featured_slika' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800',
                'vrste' => ['termalna', 'wellness'],
                'indikacije' => ['reumatske-bolesti', 'lokomotorni-sistem', 'neuroloske-bolesti'],
                'terapije' => ['hidroterapija', 'balneoterapija', 'kineziterapija', 'masaza', 'sauna'],
            ],
            [
                'naziv' => 'Banja Vrućica',
                'slug' => 'banja-vrucica',
                'grad' => 'Teslić',
                'regija' => 'Dobojska',
                'adresa' => 'Banja Vrućica bb, Teslić',
                'latitude' => 44.5833,
                'longitude' => 17.8500,
                'telefon' => '+387 53 431 100',
                'email' => 'info@banjavrucica.com',
                'website' => 'https://banjavrucica.com',
                'opis' => 'Poznata banja sa ljekovitom termalnom vodom i blatom. Specijalizirana za reumatske i kožne bolesti.',
                'detaljni_opis' => 'Banja Vrućica je smještena u prekrasnom prirodnom okruženju i poznata je po ljekovitoj termalnoj vodi i peloidu. Nudi kompletan program rehabilitacije i wellness tretmana.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatar, dermatolozi, fizioterapeuti',
                'ima_smjestaj' => true,
                'broj_kreveta' => 150,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.4,
                'broj_recenzija' => 89,
                'broj_pregleda' => 1800,
                'featured_slika' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800',
                'vrste' => ['termalna', 'mineralna'],
                'indikacije' => ['reumatske-bolesti', 'kozne-bolesti', 'respiratorne'],
                'terapije' => ['balneoterapija', 'peloidoterapija', 'inhalacije', 'elektroterapija'],
            ],
            [
                'naziv' => 'Reumal Fojnica',
                'slug' => 'reumal-fojnica',
                'grad' => 'Fojnica',
                'regija' => 'Srednjobosanska',
                'adresa' => 'Banjska 1, Fojnica',
                'latitude' => 43.9614,
                'longitude' => 17.8978,
                'telefon' => '+387 30 831 555',
                'email' => 'info@reumal.ba',
                'website' => 'https://reumal.ba',
                'opis' => 'Specijalizirana ustanova za rehabilitaciju reumatskih i neuroloških oboljenja sa dugom tradicijom.',
                'detaljni_opis' => 'Reumal Fojnica je vodeća ustanova za rehabilitaciju u BiH. Specijalizirana je za liječenje reumatskih, neuroloških i posttraumatskih stanja uz primjenu najsavremenijih metoda fizikalne medicine.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatri, neurolozi, fizioterapeuti, radni terapeuti',
                'ima_smjestaj' => true,
                'broj_kreveta' => 180,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.7,
                'broj_recenzija' => 203,
                'broj_pregleda' => 3200,
                'featured_slika' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800',
                'vrste' => ['termalna', 'rehabilitacijski'],
                'indikacije' => ['reumatske-bolesti', 'neuroloske-bolesti', 'postoperativna', 'lokomotorni-sistem'],
                'terapije' => ['hidroterapija', 'kineziterapija', 'elektroterapija', 'magnetoterapija', 'ultrazvuk', 'masaza'],
            ],
            [
                'naziv' => 'Banja Slatina',
                'slug' => 'banja-slatina',
                'grad' => 'Banja Luka',
                'regija' => 'Banjalučka',
                'adresa' => 'Slatina bb, Banja Luka',
                'latitude' => 44.8125,
                'longitude' => 17.1897,
                'telefon' => '+387 51 586 100',
                'email' => 'info@banjaslatina.com',
                'website' => 'https://banjaslatina.com',
                'opis' => 'Moderna banja u blizini Banja Luke sa termalnim izvorima i wellness sadržajima.',
                'detaljni_opis' => 'Banja Slatina je smještena u neposrednoj blizini Banja Luke i nudi širok spektar wellness i rehabilitacijskih usluga baziranih na termalnoj vodi.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatar, fizioterapeuti',
                'ima_smjestaj' => true,
                'broj_kreveta' => 100,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.3,
                'broj_recenzija' => 67,
                'broj_pregleda' => 1200,
                'featured_slika' => 'https://images.unsplash.com/photo-1600334129128-685c5582fd35?w=800',
                'vrste' => ['termalna', 'wellness'],
                'indikacije' => ['reumatske-bolesti', 'lokomotorni-sistem', 'stres-anksioznost'],
                'terapije' => ['hidroterapija', 'balneoterapija', 'masaza', 'sauna'],
            ],
            [
                'naziv' => 'Banja Guber',
                'slug' => 'banja-guber',
                'grad' => 'Srebrenica',
                'regija' => 'Podrinjska',
                'adresa' => 'Guber bb, Srebrenica',
                'latitude' => 44.1069,
                'longitude' => 19.2972,
                'telefon' => '+387 56 440 100',
                'email' => 'info@banjaguber.ba',
                'website' => 'https://banjaguber.ba',
                'opis' => 'Poznata banja sa ljekovitom mineralnom vodom, specijalizirana za bolesti probavnog sistema.',
                'detaljni_opis' => 'Banja Guber je poznata po ljekovitoj mineralnoj vodi koja se koristi za liječenje bolesti probavnog sistema, jetre i žuči. Smještena je u prekrasnom prirodnom okruženju.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => false,
                'medicinsko_osoblje' => 'Medicinske sestre, fizioterapeuti',
                'ima_smjestaj' => true,
                'broj_kreveta' => 80,
                'online_rezervacija' => false,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.1,
                'broj_recenzija' => 45,
                'broj_pregleda' => 800,
                'featured_slika' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800',
                'vrste' => ['mineralna', 'klimatska'],
                'indikacije' => ['dijabetes', 'kardiovaskularne'],
                'terapije' => ['balneoterapija', 'inhalacije'],
            ],
            [
                'naziv' => 'Banja Laktaši',
                'slug' => 'banja-laktasi',
                'grad' => 'Laktaši',
                'regija' => 'Banjalučka',
                'adresa' => 'Karađorđeva 44, Laktaši',
                'latitude' => 44.9089,
                'longitude' => 17.3017,
                'telefon' => '+387 51 535 200',
                'email' => 'info@banjalaktasi.com',
                'website' => 'https://banjalaktasi.com',
                'opis' => 'Termalna banja sa modernim wellness centrom i rehabilitacijskim programima.',
                'detaljni_opis' => 'Banja Laktaši je moderna termalna banja koja nudi širok spektar wellness i rehabilitacijskih usluga. Poznata je po kvalitetnoj termalnoj vodi i stručnom osoblju.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatar, fizioterapeuti, medicinske sestre',
                'ima_smjestaj' => true,
                'broj_kreveta' => 120,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.5,
                'broj_recenzija' => 112,
                'broj_pregleda' => 1600,
                'featured_slika' => 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=800',
                'vrste' => ['termalna', 'wellness', 'rehabilitacijski'],
                'indikacije' => ['reumatske-bolesti', 'lokomotorni-sistem', 'postoperativna'],
                'terapije' => ['hidroterapija', 'balneoterapija', 'kineziterapija', 'elektroterapija', 'masaza', 'sauna'],
            ],
            [
                'naziv' => 'Banja Dvorovi',
                'slug' => 'banja-dvorovi',
                'grad' => 'Bijeljina',
                'regija' => 'Semberija',
                'adresa' => 'Dvorovi bb, Bijeljina',
                'latitude' => 44.7569,
                'longitude' => 19.2142,
                'telefon' => '+387 55 210 300',
                'email' => 'info@banjadvorovi.com',
                'website' => 'https://banjadvorovi.com',
                'opis' => 'Termalna banja u Semberiji sa tradicionalnim pristupom liječenju i modernim sadržajima.',
                'detaljni_opis' => 'Banja Dvorovi je smještena u blizini Bijeljine i poznata je po termalnoj vodi koja se koristi za liječenje reumatskih i kožnih oboljenja.',
                'medicinski_nadzor' => true,
                'fizijatar_prisutan' => true,
                'medicinsko_osoblje' => 'Fizijatar, fizioterapeuti',
                'ima_smjestaj' => true,
                'broj_kreveta' => 90,
                'online_rezervacija' => true,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.2,
                'broj_recenzija' => 78,
                'broj_pregleda' => 950,
                'featured_slika' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800',
                'vrste' => ['termalna', 'mineralna'],
                'indikacije' => ['reumatske-bolesti', 'kozne-bolesti', 'lokomotorni-sistem'],
                'terapije' => ['hidroterapija', 'balneoterapija', 'peloidoterapija', 'masaza'],
            ],
        ];

        foreach ($banje as $banjaData) {
            $vrste = $banjaData['vrste'] ?? [];
            $indikacije = $banjaData['indikacije'] ?? [];
            $terapije = $banjaData['terapije'] ?? [];
            unset($banjaData['vrste'], $banjaData['indikacije'], $banjaData['terapije']);

            $banja = Banja::updateOrCreate(
                ['slug' => $banjaData['slug']],
                $banjaData
            );

            // Sync vrste
            $vrsteIds = VrstaBanje::whereIn('slug', $vrste)->pluck('id')->toArray();
            $banja->vrste()->sync($vrsteIds);

            // Sync indikacije
            $indikacijeIds = Indikacija::whereIn('slug', $indikacije)->pluck('id')->toArray();
            $indikacijeSync = [];
            foreach ($indikacijeIds as $index => $id) {
                $indikacijeSync[$id] = ['prioritet' => $index + 1];
            }
            $banja->indikacije()->sync($indikacijeSync);

            // Sync terapije
            $terapijeIds = Terapija::whereIn('slug', $terapije)->pluck('id')->toArray();
            $terapijeSync = [];
            foreach ($terapijeIds as $index => $id) {
                $terapijeSync[$id] = [
                    'cijena' => rand(20, 80),
                    'trajanje_minuta' => rand(20, 60),
                ];
            }
            $banja->terapije()->sync($terapijeSync);

            $this->command->info("  ✓ Banja '{$banja->naziv}' kreirana");
        }
    }

    private function linkBanjaToUser(): void
    {
        // Pronađi ili kreiraj spa_manager korisnika
        $spaUser = User::where('email', 'banja.test@medibih.ba')->first();

        if (!$spaUser) {
            $spaUser = User::create([
                'name' => 'Banja',
                'prezime' => 'Manager',
                'email' => 'banja.test@medibih.ba',
                'password' => Hash::make('BanjaTest123!'),
                'email_verified_at' => now(),
            ]);

            // Assign role
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'spa_manager', 'guard_name' => 'web']);
                $spaUser->assignRole($role);
            }

            $this->command->info('  ✓ Kreiran spa_manager korisnik: banja.test@medibih.ba');
        }

        // Poveži prvu banju sa korisnikom ako nije već povezana
        $banja = Banja::where('user_id', $spaUser->id)->first();

        if (!$banja) {
            $banja = Banja::first();
            if ($banja) {
                $banja->update(['user_id' => $spaUser->id]);
                $this->command->info("  ✓ Banja '{$banja->naziv}' povezana sa korisnikom banja.test@medibih.ba");
            }
        } else {
            $this->command->info("  ℹ️ Korisnik već ima banju: {$banja->naziv}");
        }

        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('📋 LOGIN PODACI ZA SPA DASHBOARD:');
        $this->command->info('========================================');
        $this->command->info('   Email: banja.test@medibih.ba');
        $this->command->info('   Password: BanjaTest123!');
        $this->command->info('   Dashboard: /spa-dashboard');
        $this->command->info('========================================');
    }
}
