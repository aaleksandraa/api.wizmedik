<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtiesSeederComplete extends Seeder
{
    private $now;

    public function run(): void
    {
        DB::table('specijalnosti')->truncate();
        $this->now = now();

        $this->command->info('🏥 Seeding specialties...');

        // Seed all main categories
        $this->seedOpstaInternaMedicina();
        $this->seedSrceKrvniSudovi();
        $this->seedZenskoZdravlje();
        $this->seedZdravljeDjece();
        $this->seedKozaKosaNokti();
        $this->seedNervniSistem();
        $this->seedKostiZgloboviMisici();
        $this->seedUhoGrloNos();
        $this->seedOciVid();
        $this->seedMentalnoZdravlje();
        $this->seedStomatologija();
        $this->seedHirurgija();
        $this->seedDijagnostika();
        $this->seedRehabilitacija();
        $this->seedUrologijaMuskoZdravlje();
        $this->seedEndokrinologijaMetabolizam();
        $this->seedGastroenterologija();
        $this->seedPulmologija();
        $this->seedInfektologija();
        $this->seedOnkologija();
        $this->seedAlternativnaMedicina();
        $this->seedHitnaUrgentna();

        $count = DB::table('specijalnosti')->count();
        $this->command->info("✅ Successfully seeded {$count} specialties!");
    }

    // NOTE: Methods seedOpstaInternaMedicina through seedStomatologija are already implemented
    // Copy them from the existing SpecialtiesSeeder.php file
    // Below are the 11 remaining category implementations

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
            'created_at' => $this->now,
            'updated_at' => $this->now,
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
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
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Radiologija', 'slug' => 'radiologija', 'opis' => 'Dijagnostika bolesti pomoću radioloških metoda snimanja.', 'meta_title' => 'Radiologija snimanja i pregledi | WizMedik', 'meta_description' => 'Radiološka dijagnostika i tumačenje snimaka za otkrivanje bolesti.', 'meta_keywords' => 'radiologija, radiolog, snimanje', 'kljucne_rijeci' => ['radiolog', 'rendgen', 'snimanje', 'radiološki pregled'], 'uvodni_tekst' => 'Radiologija koristi savremene metode snimanja za dijagnostiku bolesti.', 'detaljan_opis' => 'Radiolog je doktor medicine koji tumači snimke i nalaze dobijene različitim dijagnostičkim metodama, uključujući rendgen, CT, MR i ultrazvuk.', 'zakljucni_tekst' => 'Tačno tumačenje snimaka ključno je za postavljanje ispravne dijagnoze.', 'usluge' => [['naziv' => 'Radiološki pregled'], ['naziv' => 'Tumačenje snimaka']], 'faq' => [['pitanje' => 'Da li radiolog postavlja dijagnozu?', 'odgovor' => 'Radiolog daje stručno mišljenje na osnovu snimaka, a konačnu dijagnozu postavlja ljekar koji vodi liječenje.']]],
            ['naziv' => 'CT dijagnostika', 'slug' => 'ct-dijagnostika', 'opis' => 'Kompjuterizovana tomografija za detaljno snimanje unutrašnjih struktura.', 'meta_title' => 'CT dijagnostika snimanje | WizMedik', 'meta_description' => 'CT snimanje za preciznu dijagnostiku organa i tkiva.', 'meta_keywords' => 'CT, kompjuterizovana tomografija', 'kljucne_rijeci' => ['CT snimanje', 'CT pregled', 'tomografija'], 'uvodni_tekst' => 'CT dijagnostika omogućava brzu i preciznu procjenu unutrašnjih organa.', 'detaljan_opis' => 'CT se koristi u hitnim i planiranim slučajevima za dijagnostiku povreda, tumora, krvarenja i drugih stanja.', 'zakljucni_tekst' => 'CT snimanje je nezamjenjivo u savremenoj medicini.', 'usluge' => [['naziv' => 'CT snimanje'], ['naziv' => 'Tumačenje CT nalaza']], 'faq' => [['pitanje' => 'Da li CT koristi zračenje?', 'odgovor' => 'Da, ali u kontrolisanim i bezbjednim dozama.']]],
            ['naziv' => 'MR dijagnostika', 'slug' => 'mr-dijagnostika', 'opis' => 'Magnetna rezonanca za detaljan prikaz mekih tkiva.', 'meta_title' => 'MR dijagnostika magnetna rezonanca | WizMedik', 'meta_description' => 'MR snimanje za preciznu dijagnostiku bez jonizujućeg zračenja.', 'meta_keywords' => 'MR, magnetna rezonanca', 'kljucne_rijeci' => ['MR snimanje', 'magnetna rezonanca', 'MR pregled'], 'uvodni_tekst' => 'MR dijagnostika koristi magnetno polje za dobijanje detaljnih snimaka.', 'detaljan_opis' => 'MR je posebno korisna za dijagnostiku mozga, kičme, zglobova i mekih tkiva.', 'zakljucni_tekst' => 'MR omogućava visoku preciznost bez izlaganja zračenju.', 'usluge' => [['naziv' => 'MR snimanje'], ['naziv' => 'Tumačenje MR nalaza']], 'faq' => [['pitanje' => 'Da li MR snimanje boli?', 'odgovor' => 'Ne. Pregled je bezbolan, ali može trajati duže.']]],
            ['naziv' => 'Ultrazvuk', 'slug' => 'ultrazvuk', 'opis' => 'Ultrazvučni pregled organa i tkiva.', 'meta_title' => 'Ultrazvuk dijagnostički pregled | WizMedik', 'meta_description' => 'Ultrazvučni pregledi bez zračenja za brzu dijagnostiku.', 'meta_keywords' => 'ultrazvuk, ultrazvučni pregled', 'kljucne_rijeci' => ['ultrazvuk abdomena', 'ultrazvuk štitne', 'ultrazvuk srca'], 'uvodni_tekst' => 'Ultrazvuk je jedna od najčešće korištenih dijagnostičkih metoda.', 'detaljan_opis' => 'Koristi zvučne talase za prikaz organa u realnom vremenu i bez štetnog zračenja.', 'zakljucni_tekst' => 'Ultrazvuk je brz, bezbjedan i dostupan dijagnostički pregled.', 'usluge' => [['naziv' => 'Ultrazvučni pregled'], ['naziv' => 'Praćenje stanja']], 'faq' => [['pitanje' => 'Da li je ultrazvuk bezbjedan?', 'odgovor' => 'Da. Može se ponavljati bez rizika.']]],
            ['naziv' => 'Laboratorijska dijagnostika', 'slug' => 'laboratorijska-dijagnostika', 'opis' => 'Analiza krvi, urina i drugih uzoraka.', 'meta_title' => 'Laboratorijska dijagnostika analize | WizMedik', 'meta_description' => 'Krvne, biohemijske i druge laboratorijske analize.', 'meta_keywords' => 'laboratorija, laboratorijske analize', 'kljucne_rijeci' => ['krvne analize', 'laboratorija', 'nalaz krvi', 'urin'], 'uvodni_tekst' => 'Laboratorijske analize su osnov za procjenu opšteg zdravstvenog stanja.', 'detaljan_opis' => 'Laboratorijska dijagnostika obuhvata analize krvi, urina i drugih uzoraka koje pomažu u otkrivanju infekcija, poremećaja i hroničnih bolesti.', 'zakljucni_tekst' => 'Tačni laboratorijski nalazi omogućavaju pravovremeno i pravilno liječenje.', 'usluge' => [['naziv' => 'Krvne analize'], ['naziv' => 'Biohemijske analize'], ['naziv' => 'Hormonski testovi']], 'faq' => [['pitanje' => 'Da li se laboratorijske analize rade na prazan stomak?', 'odgovor' => 'Za neke analize da, ali to zavisi od vrste testa.']]],
            ['naziv' => 'Patohistologija', 'slug' => 'patohistologija', 'opis' => 'Mikroskopska analiza tkiva.', 'meta_title' => 'Patohistologija analiza tkiva | WizMedik', 'meta_description' => 'Patohistološka dijagnostika za preciznu analizu tkiva.', 'meta_keywords' => 'patohistologija, biopsija', 'kljucne_rijeci' => ['patohistologija', 'biopsija', 'analiza tkiva'], 'uvodni_tekst' => 'Patohistologija omogućava preciznu dijagnozu na nivou tkiva.', 'detaljan_opis' => 'Patohistološka analiza se radi nakon biopsije ili operacije kako bi se utvrdila priroda promjene u tkivu.', 'zakljucni_tekst' => 'Patohistološki nalaz je često ključan za određivanje terapije.', 'usluge' => [['naziv' => 'Patohistološka analiza']], 'faq' => [['pitanje' => 'Kada se radi patohistološki nalaz?', 'odgovor' => 'Kada je potrebno precizno odrediti prirodu promjene u tkivu.']]],
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedRehabilitacija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Rehabilitacija i fizikalna terapija',
            'slug' => 'rehabilitacija-i-fizikalna-terapija',
            'opis' => 'Liječenje, oporavak i povratak funkcije nakon povreda, bolesti i operativnih zahvata.',
            'meta_title' => 'Rehabilitacija i fizikalna terapija oporavak | WizMedik',
            'meta_description' => 'Fizikalna medicina, fizikalna terapija i rehabilitacija nakon povreda i operacija.',
            'meta_keywords' => 'rehabilitacija, fizikalna terapija, fizikalna medicina, oporavak',
            'kljucne_rijeci' => json_encode(['rehabilitacija', 'fizikalna terapija', 'fizijatar', 'bol u leđima', 'oporavak nakon povrede', 'rehabilitacija nakon operacije']),
            'uvodni_tekst' => 'Rehabilitacija i fizikalna terapija imaju ključnu ulogu u vraćanju pokretljivosti, snage i funkcionalnosti nakon povreda, bolesti i hirurških zahvata.',
            'detaljan_opis' => 'Ova oblast medicine obuhvata dijagnostiku i liječenje funkcionalnih poremećaja lokomotornog sistema, nervnog sistema i drugih stanja koja utiču na kretanje i svakodnevne aktivnosti. Rehabilitacija se sprovodi pod nadzorom doktora fizikalne medicine i uključuje različite terapijske postupke. Cilj nije samo smanjenje bola, već potpuni funkcionalni oporavak i prevencija trajnih posljedica.',
            'zakljucni_tekst' => 'Pravilno vođena rehabilitacija omogućava brži i sigurniji povratak svakodnevnim i radnim aktivnostima.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Pregled fizijatra'],
                ['naziv' => 'Plan rehabilitacije'],
                ['naziv' => 'Fizikalna terapija'],
                ['naziv' => 'Praćenje oporavka']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada je potrebna rehabilitacija?', 'odgovor' => 'Nakon povreda, operacija, moždanog udara, kao i kod hroničnih bolova i smanjene pokretljivosti.'],
                ['pitanje' => 'Ko vodi proces rehabilitacije?', 'odgovor' => 'Proces vodi doktor fizikalne medicine, uz saradnju fizioterapeuta i drugih stručnjaka.'],
                ['pitanje' => 'Da li je rehabilitacija bolna?', 'odgovor' => 'Terapija može biti neprijatna u početku, ali je prilagođena stanju pacijenta i ne treba biti bolna.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Fizikalna medicina', 'slug' => 'fizikalna-medicina', 'opis' => 'Medicinska specijalnost koja se bavi dijagnostikom i liječenjem funkcionalnih poremećaja.', 'meta_title' => 'Fizikalna medicina pregled fizijatra | WizMedik', 'meta_description' => 'Pregledi kod doktora fizikalne medicine i planiranje rehabilitacije.', 'meta_keywords' => 'fizikalna medicina, fizijatar', 'kljucne_rijeci' => ['fizijatar', 'fizikalna medicina', 'bol u leđima', 'smanjena pokretljivost'], 'uvodni_tekst' => 'Fizikalna medicina je temelj rehabilitacije i funkcionalnog liječenja.', 'detaljan_opis' => 'Doktor fizikalne medicine procjenjuje funkcionalno stanje pacijenta, postavlja dijagnozu i određuje plan rehabilitacije. Bavi se bolovima u mišićima i zglobovima, neurološkim oštećenjima i posljedicama povreda.', 'zakljucni_tekst' => 'Pregled kod fizijatra je prvi korak ka pravilnoj rehabilitaciji.', 'usluge' => [['naziv' => 'Pregled fizijatra'], ['naziv' => 'Izrada plana terapije']], 'faq' => [['pitanje' => 'Da li je potreban pregled fizijatra prije terapije?', 'odgovor' => 'Da. Terapija se sprovodi isključivo prema planu doktora fizikalne medicine.']]],
            ['naziv' => 'Fizikalna terapija', 'slug' => 'fizikalna-terapija', 'opis' => 'Primjena terapijskih procedura za smanjenje bola i poboljšanje funkcije.', 'meta_title' => 'Fizikalna terapija liječenje bola | WizMedik', 'meta_description' => 'Elektroterapija, magnetoterapija i druge fizikalne procedure.', 'meta_keywords' => 'fizikalna terapija, elektroterapija', 'kljucne_rijeci' => ['fizikalna terapija', 'terapija bola', 'elektroterapija', 'magnetoterapija'], 'uvodni_tekst' => 'Fizikalna terapija se sprovodi kao dio rehabilitacionog procesa.', 'detaljan_opis' => 'Obuhvata primjenu različitih terapijskih procedura koje smanjuju bol, upalu i poboljšavaju cirkulaciju, u skladu sa indikacijama doktora fizikalne medicine.', 'zakljucni_tekst' => 'Pravilno dozirana terapija ubrzava oporavak.', 'usluge' => [['naziv' => 'Elektroterapija'], ['naziv' => 'Ultrazvučna terapija'], ['naziv' => 'Magnetoterapija']], 'faq' => [['pitanje' => 'Koliko traje fizikalna terapija?', 'odgovor' => 'Trajanje zavisi od dijagnoze i terapijskog plana.']]],
            ['naziv' => 'Kineziterapija', 'slug' => 'kineziterapija', 'opis' => 'Terapija pokretom uz stručno vođene vježbe.', 'meta_title' => 'Kineziterapija terapija pokretom | WizMedik', 'meta_description' => 'Terapijske vježbe za jačanje mišića i vraćanje pokretljivosti.', 'meta_keywords' => 'kineziterapija, terapijske vježbe', 'kljucne_rijeci' => ['kineziterapija', 'vježbe za leđa', 'rehabilitacione vježbe'], 'uvodni_tekst' => 'Kineziterapija koristi pokret kao osnovno sredstvo liječenja.', 'detaljan_opis' => 'Sprovodi se individualno ili grupno, pod nadzorom fizioterapeuta, sa ciljem jačanja mišića, poboljšanja koordinacije i stabilnosti.', 'zakljucni_tekst' => 'Redovno izvođenje pravilnih vježbi ključno je za uspješnu rehabilitaciju.', 'usluge' => [['naziv' => 'Individualne vježbe'], ['naziv' => 'Rehabilitacione vježbe']], 'faq' => [['pitanje' => 'Da li se vježbe rade i kod bolova?', 'odgovor' => 'Da, ali se prilagođavaju stanju pacijenta.']]],
            ['naziv' => 'Rehabilitacija nakon povreda', 'slug' => 'rehabilitacija-nakon-povreda', 'opis' => 'Oporavak nakon povreda kostiju, zglobova, mišića i nerava.', 'meta_title' => 'Rehabilitacija nakon povreda oporavak | WizMedik', 'meta_description' => 'Rehabilitacija nakon preloma, uganuća i drugih povreda.', 'meta_keywords' => 'rehabilitacija nakon povrede', 'kljucne_rijeci' => ['oporavak nakon povrede', 'rehabilitacija nakon preloma'], 'uvodni_tekst' => 'Rehabilitacija nakon povreda je ključna za povratak pune funkcije.', 'detaljan_opis' => 'Obuhvata fizikalnu terapiju i kineziterapiju nakon sportskih i drugih povreda, uz nadzor fizijatra.', 'zakljucni_tekst' => 'Bez rehabilitacije oporavak može biti nepotpun.', 'usluge' => [['naziv' => 'Posttraumatska rehabilitacija']], 'faq' => [['pitanje' => 'Koliko traje rehabilitacija nakon povrede?', 'odgovor' => 'Trajanje zavisi od težine povrede i individualnog napretka.']]],
            ['naziv' => 'Neurološka rehabilitacija', 'slug' => 'neuroloska-rehabilitacija', 'opis' => 'Rehabilitacija nakon oštećenja nervnog sistema.', 'meta_title' => 'Neurološka rehabilitacija oporavak | WizMedik', 'meta_description' => 'Rehabilitacija nakon moždanog udara i neuroloških oštećenja.', 'meta_keywords' => 'neurološka rehabilitacija', 'kljucne_rijeci' => ['neurološka rehabilitacija', 'oporavak nakon moždanog udara'], 'uvodni_tekst' => 'Neurološka rehabilitacija pomaže u oporavku nakon oštećenja nervnog sistema.', 'detaljan_opis' => 'Sprovodi se nakon moždanog udara, povreda mozga ili kičmene moždine, sa ciljem vraćanja funkcionalnosti.', 'zakljucni_tekst' => 'Rana rehabilitacija poboljšava ishode oporavka.', 'usluge' => [['naziv' => 'Neurološka rehabilitacija']], 'faq' => [['pitanje' => 'Kada je potrebna neurološka rehabilitacija?', 'odgovor' => 'Nakon moždanog udara, povreda mozga ili kičmene moždine.']]],
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedUrologijaMuskoZdravlje()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Urologija i muško zdravlje',
            'slug' => 'urologija-i-musko-zdravlje',
            'opis' => 'Dijagnostika i liječenje bolesti mokraćnog sistema i muškog reproduktivnog zdravlja.',
            'meta_title' => 'Urologija i muško zdravlje pregledi | WizMedik',
            'meta_description' => 'Urologija i andrologija. Pregledi mokraćnog sistema i muškog reproduktivnog zdravlja.',
            'meta_keywords' => 'urologija, urolog, muško zdravlje, andrologija',
            'kljucne_rijeci' => json_encode(['urolog', 'muško zdravlje', 'problemi sa mokrenjem', 'prostata', 'bol u donjem stomaku', 'erektilna disfunkcija', 'infertilitet muškarca']),
            'uvodni_tekst' => 'Urologija i muško zdravlje obuhvataju bolesti mokraćnog sistema kod muškaraca i žena, kao i specifične probleme muškog reproduktivnog zdravlja. Tegobe u ovoj oblasti su česte, ali se često odgađa odlazak ljekaru.',
            'detaljan_opis' => 'Oblast urologije bavi se dijagnostikom i liječenjem bolesti bubrega, mokraćne bešike, mokraćnih puteva i prostate. Andrologija je uža grana urologije koja se bavi muškim reproduktivnim zdravljem, plodnošću i seksualnom funkcijom. Urološki pregledi su važni i u preventivne svrhe, posebno kod muškaraca srednje i starije životne dobi.',
            'zakljucni_tekst' => 'Pravovremeni urološki pregled omogućava rano otkrivanje bolesti i uspješnije liječenje.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Urološki pregled'],
                ['naziv' => 'Pregled prostate'],
                ['naziv' => 'Dijagnostika mokraćnog sistema'],
                ['naziv' => 'Savjetovanje o muškom zdravlju']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti urologu?', 'odgovor' => 'Ako imate probleme sa mokrenjem, bol u donjem stomaku ili leđima, učestalo mokrenje ili promjene u mokraći.'],
                ['pitanje' => 'Da li urolog liječi i žene?', 'odgovor' => 'Da. Urologija se bavi mokraćnim sistemom i kod žena.'],
                ['pitanje' => 'Da li su urološki pregledi neprijatni?', 'odgovor' => 'Pregledi su kratki i prilagođeni pacijentu, a nelagoda je minimalna.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Urologija', 'slug' => 'urologija', 'opis' => 'Medicinska specijalnost koja se bavi bolestima mokraćnog sistema i muških polnih organa.', 'meta_title' => 'Urologija pregledi i liječenje | WizMedik', 'meta_description' => 'Urološki pregledi, dijagnostika i liječenje bolesti bubrega, bešike i prostate.', 'meta_keywords' => 'urologija, urolog, mokraćni sistem', 'kljucne_rijeci' => ['urolog', 'problemi sa mokrenjem', 'bol u bubrezima', 'infekcije mokraćnih puteva', 'prostata'], 'uvodni_tekst' => 'Urologija se bavi bolestima koje utiču na mokrenje i funkciju mokraćnog sistema.', 'detaljan_opis' => 'Urolog dijagnostikuje i liječi infekcije mokraćnih puteva, kamence, poremećaje mokrenja, bolesti prostate i druga urološka stanja.', 'zakljucni_tekst' => 'Urološki pregled je ključan za očuvanje zdravlja mokraćnog sistema.', 'usluge' => [['naziv' => 'Urološki pregled'], ['naziv' => 'Ultrazvuk mokraćnog sistema'], ['naziv' => 'Praćenje hroničnih stanja']], 'faq' => [['pitanje' => 'Da li učestalo mokrenje uvijek znači infekciju?', 'odgovor' => 'Ne. Može biti povezano i sa drugim urološkim ili hormonskim stanjima.']]],
            ['naziv' => 'Andrologija', 'slug' => 'andrologija', 'opis' => 'Muško reproduktivno zdravlje i seksualna funkcija.', 'meta_title' => 'Andrologija muško reproduktivno zdravlje | WizMedik', 'meta_description' => 'Pregledi i liječenje problema plodnosti i seksualne funkcije kod muškaraca.', 'meta_keywords' => 'andrologija, androlog, muška plodnost', 'kljucne_rijeci' => ['androlog', 'muška neplodnost', 'erektilna disfunkcija', 'nizak testosteron'], 'uvodni_tekst' => 'Andrologija se bavi zdravljem muških polnih organa i reproduktivnom funkcijom.', 'detaljan_opis' => 'Androlog procjenjuje i liječi probleme muške plodnosti, hormonalne poremećaje i seksualne disfunkcije, često u saradnji sa drugim specijalistima.', 'zakljucni_tekst' => 'Rano savjetovanje poboljšava uspješnost liječenja.', 'usluge' => [['naziv' => 'Androloški pregled'], ['naziv' => 'Savjetovanje o plodnosti']], 'faq' => [['pitanje' => 'Da li se muška neplodnost može liječiti?', 'odgovor' => 'U mnogim slučajevima da, uz pravovremenu dijagnostiku i terapiju.']]],
            ['naziv' => 'Urološka onkologija', 'slug' => 'uroloska-onkologija', 'opis' => 'Dijagnostika i liječenje tumora mokraćnog sistema i prostate.', 'meta_title' => 'Urološka onkologija tumori mokraćnog sistema | WizMedik', 'meta_description' => 'Dijagnostika i liječenje tumora prostate, bešike i bubrega.', 'meta_keywords' => 'urološka onkologija, rak prostate', 'kljucne_rijeci' => ['rak prostate', 'tumor bešike', 'urološki tumori'], 'uvodni_tekst' => 'Urološka onkologija se bavi tumorima mokraćnog sistema.', 'detaljan_opis' => 'Obuhvata dijagnostiku i liječenje tumora prostate, bešike, bubrega i drugih uroloških organa.', 'zakljucni_tekst' => 'Rano otkrivanje značajno poboljšava prognozu.', 'usluge' => [['naziv' => 'Onkološki urološki pregled']], 'faq' => [['pitanje' => 'Da li su tumori prostate česti?', 'odgovor' => 'Da, posebno kod starijih muškaraca, zbog čega su preventivni pregledi važni.']]],
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedEndokrinologijaMetabolizam()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Endokrinologija i metabolizam',
            'slug' => 'endokrinologija-i-metabolizam',
            'opis' => 'Dijagnostika i liječenje hormonskih poremećaja i bolesti metabolizma.',
            'meta_title' => 'Endokrinologija i metabolizam hormoni i dijabetes | WizMedik',
            'meta_description' => 'Endokrinologija, dijabetes i poremećaji štitne žlijezde. Dijagnostika i liječenje hormonskih bolesti.',
            'meta_keywords' => 'endokrinologija, endokrinolog, hormoni, metabolizam, dijabetes',
            'kljucne_rijeci' => json_encode(['endokrinolog', 'hormonski poremećaji', 'dijabetes', 'štitna žlijezda', 'problemi sa hormonima', 'gojaznost', 'poremećaji metabolizma']),
            'uvodni_tekst' => 'Hormoni upravljaju gotovo svim procesima u organizmu, od rasta i razvoja do metabolizma i reproduktivnog zdravlja. Poremećaji hormonskog sistema mogu imati širok spektar simptoma i često se razvijaju postepeno.',
            'detaljan_opis' => 'Endokrinologija i metabolizam bave se bolestima endokrinih žlijezda i poremećajima metabolizma. To uključuje oboljenja štitne, nadbubrežne i polnih žlijezda, poremećaje nivoa šećera u krvi, kao i stanja vezana za tjelesnu težinu i metabolizam. Endokrinolog dijagnostikuje i liječi dijabetes, poremećaje štitne žlijezde, hormonske neravnoteže i metaboličke sindrome.',
            'zakljucni_tekst' => 'Pravovremena dijagnostika i liječenje hormonskih poremećaja ključni su za očuvanje zdravlja i kvaliteta života.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Endokrinološki pregled'],
                ['naziv' => 'Hormonske analize'],
                ['naziv' => 'Praćenje dijabetesa'],
                ['naziv' => 'Savjetovanje o metabolizmu']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti endokrinologu?', 'odgovor' => 'Ako imate simptome kao što su umor, promjene težine, problemi sa šećerom, znojenje ili promjene raspoloženja.'],
                ['pitanje' => 'Da li dijabetes zahtijeva stalno praćenje?', 'odgovor' => 'Da. Redovne kontrole su neophodne za pravilno vođenje terapije.'],
                ['pitanje' => 'Da li su hormonski poremećaji česti?', 'odgovor' => 'Da, posebno kod žena i starijih osoba.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Endokrinologija', 'slug' => 'endokrinologija', 'opis' => 'Dijagnostika i liječenje bolesti endokrinih žlijezda i hormonskih poremećaja.', 'meta_title' => 'Endokrinologija hormoni i žlijezde | WizMedik', 'meta_description' => 'Endokrinološki pregledi i liječenje hormonskih poremećaja.', 'meta_keywords' => 'endokrinologija, endokrinolog, hormoni', 'kljucne_rijeci' => ['endokrinolog', 'hormoni', 'hormonski poremećaji', 'žlijezde'], 'uvodni_tekst' => 'Endokrinologija se bavi hormonskim sistemom organizma.', 'detaljan_opis' => 'Endokrinolog dijagnostikuje i liječi poremećaje štitne žlijezde, hipofize, nadbubrežnih žlijezda i drugih endokrinih organa.', 'zakljucni_tekst' => 'Hormonska ravnoteža je ključna za zdravlje.', 'usluge' => [['naziv' => 'Endokrinološki pregled'], ['naziv' => 'Hormonske analize']], 'faq' => [['pitanje' => 'Koji su simptomi hormonskih poremećaja?', 'odgovor' => 'Umor, promjene težine, znojenje, problemi sa spavanjem i raspoloženjem.']]],
            ['naziv' => 'Dijabetes', 'slug' => 'dijabetes', 'opis' => 'Dijagnostika, liječenje i praćenje šećerne bolesti.', 'meta_title' => 'Dijabetes šećerna bolest liječenje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje dijabetesa tipa 1 i 2.', 'meta_keywords' => 'dijabetes, šećerna bolest', 'kljucne_rijeci' => ['dijabetes', 'šećerna bolest', 'visok šećer', 'insulin'], 'uvodni_tekst' => 'Dijabetes je hronična bolest koja zahtijeva stalno praćenje.', 'detaljan_opis' => 'Dijabetes nastaje kada organizam ne proizvodi dovoljno insulina ili ga ne koristi pravilno. Zahtijeva redovne kontrole i prilagođenu terapiju.', 'zakljucni_tekst' => 'Pravilno vođenje dijabetesa sprečava komplikacije.', 'usluge' => [['naziv' => 'Dijagnostika dijabetesa'], ['naziv' => 'Praćenje šećera'], ['naziv' => 'Edukacija pacijenata']], 'faq' => [['pitanje' => 'Da li dijabetes može proći?', 'odgovor' => 'Dijabetes tip 1 ne prolazi, ali tip 2 se može kontrolisati promjenom načina života.']]],
            ['naziv' => 'Poremećaji štitne žlijezde', 'slug' => 'poremecaji-stitne-zlijezde', 'opis' => 'Dijagnostika i liječenje hipotireoze, hipertireoze i drugih bolesti štitne žlijezde.', 'meta_title' => 'Poremećaji štitne žlijezde liječenje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje poremećaja funkcije štitne žlijezde.', 'meta_keywords' => 'štitna žlijezda, hipotireoza, hipertireoza', 'kljucne_rijeci' => ['štitna žlijezda', 'hipotireoza', 'hipertireoza', 'čvorovi štitne'], 'uvodni_tekst' => 'Štitna žlijezda reguliše metabolizam organizma.', 'detaljan_opis' => 'Poremećaji štitne žlijezde mogu uzrokovati umor, promjene težine, probleme sa srcem i raspoloženjem.', 'zakljucni_tekst' => 'Pravovremena terapija vraća hormonsku ravnotežu.', 'usluge' => [['naziv' => 'Pregled štitne žlijezde'], ['naziv' => 'Hormonske analize']], 'faq' => [['pitanje' => 'Koji su simptomi problema sa štitnom žlijezdom?', 'odgovor' => 'Umor, promjene težine, lupanje srca, znojenje ili hladnoća.']]],
            ['naziv' => 'Metabolički poremećaji', 'slug' => 'metabolicki-poremecaji', 'opis' => 'Poremećaji metabolizma i tjelesne težine.', 'meta_title' => 'Metabolički poremećaji gojaznost i metabolizam | WizMedik', 'meta_description' => 'Dijagnostika i liječenje metaboličkih poremećaja i gojaznosti.', 'meta_keywords' => 'metabolizam, gojaznost, metabolički sindrom', 'kljucne_rijeci' => ['metabolizam', 'gojaznost', 'metabolički sindrom', 'holesterol'], 'uvodni_tekst' => 'Metabolički poremećaji utiču na tjelesnu težinu i opšte zdravlje.', 'detaljan_opis' => 'Obuhvataju gojaznost, metabolički sindrom, poremećaje lipida i druge stanja koja utiču na metabolizam.', 'zakljucni_tekst' => 'Pravilna dijagnostika omogućava ciljanu terapiju.', 'usluge' => [['naziv' => 'Procjena metabolizma'], ['naziv' => 'Savjetovanje o ishrani']], 'faq' => [['pitanje' => 'Šta je metabolički sindrom?', 'odgovor' => 'Kombinacija gojaznosti, visokog pritiska, šećera i holesterola.']]],
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }

    private function seedGastroenterologija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Gastroenterologija',
            'slug' => 'gastroenterologija',
            'opis' => 'Dijagnostika i liječenje bolesti probavnog sistema, jetre i pankreasa.',
            'meta_title' => 'Gastroenterologija probavni sistem i jetra | WizMedik',
            'meta_description' => 'Gastroenterološki pregledi, endoskopija i liječenje bolesti probavnog sistema.',
            'meta_keywords' => 'gastroenterologija, gastroenterolog, probavni sistem',
            'kljucne_rijeci' => json_encode(['gastroenterolog', 'probavni sistem', 'bol u stomaku', 'nadimanje', 'proliv', 'zatvor', 'jetra', 'endoskopija']),
            'uvodni_tekst' => 'Gastroenterologija se bavi zdravljem probavnog sistema, koji je ključan za ishranu, varenje i opšte zdravlje organizma.',
            'detaljan_opis' => 'Oblast gastroenterologije obuhvata dijagnostiku i liječenje bolesti jednjaka, želuca, crijeva, jetre, žučne kese i pankreasa. Najčešći razlozi dolaska su bol u stomaku, nadimanje, mučnina, proliv, zatvor, krvarenje iz probavnog trakta i žutica. Gastroenterolog koristi različite dijagnostičke metode, uključujući endoskopiju, kako bi precizno utvrdio uzrok tegoba.',
            'zakljucni_tekst' => 'Pravovremena gastroenterološka dijagnostika omogućava uspješno liječenje i sprečavanje ozbiljnih komplikacija.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Gastroenterološki pregled'],
                ['naziv' => 'Endoskopija'],
                ['naziv' => 'Kolonoskopija'],
                ['naziv' => 'Ultrazvuk abdomena']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti gastroenterologu?', 'odgovor' => 'Ako imate dugotrajne probavne tegobe, bol u stomaku, krvarenje, žuticu ili promjene u stolici.'],
                ['pitanje' => 'Da li je endoskopija bolna?', 'odgovor' => 'Endoskopija se izvodi uz sedaciju ili anesteziju, tako da pacijent ne osjeća bol.'],
                ['pitanje' => 'Koliko često treba raditi kolonoskopiju?', 'odgovor' => 'Preventivno nakon 50. godine, a ranije ako postoje simptomi ili porodična istorija.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Gastroenterologija', 'slug' => 'gastroenterologija-opsta', 'opis' => 'Dijagnostika i liječenje bolesti probavnog trakta.', 'meta_title' => 'Gastroenterologija probavne bolesti | WizMedik', 'meta_description' => 'Pregledi i liječenje bolesti želuca, crijeva i probavnog sistema.', 'meta_keywords' => 'gastroenterologija, gastroenterolog', 'kljucne_rijeci' => ['gastroenterolog', 'bol u stomaku', 'nadimanje', 'probavne tegobe'], 'uvodni_tekst' => 'Gastroenterologija se bavi bolestima probavnog trakta.', 'detaljan_opis' => 'Gastroenterolog dijagnostikuje i liječi bolesti jednjaka, želuca i crijeva, uključujući gastritis, čir, refluks i upalne bolesti crijeva.', 'zakljucni_tekst' => 'Pravovremena dijagnostika sprečava hronične probleme.', 'usluge' => [['naziv' => 'Gastroenterološki pregled'], ['naziv' => 'Endoskopija']], 'faq' => [['pitanje' => 'Da li nadimanje zahtijeva pregled?', 'odgovor' => 'Ako je učestalo i praćeno drugim simptomima, preporučuje se pregled.']]],
            ['naziv' => 'Hepatologija', 'slug' => 'hepatologija', 'opis' => 'Bolesti jetre i žučnih puteva.', 'meta_title' => 'Hepatologija bolesti jetre | WizMedik', 'meta_description' => 'Dijagnostika i liječenje bolesti jetre, hepatitisa i ciroze.', 'meta_keywords' => 'hepatologija, jetra, hepatitis', 'kljucne_rijeci' => ['jetra', 'hepatitis', 'ciroza', 'žutica', 'masna jetra'], 'uvodni_tekst' => 'Hepatologija se bavi zdravljem jetre.', 'detaljan_opis' => 'Hepatolog liječi hepatitis, cirozu, masnu jetru i druge bolesti jetre koje mogu biti asimptomatske u ranoj fazi.', 'zakljucni_tekst' => 'Jetra je vitalni organ koji zahtijeva pažnju.', 'usluge' => [['naziv' => 'Pregled jetre'], ['naziv' => 'Ultrazvuk jetre']], 'faq' => [['pitanje' => 'Šta je masna jetra?', 'odgovor' => 'Nakupljanje masti u jetri, često povezano sa gojaznošću i metabolizmom.']]],
            ['naziv' => 'Proktologija', 'slug' => 'proktologija-gastro', 'opis' => 'Bolesti završnog dijela crijeva i analne regije.', 'meta_title' => 'Proktologija hemoroidi i analne bolesti | WizMedik', 'meta_description' => 'Dijagnostika i liječenje hemoroida, fisura i drugih proktoloških problema.', 'meta_keywords' => 'proktologija, hemoroidi', 'kljucne_rijeci' => ['hemoroidi', 'analne fisure', 'krvarenje iz anuса', 'bol u anusu'], 'uvodni_tekst' => 'Proktologija se bavi bolestima završnog dijela probavnog trakta.', 'detaljan_opis' => 'Proktolog liječi hemoroide, analne fisure, fistule i druge bolesti analne regije.', 'zakljucni_tekst' => 'Rano liječenje sprečava komplikacije.', 'usluge' => [['naziv' => 'Proktološki pregled']], 'faq' => [['pitanje' => 'Da li hemoroidi prolaze sami?', 'odgovor' => 'Ponekad da, ali često zahtijevaju liječenje.']]],
            ['naziv' => 'Bolesti crijeva', 'slug' => 'bolesti-crijeva', 'opis' => 'Upalne i funkcionalne bolesti tankog i debelog crijeva.', 'meta_title' => 'Bolesti crijeva upale i poremećaji | WizMedik', 'meta_description' => 'Dijagnostika i liječenje upalnih bolesti crijeva i sindroma iritabilnog crijeva.', 'meta_keywords' => 'bolesti crijeva, upalno crijevo', 'kljucne_rijeci' => ['upalno crijevo', 'Crohnova bolest', 'ulcerozni kolitis', 'sindrom iritabilnog crijeva'], 'uvodni_tekst' => 'Bolesti crijeva mogu biti upalne ili funkcionalne prirode.', 'detaljan_opis' => 'Obuhvataju Crohnovu bolest, ulcerozni kolitis, sindrom iritabilnog crijeva i druge poremećaje koji utiču na funkciju crijeva.', 'zakljucni_tekst' => 'Pravilna dijagnostika omogućava ciljanu terapiju.', 'usluge' => [['naziv' => 'Dijagnostika bolesti crijeva']], 'faq' => [['pitanje' => 'Šta je sindrom iritabilnog crijeva?', 'odgovor' => 'Funkcionalni poremećaj koji uzrokuje bol, nadimanje i promjene u stolici.']]],
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
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }
    }
