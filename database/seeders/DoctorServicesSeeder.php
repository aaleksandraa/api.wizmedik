<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorServicesSeeder extends Seeder
{
    /**
     * Seed test service catalogues for every doctor, keyed by specialty.
     * Doctors that already have categories/services are skipped.
     */
    public function run(): void
    {
        $catalogBySpecialty = $this->catalogBySpecialty();
        $defaultCatalog = $this->defaultCatalog();

        $doctors = DB::table('doktori')
            ->select('id', 'ime', 'prezime', 'specijalnost')
            ->whereNull('deleted_at')
            ->get();

        $seeded = 0;
        $skipped = 0;

        foreach ($doctors as $doctor) {
            $existingCount = DB::table('usluge')
                ->where('doktor_id', $doctor->id)
                ->count();

            if ($existingCount > 0) {
                $skipped++;
                continue;
            }

            $specialty = trim((string) $doctor->specijalnost);
            $catalog = $catalogBySpecialty[$specialty] ?? $defaultCatalog;

            foreach ($catalog as $index => $kategorija) {
                $categoryId = DB::table('doktor_kategorije_usluga')->insertGetId([
                    'doktor_id' => $doctor->id,
                    'naziv' => $kategorija['naziv'],
                    'opis' => $kategorija['opis'] ?? null,
                    'redoslijed' => $index,
                    'aktivan' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($kategorija['usluge'] as $serviceIndex => $usluga) {
                    DB::table('usluge')->insert([
                        'doktor_id' => $doctor->id,
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

            $seeded++;
            $this->command?->info("  ✓ {$doctor->ime} {$doctor->prezime} ({$specialty})");
        }

        $this->command?->info("✅ Doctor services seeded: {$seeded} doctors, skipped {$skipped} with existing services.");
    }

    private function catalogBySpecialty(): array
    {
        return [
            'Kardiologija' => [
                [
                    'naziv' => 'Dijagnostika',
                    'opis' => 'Dijagnostičke procedure i pregledi',
                    'usluge' => [
                        ['naziv' => 'EKG pregled', 'opis' => 'Elektrokardiografski pregled srca', 'cijena' => 30, 'trajanje_minuti' => 20],
                        ['naziv' => 'Holter EKG 24h', 'opis' => '24-satno praćenje rada srca', 'cijena' => 80, 'trajanje_minuti' => 30],
                        ['naziv' => 'Ehokardiografija', 'opis' => 'Ultrazvučni pregled srca', 'cijena' => 100, 'trajanje_minuti' => 45],
                        ['naziv' => 'Ergometrija', 'opis' => 'Test opterećenja na traci', 'cijena' => 70, 'trajanje_minuti' => 40],
                    ],
                ],
                [
                    'naziv' => 'Konsultacije',
                    'opis' => 'Kardiološke konsultacije i savjeti',
                    'usluge' => [
                        ['naziv' => 'Prvi kardiološki pregled', 'opis' => 'Detaljan pregled sa anamnezom', 'cijena' => 60, 'trajanje_minuti' => 45],
                        ['naziv' => 'Kontrolni pregled', 'opis' => 'Kontrola nakon terapije', 'cijena' => 40, 'trajanje_minuti' => 30],
                        ['naziv' => 'Konsultacije za hipertenziju', 'opis' => 'Savjeti i terapija za visoki krvni pritisak', 'cijena' => 50, 'trajanje_minuti' => 30],
                    ],
                ],
            ],
            'Opšta medicina i porodična medicina' => [
                [
                    'naziv' => 'Opšti pregledi',
                    'opis' => 'Osnovni zdravstveni pregledi',
                    'usluge' => [
                        ['naziv' => 'Sistematski pregled', 'opis' => 'Kompletan zdravstveni pregled', 'cijena' => 50, 'trajanje_minuti' => 45],
                        ['naziv' => 'Prvi pregled', 'opis' => 'Pregled sa anamnezom', 'cijena' => 40, 'trajanje_minuti' => 30],
                        ['naziv' => 'Kontrolni pregled', 'opis' => 'Kontrola nakon terapije', 'cijena' => 30, 'trajanje_minuti' => 20],
                        ['naziv' => 'Vakcinacija', 'opis' => 'Primjena vakcina', 'cijena' => 30, 'trajanje_minuti' => 15],
                    ],
                ],
                [
                    'naziv' => 'Laboratorijske analize',
                    'opis' => 'Osnovne laboratorijske pretrage',
                    'usluge' => [
                        ['naziv' => 'Kompletna krvna slika', 'opis' => 'Analiza krvi', 'cijena' => 25, 'trajanje_minuti' => 10],
                        ['naziv' => 'Biohemijske analize', 'opis' => 'Šećer, holesterol, trigliceridi', 'cijena' => 35, 'trajanje_minuti' => 10],
                    ],
                ],
            ],
            'Interna medicina' => [
                [
                    'naziv' => 'Internistički pregledi',
                    'opis' => 'Pregledi internih organa',
                    'usluge' => [
                        ['naziv' => 'Prvi internistički pregled', 'opis' => 'Detaljan pregled sa dijagnostikom', 'cijena' => 70, 'trajanje_minuti' => 60],
                        ['naziv' => 'Kontrolni pregled', 'opis' => 'Praćenje terapije i stanja', 'cijena' => 45, 'trajanje_minuti' => 30],
                        ['naziv' => 'Ultrazvuk abdomena', 'opis' => 'UZ pregled trbušnih organa', 'cijena' => 60, 'trajanje_minuti' => 30],
                    ],
                ],
                [
                    'naziv' => 'Dijabetologija',
                    'opis' => 'Liječenje i praćenje dijabetesa',
                    'usluge' => [
                        ['naziv' => 'Dijabetološka kontrola', 'opis' => 'Praćenje šećerne bolesti', 'cijena' => 50, 'trajanje_minuti' => 30],
                        ['naziv' => 'Edukacija o dijabetesu', 'opis' => 'Savjeti o ishrani i terapiji', 'cijena' => 40, 'trajanje_minuti' => 45],
                    ],
                ],
            ],
            'Angiologija' => [
                [
                    'naziv' => 'Vaskularna dijagnostika',
                    'opis' => 'Pregledi krvnih sudova',
                    'usluge' => [
                        ['naziv' => 'Color Doppler arterija', 'opis' => 'UZ pregled arterija nogu', 'cijena' => 80, 'trajanje_minuti' => 40],
                        ['naziv' => 'Color Doppler vena', 'opis' => 'UZ pregled vena nogu', 'cijena' => 70, 'trajanje_minuti' => 35],
                        ['naziv' => 'Angiološki pregled', 'opis' => 'Kompletan pregled krvotoka', 'cijena' => 90, 'trajanje_minuti' => 50],
                    ],
                ],
            ],
            'Vaskularna hirurgija' => [
                [
                    'naziv' => 'Hirurške konsultacije',
                    'opis' => 'Priprema za vaskularne operacije',
                    'usluge' => [
                        ['naziv' => 'Preoperativna konsultacija', 'opis' => 'Priprema za vaskularnu operaciju', 'cijena' => 100, 'trajanje_minuti' => 60],
                        ['naziv' => 'Postoperativna kontrola', 'opis' => 'Kontrola nakon operacije', 'cijena' => 60, 'trajanje_minuti' => 30],
                    ],
                ],
                [
                    'naziv' => 'Tretmani varikoznih vena',
                    'opis' => 'Liječenje proširenih vena',
                    'usluge' => [
                        ['naziv' => 'Skleroterapija', 'opis' => 'Tretman varikoznih vena injekcijama', 'cijena' => 150, 'trajanje_minuti' => 45],
                        ['naziv' => 'Laser tretman vena', 'opis' => 'Lasersko uklanjanje varikoznih vena', 'cijena' => 300, 'cijena_popust' => 250, 'trajanje_minuti' => 60],
                    ],
                ],
            ],
            'Primarna zdravstvena zaštita' => [
                [
                    'naziv' => 'Primarna zaštita',
                    'opis' => 'Osnovne usluge primarne zdravstvene zaštite',
                    'usluge' => [
                        ['naziv' => 'Pregled porodičnog ljekara', 'opis' => 'Opšti pregled i savjetovanje', 'cijena' => 35, 'trajanje_minuti' => 25],
                        ['naziv' => 'Kontrolni pregled', 'opis' => 'Praćenje terapije', 'cijena' => 25, 'trajanje_minuti' => 15],
                        ['naziv' => 'Izdavanje uputnice', 'opis' => 'Uputnica za specijalistu ili laboratoriju', 'cijena' => 15, 'trajanje_minuti' => 10],
                        ['naziv' => 'Mjerenje pritiska i savjet', 'opis' => 'Kontrola krvnog pritiska', 'cijena' => 20, 'trajanje_minuti' => 15],
                    ],
                ],
            ],
        ];
    }

    private function defaultCatalog(): array
    {
        return [
            [
                'naziv' => 'Pregledi',
                'opis' => 'Standardne usluge pregleda',
                'usluge' => [
                    ['naziv' => 'Prvi pregled', 'opis' => 'Detaljan pregled sa anamnezom', 'cijena' => 50, 'trajanje_minuti' => 40],
                    ['naziv' => 'Kontrolni pregled', 'opis' => 'Kontrola nakon terapije', 'cijena' => 35, 'trajanje_minuti' => 25],
                    ['naziv' => 'Konsultacija', 'opis' => 'Stručno savjetovanje', 'cijena' => 40, 'trajanje_minuti' => 30],
                ],
            ],
        ];
    }
}
