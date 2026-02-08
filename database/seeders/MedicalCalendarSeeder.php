<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedicalCalendar;
use Carbon\Carbon;

class MedicalCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // JANUAR 2026
            [
                'date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'title' => 'Cervical Cancer Awareness Month',
                'description' => 'Mjesec svjesnosti o raku grlića materice',
                'type' => 'month',
                'category' => 'cancer',
                'color' => '#9333ea'
            ],
            [
                'date' => '2026-01-19',
                'end_date' => '2026-01-25',
                'title' => 'Cervical Cancer Prevention Week',
                'description' => 'Sedmica prevencije raka grlića materice',
                'type' => 'week',
                'category' => 'cancer',
                'color' => '#9333ea'
            ],
            [
                'date' => '2026-01-04',
                'title' => 'World Braille Day',
                'description' => 'Svjetski dan Brailleovog pisma',
                'type' => 'day',
                'category' => 'disability',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-01-31',
                'title' => 'World Leprosy Day',
                'description' => 'Svjetski dan lepre',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],

            // FEBRUAR 2026
            [
                'date' => '2026-02-01',
                'end_date' => '2026-02-28',
                'title' => 'Healthy Lifestyle Awareness Month',
                'description' => 'Mjesec svjesnosti o zdravom načinu života',
                'type' => 'month',
                'category' => 'lifestyle',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-02-04',
                'title' => 'World Cancer Day',
                'description' => 'Svjetski dan borbe protiv raka',
                'type' => 'day',
                'category' => 'cancer',
                'color' => '#dc2626'
            ],
            [
                'date' => '2026-02-12',
                'title' => 'International Epilepsy Day',
                'description' => 'Međunarodni dan epilepsije',
                'type' => 'day',
                'category' => 'neurological',
                'color' => '#8b5cf6'
            ],

            // MART 2026
            [
                'date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'title' => 'TB Awareness Month',
                'description' => 'Mjesec svjesnosti o tuberkulozi',
                'type' => 'month',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],
            [
                'date' => '2026-03-03',
                'title' => 'World Hearing Day',
                'description' => 'Svjetski dan sluha',
                'type' => 'day',
                'category' => 'sensory',
                'color' => '#06b6d4'
            ],
            [
                'date' => '2026-03-08',
                'title' => 'International Women\'s Day',
                'description' => 'Međunarodni dan žena',
                'type' => 'day',
                'category' => 'womens-health',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-03-12',
                'title' => 'World Kidney Day',
                'description' => 'Svjetski dan bubrega',
                'type' => 'day',
                'category' => 'organ-health',
                'color' => '#ef4444'
            ],
            [
                'date' => '2026-03-21',
                'title' => 'World Down Syndrome Day',
                'description' => 'Svjetski dan Down sindroma',
                'type' => 'day',
                'category' => 'genetic',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-03-24',
                'title' => 'World TB Day',
                'description' => 'Svjetski dan tuberkuloze',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],

            // APRIL 2026
            [
                'date' => '2026-04-02',
                'title' => 'World Autism Awareness Day',
                'description' => 'Svjetski dan svjesnosti o autizmu',
                'type' => 'day',
                'category' => 'neurological',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-04-07',
                'title' => 'World Health Day',
                'description' => 'Svjetski dan zdravlja',
                'type' => 'day',
                'category' => 'general-health',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-04-17',
                'title' => 'World Haemophilia Day',
                'description' => 'Svjetski dan hemofilije',
                'type' => 'day',
                'category' => 'blood-disorder',
                'color' => '#dc2626'
            ],
            [
                'date' => '2026-04-25',
                'title' => 'World Malaria Day',
                'description' => 'Svjetski dan malarije',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],

            // MAJ 2026
            [
                'date' => '2026-05-01',
                'end_date' => '2026-05-31',
                'title' => 'No Tobacco Awareness Month',
                'description' => 'Mjesec svjesnosti o štetnosti duvana',
                'type' => 'month',
                'category' => 'prevention',
                'color' => '#64748b'
            ],
            [
                'date' => '2026-05-05',
                'title' => 'World Hand Hygiene Day',
                'description' => 'Svjetski dan higijene ruku',
                'type' => 'day',
                'category' => 'prevention',
                'color' => '#06b6d4'
            ],
            [
                'date' => '2026-05-12',
                'title' => 'International Nurses Day',
                'description' => 'Međunarodni dan medicinskih sestara',
                'type' => 'day',
                'category' => 'healthcare-workers',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-05-17',
                'title' => 'World Hypertension Day',
                'description' => 'Svjetski dan hipertenzije',
                'type' => 'day',
                'category' => 'cardiovascular',
                'color' => '#ef4444'
            ],
            [
                'date' => '2026-05-31',
                'title' => 'World No Tobacco Day',
                'description' => 'Svjetski dan bez duvana',
                'type' => 'day',
                'category' => 'prevention',
                'color' => '#64748b'
            ],

            // JUNI 2026
            [
                'date' => '2026-06-01',
                'end_date' => '2026-06-30',
                'title' => 'Men\'s Health Month',
                'description' => 'Mjesec muškog zdravlja',
                'type' => 'month',
                'category' => 'mens-health',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-06-05',
                'title' => 'World Environment Day',
                'description' => 'Svjetski dan zaštite životne sredine',
                'type' => 'day',
                'category' => 'environmental-health',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-06-14',
                'title' => 'World Blood Donor Day',
                'description' => 'Svjetski dan davalaca krvi',
                'type' => 'day',
                'category' => 'blood-donation',
                'color' => '#dc2626'
            ],
            [
                'date' => '2026-06-15',
                'title' => 'World Elder Abuse Awareness Day',
                'description' => 'Svjetski dan svjesnosti o zlostavljanju starijih osoba',
                'type' => 'day',
                'category' => 'elderly-care',
                'color' => '#8b5cf6'
            ],
            [
                'date' => '2026-06-25',
                'title' => 'World Vitiligo Day',
                'description' => 'Svjetski dan vitiliga',
                'type' => 'day',
                'category' => 'skin-condition',
                'color' => '#a855f7'
            ],
            [
                'date' => '2026-06-26',
                'title' => 'International Day against Drug Abuse',
                'description' => 'Međunarodni dan borbe protiv zloupotrebe droga',
                'type' => 'day',
                'category' => 'substance-abuse',
                'color' => '#dc2626'
            ],

            // JULI 2026
            [
                'date' => '2026-07-01',
                'end_date' => '2026-07-31',
                'title' => 'Mental Illness Awareness Month',
                'description' => 'Mjesec svjesnosti o mentalnim bolestima',
                'type' => 'month',
                'category' => 'mental-health',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-07-11',
                'title' => 'World Population Day',
                'description' => 'Svjetski dan populacije',
                'type' => 'day',
                'category' => 'public-health',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-07-28',
                'title' => 'World Hepatitis Day',
                'description' => 'Svjetski dan hepatitisa',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],

            // AVGUST 2026
            [
                'date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'title' => 'National Women\'s Month',
                'description' => 'Nacionalni mjesec žena',
                'type' => 'month',
                'category' => 'womens-health',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-08-01',
                'end_date' => '2026-08-07',
                'title' => 'World Breastfeeding Week',
                'description' => 'Svjetska sedmica dojenja',
                'type' => 'week',
                'category' => 'maternal-health',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-08-20',
                'title' => 'World Mosquito Day',
                'description' => 'Svjetski dan komarca (prevencija malarije)',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],

            // SEPTEMBAR 2026
            [
                'date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'title' => 'Childhood Cancer Awareness Month',
                'description' => 'Mjesec svjesnosti o dječijem raku',
                'type' => 'month',
                'category' => 'cancer',
                'color' => '#fbbf24'
            ],
            [
                'date' => '2026-09-08',
                'title' => 'World Physiotherapy Day',
                'description' => 'Svjetski dan fizioterapije',
                'type' => 'day',
                'category' => 'healthcare-workers',
                'color' => '#06b6d4'
            ],
            [
                'date' => '2026-09-10',
                'title' => 'World Suicide Prevention Day',
                'description' => 'Svjetski dan prevencije samoubistva',
                'type' => 'day',
                'category' => 'mental-health',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-09-21',
                'title' => 'World Alzheimer\'s Day',
                'description' => 'Svjetski dan Alchajmerove bolesti',
                'type' => 'day',
                'category' => 'neurological',
                'color' => '#8b5cf6'
            ],
            [
                'date' => '2026-09-25',
                'title' => 'World Pharmacist Day',
                'description' => 'Svjetski dan farmaceuta',
                'type' => 'day',
                'category' => 'healthcare-workers',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-09-26',
                'title' => 'World Contraception Day',
                'description' => 'Svjetski dan kontracepcije',
                'type' => 'day',
                'category' => 'reproductive-health',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-09-28',
                'title' => 'World Rabies Day',
                'description' => 'Svjetski dan bjesnoće',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],
            [
                'date' => '2026-09-29',
                'title' => 'World Heart Day',
                'description' => 'Svjetski dan srca',
                'type' => 'day',
                'category' => 'cardiovascular',
                'color' => '#ef4444'
            ],

            // OKTOBAR 2026
            [
                'date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'title' => 'Breast Cancer Awareness Month',
                'description' => 'Mjesec svjesnosti o raku dojke',
                'type' => 'month',
                'category' => 'cancer',
                'color' => '#ec4899'
            ],
            [
                'date' => '2026-10-01',
                'title' => 'International Day of Older Persons',
                'description' => 'Međunarodni dan starijih osoba',
                'type' => 'day',
                'category' => 'elderly-care',
                'color' => '#8b5cf6'
            ],
            [
                'date' => '2026-10-10',
                'title' => 'World Mental Health Day',
                'description' => 'Svjetski dan mentalnog zdravlja',
                'type' => 'day',
                'category' => 'mental-health',
                'color' => '#10b981'
            ],
            [
                'date' => '2026-10-12',
                'title' => 'World Arthritis Day',
                'description' => 'Svjetski dan artritisa',
                'type' => 'day',
                'category' => 'rheumatology',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-10-15',
                'title' => 'Global Handwashing Day',
                'description' => 'Globalni dan pranja ruku',
                'type' => 'day',
                'category' => 'prevention',
                'color' => '#06b6d4'
            ],
            [
                'date' => '2026-10-16',
                'title' => 'World Food Day',
                'description' => 'Svjetski dan hrane',
                'type' => 'day',
                'category' => 'nutrition',
                'color' => '#f59e0b'
            ],
            [
                'date' => '2026-10-20',
                'title' => 'World Osteoporosis Day',
                'description' => 'Svjetski dan osteoporoze',
                'type' => 'day',
                'category' => 'bone-health',
                'color' => '#94a3b8'
            ],
            [
                'date' => '2026-10-24',
                'title' => 'World Polio Day',
                'description' => 'Svjetski dan dječije paralize',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#f59e0b'
            ],
            [
                'date' => '2026-10-29',
                'title' => 'World Stroke Day',
                'description' => 'Svjetski dan moždanog udara',
                'type' => 'day',
                'category' => 'cardiovascular',
                'color' => '#ef4444'
            ],

            // NOVEMBAR 2026
            [
                'date' => '2026-11-03',
                'end_date' => '2026-12-03',
                'title' => 'Disability Rights Awareness Month',
                'description' => 'Mjesec svjesnosti o pravima osoba sa invaliditetom',
                'type' => 'month',
                'category' => 'disability',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-11-14',
                'title' => 'World Diabetes Day',
                'description' => 'Svjetski dan dijabetesa',
                'type' => 'day',
                'category' => 'metabolic',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-11-17',
                'title' => 'World Prematurity Day',
                'description' => 'Svjetski dan prevremeno rođene djece',
                'type' => 'day',
                'category' => 'maternal-health',
                'color' => '#a855f7'
            ],
            [
                'date' => '2026-11-18',
                'title' => 'World COPD Day',
                'description' => 'Svjetski dan hronične opstruktivne bolesti pluća',
                'type' => 'day',
                'category' => 'respiratory',
                'color' => '#06b6d4'
            ],
            [
                'date' => '2026-11-25',
                'title' => 'International Day for Elimination of Violence against Women',
                'description' => 'Međunarodni dan za eliminaciju nasilja nad ženama',
                'type' => 'day',
                'category' => 'womens-health',
                'color' => '#ec4899'
            ],

            // DECEMBAR 2026
            [
                'date' => '2026-12-01',
                'end_date' => '2027-01-31',
                'title' => 'SunSmart Skin Cancer Awareness Month',
                'description' => 'Mjesec svjesnosti o raku kože',
                'type' => 'month',
                'category' => 'cancer',
                'color' => '#f59e0b'
            ],
            [
                'date' => '2026-12-01',
                'title' => 'World AIDS Day',
                'description' => 'Svjetski dan AIDS-a',
                'type' => 'day',
                'category' => 'infectious-disease',
                'color' => '#dc2626'
            ],
            [
                'date' => '2026-12-03',
                'title' => 'International Day of Persons with Disabilities',
                'description' => 'Međunarodni dan osoba sa invaliditetom',
                'type' => 'day',
                'category' => 'disability',
                'color' => '#0891b2'
            ],
            [
                'date' => '2026-12-10',
                'title' => 'International Human Rights Day',
                'description' => 'Međunarodni dan ljudskih prava',
                'type' => 'day',
                'category' => 'human-rights',
                'color' => '#8b5cf6'
            ],
            [
                'date' => '2026-12-12',
                'title' => 'Universal Health Coverage Day',
                'description' => 'Dan univerzalnog zdravstvenog pokrića',
                'type' => 'day',
                'category' => 'public-health',
                'color' => '#10b981'
            ],
        ];

        foreach ($events as $index => $event) {
            MedicalCalendar::create(array_merge($event, ['sort_order' => $index]));
        }
    }
}
