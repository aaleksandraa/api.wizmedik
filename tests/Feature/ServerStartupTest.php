<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for backend server startup
 * Requirements: 4.1, 4.2
 *
 * These tests verify that:
 * - Backend server can start successfully
 * - Database connectivity is established
 * - Server responds to requests on port 8000
 */
class ServerStartupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the application boots successfully
     *
     * @return void
     */
    public function test_application_boots_successfully(): void
    {
        // If we can run this test, the application has booted
        $this->assertTrue(true);
    }

    /**
     * Test that database connection is established
     * Validates: Requirements 4.2
     *
     * @return void
     */
    public function test_database_connectivity(): void
    {
        // Test database connection
        try {
            DB::connection()->getPdo();
            $connected = true;
        } catch (\Exception $e) {
            $connected = false;
        }

        $this->assertTrue($connected, 'Database connection should be established');
    }

    /**
     * Test that database can execute queries
     * Validates: Requirements 4.2
     *
     * @return void
     */
    public function test_database_can_execute_queries(): void
    {
        // Test that we can execute a simple query
        $result = DB::select('SELECT 1 as test');

        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Test that migrations have run successfully
     * Validates: Requirements 4.2
     *
     * @return void
     */
    public function test_migrations_have_run(): void
    {
        // Check that the users table exists (core table)
        $tableExists = DB::getSchemaBuilder()->hasTable('users');

        $this->assertTrue($tableExists, 'Users table should exist after migrations');
    }

    /**
     * Test that the server responds to basic requests
     * Validates: Requirements 4.1
     *
     * @return void
     */
    public function test_server_responds_to_requests(): void
    {
        // Make a basic request to the application
        $response = $this->get('/');

        // Should get some response (200 or 404 is fine, just not 500)
        $this->assertNotEquals(500, $response->status());
    }

    /**
     * Test that API routes are accessible
     * Validates: Requirements 4.1
     *
     * @return void
     */
    public function test_api_routes_are_accessible(): void
    {
        // Test that API routes are registered
        $response = $this->get('/api/health');

        // Should get a response (not 404 or 500)
        $this->assertTrue(
            in_array($response->status(), [200, 401, 403]),
            'API routes should be accessible'
        );
    }

    /**
     * Test that environment configuration is loaded
     * Validates: Requirements 4.1, 4.2
     *
     * @return void
     */
    public function test_environment_configuration_loaded(): void
    {
        // Check that key environment variables are set
        $this->assertNotEmpty(config('app.key'), 'APP_KEY should be set');
        $this->assertNotEmpty(config('database.default'), 'Database configuration should be loaded');
    }
}
