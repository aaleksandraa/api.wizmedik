<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Test: Complete Authentication Flow Integration
 *
 * This test validates the end-to-end authentication flow from frontend to backend,
 * testing both successful and failed authentication scenarios.
 *
 * Validates: Requirements 2.1, 2.2, 2.4
 */
class AuthenticationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Test: Complete successful authentication flow
     * Validates: Requirement 2.1
     */
    public function test_complete_successful_authentication_flow(): void
    {
        // Arrange: Create a test user with proper role
        $user = User::factory()->create([
            'email' => 'integration@example.com',
            'password' => Hash::make('IntegrationTest123!'),
            'ime' => 'Integration',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Perform login request (simulating frontend request)
        $loginResponse = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'integration@example.com',
            'password' => 'IntegrationTest123!',
        ]);

        // Assert: Login should succeed
        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'ime',
                    'prezime',
                    'email',
                    'role',
                ],
                'token'
            ]);

        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token);

        // Act: Use the token to access protected endpoint
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/user');

        // Assert: Should be able to access protected resource
        $userResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'email' => 'integration@example.com',
                ]
            ]);

        // Verify token works for multiple requests
        $secondUserResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/user');

        // Assert: Token should work consistently
        $secondUserResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'email' => 'integration@example.com',
                ]
            ]);
    }

    /**
     * Test: Failed authentication with invalid credentials
     * Validates: Requirement 2.2
     */
    public function test_failed_authentication_with_invalid_credentials(): void
    {
        // Act: Attempt login with non-existent user
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongPassword123!',
        ]);

        // Assert: Should return 422 with clear error message
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email'
                ]
            ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('email', $responseData['errors']);
        $this->assertEquals(['Neispravni pristupni podaci.'], $responseData['errors']['email']);
    }

    /**
     * Test: Failed authentication with wrong password
     * Validates: Requirement 2.2, 2.4
     */
    public function test_failed_authentication_with_wrong_password(): void
    {
        // Arrange: Create a test user
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'ime' => 'Test',
            'prezime' => 'User',
        ]);
        $user->assignRole('patient');

        // Act: Attempt login with wrong password
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'WrongPassword123!',
        ]);

        // Assert: Should return 422 with error message
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email'
                ]
            ]);

        // Verify no sensitive information is exposed
        $responseJson = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($responseJson));
        $this->assertStringNotContainsString('hash', strtolower($responseJson));
        $this->assertStringNotContainsString('database', strtolower($responseJson));
    }

    /**
     * Test: Failed authentication with invalid email format
     * Validates: Requirement 2.2
     */
    public function test_failed_authentication_with_invalid_email_format(): void
    {
        // Act: Attempt login with invalid email format
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'SomePassword123!',
        ]);

        // Assert: Should return 422 with validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Failed authentication with missing credentials
     * Validates: Requirement 2.2
     */
    public function test_failed_authentication_with_missing_credentials(): void
    {
        // Act: Attempt login with missing email
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'password' => 'SomePassword123!',
        ]);

        // Assert: Should return 422 with validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Act: Attempt login with missing password
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        // Assert: Should return 422 with validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test: Authentication flow with CORS headers
     * Validates: Requirement 2.1 (CORS handling)
     */
    public function test_authentication_flow_includes_cors_headers(): void
    {
        // Arrange: Create a test user
        $user = User::factory()->create([
            'email' => 'cors@example.com',
            'password' => Hash::make('CorsTest123!'),
            'ime' => 'CORS',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Perform login with Origin header
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'cors@example.com',
            'password' => 'CorsTest123!',
        ]);

        // Assert: Should include CORS headers
        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin');

        // Verify the origin is allowed
        $allowedOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $this->assertTrue(
            $allowedOrigin === 'http://localhost:5173' || $allowedOrigin === '*',
            'localhost:5173 should be allowed as origin'
        );
    }

    /**
     * Test: Multiple failed login attempts
     * Validates: Requirement 2.4 (error messages without sensitive info)
     */
    public function test_multiple_failed_login_attempts(): void
    {
        // Arrange: Create a test user
        $user = User::factory()->create([
            'email' => 'multitest@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'ime' => 'Multi',
            'prezime' => 'Test',
        ]);
        $user->assignRole('patient');

        // Act: Attempt multiple failed logins
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeaders([
                'Accept' => 'application/json',
                'Origin' => 'http://localhost:5173',
            ])->postJson('/api/login', [
                'email' => 'multitest@example.com',
                'password' => 'WrongPassword' . $i,
            ]);

            // Assert: Each attempt should return consistent error
            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => ['email']
                ]);
        }

        // Act: Finally attempt with correct password
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:5173',
        ])->postJson('/api/login', [
            'email' => 'multitest@example.com',
            'password' => 'CorrectPassword123!',
        ]);

        // Assert: Should succeed with correct credentials
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token'
            ]);
    }
}
