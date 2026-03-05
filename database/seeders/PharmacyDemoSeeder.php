<?php

namespace Database\Seeders;

use App\Models\ApotekaFirma;
use App\Models\ApotekaPoslovnica;
use App\Models\ApotekaRadnoVrijeme;
use App\Models\Grad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PharmacyDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'pharmacy_owner',
            'guard_name' => 'web',
        ]);

        $pharmacies = [
            [
                'brand' => 'Vita Plus',
                'ime' => 'Amina',
                'prezime' => 'Hadzic',
                'email' => 'apoteka.sarajevo@wizmedik.test',
                'password' => 'WizPharm!2026A',
                'telefon' => '+38761111001',
                'grad' => 'Sarajevo',
                'branches' => [
                    [
                        'naziv' => 'Vita Plus Centar',
                        'adresa' => 'Titova 12',
                        'postanski_broj' => '71000',
                        'latitude' => 43.8563,
                        'longitude' => 18.4131,
                        'is_24h' => false,
                    ],
                    [
                        'naziv' => 'Vita Plus Grbavica',
                        'adresa' => 'Zagrebacka 18',
                        'postanski_broj' => '71000',
                        'latitude' => 43.8501,
                        'longitude' => 18.3925,
                        'is_24h' => true,
                    ],
                ],
            ],
            [
                'brand' => 'Farmis Nova',
                'ime' => 'Marko',
                'prezime' => 'Petrovic',
                'email' => 'apoteka.banjaluka@wizmedik.test',
                'password' => 'WizPharm!2026B',
                'telefon' => '+38765111002',
                'grad' => 'Banja Luka',
                'branches' => [
                    [
                        'naziv' => 'Farmis Nova Centar',
                        'adresa' => 'Kralja Petra I Karadjordjevica 55',
                        'postanski_broj' => '78000',
                        'latitude' => 44.7722,
                        'longitude' => 17.1910,
                        'is_24h' => false,
                    ],
                    [
                        'naziv' => 'Farmis Nova Borik',
                        'adresa' => 'Bulevar vojvode Stepe Stepanovica 101',
                        'postanski_broj' => '78000',
                        'latitude' => 44.7804,
                        'longitude' => 17.2086,
                        'is_24h' => false,
                    ],
                ],
            ],
            [
                'brand' => 'Medica Tuzla',
                'ime' => 'Lejla',
                'prezime' => 'Selimovic',
                'email' => 'apoteka.tuzla@wizmedik.test',
                'password' => 'WizPharm!2026C',
                'telefon' => '+38761111003',
                'grad' => 'Tuzla',
                'branches' => [
                    [
                        'naziv' => 'Medica Tuzla Slatina',
                        'adresa' => 'Aleja Alije Izetbegovica 23',
                        'postanski_broj' => '75000',
                        'latitude' => 44.5370,
                        'longitude' => 18.6764,
                        'is_24h' => true,
                    ],
                ],
            ],
        ];

        foreach ($pharmacies as $item) {
            $this->seedPharmacy($item);
        }
    }

    private function seedPharmacy(array $item): void
    {
        $city = Grad::query()
            ->whereRaw('LOWER(naziv) = ?', [mb_strtolower($item['grad'])])
            ->first();

        $user = User::query()->updateOrCreate(
            ['email' => $item['email']],
            [
                'name' => trim($item['ime'] . ' ' . $item['prezime']),
                'ime' => $item['ime'],
                'prezime' => $item['prezime'],
                'telefon' => $item['telefon'],
                'grad' => $item['grad'],
                'role' => 'pharmacy_owner',
                'password' => Hash::make($item['password']),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $user->syncRoles(['pharmacy_owner']);

        $firma = ApotekaFirma::query()->updateOrCreate(
            ['owner_user_id' => $user->id],
            [
                'naziv_brenda' => $item['brand'],
                'pravni_naziv' => $item['brand'] . ' d.o.o.',
                'telefon' => $item['telefon'],
                'email' => $item['email'],
                'opis' => 'Testna apoteka kreirana za demo i QA provjeru.',
                'status' => 'verified',
                'is_active' => true,
                'verified_at' => Carbon::now(),
                'verified_by' => null,
            ]
        );

        foreach ($item['branches'] as $branchData) {
            $branch = ApotekaPoslovnica::query()->firstOrNew([
                'firma_id' => $firma->id,
                'naziv' => $branchData['naziv'],
            ]);

            if (!$branch->exists || empty($branch->slug)) {
                $branch->slug = ApotekaPoslovnica::generateUniqueSlug(
                    $branchData['naziv'],
                    $branch->exists ? $branch->id : null
                );
            }

            $branch->grad_id = $city?->id;
            $branch->grad_naziv = $item['grad'];
            $branch->adresa = $branchData['adresa'];
            $branch->postanski_broj = $branchData['postanski_broj'];
            $branch->latitude = $branchData['latitude'];
            $branch->longitude = $branchData['longitude'];
            $branch->telefon = $item['telefon'];
            $branch->email = $item['email'];
            $branch->kratki_opis = $item['brand'] . ' poslovnica - test profil.';
            $branch->google_maps_link = 'https://www.google.com/maps/search/?api=1&query=' .
                rawurlencode($branchData['adresa'] . ', ' . $item['grad']);
            $branch->ima_dostavu = true;
            $branch->ima_parking = true;
            $branch->pristup_invalidima = true;
            $branch->is_24h = (bool) $branchData['is_24h'];
            $branch->is_active = true;
            $branch->is_verified = true;
            $branch->verified_at = Carbon::now();
            $branch->verified_by = null;
            $branch->save();

            $this->seedWorkingHours($branch->id, $branch->is_24h);
        }
    }

    private function seedWorkingHours(int $branchId, bool $is24h): void
    {
        for ($day = 1; $day <= 7; $day++) {
            $closed = $is24h ? false : ($day === 7);
            ApotekaRadnoVrijeme::query()->updateOrCreate(
                [
                    'poslovnica_id' => $branchId,
                    'day_of_week' => $day,
                ],
                [
                    'open_time' => $closed ? null : ($is24h ? '00:00' : '08:00'),
                    'close_time' => $closed ? null : ($is24h ? '23:59' : '21:00'),
                    'closed' => $closed,
                ]
            );
        }
    }
}

