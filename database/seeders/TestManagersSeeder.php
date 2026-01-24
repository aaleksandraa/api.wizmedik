<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Dom;
use App\Models\Banja;

class TestManagersSeeder extends Seeder
{
    /**
     * Kreira test korisnike za dom i banju sa login podacima
     *
     * LOGIN PODACI:
     *
     * DOM MANAGER:
     * Email: dom.test@wizmedik.com
     * Password: DomTest123!
     * Dashboard: /dom-dashboard
     *
     * SPA MANAGER:
     * Email: banja.test@wizmedik.com
     * Password: BanjaTest123!
     * Dashboard: /spa-dashboard
     */
    public function run(): void
    {
        $this->command->info('🔐 Kreiranje test korisnika za dom i banju...');

        // ========================================
        // DOM MANAGER
        // ========================================
        $domUser = User::updateOrCreate(
            ['email' => 'dom.test@wizmedik.com'],
            [
                'name' => 'Dom',
                'prezime' => 'Manager',
                'password' => Hash::make('DomTest123!'),
                'email_verified_at' => now(),
            ]
        );

        // Assign role
        if (!$domUser->hasRole('dom_manager')) {
            $domUser->assignRole('dom_manager');
        }

        // Provjeri da li postoji dom za ovog korisnika
        $existingDom = Dom::where('user_id', $domUser->id)->first();

        if (!$existingDom) {
            // Provjeri da li postoji prvi dom i dodijeli ga
            $dom = Dom::first();
            if ($dom) {
                $dom->update(['user_id' => $domUser->id]);
                $this->command->info("✅ Dom '{$dom->naziv}' dodijeljen korisniku dom.test@wizmedik.com");
            } else {
                // Kreiraj novi dom ako ne postoji nijedan
                $this->createTestDom($domUser->id);
            }
        } else {
            $this->command->info("ℹ️ Korisnik dom.test@wizmedik.com već ima dom: {$existingDom->naziv}");
        }

        // ========================================
        // SPA MANAGER
        // ========================================
        $spaUser = User::updateOrCreate(
            ['email' => 'banja.test@wizmedik.com'],
            [
                'name' => 'Banja',
                'prezime' => 'Manager',
                'password' => Hash::make('BanjaTest123!'),
                'email_verified_at' => now(),
            ]
        );

        // Assign role
        if (!$spaUser->hasRole('spa_manager')) {
            $spaUser->assignRole('spa_manager');
        }

        // Provjeri da li postoji banja za ovog korisnika
        $existingBanja = Banja::where('user_id', $spaUser->id)->first();

        if (!$existingBanja) {
            // Provjeri da li postoji prva banja i dodijeli je
            $banja = Banja::first();
            if ($banja) {
                $banja->update(['user_id' => $spaUser->id]);
                $this->command->info("✅ Banja '{$banja->naziv}' dodijeljena korisniku banja.test@wizmedik.com");
            } else {
                // Kreiraj novu banju ako ne postoji nijedna
                $this->createTestBanja($spaUser->id);
            }
        } else {
            $this->command->info("ℹ️ Korisnik banja.test@wizmedik.com već ima banju: {$existingBanja->naziv}");
        }

        $this->command->newLine();
        $this->command->info('========================================');
        $this->command->info('📋 LOGIN PODACI ZA TESTIRANJE:');
        $this->command->info('========================================');
        $this->command->newLine();
        $this->command->info('🏠 DOM MANAGER:');
        $this->command->info('   Email: dom.test@wizmedik.com');
        $this->command->info('   Password: DomTest123!');
        $this->command->info('   Dashboard: /dom-dashboard');
        $this->command->newLine();
        $this->command->info('🏨 SPA/BANJA MANAGER:');
        $this->command->info('   Email: banja.test@wizmedik.com');
        $this->command->info('   Password: BanjaTest123!');
        $this->command->info('   Dashboard: /spa-dashboard');
        $this->command->info('========================================');
    }

    private function createTestDom(int $userId): void
    {
        // Dohvati taxonomy IDs
        $tipDoma = DB::table('tipovi_domova')->where('slug', 'dom-starija-bolesna')->first();
        $nivoNjege = DB::table('nivoi_njege')->where('slug', 'stalna-24-7')->first();

        if (!$tipDoma || !$nivoNjege) {
            $this->command->warn('⚠️ Potrebno je prvo pokrenuti DomoviTaxonomySeeder');
            return;
        }

        $dom = Dom::create([
            'user_id' => $userId,
            'naziv' => 'Test Dom za Starije "Sunčani dom"',
            'slug' => 'test-dom-suncani-dom',
            'grad' => 'Sarajevo',
            'regija' => 'Sarajevska',
            'adresa' => 'Testna ulica 123, Sarajevo',
            'latitude' => 43.8563,
            'longitude' => 18.4131,
            'telefon' => '+387 33 111 222',
            'email' => 'info@suncani-dom.ba',
            'website' => 'https://suncani-dom.ba',
            'opis' => 'Moderni dom za starije osobe sa profesionalnom njegom i toplom atmosferom. Pružamo 24/7 medicinsku njegu i individualni pristup svakom štićeniku.',
            'detaljni_opis' => 'Test Dom "Sunčani dom" je moderna ustanova za njegu starijih osoba. Naš tim stručnjaka osigurava kvalitetnu njegu, medicinsku podršku i toplu atmosferu za sve naše štićenike.',
            'tip_doma_id' => $tipDoma->id,
            'nivo_njege_id' => $nivoNjege->id,
            'accepts_tags' => ['starije osobe', 'demencija', 'dijabetes'],
            'nurses_availability' => '24_7',
            'doctor_availability' => 'periodic',
            'has_physiotherapist' => true,
            'has_physiatrist' => false,
            'emergency_protocol' => true,
            'controlled_entry' => true,
            'video_surveillance' => true,
            'visiting_rules' => 'Posjete su dozvoljene svakodnevno od 10:00 do 20:00.',
            'pricing_mode' => 'public',
            'price_from' => 1000.00,
            'online_upit' => true,
            'verifikovan' => true,
            'aktivan' => true,
            'prosjecna_ocjena' => 4.5,
            'broj_recenzija' => 12,
            'broj_pregleda' => 150,
            'featured_slika' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800',
        ]);

        $this->command->info("✅ Kreiran test dom: {$dom->naziv}");
    }

    private function createTestBanja(int $userId): void
    {
        // Dohvati taxonomy IDs
        $vrsta = DB::table('vrste_banja')->first();

        if (!$vrsta) {
            $this->command->warn('⚠️ Potrebno je prvo pokrenuti BanjeTaxonomySeeder');
            return;
        }

        $banja = Banja::create([
            'user_id' => $userId,
            'naziv' => 'Test Banja "Termalni raj"',
            'slug' => 'test-banja-termalni-raj',
            'grad' => 'Fojnica',
            'regija' => 'Srednjobosanska',
            'adresa' => 'Banjska ulica 1, Fojnica',
            'latitude' => 43.9614,
            'longitude' => 17.8978,
            'telefon' => '+387 30 333 444',
            'email' => 'info@termalni-raj.ba',
            'website' => 'https://termalni-raj.ba',
            'opis' => 'Moderna banja sa termalnim izvorima i širokim spektrom wellness i medicinskih tretmana. Idealno mjesto za odmor i rehabilitaciju.',
            'detaljni_opis' => 'Test Banja "Termalni raj" nudi jedinstveno iskustvo wellness i rehabilitacije. Naši termalni izvori i stručni tim garantuju kvalitetne tretmane za sve goste.',
            'medicinski_nadzor' => true,
            'ima_smjestaj' => true,
            'online_rezervacija' => true,
            'verifikovan' => true,
            'aktivan' => true,
            'prosjecna_ocjena' => 4.7,
            'broj_recenzija' => 25,
            'broj_pregleda' => 320,
            'featured_slika' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800',
        ]);

        // Dodaj vrstu banje
        if ($vrsta) {
            DB::table('banja_vrste')->insert([
                'banja_id' => $banja->id,
                'vrsta_id' => $vrsta->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("✅ Kreirana test banja: {$banja->naziv}");
    }
}
