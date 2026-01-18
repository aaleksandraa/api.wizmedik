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
            ['naziv' => 'Proktologija', 'slug' => 'proktologija-gastro', 'opis' => 'Bolesti završnog dijela crijeva i analne regije.', 'meta_title' => 'Proktologija hemoroidi i analne bolesti | WizMedik', 'meta_description' => 'Dijagnostika i liječenje hemoroida, fisura i drugih proktoloških problema.', 'meta_keywords' => 'proktologija, hemoroidi', 'kljucne_rijeci' => ['hemoroidi', 'analne fisure', 'krvarenje iz anusa', 'bol u anusu'], 'uvodni_tekst' => 'Proktologija se bavi bolestima završnog dijela probavnog trakta.', 'detaljan_opis' => 'Proktolog liječi hemoroide, analne fisure, fistule i druge bolesti analne regije.', 'zakljucni_tekst' => 'Rano liječenje sprečava komplikacije.', 'usluge' => [['naziv' => 'Proktološki pregled']], 'faq' => [['pitanje' => 'Da li hemoroidi prolaze sami?', 'odgovor' => 'Ponekad da, ali često zahtijevaju liječenje.']]],
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

    private function seedPulmologija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Pulmologija',
            'slug' => 'pulmologija',
            'opis' => 'Dijagnostika i liječenje bolesti pluća i disajnih puteva.',
            'meta_title' => 'Pulmologija bolesti pluća i disanja | WizMedik',
            'meta_description' => 'Pulmološki pregledi, spirometrija i liječenje astme, HOBP i drugih bolesti pluća.',
            'meta_keywords' => 'pulmologija, pulmolog, pluća, astma',
            'kljucne_rijeci' => json_encode(['pulmolog', 'pluća', 'astma', 'otežano disanje', 'kašalj', 'HOBP', 'alergija']),
            'uvodni_tekst' => 'Pulmologija se bavi zdravljem pluća i disajnih puteva, što je ključno za pravilan unos kiseonika i funkcionisanje organizma.',
            'detaljan_opis' => 'Oblast pulmologije obuhvata dijagnostiku i liječenje astme, hronične opstruktivne bolesti pluća (HOBP), upala pluća, alergijskih bolesti disajnih puteva i drugih stanja koja utiču na disanje. Pulmolog koristi različite dijagnostičke metode, uključujući spirometriju i radiološke preglede, kako bi precizno utvrdio uzrok tegoba.',
            'zakljucni_tekst' => 'Pravovremena pulmološka dijagnostika omogućava kontrolu simptoma i sprečavanje pogoršanja bolesti pluća.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Pulmološki pregled'],
                ['naziv' => 'Spirometrija'],
                ['naziv' => 'Alergološko testiranje'],
                ['naziv' => 'Praćenje hroničnih bolesti pluća']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti pulmologu?', 'odgovor' => 'Ako imate otežano disanje, hronični kašalj, zviždanje pri disanju ili česte upale pluća.'],
                ['pitanje' => 'Da li je astma izlječiva?', 'odgovor' => 'Astma se ne može potpuno izliječiti, ali se može uspješno kontrolisati terapijom.'],
                ['pitanje' => 'Šta je spirometrija?', 'odgovor' => 'Test koji mjeri kapacitet pluća i protok vazduha kroz disajne puteve.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Pulmologija', 'slug' => 'pulmologija-opsta', 'opis' => 'Dijagnostika i liječenje bolesti pluća.', 'meta_title' => 'Pulmologija bolesti pluća | WizMedik', 'meta_description' => 'Pulmološki pregledi i liječenje bolesti disajnih puteva.', 'meta_keywords' => 'pulmologija, pulmolog, pluća', 'kljucne_rijeci' => ['pulmolog', 'pluća', 'kašalj', 'otežano disanje'], 'uvodni_tekst' => 'Pulmologija se bavi zdravljem pluća i disajnih puteva.', 'detaljan_opis' => 'Pulmolog dijagnostikuje i liječi astmu, HOBP, upale pluća i druge bolesti koje utiču na disanje.', 'zakljucni_tekst' => 'Pravovremeno liječenje poboljšava kvalitet života.', 'usluge' => [['naziv' => 'Pulmološki pregled'], ['naziv' => 'Spirometrija']], 'faq' => [['pitanje' => 'Da li pušenje utiče na pluća?', 'odgovor' => 'Da. Pušenje je glavni uzrok HOBP i drugih bolesti pluća.']]],
            ['naziv' => 'Alergologija', 'slug' => 'alergologija', 'opis' => 'Dijagnostika i liječenje alergijskih bolesti.', 'meta_title' => 'Alergologija alergije i testiranje | WizMedik', 'meta_description' => 'Alergološki pregledi, testiranje i liječenje alergija.', 'meta_keywords' => 'alergologija, alergolog, alergije', 'kljucne_rijeci' => ['alergolog', 'alergije', 'alergijsko testiranje', 'kijanje', 'svrab'], 'uvodni_tekst' => 'Alergologija se bavi dijagnostikom i liječenjem alergijskih reakcija.', 'detaljan_opis' => 'Alergolog testira i liječi alergije na polen, hranu, lijekove, insekte i druge alergene koji izazivaju simptome.', 'zakljucni_tekst' => 'Identifikacija alergena omogućava ciljanu terapiju.', 'usluge' => [['naziv' => 'Alergološki pregled'], ['naziv' => 'Kožno testiranje']], 'faq' => [['pitanje' => 'Kako se dijagnostikuju alergije?', 'odgovor' => 'Kožnim testovima ili krvnim analizama.']]],
            ['naziv' => 'Astma i hronične bolesti pluća', 'slug' => 'astma-i-hronicne-bolesti-pluca', 'opis' => 'Liječenje i praćenje astme i HOBP.', 'meta_title' => 'Astma i HOBP liječenje | WizMedik', 'meta_description' => 'Dijagnostika i liječenje astme i hronične opstruktivne bolesti pluća.', 'meta_keywords' => 'astma, HOBP, hronične bolesti pluća', 'kljucne_rijeci' => ['astma', 'HOBP', 'otežano disanje', 'inhalatori'], 'uvodni_tekst' => 'Astma i HOBP su hronične bolesti koje zahtijevaju stalno praćenje.', 'detaljan_opis' => 'Ove bolesti se kontrolišu terapijom koja smanjuje upalu i olakšava disanje, omogućavajući normalan život.', 'zakljucni_tekst' => 'Pravilna terapija omogućava kontrolu simptoma.', 'usluge' => [['naziv' => 'Praćenje astme'], ['naziv' => 'Edukacija o terapiji']], 'faq' => [['pitanje' => 'Da li astma može biti opasna?', 'odgovor' => 'Može, ako se ne liječi pravilno. Teški napadi astme zahtijevaju hitnu pomoć.']]],
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

    private function seedInfektologija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Infektologija',
            'slug' => 'infektologija',
            'opis' => 'Dijagnostika i liječenje zaraznih bolesti uzrokovanih bakterijama, virusima i parazitima.',
            'meta_title' => 'Infektologija zarazne bolesti i liječenje | WizMedik',
            'meta_description' => 'Infektološki pregledi, dijagnostika i liječenje zaraznih bolesti i infekcija.',
            'meta_keywords' => 'infektologija, infektolog, zarazne bolesti, infekcije',
            'kljucne_rijeci' => json_encode(['infektolog', 'zarazne bolesti', 'infekcije', 'temperatura', 'virus', 'bakterija']),
            'uvodni_tekst' => 'Infektologija se bavi dijagnostikom, liječenjem i prevencijom zaraznih bolesti koje mogu biti uzrokovane bakterijama, virusima, gljivicama ili parazitima.',
            'detaljan_opis' => 'Oblast infektologije obuhvata širok spektar bolesti, od čestih respiratornih infekcija do ozbiljnih sistemskih infekcija. Infektolog se bavi i putnom medicinom, savjetovanjem prije putovanja i liječenjem tropskih bolesti. Takođe prati pacijente sa hroničnim infekcijama koje zahtijevaju dugotrajno liječenje.',
            'zakljucni_tekst' => 'Pravovremena infektološka dijagnostika i terapija ključni su za sprečavanje širenja infekcije i oporavak pacijenta.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Infektološki pregled'],
                ['naziv' => 'Dijagnostika infekcija'],
                ['naziv' => 'Savjetovanje prije putovanja'],
                ['naziv' => 'Praćenje hroničnih infekcija']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti infektologu?', 'odgovor' => 'Ako imate dugotrajnu temperaturu, česte infekcije ili sumnju na zaraznu bolest.'],
                ['pitanje' => 'Da li su sve infekcije zarazne?', 'odgovor' => 'Ne. Neke infekcije se ne prenose sa osobe na osobu.'],
                ['pitanje' => 'Šta je putna medicina?', 'odgovor' => 'Savjetovanje i vakcinacija prije putovanja u tropske i egzotične destinacije.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Infektivne bolesti', 'slug' => 'infektivne-bolesti', 'opis' => 'Dijagnostika i liječenje akutnih i hroničnih infekcija.', 'meta_title' => 'Infektivne bolesti dijagnostika i liječenje | WizMedik', 'meta_description' => 'Liječenje bakterijskih, virusnih i drugih infekcija.', 'meta_keywords' => 'infektivne bolesti, infekcije', 'kljucne_rijeci' => ['infekcije', 'temperatura', 'zarazne bolesti', 'antibiotici'], 'uvodni_tekst' => 'Infektivne bolesti mogu biti akutne ili hronične.', 'detaljan_opis' => 'Infektolog dijagnostikuje uzročnika infekcije i određuje odgovarajuću terapiju, često uz mikrobiološke analize.', 'zakljucni_tekst' => 'Pravilna terapija sprečava komplikacije.', 'usluge' => [['naziv' => 'Dijagnostika infekcija'], ['naziv' => 'Antimikrobna terapija']], 'faq' => [['pitanje' => 'Kada su potrebni antibiotici?', 'odgovor' => 'Samo kod bakterijskih infekcija, ne kod virusnih.']]],
            ['naziv' => 'Putna medicina', 'slug' => 'putna-medicina', 'opis' => 'Savjetovanje i zaštita prije putovanja u strane zemlje.', 'meta_title' => 'Putna medicina vakcinacija i savjeti | WizMedik', 'meta_description' => 'Savjetovanje prije putovanja i vakcinacija za tropske bolesti.', 'meta_keywords' => 'putna medicina, vakcinacija, putovanje', 'kljucne_rijeci' => ['putna medicina', 'vakcinacija', 'tropske bolesti', 'putovanje'], 'uvodni_tekst' => 'Putna medicina priprema putnike za sigurno putovanje.', 'detaljan_opis' => 'Obuhvata vakcinaciju, savjete o prevenciji bolesti i lijekove za putnu apoteku.', 'zakljucni_tekst' => 'Priprema prije putovanja smanjuje rizik od bolesti.', 'usluge' => [['naziv' => 'Savjetovanje prije putovanja'], ['naziv' => 'Vakcinacija']], 'faq' => [['pitanje' => 'Koje vakcine su potrebne za putovanje?', 'odgovor' => 'Zavisi od destinacije. Infektolog daje preporuke.']]],
            ['naziv' => 'Hronične infektivne bolesti', 'slug' => 'hronicne-infektivne-bolesti', 'opis' => 'Praćenje i liječenje dugotrajnih infekcija.', 'meta_title' => 'Hronične infekcije praćenje i terapija | WizMedik', 'meta_description' => 'Liječenje hroničnih virusnih i bakterijskih infekcija.', 'meta_keywords' => 'hronične infekcije', 'kljucne_rijeci' => ['hronične infekcije', 'dugotrajno liječenje'], 'uvodni_tekst' => 'Neke infekcije zahtijevaju dugotrajno praćenje i terapiju.', 'detaljan_opis' => 'Hronične infekcije mogu biti virusne, bakterijske ili parazitske i zahtijevaju individualizovan pristup liječenju.', 'zakljucni_tekst' => 'Redovno praćenje omogućava kontrolu bolesti.', 'usluge' => [['naziv' => 'Praćenje hroničnih infekcija']], 'faq' => [['pitanje' => 'Da li se hronične infekcije mogu izliječiti?', 'odgovor' => 'Neke da, druge se mogu kontrolisati terapijom.']]],
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

    private function seedOnkologija()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Onkologija',
            'slug' => 'onkologija',
            'opis' => 'Dijagnostika i liječenje malignih bolesti i tumora.',
            'meta_title' => 'Onkologija dijagnostika i liječenje raka | WizMedik',
            'meta_description' => 'Onkološki pregledi, hemoterapija i liječenje malignih bolesti.',
            'meta_keywords' => 'onkologija, onkolog, rak, tumor, hemoterapija',
            'kljucne_rijeci' => json_encode(['onkolog', 'rak', 'tumor', 'hemoterapija', 'maligna bolest', 'karcinom']),
            'uvodni_tekst' => 'Onkologija se bavi dijagnostikom, liječenjem i praćenjem pacijenata sa malignim bolestima. Rana dijagnostika značajno povećava šanse za uspješno liječenje.',
            'detaljan_opis' => 'Oblast onkologije obuhvata različite vrste tumora i malignih bolesti. Onkolog radi u timu sa hirurgom, radioterapeutom i drugim specijalistima kako bi pacijentu pružio najbolju moguću terapiju. Liječenje može uključivati hemoterapiju, imunoterapiju, ciljanu terapiju i praćenje nakon završenog liječenja.',
            'zakljucni_tekst' => 'Savremena onkologija nudi sve više mogućnosti liječenja, a rana dijagnostika je ključna za uspjeh terapije.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Onkološki pregled'],
                ['naziv' => 'Hemoterapija'],
                ['naziv' => 'Praćenje nakon liječenja'],
                ['naziv' => 'Savjetovanje o terapiji']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada se treba javiti onkologu?', 'odgovor' => 'Nakon dijagnoze malignog tumora ili sumnje na malignu bolest.'],
                ['pitanje' => 'Da li je hemoterapija uvijek potrebna?', 'odgovor' => 'Ne. Terapija zavisi od vrste i stadijuma bolesti.'],
                ['pitanje' => 'Da li se rak može izliječiti?', 'odgovor' => 'Mnogi tipovi raka se mogu uspješno izliječiti, posebno ako se otkriju rano.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Onkologija', 'slug' => 'onkologija-opsta', 'opis' => 'Dijagnostika i liječenje solidnih tumora.', 'meta_title' => 'Onkologija liječenje tumora | WizMedik', 'meta_description' => 'Onkološko liječenje različitih vrsta tumora.', 'meta_keywords' => 'onkologija, onkolog, tumor', 'kljucne_rijeci' => ['onkolog', 'tumor', 'rak', 'hemoterapija'], 'uvodni_tekst' => 'Onkologija se bavi solidnim tumorima različitih organa.', 'detaljan_opis' => 'Onkolog planira i sprovodi sistemsku terapiju tumora, uključujući hemoterapiju, imunoterapiju i ciljanu terapiju.', 'zakljucni_tekst' => 'Individualizovan pristup poboljšava ishode liječenja.', 'usluge' => [['naziv' => 'Onkološki pregled'], ['naziv' => 'Sistemska terapija']], 'faq' => [['pitanje' => 'Koliko traje hemoterapija?', 'odgovor' => 'Zavisi od protokola liječenja, obično nekoliko mjeseci.']]],
            ['naziv' => 'Hematologija', 'slug' => 'hematologija', 'opis' => 'Bolesti krvi i krvotvornih organa.', 'meta_title' => 'Hematologija bolesti krvi | WizMedik', 'meta_description' => 'Dijagnostika i liječenje anemije, leukemije i drugih bolesti krvi.', 'meta_keywords' => 'hematologija, hematolog, bolesti krvi', 'kljucne_rijeci' => ['hematolog', 'anemija', 'leukemija', 'bolesti krvi'], 'uvodni_tekst' => 'Hematologija se bavi bolestima krvi i krvotvornih organa.', 'detaljan_opis' => 'Hematolog dijagnostikuje i liječi anemije, poremećaje koagulacije, leukemije i druge bolesti krvi.', 'zakljucni_tekst' => 'Pravovremena dijagnostika omogućava uspješno liječenje.', 'usluge' => [['naziv' => 'Hematološki pregled'], ['naziv' => 'Analiza krvi']], 'faq' => [['pitanje' => 'Šta je anemija?', 'odgovor' => 'Nedostatak crvenih krvnih zrnaca ili hemoglobina u krvi.']]],
            ['naziv' => 'Hematoonkologija', 'slug' => 'hematoonkologija', 'opis' => 'Maligne bolesti krvi i limfnog sistema.', 'meta_title' => 'Hematoonkologija leukemija i limfomi | WizMedik', 'meta_description' => 'Liječenje leukemija, limfoma i drugih malignih bolesti krvi.', 'meta_keywords' => 'hematoonkologija, leukemija, limfom', 'kljucne_rijeci' => ['leukemija', 'limfom', 'maligne bolesti krvi'], 'uvodni_tekst' => 'Hematoonkologija se bavi malignim bolestima krvi.', 'detaljan_opis' => 'Obuhvata dijagnostiku i liječenje leukemija, limfoma, mijeloma i drugih malignih bolesti krvotvornog sistema.', 'zakljucni_tekst' => 'Savremena terapija značajno poboljšava prognozu.', 'usluge' => [['naziv' => 'Hematoonkološki pregled'], ['naziv' => 'Hemoterapija']], 'faq' => [['pitanje' => 'Da li se leukemija može izliječiti?', 'odgovor' => 'Mnogi tipovi leukemije se mogu uspješno liječiti, posebno kod djece.']]],
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

    private function seedAlternativnaMedicina()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Alternativna i komplementarna medicina',
            'slug' => 'alternativna-i-komplementarna-medicina',
            'opis' => 'Prirodni i holistički pristupi zdravlju koji dopunjuju konvencionalnu medicinu.',
            'meta_title' => 'Alternativna medicina prirodno liječenje | WizMedik',
            'meta_description' => 'Akupunktura, naturopatija, nutricionizam i integrativni pristup zdravlju.',
            'meta_keywords' => 'alternativna medicina, akupunktura, naturopatija, nutricionizam',
            'kljucne_rijeci' => json_encode(['alternativna medicina', 'akupunktura', 'naturopatija', 'nutricionista', 'prirodno liječenje', 'holistički pristup']),
            'uvodni_tekst' => 'Alternativna i komplementarna medicina nude prirodne i holistič ke pristupe zdravlju koji mogu dopuniti konvencionalnu medicinu ili se koristiti samostalno za prevenciju i poboljšanje opšteg blagostanja.',
            'detaljan_opis' => 'Ova oblast obuhvata različite metode liječenja koje se fokusiraju na cjelokupno zdravlje osobe, uključujući fizičko, mentalno i emocionalno blagostanje. Akupunktura, naturopatija, nutricionizam i drugi pristupi mogu pomoći u ublažavanju simptoma, jačanju imuniteta i poboljšanju kvaliteta života.',
            'zakljucni_tekst' => 'Integrativni pristup koji kombinuje konvencionalnu i alternativnu medicinu može pružiti najbolje rezultate za pacijenta.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Akupunktura'],
                ['naziv' => 'Naturopatsko savjetovanje'],
                ['naziv' => 'Nutricionističko savjetovanje'],
                ['naziv' => 'Holistička procjena zdravlja']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Da li alternativna medicina može zamijeniti konvencionalnu?', 'odgovor' => 'Ne uvijek. Najbolji pristup je često kombinacija oba pristupa.'],
                ['pitanje' => 'Da li je akupunktura bolna?', 'odgovor' => 'Ne. Igle su vrlo tanke i većina ljudi osjeća samo blagi pritisak.'],
                ['pitanje' => 'Šta je naturopatija?', 'odgovor' => 'Pristup koji koristi prirodne metode liječenja i fokusira se na uzroke bolesti.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Akupunktura', 'slug' => 'akupunktura', 'opis' => 'Tradicionalna kineska metoda liječenja pomoću igala.', 'meta_title' => 'Akupunktura prirodno ublažavanje bola | WizMedik', 'meta_description' => 'Akupunktura za ublažavanje bola i poboljšanje zdravlja.', 'meta_keywords' => 'akupunktura, tradicionalna kineska medicina', 'kljucne_rijeci' => ['akupunktura', 'igle', 'bol', 'tradicionalna medicina'], 'uvodni_tekst' => 'Akupunktura je drevna metoda koja stimuliše određene tačke na tijelu.', 'detaljan_opis' => 'Koristi se za ublažavanje bola, smanjenje stresa, poboljšanje cirkulacije i liječenje različitih zdravstvenih stanja.', 'zakljucni_tekst' => 'Akupunktura može biti efikasna dopuna konvencionalnom liječenju.', 'usluge' => [['naziv' => 'Akupunkturni tretman']], 'faq' => [['pitanje' => 'Koliko tretmana je potrebno?', 'odgovor' => 'Zavisi od stanja, obično 6-10 tretmana.']]],
            ['naziv' => 'Naturopatija', 'slug' => 'naturopatija', 'opis' => 'Prirodni pristup liječenju koji koristi moć prirode.', 'meta_title' => 'Naturopatija prirodno liječenje | WizMedik', 'meta_description' => 'Naturopatski pristup zdravlju i prirodno liječenje.', 'meta_keywords' => 'naturopatija, prirodno liječenje', 'kljucne_rijeci' => ['naturopatija', 'prirodno liječenje', 'biljni lijekovi'], 'uvodni_tekst' => 'Naturopatija koristi prirodne metode za podsticanje samoiscijelenja.', 'detaljan_opis' => 'Naturopata koristi ishranu, biljne preparate, promjene životnog stila i druge prirodne metode za liječenje i prevenciju bolesti.', 'zakljucni_tekst' => 'Fokus je na uzrocima bolesti, ne samo na simptomima.', 'usluge' => [['naziv' => 'Naturopatsko savjetovanje']], 'faq' => [['pitanje' => 'Da li naturopatija koristi lijekove?', 'odgovor' => 'Koristi prirodne preparate, ne sintetičke lijekove.']]],
            ['naziv' => 'Nutricionizam', 'slug' => 'nutricionizam', 'opis' => 'Savjetovanje o ishrani i zdravom načinu života.', 'meta_title' => 'Nutricionizam zdrava ishrana i dijeta | WizMedik', 'meta_description' => 'Nutricionističko savjetovanje za zdravu ishranu i mršavljenje.', 'meta_keywords' => 'nutricionizam, nutricionista, ishrana', 'kljucne_rijeci' => ['nutricionista', 'ishrana', 'dijeta', 'mršavljenje', 'zdrava hrana'], 'uvodni_tekst' => 'Nutricionizam se bavi ulogom ishrane u zdravlju.', 'detaljan_opis' => 'Nutricionista pomaže u planiranju zdrave ishrane, mršavljenju, sportskoj ishrani i liječenju bolesti putem ishrane.', 'zakljucni_tekst' => 'Pravilna ishrana je temelj dobrog zdravlja.', 'usluge' => [['naziv' => 'Nutricionističko savjetovanje'], ['naziv' => 'Plan ishrane']], 'faq' => [['pitanje' => 'Da li nutricionista propisuje dijete?', 'odgovor' => 'Da, individualizovane planove ishrane prema potrebama.']]],
            ['naziv' => 'Integrativni pristup zdravlju', 'slug' => 'integrativni-pristup-zdravlju', 'opis' => 'Kombinacija konvencionalne i alternativne medicine.', 'meta_title' => 'Integrativna medicina holistički pristup | WizMedik', 'meta_description' => 'Integrativni pristup koji kombinuje najbolje iz oba svijeta.', 'meta_keywords' => 'integrativna medicina, holistički pristup', 'kljucne_rijeci' => ['integrativna medicina', 'holistički pristup', 'cjelovito zdravlje'], 'uvodni_tekst' => 'Integrativna medicina kombinuje različite pristupe liječenju.', 'detaljan_opis' => 'Fokusira se na cjelokupnu osobu i koristi najbolje metode iz konvencionalne i alternativne medicine.', 'zakljucni_tekst' => 'Cilj je optimalno zdravlje i blagostanje.', 'usluge' => [['naziv' => 'Integrativno savjetovanje']], 'faq' => [['pitanje' => 'Šta znači holistički pristup?', 'odgovor' => 'Pristup koji posmatra cijelu osobu, ne samo simptome.']]],
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

    private function seedHitnaUrgentna()
    {
        $id = DB::table('specijalnosti')->insertGetId([
            'naziv' => 'Hitna i urgentna medicina',
            'slug' => 'hitna-i-urgentna-medicina',
            'opis' => 'Brza medicinska pomoć u životno ugrožavajućim i hitnim stanjima.',
            'meta_title' => 'Hitna medicina urgentna pomoć | WizMedik',
            'meta_description' => 'Hitna medicinska pomoć, urgentni centar i liječenje akutnih stanja.',
            'meta_keywords' => 'hitna medicina, urgentna medicina, hitna pomoć',
            'kljucne_rijeci' => json_encode(['hitna pomoć', 'urgentni centar', 'hitna medicina', 'akutno stanje', 'urgencija']),
            'uvodni_tekst' => 'Hitna i urgentna medicina pruža brzu medicinsku pomoć u životno ugrožavajućim i akutnim stanjima koja zahtijevaju trenutnu intervenciju.',
            'detaljan_opis' => 'Oblast hitne medicine obuhvata zbrinjavanje pacijenata sa akutnim bolestima i povredama koje zahtijevaju hitnu medicinsku pažnju. Urgentni centri i odjeljenja hitne medicine rade 24 sata dnevno i opremljeni su za stabilizaciju pacijenata i pružanje neodložne medicinske pomoći. Ljekari hitne medicine obučeni su za brzu procjenu stanja i donošenje odluka u kritičnim situacijama.',
            'zakljucni_tekst' => 'Hitna medicina spašava živote brzom i stručnom intervencijom u kritičnim trenucima.',
            'prikazi_usluge' => true,
            'usluge' => json_encode([
                ['naziv' => 'Hitna medicinska pomoć'],
                ['naziv' => 'Stabilizacija pacijenta'],
                ['naziv' => 'Urgentna dijagnostika'],
                ['naziv' => 'Reanimacija']
            ]),
            'prikazi_faq' => true,
            'faq' => json_encode([
                ['pitanje' => 'Kada pozvati hitnu pomoć?', 'odgovor' => 'Kod bola u grudima, otežanog disanja, gubitka svijesti, jake povrede ili drugih životno ugrožavajućih stanja.'],
                ['pitanje' => 'Šta je urgentni centar?', 'odgovor' => 'Odjeljenje bolnice koje pruža hitnu medicinsku pomoć 24/7.'],
                ['pitanje' => 'Da li urgentni centar prima sve pacijente?', 'odgovor' => 'Da, ali prioritet imaju životno ugroženi pacijenti.']
            ]),
            'aktivan' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        $subcategories = [
            ['naziv' => 'Urgentni centar', 'slug' => 'urgentni-centar', 'opis' => 'Odjeljenje za prijem i zbrinjavanje hitnih stanja.', 'meta_title' => 'Urgentni centar hitna pomoć 24/7 | WizMedik', 'meta_description' => 'Urgentni centar za hitna medicinska stanja i povrede.', 'meta_keywords' => 'urgentni centar, hitna pomoć', 'kljucne_rijeci' => ['urgentni centar', 'hitna pomoć', 'urgencija', '24/7'], 'uvodni_tekst' => 'Urgentni centar je prva linija odbrane u hitnim stanjima.', 'detaljan_opis' => 'Urgentni centar prima pacijente sa akutnim bolestima i povredama, vrši brzu procjenu i pruža neodložnu medicinsku pomoć.', 'zakljucni_tekst' => 'Dostupnost 24/7 osigurava pomoć u svakom trenutku.', 'usluge' => [['naziv' => 'Prijem hitnih pacijenata'], ['naziv' => 'Brza dijagnostika']], 'faq' => [['pitanje' => 'Da li je potreban uput za urgentni centar?', 'odgovor' => 'Ne. Urgentni centar prima sve pacijente bez uputa.']]],
            ['naziv' => 'Hitna medicina', 'slug' => 'hitna-medicina', 'opis' => 'Medicinska specijalnost za zbrinjavanje akutnih stanja.', 'meta_title' => 'Hitna medicina akutna stanja | WizMedik', 'meta_description' => 'Hitna medicinska pomoć i liječenje akutnih stanja.', 'meta_keywords' => 'hitna medicina, akutna stanja', 'kljucne_rijeci' => ['hitna medicina', 'akutno stanje', 'hitna intervencija'], 'uvodni_tekst' => 'Hitna medicina se bavi životno ugrožavajućim stanjima.', 'detaljan_opis' => 'Ljekari hitne medicine obučeni su za brzu procjenu, stabilizaciju i liječenje pacijenata u kritičnim stanjima.', 'zakljucni_tekst' => 'Brza i stručna intervencija spašava živote.', 'usluge' => [['naziv' => 'Hitna intervencija'], ['naziv' => 'Stabilizacija']], 'faq' => [['pitanje' => 'Šta je trijaža?', 'odgovor' => 'Proces procjene hitnosti stanja i određivanja prioriteta liječenja.']]],
            ['naziv' => 'Urgentna interna medicina', 'slug' => 'urgentna-interna-medicina', 'opis' => 'Hitna stanja u internoj medicini.', 'meta_title' => 'Urgentna interna medicina akutna stanja | WizMedik', 'meta_description' => 'Zbrinjavanje akutnih internističkih stanja.', 'meta_keywords' => 'urgentna interna medicina', 'kljucne_rijeci' => ['urgentna interna medicina', 'akutna internistička stanja'], 'uvodni_tekst' => 'Urgentna interna medicina zbri njava akutna internistička stanja.', 'detaljan_opis' => 'Obuhvata hitna stanja kao što su infarkt, moždani udar, akutne infekcije i druge životno ugrožavajuće bolesti unutrašnjih organa.', 'zakljucni_tekst' => 'Brza dijagnostika i terapija ključni su za preživljavanje.', 'usluge' => [['naziv' => 'Urgentna internistička pomoć']], 'faq' => [['pitanje' => 'Koji su znaci infarkta?', 'odgovor' => 'Bol u grudima, otežano disanje, znojenje, mučnina.']]],
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
}
