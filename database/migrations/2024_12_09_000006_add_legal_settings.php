<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SiteSetting;

return new class extends Migration
{
    public function up(): void
    {
        // Cookie consent settings
        SiteSetting::set('cookie_consent_text', 'Koristimo kolačiće i slične tehnologije kako bismo poboljšali vaše korisničko iskustvo, analizirali saobraćaj na stranici i pružili personalizovani sadržaj. Klikom na "Prihvati" pristajete na upotrebu kolačića u skladu s našom Politikom privatnosti.');
        SiteSetting::set('cookie_accept_button', 'Prihvati');
        SiteSetting::set('cookie_reject_button', 'Odbij');
        SiteSetting::set('cookie_consent_enabled', 'true');

        // Privacy Policy
        SiteSetting::set('privacy_policy_title', 'Politika privatnosti');
        SiteSetting::set('privacy_policy_content', '<h2>1. Uvod</h2><p>Ova Politika privatnosti opisuje kako prikupljamo, koristimo i štitimo vaše lične podatke.</p><h2>2. Podaci koje prikupljamo</h2><p>Prikupljamo podatke koje nam direktno pružate prilikom registracije, zakazivanja termina ili kontaktiranja.</p><h2>3. Kako koristimo vaše podatke</h2><p>Vaše podatke koristimo za pružanje usluga, poboljšanje korisničkog iskustva i komunikaciju s vama.</p><h2>4. Zaštita podataka</h2><p>Primjenjujemo odgovarajuće tehničke i organizacione mjere za zaštitu vaših podataka.</p><h2>5. Kontakt</h2><p>Za sva pitanja u vezi s privatnošću, kontaktirajte nas putem emaila.</p>');

        // Terms of Service
        SiteSetting::set('terms_of_service_title', 'Uslovi korištenja');
        SiteSetting::set('terms_of_service_content', '<h2>1. Prihvatanje uslova</h2><p>Korištenjem ove platforme prihvatate ove Uslove korištenja.</p><h2>2. Opis usluge</h2><p>Platforma omogućava pretragu doktora i klinika te zakazivanje termina.</p><h2>3. Korisnički nalozi</h2><p>Odgovorni ste za čuvanje povjerljivosti vašeg naloga i lozinke.</p><h2>4. Pravila ponašanja</h2><p>Obavezujete se da nećete zloupotrebljavati platformu niti kršiti prava drugih korisnika.</p><h2>5. Ograničenje odgovornosti</h2><p>Platforma ne preuzima odgovornost za medicinske savjete ili odluke donesene na osnovu informacija na platformi.</p><h2>6. Izmjene uslova</h2><p>Zadržavamo pravo izmjene ovih uslova u bilo kojem trenutku.</p>');
    }

    public function down(): void
    {
        // Remove settings
        \DB::table('site_settings')->whereIn('key', [
            'cookie_consent_text', 'cookie_accept_button', 'cookie_reject_button', 'cookie_consent_enabled',
            'privacy_policy_title', 'privacy_policy_content',
            'terms_of_service_title', 'terms_of_service_content'
        ])->delete();
    }
};
