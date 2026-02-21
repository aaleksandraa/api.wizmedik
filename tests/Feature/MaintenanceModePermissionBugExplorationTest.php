<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test for Maintenance Mode Permission Issue
 *
 * **Validates: Requirements 2.1, 2.2, 2.3**
 *
 * CRITICAL: This test is EXPECTED TO FAIL on unfixed code.
 * Failure confirms the bug exists (Permission denied when creating storage/framework/down).
 *
 * This test encodes the expected behavior:
 * - Plesk user should be able to create files in storage/framework/
 * - php artisan down should succeed
 * - storage/framework/down file should be created
 *
 * When this test passes after implementing the fix, it confirms the bug is resolved.
 */
class MaintenanceModePermissionBugExplorationTest extends TestCase
{
    protected string $testFilePath;
    protected string $downFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testFilePath = storage_path('framework/test.txt');
        $this->downFilePath = storage_path('framework/down');

        // Clean up any existing test files
        if (File::exists($this->testFilePath)) {
            @unlink($this->testFilePath);
        }
        if (File::exists($this->downFilePath)) {
            @unlink($this->downFilePath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testFilePath)) {
            @unlink($this->testFilePath);
        }

        // Ensure application is not in maintenance mode after test
        if (File::exists($this->downFilePath)) {
            Artisan::call('up');
        }

        parent::tearDown();
    }

    /**
     * Test that Plesk user can create file in storage/framework/
     *
     * Bug Condition: Plesk user attempting to create files in storage/framework/
     * when directory lacks 775 permissions or plesk_user:psacln ownership
     *
     * Expected on UNFIXED code: FAILS with "Permission denied"
     * Expected on FIXED code: PASSES - file is created successfully
     */
    public function test_can_create_file_in_storage_framework(): void
    {
        // Attempt to create a test file in storage/framework/
        $result = @file_put_contents($this->testFilePath, 'test content');

        // Document current state for debugging
        $frameworkPath = storage_path('framework');
        $permissions = substr(sprintf('%o', fileperms($frameworkPath)), -4);
        $owner = function_exists('posix_getpwuid') && function_exists('fileowner')
            ? posix_getpwuid(fileowner($frameworkPath))['name'] ?? 'unknown'
            : 'unknown';
        $group = function_exists('posix_getgrgid') && function_exists('filegroup')
            ? posix_getgrgid(filegroup($frameworkPath))['name'] ?? 'unknown'
            : 'unknown';

        $this->assertNotFalse(
            $result,
            "Failed to create file in storage/framework/. " .
            "Current permissions: {$permissions}, Owner: {$owner}:{$group}. " .
            "Expected: 775 permissions with plesk_user:psacln ownership. " .
            "This confirms the bug exists - Permission denied for file creation."
        );

        $this->assertFileExists(
            $this->testFilePath,
            "Test file was not created in storage/framework/. Bug confirmed."
        );
    }

    /**
     * Test that php artisan down succeeds
     *
     * Bug Condition: php artisan down fails when trying to create storage/framework/down
     * due to insufficient permissions
     *
     * Expected on UNFIXED code: FAILS with "Failed to enter maintenance mode: Permission denied"
     * Expected on FIXED code: PASSES - maintenance mode is activated successfully
     */
    public function test_artisan_down_succeeds(): void
    {
        // Attempt to enter maintenance mode
        $exitCode = Artisan::call('down');

        // Document current state for debugging
        $frameworkPath = storage_path('framework');
        $permissions = substr(sprintf('%o', fileperms($frameworkPath)), -4);

        $this->assertEquals(
            0,
            $exitCode,
            "php artisan down failed with exit code {$exitCode}. " .
            "Current storage/framework/ permissions: {$permissions}. " .
            "Expected: 775 permissions. " .
            "This confirms the bug exists - Cannot enter maintenance mode due to permission denied."
        );

        $this->assertFileExists(
            $this->downFilePath,
            "storage/framework/down file was not created. " .
            "Maintenance mode failed to activate. Bug confirmed."
        );
    }

    /**
     * Test that php artisan up succeeds (cleanup test)
     *
     * This test verifies that we can exit maintenance mode after entering it.
     * This is part of the expected behavior validation.
     */
    public function test_artisan_up_succeeds_after_down(): void
    {
        // First enter maintenance mode
        $downExitCode = Artisan::call('down');

        $this->assertEquals(
            0,
            $downExitCode,
            "Cannot test artisan up because artisan down failed. " .
            "This is expected on unfixed code."
        );

        // Then exit maintenance mode
        $upExitCode = Artisan::call('up');

        $this->assertEquals(
            0,
            $upExitCode,
            "php artisan up failed with exit code {$upExitCode}. " .
            "Cannot exit maintenance mode."
        );

        $this->assertFileDoesNotExist(
            $this->downFilePath,
            "storage/framework/down file still exists after artisan up. " .
            "Maintenance mode did not deactivate properly."
        );
    }

    /**
     * Test subdirectory permissions
     *
     * Verify that subdirectories within storage/framework/ also have correct permissions
     * This is important because the bug might affect nested directories too.
     */
    public function test_subdirectories_have_correct_permissions(): void
    {
        $subdirectories = [
            'cache',
            'sessions',
            'views',
            'testing'
        ];

        $frameworkPath = storage_path('framework');
        $issues = [];

        foreach ($subdirectories as $subdir) {
            $path = $frameworkPath . DIRECTORY_SEPARATOR . $subdir;

            if (!is_dir($path)) {
                continue;
            }

            $permissions = substr(sprintf('%o', fileperms($path)), -4);
            $owner = function_exists('posix_getpwuid') && function_exists('fileowner')
                ? posix_getpwuid(fileowner($path))['name'] ?? 'unknown'
                : 'unknown';
            $group = function_exists('posix_getgrgid') && function_exists('filegroup')
                ? posix_getgrgid(filegroup($path))['name'] ?? 'unknown'
                : 'unknown';

            // Try to create a test file in the subdirectory
            $testFile = $path . DIRECTORY_SEPARATOR . 'permission_test.txt';
            $canWrite = @file_put_contents($testFile, 'test');

            if ($canWrite === false) {
                $issues[] = "{$subdir}: Cannot write (permissions: {$permissions}, owner: {$owner}:{$group})";
            } else {
                @unlink($testFile);
            }
        }

        $this->assertEmpty(
            $issues,
            "Permission issues found in subdirectories:\n" . implode("\n", $issues) .
            "\nExpected: All subdirectories should have 775 permissions with plesk_user:psacln ownership."
        );
    }
}
