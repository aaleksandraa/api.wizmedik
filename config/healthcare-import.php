<?php

return [
    'source' => 'wizmedik_healthcare_master_2026',

    'clinic_types' => [
        'dental_practice',
        'dental_clinic',
        'specialist_practice',
        'polyclinic',
        'specialist_clinic',
        'medical_center',
        'private_hospital',
        'hospital',
        'general_practice',
        'health_center',
        'specialist_hospital',
        'clinical_center',
        'fertility_clinic',
        'ophthalmology_clinic',
    ],

    'skipped_types' => [
        'laboratory',
        'diagnostic_center',
        'rehabilitation_center',
    ],

    /*
     * Host/fragment from Excel Primary source or Napomene → existing gradovi.naziv.
     * Used only for doctors without a clinic affiliation.
     */
    'standalone_source_cities' => [
        'zzotk.ba' => 'Tuzla',
        'zzo tk' => 'Tuzla',
    ],

    'unpublished_phone' => 'nije javno',

    'forbidden_tables' => [
        'apoteke_firme',
        'apoteke_poslovnice',
        'apoteke_radno_vrijeme',
        'lijekovi',
        'lijek_registar_zapisi',
        'lijek_fond_zapisi',
        'banje',
        'laboratorije',
        'domovi_njega',
        'usluge',
    ],

    'city_aliases' => [
        'grad sarajevo' => 'Sarajevo',
        'sarajevo' => 'Sarajevo',
        'grad mostar' => 'Mostar',
        'mostar' => 'Mostar',
        'siroki brijeg' => 'Široki Brijeg',
        'široki brijeg' => 'Široki Brijeg',
        'gorazde' => 'Goražde',
        'goražde' => 'Goražde',
        'brcko' => 'Brčko',
        'brčko' => 'Brčko',
        'brcko distrikt' => 'Brčko',
        'brčko distrikt' => 'Brčko',
        'banja luka' => 'Banja Luka',
        'istocno sarajevo' => 'Istočno Novo Sarajevo',
        'istočno sarajevo' => 'Istočno Novo Sarajevo',
    ],

    /*
     * Keys are lowercase aliases (names or excel canonical candidates).
     * Values are existing specijalnosti.slug from SpecialtiesSeeder.
     */
    'specialty_aliases' => [
        'internista' => 'interna-medicina',
        'interna medicina' => 'interna-medicina',
        'specijalista interne medicine' => 'interna-medicina',
        'ginekologija i akušerstvo' => 'ginekologija',
        'ginekologija i akuserstvo' => 'ginekologija',
        'ginekologija i porodništvo' => 'ginekologija',
        'ginekologija i porodnistvo' => 'ginekologija',
        'ginekologija-i-akuserstvo' => 'ginekologija',
        'orl' => 'orl-i-otorinolaringologija',
        'otorinolaringologija' => 'orl-i-otorinolaringologija',
        'bolesti uha grla i nosa' => 'orl-i-otorinolaringologija',
        'uho grlo i nos' => 'uho-grlo-i-nos',
        'stomatolog' => 'stomatologija',
        'dental' => 'stomatologija',
        'dentist' => 'stomatologija',
        'ortodont' => 'ortodoncija',
        'kardiolog' => 'kardiologija',
        'neurolog' => 'neurologija',
        'dermatolog' => 'dermatologija',
        'urolog' => 'urologija',
        'oftalmolog' => 'oftalmologija',
        'ocni ljekar' => 'oftalmologija',
        'očni ljekar' => 'oftalmologija',
        'psihijatar' => 'psihijatrija',
        'endokrinolog' => 'endokrinologija',
        'gastroenterolog' => 'gastroenterologija',
        'pulmolog' => 'pulmologija',
        'ortoped' => 'ortopedija',
        'reumatolog' => 'reumatologija',
        'hirurg' => 'hirurgija',
        'pedijatar' => 'pedijatrija',
        'opsta medicina' => 'opsta-medicina-i-porodicna-medicina',
        'opšta medicina' => 'opsta-medicina-i-porodicna-medicina',
        'porodicna medicina' => 'opsta-medicina-i-porodicna-medicina',
        'porodična medicina' => 'opsta-medicina-i-porodicna-medicina',
        'ljekar opste prakse' => 'opsta-medicina-i-porodicna-medicina',
        'hepatologija' => 'gastroenterologija',
    ],
];
