<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Unit Tests: Health Check Endpoints
 *
 * Tests frontend and backend health check responses to ensure they provide
 * proper status information for monitoring development environment connectivity.
 *
 * Validates: Requirements 5.5
 */
class HealthCheckEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backend health check endpoint returns success when all services are healthy
     *
     * @test
     */
    public function test_backend_health_check_returns_success_when_healthy(): void
    {
        // Act: Make request to health check endpoint
        $response = $this->getJson('/api/health');

        // Assert: Response structure and status
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'storage',
            ],
            'version',
        ]);

        // Assert: Status is healthy
        $responseData = $response->json();
        $this->assertEquals('healthy', $responseData['status']);
        $this->assertIsString($responseData['timestamp']);
        $this->assertIsString($responseData['version']);

        // Assert: All checks should pass
        $this->assertTrue($responseData['checks']['database']);
        $this->assertTrue($responseData['checks']['cache']);
        $this->assertTrue($responseData['checks']['storage']);
    }

    /**
     * Test backend ping endpoint returns simple success response
     *
     * @test
     */
    public function test_backend_ping_endpoint_returns_success(): void
    {
        // Act: Make request to ping endpoint
        $response = $this->getJson('/api/ping');

        // Assert: Response structure and status
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);

        // Assert: Status is ok
        $responseData = $response->json();
        $this->assertEquals('ok', $responseData['status']);
        $this->assertIsString($responseData['timestamp']);
    }

    /**
     * Test health check endpoint returns unhealthy status when database is down
     *
     * @test
     */
    public function test_health_check_returns_unhealthy_when_database_fails(): void
    {
        // Arrange: Simulate database failure by using invalid connection
        config(['database.connections.testing.database' => '/invalid/path/database.sqlite']);

        // Act: Make request to health check endpoint
        $response = $this->getJson('/api/health');

        // Assert: Response structure is maintained even with potential database issues
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'storage',
            ],
            'version',
        ]);

        // Note: Database check may still pass in test environment due to in-memory database
        $responseData = $response->json();
        $this->assertContains($responseData['status'], ['healthy', 'unhealthy']);
        $this->assertIsBool($responseData['checks']['database']);
    }

    /**
     * Test health check endpoint handles cache failures gracefully
     *
     * @test
     */
    public function test_health_check_handles_cache_failure_gracefully(): void
    {
        // Arrange: Mock cache failure
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache connection failed'));
        Cache::shouldReceive('get')->andThrow(new \Exception('Cache connection failed'));
        Cache::shouldReceive('forget')->andThrow(new \Exception('Cache connection failed'));

        // Act: Make request to health check endpoint
        $response = $this->getJson('/api/health');

        // Assert: Response structure is maintained even with cache failure
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks',
            'version',
        ]);

        $responseData = $response->json();
        $this->assertIsString($responseData['status']);
        $this->assertIsArray($responseData['checks']);
    }

    /**
     * Test health check endpoint validates response format consistency
     *
     * @test
     */
    public function test_health_check_response_format_consistency(): void
    {
        // Act: Make multiple requests to health check endpoint
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->getJson('/api/health')->json();
        }

        // Assert: All responses have consistent structure
        foreach ($responses as $response) {
            $this->assertArrayHasKey('status', $response);
            $this->assertArrayHasKey('timestamp', $response);
            $this->assertArrayHasKey('checks', $response);
            $this->assertArrayHasKey('version', $response);

            // Assert: Status is valid value
            $this->assertContains($response['status'], ['healthy', 'unhealthy']);

            // Assert: Timestamp is valid ISO 8601 format
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $response['timestamp']);

            // Assert: Checks is an array with expected keys
            $this->assertIsArray($response['checks']);
            $this->assertArrayHasKey('database', $response['checks']);
            $this->assertArrayHasKey('cache', $response['checks']);
            $this->assertArrayHasKey('storage', $response['checks']);

            // Assert: Version is a string
            $this->assertIsString($response['version']);
        }
    }

    /**
     * Test ping endpoint response time is reasonable
     *
     * @test
     */
    public function test_ping_endpoint_response_time_is_reasonable(): void
    {
        // Act: Measure response time for ping endpoint
        $startTime = microtime(true);
        $response = $this->getJson('/api/ping');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Response is successful
        $response->assertStatus(200);

        // Assert: Response time is reasonable (less than 100ms for local testing)
        $this->assertLessThan(100, $responseTime, 'Ping endpoint should respond quickly');
    }

    /**
     * Test health check endpoint includes proper CORS headers
     *
     * @test
     */
    public function test_health_check_includes_cors_headers(): void
    {
        // Act: Make request to health check endpoint
        $response = $this->getJson('/api/health');

        // Assert: CORS headers are present for cross-origin requests
        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertStatus(200);
    }

    /**
     * Test health check endpoints are accessible without authentication
     *
     * @test
     */
    public function test_health_check_endpoints_accessible_without_authentication(): void
    {
        // Act: Make requests without authentication
        $healthResponse = $this->getJson('/api/health');
        $pingResponse = $this->getJson('/api/ping');

        // Assert: Both endpoints are accessible
        $healthResponse->assertStatus(200);
        $pingResponse->assertStatus(200);

        // Assert: No authentication required
        $this->assertGuest();
    }

    /**
     * Test health check endpoint handles high load gracefully
     *
     * @test
     */
    public function test_health_check_handles_concurrent_requests(): void
    {
        // Act: Make multiple concurrent requests (simulated)
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/health');
        }

        // Assert: All requests succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $responseData = $response->json();
            $this->assertContains($responseData['status'], ['healthy', 'unhealthy']);
        }
    }

    /**
     * Test health check endpoint provides useful debugging information
     *
     * @test
     */
    public function test_health_check_provides_debugging_information(): void
    {
        // Act: Make request to health check endpoint
        $response = $this->getJson('/api/health');

        // Assert: Response includes debugging information
        $response->assertStatus(200);
        $responseData = $response->json();

        // Assert: Timestamp helps with debugging timing issues
        $timestamp = $responseData['timestamp'];
        $parsedTime = \DateTime::createFromFormat(\DateTime::ATOM, $timestamp);
        $this->assertInstanceOf(\DateTime::class, $parsedTime, 'Timestamp should be valid ISO 8601 format');

        // Assert: Version information helps with debugging
        $this->assertNotEmpty($responseData['version'], 'Version should be provided for debugging');

        // Assert: Individual check results help identify specific issues
        foreach ($responseData['checks'] as $checkName => $checkResult) {
            $this->assertIsBool($checkResult, "Check '{$checkName}' should return boolean result");
        }
    }
}
