<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap Files Included In /sitemap.xml
    |--------------------------------------------------------------------------
    |
    | Keep this list in sync with generated sitemap files. The index endpoint
    | and static sitemap generation command both read from this config.
    |
    */
    'index_files' => [
        'sitemap-pages.xml',
        'sitemap-doctors.xml',
        'sitemap-clinics.xml',
        'sitemap-specialties.xml',
        'sitemap-service-pages.xml',
        'sitemap-cities.xml',
        'sitemap-laboratories.xml',
        'sitemap-lijekovi.xml',
        'sitemap-pharmacies.xml',
        'sitemap-spas.xml',
        'sitemap-care-homes.xml',
        'sitemap-doctor-city-specialties.xml',
        'sitemap-clinic-city-specialties.xml',
        'sitemap-blog.xml',
        'sitemap-pitanja.xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Method Map
    |--------------------------------------------------------------------------
    |
    | Maps output file names to methods on SitemapController.
    |
    */
    'generators' => [
        'sitemap.xml' => 'index',
        'sitemap-pages.xml' => 'pages',
        'sitemap-doctors.xml' => 'doctors',
        'sitemap-clinics.xml' => 'clinics',
        'sitemap-laboratories.xml' => 'laboratories',
        'sitemap-lijekovi.xml' => 'medicines',
        'sitemap-pharmacies.xml' => 'pharmacies',
        'sitemap-spas.xml' => 'spas',
        'sitemap-care-homes.xml' => 'careHomes',
        'sitemap-doctor-city-specialties.xml' => 'doctorCitySpecialties',
        'sitemap-clinic-city-specialties.xml' => 'clinicCitySpecialties',
        'sitemap-specialties.xml' => 'specialties',
        'sitemap-service-pages.xml' => 'servicePages',
        'sitemap-cities.xml' => 'cities',
        'sitemap-blog.xml' => 'blog',
        'sitemap-pitanja.xml' => 'questions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Methods Used For SEO Prerender Path Discovery
    |--------------------------------------------------------------------------
    */
    'prerender_methods' => [
        'pages',
        'doctors',
        'clinics',
        'specialties',
        'servicePages',
        'cities',
        'laboratories',
        'medicines',
        'pharmacies',
        'spas',
        'careHomes',
        'doctorCitySpecialties',
        'clinicCitySpecialties',
        'blog',
        'questions',
    ],
];
