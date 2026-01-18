<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create default admin user (if not exists)
        $admin = User::firstOrCreate(
            ['email' => 'admin@wizmedik.com'],
            [
                'name' => 'Admin User',
                'ime' => 'Admin',
                'prezime' => 'User',
                'password' => bcrypt('AdminPassword123!'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create test patient (if not exists)
        $patient = User::firstOrCreate(
            ['email' => 'patient@example.com'],
            [
                'name' => 'Test Patient',
                'ime' => 'Test',
                'prezime' => 'Patient',
                'password' => bcrypt('PatientPassword123!'),
                'role' => 'patient',
                'email_verified_at' => now(),
            ]
        );
        if (!$patient->hasRole('patient')) {
            $patient->assignRole('patient');
        }

        $this->command->info('✅ Admin i pacijent korisnici kreirani');

        // Seed master data
        $this->call([
            CitiesSeeder::class,
            SpecialtiesSeeder::class,
            ClinicsSeeder::class,
            DoctorsSeeder::class,

            // Laboratory system
            KategorijeAnalizaSeeder::class,
            LaboratorijeSeeder::class,

            // Spa/Rehabilitation system
            VrsteBanjaSeeder::class,
            IndikacijeSeeder::class,
            TerapijeSeeder::class,
            BanjeSeeder::class,

            // Care Homes system
            TipoviDomovaSeeder::class,
            NivoiNjegeSeeder::class,
            ProgramiNjegeSeeder::class,
            MedicinskUslugaSeeder::class,
            SmjestajUsloviSeeder::class,
            DomoviSeeder::class,

            // Q&A system
            PitanjaSeeder::class,

            // MKB-10 (ICD-10) system
            Mkb10KategorijeSeeder::class,
            Mkb10DijagnozeSeeder::class,
        ]);

        // Create test users for each entity type AFTER seeders
        $this->createTestUsers();

        $this->command->info('✅ Database seeding completed successfully!');
    }

    /**
     * Create test users for each entity type
     */
    private function createTestUsers(): void
    {
        $this->command->info('🔧 Creating test users for entities...');

        // 1. Test Clinic Manager
        $clinicUser = User::firstOrCreate(
            ['email' => 'clinic@example.com'],
            [
                'name' => 'Clinic Manager',
                'ime' => 'Clinic',
                'prezime' => 'Manager',
                'password' => bcrypt('ClinicPassword123!'),
                'role' => 'clinic',
                'email_verified_at' => now(),
            ]
        );
        if (!$clinicUser->hasRole('clinic')) {
            $clinicUser->assignRole('clinic');
        }

        // Link clinic user to first clinic (KCUS)
        $firstClinic = \App\Models\Klinika::first();
        if ($firstClinic && !$firstClinic->user_id) {
            $firstClinic->update(['user_id' => $clinicUser->id]);
            $this->command->info('  ✓ Clinic user linked to: ' . $firstClinic->naziv);
        }
        $this->command->info('  ✓ Clinic user created (clinic@example.com)');

        // 2. Test Laboratory Manager
        $labUser = User::firstOrCreate(
            ['email' => 'lab@example.com'],
            [
                'name' => 'Laboratory Manager',
                'ime' => 'Laboratory',
                'prezime' => 'Manager',
                'password' => bcrypt('LabPassword123!'),
                'role' => 'laboratory',
                'email_verified_at' => now(),
            ]
        );
        if (!$labUser->hasRole('laboratory')) {
            $labUser->assignRole('laboratory');
        }

        // Link to first laboratory
        $firstLab = \App\Models\Laboratorija::where('slug', 'laboratorija-sarajevo')->first();
        if ($firstLab && $firstLab->user_id !== $labUser->id) {
            $firstLab->update(['user_id' => $labUser->id]);
            $this->command->info('  ✓ Laboratory user linked to Laboratorija Sarajevo');
        }

        // 3. Test Spa Manager
        $spaUser = User::firstOrCreate(
            ['email' => 'spa@example.com'],
            [
                'name' => 'Spa Manager',
                'ime' => 'Spa',
                'prezime' => 'Manager',
                'password' => bcrypt('SpaPassword123!'),
                'role' => 'spa_manager',
                'email_verified_at' => now(),
            ]
        );
        if (!$spaUser->hasRole('spa_manager')) {
            $spaUser->assignRole('spa_manager');
        }

        // Link to first spa (Banja Vrućica)
        $firstSpa = \App\Models\Banja::where('slug', 'banja-vrucica')->first();
        if ($firstSpa && $firstSpa->user_id !== $spaUser->id) {
            $firstSpa->update(['user_id' => $spaUser->id]);
            $this->command->info('  ✓ Spa user linked to Banja Vrućica');
        }

        // 4. Test Care Home Manager
        $domUser = User::firstOrCreate(
            ['email' => 'dom@example.com'],
            [
                'name' => 'Care Home Manager',
                'ime' => 'Dom',
                'prezime' => 'Manager',
                'password' => bcrypt('DomPassword123!'),
                'role' => 'dom_manager',
                'email_verified_at' => now(),
            ]
        );
        if (!$domUser->hasRole('dom_manager')) {
            $domUser->assignRole('dom_manager');
        }

        // Link to first care home (Dom za starije "Sunce")
        $firstDom = \App\Models\Dom::where('slug', 'dom-za-starije-sunce')->first();
        if ($firstDom && $firstDom->user_id !== $domUser->id) {
            $firstDom->update(['user_id' => $domUser->id]);
            $this->command->info('  ✓ Care Home user linked to Dom za starije "Sunce"');
        }

        $this->command->newLine();
        $this->command->info('📋 Test User Credentials:');
        $this->command->table(
            ['Role', 'Email', 'Password', 'Entity'],
            [
                ['Admin', 'admin@wizmedik.com', 'AdminPassword123!', 'N/A'],
                ['Patient', 'patient@example.com', 'PatientPassword123!', 'N/A'],
                ['Doctor', 'amir.hodzic@example.com', 'TestPassword123!', 'Amir Hodžić'],
                ['Clinic', 'clinic@example.com', 'ClinicPassword123!', 'KCUS'],
                ['Laboratory', 'lab@example.com', 'LabPassword123!', 'Laboratorija Sarajevo'],
                ['Spa', 'spa@example.com', 'SpaPassword123!', 'Banja Vrućica'],
                ['Care Home', 'dom@example.com', 'DomPassword123!', 'Dom za starije "Sunce"'],
            ]
        );
    }
}
