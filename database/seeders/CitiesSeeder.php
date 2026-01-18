<?php

namespace Database\Seeders;

use App\Models\Grad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        // Glavni gradovi sa detaljnim informacijama
        $majorCities = [
            [
                'naziv' => 'Sarajevo',
                'u_gradu' => 'u Sarajevu',
                'opis' => 'Glavni grad Bosne i Hercegovine',
                'detaljni_opis' => 'Sarajevo je glavni i najveći grad Bosne i Hercegovine sa preko 400.000 stanovnika.',
                'populacija' => '400,000+',
                'broj_bolnica' => 5,
                'hitna_pomoc' => '124',
                'kljucne_tacke' => ['KCUS', 'Klinika Podhrastovi', 'Dom zdravlja Centar'],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Banja Luka',
                'u_gradu' => 'u Banjoj Luci',
                'opis' => 'Glavni grad Republike Srpske',
                'detaljni_opis' => 'Banja Luka je drugi po veličini grad u BiH.',
                'populacija' => '200,000+',
                'broj_bolnica' => 3,
                'hitna_pomoc' => '124',
                'kljucne_tacke' => ['UKC RS', 'Dom zdravlja Banja Luka'],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Tuzla',
                'u_gradu' => 'u Tuzli',
                'opis' => 'Industrijski i univerzitetski grad',
                'detaljni_opis' => 'Tuzla je treći po veličini grad u BiH.',
                'populacija' => '120,000+',
                'broj_bolnica' => 2,
                'hitna_pomoc' => '124',
                'kljucne_tacke' => ['UKC Tuzla'],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Zenica',
                'u_gradu' => 'u Zenici',
                'opis' => 'Grad čelika',
                'detaljni_opis' => 'Zenica je četvrti po veličini grad u BiH.',
                'populacija' => '110,000+',
                'broj_bolnica' => 2,
                'hitna_pomoc' => '124',
                'kljucne_tacke' => ['Kantonalna bolnica Zenica'],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Mostar',
                'u_gradu' => 'u Mostaru',
                'opis' => 'Grad na Neretvi',
                'detaljni_opis' => 'Mostar je glavni grad Hercegovine.',
                'populacija' => '105,000+',
                'broj_bolnica' => 1,
                'hitna_pomoc' => '124',
                'kljucne_tacke' => ['UKC Mostar'],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Bijeljina',
                'u_gradu' => 'u Bijeljini',
                'opis' => 'Grad u Semberiji',
                'detaljni_opis' => 'Bijeljina je grad u sjeveroistočnoj Bosni i Hercegovini.',
                'populacija' => '107,000+',
                'aktivan' => true,
            ],
            [
                'naziv' => 'Brčko',
                'u_gradu' => 'u Brčkom',
                'opis' => 'Brčko Distrikt BiH',
                'detaljni_opis' => 'Brčko je grad i sjedište Brčko Distrikta BiH.',
                'populacija' => '83,000+',
                'aktivan' => true,
            ],
            [
                'naziv' => 'Prijedor',
                'u_gradu' => 'u Prijedoru',
                'opis' => 'Grad u sjeverozapadnoj BiH',
                'detaljni_opis' => 'Prijedor je grad u sjeverozapadnom dijelu Bosne i Hercegovine.',
                'populacija' => '89,000+',
                'aktivan' => true,
            ],
            [
                'naziv' => 'Doboj',
                'u_gradu' => 'u Doboju',
                'opis' => 'Grad na rijeci Bosni',
                'detaljni_opis' => 'Doboj je grad u centralnoj Bosni i Hercegovini.',
                'populacija' => '72,000+',
                'aktivan' => true,
            ],
            [
                'naziv' => 'Trebinje',
                'u_gradu' => 'u Trebinju',
                'opis' => 'Grad u istočnoj Hercegovini',
                'detaljni_opis' => 'Trebinje je grad u istočnoj Hercegovini.',
                'populacija' => '31,000+',
                'aktivan' => true,
            ],
        ];

        // Svi ostali gradovi i općine BiH
        $allCities = [
            // Federacija BiH - Unsko-sanski kanton
            'Bihać', 'Bosanska Krupa', 'Bosanski Petrovac', 'Bužim', 'Cazin', 'Ključ', 'Sanski Most', 'Velika Kladuša',
            // Posavski kanton
            'Odžak', 'Orašje', 'Domaljevac-Šamac',
            // Tuzlanski kanton
            'Banovići', 'Čelić', 'Doboj Istok', 'Gračanica', 'Gradačac', 'Kalesija', 'Kladanj', 'Lukavac', 'Sapna', 'Srebrenik', 'Teočak', 'Živinice',
            // Zeničko-dobojski kanton
            'Breza', 'Doboj Jug', 'Kakanj', 'Maglaj', 'Olovo', 'Tešanj', 'Usora', 'Vareš', 'Visoko', 'Zavidovići', 'Žepče',
            // Bosansko-podrinjski kanton
            'Foča-Ustikolina', 'Goražde', 'Pale-Prača',
            // Srednjobosanski kanton
            'Bugojno', 'Busovača', 'Donji Vakuf', 'Fojnica', 'Gornji Vakuf-Uskoplje', 'Jajce', 'Kiseljak', 'Kreševo', 'Novi Travnik', 'Travnik', 'Vitez',
            // Hercegovačko-neretvanski kanton
            'Čapljina', 'Čitluk', 'Jablanica', 'Konjic', 'Neum', 'Prozor-Rama', 'Ravno', 'Stolac',
            // Zapadnohercegovački kanton
            'Grude', 'Ljubuški', 'Posušje', 'Široki Brijeg',
            // Kanton Sarajevo
            'Centar Sarajevo', 'Hadžići', 'Ilidža', 'Ilijaš', 'Novi Grad Sarajevo', 'Novo Sarajevo', 'Stari Grad Sarajevo', 'Trnovo', 'Vogošća',
            // Kanton 10 (Livanjski)
            'Bosansko Grahovo', 'Drvar', 'Glamoč', 'Kupres', 'Livno', 'Tomislavgrad',
            // Republika Srpska
            'Berkovići', 'Bileća', 'Bratunac', 'Čajniče', 'Čelinac', 'Derventa', 'Donji Žabar', 'Foča', 'Gacko', 'Gradiška', 'Han Pijesak', 'Istočna Ilidža', 'Istočni Drvar', 'Istočni Mostar', 'Istočni Stari Grad', 'Istočno Novo Sarajevo', 'Jezero', 'Kalinovik', 'Kneževo', 'Kotor Varoš', 'Kozarska Dubica', 'Krupa na Uni', 'Kupres RS', 'Laktaši', 'Lopare', 'Ljubinje', 'Milići', 'Modriča', 'Mrkonjić Grad', 'Nevesinje', 'Novi Grad', 'Novo Goražde', 'Osmaci', 'Oštra Luka', 'Pale', 'Pelagićevo', 'Petrovac', 'Petrovo', 'Prnjavor', 'Ribnik', 'Rogatica', 'Rudo', 'Šamac', 'Šekovići', 'Šipovo', 'Sokolac', 'Srbac', 'Srebrenica', 'Stanari', 'Teslić', 'Trnovo RS', 'Ugljevik', 'Višegrad', 'Vlasenica', 'Vukosavlje', 'Zvornik',
        ];

        // Unesi glavne gradove sa detaljima
        foreach ($majorCities as $city) {
            $city['slug'] = Str::slug($city['naziv']);
            Grad::updateOrCreate(
                ['slug' => $city['slug']],
                $city
            );
        }

        // Unesi ostale gradove
        foreach ($allCities as $cityName) {
            $slug = Str::slug($cityName);

            // Provjeri da li već postoji (možda je u majorCities)
            if (Grad::where('slug', $slug)->exists()) {
                continue;
            }

            // Generiši "u gradu" formu
            $uGradu = $this->generateUGradu($cityName);

            Grad::create([
                'naziv' => $cityName,
                'u_gradu' => $uGradu,
                'slug' => $slug,
                'opis' => 'Grad u Bosni i Hercegovini',
                'detaljni_opis' => $cityName . ' je grad/općina u Bosni i Hercegovini.',
                'aktivan' => true,
            ]);
        }
    }

    /**
     * Generiši lokativ (u gradu) formu za grad
     */
    private function generateUGradu(string $naziv): string
    {
        // Posebni slučajevi
        $special = [
            'Bihać' => 'u Bihaću',
            'Cazin' => 'u Cazinu',
            'Livno' => 'u Livnu',
            'Jajce' => 'u Jajcu',
            'Brčko' => 'u Brčkom',
            'Goražde' => 'u Goraždu',
            'Konjic' => 'u Konjicu',
            'Stolac' => 'u Stocu',
            'Neum' => 'u Neumu',
            'Drvar' => 'u Drvaru',
            'Glamoč' => 'u Glamoču',
            'Kupres' => 'na Kupresu',
            'Pale' => 'na Palama',
            'Foča' => 'u Foči',
            'Gacko' => 'u Gacku',
            'Rudo' => 'u Rudom',
            'Višegrad' => 'u Višegradu',
            'Zvornik' => 'u Zvorniku',
        ];

        if (isset($special[$naziv])) {
            return $special[$naziv];
        }

        // Opća pravila
        $lastChar = mb_substr($naziv, -1);
        $lastTwoChars = mb_substr($naziv, -2);

        // Završava na -a (ženski rod)
        if ($lastChar === 'a') {
            return 'u ' . mb_substr($naziv, 0, -1) . 'i';
        }

        // Završava na -o ili -e (srednji rod)
        if ($lastChar === 'o' || $lastChar === 'e') {
            return 'u ' . mb_substr($naziv, 0, -1) . 'u';
        }

        // Završava na suglasnik (muški rod)
        return 'u ' . $naziv . 'u';
    }
}
