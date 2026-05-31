<?php

namespace Tests\Feature;

use App\Models\Banja;
use App\Models\Doktor;
use App\Models\Dom;
use App\Models\Klinika;
use App\Models\Lijek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileSlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profiles_return_redirect_metadata_after_name_slug_changes(): void
    {
        $clinic = Klinika::create([
            'naziv' => 'Stara Klinika',
            'adresa' => 'Adresa 1',
            'grad' => 'Sarajevo',
            'telefon' => '061111111',
            'aktivan' => true,
            'verifikovan' => true,
        ]);
        $oldClinicSlug = $clinic->slug;
        $clinic->update(['naziv' => 'Nova Klinika']);
        $this->getJson("/api/clinics/{$oldClinicSlug}")
            ->assertOk()
            ->assertJsonPath('redirect_to', "/klinika/{$clinic->fresh()->slug}");

        $doctor = Doktor::create([
            'ime' => 'Stari',
            'prezime' => 'Doktor',
            'specijalnost' => 'Kardiolog',
            'grad' => 'Sarajevo',
            'lokacija' => 'Centar',
            'telefon' => '061111112',
            'aktivan' => true,
            'verifikovan' => true,
        ]);
        $oldDoctorSlug = $doctor->slug;
        $doctor->update(['ime' => 'Novi']);
        $this->getJson("/api/doctors/slug/{$oldDoctorSlug}")
            ->assertOk()
            ->assertJsonPath('redirect_to', "/doktor/{$doctor->fresh()->slug}");

        $spa = Banja::create([
            'naziv' => 'Stara Banja',
            'grad' => 'Fojnica',
            'adresa' => 'Adresa 2',
            'opis' => 'Opis banje',
            'aktivan' => true,
            'verifikovan' => true,
        ]);
        $oldSpaSlug = $spa->slug;
        $spa->update(['naziv' => 'Nova Banja']);
        $this->getJson("/api/banje/{$oldSpaSlug}")
            ->assertOk()
            ->assertJsonPath('redirect_to', "/banja/{$spa->fresh()->slug}");

        $tipDomaId = DB::table('tipovi_domova')->insertGetId([
            'naziv' => 'Privatni dom',
            'slug' => 'privatni-dom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $nivoNjegeId = DB::table('nivoi_njege')->insertGetId([
            'naziv' => 'Osnovna njega',
            'slug' => 'osnovna-njega',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $home = Dom::create([
            'naziv' => 'Stari Dom',
            'grad' => 'Mostar',
            'adresa' => 'Adresa 3',
            'opis' => 'Opis doma',
            'tip_doma_id' => $tipDomaId,
            'nivo_njege_id' => $nivoNjegeId,
            'aktivan' => true,
            'verifikovan' => true,
        ]);
        $oldHomeSlug = $home->slug;
        $home->update(['naziv' => 'Novi Dom']);
        $this->getJson("/api/domovi-njega/{$oldHomeSlug}")
            ->assertOk()
            ->assertJsonPath('redirect_to', "/dom-njega/{$home->fresh()->slug}");

        $medicine = Lijek::create([
            'lijek_id' => 900001,
            'naziv' => 'Stari Lijek',
        ]);
        $oldMedicineSlug = $medicine->slug;
        $medicine->update(['naziv' => 'Novi Lijek']);
        $this->getJson("/api/lijekovi/{$oldMedicineSlug}")
            ->assertOk()
            ->assertJsonPath('redirect_to', "/lijekovi/{$medicine->fresh()->slug}");
    }
}
