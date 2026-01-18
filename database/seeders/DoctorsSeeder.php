<?php

namespace Database\Seeders;

use App\Models\{Doktor, User, Specijalnost};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorsSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            // Kardiologija
            [
                'ime' => 'Amir',
                'prezime' => 'Hodžić',
                'slug' => 'amir-hodzic',
                'email' => 'amir.hodzic@example.com',
                'telefon' => '+387 61 123 456',
                'specijalnost' => 'Kardiologija',
                'specijalnost_slug' => 'kardiologija',
                'grad' => 'Sarajevo',
                'lokacija' => 'Alipašina 32',
                'opis' => 'Iskusni kardiolog sa 15 godina prakse. Specijaliziran za dijagnostiku i liječenje srčanih bolesti.',
                'ocjena' => 4.8,
                'broj_ocjena' => 45,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Amir+Hodzic&size=200&background=667eea&color=fff',
            ],
            [
                'ime' => 'Lejla',
                'prezime' => 'Karić',
                'slug' => 'lejla-karic',
                'email' => 'lejla.karic@example.com',
                'telefon' => '+387 61 234 567',
                'specijalnost' => 'Kardiologija',
                'specijalnost_slug' => 'kardiologija',
                'grad' => 'Banja Luka',
                'lokacija' => 'Kralja Petra 15',
                'opis' => 'Kardiolog sa specijalizacijom za preventivnu kardiologiju i rehabilitaciju.',
                'ocjena' => 4.9,
                'broj_ocjena' => 62,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Lejla+Karic&size=200&background=764ba2&color=fff',
            ],
            [
                'ime' => 'Emir',
                'prezime' => 'Softić',
                'slug' => 'emir-softic',
                'email' => 'emir.softic@example.com',
                'telefon' => '+387 61 345 678',
                'specijalnost' => 'Kardiologija',
                'specijalnost_slug' => 'kardiologija',
                'grad' => 'Tuzla',
                'lokacija' => 'Bratstva i jedinstva 1',
                'opis' => 'Kardiolog sa iskustvom u invazivnoj kardiologiji i kateterizaciji.',
                'ocjena' => 4.7,
                'broj_ocjena' => 38,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 45,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Emir+Softic&size=200&background=10b981&color=fff',
            ],

            // Opšta medicina
            [
                'ime' => 'Amina',
                'prezime' => 'Begić',
                'slug' => 'amina-begic',
                'email' => 'amina.begic@example.com',
                'telefon' => '+387 61 456 789',
                'specijalnost' => 'Opšta medicina i porodična medicina',
                'specijalnost_slug' => 'opsta-medicina-i-porodicna-medicina',
                'grad' => 'Sarajevo',
                'lokacija' => 'Zmaja od Bosne 8',
                'opis' => 'Doktor opšte medicine sa fokusom na porodičnu medicinu i preventivne preglede.',
                'ocjena' => 4.9,
                'broj_ocjena' => 51,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Amina+Begic&size=200&background=f59e0b&color=fff',
            ],
            [
                'ime' => 'Nermin',
                'prezime' => 'Halilović',
                'slug' => 'nermin-halilovic',
                'email' => 'nermin.halilovic@example.com',
                'telefon' => '+387 61 567 890',
                'specijalnost' => 'Opšta medicina i porodična medicina',
                'specijalnost_slug' => 'opsta-medicina-i-porodicna-medicina',
                'grad' => 'Mostar',
                'lokacija' => 'Maršala Tita 45',
                'opis' => 'Iskusan doktor opšte prakse sa 20 godina iskustva u primarnoj zdravstvenoj zaštiti.',
                'ocjena' => 4.8,
                'broj_ocjena' => 73,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Nermin+Halilovic&size=200&background=3b82f6&color=fff',
            ],

            // Interna medicina
            [
                'ime' => 'Selma',
                'prezime' => 'Džaferović',
                'slug' => 'selma-dzaferovic',
                'email' => 'selma.dzaferovic@example.com',
                'telefon' => '+387 61 678 901',
                'specijalnost' => 'Interna medicina',
                'specijalnost_slug' => 'interna-medicina',
                'grad' => 'Sarajevo',
                'lokacija' => 'Bolnička 25',
                'opis' => 'Specijalistkinja interne medicine sa iskustvom u dijagnostici i liječenju hroničnih bolesti.',
                'ocjena' => 4.7,
                'broj_ocjena' => 42,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Selma+Dzaferovic&size=200&background=8b5cf6&color=fff',
            ],
            [
                'ime' => 'Adnan',
                'prezime' => 'Muratović',
                'slug' => 'adnan-muratovic',
                'email' => 'adnan.muratovic@example.com',
                'telefon' => '+387 61 789 012',
                'specijalnost' => 'Interna medicina',
                'specijalnost_slug' => 'interna-medicina',
                'grad' => 'Zenica',
                'lokacija' => 'Crkvice 67',
                'opis' => 'Internista sa specijalizacijom za gastroenterologiju i hepatologiju.',
                'ocjena' => 4.8,
                'broj_ocjena' => 55,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Adnan+Muratovic&size=200&background=ec4899&color=fff',
            ],

            // Angiologija
            [
                'ime' => 'Mirza',
                'prezime' => 'Softić',
                'slug' => 'mirza-softic',
                'email' => 'mirza.softic@example.com',
                'telefon' => '+387 61 890 123',
                'specijalnost' => 'Angiologija',
                'specijalnost_slug' => 'angiologija',
                'grad' => 'Sarajevo',
                'lokacija' => 'Grbavička 8a',
                'opis' => 'Angiolog specijaliziran za dijagnostiku i liječenje bolesti krvnih sudova.',
                'ocjena' => 4.6,
                'broj_ocjena' => 28,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 45,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Mirza+Softic&size=200&background=14b8a6&color=fff',
            ],

            // Vaskularna hirurgija
            [
                'ime' => 'Haris',
                'prezime' => 'Imamović',
                'slug' => 'haris-imamovic',
                'email' => 'haris.imamovic@example.com',
                'telefon' => '+387 61 901 234',
                'specijalnost' => 'Vaskularna hirurgija',
                'specijalnost_slug' => 'vaskularna-hirurgija',
                'grad' => 'Banja Luka',
                'lokacija' => '12 beba bb',
                'opis' => 'Vaskularni hirurg sa iskustvom u endovaskularnim procedurama.',
                'ocjena' => 4.9,
                'broj_ocjena' => 34,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 60,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Haris+Imamovic&size=200&background=f97316&color=fff',
            ],

            // Primarna zdravstvena zaštita
            [
                'ime' => 'Jasmina',
                'prezime' => 'Softić',
                'slug' => 'jasmina-softic',
                'email' => 'jasmina.softic@example.com',
                'telefon' => '+387 61 012 345',
                'specijalnost' => 'Primarna zdravstvena zaštita',
                'specijalnost_slug' => 'primarna-zdravstvena-zastita',
                'grad' => 'Tuzla',
                'lokacija' => 'Turalibegova 15',
                'opis' => 'Doktor primarne zdravstvene zaštite sa dugogodišnjim iskustvom.',
                'ocjena' => 4.8,
                'broj_ocjena' => 67,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Jasmina+Softic&size=200&background=06b6d4&color=fff',
            ],
            [
                'ime' => 'Kenan',
                'prezime' => 'Softić',
                'slug' => 'kenan-softic',
                'email' => 'kenan.softic@example.com',
                'telefon' => '+387 61 123 456',
                'specijalnost' => 'Primarna zdravstvena zaštita',
                'specijalnost_slug' => 'primarna-zdravstvena-zastita',
                'grad' => 'Sarajevo',
                'lokacija' => 'Safeta Zajke 3',
                'opis' => 'Doktor primarne zdravstvene zaštite fokusiran na preventivnu medicinu.',
                'ocjena' => 4.7,
                'broj_ocjena' => 49,
                'prihvata_online' => true,
                'slot_trajanje_minuti' => 30,
                'slika_profila' => 'https://ui-avatars.com/api/?name=Kenan+Softic&size=200&background=84cc16&color=fff',
            ],
        ];

        foreach ($doctors as $doctorData) {
            // Find specialty by slug
            $specijalnost = Specijalnost::where('slug', $doctorData['specijalnost_slug'])->first();

            if (!$specijalnost) {
                $this->command->warn("Specijalnost '{$doctorData['specijalnost_slug']}' nije pronađena, preskačem doktora {$doctorData['ime']} {$doctorData['prezime']}");
                continue;
            }

            // Remove specijalnost_slug from data before creating
            unset($doctorData['specijalnost_slug']);

            // Create or find user account
            $user = User::firstOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['ime'] . ' ' . $doctorData['prezime'],
                    'ime' => $doctorData['ime'],
                    'prezime' => $doctorData['prezime'],
                    'password' => Hash::make('TestPassword123!'),
                    'role' => 'doctor',
                ]
            );

            if (!$user->hasRole('doctor')) {
                $user->assignRole('doctor');
            }

            // Create or update doctor profile
            $doctor = Doktor::updateOrCreate(
                ['slug' => $doctorData['slug']],
                [
                    ...$doctorData,
                    'specijalnost_id' => $specijalnost->id,
                    'user_id' => $user->id,
                    'radno_vrijeme' => [
                        'ponedjeljak' => ['closed' => false, 'open' => '08:00', 'close' => '16:00'],
                        'utorak' => ['closed' => false, 'open' => '08:00', 'close' => '16:00'],
                        'srijeda' => ['closed' => false, 'open' => '08:00', 'close' => '16:00'],
                        'četvrtak' => ['closed' => false, 'open' => '08:00', 'close' => '16:00'],
                        'petak' => ['closed' => false, 'open' => '08:00', 'close' => '14:00'],
                        'subota' => ['closed' => true, 'open' => '08:00', 'close' => '16:00'],
                        'nedjelja' => ['closed' => true, 'open' => '08:00', 'close' => '16:00'],
                    ],
                ]
            );

            // Attach specialty to doctor via pivot table
            $doctor->specijalnosti()->syncWithoutDetaching($specijalnost->id);
        }
    }
}
