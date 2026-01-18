<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specijalnosti', function (Blueprint $table) {
            $table->json('kljucne_rijeci')->nullable()->after('meta_keywords');
        });

        // Populate with default keywords for common specialties
        $keywords = [
            'kardiologija' => ['kardiolog', 'srce', 'srcani', 'kardio', 'infarkt', 'aritmija', 'hipertenzija', 'krvni pritisak', 'ekg', 'holter'],
            'ortopedija' => ['ortoped', 'kosti', 'zglobovi', 'prelom', 'fraktura', 'kicma', 'kuk', 'koljeno', 'rame', 'sportske povrede', 'artroza'],
            'neurologija' => ['neurolog', 'mozak', 'zivci', 'glavobolja', 'migrena', 'epilepsija', 'parkinson', 'alzheimer', 'mozdani udar'],
            'dermatologija' => ['dermatolog', 'koza', 'kozni', 'akne', 'ekcem', 'psoriaza', 'mladeži', 'alergija', 'osip'],
            'ginekologija' => ['ginekolog', 'zensko zdravlje', 'trudnoca', 'porod', 'menstruacija', 'jajnici', 'maternica', 'papa test'],
            'pedijatrija' => ['pedijatar', 'djeca', 'djecji', 'beba', 'novorodence', 'vakcinacija', 'rast', 'razvoj'],
            'oftalmologija' => ['oftalmolog', 'ocni', 'oci', 'vid', 'naocale', 'katarakta', 'glaukom', 'kratkovidnost', 'dalekovidnost'],
            'stomatologija' => ['stomatolog', 'zubar', 'zubi', 'zubni', 'karijes', 'plomba', 'proteza', 'implant', 'ortodoncija'],
            'urologija' => ['urolog', 'bubrezi', 'mjehur', 'prostata', 'kamenci', 'urin', 'mokracni'],
            'psihijatrija' => ['psihijatar', 'mentalno zdravlje', 'depresija', 'anksioznost', 'stres', 'panika', 'fobija'],
            'interna-medicina' => ['internista', 'interna', 'opsta medicina', 'dijabetes', 'secer', 'stitnjaca', 'holesterol'],
            'otorinolaringologija' => ['orl', 'uho', 'grlo', 'nos', 'sinusi', 'sluh', 'tonzile', 'krajnici'],
            'fizikalna-medicina' => ['fizijatar', 'rehabilitacija', 'fizikalna terapija', 'masaza', 'bol u ledjima'],
            'radiologija' => ['radiolog', 'rendgen', 'ultrazvuk', 'ct', 'magnetna rezonanca', 'mr', 'snimanje'],
            'onkologija' => ['onkolog', 'rak', 'tumor', 'kemoterapija', 'radioterapija', 'maligno'],
            'endokrinologija' => ['endokrinolog', 'hormoni', 'stitnjaca', 'dijabetes', 'metabolizam'],
            'gastroenterologija' => ['gastroenterolog', 'zeludac', 'crijeva', 'jetra', 'probava', 'gastritis', 'ulkus', 'kolonoskopija'],
            'pulmologija' => ['pulmolog', 'pluca', 'disanje', 'astma', 'bronhitis', 'upala pluca'],
            'reumatologija' => ['reumatolog', 'artritis', 'reumatizam', 'zglobovi', 'upala zglobova'],
            'alergologija' => ['alergolog', 'alergija', 'astma', 'peludna groznica', 'koprivnjaca'],
            'hematologija' => ['hematolog', 'krv', 'anemija', 'leukemija', 'koagulacija'],
            'nefrologija' => ['nefrolog', 'bubrezi', 'dijaliza', 'bubreži'],
            'infektologija' => ['infektolog', 'infekcija', 'virus', 'bakterija', 'zarazne bolesti'],
        ];

        foreach ($keywords as $slug => $words) {
            \DB::table('specijalnosti')
                ->where('slug', $slug)
                ->update(['kljucne_rijeci' => json_encode($words)]);
        }
    }

    public function down(): void
    {
        Schema::table('specijalnosti', function (Blueprint $table) {
            $table->dropColumn('kljucne_rijeci');
        });
    }
};
