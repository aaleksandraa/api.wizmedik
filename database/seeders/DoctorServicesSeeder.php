<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Services for different specialties
        $services = [
            // Kardiologija - Dr. Amir Hodžić (ID: 1)
            1 => [
                'kategorije' => [
                    [
                        'naziv' => 'Dijagnostika',
                        'opis' => 'Dijagnostičke procedure i pregledi',
                        'usluge' => [
                            ['naziv' => 'EKG pregled', 'opis' => 'Elektrokardiografski pregled srca', 'cijena' => 30, 'trajanje_minuti' => 20],
                            ['naziv' => 'Holter EKG 24h', 'opis' => '24-satno praćenje rada srca', 'cijena' => 80, 'trajanje_minuti' => 30],
                            ['naziv' => 'Ehokardiografija', 'opis' => 'Ultrazvučni pregled srca', 'cijena' => 100, 'trajanje_minuti' => 45],
                            ['naziv' => 'Ergometrija', 'opis' => 'Test opterećenja na traci', 'cijena' => 70, 'trajanje_minuti' => 40],
                        ]
                    ],
                    [
                        'naziv' => 'Konsultacije',
                        'opis' => 'Kardiološke konsultacije i savjeti',
                        'usluge' => [
                            ['naziv' => 'Prvi kardiološki pregled', 'opis' => 'Detaljan pregled sa anamnezom', 'cijena' => 60, 'trajanje_minuti' => 45],
                            ['naziv' => 'Kontrolni pregled', 'opis' => 'Kontrola nakon terapije', 'cijena' => 40, 'trajanje_minuti' => 30],
                            ['naziv' => 'Konsultacije za hipertenziju', 'opis' => 'Savjeti i terapija za visoki krvni pritisak', 'cijena' => 50, 'trajanje_minuti' => 30],
                        ]
                    ]
                ]
            ],

            // Kardiologija - Dr. Lejla Karić (ID: 2)
            2 => [
                'kategorije' => [
                    [
                        'naziv' => 'Preventivni pregledi',
                        'opis' => 'Prevencija kardiovaskularnih bolesti',
                        'usluge' => [
                            ['naziv' => 'Kardiovaskularni check-up', 'opis' => 'Kompletan pregled srca i krvnih sudova', 'cijena' => 150, 'cijena_popust' => 120, 'trajanje_minuti' => 60],
                            ['naziv' => 'Procjena kardiovaskularnog rizika', 'opis' => 'Analiza faktora rizika', 'cijena' => 50, 'trajanje_minuti' => 30],
                        ]
                    ]
                ]
            ],

            // Opšta medicina - Dr. Amina Begić (ID: 4)
            4 => [
                'kategorije' => [
                    [
                        'naziv' => 'Opšti pregledi',
                        'opis' => 'Osnovni zdravstveni pregledi',
                        'usluge' => [
                            ['naziv' => 'Sistematski pregled', 'opis' => 'Kompletan zdravstveni pregled', 'cijena' => 50, 'trajanje_minuti' => 45],
                            ['naziv' => 'Pregled djece', 'opis' => 'Pedijatrijski pregled', 'cijena' => 40, 'trajanje_minuti' => 30],
                            ['naziv' => 'Vakcinacija', 'opis' => 'Primjena vakcina', 'cijena' => 30, 'trajanje_minuti' => 15],
                        ]
                    ],
                    [
                        'naziv' => 'Laboratorijske analize',
                        'opis' => 'Osnovne laboratorijske pretrage',
                        'usluge' => [
                            ['naziv' => 'Kompletna krvna slika', 'opis' => 'Analiza krvi', 'cijena' => 25, 'trajanje_minuti' => 10],
                            ['naziv' => 'Biohemijske analize', 'opis' => 'Šećer, holesterol, trigliceridi', 'cijena' => 35, 'trajanje_minuti' => 10],
                        ]
                    ]
                ]
            ],

            // Interna medicina - Dr. Adnan Muratović (ID: 7)
            7 => [
                'kategorije' => [
                    [
                        'naziv' => 'Internistički pregledi',
                        'opis' => 'Pregledi internih organa',
                        'usluge' => [
                            ['naziv' => 'Prvi internistički pregled', 'opis' => 'Detaljan pregled sa dijagnostikom', 'cijena' => 70, 'trajanje_minuti' => 60],
                            ['naziv' => 'Kontrolni pregled', 'opis' => 'Praćenje terapije i stanja', 'cijena' => 45, 'trajanje_minuti' => 30],
                            ['naziv' => 'Ultrazvuk abdomena', 'opis' => 'UZ pregled trbušnih organa', 'cijena' => 60, 'trajanje_minuti' => 30],
                        ]
                    ],
                    [
                        'naziv' => 'Dijabetologija',
                        'opis' => 'Liječenje i praćenje dijabetesa',
                        'usluge' => [
                            ['naziv' => 'Dijabetološka kontrola', 'opis' => 'Praćenje šećerne bolesti', 'cijena' => 50, 'trajanje_minuti' => 30],
                            ['naziv' => 'Edukacija o dijabetesu', 'opis' => 'Savjeti o ishrani i terapiji', 'cijena' => 40, 'trajanje_minuti' => 45],
                        ]
                    ],
                    [
                        'naziv' => 'Gastroenterologija',
                        'opis' => 'Bolesti digestivnog sistema',
                        'usluge' => [
                            ['naziv' => 'Gastroenterološki pregled', 'opis' => 'Pregled probavnog sistema', 'cijena' => 60, 'trajanje_minuti' => 45],
                            ['naziv' => 'H. pylori test', 'opis' => 'Dijagnostika bakterije H. pylori', 'cijena' => 35, 'trajanje_minuti' => 15],
                        ]
                    ]
                ]
            ],

            // Angiologija - Dr. Mirza Softić (ID: 8)
            8 => [
                'kategorije' => [
                    [
                        'naziv' => 'Vaskularna dijagnostika',
                        'opis' => 'Pregledi krvnih sudova',
                        'usluge' => [
                            ['naziv' => 'Color Doppler arterija', 'opis' => 'UZ pregled arterija nogu', 'cijena' => 80, 'trajanje_minuti' => 40],
                            ['naziv' => 'Color Doppler vena', 'opis' => 'UZ pregled vena nogu', 'cijena' => 70, 'trajanje_minuti' => 35],
                            ['naziv' => 'Angiološki pregled', 'opis' => 'Kompletan pregled krvotoka', 'cijena' => 90, 'trajanje_minuti' => 50],
                        ]
                    ]
                ]
            ],

            // Vaskularna hirurgija - Dr. Haris Imamović (ID: 9)
            9 => [
                'kategorije' => [
                    [
                        'naziv' => 'Hirurške konsultacije',
                        'opis' => 'Priprema za vaskularne operacije',
                        'usluge' => [
                            ['naziv' => 'Preoperativna konsultacija', 'opis' => 'Priprema za vaskularnu operaciju', 'cijena' => 100, 'trajanje_minuti' => 60],
                            ['naziv' => 'Postoperativna kontrola', 'opis' => 'Kontrola nakon operacije', 'cijena' => 60, 'trajanje_minuti' => 30],
                        ]
                    ],
                    [
                        'naziv' => 'Tretmani varikoznih vena',
                        'opis' => 'Liječenje proširenih vena',
                        'usluge' => [
                            ['naziv' => 'Skleroterapija', 'opis' => 'Tretman varikoznih vena injekcijama', 'cijena' => 150, 'trajanje_minuti' => 45],
                            ['naziv' => 'Laser tretman vena', 'opis' => 'Lasersko uklanjanje varikoznih vena', 'cijena' => 300, 'cijena_popust' => 250, 'trajanje_minuti' => 60],
                        ]
                    ]
                ]
            ],
        ];

        foreach ($services as $doctorId => $data) {
            if (isset($data['kategorije'])) {
                foreach ($data['kategorije'] as $index => $kategorija) {
                    // Create category
                    $categoryId = DB::table('doktor_kategorije_usluga')->insertGetId([
                        'doktor_id' => $doctorId,
                        'naziv' => $kategorija['naziv'],
                        'opis' => $kategorija['opis'] ?? null,
                        'redoslijed' => $index,
                        'aktivan' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Create services for this category
                    foreach ($kategorija['usluge'] as $serviceIndex => $usluga) {
                        DB::table('usluge')->insert([
                            'doktor_id' => $doctorId,
                            'kategorija_id' => $categoryId,
                            'naziv' => $usluga['naziv'],
                            'opis' => $usluga['opis'] ?? null,
                            'cijena' => $usluga['cijena'] ?? null,
                            'cijena_popust' => $usluga['cijena_popust'] ?? null,
                            'trajanje_minuti' => $usluga['trajanje_minuti'] ?? 30,
                            'redoslijed' => $serviceIndex,
                            'aktivan' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('✅ Doctor services seeded successfully!');
    }
}
