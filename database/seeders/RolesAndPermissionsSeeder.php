<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles (or get existing)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $doctorRole = Role::firstOrCreate(['name' => 'doctor']);
        $clinicRole = Role::firstOrCreate(['name' => 'clinic']);
        $laboratoryRole = Role::firstOrCreate(['name' => 'laboratory']);
        $spaManagerRole = Role::firstOrCreate(['name' => 'spa_manager']);
        $domManagerRole = Role::firstOrCreate(['name' => 'dom_manager']);
        $patientRole = Role::firstOrCreate(['name' => 'patient']);

        // Create permissions (or get existing)
        $permissions = [
            'manage users',
            'manage doctors',
            'manage clinics',
            'manage laboratories',
            'manage cities',
            'manage specialties',
            'view appointments',
            'create appointments',
            'update appointments',
            'delete appointments',
            'manage own profile',
            'manage clinic profile',
            'manage laboratory profile',
            'manage spa profile',
            'manage dom profile',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

        $doctorRole->givePermissionTo([
            'view appointments',
            'create appointments',
            'update appointments',
            'manage own profile',
        ]);

        $clinicRole->givePermissionTo([
            'view appointments',
            'create appointments',
            'update appointments',
            'manage clinic profile',
        ]);

        $laboratoryRole->givePermissionTo([
            'manage laboratory profile',
            'manage own profile',
        ]);

        $spaManagerRole->givePermissionTo([
            'manage spa profile',
            'manage own profile',
        ]);

        $domManagerRole->givePermissionTo([
            'manage dom profile',
            'manage own profile',
        ]);

        $patientRole->givePermissionTo([
            'view appointments',
            'create appointments',
            'update appointments',
            'manage own profile',
        ]);
    }
}
