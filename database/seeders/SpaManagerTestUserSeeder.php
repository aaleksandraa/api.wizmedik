<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Banja;
use Illuminate\Support\Facades\Hash;

class SpaManagerTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if test user already exists
        $existingUser = User::where('email', 'info@banjavrucica.com')->first();

        if ($existingUser) {
            $this->command->info('Test spa manager user already exists.');
            return;
        }

        // Create test user for Banja Vrućica
        $user = User::create([
            'name' => 'Marko Marković',
            'email' => 'info@banjavrucica.com',
            'password' => Hash::make('password123'),
            'role' => 'spa_manager',
            'email_verified_at' => now(),
        ]);

        // Link user to existing Banja Vrućica
        $banja = Banja::where('slug', 'banja-vrucica')->first();

        if ($banja) {
            $banja->update(['user_id' => $user->id]);
            $this->command->info('Test spa manager user created and linked to Banja Vrućica.');
            $this->command->info('Email: info@banjavrucica.com');
            $this->command->info('Password: password123');
        } else {
            $this->command->warn('Banja Vrućica not found. User created but not linked.');
        }
    }
}
