<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DomoviSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get taxonomy IDs
        $tipoviDomova = DB::table('tipovi_domova')->pluck('id', 'slug');
        $nivoiNjege = DB::table('nivoi_njege')->pluck('id', 'slug');
        $programiNjege = DB::table('programi_njege')->pluck('id', 'slug');
        $medicinskUsluge = DB::table('medicinske_usluge')->pluck('id', 'slug');
        $smjestajUslovi = DB::table('smjestaj_uslovi')->pluck('id', 'slug');

        $domovi = [
            [
                'naziv' => 'Dom za starije "Sunce"',
                'slug' => 'dom-za-starije-sunce',
                'grad' => 'Sarajevo',
                'regija' => 'Sarajevska',
                'adresa' => 'Zmaja od Bosne 15, Sarajevo',
                'latitude' => 43.8563,
                'longitude' => 18.4131,
                'telefon' => '+387 33 123 456',
                'email' => 'info@dom-sunce.ba',
                'website' => 'https://dom-sunce.ba',
                'opis' => 'Moderni dom za starije osobe sa 24/7 medicinskim nadzorom i profesionalnim pristupom svakom štićeniku.',
                'detaljni_opis' => 'Dom za starije "Sunce" je moderna ustanova koja pruža sveobuhvatnu njegu za starije osobe. Naš tim stručnjaka osigurava 24/7 medicinsku podršku, individualne planove njege i toplu, obiteljsku atmosferu. Smješteni smo u mirnom dijelu Sarajeva sa prekrasnim pogledom na grad.',
                'tip_doma_id' => $tipoviDomova['dom-starija-bolesna'],
                'nivo_njege_id' => $nivoiNjege['stalna-24-7'],
                'accepts_tags' => ['starije osobe', 'demencija', 'dijabetes', 'hipertenzija'],
                'not_accepts_text' => 'Ne primamo osobe sa teškim psihijatrijskim oboljenjima ili zavisnostima.',
                'nurses_availability' => '24_7',
                'doctor_availability' => 'periodic',
                'has_physiotherapist' => true,
                'has_physiatrist' => false,
                'emergency_protocol' => true,
                'emergency_protocol_text' => 'Imamo protokol za hitne slučajeve sa direktnom vezom sa Kliničkim centrom.',
                'controlled_entry' => true,
                'video_surveillance' => true,
                'visiting_rules' => 'Posjete su dozvoljene svakodnevno od 10:00 do 20:00 sati.',
                'pricing_mode' => 'public',
                'price_from' => 1200.00,
                'price_includes' => 'Smještaj, ishrana, osnovna medicinska njega, aktivnosti',
                'extra_charges' => 'Dodatne terapije, privatni ljekar, frizerske usluge',
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.5,
                'broj_recenzija' => 23,
                'broj_pregleda' => 156,
                'featured_slika' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800',
                'galerija' => [
                    'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800',
                    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800',
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800'
                ],
                'radno_vrijeme' => [
                    ['dan' => 'ponedeljak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'utorak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'sreda', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'cetvrtak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'petak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'subota', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'nedelja', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false]
                ],
                'faqs' => [
                    ['pitanje' => 'Kakva je procedura prijema?', 'odgovor' => 'Potreban je ljekarki nalaz, lična karta i razgovor sa našim timom.'],
                    ['pitanje' => 'Da li primate osobe sa demencijom?', 'odgovor' => 'Da, imamo specijalizirani program za osobe sa demencijom.'],
                    ['pitanje' => 'Koliko košta smještaj?', 'odgovor' => 'Cijena počinje od 1200 KM mjesečno, ovisno o nivou njege.']
                ],
                'meta_title' => 'Dom za starije Sunce Sarajevo - 24/7 medicinska njega',
                'meta_description' => 'Moderni dom za starije u Sarajevu sa profesionalnom njegom, medicinskim nadzorom i toplom atmosferom.',
                'meta_keywords' => 'dom za starije sarajevo, medicinska njega, demencija, starije osobe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Rehabilitacioni centar "Nada"',
                'slug' => 'rehabilitacioni-centar-nada',
                'grad' => 'Banja Luka',
                'regija' => 'Republika Srpska',
                'adresa' => 'Kralja Petra I 25, Banja Luka',
                'latitude' => 44.7722,
                'longitude' => 17.1910,
                'telefon' => '+387 51 234 567',
                'email' => 'kontakt@centar-nada.rs',
                'website' => 'https://centar-nada.rs',
                'opis' => 'Specijalizovani rehabilitacioni centar za oporavak nakon operacija i bolesti sa modernom opremom.',
                'detaljni_opis' => 'Rehabilitacioni centar "Nada" je vodeća ustanova za medicinsku rehabilitaciju u Republici Srpskoj. Specijalizovani smo za postoperativni oporavak, neurološku rehabilitaciju i fizikalnu terapiju. Naš tim čine iskusni fizijatri, fizioterapeuti i medicinske sestre.',
                'tip_doma_id' => $tipoviDomova['rehabilitacioni'],
                'nivo_njege_id' => $nivoiNjege['specijalizovana'],
                'accepts_tags' => ['postoperativni oporavak', 'neurološka rehabilitacija', 'ortopedske povrede'],
                'not_accepts_text' => 'Ne primamo osobe u terminalnoj fazi bolesti.',
                'nurses_availability' => 'shifts',
                'doctor_availability' => 'permanent',
                'has_physiotherapist' => true,
                'has_physiatrist' => true,
                'emergency_protocol' => true,
                'emergency_protocol_text' => 'Imamo vlastiti tim za hitne intervencije i saradnju sa UKC RS.',
                'controlled_entry' => false,
                'video_surveillance' => true,
                'visiting_rules' => 'Posjete su dozvoljene svakodnevno od 9:00 do 21:00 sati.',
                'pricing_mode' => 'public',
                'price_from' => 800.00,
                'price_includes' => 'Smještaj, ishrana, rehabilitacione terapije, ljekarki pregled',
                'extra_charges' => 'Dodatne fizikalne terapije, privatni tretmani',
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.8,
                'broj_recenzija' => 34,
                'broj_pregleda' => 289,
                'featured_slika' => 'https://images.unsplash.com/photo-1582719471137-c3967ffb1c42?w=800',
                'galerija' => [
                    'https://images.unsplash.com/photo-1582719471137-c3967ffb1c42?w=800',
                    'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=800',
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800',
                    'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w=800'
                ],
                'radno_vrijeme' => [
                    ['dan' => 'ponedeljak', 'od' => '07:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'utorak', 'od' => '07:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'sreda', 'od' => '07:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'cetvrtak', 'od' => '07:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'petak', 'od' => '07:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'subota', 'od' => '08:00', 'do' => '16:00', 'zatvoreno' => false],
                    ['dan' => 'nedelja', 'od' => '08:00', 'do' => '16:00', 'zatvoreno' => false]
                ],
                'faqs' => [
                    ['pitanje' => 'Koliko traje rehabilitacija?', 'odgovor' => 'Ovisno o stanju, obično 2-6 sedmica.'],
                    ['pitanje' => 'Da li primate pacijente sa invaliditetom?', 'odgovor' => 'Da, specijalizovani smo za rehabilitaciju osoba sa invaliditetom.'],
                    ['pitanje' => 'Kakva je oprema?', 'odgovor' => 'Imamo najmoderniju rehabilitacionu opremu i bazene za hidroterapiju.']
                ],
                'meta_title' => 'Rehabilitacioni centar Nada Banja Luka - Medicinska rehabilitacija',
                'meta_description' => 'Vodeći rehabilitacioni centar u RS za postoperativni oporavak i neurološku rehabilitaciju.',
                'meta_keywords' => 'rehabilitacija banja luka, fizijatar, fizioterapija, oporavak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Palijativni dom "Mir"',
                'slug' => 'palijativni-dom-mir',
                'grad' => 'Tuzla',
                'regija' => 'Tuzlanska',
                'adresa' => 'Turalićeva 10, Tuzla',
                'latitude' => 44.5386,
                'longitude' => 18.6708,
                'telefon' => '+387 35 345 678',
                'email' => 'info@dom-mir.ba',
                'website' => null,
                'opis' => 'Specijalizovani dom za palijativnu njegu sa fokusom na dostojanstvo i kvalitet života.',
                'detaljni_opis' => 'Palijativni dom "Mir" pruža specijalizovanu njegu za osobe u završnoj fazi bolesti. Naš pristup je usmeren na ublažavanje bola, pružanje psihološke podrške i održavanje dostojanstva naših štićenika. Radimo sa porodicama da osiguramo najbolju moguću njegu.',
                'tip_doma_id' => $tipoviDomova['palijativna'],
                'nivo_njege_id' => $nivoiNjege['specijalizovana'],
                'accepts_tags' => ['terminalno bolesni', 'onkološki pacijenti', 'palijativna njega'],
                'not_accepts_text' => 'Primamo samo osobe koje trebaju palijativnu njegu.',
                'nurses_availability' => '24_7',
                'doctor_availability' => 'on_call',
                'has_physiotherapist' => false,
                'has_physiatrist' => false,
                'emergency_protocol' => true,
                'emergency_protocol_text' => 'Imamo protokol za hitne slučajeve prilagođen palijativnoj njezi.',
                'controlled_entry' => false,
                'video_surveillance' => false,
                'visiting_rules' => 'Posjete su dozvoljene 24 sata dnevno za članove porodice.',
                'pricing_mode' => 'on_request',
                'price_from' => null,
                'price_includes' => null,
                'extra_charges' => null,
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.9,
                'broj_recenzija' => 18,
                'broj_pregleda' => 87,
                'featured_slika' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800',
                'galerija' => [
                    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800',
                    'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w=800',
                    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800',
                    'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800'
                ],
                'radno_vrijeme' => [
                    ['dan' => 'ponedeljak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'utorak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'sreda', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'cetvrtak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'petak', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'subota', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false],
                    ['dan' => 'nedelja', 'od' => '00:00', 'do' => '23:59', 'zatvoreno' => false]
                ],
                'faqs' => [
                    ['pitanje' => 'Šta je palijativna njega?', 'odgovor' => 'Specijalizovana njega fokusirana na ublažavanje bola i poboljšanje kvaliteta života.'],
                    ['pitanje' => 'Da li mogu porodica da posjećuje?', 'odgovor' => 'Da, posjete su dozvoljene 24 sata dnevno.'],
                    ['pitanje' => 'Kakva je psihološka podrška?', 'odgovor' => 'Imamo stručni tim za psihološku podršku pacijentima i porodicama.']
                ],
                'meta_title' => 'Palijativni dom Mir Tuzla - Specijalizovana palijativna njega',
                'meta_description' => 'Specijalizovani dom za palijativnu njegu u Tuzli sa fokusom na dostojanstvo i kvalitet života.',
                'meta_keywords' => 'palijativna njega tuzla, hospis, terminalno bolesni, onkologija',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Gerijatrijski centar "Zlatna jesen"',
                'slug' => 'gerijatrijski-centar-zlatna-jesen',
                'grad' => 'Mostar',
                'regija' => 'Hercegovačko-neretvanska',
                'adresa' => 'Alekse Šantića 8, Mostar',
                'latitude' => 43.3438,
                'longitude' => 17.8078,
                'telefon' => '+387 36 456 789',
                'email' => 'info@zlatna-jesen.ba',
                'website' => 'https://zlatna-jesen.ba',
                'opis' => 'Specijalizovani gerijatrijski centar sa holističkim pristupom njezi starijih osoba.',
                'detaljni_opis' => 'Gerijatrijski centar "Zlatna jesen" je moderna ustanova specijalizovana za njegu starijih osoba. Naš holistički pristup uključuje medicinsku njegu, fizikalnu terapiju, mentalne aktivnosti i socijalnu podršku. Smješteni smo u prekrasnom ambijentu sa pogledom na Neretvu.',
                'tip_doma_id' => $tipoviDomova['gerijatrijski'],
                'nivo_njege_id' => $nivoiNjege['pojacana'],
                'accepts_tags' => ['starije osobe', 'gerijatrijski pacijenti', 'demencija', 'Alzheimer'],
                'not_accepts_text' => 'Ne primamo osobe mlađe od 65 godina osim u posebnim slučajevima.',
                'nurses_availability' => 'shifts',
                'doctor_availability' => 'periodic',
                'has_physiotherapist' => true,
                'has_physiatrist' => false,
                'emergency_protocol' => true,
                'emergency_protocol_text' => 'Imamo protokol za gerijatrijske hitne slučajeve.',
                'controlled_entry' => true,
                'video_surveillance' => true,
                'visiting_rules' => 'Posjete su dozvoljene svakodnevno od 8:00 do 20:00 sati.',
                'pricing_mode' => 'public',
                'price_from' => 950.00,
                'price_includes' => 'Smještaj, ishrana, gerijatrijska njega, aktivnosti, terapije',
                'extra_charges' => 'Privatni ljekar, dodatne terapije, izleti',
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.3,
                'broj_recenzija' => 27,
                'broj_pregleda' => 198,
                'featured_slika' => 'https://images.unsplash.com/photo-1516733968668-dbdce39c4651?w=800',
                'galerija' => [
                    'https://images.unsplash.com/photo-1516733968668-dbdce39c4651?w=800',
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800',
                    'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800',
                    'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=800'
                ],
                'radno_vrijeme' => [
                    ['dan' => 'ponedeljak', 'od' => '08:00', 'do' => '20:00', 'zatvoreno' => false],
                    ['dan' => 'utorak', 'od' => '08:00', 'do' => '20:00', 'zatvoreno' => false],
                    ['dan' => 'sreda', 'od' => '08:00', 'do' => '20:00', 'zatvoreno' => false],
                    ['dan' => 'cetvrtak', 'od' => '08:00', 'do' => '20:00', 'zatvoreno' => false],
                    ['dan' => 'petak', 'od' => '08:00', 'do' => '20:00', 'zatvoreno' => false],
                    ['dan' => 'subota', 'od' => '09:00', 'do' => '18:00', 'zatvoreno' => false],
                    ['dan' => 'nedelja', 'od' => '09:00', 'do' => '18:00', 'zatvoreno' => false]
                ],
                'faqs' => [
                    ['pitanje' => 'Kakve aktivnosti organizujete?', 'odgovor' => 'Organizujemo kreativne radionice, muzikoterapiju, vrtlarstvo i društvene igre.'],
                    ['pitanje' => 'Da li imate program za demenciju?', 'odgovor' => 'Da, imamo specijalizirani program za osobe sa demencijom i Alzheimer bolešću.'],
                    ['pitanje' => 'Kakva je ishrana?', 'odgovor' => 'Pružamo uravnoteženu ishranu prilagođenu potrebama starijih osoba.']
                ],
                'meta_title' => 'Gerijatrijski centar Zlatna jesen Mostar - Holistička njega starijih',
                'meta_description' => 'Specijalizovani gerijatrijski centar u Mostaru sa holističkim pristupom njezi starijih osoba.',
                'meta_keywords' => 'gerijatrija mostar, dom za starije, demencija, alzheimer, starije osobe',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'naziv' => 'Dom "Oaza mira"',
                'slug' => 'dom-oaza-mira',
                'grad' => 'Zenica',
                'regija' => 'Zeničko-dobojska',
                'adresa' => 'Alije Izetbegovića 33, Zenica',
                'latitude' => 44.2019,
                'longitude' => 17.9061,
                'telefon' => '+387 32 567 890',
                'email' => 'kontakt@oaza-mira.ba',
                'website' => null,
                'opis' => 'Obiteljski dom za starije sa toplom atmosferom i individualnim pristupom svakom štićeniku.',
                'detaljni_opis' => 'Dom "Oaza mira" je manja, obiteljska ustanova koja pruža personalizovanu njegu za starije osobe. Naš pristup je zasnovan na stvaranju toplog, domaćeg ambijenta gdje se svaki štićenik osjeća kao kod kuće. Imamo iskusan tim koji pruža kvalitetnu njegu 24 sata dnevno.',
                'tip_doma_id' => $tipoviDomova['dom-starije'],
                'nivo_njege_id' => $nivoiNjege['osnovna'],
                'accepts_tags' => ['starije osobe', 'osnovna njega', 'socijalna podrška'],
                'not_accepts_text' => 'Ne primamo osobe koje trebaju intenzivnu medicinsku njegu.',
                'nurses_availability' => 'shifts',
                'doctor_availability' => 'on_call',
                'has_physiotherapist' => false,
                'has_physiatrist' => false,
                'emergency_protocol' => true,
                'emergency_protocol_text' => 'Imamo protokol za hitne slučajeve sa brzom vezom sa hitnom pomoći.',
                'controlled_entry' => false,
                'video_surveillance' => false,
                'visiting_rules' => 'Posjete su dobrodošle svakodnevno od 9:00 do 19:00 sati.',
                'pricing_mode' => 'public',
                'price_from' => 700.00,
                'price_includes' => 'Smještaj, ishrana, osnovna njega, društvene aktivnosti',
                'extra_charges' => 'Ljekarki pregled, dodatne usluge, transport',
                'online_upit' => true,
                'verifikovan' => true,
                'aktivan' => true,
                'prosjecna_ocjena' => 4.6,
                'broj_recenzija' => 15,
                'broj_pregleda' => 123,
                'featured_slika' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800',
                'galerija' => [
                    'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800',
                    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800',
                    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800',
                    'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800'
                ],
                'radno_vrijeme' => [
                    ['dan' => 'ponedeljak', 'od' => '09:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'utorak', 'od' => '09:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'sreda', 'od' => '09:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'cetvrtak', 'od' => '09:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'petak', 'od' => '09:00', 'do' => '19:00', 'zatvoreno' => false],
                    ['dan' => 'subota', 'od' => '10:00', 'do' => '18:00', 'zatvoreno' => false],
                    ['dan' => 'nedelja', 'od' => '10:00', 'do' => '18:00', 'zatvoreno' => false]
                ],
                'faqs' => [
                    ['pitanje' => 'Koliko štićenika imate?', 'odgovor' => 'Imamo kapacitet za 20 štićenika što omogućava individualan pristup.'],
                    ['pitanje' => 'Kakva je atmosfera u domu?', 'odgovor' => 'Trudimo se da stvorimo toplu, obiteljsku atmosferu gdje se svi osjećaju kao kod kuće.'],
                    ['pitanje' => 'Da li organizujete aktivnosti?', 'odgovor' => 'Da, organizujemo društvene aktivnosti, čitanje, gledanje filmova i šetnje.']
                ],
                'meta_title' => 'Dom Oaza mira Zenica - Obiteljski dom za starije',
                'meta_description' => 'Obiteljski dom za starije u Zenici sa toplom atmosferom i individualnim pristupom.',
                'meta_keywords' => 'dom za starije zenica, obiteljski dom, osnovna njega, starije osobe',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insert domovi
        foreach ($domovi as $dom) {
            // Convert arrays to JSON for PostgreSQL
            $dom['accepts_tags'] = json_encode($dom['accepts_tags']);
            $dom['galerija'] = json_encode($dom['galerija']);
            $dom['radno_vrijeme'] = json_encode($dom['radno_vrijeme']);
            $dom['faqs'] = json_encode($dom['faqs']);

            $domId = DB::table('domovi_njega')->insertGetId($dom);

            // Add programs for each dom
            $this->attachPrograms($domId, $dom['slug'], $programiNjege);

            // Add medical services for each dom
            $this->attachMedicalServices($domId, $dom['slug'], $medicinskUsluge);

            // Add accommodation conditions for each dom
            $this->attachAccommodationConditions($domId, $dom['slug'], $smjestajUslovi);
        }

        $this->command->info('✅ Domovi uspješno kreirani sa vezama!');
    }

    private function attachPrograms($domId, $slug, $programiNjege)
    {
        $programs = [];

        switch ($slug) {
            case 'dom-za-starije-sunce':
                $programs = [
                    ['program_id' => $programiNjege['demencija-alzheimer'], 'prioritet' => 1],
                    ['program_id' => $programiNjege['individualni'], 'prioritet' => 2],
                    ['program_id' => $programiNjege['dijabetes'], 'prioritet' => 3],
                ];
                break;
            case 'rehabilitacioni-centar-nada':
                $programs = [
                    ['program_id' => $programiNjege['postoperativni'], 'prioritet' => 1],
                    ['program_id' => $programiNjege['individualni'], 'prioritet' => 2],
                ];
                break;
            case 'palijativni-dom-mir':
                $programs = [
                    ['program_id' => $programiNjege['palijativni'], 'prioritet' => 1],
                    ['program_id' => $programiNjege['individualni'], 'prioritet' => 2],
                ];
                break;
            case 'gerijatrijski-centar-zlatna-jesen':
                $programs = [
                    ['program_id' => $programiNjege['demencija-alzheimer'], 'prioritet' => 1],
                    ['program_id' => $programiNjege['individualni'], 'prioritet' => 2],
                    ['program_id' => $programiNjege['nutritivna'], 'prioritet' => 3],
                ];
                break;
            case 'dom-oaza-mira':
                $programs = [
                    ['program_id' => $programiNjege['individualni'], 'prioritet' => 1],
                ];
                break;
        }

        foreach ($programs as $program) {
            DB::table('dom_programi_njege')->insert([
                'dom_id' => $domId,
                'program_id' => $program['program_id'],
                'prioritet' => $program['prioritet'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function attachMedicalServices($domId, $slug, $medicinskUsluge)
    {
        $services = [];

        switch ($slug) {
            case 'dom-za-starije-sunce':
                $services = [
                    $medicinskUsluge['sestrinska'],
                    $medicinskUsluge['terapija-lijekovi'],
                    $medicinskUsluge['vitalni-parametri'],
                    $medicinskUsluge['hitna-intervencija'],
                    $medicinskUsluge['vanjski-ljekari'],
                ];
                break;
            case 'rehabilitacioni-centar-nada':
                $services = [
                    $medicinskUsluge['sestrinska'],
                    $medicinskUsluge['fizikalna-osnovna'],
                    $medicinskUsluge['rehabilitacione-vjezbe'],
                    $medicinskUsluge['vitalni-parametri'],
                    $medicinskUsluge['vanjski-ljekari'],
                ];
                break;
            case 'palijativni-dom-mir':
                $services = [
                    $medicinskUsluge['sestrinska'],
                    $medicinskUsluge['palijativna-njega'],
                    $medicinskUsluge['terapija-lijekovi'],
                    $medicinskUsluge['vitalni-parametri'],
                ];
                break;
            case 'gerijatrijski-centar-zlatna-jesen':
                $services = [
                    $medicinskUsluge['sestrinska'],
                    $medicinskUsluge['terapija-lijekovi'],
                    $medicinskUsluge['vitalni-parametri'],
                    $medicinskUsluge['fizikalna-osnovna'],
                    $medicinskUsluge['vanjski-ljekari'],
                ];
                break;
            case 'dom-oaza-mira':
                $services = [
                    $medicinskUsluge['sestrinska'],
                    $medicinskUsluge['terapija-lijekovi'],
                    $medicinskUsluge['vitalni-parametri'],
                ];
                break;
        }

        foreach ($services as $serviceId) {
            DB::table('dom_medicinske_usluge')->insert([
                'dom_id' => $domId,
                'usluga_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function attachAccommodationConditions($domId, $slug, $smjestajUslovi)
    {
        $conditions = [];

        switch ($slug) {
            case 'dom-za-starije-sunce':
                $conditions = [
                    $smjestajUslovi['jednokrevetne'],
                    $smjestajUslovi['dvokrevetne'],
                    $smjestajUslovi['dugorocni'],
                    $smjestajUslovi['lift'],
                    $smjestajUslovi['invalidska-kolica'],
                    $smjestajUslovi['standardna-ishrana'],
                    $smjestajUslovi['dijetetska'],
                ];
                break;
            case 'rehabilitacioni-centar-nada':
                $conditions = [
                    $smjestajUslovi['jednokrevetne'],
                    $smjestajUslovi['dvokrevetne'],
                    $smjestajUslovi['privremeni'],
                    $smjestajUslovi['lift'],
                    $smjestajUslovi['invalidska-kolica'],
                    $smjestajUslovi['prilagodjena-kupatila'],
                    $smjestajUslovi['dijetetska'],
                ];
                break;
            case 'palijativni-dom-mir':
                $conditions = [
                    $smjestajUslovi['jednokrevetne'],
                    $smjestajUslovi['dugorocni'],
                    $smjestajUslovi['invalidska-kolica'],
                    $smjestajUslovi['posebne-dijete'],
                ];
                break;
            case 'gerijatrijski-centar-zlatna-jesen':
                $conditions = [
                    $smjestajUslovi['jednokrevetne'],
                    $smjestajUslovi['dvokrevetne'],
                    $smjestajUslovi['dugorocni'],
                    $smjestajUslovi['lift'],
                    $smjestajUslovi['invalidska-kolica'],
                    $smjestajUslovi['standardna-ishrana'],
                ];
                break;
            case 'dom-oaza-mira':
                $conditions = [
                    $smjestajUslovi['dvokrevetne'],
                    $smjestajUslovi['visekrevetne'],
                    $smjestajUslovi['dugorocni'],
                    $smjestajUslovi['standardna-ishrana'],
                ];
                break;
        }

        foreach ($conditions as $conditionId) {
            DB::table('dom_smjestaj_uslovi')->insert([
                'dom_id' => $domId,
                'uslov_id' => $conditionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
