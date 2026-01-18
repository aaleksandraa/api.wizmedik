<?php
// This file contains the remaining 11 category implementations
// Copy these methods into SpecialtiesSeeder.php replacing the placeholder methods

    private function seedHirurgija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Hirurgija',
            'slug' => 'hirurgija',
            'opis' => 'Operativno liječenje bolesti i povreda različitih organskih sistema.',
            'meta_title' => 'Hirurgija operativno liječenje i pregledi | WizMedik',
            'meta_description' => 'Hirurški pregledi i operacije. Opšta, plastična, endokrina, ortopedska i druge hirurške specijalnosti.',
            'meta_keywords' => 'hirurgija, hirurg, operacija, hirurški zahvati',
            'kljucne_rijeci' => json_encode(['hirurg', 'operacija', 'hirurški pregled', 'bol za operaciju', 'postoperativni oporavak']),
            'uvodni_tekst' => 'Hirurgija je grana medicine koja se bavi liječenjem bolesti i povreda operativnim putem, kada konzervativno liječenje nije dovoljno ili nije moguće.',
            'detaljan_opis' => 'Oblast hirurgije obuhvata širok spektar operativnih zahvata na različitim organima i sistemima. Hirurzi se bave dijagnostikom stanja koja zahtijevaju operaciju, izvođenjem zahvata i praćenjem pacijenta u postoperativnom periodu. U zavisnosti od vrste oboljenja, pacijente zbrinjavaju specijalisti opšte hirurgije ili usko specijalizovani hirurzi.',
            'zakljucni_tekst' => 'Pravilna hirurška procjena i stručno izveden zahvat ključni su za uspješno liječenje i oporavak pacijenta.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Hirurški pregled'],
                ['naziv' => 'Procjena za operaciju'],
                ['naziv' => 'Postoperativne kontrole']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Da li je operacija uvijek posljednja opcija?', 'odgovor' => 'U većini slučajeva da. Operacija se preporučuje kada druge metode liječenja nisu dovoljne.'],
                ['pitanje' => 'Da li je potreban hirurški pregled prije operacije?', 'odgovor' => 'Da. Hirurški pregled je obavezan radi procjene indikacije i rizika.']
            ]),
            'aktivan' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subcategories = [
            ['naziv' => 'Opšta hirurgija', 'slug' => 'opsta-hirurgija', 'opis' => 'Hirurško liječenje bolesti organa trbušne duplje i mekih tkiva.', 'meta_title' => 'Opšta hirurgija pregledi i operacije | WizMedik', 'meta_description' => 'Hirurško liječenje kile, žučne kese, slijepog crijeva i drugih stanja.', 'meta_keywords' => 'opšta hirurgija, opšti hirurg', 'kljucne_rijeci' => ['opšti hirurg', 'kila', 'žučna kesa', 'slijepo crijevo', 'hirurški pregled'], 'uvodni_tekst' => 'Opšta hirurgija je osnovna hirurška specijalnost.', 'detaljan_opis' => 'Opšti hirurg liječi bolesti digestivnog sistema, kile, tumore mekih tkiva i akutna stanja koja zahtijevaju hitnu operaciju.', 'zakljucni_tekst' => 'Pravovremena operacija sprečava ozbiljne komplikacije.', 'usluge' => [['naziv' => 'Hirurški pregled'], ['naziv' => 'Operacije trbušne duplje']], 'faq' => [['pitanje' => 'Da li se kila mora operisati?', 'odgovor' => 'U većini slučajeva da, posebno ako izaziva bol ili komplikacije.']]],
            ['naziv' => 'Endokrina hirurgija', 'slug' => 'endokrina-hirurgija', 'opis' => 'Hirurško liječenje bolesti endokrinih žlijezda.', 'meta_title' => 'Endokrina hirurgija operacije žlijezda | WizMedik', 'meta_description' => 'Operacije štitne i drugih endokrinih žlijezda.', 'meta_keywords' => 'endokrina hirurgija, štitna žlijezda', 'kljucne_rijeci' => ['operacija štitne žlijezde', 'endokrini hirurg', 'čvorovi štitne'], 'uvodni_tekst' => 'Endokrina hirurgija se bavi operacijama hormonskih žlijezda.', 'detaljan_opis' => 'Najčešće obuhvata operacije štitne i paratiroidnih žlijezda kod čvorova, tumora i poremećaja funkcije.', 'zakljucni_tekst' => 'Iskustvo hirurga je ključno za siguran zahvat.', 'usluge' => [['naziv' => 'Pregled štitne žlijezde'], ['naziv' => 'Operativno liječenje']], 'faq' => [['pitanje' => 'Da li se svi čvorovi štitne žlijezde operišu?', 'odgovor' => 'Ne. Operacija se preporučuje samo u određenim slučajevima.']]],
            ['naziv' => 'Plastična hirurgija', 'slug' => 'plasticna-hirurgija', 'opis' => 'Rekonstruktivni i estetski hirurški zahvati.', 'meta_title' => 'Plastična hirurgija rekonstruktivni zahvati | WizMedik', 'meta_description' => 'Rekonstrukcija nakon povreda i bolesti, kao i estetski hirurški zahvati.', 'meta_keywords' => 'plastična hirurgija, plastični hirurg', 'kljucne_rijeci' => ['plastični hirurg', 'rekonstrukcija', 'estetska operacija'], 'uvodni_tekst' => 'Plastična hirurgija obnavlja funkciju i izgled tkiva.', 'detaljan_opis' => 'Plastični hirurg se bavi rekonstrukcijom nakon povreda, operacija i urođenih deformiteta, kao i estetskim zahvatima.', 'zakljucni_tekst' => 'Cilj je funkcionalan i prirodan rezultat.', 'usluge' => [['naziv' => 'Plastično hirurški pregled']], 'faq' => [['pitanje' => 'Da li je plastična hirurgija samo estetska?', 'odgovor' => 'Ne. Veliki dio je rekonstruktivne prirode.']]],
            ['naziv' => 'Ortopedska hirurgija', 'slug' => 'ortopedska-hirurgija', 'opis' => 'Operativno liječenje bolesti i povreda kostiju i zglobova.', 'meta_title' => 'Ortopedska hirurgija operacije zglobova | WizMedik', 'meta_description' => 'Operacije koljena, kuka, ramena i drugih zglobova.', 'meta_keywords' => 'ortopedska hirurgija, ortopedski hirurg', 'kljucne_rijeci' => ['operacija koljena', 'operacija kuka', 'ortopedski hirurg'], 'uvodni_tekst' => 'Ortopedska hirurgija se primjenjuje kod težih oboljenja i povreda.', 'detaljan_opis' => 'Obuhvata operacije zglobova, korekciju deformiteta i liječenje preloma.', 'zakljucni_tekst' => 'Cilj je povratak pokretljivosti i smanjenje bola.', 'usluge' => [['naziv' => 'Ortopedski hirurški pregled']], 'faq' => [['pitanje' => 'Da li se svaka artroza mora operisati?', 'odgovor' => 'Ne. Operacija je opcija kada terapija ne pomaže.']]],
            ['naziv' => 'Neurohirurgija', 'slug' => 'neurohirurgija-hirurska', 'opis' => 'Hirurško liječenje bolesti mozga i kičme.', 'meta_title' => 'Neurohirurgija operacije mozga i kičme | WizMedik', 'meta_description' => 'Operativno liječenje tumora, diskus hernije i drugih neurohirurških stanja.', 'meta_keywords' => 'neurohirurgija, neurohirurg', 'kljucne_rijeci' => ['neurohirurg', 'operacija mozga', 'operacija kičme'], 'uvodni_tekst' => 'Neurohirurgija je visoko specijalizovana oblast.', 'detaljan_opis' => 'Neurohirurg izvodi složene operacije na mozgu, kičmenoj moždini i nervima.', 'zakljucni_tekst' => 'Neurohirurški zahvati zahtijevaju visoku stručnost.', 'usluge' => [['naziv' => 'Neurohirurški pregled']], 'faq' => [['pitanje' => 'Da li se diskus hernija uvijek operiše?', 'odgovor' => 'Ne. Većina se liječi bez operacije.']]],
            ['naziv' => 'Proktologija', 'slug' => 'proktologija-hirurska', 'opis' => 'Bolesti završnog dijela debelog crijeva i analne regije.', 'meta_title' => 'Proktologija pregled i liječenje | WizMedik', 'meta_description' => 'Liječenje hemoroida, fisura i drugih proktoloških bolesti.', 'meta_keywords' => 'proktologija, proktolog', 'kljucne_rijeci' => ['hemoroidi', 'bol u anusu', 'krvarenje', 'proktolog'], 'uvodni_tekst' => 'Proktologija se bavi bolestima koje često izazivaju nelagodu, ali su česte.', 'detaljan_opis' => 'Proktolog liječi hemoroide, analne fisure, fistule i druge bolesti završnog dijela crijeva.', 'zakljucni_tekst' => 'Rano javljanje ljekaru sprječava komplikacije.', 'usluge' => [['naziv' => 'Proktološki pregled'], ['naziv' => 'Hirurško liječenje']], 'faq' => [['pitanje' => 'Da li su hemoroidi opasni?', 'odgovor' => 'Najčešće nisu, ali mogu izazvati ozbiljne tegobe ako se ne liječe.']]],
            ['naziv' => 'Torakalna hirurgija', 'slug' => 'torakalna-hirurgija', 'opis' => 'Hirurgija organa grudnog koša.', 'meta_title' => 'Torakalna hirurgija operacije grudnog koša | WizMedik', 'meta_description' => 'Hirurško liječenje bolesti pluća i drugih organa grudnog koša.', 'meta_keywords' => 'torakalna hirurgija', 'kljucne_rijeci' => ['torakalna hirurgija', 'operacija pluća'], 'uvodni_tekst' => 'Torakalna hirurgija se bavi organima grudnog koša.', 'detaljan_opis' => 'Obuhvata operacije pluća, jednjaka i drugih struktura grudnog koša.', 'zakljucni_tekst' => 'Torakalna hirurgija zahtijeva visoku specijalizaciju.', 'usluge' => [['naziv' => 'Torakalni hirurški pregled']], 'faq' => [['pitanje' => 'Da li torakalna hirurgija uključuje operacije pluća?', 'odgovor' => 'Da, uključuje pluća i druge strukture grudnog koša.']]],
        ];

        foreach ($subcategories as $sub) {
            DB::table('specijalnosti')->insert([
                'parent_id' => $id,
                'naziv' => $sub['naziv'],
                'slug' => $sub['slug'],
                'opis' => $sub['opis'],
                'meta_title' => $sub['meta_title'],
                'meta_description' => $sub['meta_description'],
                'meta_keywords' => $sub['meta_keywords'],
                'kljucne_rijeci' => json_encode($sub['kljucne_rijeci']),
                'uvodni_tekst' => $sub['uvodni_tekst'],
                'detaljan_opis' => $sub['detaljan_opis'],
                'zakljucni_tekst' => $sub['zakljucni_tekst'],
                'prikazi_usluge' => true,
                'usluge' => json_encode($sub['usluge']),
                'prikazi_faq' => true,
                'faq' => json_encode($sub['faq']),
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedDijagnostika()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Dijagnostika',
            'slug' => 'dijagnostika',
            'opis' => 'Medicinske metode i pregledi za otkrivanje, praćenje i procjenu bolesti i zdravstvenog stanja.',
            'meta_title' => 'Dijagnostika pregledi i snimanja | WizMedik',
            'meta_description' => 'Radiologija, CT, MR, ultrazvuk i laboratorijska dijagnostika. Precizna i pouzdana medicinska dijagnostika.',
            'meta_keywords' => 'dijagnostika, radiologija, CT, MR, ultrazvuk, laboratorija',
            'kljucne_rijeci' => json_encode(['dijagnostika', 'snimanje', 'radiolog', 'CT snimanje', 'MR snimanje', 'ultrazvuk', 'laboratorijske analize', 'krvne pretrage']),
            'uvodni_tekst' => 'Dijagnostika predstavlja osnovu savremene medicine i omogućava tačno otkrivanje bolesti prije nego što se pojave ozbiljni simptomi.',
            'detaljan_opis' => 'Medicinska dijagnostika obuhvata različite metode pregleda i ispitivanja kojima se procjenjuje stanje organa, tkiva i funkcija organizma. Najčešće uključuje radiološka snimanja, ultrazvučne preglede i laboratorijske analize. Precizna dijagnostika omogućava ljekarima da postave tačnu dijagnozu, započnu odgovarajuće liječenje i prate tok bolesti ili oporavka.',
            'zakljucni_tekst' => 'Bez kvalitetne dijagnostike nema pravilne terapije. Pravovremeni pregledi su ključ uspješnog liječenja.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Radiološko snimanje'],
                ['naziv' => 'Ultrazvučni pregledi'],
                ['naziv' => 'Laboratorijske analize'],
                ['naziv' => 'Kontrolna dijagnostika']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Da li je dijagnostika potrebna i kada nema simptoma?', 'odgovor' => 'Da. Mnoge bolesti se mogu otkriti u ranoj fazi samo dijagnostičkim pregledima.'],
                ['pitanje' => 'Da li su dijagnostički pregledi bezbjedni?', 'odgovor' => 'Većina pregleda je bezbjedna kada se izvodi prema medicinskim smjernicama.'],
                ['pitanje' => 'Ko određuje koju dijagnostiku treba uraditi?', 'odgovor' => 'Dijagnostički pregled najčešće preporučuje ljekar na osnovu simptoma ili sumnje na određeno oboljenje.']
            ]),
            'aktivan' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subcategories = [
            ['naziv' => 'Radiologija', 'slug' => 'radiologija', 'opis' => 'Dijagnostika bolesti pomoću radioloških metoda snimanja.', 'meta_title' => 'Radiologija snimanja i pregledi | WizMedik', 'meta_description' => 'Radiološka dijagnostika i tumačenje snimaka za otkrivanje bolesti.', 'meta_keywords' => 'radiologija, radiolog, snimanje', 'kljucne_rijeci' => ['radiolog', 'rendgen', 'snimanje', 'radiološki pregled'], 'uvodni_tekst' => 'Radiologija koristi savremene metode snimanja za dijagnostiku bolesti.', 'detaljan_opis' => 'Radiolog je doktor medicine koji tumači snimke i nalaze dobijene različitim dijagnostičkim metodama, uključujući rendgen, CT, MR i ultrazvuk.', 'zakljucni_tekst' => 'Tačno tumačenje snimaka ključno je za postavljanje ispravne dijagnoze.', 'usluge' => [['naziv' => 'Radiološki pregled'], ['naziv' => 'Tumačenje snimaka']], 'faq' => [['pitanje' => 'Da li radiolog postavlja dijagnozu?', 'odgovor' => 'Radiolog daje stručno mišljenje na osnovu snimaka, a konačnu dijagnozu postavlja ljekar koji vodi liječenje.']]],
            ['naziv' => 'CT dijagnostika', 'slug' => 'ct-dijagnostika', 'opis' => 'Kompjuterizovana tomografija za detaljno snimanje unutrašnjih struktura.', 'meta_title' => 'CT dijagnostika snimanje | WizMedik', 'meta_description' => 'CT snimanje za preciznu dijagnostiku organa i tkiva.', 'meta_keywords' => 'CT, kompjuterizovana tomografija', 'kljucne_rijeci' => ['CT snimanje', 'CT pregled', 'tomografija'], 'uvodni_tekst' => 'CT dijagnostika omogućava brzu i preciznu procjenu unutrašnjih organa.', 'detaljan_opis' => 'CT se koristi u hitnim i planiranim slučajevima za dijagnostiku povreda, tumora, krvarenja i drugih stanja.', 'zakljucni_tekst' => 'CT snimanje je nezamjenjivo u savremenoj medicini.', 'usluge' => [['naziv' => 'CT snimanje'], ['naziv' => 'Tumačenje CT nalaza']], 'faq' => [['pitanje' => 'Da li CT koristi zračenje?', 'odgovor' => 'Da, ali u kontrolisanim i bezbjednim dozama.']]],
            ['naziv' => 'MR dijagnostika', 'slug' => 'mr-dijagnostika', 'opis' => 'Magnetna rezonanca za detaljan prikaz mekih tkiva.', 'meta_title' => 'MR dijagnostika magnetna rezonanca | WizMedik', 'meta_description' => 'MR snimanje za preciznu dijagnostiku bez jonizujućeg zračenja.', 'meta_keywords' => 'MR, magnetna rezonanca', 'kljucne_rijeci' => ['MR snimanje', 'magnetna rezonanca', 'MR pregled'], 'uvodni_tekst' => 'MR dijagnostika koristi magnetno polje za dobijanje detaljnih snimaka.', 'detaljan_opis' => 'MR je posebno korisna za dijagnostiku mozga, kičme, zglobova i mekih tkiva.', 'zakljucni_tekst' => 'MR omogućava visoku preciznost bez izlaganja zračenju.', 'usluge' => [['naziv' => 'MR snimanje'], ['naziv' => 'Tumačenje MR nalaza']], 'faq' => [['pitanje' => 'Da li MR snimanje boli?', 'odgovor' => 'Ne. Pregled je bezbolan, ali može trajati duže.']]],
            ['naziv' => 'Ultrazvuk', 'slug' => 'ultrazvuk', 'opis' => 'Ultrazvučni pregled organa i tkiva.', 'meta_title' => 'Ultrazvuk dijagnostički pregled | WizMedik', 'meta_description' => 'Ultrazvučni pregledi bez zračenja za brzu dijagnostiku.', 'meta_keywords' => 'ultrazvuk, ultrazvučni pregled', 'kljucne_rijeci' => ['ultrazvuk abdomena', 'ultrazvuk štitne', 'ultrazvuk srca'], 'uvodni_tekst' => 'Ultrazvuk je jedna od najčešće korištenih dijagnostičkih metoda.', 'detaljan_opis' => 'Koristi zvučne talase za prikaz organa u realnom vremenu i bez štetnog zračenja.', 'zakljucni_tekst' => 'Ultrazvuk je brz, bezbjedan i dostupan dijagnostički pregled.', 'usluge' => [['naziv' => 'Ultrazvučni pregled'], ['naziv' => 'Praćenje stanja']], 'faq' => [['pitanje' => 'Da li je ultrazvuk bezbjedan?', 'odgovor' => 'Da. Može se ponavljati bez rizika.']]],
            ['naziv' => 'Laboratorijska dijagnostika', 'slug' => 'laboratorijska-dijagnostika', 'opis' => 'Analiza krvi, urina i drugih uzoraka.', 'meta_title' => 'Laboratorijska dijagnostika analize | WizMedik', 'meta_description' => 'Krvne, biohemijske i druge laboratorijske analize.', 'meta_keywords' => 'laboratorija, laboratorijske analize', 'kljucne_rijeci' => ['krvne analize', 'laboratorija', 'nalaz krvi', 'urin'], 'uvodni_tekst' => 'Laboratorijske analize su osnov za procjenu opšteg zdravstvenog stanja.', 'detaljan_opis' => 'Laboratorijska dijagnostika obuhvata analize krvi, urina i drugih uzoraka koje pomažu u otkrivanju infekcija, poremećaja i hroničnih bolesti.', 'zakljucni_tekst' => 'Tačni laboratorijski nalazi omogućavaju pravovremeno i pravilno liječenje.', 'usluge' => [['naziv' => 'Krvne analize'], ['naziv' => 'Biohemijske analize'], ['naziv' => 'Hormonski testovi']], 'faq' => [['pitanje' => 'Da li se laboratorijske analize rade na prazan stomak?', 'odgovor' => 'Za neke analize da, ali to zavisi od vrste testa.']]],
            ['naziv' => 'Patohistologija', 'slug' => 'patohistologija', 'opis' => 'Mikroskopska analiza tkiva.', 'meta_title' => 'Patohistologija analiza tkiva | WizMedik', 'meta_description' => 'Patohistološka dijagnostika za preciznu analizu tkiva.', 'meta_keywords' => 'patohistologija, analiza tkiva', 'kljucne_rijeci' => ['patohistologija', 'biopsija', 'analiza tkiva'], 'uvodni_tekst' => 'Patohistologija omogućava preciznu dijagnozu na nivou tkiva.', 'detaljan_opis' => 'Patohistolog analizira uzorke tkiva pod mikroskopom kako bi utvrdio prirodu promjena.', 'zakljucni_tekst' => 'Patohistološki nalaz je često ključan za konačnu dijagnozu.', 'usluge' => [['naziv' => 'Patohistološka analiza']], 'faq' => [['pitanje' => 'Kada se radi patohistološki nalaz?', 'odgovor' => 'Kada je potrebno precizno odrediti prirodu promjene u tkivu.']]],
        ];

        foreach ($subcategories as $sub) {
            DB::table('specijalnosti')->insert([
                'parent_id' => $id,
                'naziv' => $sub['naziv'],
                'slug' => $sub['slug'],
                'opis' => $sub['opis'],
                'meta_title' => $sub['meta_title'],
                'meta_description' => $sub['meta_description'],
                'meta_keywords' => $sub['meta_keywords'],
                'kljucne_rijeci' => json_encode($sub['kljucne_rijeci']),
                'uvodni_tekst' => $sub['uvodni_tekst'],
                'detaljan_opis' => $sub['detaljan_opis'],
                'zakljucni_tekst' => $sub['zakljucni_tekst'],
                'prikazi_usluge' => true,
                'usluge' => json_encode($sub['usluge']),
                'prikazi_faq' => true,
                'faq' => json_encode($sub['faq']),
                'aktivan' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

// Continue with remaining 9 categories...
// Due to file size, this is split into multiple parts
