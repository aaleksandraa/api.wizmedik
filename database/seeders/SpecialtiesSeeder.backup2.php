<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtiesSeeder extends Seeder
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

    private function seedOpstaInternaMedicina()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Opšta i interna medicina',
            'slug' => 'opsta-i-interna-medicina',
            'opis' => 'Osnovna zdravstvena zaštita odraslih osoba, dijagnostika, liječenje i praćenje opšteg zdravstvenog stanja i bolesti unutrašnjih organa.',
            'meta_title' => 'Opšta i interna medicina pregledi i dijagnostika | WizMedik',
            'meta_description' => 'Pregledi kod ljekara opšte prakse i interniste. Dijagnostika, terapija i prevencija bolesti kod odraslih osoba.',
            'meta_keywords' => 'opšta medicina, interna medicina, internista, ljekar opšte prakse, zdravstveni pregled',
            'kljucne_rijeci' => json_encode(['opšta medicina', 'interna medicina', 'internista', 'ljekar opšte prakse', 'porodični ljekar', 'unutrašnje bolesti', 'pregled kod doktora', 'opšti pregled', 'preventivni pregled']),
            'uvodni_tekst' => 'Opšta i interna medicina predstavlja prvi i najvažniji korak u očuvanju zdravlja odraslih osoba. Ova oblast medicine obuhvata pregled, dijagnostiku, liječenje i praćenje širokog spektra zdravstvenih stanja, od blagih tegoba do hroničnih bolesti.',
            'detaljan_opis' => 'Ljekar opšte prakse i internista često su prvi zdravstveni stručnjaci kojima se pacijenti obraćaju zbog različitih simptoma kao što su umor, bolovi, povišen krvni pritisak, problemi sa šećerom u krvi, probavne smetnje ili opšte loše stanje. Interna medicina se posebno bavi bolestima srca, pluća, želuca i crijeva, jetre, bubrega, endokrinog sistema i krvi. Cilj nije samo liječenje bolesti, već i njihovo rano otkrivanje, praćenje i prevencija komplikacija. Opšta i interna medicina ima ključnu ulogu u usmjeravanju pacijenta ka drugim specijalistima kada je to potrebno.',
            'zakljucni_tekst' => 'Redovni pregledi kod ljekara opšte prakse ili interniste omogućavaju pravovremeno otkrivanje bolesti i očuvanje dugoročnog zdravlja. Ova oblast medicine je temelj kvalitetne zdravstvene zaštite.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Opšti ljekarski pregled'],
                ['naziv' => 'Internistički pregled'],
                ['naziv' => 'Kontrola hroničnih bolesti'],
                ['naziv' => 'Tumačenje laboratorijskih nalaza'],
                ['naziv' => 'Savjetovanje o zdravom načinu života']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti ljekaru opšte prakse ili internisti?', 'odgovor' => 'Kada imate dugotrajne ili nejasne tegobe, povišen pritisak, promjene u nalazima krvi ili jednostavno želite provjeriti svoje zdravstveno stanje.'],
                ['pitanje' => 'Koja je razlika između opšte i interne medicine?', 'odgovor' => 'Ljekar opšte prakse pruža osnovnu zdravstvenu zaštitu i prati pacijenta dugoročno, dok se internista bavi detaljnijom dijagnostikom i liječenjem bolesti unutrašnjih organa.'],
                ['pitanje' => 'Da li je potreban uput za internistu?', 'odgovor' => 'U privatnoj praksi najčešće nije potreban uput, dok u javnom zdravstvenom sistemu to zavisi od pravila ustanove.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Opšta medicina i porodična medicina', 'slug' => 'opsta-medicina-i-porodicna-medicina', 'opis' => 'Sveobuhvatna zdravstvena briga za odrasle osobe i porodice kroz sve faze života.', 'meta_title' => 'Opšta i porodična medicina pregledi i savjetovanje | WizMedik', 'meta_description' => 'Pregledi kod ljekara opšte i porodične medicine. Prvi korak u dijagnostici i liječenju zdravstvenih problema.', 'meta_keywords' => 'opšta medicina, porodična medicina, ljekar opšte prakse', 'kljucne_rijeci' => ['porodični ljekar', 'opšti doktor', 'ljekar opšte prakse', 'pregled kod doktora', 'porodična medicina'], 'uvodni_tekst' => 'Opšta i porodična medicina predstavlja temelj zdravstvene zaštite i prvi kontakt pacijenta sa zdravstvenim sistemom.', 'detaljan_opis' => 'Porodični ljekar prati zdravstveno stanje pacijenta dugoročno, poznaje njegovu medicinsku istoriju i koordinira dalju dijagnostiku i liječenje. Ova oblast medicine obuhvata preventivne preglede, liječenje akutnih stanja i praćenje hroničnih bolesti.', 'zakljucni_tekst' => 'Ljekar opšte i porodične medicine je oslonac dugoročnog i stabilnog zdravstvenog sistema.', 'usluge' => [['naziv' => 'Opšti pregled'], ['naziv' => 'Savjetovanje'], ['naziv' => 'Kontrola terapije']], 'faq' => [['pitanje' => 'Da li porodični ljekar liječi sve bolesti?', 'odgovor' => 'Porodični ljekar liječi većinu čestih zdravstvenih problema i po potrebi upućuje pacijenta specijalisti.']]],
            ['naziv' => 'Interna medicina', 'slug' => 'interna-medicina', 'opis' => 'Dijagnostika i liječenje bolesti unutrašnjih organa kod odraslih.', 'meta_title' => 'Interna medicina pregledi i liječenje | WizMedik', 'meta_description' => 'Internistički pregledi i liječenje bolesti srca, pluća, probavnog i endokrinog sistema.', 'meta_keywords' => 'interna medicina, internista, unutrašnje bolesti', 'kljucne_rijeci' => ['internista', 'unutrašnje bolesti', 'pritisak', 'šećer', 'holesterol'], 'uvodni_tekst' => 'Interna medicina se bavi složenim zdravstvenim stanjima odraslih osoba.', 'detaljan_opis' => 'Internista procjenjuje rad unutrašnjih organa i povezuje simptome u cjelovitu dijagnozu. Posebno je važna kod hroničnih i višestrukih oboljenja.', 'zakljucni_tekst' => 'Internistički pregled je ključan za preciznu dijagnostiku i dugoročno praćenje zdravlja.', 'usluge' => [['naziv' => 'Internistički pregled'], ['naziv' => 'Procjena hroničnih bolesti']], 'faq' => [['pitanje' => 'Kada je potreban internistički pregled?', 'odgovor' => 'Kod dugotrajnih tegoba ili kada postoji više zdravstvenih problema istovremeno.']]],
            ['naziv' => 'Primarna zdravstvena zaštita', 'slug' => 'primarna-zdravstvena-zastita', 'opis' => 'Osnovna zdravstvena njega i prvi kontakt sa zdravstvenim sistemom.', 'meta_title' => 'Primarna zdravstvena zaštita pregledi | WizMedik', 'meta_description' => 'Osnovni zdravstveni pregledi, savjetovanje i usmjeravanje pacijenata.', 'meta_keywords' => 'primarna zdravstvena zaštita, prvi pregled', 'kljucne_rijeci' => ['primarna zaštita', 'prvi pregled', 'doktor opšte prakse'], 'uvodni_tekst' => 'Primarna zdravstvena zaštita je početna tačka zdravstvene brige.', 'detaljan_opis' => 'Obuhvata ranu dijagnostiku, liječenje lakših stanja i upućivanje na dalje preglede.', 'zakljucni_tekst' => 'Kvalitetna primarna zaštita znači zdraviju populaciju.', 'usluge' => [['naziv' => 'Osnovni pregled'], ['naziv' => 'Savjetovanje']], 'faq' => [['pitanje' => 'Da li je primarna zaštita dovoljna za sve bolesti?', 'odgovor' => 'Za mnoga stanja jeste, ali za složenija je potrebna dodatna dijagnostika.']]],
            ['naziv' => 'Preventivni pregledi', 'slug' => 'preventivni-pregledi', 'opis' => 'Pregledi bez prisutnih simptoma sa ciljem ranog otkrivanja bolesti.', 'meta_title' => 'Preventivni pregledi očuvanje zdravlja | WizMedik', 'meta_description' => 'Preventivni pregledi za rano otkrivanje bolesti i očuvanje dugoročnog zdravlja.', 'meta_keywords' => 'preventivni pregled, sistematski pregled', 'kljucne_rijeci' => ['preventivni pregled', 'sistematski pregled', 'kontrola zdravlja'], 'uvodni_tekst' => 'Preventivni pregledi se rade i kada se osoba osjeća zdravo.', 'detaljan_opis' => 'Cilj preventivnih pregleda je rano otkrivanje bolesti prije pojave simptoma, što značajno povećava uspješnost liječenja.', 'zakljucni_tekst' => 'Prevencija je najefikasniji oblik zdravstvene zaštite.', 'usluge' => [['naziv' => 'Sistematski pregled'], ['naziv' => 'Kontrolni pregledi']], 'faq' => [['pitanje' => 'Koliko često raditi preventivni pregled?', 'odgovor' => 'Najmanje jednom godišnje, a po preporuci ljekara i češće.']]],
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

    private function seedSrceKrvniSudovi()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Srce i krvni sudovi',
            'slug' => 'srce-i-krvni-sudovi',
            'opis' => 'Prevencija, dijagnostika i liječenje bolesti srca, arterija i vena.',
            'meta_title' => 'Srce i krvni sudovi pregledi i liječenje | WizMedik',
            'meta_description' => 'Pregledi srca i krvnih sudova. Kardiologija, vaskularna hirurgija i angiologija na jednom mjestu.',
            'meta_keywords' => 'srce, krvni sudovi, kardiologija, vaskularna hirurgija, angiologija',
            'kljucne_rijeci' => json_encode(['srce', 'krvni sudovi', 'kardiolog', 'bol u grudima', 'pritisak', 'vene', 'arterije', 'cirkulacija', 'suženje krvnih sudova']),
            'uvodni_tekst' => 'Bolesti srca i krvnih sudova spadaju među najčešće i najozbiljnije zdravstvene probleme današnjice. Pravovremeni pregledi i pravilno liječenje igraju ključnu ulogu u očuvanju života i kvaliteta svakodnevnog funkcionisanja.',
            'detaljan_opis' => 'Oblast srca i krvnih sudova obuhvata bolesti koje zahvataju srčani mišić, srčane zaliske, arterije, vene i kapilare. Simptomi mogu biti jasni, kao što su bol u grudima ili oticanje nogu, ali i tihi, bez izraženih znakova, zbog čega su redovni pregledi od izuzetnog značaja. U okviru ove oblasti djeluju kardiolozi, angiolozi i vaskularni hirurzi koji se bave dijagnostikom, terapijom i hirurškim liječenjem oboljenja krvotoka i srca.',
            'zakljucni_tekst' => 'Briga o srcu i krvnim sudovima nije samo liječenje bolesti, već dugoročno ulaganje u zdravlje i kvalitet života.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Kardiološki pregled'],
                ['naziv' => 'Pregled krvnih sudova'],
                ['naziv' => 'Ultrazvuk srca i krvnih sudova'],
                ['naziv' => 'Procjena rizika od kardiovaskularnih bolesti']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti ljekaru za srce i krvne sudove?', 'odgovor' => 'Kod bola u grudima, lupanja srca, otežanog disanja, oticanja nogu, trnjenja ili osjećaja hladnoće u ekstremitetima.'],
                ['pitanje' => 'Da li su bolesti srca uvijek praćene simptomima?', 'odgovor' => 'Ne. Mnoge kardiovaskularne bolesti mogu dugo trajati bez izraženih simptoma.'],
                ['pitanje' => 'Koja je razlika između kardiologa, angiologa i vaskularnog hirurga?', 'odgovor' => 'Kardiolog se bavi srcem, angiolog krvnim sudovima, a vaskularni hirurg operativnim liječenjem bolesti krvnih sudova.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Kardiologija', 'slug' => 'kardiologija', 'opis' => 'Dijagnostika i liječenje bolesti srca i srčanog ritma.', 'meta_title' => 'Kardiologija pregledi srca i krvnog pritiska | WizMedik', 'meta_description' => 'Pregledi srca, EKG, ultrazvuk srca i terapija kardioloških oboljenja.', 'meta_keywords' => 'kardiologija, kardiolog, srce', 'kljucne_rijeci' => ['kardiolog', 'srce', 'bol u grudima', 'pritisak', 'aritmija', 'lupanje srca', 'EKG'], 'uvodni_tekst' => 'Kardiologija se bavi zdravljem srca i poremećajima njegovog rada.', 'detaljan_opis' => 'Kardiolog procjenjuje rad srca, srčani ritam i stanje krvnog pritiska. Bavi se bolestima kao što su povišen pritisak, aritmije, koronarna bolest i srčana slabost.', 'zakljucni_tekst' => 'Redovni kardiološki pregledi značajno smanjuju rizik od ozbiljnih srčanih komplikacija.', 'usluge' => [['naziv' => 'Kardiološki pregled'], ['naziv' => 'EKG'], ['naziv' => 'Ultrazvuk srca'], ['naziv' => 'Holter EKG i pritiska']], 'faq' => [['pitanje' => 'Da li je lupanje srca razlog za pregled?', 'odgovor' => 'Da. Svako učestalo ili neprijatno lupanje srca treba provjeriti.'], ['pitanje' => 'Da li mlade osobe trebaju kardiološki pregled?', 'odgovor' => 'Da, posebno ako postoji porodična istorija srčanih bolesti.']]],
            ['naziv' => 'Angiologija', 'slug' => 'angiologija', 'opis' => 'Bolesti arterija i vena i poremećaji cirkulacije.', 'meta_title' => 'Angiologija pregledi krvnih sudova | WizMedik', 'meta_description' => 'Pregledi i liječenje bolesti vena i arterija, poremećaja cirkulacije i tromboze.', 'meta_keywords' => 'angiologija, angiolog, krvni sudovi', 'kljucne_rijeci' => ['angiolog', 'vene', 'arterije', 'cirkulacija', 'tromboza', 'proširene vene'], 'uvodni_tekst' => 'Angiologija se bavi bolestima krvnih sudova i poremećajima protoka krvi.', 'detaljan_opis' => 'Problemi sa cirkulacijom mogu dovesti do bola, oticanja, trnjenja i promjena boje kože. Angiolog se bavi dijagnostikom i terapijom ovih stanja.', 'zakljucni_tekst' => 'Zdravi krvni sudovi su osnov pravilne cirkulacije i dobrog zdravlja.', 'usluge' => [['naziv' => 'Pregled krvnih sudova'], ['naziv' => 'Dopler krvnih sudova'], ['naziv' => 'Procjena cirkulacije']], 'faq' => [['pitanje' => 'Da li su hladne noge znak loše cirkulacije?', 'odgovor' => 'Mogu biti, ali zahtijevaju pregled kako bi se utvrdio uzrok.']]],
            ['naziv' => 'Vaskularna hirurgija', 'slug' => 'vaskularna-hirurgija', 'opis' => 'Hirurško liječenje bolesti krvnih sudova.', 'meta_title' => 'Vaskularna hirurgija operacije krvnih sudova | WizMedik', 'meta_description' => 'Hirurško liječenje suženja, začepljenja i drugih bolesti krvnih sudova.', 'meta_keywords' => 'vaskularna hirurgija, krvni sudovi, operacija vena', 'kljucne_rijeci' => ['vaskularni hirurg', 'operacija vena', 'suženje arterija', 'aneurizma'], 'uvodni_tekst' => 'Vaskularna hirurgija se primjenjuje kada konzervativno liječenje nije dovoljno.', 'detaljan_opis' => 'Vaskularni hirurg liječi ozbiljna oboljenja krvnih sudova kao što su suženja arterija, aneurizme i uznapredovale bolesti vena.', 'zakljucni_tekst' => 'Hirurško liječenje često spašava ekstremitete i život pacijenta.', 'usluge' => [['naziv' => 'Operativno liječenje krvnih sudova'], ['naziv' => 'Procjena za hirurški zahvat']], 'faq' => [['pitanje' => 'Kada je potrebna operacija krvnih sudova?', 'odgovor' => 'Kada postoji ozbiljno suženje, začepljenje ili prijetnja komplikacijama.']]],
            ['naziv' => 'Hipertenzija i kardiovaskularni rizik', 'slug' => 'hipertenzija-i-kardiovaskularni-rizik', 'opis' => 'Povišen krvni pritisak i procjena rizika od srčanih bolesti.', 'meta_title' => 'Povišen krvni pritisak i rizik za srce | WizMedik', 'meta_description' => 'Dijagnostika i liječenje povišenog krvnog pritiska i kardiovaskularnog rizika.', 'meta_keywords' => 'hipertenzija, povišen pritisak, kardiovaskularni rizik', 'kljucne_rijeci' => ['povišen pritisak', 'hipertenzija', 'rizik za srce'], 'uvodni_tekst' => 'Povišen krvni pritisak često nema simptome, ali ozbiljno ugrožava zdravlje.', 'detaljan_opis' => 'Hipertenzija je tihi ubica koji povećava rizik od infarkta, moždanog udara i drugih kardiovaskularnih komplikacija. Redovna kontrola i pravilna terapija su ključni.', 'zakljucni_tekst' => 'Kontrola krvnog pritiska je osnov prevencije srčanih bolesti.', 'usluge' => [['naziv' => 'Mjerenje krvnog pritiska'], ['naziv' => 'Procjena kardiovaskularnog rizika'], ['naziv' => 'Terapija hipertenzije']], 'faq' => [['pitanje' => 'Da li je povišen pritisak opasan ako nemam tegobe?', 'odgovor' => 'Da. Hipertenzija često nema simptome, ali povećava rizik od infarkta i moždanog udara.']]],
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

    // Due to response length limitations, I'll create a helper method to continue
    // The pattern is established - each main category gets its own seed method

    private function seedZenskoZdravlje()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Žensko zdravlje',
            'slug' => 'zensko-zdravlje',
            'opis' => 'Zdravstvena briga o ženi kroz sve faze života od puberteta do menopauze i starije dobi.',
            'meta_title' => 'Žensko zdravlje ginekološki pregledi i trudnoća | WizMedik',
            'meta_description' => 'Ginekologija, trudnoća, reproduktivna medicina i liječenje neplodnosti. Sve za zdravlje žene na jednom mjestu.',
            'meta_keywords' => 'žensko zdravlje, ginekologija, trudnoća, reproduktivna medicina, neplodnost',
            'kljucne_rijeci' => json_encode(['žensko zdravlje', 'ginekolog', 'ginekologija', 'trudnoća', 'ciklus', 'hormoni', 'neplodnost', 'menopauza', 'pregled kod ginekologa']),
            'uvodni_tekst' => 'Žensko zdravlje obuhvata sve aspekte fizičkog i reproduktivnog zdravlja žene tokom cijelog života. Redovni pregledi i pravovremena briga ključni su za prevenciju i očuvanje zdravlja.',
            'detaljan_opis' => 'Zdravlje žene prolazi kroz različite faze i promjene koje zahtijevaju stručnu medicinsku podršku. Od prvih ginekoloških pregleda, preko planiranja trudnoće i vođenja trudnoće, do liječenja hormonskih poremećaja i problema sa plodnošću. U okviru ove oblasti djeluju ginekolozi, akušeri i specijalisti reproduktivne medicine koji se bave dijagnostikom, liječenjem i savjetovanjem žena u svim životnim periodima.',
            'zakljucni_tekst' => 'Briga o ženskom zdravlju znači ulaganje u dugoročno fizičko i psihičko blagostanje žene i porodice.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Ginekološki pregled'],
                ['naziv' => 'Ultrazvuk'],
                ['naziv' => 'Savjetovanje o trudnoći'],
                ['naziv' => 'Hormonska dijagnostika'],
                ['naziv' => 'Planiranje porodice']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Koliko često treba ići na ginekološki pregled?', 'odgovor' => 'Najmanje jednom godišnje, a češće ukoliko postoje tegobe ili preporuka ljekara.'],
                ['pitanje' => 'Da li je ginekološki pregled potreban i ako nema simptoma?', 'odgovor' => 'Da. Mnoge bolesti u početku nemaju simptome i mogu se otkriti samo pregledom.'],
                ['pitanje' => 'Kada žena treba prvi put posjetiti ginekologa?', 'odgovor' => 'Preporučuje se nakon početka polne zrelosti ili ranije ukoliko postoje tegobe.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Ginekologija', 'slug' => 'ginekologija', 'opis' => 'Dijagnostika i liječenje bolesti ženskog reproduktivnog sistema.', 'meta_title' => 'Ginekologija pregledi i žensko zdravlje | WizMedik', 'meta_description' => 'Ginekološki pregledi, ultrazvuk i liječenje ginekoloških oboljenja kod žena.', 'meta_keywords' => 'ginekologija, ginekolog, ginekološki pregled', 'kljucne_rijeci' => ['ginekolog', 'ginekološki pregled', 'ciklus', 'bol u stomaku', 'vaginalne infekcije', 'krvarenje'], 'uvodni_tekst' => 'Ginekologija se bavi očuvanjem i liječenjem zdravlja ženskih polnih organa.', 'detaljan_opis' => 'Ginekolog prati menstrualni ciklus, dijagnostikuje i liječi infekcije, ciste, miome i druge ginekološke bolesti. Redovni pregledi omogućavaju rano otkrivanje ozbiljnih stanja.', 'zakljucni_tekst' => 'Redovni ginekološki pregledi su osnova zdravlja svake žene.', 'usluge' => [['naziv' => 'Ginekološki pregled'], ['naziv' => 'Ultrazvuk'], ['naziv' => 'PAPA test'], ['naziv' => 'Kolposkopija']], 'faq' => [['pitanje' => 'Da li je ginekološki pregled bolan?', 'odgovor' => 'Pregled može biti neprijatan, ali ne bi trebao biti bolan.'], ['pitanje' => 'Da li je potreban pregled ako je ciklus redovan?', 'odgovor' => 'Da. Redovan ciklus ne isključuje postojanje drugih problema.']]],
            ['naziv' => 'Akušerstvo i trudnoća', 'slug' => 'akuserstvo-i-trudnoca', 'opis' => 'Praćenje trudnoće, porođaj i postporođajna njega.', 'meta_title' => 'Trudnoća i akušerstvo praćenje trudnoće | WizMedik', 'meta_description' => 'Praćenje trudnoće, ultrazvučni pregledi i savjetovanje tokom trudnoće.', 'meta_keywords' => 'trudnoća, akušerstvo, ginekolog', 'kljucne_rijeci' => ['trudnoća', 'trudnica', 'praćenje trudnoće', 'ultrazvuk u trudnoći', 'porođaj'], 'uvodni_tekst' => 'Akušerstvo se bavi brigom o ženi tokom trudnoće i porođaja.', 'detaljan_opis' => 'Tokom trudnoće važno je redovno praćenje zdravlja majke i bebe. Akušer prati razvoj ploda, savjetuje trudnicu i reaguje na eventualne komplikacije.', 'zakljucni_tekst' => 'Pravilno vođena trudnoća doprinosi sigurnom porođaju i zdravlju majke i djeteta.', 'usluge' => [['naziv' => 'Praćenje trudnoće'], ['naziv' => 'Ultrazvuk u trudnoći'], ['naziv' => 'Savjetovanje trudnica']], 'faq' => [['pitanje' => 'Koliko često se rade pregledi u trudnoći?', 'odgovor' => 'U pravilu jednom mjesečno, a kasnije i češće po preporuci ljekara.'], ['pitanje' => 'Da li je ultrazvuk bezbjedan u trudnoći?', 'odgovor' => 'Da. Ultrazvuk je bezbjedna i standardna metoda praćenja trudnoće.']]],
            ['naziv' => 'Reproduktivna medicina', 'slug' => 'reproduktivna-medicina', 'opis' => 'Dijagnostika i liječenje problema vezanih za začeće i plodnost.', 'meta_title' => 'Reproduktivna medicina planiranje porodice | WizMedik', 'meta_description' => 'Dijagnostika i liječenje problema plodnosti i planiranje trudnoće.', 'meta_keywords' => 'reproduktivna medicina, plodnost, začeće', 'kljucne_rijeci' => ['plodnost', 'začeće', 'hormoni', 'planiranje trudnoće'], 'uvodni_tekst' => 'Reproduktivna medicina pomaže parovima u planiranju porodice.', 'detaljan_opis' => 'Obuhvata hormonsku dijagnostiku, procjenu ovulacije i savjetovanje o optimalnom vremenu za trudnoću.', 'zakljucni_tekst' => 'Savremena medicina nudi brojne mogućnosti za pomoć u ostvarivanju trudnoće.', 'usluge' => [['naziv' => 'Hormonske analize'], ['naziv' => 'Praćenje ovulacije'], ['naziv' => 'Savjetovanje parova']], 'faq' => [['pitanje' => 'Kada se obratiti specijalisti reproduktivne medicine?', 'odgovor' => 'Ako trudnoća izostane nakon godinu dana redovnih pokušaja.']]],
            ['naziv' => 'Infertilitet i IVF', 'slug' => 'infertilitet-i-ivf', 'opis' => 'Liječenje neplodnosti i potpomognuta oplodnja.', 'meta_title' => 'Neplodnost i IVF liječenje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje neplodnosti i postupci vantjelesne oplodnje.', 'meta_keywords' => 'neplodnost, infertilitet, IVF', 'kljucne_rijeci' => ['neplodnost', 'IVF', 'vantjelesna oplodnja', 'ne mogu zatrudnjeti'], 'uvodni_tekst' => 'Infertilitet je problem sa kojim se susreće sve veći broj parova.', 'detaljan_opis' => 'IVF i druge metode potpomognute oplodnje pomažu parovima kod kojih prirodno začeće nije moguće.', 'zakljucni_tekst' => 'Stručna podrška i savremene metode povećavaju šanse za ostvarenje roditeljstva.', 'usluge' => [['naziv' => 'Dijagnostika infertiliteta'], ['naziv' => 'IVF postupci'], ['naziv' => 'Savjetovanje parova']], 'faq' => [['pitanje' => 'Da li je neplodnost samo problem žene?', 'odgovor' => 'Ne. Uzrok može biti kod žene, muškarca ili kod oba partnera.'], ['pitanje' => 'Kolike su šanse za uspjeh IVF postupka?', 'odgovor' => 'Zavise od godina, uzroka i zdravstvenog stanja, ali savremeni postupci značajno povećavaju uspjeh.']]],
            ['naziv' => 'Hormonski poremećaji i menopauza', 'slug' => 'hormonski-poremecaji-i-menopauza', 'opis' => 'Poremećaji hormona i promjene u menopauzi.', 'meta_title' => 'Hormoni i menopauza žensko zdravlje | WizMedik', 'meta_description' => 'Liječenje hormonskih poremećaja i tegoba u menopauzi.', 'meta_keywords' => 'hormoni, menopauza, hormonski poremećaji', 'kljucne_rijeci' => ['menopauza', 'hormoni', 'hormonski poremećaji'], 'uvodni_tekst' => 'Hormonski poremećaji utiču na zdravlje i kvalitet života žene.', 'detaljan_opis' => 'Menopauza i hormonski poremećaji zahtijevaju stručnu podršku i pravilnu terapiju.', 'zakljucni_tekst' => 'Pravilno liječenje omogućava kvalitetan život u svim fazama.', 'usluge' => [['naziv' => 'Hormonska dijagnostika'], ['naziv' => 'Terapija menopauze']], 'faq' => [['pitanje' => 'Kada počinje menopauza?', 'odgovor' => 'Menopauza nastupa nakon 12 mjeseci bez menstrualnog ciklusa.']]],
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

    private function seedZdravljeDjece()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Zdravlje djece',
            'slug' => 'zdravlje-djece',
            'opis' => 'Medicinska briga o zdravlju djece od rođenja do adolescencije, praćenje rasta, razvoja i liječenje bolesti.',
            'meta_title' => 'Zdravlje djece pedijatrijski pregledi i savjetovanje | WizMedik',
            'meta_description' => 'Pedijatrijski pregledi, zdravlje beba i djece, dijagnostika i liječenje dječijih bolesti na jednom mjestu.',
            'meta_keywords' => 'zdravlje djece, pedijatrija, pedijatar, dječije bolesti',
            'kljucne_rijeci' => json_encode(['zdravlje djece', 'pedijatar', 'pedijatrija', 'dijete', 'beba', 'dječije bolesti', 'rast i razvoj', 'pregled djeteta']),
            'uvodni_tekst' => 'Zdravlje djece zahtijeva poseban pristup, pažnju i stručno praćenje kroz sve faze rasta i razvoja. Djeca nisu mali odrasli i svaka razvojna faza nosi svoje specifičnosti.',
            'detaljan_opis' => 'Oblast zdravlja djece obuhvata preventivne preglede, praćenje rasta i razvoja, vakcinaciju, dijagnostiku i liječenje akutnih i hroničnih bolesti. Posebna pažnja posvećuje se razvoju nervnog i kardiovaskularnog sistema, kao i oralnom zdravlju djece. U ovoj oblasti djeluju pedijatri i subspecijalisti koji se bave zdravljem novorođenčadi, dojenčadi, djece i adolescenata.',
            'zakljucni_tekst' => 'Redovni pregledi i pravovremena reakcija ključni su za zdrav i siguran razvoj djeteta.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Pedijatrijski pregled'],
                ['naziv' => 'Praćenje rasta i razvoja'],
                ['naziv' => 'Savjetovanje roditelja'],
                ['naziv' => 'Preventivni pregledi']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada dijete treba prvi put kod pedijatra?', 'odgovor' => 'Odmah nakon rođenja, a zatim redovno prema preporučenom rasporedu pregleda.'],
                ['pitanje' => 'Da li je normalno da djeca često budu bolesna?', 'odgovor' => 'Da. Imuni sistem se razvija i česte blaže infekcije su dio tog procesa.'],
                ['pitanje' => 'Kada roditelji treba da se zabrinu?', 'odgovor' => 'Ako dijete ima visoku temperaturu koja ne prolazi, gubitak apetita, pospanost ili promjene u ponašanju.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Pedijatrija', 'slug' => 'pedijatrija', 'opis' => 'Osnovna zdravstvena zaštita djece od rođenja do adolescencije.', 'meta_title' => 'Pedijatrija pregledi i zdravlje djece | WizMedik', 'meta_description' => 'Pedijatrijski pregledi, praćenje rasta, razvoja i liječenje dječijih bolesti.', 'meta_keywords' => 'pedijatrija, pedijatar, zdravlje djece', 'kljucne_rijeci' => ['pedijatar', 'pedijatrija', 'pregled djeteta', 'dječije bolesti', 'beba', 'dijete'], 'uvodni_tekst' => 'Pedijatrija je temelj zdravstvene zaštite djece.', 'detaljan_opis' => 'Pedijatar prati rast, razvoj, ishranu i imunitet djeteta, te liječi najčešće dječije bolesti. Takođe savjetuje roditelje o pravilnoj njezi i razvoju djeteta.', 'zakljucni_tekst' => 'Redovni pedijatrijski pregledi osiguravaju pravilan razvoj i rano otkrivanje problema.', 'usluge' => [['naziv' => 'Pedijatrijski pregled'], ['naziv' => 'Savjetovanje roditelja'], ['naziv' => 'Praćenje rasta i razvoja']], 'faq' => [['pitanje' => 'Koliko često dijete treba ići pedijatru?', 'odgovor' => 'U prvim godinama života redovno, a kasnije prema potrebi i savjetu pedijatra.']]],
            ['naziv' => 'Neonatologija', 'slug' => 'neonatologija', 'opis' => 'Zdravstvena briga o novorođenčadi u prvim danima i sedmicama života.', 'meta_title' => 'Neonatologija zdravlje novorođenčadi | WizMedik', 'meta_description' => 'Pregledi i praćenje zdravlja novorođenčadi, prijevremeno rođenih beba i rizičnih stanja.', 'meta_keywords' => 'neonatologija, novorođenče, beba', 'kljucne_rijeci' => ['neonatolog', 'novorođenče', 'beba', 'prijevremeno rođenje'], 'uvodni_tekst' => 'Neonatologija se bavi zdravljem beba u najranijem periodu života.', 'detaljan_opis' => 'Neonatolog prati adaptaciju bebe nakon rođenja, rast, disanje i osnovne životne funkcije, posebno kod prijevremeno rođenih ili rizičnih beba.', 'zakljucni_tekst' => 'Pravovremena neonatološka njega daje bebi najbolji početak života.', 'usluge' => [['naziv' => 'Pregled novorođenčeta'], ['naziv' => 'Praćenje rasta i razvoja']], 'faq' => [['pitanje' => 'Da li svaka beba treba neonatologa?', 'odgovor' => 'Da u prvim danima života, posebno ako postoji rizik ili komplikacije.']]],
            ['naziv' => 'Dječija neurologija', 'slug' => 'djecija-neurologija', 'opis' => 'Bolesti i poremećaji nervnog sistema kod djece.', 'meta_title' => 'Dječija neurologija razvoj i nervni sistem | WizMedik', 'meta_description' => 'Pregledi i liječenje neuroloških poremećaja kod djece.', 'meta_keywords' => 'dječija neurologija, neurolog, razvoj djeteta', 'kljucne_rijeci' => ['dječiji neurolog', 'kašnjenje u razvoju', 'epilepsija', 'grčevi'], 'uvodni_tekst' => 'Dječija neurologija prati razvoj nervnog sistema djeteta.', 'detaljan_opis' => 'Bavi se poremećajima kao što su kašnjenje u razvoju, epilepsija, smetnje kretanja i koordinacije.', 'zakljucni_tekst' => 'Rano prepoznavanje neuroloških problema poboljšava ishode liječenja.', 'usluge' => [['naziv' => 'Neurološki pregled djeteta']], 'faq' => [['pitanje' => 'Kada se dijete upućuje dječijem neurologu?', 'odgovor' => 'Ako kasni u razvoju, ima grčeve ili probleme sa koordinacijom.']]],
            ['naziv' => 'Dječija kardiologija', 'slug' => 'djecija-kardiologija', 'opis' => 'Bolesti srca i krvnih sudova kod djece.', 'meta_title' => 'Dječija kardiologija srce kod djece | WizMedik', 'meta_description' => 'Pregledi srca kod djece, urođene i stečene srčane bolesti.', 'meta_keywords' => 'dječija kardiologija, srce kod djece', 'kljucne_rijeci' => ['srce kod djece', 'šum na srcu', 'dječiji kardiolog'], 'uvodni_tekst' => 'Dječija kardiologija se bavi zdravljem srca kod djece.', 'detaljan_opis' => 'Pregledi se rade kod sumnje na urođene srčane mane, šum na srcu ili poremećaje ritma.', 'zakljucni_tekst' => 'Pravovremeni pregledi omogućavaju normalan razvoj djeteta.', 'usluge' => [['naziv' => 'Pregled srca kod djece'], ['naziv' => 'Ultrazvuk srca']], 'faq' => [['pitanje' => 'Da li je šum na srcu uvijek opasan?', 'odgovor' => 'Ne. Mnogi šumovi su bezazleni, ali zahtijevaju pregled.']]],
            ['naziv' => 'Dječija stomatologija', 'slug' => 'djecija-stomatologija', 'opis' => 'Oralno zdravlje djece i prevencija dentalnih problema.', 'meta_title' => 'Dječija stomatologija zdravlje zuba kod djece | WizMedik', 'meta_description' => 'Pregledi, prevencija i liječenje zuba kod djece.', 'meta_keywords' => 'dječija stomatologija, zubi kod djece', 'kljucne_rijeci' => ['dječiji stomatolog', 'mliječni zubi', 'karijes kod djece'], 'uvodni_tekst' => 'Dječija stomatologija stvara temelje zdravih zuba.', 'detaljan_opis' => 'Redovni pregledi pomažu djetetu da razvije zdrav odnos prema oralnoj higijeni.', 'zakljucni_tekst' => 'Zdravi mliječni zubi su važni za pravilan razvoj stalnih zuba.', 'usluge' => [['naziv' => 'Pregled zuba'], ['naziv' => 'Preventivne mjere']], 'faq' => [['pitanje' => 'Kada dijete treba prvi put kod stomatologa?', 'odgovor' => 'Kada nikne prvi zub ili najkasnije do prve godine.']]],
            ['naziv' => 'Razvojni pregledi i savjetovalište za roditelje', 'slug' => 'razvojni-pregledi-i-savjetovaliste-za-roditelje', 'opis' => 'Praćenje psihofizičkog razvoja djeteta i savjetovanje roditelja.', 'meta_title' => 'Razvojni pregledi djece savjetovanje | WizMedik', 'meta_description' => 'Praćenje razvoja djeteta i savjetovanje roditelja o zdravlju i razvoju.', 'meta_keywords' => 'razvojni pregledi, savjetovanje roditelja', 'kljucne_rijeci' => ['razvojni pregledi', 'razvoj djeteta', 'savjetovanje roditelja'], 'uvodni_tekst' => 'Razvojni pregledi prate psihofizički razvoj djeteta.', 'detaljan_opis' => 'Omogućavaju rano otkrivanje razvojnih problema i pružaju podršku roditeljima.', 'zakljucni_tekst' => 'Praćenje razvoja je ključno za pravovremenu intervenciju.', 'usluge' => [['naziv' => 'Razvojni pregled'], ['naziv' => 'Savjetovanje roditelja']], 'faq' => [['pitanje' => 'Šta ako sumnjam da dijete kasni u razvoju?', 'odgovor' => 'Obratite se pedijatru koji će procijeniti potrebu za dodatnim pregledima.']]],
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

    private function seedKozaKosaNokti()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Koža, kosa i nokti',
            'slug' => 'koza-kosa-i-nokti',
            'opis' => 'Zdravlje kože, kose i noktiju, dijagnostika i liječenje kožnih oboljenja i estetskih promjena.',
            'meta_title' => 'Koža, kosa i nokti dermatološki pregledi | WizMedik',
            'meta_description' => 'Dermatološki pregledi, problemi sa kožom, kosom i noktima, liječenje i savjetovanje.',
            'meta_keywords' => 'koža, kosa, nokti, dermatologija, dermatolog',
            'kljucne_rijeci' => json_encode(['koža', 'dermatolog', 'osip', 'akne', 'svrab kože', 'opadanje kose', 'problemi sa noktima', 'pregled madeža', 'kožne promjene']),
            'uvodni_tekst' => 'Koža, kosa i nokti nisu samo estetski dio izgleda, već važan pokazatelj opšteg zdravstvenog stanja organizma. Promjene na koži često su prvi znak različitih zdravstvenih problema.',
            'detaljan_opis' => 'Oblast zdravlja kože, kose i noktiju obuhvata dijagnostiku i liječenje kožnih bolesti, infekcija, alergijskih reakcija, promjena pigmentacije, opadanja kose i oboljenja noktiju. Takođe uključuje preventivne preglede, posebno pregled madeža i sumnjivih promjena na koži. U okviru ove oblasti djeluju dermatolozi i dermato venerolozi, kao i specijalisti estetske dermatologije koji se bave poboljšanjem izgleda i kvaliteta kože.',
            'zakljucni_tekst' => 'Svaka promjena na koži koja traje, boli, svrbi ili se mijenja zahtijeva pregled dermatologa.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Dermatološki pregled'],
                ['naziv' => 'Pregled madeža'],
                ['naziv' => 'Liječenje kožnih bolesti'],
                ['naziv' => 'Savjetovanje o njezi kože']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti dermatologu?', 'odgovor' => 'Kada primijetite osip, promjene boje ili oblika madeža, svrab, akne koje ne prolaze ili pojačano opadanje kose.'],
                ['pitanje' => 'Da li su kožne promjene uvijek bezazlene?', 'odgovor' => 'Ne. Neke promjene mogu biti znak ozbiljnijih oboljenja i zahtijevaju pregled.'],
                ['pitanje' => 'Da li dermatolog liječi i probleme sa kosom i noktima?', 'odgovor' => 'Da. Dermatolog se bavi kožom, kosom i noktima kao cjelinom.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Dermatologija', 'slug' => 'dermatologija', 'opis' => 'Dijagnostika i liječenje bolesti kože, kose i noktiju.', 'meta_title' => 'Dermatologija pregledi i kožne bolesti | WizMedik', 'meta_description' => 'Dermatološki pregledi, liječenje akni, ekcema, psorijaze i drugih kožnih oboljenja.', 'meta_keywords' => 'dermatologija, dermatolog, kožne bolesti', 'kljucne_rijeci' => ['dermatolog', 'akne', 'ekcem', 'psorijaza', 'osip', 'svrab kože', 'pregled madeža'], 'uvodni_tekst' => 'Dermatologija se bavi bolestima i promjenama na koži, kosi i noktima.', 'detaljan_opis' => 'Dermatolog dijagnostikuje i liječi širok spektar kožnih oboljenja, uključujući upalne, hronične i autoimune bolesti kože, kao i infekcije i alergijske reakcije.', 'zakljucni_tekst' => 'Rana dijagnostika omogućava uspješno liječenje i sprječavanje komplikacija.', 'usluge' => [['naziv' => 'Dermatološki pregled'], ['naziv' => 'Pregled madeža'], ['naziv' => 'Liječenje akni i ekcema']], 'faq' => [['pitanje' => 'Da li akne prestaju same od sebe?', 'odgovor' => 'Kod nekih osoba da, ali često zahtijevaju stručnu terapiju.'], ['pitanje' => 'Koliko često treba pregledati madeže?', 'odgovor' => 'Najmanje jednom godišnje ili ranije ako se madež mijenja.']]],
            ['naziv' => 'Dermato venerologija', 'slug' => 'dermato-venerologija', 'opis' => 'Kožne i polno prenosive bolesti.', 'meta_title' => 'Dermato venerologija kožne i polno prenosive bolesti | WizMedik', 'meta_description' => 'Dijagnostika i liječenje kožnih i polno prenosivih bolesti uz diskretan i stručan pristup.', 'meta_keywords' => 'dermato venerologija, polno prenosive bolesti, kožne infekcije', 'kljucne_rijeci' => ['polno prenosive bolesti', 'genitalne promjene', 'infekcije kože', 'svrab genitalija'], 'uvodni_tekst' => 'Dermato venerologija se bavi kožnim i polno prenosivim bolestima.', 'detaljan_opis' => 'Obuhvata dijagnostiku i liječenje infekcija koje se prenose polnim putem, kao i kožnih promjena u intimnoj regiji. Pregledi se obavljaju diskretno i povjerljivo.', 'zakljucni_tekst' => 'Rano liječenje sprječava širenje infekcije i ozbiljne komplikacije.', 'usluge' => [['naziv' => 'Pregled kožnih infekcija'], ['naziv' => 'Savjetovanje i terapija']], 'faq' => [['pitanje' => 'Da li su polno prenosive bolesti uvijek praćene simptomima?', 'odgovor' => 'Ne. Mnoge infekcije mogu dugo biti bez simptoma.'], ['pitanje' => 'Da li je pregled povjerljiv?', 'odgovor' => 'Da. Diskrecija je osnovni princip u dermato venerologiji.']]],
            ['naziv' => 'Estetska dermatologija', 'slug' => 'estetska-dermatologija', 'opis' => 'Poboljšanje izgleda kože i usporavanje znakova starenja.', 'meta_title' => 'Estetska dermatologija njega i izgled kože | WizMedik', 'meta_description' => 'Estetski dermatološki tretmani za zdraviju i mlađu kožu.', 'meta_keywords' => 'estetska dermatologija, njega kože, podmlađivanje', 'kljucne_rijeci' => ['estetski tretmani', 'bore', 'fleke na koži', 'njega lica'], 'uvodni_tekst' => 'Estetska dermatologija kombinuje medicinsko znanje i estetiku.', 'detaljan_opis' => 'Cilj estetske dermatologije je poboljšanje kvaliteta kože, smanjenje znakova starenja i rješavanje estetskih problema uz očuvanje prirodnog izgleda.', 'zakljucni_tekst' => 'Zdrava koža je osnova lijepog izgleda.', 'usluge' => [['naziv' => 'Estetski dermatološki tretmani'], ['naziv' => 'Savjetovanje o njezi kože']], 'faq' => [['pitanje' => 'Da li su estetski tretmani bezbjedni?', 'odgovor' => 'Da, kada ih izvodi stručno medicinsko osoblje.']]],
            ['naziv' => 'Bolesti kose i vlasišta', 'slug' => 'bolesti-kose-i-vlasista', 'opis' => 'Opadanje kose i problemi vlasišta.', 'meta_title' => 'Bolesti kose i vlasišta opadanje kose | WizMedik', 'meta_description' => 'Dijagnostika i liječenje opadanja kose i problema vlasišta.', 'meta_keywords' => 'opadanje kose, alopecija, perut', 'kljucne_rijeci' => ['opadanje kose', 'alopecija', 'perut', 'svrab vlasišta'], 'uvodni_tekst' => 'Problemi sa kosom i vlasištom zahtijevaju stručnu procjenu.', 'detaljan_opis' => 'Dermatolog dijagnostikuje uzroke opadanja kose i problema vlasišta te predlaže odgovarajuću terapiju.', 'zakljucni_tekst' => 'Rano liječenje može spriječiti trajni gubitak kose.', 'usluge' => [['naziv' => 'Pregled vlasišta'], ['naziv' => 'Terapija opadanja kose']], 'faq' => [['pitanje' => 'Da li opadanje kose uvijek znači bolest?', 'odgovor' => 'Ne, ali zahtijeva pregled kako bi se utvrdio uzrok.']]],
            ['naziv' => 'Bolesti noktiju', 'slug' => 'bolesti-noktiju', 'opis' => 'Promjene i oboljenja noktiju.', 'meta_title' => 'Bolesti noktiju dijagnostika i liječenje | WizMedik', 'meta_description' => 'Pregledi i liječenje promjena i oboljenja noktiju.', 'meta_keywords' => 'bolesti noktiju, promjene noktiju', 'kljucne_rijeci' => ['bolesti noktiju', 'promjene noktiju', 'gljivice noktiju'], 'uvodni_tekst' => 'Nokti često odražavaju opšte zdravstveno stanje.', 'detaljan_opis' => 'Promjene boje, oblika ili strukture noktiju mogu ukazivati na različita oboljenja koja zahtijevaju dermatološku procjenu.', 'zakljucni_tekst' => 'Pravovremena dijagnostika omogućava uspješno liječenje.', 'usluge' => [['naziv' => 'Pregled noktiju'], ['naziv' => 'Liječenje oboljenja noktiju']], 'faq' => [['pitanje' => 'Da li promjene na noktima mogu ukazivati na bolest?', 'odgovor' => 'Da. Nokti često odražavaju opšte zdravstveno stanje.']]],
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

    private function seedNervniSistem()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Nervni sistem',
            'slug' => 'nervni-sistem',
            'opis' => 'Bolesti mozga, kičmene moždine i perifernih nerava.',
            'meta_title' => 'Nervni sistem neurološki pregledi i liječenje | WizMedik',
            'meta_description' => 'Pregledi i liječenje bolesti nervnog sistema, neurologija i neurohirurgija na jednom mjestu.',
            'meta_keywords' => 'nervni sistem, neurologija, neurohirurgija',
            'kljucne_rijeci' => json_encode(['nervni sistem', 'neurolog', 'neurohirurg', 'glavobolja', 'vrtoglavica', 'trnjenje', 'gubitak snage', 'epilepsija']),
            'uvodni_tekst' => 'Nervni sistem upravlja svim funkcijama tijela i omogućava kretanje, govor, pamćenje i osjet. Poremećaji nervnog sistema mogu značajno uticati na kvalitet života.',
            'detaljan_opis' => 'Oblast nervnog sistema obuhvata bolesti mozga, kičmene moždine i perifernih nerava. Simptomi mogu biti različiti i uključuju glavobolje, vrtoglavicu, trnjenje, slabost mišića, smetnje govora ili pamćenja. U ovoj oblasti djeluju neurolozi koji se bave dijagnostikom i konzervativnim liječenjem, kao i neurohirurzi koji se bave operativnim liječenjem složenih oboljenja nervnog sistema.',
            'zakljucni_tekst' => 'Rano prepoznavanje neuroloških simptoma omogućava pravovremeno liječenje i bolje ishode.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Neurološki pregled'],
                ['naziv' => 'Dijagnostika bolesti nervnog sistema'],
                ['naziv' => 'Procjena za hirurško liječenje']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti neurologu?', 'odgovor' => 'Ako imate učestale glavobolje, vrtoglavicu, trnjenje, slabost ili probleme sa govorom i pamćenjem.'],
                ['pitanje' => 'Da li su neurološki simptomi uvijek ozbiljni?', 'odgovor' => 'Ne uvijek, ali ih nikada ne treba ignorisati.'],
                ['pitanje' => 'Koja je razlika između neurologa i neurohirurga?', 'odgovor' => 'Neurolog liječi bolesti nervnog sistema lijekovima i terapijom, dok neurohirurg izvodi operativne zahvate kada su potrebni.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Neurologija', 'slug' => 'neurologija', 'opis' => 'Dijagnostika i liječenje bolesti nervnog sistema bez operacije.', 'meta_title' => 'Neurologija pregledi i liječenje | WizMedik', 'meta_description' => 'Neurološki pregledi i liječenje glavobolja, epilepsije, moždanog udara i drugih neuroloških stanja.', 'meta_keywords' => 'neurologija, neurolog, neurološki pregled', 'kljucne_rijeci' => ['neurolog', 'glavobolja', 'migrena', 'epilepsija', 'vrtoglavica', 'trnjenje ruku i nogu'], 'uvodni_tekst' => 'Neurologija se bavi poremećajima nervnog sistema koji se liječe bez operativnih zahvata.', 'detaljan_opis' => 'Neurolog procjenjuje funkciju mozga, kičmene moždine i nerava. Liječi stanja kao što su migrene, epilepsija, multipla skleroza, Parkinsonova bolest i posljedice moždanog udara.', 'zakljucni_tekst' => 'Neurološki pregled je ključan za postavljanje tačne dijagnoze i započinjanje terapije.', 'usluge' => [['naziv' => 'Neurološki pregled'], ['naziv' => 'Dijagnostika neuroloških bolesti']], 'faq' => [['pitanje' => 'Da li su česte glavobolje razlog za pregled?', 'odgovor' => 'Da. Posebno ako se učestalost ili intenzitet mijenjaju.'], ['pitanje' => 'Da li neurolog liječi i vrtoglavice?', 'odgovor' => 'Da. Vrtoglavice su čest neurološki simptom.']]],
            ['naziv' => 'Neurohirurgija', 'slug' => 'neurohirurgija', 'opis' => 'Hirurško liječenje bolesti mozga, kičme i nerava.', 'meta_title' => 'Neurohirurgija operacije nervnog sistema | WizMedik', 'meta_description' => 'Operativno liječenje tumora, povreda i drugih ozbiljnih oboljenja nervnog sistema.', 'meta_keywords' => 'neurohirurgija, neurohirurg, operacija kičme', 'kljucne_rijeci' => ['neurohirurg', 'operacija kičme', 'tumor mozga', 'hernija diska'], 'uvodni_tekst' => 'Neurohirurgija se primjenjuje kada konzervativno liječenje nije dovoljno.', 'detaljan_opis' => 'Neurohirurg se bavi operacijama mozga, kičmene moždine i perifernih nerava kod tumora, povreda, diskus hernije i drugih ozbiljnih stanja.', 'zakljucni_tekst' => 'Hirurško liječenje često donosi značajno poboljšanje kvaliteta života.', 'usluge' => [['naziv' => 'Neurohirurški pregled'], ['naziv' => 'Procjena za operaciju']], 'faq' => [['pitanje' => 'Da li svaka bolest kičme zahtijeva operaciju?', 'odgovor' => 'Ne. Većina se liječi konzervativno, a operacija je potrebna samo u određenim slučajevima.']]],
            ['naziv' => 'Glavobolje i migrene', 'slug' => 'glavobolje-i-migrene', 'opis' => 'Dijagnostika i liječenje hroničnih i akutnih glavobolja.', 'meta_title' => 'Glavobolje i migrene dijagnostika i liječenje | WizMedik', 'meta_description' => 'Liječenje hroničnih glavobolja i migrena.', 'meta_keywords' => 'glavobolja, migrena, bol u glavi', 'kljucne_rijeci' => ['glavobolja', 'migrena', 'bol u glavi'], 'uvodni_tekst' => 'Glavobolje i migrene mogu značajno narušiti kvalitet života.', 'detaljan_opis' => 'Neurolog procjenjuje tip i uzrok glavobolje te propisuje odgovarajuću terapiju za smanjenje učestalosti i intenziteta napada.', 'zakljucni_tekst' => 'Pravilna dijagnostika omogućava efikasno liječenje.', 'usluge' => [['naziv' => 'Dijagnostika glavobolja'], ['naziv' => 'Terapija migrena']], 'faq' => [['pitanje' => 'Kada je glavobolja znak za zabrinutost?', 'odgovor' => 'Ako je jaka, nagla, učestala ili praćena drugim simptomima kao što su slabost ili smetnje vida.']]],
            ['naziv' => 'Bolesti kičme i perifernih nerava', 'slug' => 'bolesti-kicme-i-perifernih-nerava', 'opis' => 'Bolovi u leđima, vratu i trnjenje ekstremiteta.', 'meta_title' => 'Bolesti kičme i perifernih nerava | WizMedik', 'meta_description' => 'Dijagnostika i liječenje bolova u kičmi i poremećaja perifernih nerava.', 'meta_keywords' => 'bolesti kičme, periferni nervi, bol u leđima', 'kljucne_rijeci' => ['bol u leđima', 'bol u vratu', 'trnjenje ruku', 'trnjenje nogu'], 'uvodni_tekst' => 'Bolesti kičme i perifernih nerava su čest uzrok bola i funkcionalnih smetnji.', 'detaljan_opis' => 'Neurolog dijagnostikuje uzroke bola u kičmi i trnjenja ekstremiteta te predlaže konzervativno ili hirurško liječenje.', 'zakljucni_tekst' => 'Pravovremena dijagnostika sprječava trajne posljedice.', 'usluge' => [['naziv' => 'Neurološki pregled kičme'], ['naziv' => 'Dijagnostika perifernih nerava']], 'faq' => [['pitanje' => 'Da li trnjenje ruku i nogu ukazuje na problem sa nervima?', 'odgovor' => 'Često da i zahtijeva neurološki pregled.']]],
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

    private function seedKostiZgloboviMisici()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Kosti, zglobovi i mišići',
            'slug' => 'kosti-zglobovi-i-misici',
            'opis' => 'Bolesti i povrede lokomotornog sistema, bolovi u zglobovima, leđima i mišićima.',
            'meta_title' => 'Kosti, zglobovi i mišići pregledi i liječenje | WizMedik',
            'meta_description' => 'Ortopedija, traumatologija, reumatologija i sportska medicina. Pregledi i liječenje bolova i povreda.',
            'meta_keywords' => 'kosti, zglobovi, mišići, ortopedija, reumatologija, traumatologija',
            'kljucne_rijeci' => json_encode(['bol u zglobovima', 'bol u leđima', 'koljeno', 'kuk', 'kičma', 'ortoped', 'povreda', 'mišići', 'reuma', 'sportske povrede']),
            'uvodni_tekst' => 'Kosti, zglobovi i mišići omogućavaju kretanje i svakodnevno funkcionisanje. Bolovi, ukočenost i povrede lokomotornog sistema značajno utiču na kvalitet života.',
            'detaljan_opis' => 'Oblast kostiju, zglobova i mišića obuhvata dijagnostiku i liječenje povreda, degenerativnih promjena, upalnih i hroničnih oboljenja lokomotornog sistema. Najčešći razlozi dolaska su bol u leđima, koljenima, kukovima, ramenima i vratu. U okviru ove oblasti djeluju ortopedi, traumatolozi, reumatolozi i specijalisti sportske medicine koji se bave liječenjem povreda, hroničnih bolesti i oporavkom nakon napora ili operacija.',
            'zakljucni_tekst' => 'Pravovremena dijagnostika i odgovarajuće liječenje omogućavaju očuvanje pokretljivosti i aktivnog života.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Ortopedski pregled'],
                ['naziv' => 'Pregled povreda'],
                ['naziv' => 'Dijagnostika bolova u zglobovima'],
                ['naziv' => 'Savjetovanje i plan terapije']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti ljekaru zbog bolova u zglobovima ili leđima?', 'odgovor' => 'Ako bol traje duže od nekoliko dana, pojačava se ili ograničava kretanje.'],
                ['pitanje' => 'Da li su bolovi u zglobovima znak ozbiljne bolesti?', 'odgovor' => 'Ne uvijek, ali mogu ukazivati na degenerativne ili upalne promjene.'],
                ['pitanje' => 'Da li je mirovanje uvijek najbolje rješenje?', 'odgovor' => 'Ne. U mnogim slučajevima pravilno kretanje i terapija su važni za oporavak.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Ortopedija', 'slug' => 'ortopedija', 'opis' => 'Bolesti i deformiteti kostiju i zglobova.', 'meta_title' => 'Ortopedija pregledi i liječenje zglobova | WizMedik', 'meta_description' => 'Ortopedski pregledi i liječenje bolesti i deformiteta kostiju i zglobova.', 'meta_keywords' => 'ortopedija, ortoped, zglobovi', 'kljucne_rijeci' => ['ortoped', 'bol u koljenu', 'kuk', 'rame', 'kičma', 'zglobovi'], 'uvodni_tekst' => 'Ortopedija se bavi zdravljem kostiju i zglobova.', 'detaljan_opis' => 'Ortoped dijagnostikuje i liječi degenerativne promjene, deformitete, povrede i hronične bolesti lokomotornog sistema.', 'zakljucni_tekst' => 'Ortopedski pregled pomaže u očuvanju pokretljivosti i smanjenju bola.', 'usluge' => [['naziv' => 'Ortopedski pregled'], ['naziv' => 'Procjena stanja zglobova']], 'faq' => [['pitanje' => 'Da li je bol u koljenu uvijek znak oštećenja?', 'odgovor' => 'Ne, ali zahtijeva pregled ako traje ili se pogoršava.']]],
            ['naziv' => 'Traumatologija', 'slug' => 'traumatologija', 'opis' => 'Liječenje povreda kostiju, zglobova i mišića.', 'meta_title' => 'Traumatologija povrede i liječenje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje povreda nastalih usljed padova, nezgoda i udaraca.', 'meta_keywords' => 'traumatologija, povrede, prelomi', 'kljucne_rijeci' => ['povreda', 'prelom', 'uganuće', 'iščašenje', 'pad'], 'uvodni_tekst' => 'Traumatologija se bavi akutnim povredama lokomotornog sistema.', 'detaljan_opis' => 'Traumatolog liječi prelome, uganuća, istegnuća i povrede nastale u nezgodama ili sportskim aktivnostima.', 'zakljucni_tekst' => 'Brza i pravilna reakcija nakon povrede sprječava komplikacije.', 'usluge' => [['naziv' => 'Pregled povrede'], ['naziv' => 'Imobilizacija'], ['naziv' => 'Kontrola zarastanja']], 'faq' => [['pitanje' => 'Da li svaka povreda zahtijeva snimanje?', 'odgovor' => 'Ne, ali kod jačeg bola ili deformiteta snimanje je potrebno.']]],
            ['naziv' => 'Reumatologija', 'slug' => 'reumatologija', 'opis' => 'Upalne i hronične bolesti zglobova i vezivnog tkiva.', 'meta_title' => 'Reumatologija bolovi i ukočenost zglobova | WizMedik', 'meta_description' => 'Dijagnostika i liječenje reumatskih bolesti i hroničnih bolova u zglobovima.', 'meta_keywords' => 'reumatologija, reumatolog, reuma', 'kljucne_rijeci' => ['reumatolog', 'reuma', 'ukočenost zglobova', 'otok zglobova'], 'uvodni_tekst' => 'Reumatologija se bavi bolestima koje uzrokuju bol i ukočenost zglobova.', 'detaljan_opis' => 'Reumatolog liječi upalne bolesti poput reumatoidnog artritisa, kao i degenerativna stanja koja uzrokuju hronični bol.', 'zakljucni_tekst' => 'Rano liječenje reumatskih bolesti sprečava oštećenja zglobova.', 'usluge' => [['naziv' => 'Reumatološki pregled'], ['naziv' => 'Praćenje hroničnih bolesti']], 'faq' => [['pitanje' => 'Da li je jutarnja ukočenost znak reume?', 'odgovor' => 'Može biti i zahtijeva pregled.']]],
            ['naziv' => 'Sportska medicina', 'slug' => 'sportska-medicina', 'opis' => 'Prevencija i liječenje sportskih povreda i oporavak.', 'meta_title' => 'Sportska medicina povrede i oporavak | WizMedik', 'meta_description' => 'Dijagnostika i liječenje povreda nastalih tokom sportskih i rekreativnih aktivnosti.', 'meta_keywords' => 'sportska medicina, sportske povrede', 'kljucne_rijeci' => ['sportske povrede', 'bol u mišićima', 'istegnuće', 'oporavak'], 'uvodni_tekst' => 'Sportska medicina pomaže sportistima i rekreativcima.', 'detaljan_opis' => 'Bavi se prevencijom povreda, pravilnim oporavkom i vraćanjem fizičkoj aktivnosti.', 'zakljucni_tekst' => 'Pravilan oporavak sprečava ponovne povrede.', 'usluge' => [['naziv' => 'Pregled sportske povrede'], ['naziv' => 'Savjetovanje o oporavku']], 'faq' => [['pitanje' => 'Da li sportska medicina važi samo za profesionalne sportiste?', 'odgovor' => 'Ne. Namijenjena je i rekreativcima.']]],
            ['naziv' => 'Bol u leđima i kičmi', 'slug' => 'bol-u-ledima-i-kicmi', 'opis' => 'Hronični i akutni bolovi u kičmi.', 'meta_title' => 'Bol u leđima i kičmi dijagnostika | WizMedik', 'meta_description' => 'Dijagnostika i liječenje bolova u leđima i kičmi.', 'meta_keywords' => 'bol u leđima, bol u kičmi', 'kljucne_rijeci' => ['bol u leđima', 'bol u kičmi', 'lumbago'], 'uvodni_tekst' => 'Bol u leđima je jedan od najčešćih zdravstvenih problema.', 'detaljan_opis' => 'Ortoped ili neurolog procjenjuje uzrok bola i predlaže odgovarajuću terapiju ili rehabilitaciju.', 'zakljucni_tekst' => 'Pravovremeno liječenje sprječava hronične probleme.', 'usluge' => [['naziv' => 'Pregled kičme'], ['naziv' => 'Dijagnostika bola']], 'faq' => [['pitanje' => 'Da li je bol u leđima uvijek zbog kičme?', 'odgovor' => 'Ne, ali zahtijeva pregled radi utvrđivanja uzroka.']]],
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

    private function seedUhoGrloNos()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Uho, grlo i nos',
            'slug' => 'uho-grlo-i-nos',
            'opis' => 'Bolesti i poremećaji sluha, disanja, glasa i ravnoteže.',
            'meta_title' => 'Uho, grlo i nos ORL pregledi i liječenje | WizMedik',
            'meta_description' => 'ORL pregledi, problemi sa sluhom, sinusima, grlom i glasom. Dijagnostika i liječenje na jednom mjestu.',
            'meta_keywords' => 'uho, grlo, nos, ORL, otorinolaringologija, sluh, sinusi',
            'kljucne_rijeci' => json_encode(['ORL', 'uho', 'grlo', 'nos', 'bol u uhu', 'sinusi', 'zapušen nos', 'promuklost', 'vrtoglavica', 'sluh', 'zujanje u ušima']),
            'uvodni_tekst' => 'Zdravlje uha, grla i nosa direktno utiče na disanje, sluh, govor i ravnotežu. Tegobe u ovoj oblasti česte su kod djece i odraslih i često se ponavljaju ako se ne liječe pravilno.',
            'detaljan_opis' => 'Oblast uho, grlo i nos obuhvata bolesti i poremećaje gornjih disajnih puteva, sluha i ravnoteže. Najčešći problemi uključuju upale uha i grla, probleme sa sinusima, zapušen nos, promuklost, smetnje sluha i vrtoglavicu. U ovoj oblasti djeluju otorinolaringolozi i audiolozi koji se bave dijagnostikom, terapijom i savjetovanjem pacijenata svih uzrasta.',
            'zakljucni_tekst' => 'Pravovremeni ORL pregled sprječava hronične tegobe i komplikacije koje mogu značajno narušiti kvalitet života.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'ORL pregled'],
                ['naziv' => 'Pregled sluha'],
                ['naziv' => 'Dijagnostika sinusa'],
                ['naziv' => 'Savjetovanje i terapija']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti ORL ljekaru?', 'odgovor' => 'Ako imate učestale upale grla ili uha, dugotrajno zapušen nos, bol u uhu, promuklost ili smetnje sluha.'],
                ['pitanje' => 'Da li su problemi sa sinusima uvijek zbog prehlade?', 'odgovor' => 'Ne. Mogu biti posljedica alergija, anatomskih promjena ili hroničnih upala.'],
                ['pitanje' => 'Da li su ORL problemi česti kod djece?', 'odgovor' => 'Da. Djeca često imaju upale uha, krajnika i adenoida.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'ORL i otorinolaringologija', 'slug' => 'orl-i-otorinolaringologija', 'opis' => 'Dijagnostika i liječenje bolesti uha, grla i nosa.', 'meta_title' => 'ORL pregledi bolesti uha grla i nosa | WizMedik', 'meta_description' => 'ORL pregledi i liječenje upala, problema sa disanjem i glasom.', 'meta_keywords' => 'ORL, otorinolaringologija, otorinolaringolog', 'kljucne_rijeci' => ['ORL', 'otorinolaringolog', 'upala uha', 'upala grla', 'zapušen nos'], 'uvodni_tekst' => 'ORL se bavi bolestima gornjih disajnih puteva i sluha.', 'detaljan_opis' => 'ORL ljekar dijagnostikuje i liječi infekcije, upale, alergijske reakcije i funkcionalne poremećaje uha, grla i nosa.', 'zakljucni_tekst' => 'Redovni ORL pregledi sprečavaju prelazak akutnih stanja u hronične probleme.', 'usluge' => [['naziv' => 'ORL pregled'], ['naziv' => 'Pregled nosa i grla'], ['naziv' => 'Terapija upala']], 'faq' => [['pitanje' => 'Da li upale uha mogu proći same?', 'odgovor' => 'Ponekad da, ali često zahtijevaju terapiju kako bi se spriječile komplikacije.']]],
            ['naziv' => 'Audiologija', 'slug' => 'audiologija', 'opis' => 'Dijagnostika i procjena sluha kod djece i odraslih.', 'meta_title' => 'Audiologija pregledi sluha | WizMedik', 'meta_description' => 'Testiranje sluha, procjena oštećenja sluha i savjetovanje.', 'meta_keywords' => 'audiologija, sluh, audiolog', 'kljucne_rijeci' => ['audiolog', 'pregled sluha', 'test sluha', 'slab sluh'], 'uvodni_tekst' => 'Audiologija se bavi procjenom i očuvanjem sluha.', 'detaljan_opis' => 'Audiolog sprovodi testove sluha, procjenjuje stepen oštećenja i savjetuje o daljem liječenju ili pomagalima.', 'zakljucni_tekst' => 'Rano otkrivanje problema sa sluhom značajno poboljšava kvalitet života.', 'usluge' => [['naziv' => 'Test sluha'], ['naziv' => 'Audiometrija'], ['naziv' => 'Savjetovanje']], 'faq' => [['pitanje' => 'Da li slab sluh dolazi samo u starijoj dobi?', 'odgovor' => 'Ne. Može se javiti u bilo kojoj životnoj dobi.']]],
            ['naziv' => 'Poremećaji sluha', 'slug' => 'poremecaji-sluha', 'opis' => 'Smanjen ili izmijenjen sluh, zujanje u ušima i osjećaj punoće u uhu.', 'meta_title' => 'Poremećaji sluha slab sluh i zujanje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje poremećaja sluha i zujanja u ušima.', 'meta_keywords' => 'poremećaji sluha, zujanje u ušima, slab sluh', 'kljucne_rijeci' => ['slab sluh', 'zujanje u ušima', 'šum u uhu', 'gubitak sluha'], 'uvodni_tekst' => 'Poremećaji sluha mogu nastati naglo ili postepeno.', 'detaljan_opis' => 'Smanjen sluh, šum ili zujanje u ušima mogu imati različite uzroke, od upala do oštećenja nerva sluha.', 'zakljucni_tekst' => 'Svaka promjena sluha zahtijeva stručnu procjenu.', 'usluge' => [['naziv' => 'Procjena sluha'], ['naziv' => 'Dijagnostika uzroka']], 'faq' => [['pitanje' => 'Da li je zujanje u ušima opasno?', 'odgovor' => 'Najčešće nije, ali može ukazivati na određene poremećaje.']]],
            ['naziv' => 'Sinusi i disanje kroz nos', 'slug' => 'sinusi-i-disanje-kroz-nos', 'opis' => 'Upale sinusa i otežano disanje kroz nos.', 'meta_title' => 'Sinusi i disanje kroz nos | WizMedik', 'meta_description' => 'Dijagnostika i liječenje upala sinusa i problema sa disanjem.', 'meta_keywords' => 'sinusi, upala sinusa, zapušen nos', 'kljucne_rijeci' => ['sinusi', 'upala sinusa', 'zapušen nos', 'bol u licu'], 'uvodni_tekst' => 'Problemi sa sinusima značajno utiču na kvalitet života.', 'detaljan_opis' => 'ORL ljekar dijagnostikuje uzroke upale sinusa i problema sa disanjem te predlaže odgovarajuću terapiju.', 'zakljucni_tekst' => 'Pravovremeno liječenje sprječava hronične probleme.', 'usluge' => [['naziv' => 'Pregled sinusa'], ['naziv' => 'Terapija upala']], 'faq' => [['pitanje' => 'Kada upala sinusa postaje hronična?', 'odgovor' => 'Kada simptomi traju duže od nekoliko sedmica ili se često ponavljaju.']]],
            ['naziv' => 'Vrtoglavice i ravnoteža', 'slug' => 'vrtoglavice-i-ravnoteza', 'opis' => 'Poremećaji ravnoteže povezani sa unutrašnjim uhom.', 'meta_title' => 'Vrtoglavice i ravnoteža | WizMedik', 'meta_description' => 'Dijagnostika i liječenje vrtoglavica i poremećaja ravnoteže.', 'meta_keywords' => 'vrtoglavica, ravnoteža', 'kljucne_rijeci' => ['vrtoglavica', 'poremećaj ravnoteže', 'vrtoglavica iz uha'], 'uvodni_tekst' => 'Vrtoglavice mogu biti povezane sa poremećajima unutrašnjeg uha.', 'detaljan_opis' => 'ORL ljekar procjenjuje uzrok vrtoglavica i predlaže odgovarajuću terapiju ili upućuje na dodatne preglede.', 'zakljucni_tekst' => 'Tačna dijagnostika omogućava efikasno liječenje.', 'usluge' => [['naziv' => 'Pregled ravnoteže'], ['naziv' => 'Dijagnostika vrtoglavica']], 'faq' => [['pitanje' => 'Da li vrtoglavica uvijek dolazi iz uha?', 'odgovor' => 'Ne, ali često je povezana sa poremećajem unutrašnjeg uha.']]],
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

    private function seedOciVid()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Oči i vid',
            'slug' => 'oci-i-vid',
            'opis' => 'Dijagnostika, liječenje i praćenje zdravlja očiju i vida kod djece i odraslih.',
            'meta_title' => 'Oči i vid pregledi i liječenje | WizMedik',
            'meta_description' => 'Pregledi očiju i vida, oftalmologija i optometrija. Dijagnostika i korekcija problema sa vidom.',
            'meta_keywords' => 'oči, vid, oftalmologija, oftalmolog, optometrija, pregled vida',
            'kljucne_rijeci' => json_encode(['oči', 'vid', 'oftalmolog', 'pregled očiju', 'slab vid', 'zamagljen vid', 'bol u očima', 'crvenilo očiju', 'kontrola vida']),
            'uvodni_tekst' => 'Zdravlje očiju i dobar vid imaju ključnu ulogu u svakodnevnom životu, radu i sigurnosti. Problemi sa vidom mogu nastati postepeno ili iznenada i ne treba ih zanemarivati.',
            'detaljan_opis' => 'Oblast očiju i vida obuhvata dijagnostiku i liječenje bolesti oka, kao i procjenu i korekciju vida. Najčešći razlozi dolaska su slab ili zamagljen vid, crvenilo očiju, bol, peckanje, suzenje ili glavobolje povezane sa vidom. U ovoj oblasti djeluju oftalmolozi, doktori medicine koji se bave bolestima oka, i optometristi koji se bave mjerenjem vida i korekcijom refraktivnih grešaka.',
            'zakljucni_tekst' => 'Redovni pregledi očiju omogućavaju rano otkrivanje problema i očuvanje dobrog vida tokom cijelog života.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Pregled očiju'],
                ['naziv' => 'Pregled vida'],
                ['naziv' => 'Dijagnostika očnih bolesti'],
                ['naziv' => 'Savjetovanje o korekciji vida']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada treba uraditi pregled očiju?', 'odgovor' => 'Ako primijetite slabiji ili zamagljen vid, glavobolje, bol ili crvenilo očiju, ili preventivno jednom godišnje.'],
                ['pitanje' => 'Da li problemi sa vidom uvijek znače bolest oka?', 'odgovor' => 'Ne. Nekada je riječ samo o potrebi za korekcijom vida, ali pregled je neophodan da se isključe ozbiljnija stanja.'],
                ['pitanje' => 'Da li djeca trebaju redovne preglede vida?', 'odgovor' => 'Da. Rano otkrivanje problema sa vidom ključno je za pravilan razvoj djeteta.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Oftalmologija', 'slug' => 'oftalmologija', 'opis' => 'Medicinska specijalnost koja se bavi bolestima oka i očnih struktura.', 'meta_title' => 'Oftalmologija pregledi i bolesti oka | WizMedik', 'meta_description' => 'Oftalmološki pregledi i liječenje bolesti oka kod djece i odraslih.', 'meta_keywords' => 'oftalmologija, oftalmolog, bolesti oka', 'kljucne_rijeci' => ['oftalmolog', 'pregled očiju', 'bol u oku', 'crvenilo oka', 'glaukom', 'katarakta', 'upala oka'], 'uvodni_tekst' => 'Oftalmologija je medicinska grana koja se bavi dijagnostikom i liječenjem bolesti oka.', 'detaljan_opis' => 'Oftalmolog dijagnostikuje i liječi stanja kao što su upale oka, glaukom, katarakta, bolesti mrežnjače i povrede oka. Takođe prati hronične očne bolesti i procjenjuje potrebu za operativnim liječenjem.', 'zakljucni_tekst' => 'Pregled kod oftalmologa je neophodan kod svakog bola, naglog pogoršanja vida ili sumnje na očnu bolest.', 'usluge' => [['naziv' => 'Oftalmološki pregled'], ['naziv' => 'Mjerenje očnog pritiska'], ['naziv' => 'Pregled očnog dna']], 'faq' => [['pitanje' => 'Da li oftalmolog liječi i slab vid?', 'odgovor' => 'Da, ali prvenstveno se bavi bolestima oka. Korekciju vida često radi optometrista.'], ['pitanje' => 'Da li je crvenilo oka uvijek bezazleno?', 'odgovor' => 'Ne. Može ukazivati na upalu ili drugo očni problem koji zahtijeva pregled.']]],
            ['naziv' => 'Optometrija', 'slug' => 'optometrija', 'opis' => 'Procjena vida i korekcija refraktivnih grešaka.', 'meta_title' => 'Optometrija pregled vida i korekcija | WizMedik', 'meta_description' => 'Mjerenje vida i korekcija kratkovidosti, dalekovidosti i astigmatizma.', 'meta_keywords' => 'optometrija, pregled vida, dioptrija', 'kljucne_rijeci' => ['optometrista', 'pregled vida', 'dioptrija', 'slab vid', 'naočale', 'sočiva'], 'uvodni_tekst' => 'Optometrija se bavi mjerenjem vida i određivanjem odgovarajuće korekcije.', 'detaljan_opis' => 'Optometrista procjenjuje kvalitet vida, otkriva refraktivne greške i preporučuje naočale ili kontaktna sočiva. Ne bavi se liječenjem bolesti oka, već funkcionalnim problemima vida.', 'zakljucni_tekst' => 'Redovni pregledi vida pomažu u očuvanju jasnog vida i smanjenju naprezanja očiju.', 'usluge' => [['naziv' => 'Pregled vida'], ['naziv' => 'Određivanje dioptrije'], ['naziv' => 'Savjetovanje o korekciji vida']], 'faq' => [['pitanje' => 'Da li mogu ići kod optometriste bez pregleda kod oftalmologa?', 'odgovor' => 'Da, ako nemate simptome bolesti oka i trebate samo korekciju vida.'], ['pitanje' => 'Da li optometrista može otkriti bolest oka?', 'odgovor' => 'Može posumnjati i uputiti oftalmologu, ali ne liječi očne bolesti.']]],
            ['naziv' => 'Dječija oftalmologija', 'slug' => 'djecija-oftalmologija', 'opis' => 'Pregledi i liječenje očnih problema kod djece.', 'meta_title' => 'Dječija oftalmologija pregledi vida kod djece | WizMedik', 'meta_description' => 'Oftalmološki pregledi i liječenje problema sa vidom kod djece.', 'meta_keywords' => 'dječija oftalmologija, vid kod djece', 'kljucne_rijeci' => ['pregled vida kod djece', 'dječiji oftalmolog', 'slab vid kod djece', 'razrokost'], 'uvodni_tekst' => 'Dječija oftalmologija se bavi specifičnim problemima vida kod djece.', 'detaljan_opis' => 'Rano otkrivanje problema sa vidom kod djece ključno je za pravilan razvoj i školski uspjeh.', 'zakljucni_tekst' => 'Redovni pregledi vida omogućavaju pravovremenu korekciju.', 'usluge' => [['naziv' => 'Pregled vida kod djece'], ['naziv' => 'Dijagnostika razrokosti']], 'faq' => [['pitanje' => 'Kada dijete treba prvi pregled vida?', 'odgovor' => 'Preporučuje se u ranom djetinjstvu i prije polaska u školu.']]],
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

    private function seedMentalnoZdravlje()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Mentalno zdravlje',
            'slug' => 'mentalno-zdravlje',
            'opis' => 'Prevencija, dijagnostika i liječenje psihičkih i emocionalnih poremećaja kod djece i odraslih.',
            'meta_title' => 'Mentalno zdravlje pregledi i podrška | WizMedik',
            'meta_description' => 'Psihijatrija, psihologija i psihoterapija. Stručna pomoć za mentalno i emocionalno zdravlje.',
            'meta_keywords' => 'mentalno zdravlje, psihijatrija, psihologija, psihoterapija',
            'kljucne_rijeci' => json_encode(['mentalno zdravlje', 'psihijatar', 'psiholog', 'psihoterapeut', 'anksioznost', 'depresija', 'stres', 'panični napadi', 'problemi sa spavanjem']),
            'uvodni_tekst' => 'Mentalno zdravlje je sastavni dio opšteg zdravlja i utiče na način razmišljanja, osjećanja i ponašanja. Problemi mentalnog zdravlja mogu se javiti u bilo kojoj životnoj dobi i nisu znak slabosti.',
            'detaljan_opis' => 'Oblast mentalnog zdravlja obuhvata dijagnostiku i liječenje psihičkih poremećaja, kao i pružanje stručne psihološke i psihoterapijske podrške. Najčešći razlozi za obraćanje stručnjacima su anksioznost, depresija, poremećaji raspoloženja, stres, problemi sa spavanjem i teškoće u svakodnevnom funkcionisanju. U ovoj oblasti djeluju psihijatri kao doktori medicine, psiholozi kao stručnjaci za psihološku procjenu i savjetovanje, te psihoterapeuti koji sprovode terapijske metode liječenja kroz razgovor.',
            'zakljucni_tekst' => 'Pravovremeno traženje stručne pomoći može značajno poboljšati kvalitet života i spriječiti pogoršanje problema mentalnog zdravlja.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Psihijatrijski pregled'],
                ['naziv' => 'Psihološko savjetovanje'],
                ['naziv' => 'Psihoterapija'],
                ['naziv' => 'Procjena mentalnog stanja']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba obratiti stručnjaku za mentalno zdravlje?', 'odgovor' => 'Kada osjećate dugotrajnu tugu, strah, napetost, gubitak interesa, probleme sa snom ili teškoće u svakodnevnom funkcionisanju.'],
                ['pitanje' => 'Da li su problemi mentalnog zdravlja česti?', 'odgovor' => 'Da. Mentalni poremećaji su vrlo česti i mogu se javiti kod svakoga.'],
                ['pitanje' => 'Da li je razgovor sa stručnjakom povjerljiv?', 'odgovor' => 'Da. Povjerljivost je osnovni princip rada u oblasti mentalnog zdravlja.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Psihijatrija', 'slug' => 'psihijatrija', 'opis' => 'Medicinska specijalnost koja se bavi dijagnostikom i liječenjem psihičkih poremećaja.', 'meta_title' => 'Psihijatrija pregledi i liječenje | WizMedik', 'meta_description' => 'Psihijatrijski pregledi, dijagnostika i terapija psihičkih poremećaja.', 'meta_keywords' => 'psihijatrija, psihijatar, psihički poremećaji', 'kljucne_rijeci' => ['psihijatar', 'depresija', 'anksioznost', 'panični napadi', 'nesanica', 'poremećaji raspoloženja'], 'uvodni_tekst' => 'Psihijatrija je grana medicine koja se bavi mentalnim i emocionalnim poremećajima.', 'detaljan_opis' => 'Psihijatar je doktor medicine koji postavlja dijagnozu psihičkih poremećaja i po potrebi propisuje terapiju. Liječi stanja kao što su depresija, anksiozni poremećaji, bipolarni poremećaj, psihoze i poremećaji spavanja.', 'zakljucni_tekst' => 'Psihijatrijski pregled je važan korak ka stabilizaciji i poboljšanju mentalnog zdravlja.', 'usluge' => [['naziv' => 'Psihijatrijski pregled'], ['naziv' => 'Propisivanje terapije'], ['naziv' => 'Praćenje stanja']], 'faq' => [['pitanje' => 'Da li psihijatar uvijek propisuje lijekove?', 'odgovor' => 'Ne. Terapija zavisi od dijagnoze i može uključivati i druge oblike liječenja.']]],
            ['naziv' => 'Psihologija', 'slug' => 'psihologija', 'opis' => 'Psihološka procjena, savjetovanje i podrška bez primjene lijekova.', 'meta_title' => 'Psihologija psihološko savjetovanje | WizMedik', 'meta_description' => 'Psihološka procjena, testiranja i savjetovanje za djecu i odrasle.', 'meta_keywords' => 'psihologija, psiholog, psihološko savjetovanje', 'kljucne_rijeci' => ['psiholog', 'razgovor sa psihologom', 'stres', 'problemi u odnosima', 'emocionalne poteškoće'], 'uvodni_tekst' => 'Psihologija se bavi razumijevanjem ponašanja, emocija i načina razmišljanja.', 'detaljan_opis' => 'Psiholog pomaže osobama koje prolaze kroz stresne situacije, emocionalne poteškoće ili žele bolje razumjeti sebe i svoje reakcije. Ne propisuje lijekove.', 'zakljucni_tekst' => 'Psihološka podrška pomaže u jačanju mentalne otpornosti.', 'usluge' => [['naziv' => 'Psihološko savjetovanje'], ['naziv' => 'Psihološka procjena'], ['naziv' => 'Testiranja']], 'faq' => [['pitanje' => 'Da li je psiholog isto što i psihijatar?', 'odgovor' => 'Ne. Psiholog ne propisuje lijekove, dok psihijatar ima medicinsku specijalizaciju.']]],
            ['naziv' => 'Psihoterapija', 'slug' => 'psihoterapija', 'opis' => 'Terapijski rad kroz strukturisan razgovor sa licenciranim terapeutom.', 'meta_title' => 'Psihoterapija terapija razgovorom | WizMedik', 'meta_description' => 'Psihoterapija kao metoda liječenja emocionalnih i psihičkih poteškoća.', 'meta_keywords' => 'psihoterapija, psihoterapeut, terapija razgovorom', 'kljucne_rijeci' => ['psihoterapeut', 'psihoterapija', 'razgovorna terapija', 'anksioznost', 'depresija'], 'uvodni_tekst' => 'Psihoterapija pomaže u razumijevanju i promjeni obrazaca razmišljanja i ponašanja.', 'detaljan_opis' => 'Psihoterapeut koristi različite terapijske pravce kako bi pomogao osobi da se izbori sa emocionalnim i psihičkim poteškoćama.', 'zakljucni_tekst' => 'Psihoterapija je proces koji vodi ka dugoročnim promjenama i boljem kvalitetu života.', 'usluge' => [['naziv' => 'Individualna psihoterapija'], ['naziv' => 'Partnerska i porodična terapija']], 'faq' => [['pitanje' => 'Koliko traje psihoterapija?', 'odgovor' => 'Trajanje zavisi od problema i ciljeva terapije.']]],
            ['naziv' => 'Savjetovanje', 'slug' => 'savjetovanje', 'opis' => 'Stručno savjetovanje za životne i emocionalne poteškoće.', 'meta_title' => 'Psihološko savjetovanje podrška | WizMedik', 'meta_description' => 'Savjetovanje za stres, životne promjene i emocionalne izazove.', 'meta_keywords' => 'savjetovanje, psihološko savjetovanje', 'kljucne_rijeci' => ['savjetovanje', 'stres', 'problemi u vezi', 'životne krize'], 'uvodni_tekst' => 'Savjetovanje pruža podršku u rješavanju konkretnih životnih problema.', 'detaljan_opis' => 'Namijenjeno je osobama koje prolaze kroz stresne periode, ali nemaju dijagnostikovan psihički poremećaj.', 'zakljucni_tekst' => 'Razgovor sa stručnom osobom često je prvi korak ka rješenju problema.', 'usluge' => [['naziv' => 'Individualno savjetovanje']], 'faq' => [['pitanje' => 'Da li je savjetovanje isto što i psihoterapija?', 'odgovor' => 'Ne. Savjetovanje je kraće i fokusirano na konkretan problem.']]],
            ['naziv' => 'Dječija i adolescentna psihijatrija', 'slug' => 'djecija-i-adolescentna-psihijatrija', 'opis' => 'Mentalno zdravlje djece i adolescenata.', 'meta_title' => 'Dječija psihijatrija mentalno zdravlje djece | WizMedik', 'meta_description' => 'Psihijatrijska pomoć za djecu i adolescente.', 'meta_keywords' => 'dječija psihijatrija, mentalno zdravlje djece', 'kljucne_rijeci' => ['dječiji psihijatar', 'problemi u ponašanju', 'emocionalne smetnje kod djece'], 'uvodni_tekst' => 'Dječija psihijatrija se bavi mentalnim zdravljem djece i adolescenata.', 'detaljan_opis' => 'Dječiji psihijatar dijagnostikuje i liječi emocionalne i ponašajne probleme kod djece.', 'zakljucni_tekst' => 'Rana intervencija poboljšava ishode liječenja.', 'usluge' => [['naziv' => 'Pregled dječijeg psihijatra'], ['naziv' => 'Terapija']], 'faq' => [['pitanje' => 'Kada dijete treba pregled kod dječijeg psihijatra?', 'odgovor' => 'Ako ima dugotrajne emocionalne ili ponašajne poteškoće koje utiču na svakodnevni život.']]],
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


    // Remaining methods to be implemented:
    // - seedStomatologija()
    // - seedHirurgija()
    // - seedDijagnostika()
    // - seedRehabilitacija()
    // - seedUrologijaMuskoZdravlje()
    // - seedEndokrinologijaMetabolizam()
    // - seedGastroenterologija()
    // - seedPulmologija()
    // - seedInfektologija()
    // - seedOnkologija()
    // - seedAlternativnaMedicina()
    // - seedHitnaUrgentna()

    private function seedStomatologija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Stomatologija',
            'slug' => 'stomatologija',
            'opis' => 'Prevencija, dijagnostika i liječenje bolesti zuba, desni i usne duplje.',
            'meta_title' => 'Stomatologija pregledi i liječenje zuba | WizMedik',
            'meta_description' => 'Stomatološki pregledi, liječenje zuba, desni i oralnih oboljenja. Sve stomatološke specijalnosti na jednom mjestu.',
            'meta_keywords' => 'stomatologija, stomatolog, zubi, desni, oralno zdravlje',
            'kljucne_rijeci' => json_encode(['stomatolog', 'zubi', 'bol u zubu', 'karijes', 'desni', 'krvarenje desni', 'pregled zuba', 'oralno zdravlje']),
            'uvodni_tekst' => 'Stomatologija se bavi očuvanjem zdravlja zuba, desni i cijele usne duplje. Oralno zdravlje ima direktan uticaj na opšte zdravstveno stanje i kvalitet života.',
            'detaljan_opis' => 'Oblast stomatologije obuhvata preventivne preglede, liječenje karijesa, bolesti desni, korekciju nepravilnosti zuba, nadoknadu izgubljenih zuba i hirurške zahvate u usnoj duplji. Redovni stomatološki pregledi omogućavaju rano otkrivanje problema i sprječavanje ozbiljnih komplikacija. U ovoj oblasti djeluju doktori stomatologije i specijalisti različitih grana stomatologije koji se bave funkcionalnim i estetskim zdravljem usne duplje.',
            'zakljucni_tekst' => 'Redovna briga o oralnom zdravlju je ključna za dugoročno očuvanje zuba i desni.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Stomatološki pregled'],
                ['naziv' => 'Liječenje karijesa'],
                ['naziv' => 'Čišćenje zuba'],
                ['naziv' => 'Savjetovanje o oralnoj higijeni']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Koliko često treba ići stomatologu?', 'odgovor' => 'Najmanje dva puta godišnje ili češće po preporuci stomatologa.'],
                ['pitanje' => 'Da li je bol u zubu uvijek znak karijesa?', 'odgovor' => 'Ne uvijek, ali zahtijeva pregled kako bi se utvrdio tačan uzrok.'],
                ['pitanje' => 'Da li su bolesti desni ozbiljne?', 'odgovor' => 'Da. Neliječene bolesti desni mogu dovesti do gubitka zuba.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Opšta stomatologija', 'slug' => 'opsta-stomatologija', 'opis' => 'Osnovna stomatološka zaštita i liječenje zuba i desni.', 'meta_title' => 'Opšta stomatologija pregledi i liječenje | WizMedik', 'meta_description' => 'Pregledi, liječenje karijesa i osnovne stomatološke intervencije.', 'meta_keywords' => 'opšta stomatologija, stomatolog', 'kljucne_rijeci' => ['stomatolog', 'karijes', 'bol u zubu', 'plombiranje', 'pregled zuba'], 'uvodni_tekst' => 'Opšta stomatologija je prvi korak u očuvanju oralnog zdravlja.', 'detaljan_opis' => 'Doktor opšte stomatologije obavlja preglede, liječi karijes, sanira zube i savjetuje pacijente o pravilnoj oralnoj higijeni.', 'zakljucni_tekst' => 'Redovni pregledi kod stomatologa sprječavaju ozbiljne dentalne probleme.', 'usluge' => [['naziv' => 'Pregled zuba'], ['naziv' => 'Plombiranje'], ['naziv' => 'Čišćenje kamenca']], 'faq' => [['pitanje' => 'Da li se karijes uvijek vidi golim okom?', 'odgovor' => 'Ne. Neki oblici karijesa otkrivaju se tek na pregledu ili snimku.']]],
            ['naziv' => 'Oralna hirurgija', 'slug' => 'oralna-hirurgija', 'opis' => 'Hirurško liječenje bolesti i stanja u usnoj duplji.', 'meta_title' => 'Oralna hirurgija zahvati u usnoj duplji | WizMedik', 'meta_description' => 'Vađenje zuba, hirurški zahvati i liječenje komplikacija u usnoj duplji.', 'meta_keywords' => 'oralna hirurgija, oralni hirurg', 'kljucne_rijeci' => ['vađenje zuba', 'umnjaci', 'oralni hirurg', 'hirurški zahvati'], 'uvodni_tekst' => 'Oralna hirurgija se primjenjuje kada konzervativno liječenje nije dovoljno.', 'detaljan_opis' => 'Oralni hirurg izvodi vađenja zuba, posebno umnjaka, liječi ciste, upale i druge hirurške probleme u usnoj duplji.', 'zakljucni_tekst' => 'Pravilno izveden hirurški zahvat omogućava brz oporavak i sprečava komplikacije.', 'usluge' => [['naziv' => 'Hirurško vađenje zuba'], ['naziv' => 'Liječenje komplikacija']], 'faq' => [['pitanje' => 'Da li je vađenje umnjaka uvijek komplikovano?', 'odgovor' => 'Ne uvijek, ali često zahtijeva hirurški pristup.']]],
            ['naziv' => 'Ortodoncija', 'slug' => 'ortodoncija', 'opis' => 'Ispravljanje nepravilnog položaja zuba i vilica.', 'meta_title' => 'Ortodoncija ispravljanje zuba | WizMedik', 'meta_description' => 'Ortodoncija za djecu i odrasle. Ispravljanje zuba i vilica.', 'meta_keywords' => 'ortodoncija, ortodont', 'kljucne_rijeci' => ['ortodont', 'krivi zubi', 'proteza za zube'], 'uvodni_tekst' => 'Ortodoncija poboljšava funkciju i izgled zuba.', 'detaljan_opis' => 'Ortodoncija se bavi ispravljanjem nepravilnosti zuba i vilica kod djece i odraslih.', 'zakljucni_tekst' => 'Pravilan položaj zuba olakšava higijenu i poboljšava zdravlje.', 'usluge' => [['naziv' => 'Ortodonski pregled'], ['naziv' => 'Terapija fiksnom ili mobilnom protezom']], 'faq' => [['pitanje' => 'Da li su proteze samo za djecu?', 'odgovor' => 'Ne. Ortodonska terapija je moguća i kod odraslih.']]],
            ['naziv' => 'Parodontologija', 'slug' => 'parodontologija', 'opis' => 'Bolesti desni i potpornog aparata zuba.', 'meta_title' => 'Parodontologija bolesti desni | WizMedik', 'meta_description' => 'Liječenje krvarenja desni, parodontopatije i gubitka zuba.', 'meta_keywords' => 'parodontologija, parodontolog, desni', 'kljucne_rijeci' => ['krvarenje desni', 'parodontopatija', 'povlačenje desni'], 'uvodni_tekst' => 'Parodontologija se bavi zdravljem desni i potpornog aparata zuba.', 'detaljan_opis' => 'Parodontolog liječi upale desni i sprečava gubitak zuba uzrokovan parodontopatijom.', 'zakljucni_tekst' => 'Zdrave desni su osnova stabilnih zuba.', 'usluge' => [['naziv' => 'Liječenje desni'], ['naziv' => 'Parodontološka terapija']], 'faq' => [['pitanje' => 'Da li krvarenje desni znači ozbiljan problem?', 'odgovor' => 'Često da i zahtijeva pregled.']]],
            ['naziv' => 'Endodoncija', 'slug' => 'endodoncija', 'opis' => 'Liječenje korijena zuba.', 'meta_title' => 'Endodoncija liječenje korijena zuba | WizMedik', 'meta_description' => 'Endodontsko liječenje zuba i spašavanje zuba od vađenja.', 'meta_keywords' => 'endodoncija, liječenje zuba', 'kljucne_rijeci' => ['liječenje zuba', 'korijen zuba', 'bol u zubu'], 'uvodni_tekst' => 'Endodoncija omogućava očuvanje zuba.', 'detaljan_opis' => 'Endodont liječi upalu i infekciju zubne pulpe i kanala korijena.', 'zakljucni_tekst' => 'Pravovremeno liječenje spašava zub od vađenja.', 'usluge' => [['naziv' => 'Endodontski tretman']], 'faq' => [['pitanje' => 'Da li je liječenje kanala bolno?', 'odgovor' => 'Ne. Izvodi se uz lokalnu anesteziju.']]],
            ['naziv' => 'Protetika', 'slug' => 'protetika', 'opis' => 'Nadoknada izgubljenih zuba.', 'meta_title' => 'Stomatološka protetika nadoknada zuba | WizMedik', 'meta_description' => 'Krune, mostovi i proteze za funkcionalnu i estetsku obnovu zuba.', 'meta_keywords' => 'protetika, nadoknada zuba', 'kljucne_rijeci' => ['krune', 'mostovi', 'proteze', 'nedostatak zuba'], 'uvodni_tekst' => 'Protetika vraća funkciju i izgled zuba.', 'detaljan_opis' => 'Protetika se bavi izradom fiksnih i mobilnih nadoknada zuba.', 'zakljucni_tekst' => 'Nadoknada zuba poboljšava kvalitet života.', 'usluge' => [['naziv' => 'Izrada kruna'], ['naziv' => 'Mostovi'], ['naziv' => 'Proteze']], 'faq' => [['pitanje' => 'Da li se izgubljeni zubi moraju nadoknaditi?', 'odgovor' => 'Da. Gubitak zuba utiče na zagriz i zdravlje drugih zuba.']]],
            ['naziv' => 'Dječija stomatologija', 'slug' => 'djecija-stomatologija-stomatoloska', 'opis' => 'Stomatološka briga o djeci.', 'meta_title' => 'Dječija stomatologija zubi kod djece | WizMedik', 'meta_description' => 'Stomatološki pregledi i liječenje zuba kod djece.', 'meta_keywords' => 'dječija stomatologija, zubi kod djece', 'kljucne_rijeci' => ['dječiji stomatolog', 'mliječni zubi', 'zubi kod djece'], 'uvodni_tekst' => 'Dječija stomatologija stvara zdrave navike od najranijeg uzrasta.', 'detaljan_opis' => 'Dječiji stomatolog se bavi specifičnim potrebama djece i stvara pozitivan odnos prema oralnoj higijeni.', 'zakljucni_tekst' => 'Zdrav osmijeh počinje u djetinjstvu.', 'usluge' => [['naziv' => 'Pregled zuba kod djece'], ['naziv' => 'Preventivne mjere']], 'faq' => [['pitanje' => 'Zašto su mliječni zubi važni?', 'odgovor' => 'Zato što čuvaju prostor za stalne zube i utiču na razvoj vilice.']]],
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

    private function seedHirurgija()
    {
        // To be implemented
    }

    private function seedDijagnostika()
    {
        // To be implemented
    }

    private function seedRehabilitacija()
    {
        // To be implemented
    }

    private function seedUrologijaMuskoZdravlje()
    {
        // To be implemented
    }

    private function seedEndokrinologijaMetabolizam()
    {
        // To be implemented
    }

    private function seedGastroenterologija()
    {
        // To be implemented
    }

    private function seedPulmologija()
    {
        // To be implemented
    }

    private function seedInfektologija()
    {
        // To be implemented
    }

    private function seedOnkologija()
    {
        // To be implemented
    }

    private function seedAlternativnaMedicina()
    {
        // To be implemented
    }

    private function seedHitnaUrgentna()
    {
        // To be implemented
    }
}
