<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Doktor;
use App\Models\Klinika;

class UpdateDoctorRatings extends Command
{
    protected $signature = 'ratings:update';
    protected $description = 'Update doctor and clinic ratings based on actual reviews';

    public function handle()
    {
        $this->info('Updating doctor ratings...');
        
        foreach (Doktor::all() as $doktor) {
            $count = $doktor->recenzije()->count();
            $avg = $doktor->recenzije()->avg('ocjena');
            
            $doktor->update([
                'ocjena' => $avg ? round($avg, 1) : 0,
                'broj_ocjena' => $count
            ]);
            
            $this->line("Dr. {$doktor->ime} {$doktor->prezime}: {$avg} ({$count} recenzija)");
        }
        
        $this->info('Updating clinic ratings...');
        
        foreach (Klinika::all() as $klinika) {
            $count = $klinika->recenzije()->count();
            $avg = $klinika->recenzije()->avg('ocjena');
            
            $klinika->update([
                'ocjena' => $avg ? round($avg, 1) : 0,
                'broj_ocjena' => $count
            ]);
            
            $this->line("{$klinika->naziv}: {$avg} ({$count} recenzija)");
        }
        
        $this->info('All ratings updated successfully!');
        
        return 0;
    }
}
