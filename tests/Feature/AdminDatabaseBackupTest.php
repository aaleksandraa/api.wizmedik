<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_guest_cannot_access_database_backup_status(): void
    {
        $this->getJson('/api/admin/database-backup/status')
            ->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_database_backup_status(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);
        $user->assignRole('patient');
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/database-backup/status')
            ->assertForbidden();
    }

    public function test_admin_can_view_database_backup_status(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-backup@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/database-backup/status');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'schedule' => ['command', 'time', 'timezone', 'cleanup_time', 'log_file'],
                'storage' => ['disk', 'backup_name', 'reachable'],
                'backups',
            ])
            ->assertJsonPath('schedule.time', '02:00')
            ->assertJsonPath('schedule.command', 'backup:run --only-db');
    }

    public function test_admin_cannot_download_invalid_backup_path(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-backup2@example.com',
            'password' => Hash::make('AdminPassword123!'),
        ]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/database-backup/download?path=' . urlencode(base64_encode('../../../etc/passwd')))
            ->assertNotFound();
    }
}
