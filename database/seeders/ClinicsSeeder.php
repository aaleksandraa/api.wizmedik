<?php

namespace Database\Seeders;

use App\Models\Klinika;
use Illuminate\Database\Seeder;

class ClinicsSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = [
            [
                'naziv' => 'Klinički centar Univerziteta u Sarajevu',
                'slug' => 'kcus',
                'opis' => 'Najveća zdravstvena ustanova u BiH.',
                'adresa' => 'Bolnička 25',
                'grad' => 'Sarajevo',
                'telefon' => '+387 33 297 000',
                'email' => 'info@kcus.ba',
                'website' => 'https://kcus.ba',
                'radno_vrijeme' => [
                    'ponedeljak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'utorak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'sreda' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'četvrtak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'petak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'subota' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'nedelja' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                ],
                'aktivan' => true,
            ],
            [
                'naziv' => 'UKC Republike Srpske',
                'slug' => 'ukc-rs',
                'opis' => 'Univerzitetski klinički centar Republike Srpske.',
                'adresa' => 'Dvanaest beba bb',
                'grad' => 'Banja Luka',
                'telefon' => '+387 51 342 100',
                'email' => 'info@kc-bl.com',
                'website' => 'https://kc-bl.com',
                'radno_vrijeme' => [
                    'ponedeljak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'utorak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'sreda' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'četvrtak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'petak' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'subota' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                    'nedelja' => ['open' => '00:00', 'close' => '23:59', 'closed' => false],
                ],
                'aktivan' => true,
            ],
            [
                'naziv' => 'UKC Tuzla',
                'slug' => 'ukc-tuzla',
                'opis' => 'Univerzitetski klinički centar Tuzla.',
                'adresa' => 'Trg dr. Tihomila Markovića 1',
                'grad' => 'Tuzla',
                'telefon' => '+387 35 303 100',
                'email' => 'info@ukctuzla.ba',
                'website' => 'https://ukctuzla.ba',
                'radno_vrijeme' => [
                    'ponedeljak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
                    'utorak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
                    'sreda' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
                    'četvrtak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
                    'petak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
                    'subota' => ['open' => '08:00', 'close' => '15:00', 'closed' => false],
                    'nedelja' => ['open' => '08:00', 'close' => '15:00', 'closed' => true],
                ],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Dom zdravlja Centar Sarajevo',
                'slug' => 'dom-zdravlja-centar',
                'opis' => 'Primarna zdravstvena zaštita u centru Sarajeva.',
                'adresa' => 'Vrazova 11',
                'grad' => 'Sarajevo',
                'telefon' => '+387 33 214 600',
                'email' => 'info@dzcentar.ba',
                'website' => 'https://dzcentar.ba',
                'radno_vrijeme' => [
                    'ponedeljak' => ['open' => '07:00', 'close' => '19:00', 'closed' => false],
                    'utorak' => ['open' => '07:00', 'close' => '19:00', 'closed' => false],
                    'sreda' => ['open' => '07:00', 'close' => '19:00', 'closed' => false],
                    'četvrtak' => ['open' => '07:00', 'close' => '19:00', 'closed' => false],
                    'petak' => ['open' => '07:00', 'close' => '19:00', 'closed' => false],
                    'subota' => ['open' => '08:00', 'close' => '13:00', 'closed' => false],
                    'nedelja' => ['open' => '08:00', 'close' => '13:00', 'closed' => true],
                ],
                'aktivan' => true,
            ],
            [
                'naziv' => 'Specijalna bolnica Medico',
                'slug' => 'specijalna-bolnica-medico',
                'opis' => 'Specijalizovana medicinska ustanova.',
                'adresa' => 'Zmaja od Bosne 28',
                'grad' => 'Sarajevo',
                'telefon' => '+387 33 564 800',
                'email' => 'info@medico.ba',
                'website' => 'https://medico.ba',
                'radno_vrijeme' => [
                    'ponedeljak' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'utorak' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'sreda' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'četvrtak' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'petak' => ['open' => '08:00', 'close' => '20:00', 'closed' => false],
                    'subota' => ['open' => '09:00', 'close' => '15:00', 'closed' => false],
                    'nedelja' => ['open' => '09:00', 'close' => '15:00', 'closed' => true],
                ],
                'aktivan' => true,
            ],
        ];

        foreach ($clinics as $clinic) {
            $klinika = Klinika::updateOrCreate(
                ['slug' => $clinic['slug']],
                $clinic
            );

            // Attach specialty to clinic via pivot table
            if (isset($clinic['specijalnost_id'])) {
                $klinika->specijalnosti()->syncWithoutDetaching($clinic['specijalnost_id']);
            }
        }
    }
}

