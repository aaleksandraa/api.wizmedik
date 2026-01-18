<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Comprehensive keywords for all specialties - covering all possible search terms
        $keywords = [
            'kardiologija' => [
                'kardiolog', 'kardiologa', 'srce', 'srcani', 'srcana', 'kardio', 'infarkt', 'aritmija',
                'hipertenzija', 'krvni pritisak', 'visok pritisak', 'nizak pritisak', 'ekg', 'holter',
                'angina', 'pektoris', 'srcani udar', 'srcana insuficijencija', 'tahikardija', 'bradikardija',
                'palpitacije', 'lupanje srca', 'bol u grudima', 'stent', 'bajpas', 'bypass', 'ehokardiografija',
                'ultrazvuk srca', 'koronarna bolest', 'ateroskleroza', 'kolesterol', 'trigliceridi'
            ],
            'ortopedija' => [
                'ortoped', 'ortopeda', 'kosti', 'zglobovi', 'zglob', 'prelom', 'fraktura', 'kicma',
                'kuk', 'koljeno', 'rame', 'lakat', 'ruka', 'noga', 'stopalo', 'gleznj', 'sportske povrede',
                'artroza', 'artritis', 'diskus', 'hernija diska', 'skolioza', 'kifoza', 'lordoza',
                'meniskus', 'ligament', 'tetiva', 'ahilova tetiva', 'tendinitis', 'burza', 'burzitis',
                'osteoporoza', 'bol u ledjima', 'bol u kicmi', 'bol u kuku', 'bol u koljenu', 'bol u ramenu',
                'proteza kuka', 'proteza koljena', 'gips', 'imobilizacija', 'rehabilitacija'
            ],
            'neurologija' => [
                'neurolog', 'neurologa', 'mozak', 'zivci', 'zivac', 'nervni sistem', 'glavobolja',
                'migrena', 'epilepsija', 'parkinson', 'alzheimer', 'mozdani udar', 'slog', 'insult',
                'multipla skleroza', 'ms', 'tremor', 'drhtanje', 'vrtoglavica', 'nesvestica', 'gubitak svijesti',
                'utrnulost', 'trnci', 'parestezije', 'paraliza', 'pareza', 'neuropatija', 'radikulopatija',
                'ishijas', 'isijas', 'cervikalni sindrom', 'lumbalni sindrom', 'demencija', 'amnezija',
                'eeg', 'emg', 'lumbalna punkcija', 'ct mozga', 'mr mozga'
            ],
            'dermatologija' => [
                'dermatolog', 'dermatologa', 'koza', 'kozni', 'kozna', 'akne', 'bubuljice', 'pristi',
                'ekcem', 'psoriaza', 'mladeži', 'madez', 'bradavice', 'alergija', 'osip', 'svrab',
                'urtikarija', 'koprivnjaca', 'dermatitis', 'rozacea', 'vitiligo', 'gljivice', 'mikoza',
                'herpes', 'zona', 'celulitis', 'opadanje kose', 'alopecija', 'sebreja', 'perut',
                'melanom', 'rak koze', 'bazaliom', 'keratoza', 'lipom', 'cista', 'aterom',
                'botoks', 'fileri', 'mezoterapija', 'hemijski piling', 'laser', 'dermatoskopija'
            ],
            'ginekologija' => [
                'ginekolog', 'ginekologa', 'zensko zdravlje', 'zena', 'zene', 'trudnoca', 'trudnica',
                'porod', 'porodjaj', 'menstruacija', 'mjesecnica', 'ciklus', 'jajnici', 'jajnik',
                'maternica', 'materica', 'grlić maternice', 'papa test', 'kolposkopija', 'ultrazvuk',
                'miom', 'cista jajnika', 'endometrioza', 'policisticni jajnici', 'pcos', 'neplodnost',
                'kontracepcija', 'spirala', 'pilula', 'menopauza', 'klimakterij', 'hormonska terapija',
                'vaginitis', 'kandidijaza', 'hpv', 'rak grlica maternice', 'rak dojke', 'mamografija',
                'prenatalni pregled', 'amniocenteza', 'ctg', 'porodjajni plan'
            ],
            'pedijatrija' => [
                'pedijatar', 'pedijatra', 'djeca', 'dijete', 'djecji', 'djecja', 'beba', 'bebe',
                'novorodence', 'novorodjence', 'dojenče', 'vakcinacija', 'vakcina', 'cjepivo', 'imunizacija',
                'rast', 'razvoj', 'tjelesna težina', 'visina', 'percentili', 'prehrana', 'dojenje',
                'prehlada', 'gripa', 'temperatura', 'vrucica', 'kasalj', 'upala uha', 'otitis',
                'angina', 'tonzilitis', 'bronhitis', 'pneumonija', 'proljev', 'povracanje', 'dehidracija',
                'osip', 'alergija', 'astma', 'atopijski dermatitis', 'adhd', 'autizam', 'razvojni poremecaj'
            ],
            'oftalmologija' => [
                'oftalmolog', 'oftalmologa', 'ocni', 'ocna', 'oci', 'oko', 'vid', 'videnje',
                'naocale', 'naočale', 'kontaktne lece', 'dioptrija', 'kratkovidnost', 'miopija',
                'dalekovidnost', 'hipermetropija', 'astigmatizam', 'presbiopija', 'staracka dalekovidnost',
                'katarakta', 'mrena', 'glaukom', 'zelena mrena', 'makularna degeneracija', 'amd',
                'suho oko', 'konjuktivitis', 'upala oka', 'crveno oko', 'suzenje', 'svrab oka',
                'laser operacija', 'lasik', 'operacija katarakte', 'intraokularno socivo', 'iol',
                'mreznica', 'retina', 'ablacija retine', 'dijabetička retinopatija', 'vid test'
            ],
            'stomatologija' => [
                'stomatolog', 'stomatologa', 'zubar', 'zubara', 'zubi', 'zub', 'zubni', 'zubna',
                'karijes', 'plomba', 'ispun', 'proteza', 'zubna proteza', 'implant', 'implantati',
                'ortodoncija', 'aparatic', 'aparatić za zube', 'bravice', 'invisalign', 'ispravljanje zuba',
                'vadjenje zuba', 'ekstrakcija', 'umnjak', 'umnjaci', 'bol u zubu', 'zubobolja',
                'gingivitis', 'parodontoza', 'krvarenje desni', 'desni', 'upala desni',
                'izbjeljivanje zuba', 'estetska stomatologija', 'fasete', 'krunica', 'most',
                'endodoncija', 'lijecenje kanala', 'apscesni zub', 'apsces', 'cista', 'rendgen zuba'
            ],
            'urologija' => [
                'urolog', 'urologa', 'bubrezi', 'bubreg', 'mjehur', 'mokracni mjehur', 'prostata',
                'kamenci', 'kamenac', 'bubrežni kamenac', 'urin', 'mokraca', 'mokracni', 'mokracna',
                'urinarna infekcija', 'cistitis', 'upala mjehura', 'pijelonefritis', 'upala bubrega',
                'inkontinencija', 'nemogucnost zadrzavanja urina', 'cesto mokrenje', 'nocno mokrenje',
                'erektilna disfunkcija', 'impotencija', 'neplodnost', 'varikokela', 'hidrokela',
                'rak prostate', 'psa', 'benigna hiperplazija prostate', 'bhp', 'uvecana prostata',
                'cirkumcizija', 'obrezivanje', 'vazektomija', 'ureteroskopija', 'cistoskopija'
            ],
            'psihijatrija' => [
                'psihijatar', 'psihijatra', 'mentalno zdravlje', 'psihicko zdravlje', 'depresija',
                'anksioznost', 'tjeskoba', 'stres', 'panika', 'panicni napad', 'fobija', 'strah',
                'bipolarni poremecaj', 'shizofrenija', 'psihoze', 'halucinacije', 'deluzije',
                'ocd', 'opsesivno kompulzivni', 'ptsp', 'posttraumatski stres', 'trauma',
                'poremecaj licnosti', 'poremecaj ishrane', 'anoreksija', 'bulimija', 'prejedanje',
                'nesanica', 'insomnia', 'poremecaj spavanja', 'ovisnost', 'zavisnost', 'alkoholizam',
                'narkomanija', 'psihoterapija', 'antidepresivi', 'anksiolitici', 'antipsihotici'
            ],
            'otorinolaringologija' => [
                'orl', 'otorinolaringolog', 'uho', 'usi', 'grlo', 'nos', 'sinusi', 'sinus', 'sinusitis',
                'sluh', 'gluhost', 'nagluhost', 'tinitus', 'zujanje u usima', 'vrtoglavica', 'vertigo',
                'tonzile', 'krajnici', 'tonzilitis', 'angina', 'adenoidi', 'trece krajnici',
                'upala uha', 'otitis', 'upala srednjeg uha', 'cerumen', 'usna mast', 'cep u uhu',
                'devijacija septuma', 'kriva nosna pregrada', 'polipi', 'nosni polipi', 'epistaksa',
                'krvarenje iz nosa', 'hrkanje', 'apneja', 'sleep apnea', 'promuklost', 'laringitis',
                'glasne zice', 'disfagija', 'otezano gutanje', 'audiometrija', 'timpanometrija'
            ],
            'interna-medicina' => [
                'internista', 'interna', 'interna medicina', 'opsta medicina', 'opca praksa',
                'dijabetes', 'secer', 'secerna bolest', 'stitnjaca', 'tiroidea', 'hipotireoza',
                'hipertireoza', 'holesterol', 'trigliceridi', 'masnoce u krvi', 'metabolicki sindrom',
                'gojaznost', 'pretilost', 'mrsavljenje', 'anemija', 'slabokrvnost', 'umor', 'malaksalost',
                'temperatura', 'vrucica', 'upala', 'infekcija', 'prehlada', 'gripa', 'covid',
                'krvna slika', 'laboratorija', 'biohemija', 'sistematski pregled', 'check up'
            ],
            'fizikalna-medicina' => [
                'fizijatar', 'fizijatra', 'rehabilitacija', 'fizikalna terapija', 'fizioterapija',
                'masaza', 'masaža', 'elektroterapija', 'magnetna terapija', 'ultrazvuk terapija',
                'bol u ledjima', 'bol u kicmi', 'bol u vratu', 'cervikalni sindrom', 'lumbalni sindrom',
                'ishijas', 'isijas', 'rehabilitacija nakon operacije', 'rehabilitacija nakon mozdanog udara',
                'vjezbe', 'kineziterapija', 'hidroterapija', 'bazen', 'toplice', 'banja',
                'tens', 'laser terapija', 'parafin', 'krioterapija', 'termoterapija'
            ],
            'radiologija' => [
                'radiolog', 'radiologa', 'rendgen', 'rtg', 'x-ray', 'ultrazvuk', 'uzv', 'ehografija',
                'ct', 'kompjuterizirana tomografija', 'sken', 'skener', 'magnetna rezonanca', 'mr', 'mri',
                'snimanje', 'dijagnostika', 'mamografija', 'denzitometrija', 'pet ct', 'angiografija',
                'kontrastno snimanje', 'biopsija', 'punkcija', 'intervencijska radiologija'
            ],
            'onkologija' => [
                'onkolog', 'onkologa', 'rak', 'karcinom', 'tumor', 'maligno', 'maligni', 'benigno',
                'kemoterapija', 'hemoterapija', 'radioterapija', 'zracenje', 'imunoterapija',
                'metastaze', 'biopsija', 'patohistologija', 'onkoloska terapija', 'palijativna njega',
                'rak dojke', 'rak pluca', 'rak debelog crijeva', 'rak prostate', 'leukemija', 'limfom'
            ],
            'endokrinologija' => [
                'endokrinolog', 'endokrinologa', 'hormoni', 'hormonski', 'stitnjaca', 'tiroidea',
                'hipotireoza', 'hipertireoza', 'hashimoto', 'graves', 'cvorovi na stitnjaci',
                'dijabetes', 'secerna bolest', 'inzulin', 'hipofiza', 'nadbubrežna zlijezda',
                'metabolizam', 'gojaznost', 'mrsavljenje', 'osteoporoza', 'kalcij', 'vitamin d',
                'menopauza', 'hormonska terapija', 'neplodnost', 'pcos', 'testosteron', 'estrogen'
            ],
            'gastroenterologija' => [
                'gastroenterolog', 'gastroenterologa', 'zeludac', 'stomak', 'crijeva', 'crijevo',
                'jetra', 'jetrica', 'zuc', 'zucni mjehur', 'gusteraca', 'pankreas', 'probava',
                'gastritis', 'ulkus', 'cir', 'refluks', 'gorušica', 'žgaravica', 'mucnina', 'povracanje',
                'proljev', 'dijareja', 'zatvor', 'konstipacija', 'nadutost', 'gasovi', 'bol u stomaku',
                'kolonoskopija', 'gastroskopija', 'endoskopija', 'crohnova bolest', 'ulcerozni kolitis',
                'ibs', 'sindrom iritabilnog crijeva', 'celijakija', 'glutenska intolerancija',
                'hepatitis', 'ciroza', 'masna jetra', 'zucni kamenci', 'pankreatitis', 'hemoroidi'
            ],
            'pulmologija' => [
                'pulmolog', 'pulmologa', 'pluca', 'plucni', 'disanje', 'respiratorni', 'dišni putevi',
                'astma', 'bronhitis', 'hronicni bronhitis', 'kopb', 'hobp', 'emfizem', 'upala pluca',
                'pneumonija', 'tuberkuloza', 'tbc', 'kasalj', 'hronicni kasalj', 'kratkoća daha',
                'dispneja', 'otezano disanje', 'pleuralni izljev', 'pneumotoraks', 'plucna embolija',
                'spirometrija', 'plucna funkcija', 'bronhoskopija', 'rak pluca', 'sleep apnea', 'hrkanje'
            ],
            'reumatologija' => [
                'reumatolog', 'reumatologa', 'reumatizam', 'artritis', 'reumatoidni artritis',
                'zglobovi', 'upala zglobova', 'bol u zglobovima', 'ukoceni zglobovi', 'jutarnja ukočenost',
                'lupus', 'sistemski lupus', 'sjogren', 'skleroderma', 'vaskulitis', 'giht', 'podagra',
                'fibromijalgija', 'hronicna bol', 'osteoartritis', 'spondilitis', 'ankilozantni spondilitis'
            ],
            'alergologija' => [
                'alergolog', 'alergologa', 'alergija', 'alergije', 'alergijski', 'alergijska reakcija',
                'astma', 'alergijska astma', 'peludna groznica', 'rinitis', 'alergijski rinitis',
                'koprivnjaca', 'urtikarija', 'angioedem', 'anafilaksa', 'anafilakticki sok',
                'alergija na hranu', 'alergija na lijekove', 'alergija na ubod insekta',
                'atopijski dermatitis', 'ekcem', 'alergijski testovi', 'prick test', 'imunoterapija'
            ],
            'hematologija' => [
                'hematolog', 'hematologa', 'krv', 'krvni', 'krvna slika', 'anemija', 'slabokrvnost',
                'leukemija', 'rak krvi', 'limfom', 'hodgkin', 'mijelom', 'koagulacija', 'zgrušavanje',
                'tromboza', 'embolija', 'hemofilija', 'trombocitopenija', 'transfuzija', 'krvna grupa',
                'zeljezo', 'feritin', 'b12', 'folna kiselina', 'eritrociti', 'leukociti', 'trombociti'
            ],
            'nefrologija' => [
                'nefrolog', 'nefrologa', 'bubrezi', 'bubreg', 'bubrezni', 'bubrezna funkcija',
                'dijaliza', 'hemodijaliza', 'peritonejska dijaliza', 'transplantacija bubrega',
                'hronicna bubrezna bolest', 'akutno zatajenje bubrega', 'glomerulonefritis',
                'nefrotski sindrom', 'pijelonefritis', 'bubrezni kamenci', 'proteinurija', 'hematurija',
                'kreatinin', 'urea', 'gfr', 'klirens kreatinina'
            ],
            'infektologija' => [
                'infektolog', 'infektologa', 'infekcija', 'infekcije', 'zarazne bolesti', 'virus',
                'virusna infekcija', 'bakterija', 'bakterijska infekcija', 'gljivice', 'paraziti',
                'hepatitis', 'hiv', 'aids', 'covid', 'korona', 'gripa', 'influenca', 'mononukleoza',
                'lajmska bolest', 'borelioza', 'tuberkuloza', 'meningitis', 'sepsa', 'antibiotici',
                'antivirusni lijekovi', 'vakcinacija', 'imunizacija', 'putnicka medicina'
            ],
            'anesteziologija' => [
                'anesteziolog', 'anesteziologa', 'anestezija', 'narkoza', 'opsta anestezija',
                'lokalna anestezija', 'regionalna anestezija', 'epiduralna', 'spinalna',
                'intenzivna njega', 'icu', 'jil', 'reanimacija', 'terapija boli', 'hronicna bol'
            ],
            'plastična-kirurgija' => [
                'plasticni hirurg', 'plasticna hirurgija', 'estetska hirurgija', 'estetski zahvati',
                'liposukcija', 'abdominoplastika', 'rinoplastika', 'operacija nosa', 'blefaroplastika',
                'lifting lica', 'povecanje grudi', 'smanjenje grudi', 'botoks', 'fileri', 'mezoterapija',
                'rekonstruktivna hirurgija', 'opekotine', 'oziljci', 'transplantacija kose'
            ],
            'vaskularna-hirurgija' => [
                'vaskularni hirurg', 'vaskularna hirurgija', 'krvni sudovi', 'vene', 'arterije',
                'prosirene vene', 'varikozne vene', 'tromboza', 'duboka venska tromboza', 'dvt',
                'aneurizma', 'ateroskleroza', 'periferna arterijska bolest', 'dijabetičko stopalo',
                'skleroterapija', 'laser vene', 'bajpas', 'stent'
            ],
            'kardiohirurgija' => [
                'kardiohirurg', 'kardiohirurgija', 'operacija srca', 'bajpas', 'bypass', 'aortokoronarni bajpas',
                'zamjena zaliska', 'srcani zalistak', 'aortni zalistak', 'mitralni zalistak',
                'pejsmejker', 'defibrilator', 'transplantacija srca'
            ],
            'neurohirurgija' => [
                'neurohirurg', 'neurohirurgija', 'operacija mozga', 'operacija kicme', 'tumor mozga',
                'hernija diska', 'diskus', 'stenoza kicmenog kanala', 'hidrocefalus', 'aneurizma mozga',
                'epilepsija hirurgija', 'duboka mozdana stimulacija'
            ],
            'torakalna-hirurgija' => [
                'torakalni hirurg', 'torakalna hirurgija', 'operacija pluca', 'rak pluca',
                'pneumotoraks', 'pleuralni izljev', 'medijastinum', 'jednjak', 'dijafragma'
            ],
            'abdominalna-hirurgija' => [
                'abdominalni hirurg', 'abdominalna hirurgija', 'opsta hirurgija', 'operacija stomaka',
                'slijepo crijevo', 'apendektomija', 'zucni mjehur', 'holecistektomija', 'kila', 'hernija',
                'laparoskopija', 'debelo crijevo', 'kolektomija', 'jetra', 'gusteraca', 'slezena'
            ]
        ];

        foreach ($keywords as $slug => $words) {
            DB::table('specijalnosti')
                ->where('slug', $slug)
                ->update(['kljucne_rijeci' => json_encode($words)]);
        }
    }

    public function down(): void
    {
        // No rollback needed - previous migration handles column
    }
};
