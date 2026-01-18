<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banja;

class UpdateBanjeRadnoVrijemeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🕐 Ažuriranje radnog vremena banja...');

        $radnoVrijeme = [
            'ponedeljak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
            'utorak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
            'srijeda' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
            'cetvrtak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
            'petak' => ['open' => '07:00', 'close' => '20:00', 'closed' => false],
            'subota' => ['open' => '08:00', 'close' => '18:00', 'closed' => false],
            'nedjelja' => ['open' => '08:00', 'close' => '14:00', 'closed' => false],
        ];

        $banje = Banja::all();

        foreach ($banje as $banja) {
            $banja->update(['radno_vrijeme' => $radnoVrijeme]);
            $this->command->info("  ✓ Ažurirano radno vrijeme za: {$banja->naziv}");
        }

        $this->command->info('✅ Radno vrijeme uspješno ažurirano za sve banje!');
    }
}
