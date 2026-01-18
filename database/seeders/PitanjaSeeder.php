<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pitanje;
use App\Models\OdgovorNaPitanje;
use App\Models\NotifikacijaPitanja;
use App\Models\Specijalnost;
use App\Models\Doktor;

class PitanjaSeeder extends Seeder
{
    public function run(): void
    {
        $specijalnosti = Specijalnost::all();
        $doktori = Doktor::with('specijalnosti')->get();
        $createdCount = 0;

        $pitanjaData = [
            [
                'naslov' => 'Bol u grudima nakon fizičke aktivnosti',
                'sadrzaj' => 'Imam 35 godina i primjećujem bol u grudima nakon intenzivnije fizičke aktivnosti. Bol traje nekoliko minuta i prolazi nakon odmora. Da li je ovo razlog za zabrinutost? Nemam dijabetes niti povišen pritisak.',
                'ime_korisnika' => 'Amina K.',
                'email_korisnika' => 'amina@example.com',
                'specijalnost' => 'Kardiologija',
                'tagovi' => ['bol u grudima', 'fizička aktivnost', 'srce'],
            ],
            [
                'naslov' => 'Česte glavobolje i vrtoglavica',
                'sadrzaj' => 'Već nekoliko sedmica imam česte glavobolje, posebno ujutro. Ponekad imam i vrtoglavicu. Radim na računaru 8 sati dnevno. Šta može biti uzrok i da li trebam posjetiti ljekara?',
                'ime_korisnika' => 'Emir M.',
                'specijalnost' => 'Neurologija',
                'tagovi' => ['glavobolja', 'vrtoglavica', 'migrena'],
            ],
            [
                'naslov' => 'Dijabetes tip 2 - dijeta i ishrana',
                'sadrzaj' => 'Nedavno mi je dijagnostikovan dijabetes tip 2. Doktor mi je preporučio promjenu ishrane. Koje namirnice su najbolje za kontrolu šećera u krvi? Da li mogu jesti voće?',
                'ime_korisnika' => 'Fatima H.',
                'email_korisnika' => 'fatima@example.com',
                'specijalnost' => 'Interna medicina',
                'tagovi' => ['dijabetes', 'ishrana', 'dijeta', 'šećer u krvi'],
            ],
            [
                'naslov' => 'Bol u leđima koji se širi niz nogu',
                'sadrzaj' => 'Imam 42 godine i već mjesec dana me muči bol u donjem dijelu leđa koji se širi niz desnu nogu. Bol je jači ujutro i nakon dužeg sjedenja. Radim kao vozač. Šta preporučujete?',
                'ime_korisnika' => 'Adnan S.',
                'specijalnost' => 'Ortopedija',
                'tagovi' => ['bol u leđima', 'išijas', 'ortopedija'],
            ],
            [
                'naslov' => 'Alergijska reakcija na koži',
                'sadrzaj' => 'Pojavile su mi se crvene mrlje na rukama i vratu koje svrbe. Nisam mijenjala kozmetiku niti deterdžent. Može li biti alergija na hranu? Kako da utvrdim uzrok?',
                'ime_korisnika' => 'Lejla B.',
                'specijalnost' => 'Dermatologija',
                'tagovi' => ['alergija', 'osip', 'svrab', 'koža'],
            ],
        ];

        foreach ($pitanjaData as $data) {
            $specijalnost = $specijalnosti->firstWhere('naziv', $data['specijalnost']);

            if (!$specijalnost) {
                continue;
            }

            // Check if question already exists
            $existingPitanje = Pitanje::where('naslov', $data['naslov'])->first();
            if ($existingPitanje) {
                continue;
            }

            $pitanje = Pitanje::create([
                'naslov' => $data['naslov'],
                'slug' => \Illuminate\Support\Str::slug($data['naslov']) . '-' . \Illuminate\Support\Str::random(6),
                'sadrzaj' => $data['sadrzaj'],
                'ime_korisnika' => $data['ime_korisnika'],
                'email_korisnika' => $data['email_korisnika'] ?? null,
                'specijalnost_id' => $specijalnost->id,
                'tagovi' => $data['tagovi'],
                'broj_pregleda' => rand(50, 500),
                'je_javno' => true,
            ]);

            $createdCount++;

            // Kreiraj notifikacije za doktore sa tom specijalnosti
            $doktoriSaSpecijalnosti = $doktori->filter(function ($doktor) use ($specijalnost) {
                return $doktor->specijalnosti->contains('id', $specijalnost->id);
            });

            foreach ($doktoriSaSpecijalnosti as $doktor) {
                NotifikacijaPitanja::create([
                    'pitanje_id' => $pitanje->id,
                    'doktor_id' => $doktor->id,
                    'je_procitano' => rand(0, 1) == 1,
                    'procitano_u' => rand(0, 1) == 1 ? now()->subDays(rand(1, 7)) : null,
                ]);

                // 50% šanse da doktor odgovori
                if (rand(0, 1) == 1) {
                    $odgovori = [
                        'Kardiologija' => 'Hvala na pitanju. Bol u grudima nakon fizičke aktivnosti može biti znak angine pektoris. Preporučujem da što prije zakažete pregled kod kardiologa. Potrebno je uraditi EKG, test opterećenja i eventualno ehokardiografiju. Do pregleda izbjegavajte intenzivne fizičke napore.',
                        'Neurologija' => 'Česte glavobolje i vrtoglavica mogu imati više uzroka. S obzirom da radite na računaru, moguće je da je uzrok napetost u vratu i lošija postura. Preporučujem neurološki pregled, mjerenje krvnog pritiska i eventualno MRI glave. U međuvremenu, pravite pauze svakih sat vremena i vježbajte vrat.',
                        'Interna medicina' => 'Kod dijabetesa tip 2 ishrana je ključna. Preporučujem mediteransku dijetu sa puno povrća, integralnih žitarica i masnih riba. Voće možete jesti, ali u umjerenim količinama i najbolje uz obrok. Izbjegavajte sokove i prerađenu hranu. Redovno mjerite šećer i vodite dnevnik ishrane.',
                        'Ortopedija' => 'Simptomi koje opisujete ukazuju na moguću išijalgiču (pritisak na išijadični nerv). Preporučujem ortopedski pregled i MRI lumbalnog dijela kičme. U međuvremenu, izbjegavajte duže sjedenje, koristite ortopedsku podlogu i radite vježbe istezanja. Fizikalna terapija može značajno pomoći.',
                        'Dermatologija' => 'Alergijska reakcija može biti uzrokovana hranom, lijekovima ili kontaktom sa alergenom. Preporučujem dermatološki pregled i alergološko testiranje. U međuvremenu, možete koristiti antihistaminik i hidrokortison kremu. Vodite dnevnik hrane i aktivnosti da identifikujete moguće okidače.',
                    ];

                    $odgovorTekst = $odgovori[$specijalnost->naziv] ?? 'Hvala na pitanju. Preporučujem da zakažete pregled kako bismo detaljnije razmotrili vaš slučaj i postavili tačnu dijagnozu.';

                    OdgovorNaPitanje::create([
                        'pitanje_id' => $pitanje->id,
                        'doktor_id' => $doktor->id,
                        'sadrzaj' => $odgovorTekst,
                        'broj_lajkova' => rand(5, 50),
                        'je_prihvacen' => false,
                    ]);

                    break; // Samo jedan doktor odgovara
                }
            }
        }

        if ($createdCount > 0) {
            $this->command->info("Kreirano {$createdCount} novih pitanja sa odgovorima!");
        } else {
            $this->command->info('Sva pitanja već postoje u bazi.');
        }
    }
}
