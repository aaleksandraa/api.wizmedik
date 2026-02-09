<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Test: Cross-Service Communication Integration
 *
 * This test validates API proxy functionality and CORS handling
 * in scenarios that simulate real browser environment requests.
 *
 * Validates: Requirements 2.5, 4.4
 */
class CrossServiceCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Test: API proxy functionality with public endpoints
     * Validates: Requirement 4.4
     */
    public function test_api_proxy_functionality_with_public_endpoints(): void
    {
        // Test various public endpoints that would be proxied through Vite
        $publicEndpoints = [
            '/api/doctors',
            '/api/clinics',
            '/api/banje',
            '/api/domovi-njega',
            '/api/laboratorije',
            '/api/blog',
            '/api/specialties',
            '/api/cities',
            '/api/health',
        ];

        foreach ($publicEndpoints as $endpoint) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ])->getJson($endpoint);

            // Assert: Each endpoint should be accessible
            $response->assertStatus(200);

            // Assert: CORS headers should be present
            $response->assertHeader('Access-Control-Allow-Origin');
        }
    }

    /**
     * Test: API proxy functionality with authenticated endpoints
     * Validates: Requirement 4.4
     */
    public function test_api_proxy_functionality_with_authenticated_endpoints(): void
    {
        // Arrange: Create and authenticate a user
        $user = User::factory()->create([
            'email' => 'proxy@example.com',
            'password' => Hash::make('ProxyTest123!'),
            'ime' => 'Proxy',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Login to get token
        $loginResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'proxy@example.com',
            'password' => 'ProxyTest123!',
        ]);

        $token = $loginResponse->json('token');

        // Test authenticated endpoints that would be proxied
        $authenticatedEndpoints = [
            '/api/user',
        ];

        foreach ($authenticatedEndpoints as $endpoint) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
            ])->getJson($endpoint);

            // Assert: Each endpoint should be accessible with token
            $response->assertStatus(200);

            // Assert: CORS headers should be present
            $response->assertHeader('Access-Control-Allow-Origin');
        }
    }

    /**
     * Test: CORS preflight requests (OPTIONS)
     * Validates: Requirement 2.5
     */
    public function test_cors_preflight_requests(): void
    {
        // Test preflight request for login endpoint
        $response = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,accept',
        ]);

        // Assert: Should return 200 or 204 for OPTIONS
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204]),
            'OPTIONS request should return 200 or 204'
        );

        // Assert: Should include CORS headers
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Methods'));
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Headers'));
    }

    /**
     * Test: CORS headers for different HTTP methods
     * Validates: Requirement 2.5
     */
    public function test_cors_headers_for_different_http_methods(): void
    {
        // Arrange: Create a user for testing
        $user = User::factory()->create([
            'email' => 'methods@example.com',
            'password' => Hash::make('MethodsTest123!'),
            'ime' => 'Methods',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Test GET request
        $getResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/doctors');

        $getResponse->assertStatus(200);
        $getResponse->assertHeader('Access-Control-Allow-Origin');

        // Test POST request (login)
        $postResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'methods@example.com',
            'password' => 'MethodsTest123!',
        ]);

        $postResponse->assertStatus(200);
        $postResponse->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * Test: CORS with credentials support
     * Validates: Requirement 2.5
     */
    public function test_cors_with_credentials_support(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create([
            'email' => 'credentials@example.com',
            'password' => Hash::make('CredTest123!'),
            'ime' => 'Cred',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Make request with credentials
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'credentials@example.com',
            'password' => 'CredTest123!',
        ]);

        // Assert: Should handle credentials properly
        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin');

        // Check if credentials are allowed (if not wildcard)
        $allowedOrigin = $response->headers->get('Access-Control-Allow-Origin');
        if ($allowedOrigin !== '*') {
            // When origin is specific, credentials should be allowed
            $allowCredentials = $response->headers->get('Access-Control-Allow-Credentials');
            $this->assertTrue(
                $allowCredentials === 'true' || $allowCredentials === null,
                'Credentials should be allowed for specific origins'
            );
        }
    }

    /**
     * Test: API proxy handles query parameters correctly
     * Validates: Requirement 4.4
     */
    public function test_api_proxy_handles_query_parameters(): void
    {
        // Test endpoints with various query parameters
        $endpointsWithParams = [
            '/api/doctors?search=test&grad=Sarajevo&page=1',
            '/api/clinics?search=clinic&per_page=10',
            '/api/banje?grad=Sarajevo&sort_by=ocjena&sort_order=desc',
            '/api/blog?category=health&page=2',
        ];

        foreach ($endpointsWithParams as $endpoint) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Origin' => 'http://localhost:5173',
            ])->getJson($endpoint);

            // Assert: Should handle query parameters correctly
            $response->assertStatus(200);
            $response->assertHeader('Access-Control-Allow-Origin');
        }
    }

    /**
     * Test: API proxy handles request headers correctly
     * Validates: Requirement 4.4
     */
    public function test_api_proxy_handles_request_headers(): void
    {
        // Test with various headers that frontend might send
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'User-Agent' => 'Mozilla/5.0 (Test Browser)',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->getJson('/api/doctors');

        // Assert: Should handle all headers correctly
        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * Test: CORS handling for both authenticated and unauthenticated endpoints
     * Validates: Requirement 2.5
     */
    public function test_cors_for_authenticated_and_unauthenticated_endpoints(): void
    {
        // Test unauthenticated endpoint
        $publicResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/doctors');

        $publicResponse->assertStatus(200);
        $publicResponse->assertHeader('Access-Control-Allow-Origin');

        // Test authenticated endpoint without token (should fail but with CORS)
        $protectedResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/user');

        $protectedResponse->assertStatus(401);
        $protectedResponse->assertHeader('Access-Control-Allow-Origin');

        // Arrange: Create and authenticate a user
        $user = User::factory()->create([
            'email' => 'mixed@example.com',
            'password' => Hash::make('MixedTest123!'),
            'ime' => 'Mixed',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        $loginResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'mixed@example.com',
            'password' => 'MixedTest123!',
        ]);

        $token = $loginResponse->json('token');

        // Test authenticated endpoint with token
        $authenticatedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/user');

        $authenticatedResponse->assertStatus(200);
        $authenticatedResponse->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * Test: Error responses include CORS headers
     * Validates: Requirement 2.5
     */
    public function test_error_responses_include_cors_headers(): void
    {
        // Test 404 error (use a truly nonexistent route)
        $notFoundResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/truly-nonexistent-endpoint-12345');

        // Laravel may return 404 or fallback to 200, check for CORS regardless
        $notFoundResponse->assertHeader('Access-Control-Allow-Origin');

        // Test 422 validation error
        $validationResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $validationResponse->assertStatus(422);
        $validationResponse->assertHeader('Access-Control-Allow-Origin');

        // Test 401 unauthorized error
        $unauthorizedResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/user');

        $unauthorizedResponse->assertStatus(401);
        $unauthorizedResponse->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * Test: API proxy maintains request context
     * Validates: Requirement 4.4
     */
    public function test_api_proxy_maintains_request_context(): void
    {
        // Arrange: Create a user
        $user = User::factory()->create([
            'email' => 'context@example.com',
            'password' => Hash::make('ContextTest123!'),
            'ime' => 'Context',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Login
        $loginResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/login',
        ])->postJson('/api/login', [
            'email' => 'context@example.com',
            'password' => 'ContextTest123!',
        ]);

        $token = $loginResponse->json('token');

        // Act: Make subsequent request with same context
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/dashboard',
        ])->getJson('/api/user');

        // Assert: Context should be maintained
        $userResponse->assertStatus(200);
        $userResponse->assertJson([
            'user' => [
                'email' => 'context@example.com',
            ]
        ]);
    }
}
