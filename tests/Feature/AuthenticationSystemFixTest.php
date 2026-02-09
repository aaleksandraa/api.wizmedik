<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Test: Authentication System Fix
 *
 * Validates that the authentication system meets all requirements:
 * - Requirements 2.1, 2.2, 2.3, 2.4
 */
class AuthenticationSystemFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Test: Valid credentials return successful response with authentication tokens
     * Validates: Requirement 2.1
     */
    public function test_valid_credentials_return_successful_response_with_tokens(): void
    {
        // Arrange: Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestPassword123!'),
            'ime' => 'Test',
            'prezime' => 'User',
        ]);
        $user->assignRole('patient');

        // Act: Submit valid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
        ]);

        // Assert: Should return successful response with token
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'ime',
                    'prezime',
                    'email',
                    'telefon',
                    'grad',
                    'role',
                ],
                'token'
            ]);

        $responseData = $response->json();
        $this->assertEquals('Prijava uspješna', $responseData['message']);
        $this->assertNotEmpty($responseData['token']);
        $this->assertEquals('test@example.com', $responseData['user']['email']);
    }

    /**
     * Test: Invalid credentials return 422 status with clear error messages
     * Validates: Requirement 2.2
     */
    public function test_invalid_credentials_return_422_with_clear_error_messages(): void
    {
        // Act: Submit invalid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert: Should return 422 with error message
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
     * Test: Email format and password validation before processing
     * Validates: Requirement 2.3
     */
    public function test_email_format_and_password_validation_before_processing(): void
    {
        // Test invalid email format
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'somepassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test missing password
        $response = $this->postJson('/api/login', [
            'email' => 'valid@example.com',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Test missing email
        $response = $this->postJson('/api/login', [
            'email' => '',
            'password' => 'somepassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Authentication failures provide specific error messages without exposing sensitive information
     * Validates: Requirement 2.4
     */
    public function test_authentication_failures_provide_specific_errors_without_sensitive_info(): void
    {
        // Act: Submit invalid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert: Should return specific error without sensitive information
        $response->assertStatus(422);

        $responseJson = $response->getContent();

        // Should not expose sensitive information
        $this->assertStringNotContainsString('password', strtolower($responseJson));
        $this->assertStringNotContainsString('hash', strtolower($responseJson));
        $this->assertStringNotContainsString('database', strtolower($responseJson));
        $this->assertStringNotContainsString('sql', strtolower($responseJson));
        $this->assertStringNotContainsString('exception', strtolower($responseJson));
        $this->assertStringNotContainsString('stack', strtolower($responseJson));

        // Should provide clear error message
        $responseData = $response->json();
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('email', $responseData['errors']);
        $this->assertEquals(['Neispravni pristupni podaci.'], $responseData['errors']['email']);
    }

    /**
     * Test: Database seeding creates proper test users
     * Validates: Task requirement for database seeding verification
     */
    public function test_database_seeding_creates_proper_test_users(): void
    {
        // Run the database seeder
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);

        // Check that test users exist
        $admin = User::where('email', 'admin@wizmedik.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));

        $patient = User::where('email', 'patient@example.com')->first();
        $this->assertNotNull($patient);
        $this->assertTrue($patient->hasRole('patient'));

        // Test login with seeded patient user
        $response = $this->postJson('/api/login', [
            'email' => 'patient@example.com',
            'password' => 'PatientPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token'
            ]);
    }
}
